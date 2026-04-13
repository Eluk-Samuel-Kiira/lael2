<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{ DB, Log };

class SyncToRemote extends Command
{
    protected $signature   = 'pos:sync';
    protected $description = 'Push pending change_log entries to remote MySQL — all tenants';

    public function handle(): void
    {
        // ── 1. Check remote connectivity once (not per tenant) ────────────────
        if (!$this->remoteIsReachable()) {
            // Mark ALL tenants offline and fire one notification
            $pendingTenants = DB::table('change_log')
                ->whereNull('synced_at')
                ->distinct()
                ->pluck('tenant_id');

            foreach ($pendingTenants as $tid) {
                $this->updateStatus($tid, null, 'offline', 0, 'Remote unreachable');
            }

            $total = DB::table('change_log')->whereNull('synced_at')->count();
            $this->notify('offline', "No connection — {$total} row(s) queued locally");
            return;
        }

        // ── 2. Get all tenant IDs that have pending rows ──────────────────────
        $tenantIds = DB::table('change_log')
            ->whereNull('synced_at')
            ->where('retry_count', '<', 5)
            ->distinct()
            ->pluck('tenant_id');

        if ($tenantIds->isEmpty()) {
            // Nothing pending — still mark as online so the badge stays green
            $this->updateStatus(current_tenant_id(), null, 'online', 0);
            return;
        }

        $remote          = DB::connection('mysql_remote');
        $totalPushed     = 0;
        $totalErrors     = 0;

        foreach ($tenantIds as $tenantId) {
            [$pushed, $errors] = $this->syncTenant($remote, $tenantId);
            $totalPushed += $pushed;
            $totalErrors += $errors;
        }

        // ── 3. Pull master/catalog data from remote → local ───────────────────
        $this->pullMasterData($remote);

        // ── 4. Windows toast notification ─────────────────────────────────────
        if ($totalErrors > 0) {
            $this->notify('error', "Sync finished with {$totalErrors} error(s). Pushed {$totalPushed} row(s).");
        } else {
            $this->notify('success', "Sync complete — pushed {$totalPushed} row(s) across " . count($tenantIds) . " tenant(s).");
        }
    }

    // ── Per-tenant sync ───────────────────────────────────────────────────────
    private function syncTenant($remote, int $tenantId): array
    {
        $this->updateStatus($tenantId, null, 'syncing', 0);

        $pending = DB::table('change_log')
            ->where('tenant_id', $tenantId)
            ->whereNull('synced_at')
            ->where('retry_count', '<', 5)
            ->orderBy('logged_at')
            ->limit(500)
            ->get();

        $pushed = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($pending as $entry) {
                try {
                    $payload = json_decode($entry->payload, true);

                    // Safety: never sync change_log or sync_status themselves
                    if (in_array($entry->table_name, ['change_log', 'sync_status'])) {
                        DB::table('change_log')->where('id', $entry->id)
                          ->update(['synced_at' => now()]);
                        continue;
                    }

                    match ($entry->operation) {
                        'INSERT' => $remote->table($entry->table_name)
                                           ->insertOrIgnore($payload),

                        'UPDATE' => $remote->table($entry->table_name)
                                           ->where('id', $entry->row_id)
                                           ->where('tenant_id', $tenantId) // never touch another tenant
                                           ->update($payload),             // last-write-wins on updated_at

                        'DELETE' => $remote->table($entry->table_name)
                                           ->where('id', $entry->row_id)
                                           ->where('tenant_id', $tenantId)
                                           ->delete(),
                    };

                    DB::table('change_log')->where('id', $entry->id)
                      ->update(['synced_at' => now(), 'sync_error' => null]);

                    $pushed++;

                } catch (\Exception $e) {
                    DB::table('change_log')->where('id', $entry->id)
                      ->increment('retry_count', 1, [
                          'sync_error' => substr($e->getMessage(), 0, 500),
                      ]);

                    Log::error('Sync row failed', [
                        'tenant_id'     => $tenantId,
                        'change_log_id' => $entry->id,
                        'table'         => $entry->table_name,
                        'error'         => $e->getMessage(),
                    ]);
                    $errors++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sync transaction failed', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
            $this->updateStatus($tenantId, null, 'error', 0, $e->getMessage());
            return [0, 1];
        }

        $remaining = DB::table('change_log')
            ->where('tenant_id', $tenantId)
            ->whereNull('synced_at')
            ->count();

        $status = $errors > 0 ? 'error' : 'online';
        $this->updateStatus($tenantId, null, $status, $remaining, $errors > 0 ? "{$errors} row(s) failed" : null);

        Log::info("Tenant {$tenantId} sync — pushed: {$pushed}, errors: {$errors}, remaining: {$remaining}");

        return [$pushed, $errors];
    }

    // ── Pull master/catalog data ──────────────────────────────────────────────
    private function pullMasterData($remote): void
    {
        // Only pull lookup/catalog tables — NEVER pull transaction tables
        $masterTables = [
            'categories', 'product_categories', 'products',
            'taxes', 'promotions', 'unit_of_measures',
            'locations', 'departments',
        ];

        // Per-tenant last sync times
        $lastSyncTimes = DB::table('sync_status')
            ->whereNotNull('last_synced_at')
            ->pluck('last_synced_at', 'tenant_id');

        foreach ($masterTables as $table) {
            try {
                // Get the oldest last_synced_at across all tenants on this machine
                $since = $lastSyncTimes->min() ?? '2000-01-01 00:00:00';

                $remoteRows = $remote->table($table)
                    ->where('updated_at', '>', $since)
                    ->get()
                    ->toArray();

                foreach ($remoteRows as $row) {
                    // Only pull rows belonging to tenants on THIS machine
                    $localTenants = DB::table('sync_status')->pluck('tenant_id');
                    $row = (array) $row;

                    if (isset($row['tenant_id']) && !$localTenants->contains($row['tenant_id'])) {
                        continue; // don't pull other tenants' master data
                    }

                    DB::table($table)->upsert($row, ['id'], array_keys($row));
                }
            } catch (\Exception $e) {
                Log::warning("Pull failed for table {$table}: " . $e->getMessage());
            }
        }
    }

    // ── Remote connectivity check (TCP only — fast) ───────────────────────────
    private function remoteIsReachable(): bool
    {
        $host = config('database.connections.mysql_remote.host');
        $port = (int) config('database.connections.mysql_remote.port', 3306);

        try {
            $socket = @fsockopen($host, $port, $errno, $errstr, 5);
            if ($socket) {
                fclose($socket);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ── sync_status upsert ────────────────────────────────────────────────────
    private function updateStatus(
        int     $tenantId,
        ?int    $locationId,
        string  $status,
        int     $pendingCount,
        ?string $error = null
    ): void {
        DB::table('sync_status')->updateOrInsert(
            ['tenant_id' => $tenantId, 'location_id' => $locationId],
            [
                'status'         => $status,
                'last_synced_at' => $status === 'online' ? now() : DB::raw('last_synced_at'),
                'pending_count'  => $pendingCount,
                'last_error'     => $error,
                'updated_at'     => now(),
                'created_at'     => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
    }

    // ── Windows toast notification via PowerShell ─────────────────────────────
    private function notify(string $type, string $message): void
    {
        // Only attempt on Windows
        if (PHP_OS_FAMILY !== 'Windows') {
            return;
        }

        $icon = match ($type) {
            'success' => 'Info',
            'error'   => 'Error',
            default   => 'Warning',
        };

        $title   = addslashes('Stardena POS — Sync ' . ucfirst($type));
        $message = addslashes($message);

        // PowerShell BurntToast-style toast (works without extra modules on Win10+)
        $ps = <<<PS
        [Windows.UI.Notifications.ToastNotificationManager, Windows.UI.Notifications, ContentType = WindowsRuntime] | Out-Null
        [Windows.Data.Xml.Dom.XmlDocument, Windows.Data.Xml.Dom, ContentType = WindowsRuntime] | Out-Null
        \$template = @"
        <toast>
          <visual>
            <binding template="ToastGeneric">
              <text>Stardena POS Sync</text>
              <text>{$message}</text>
            </binding>
          </visual>
        </toast>
        "@
        \$xml = New-Object Windows.Data.Xml.Dom.XmlDocument
        \$xml.LoadXml(\$template)
        \$toast = [Windows.UI.Notifications.ToastNotification]::new(\$xml)
        [Windows.UI.Notifications.ToastNotificationManager]::CreateToastNotifier("Stardena POS").Show(\$toast)
        PS;

        $escaped = str_replace('"', '\\"', $ps);
        $cmd = "powershell -WindowStyle Hidden -Command \"{$escaped}\"";

        // Fire and forget — don't block the sync if notification fails
        pclose(popen("start /b {$cmd}", 'r'));
    }
}