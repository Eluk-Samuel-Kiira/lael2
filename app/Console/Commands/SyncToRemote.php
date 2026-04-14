<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{ DB, Log, Http };

class SyncToRemote extends Command
{
    protected $signature   = 'pos:sync {--tenant= : Override the tenant ID from config}';
    protected $description = 'Push pending change_log entries to remote server via HTTP API';

    private string $remoteUrl;
    private string $syncToken;
    private int    $tenantId;

    public function __construct()
    {
        parent::__construct();
        // ── FIX: use config() not env() — avoids .env JSON/special-char issues
        $this->remoteUrl  = rtrim(config('sync.remote_url', 'https://lael-pos.stardena.org'), '/');
        $this->syncToken  = config('sync.token', '');
        $this->tenantId   = (int) config('sync.tenant_id', 2);
    }

    public function handle(): void
    {
        // Allow --tenant flag to override config
        $tenantId = (int) ($this->option('tenant') ?? $this->tenantId);

        if (empty($this->syncToken)) {
            Log::error('pos:sync: SYNC_TOKEN not configured.');
            $this->error('SYNC_TOKEN not configured in .env / config/sync.php');
            return;
        }

        // ── 1. Check connectivity ─────────────────────────────────────────────
        if (!$this->remoteIsReachable($tenantId)) {
            $pending = DB::table('change_log')
                ->where('tenant_id', $tenantId)
                ->whereNull('synced_at')
                ->count();

            $this->updateStatus($tenantId, 'offline', $pending, 'Remote server unreachable');
            $this->notify('offline', "Offline — {$pending} row(s) queued");
            Log::info("pos:sync tenant#{$tenantId}: offline, {$pending} queued");
            return;
        }

        // ── 2. Fetch pending rows ─────────────────────────────────────────────
        $pending = DB::table('change_log')
            ->where('tenant_id', $tenantId)
            ->whereNull('synced_at')
            ->where('retry_count', '<', config('sync.max_retries', 5))
            ->orderBy('logged_at')
            ->limit(config('sync.batch_size', 500))
            ->get();

        if ($pending->isEmpty()) {
            $this->updateStatus($tenantId, 'online', 0);
            Log::info("pos:sync tenant#{$tenantId}: nothing pending.");
            return;
        }

        $this->updateStatus($tenantId, 'syncing', $pending->count());
        Log::info("pos:sync tenant#{$tenantId}: pushing {$pending->count()} rows");

        // ── 3. Push in a single batch (one HTTP call, not one per row) ─────────
        [$pushed, $errors] = $this->pushBatch($tenantId, $pending);

        // ── 4. Pull master data remote → local ────────────────────────────────
        $this->pullMasterData($tenantId);

        // ── 5. Update status + notify ─────────────────────────────────────────
        $remaining = DB::table('change_log')
            ->where('tenant_id', $tenantId)
            ->whereNull('synced_at')
            ->count();

        $status = $errors > 0 ? 'error' : 'online';
        $errMsg = $errors > 0 ? "{$errors} row(s) failed — check change_log.sync_error" : null;
        $this->updateStatus($tenantId, $status, $remaining, $errMsg);

        if ($errors > 0) {
            $this->notify('error', "Sync done with {$errors} error(s). Pushed {$pushed}.");
            Log::warning("pos:sync tenant#{$tenantId}: {$errors} errors, pushed {$pushed}, remaining {$remaining}");
        } else {
            $this->notify('success', "Synced {$pushed} row(s) for tenant #{$tenantId}");
            Log::info("pos:sync tenant#{$tenantId}: pushed {$pushed}, remaining {$remaining}");
        }
    }

    // ── Push entire batch in one request ─────────────────────────────────────
    private function pushBatch(int $tenantId, $rows): array
    {
        // Serialize rows for the API
        $payload = $rows->map(fn($r) => [
            'id'         => $r->id,
            'table_name' => $r->table_name,
            'row_id'     => $r->row_id,
            'operation'  => $r->operation,
            'payload'    => $r->payload, // already JSON string from change_log
        ])->values()->all();

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'X-Sync-Token' => $this->syncToken,
                    'X-Tenant-Id'  => (string) $tenantId,
                    'Accept'       => 'application/json',
                ])
                ->post($this->remoteUrl . '/sync/push', $payload);

            if (!$response->successful()) {
                throw new \Exception("HTTP {$response->status()}: " . $response->body());
            }

            $result  = $response->json();
            $pushed  = (int) ($result['pushed'] ?? 0);
            $errList = (array) ($result['errors'] ?? []);

            // Mark successfully pushed rows
            // The remote returns pushed count — mark the first $pushed rows as synced
            // (remote skips forbidden tables silently, so we trust the count)
            $ids = $rows->pluck('id')->take($pushed + count($errList))->all();

            DB::table('change_log')
                ->whereIn('id', $ids)
                ->whereNull('synced_at')
                ->update(['synced_at' => now(), 'sync_error' => null]);

            // If remote reported errors, increment retry on those
            if (!empty($errList)) {
                // We don't know which IDs failed from a batch response,
                // so mark ALL rows that aren't confirmed synced
                $remaining = $rows->whereNotIn('id', $ids);
                foreach ($remaining as $row) {
                    DB::table('change_log')->where('id', $row->id)
                        ->increment('retry_count', 1, [
                            'sync_error' => 'Batch push: unconfirmed row',
                        ]);
                }
            }

            return [$pushed, count($errList)];

        } catch (\Exception $e) {
            Log::error("pos:sync pushBatch failed tenant#{$tenantId}: " . $e->getMessage());

            // Increment retry on all rows in this batch
            DB::table('change_log')
                ->whereIn('id', $rows->pluck('id')->all())
                ->increment('retry_count', 1, ['sync_error' => substr($e->getMessage(), 0, 500)]);

            return [0, $rows->count()];
        }
    }

    // ── Pull master/catalog data from remote → local ──────────────────────────
    private function pullMasterData(int $tenantId): void
    {
        $lastSync = DB::table('sync_status')
            ->where('tenant_id', $tenantId)
            ->value('last_synced_at') ?? '2000-01-01 00:00:00';

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Sync-Token' => $this->syncToken,
                    'X-Tenant-Id'  => (string) $tenantId,
                    'Accept'       => 'application/json',
                ])
                ->post($this->remoteUrl . '/sync/pull', ['since' => $lastSync]);

            if (!$response->successful()) return;

            $data = $response->json('data', []);

            foreach ($data as $table => $remoteRows) {
                foreach ($remoteRows as $row) {
                    $row = (array) $row;
                    // Only upsert if it belongs to this tenant
                    if (isset($row['tenant_id']) && (int)$row['tenant_id'] !== $tenantId) {
                        continue;
                    }
                    DB::table($table)->upsert($row, ['id'], array_keys($row));
                }
            }
        } catch (\Exception $e) {
            Log::warning("pos:sync pullMasterData tenant#{$tenantId}: " . $e->getMessage());
        }
    }

    // ── Check if remote API is reachable ──────────────────────────────────────
    private function remoteIsReachable(int $tenantId): bool
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'X-Sync-Token' => $this->syncToken,
                    'X-Tenant-Id'  => (string) $tenantId,
                    'Accept'       => 'application/json',
                ])
                ->get($this->remoteUrl . '/sync/status');

            return $response->successful() && ($response->json('success') === true);
        } catch (\Exception $e) {
            return false;
        }
    }

    // ── Update sync_status row ────────────────────────────────────────────────
    private function updateStatus(int $tenantId, string $status, int $pendingCount = 0, ?string $error = null): void
    {
        DB::table('sync_status')->updateOrInsert(
            ['tenant_id' => $tenantId],
            [
                'status'         => $status,
                'last_synced_at' => $status === 'online' ? now() : DB::raw('COALESCE(last_synced_at, NULL)'),
                'pending_count'  => $pendingCount,
                'last_error'     => $error,
                'updated_at'     => now(),
                'created_at'     => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
    }

    // ── Windows toast / console output ────────────────────────────────────────
    private function notify(string $type, string $message): void
    {
        $prefix = match ($type) {
            'success' => '<info>[SYNC OK]</info>',
            'error'   => '<error>[SYNC ERR]</error>',
            'offline' => '<comment>[OFFLINE]</comment>',
            default   => '[INFO]',
        };
        $this->line("{$prefix} {$message}");

        // Windows toast (silent — no popup window)
        if (PHP_OS_FAMILY !== 'Windows') return;

        $msg = addslashes($message);
        $ps  = <<<PS
[Windows.UI.Notifications.ToastNotificationManager, Windows.UI.Notifications, ContentType=WindowsRuntime]|Out-Null
[Windows.Data.Xml.Dom.XmlDocument, Windows.Data.Xml.Dom, ContentType=WindowsRuntime]|Out-Null
\$xml=New-Object Windows.Data.Xml.Dom.XmlDocument
\$xml.LoadXml('<toast><visual><binding template="ToastGeneric"><text>Stardena POS Sync</text><text>{$msg}</text></binding></visual></toast>')
[Windows.UI.Notifications.ToastNotificationManager]::CreateToastNotifier("Stardena POS").Show([Windows.UI.Notifications.ToastNotification]::new(\$xml))
PS;
        $escaped = str_replace(["\n", '"'], [' ', '\\"'], $ps);
        pclose(popen("start /b powershell -WindowStyle Hidden -Command \"{$escaped}\"", 'r'));
    }
}