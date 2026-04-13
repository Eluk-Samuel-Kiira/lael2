<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{ DB, Log, Http };

class SyncToRemote extends Command
{
    protected $signature   = 'pos:sync {--tenant=}';
    protected $description = 'Push pending changes to remote server via API';

    // Get these from .env
    private $remoteUrl;
    private $syncToken;
    private $tenantId;

    public function __construct()
    {
        parent::__construct();
        $this->remoteUrl = env('SYNC_REMOTE_URL', 'https://lael-pos.stardena.org');
        $this->syncToken = env('SYNC_TOKEN', 'your-hardcoded-token-here');
        $this->tenantId = env('TENANT_ID', 1);
    }

    public function handle(): void
    {
        $tenantId = $this->option('tenant') ?? $this->tenantId;
        
        $this->info("Starting sync for tenant #{$tenantId}...");
        
        // 1. Check remote connectivity
        if (!$this->remoteIsReachable()) {
            $this->updateStatus($tenantId, 'offline', 'Remote server unreachable');
            $this->notify('offline', "No connection — offline mode");
            return;
        }

        // 2. Get pending changes
        $pending = DB::table('change_log')
            ->where('tenant_id', $tenantId)
            ->whereNull('synced_at')
            ->where('retry_count', '<', 5)
            ->orderBy('logged_at')
            ->limit(500)
            ->get();

        if ($pending->isEmpty()) {
            $this->info("No pending changes for tenant #{$tenantId}");
            $this->updateStatus($tenantId, 'online', 0);
            return;
        }

        $this->info("Found {$pending->count()} pending changes");

        // 3. Push changes via API
        $this->updateStatus($tenantId, 'syncing', $pending->count());
        
        $pushed = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            $chunks = $pending->chunk(50);
            
            foreach ($chunks as $chunk) {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'X-Sync-Token' => $this->syncToken,
                        'X-Tenant-Id' => $tenantId,
                    ])
                    ->post($this->remoteUrl . '/api/sync/push', $chunk->toArray());
                
                if ($response->successful()) {
                    $result = $response->json();
                    $pushed += $result['pushed'] ?? 0;
                    
                    // Mark as synced
                    foreach ($chunk as $entry) {
                        DB::table('change_log')
                            ->where('id', $entry->id)
                            ->update(['synced_at' => now(), 'sync_error' => null]);
                    }
                } else {
                    $errors += $chunk->count();
                    Log::error('Sync push failed', ['response' => $response->body()]);
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sync transaction failed', ['error' => $e->getMessage()]);
            $this->updateStatus($tenantId, 'error', $pending->count(), $e->getMessage());
            $this->notify('error', "Sync failed: " . $e->getMessage());
            return;
        }

        // 4. Pull master data
        $this->pullMasterData($tenantId);

        // 5. Update status
        $remaining = DB::table('change_log')
            ->where('tenant_id', $tenantId)
            ->whereNull('synced_at')
            ->count();

        $status = $errors > 0 ? 'error' : 'online';
        $this->updateStatus($tenantId, $status, $remaining, $errors > 0 ? "{$errors} row(s) failed" : null);

        // 6. Notification
        if ($errors > 0) {
            $this->notify('error', "Sync finished with {$errors} error(s). Pushed {$pushed} row(s).");
        } else {
            $this->notify('success', "Sync complete — pushed {$pushed} row(s)");
        }
        
        $this->info("Sync completed - Pushed: {$pushed}, Errors: {$errors}, Remaining: {$remaining}");
    }

    private function pullMasterData(int $tenantId): void
    {
        $masterTables = [
            'categories', 'product_categories', 'products',
            'taxes', 'promotions', 'unit_of_measures',
            'locations', 'departments'
        ];

        $lastSync = DB::table('sync_status')
            ->where('tenant_id', $tenantId)
            ->value('last_synced_at') ?? '2000-01-01 00:00:00';

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Sync-Token' => $this->syncToken,
                    'X-Tenant-Id' => $tenantId,
                ])
                ->post($this->remoteUrl . '/api/sync/pull', ['since' => $lastSync]);
            
            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                
                foreach ($data as $table => $rows) {
                    foreach ($rows as $row) {
                        DB::table($table)->updateOrInsert(
                            ['id' => $row['id']],
                            (array) $row
                        );
                    }
                }
                
                $this->info("Pulled master data from remote");
            }
        } catch (\Exception $e) {
            Log::warning("Pull master data failed: " . $e->getMessage());
        }
    }

    private function remoteIsReachable(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-Sync-Token' => $this->syncToken,
                    'X-Tenant-Id' => $this->tenantId,
                ])
                ->get($this->remoteUrl . '/api/sync/status');
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function updateStatus(int $tenantId, string $status, int $pendingCount = 0, ?string $error = null): void
    {
        DB::table('sync_status')->updateOrInsert(
            ['tenant_id' => $tenantId],
            [
                'status' => $status,
                'last_synced_at' => $status === 'online' ? now() : DB::raw('last_synced_at'),
                'pending_count' => $pendingCount,
                'last_error' => $error,
                'updated_at' => now(),
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