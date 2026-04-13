<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{ DB, Log, Http };

class SyncToRemote extends Command
{
    protected $signature   = 'pos:sync {--tenant=}';
    protected $description = 'Push pending changes to remote server via API';

    private $remoteUrl;
    private $syncToken;
    private $tenantId;

    public function __construct()
    {
        parent::__construct();
        $this->remoteUrl = rtrim(env('SYNC_REMOTE_URL', 'https://lael-pos.stardena.org'), '/');
        $this->syncToken = env('SYNC_TOKEN', '');
        $this->tenantId = env('TENANT_ID', 1);
    }

    public function handle(): void
    {
        $tenantId = $this->option('tenant') ?? $this->tenantId;
        
        $this->info("Starting sync for tenant #{$tenantId}...");
        
        // Validate token
        if (empty($this->syncToken)) {
            $this->error('SYNC_TOKEN not configured in .env');
            return;
        }
        
        // 1. Check remote connectivity (NO /api prefix)
        if (!$this->remoteIsReachable($tenantId)) {
            $this->updateStatus((int)$tenantId, 'offline', 0, 'Remote server unreachable');
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
            $this->updateStatus((int)$tenantId, 'online', 0);
            return;
        }

        $this->info("Found {$pending->count()} pending changes");
        $this->updateStatus((int)$tenantId, 'syncing', $pending->count());
        
        $pushed = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($pending as $entry) {
                try {
                    // NO /api prefix in URL
                    $response = Http::timeout(30)
                        ->withHeaders([
                            'X-Sync-Token' => $this->syncToken,
                            'X-Tenant-Id' => (string) $tenantId,
                            'Content-Type' => 'application/json',
                        ])
                        ->post($this->remoteUrl . '/sync/push', [$entry]);
                    
                    if ($response->successful()) {
                        $result = $response->json();
                        if ($result['success'] ?? false) {
                            DB::table('change_log')
                                ->where('id', $entry->id)
                                ->update(['synced_at' => now(), 'sync_error' => null]);
                            $pushed++;
                        } else {
                            throw new \Exception($result['error'] ?? 'Unknown error');
                        }
                    } else {
                        throw new \Exception('HTTP ' . $response->status() . ': ' . $response->body());
                    }
                } catch (\Exception $e) {
                    DB::table('change_log')
                        ->where('id', $entry->id)
                        ->increment('retry_count', 1, [
                            'sync_error' => substr($e->getMessage(), 0, 500)
                        ]);
                    $errors++;
                    Log::error('Sync row failed', [
                        'tenant_id' => $tenantId,
                        'change_log_id' => $entry->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sync transaction failed', ['error' => $e->getMessage()]);
            $this->updateStatus((int)$tenantId, 'error', $pending->count(), $e->getMessage());
            $this->notify('error', "Sync failed: " . $e->getMessage());
            return;
        }

        // 4. Update status
        $remaining = DB::table('change_log')
            ->where('tenant_id', $tenantId)
            ->whereNull('synced_at')
            ->count();

        $status = $errors > 0 ? 'error' : 'online';
        $this->updateStatus((int)$tenantId, $status, $remaining, $errors > 0 ? "{$errors} row(s) failed" : null);

        // 5. Notification
        if ($errors > 0) {
            $this->notify('error', "Sync finished with {$errors} error(s). Pushed {$pushed} row(s).");
        } else {
            $this->notify('success', "Sync complete — pushed {$pushed} row(s)");
        }
        
        $this->info("Sync completed - Pushed: {$pushed}, Errors: {$errors}, Remaining: {$remaining}");
    }

    private function remoteIsReachable(int $tenantId): bool
    {
        try {
            // NO /api prefix
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Sync-Token' => $this->syncToken,
                    'X-Tenant-Id' => (string) $tenantId,
                ])
                ->get($this->remoteUrl . '/sync/status');
            
            return $response->successful();
        } catch (\Exception $e) {
            $this->warn('Connection failed: ' . $e->getMessage());
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
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
    }

    private function notify(string $type, string $message): void
    {
        // Only show in console
        $icon = match ($type) {
            'success' => '✅',
            'error'   => '❌',
            'offline' => '📡',
            default   => 'ℹ️',
        };
        
        $this->line("{$icon} [{$type}] {$message}");
    }
}