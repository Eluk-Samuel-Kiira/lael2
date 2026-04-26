<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{ DB, Log, Http };

class SyncToRemote extends Command
{
    protected $signature   = 'pos:sync {--tenant= : Override the tenant ID from config}';
    // eg php artisan pos:sync --tenant=2

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
        $this->info("🚀 Starting Sync for Tenant #2...");

        // Allow --tenant flag to override config
        $tenantId = (int) ($this->option('tenant') ?? $this->tenantId);

        if (empty($this->syncToken)) {
            $this->error('❌ SYNC_TOKEN not configured in .env / config/sync.php');
            Log::error('pos:sync: SYNC_TOKEN not configured.');
            return;
        }

        $this->info("📡 Checking connectivity to: {$this->remoteUrl}");

        // ── 1. Check connectivity ─────────────────────────────────────────────
        if (!$this->remoteIsReachable($tenantId)) {
            $pending = DB::table('change_log')
                ->where('tenant_id', $tenantId)
                ->whereNull('synced_at')
                ->count();

            $this->updateStatus($tenantId, 'offline', $pending, 'Remote server unreachable');
            $this->error("❌ Offline — {$pending} row(s) queued. Remote unreachable.");
            Log::info("pos:sync tenant#{$tenantId}: offline, {$pending} queued");
            return;
        }

        $this->info("✅ Remote is Online.");

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
            $this->info("✨ Nothing pending. All synced!");
            Log::info("pos:sync tenant#{$tenantId}: nothing pending.");
            return;
        }

        $this->info("📦 Found {$pending->count()} rows to sync. Pushing...");
        $this->updateStatus($tenantId, 'syncing', $pending->count());

        // ── 3. Push in a single batch ─────────────────────────────────────────
        try {
            [$pushed, $errors] = $this->pushBatch($tenantId, $pending);
            
            $this->info("📤 Push Result: {$pushed} Success, {$errors} Errors.");

        } catch (\Exception $e) {
            $this->error("💥 Critical Push Error: " . $e->getMessage());
            Log::error("pos:sync pushBatch CRITICAL FAIL: " . $e->getMessage());
            return;
        }

        // ── 4. Pull master data remote → local ────────────────────────────────
        $this->info("⬇️ Pulling master data updates...");
        $this->pullMasterData($tenantId);

        // ── 5. Update status + notify ─────────────────────────────────────────
        $remaining = DB::table('change_log')
            ->where('tenant_id', $tenantId)
            ->whereNull('synced_at')
            ->count();

        $status = $errors > 0 ? 'error' : 'online';
        $errMsg = null;
        
        if ($errors > 0) {
            $lastError = DB::table('change_log')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('sync_error')
                ->orderBy('id', 'desc')
                ->value('sync_error');
            $errMsg = $lastError ?: "{$errors} rows failed";
        }

        $this->updateStatus($tenantId, $status, $remaining, $errMsg);

        if ($errors > 0) {
            $this->warn("⚠️ Sync done with {$errors} error(s). Pushed {$pushed}. Remaining: {$remaining}");
            Log::warning("pos:sync tenant#{$tenantId}: {$errors} errors, pushed {$pushed}, remaining {$remaining}");
        } else {
            $this->info("🎉 Synced {$pushed} row(s). Remaining: {$remaining}");
            Log::info("pos:sync tenant#{$tenantId}: pushed {$pushed}, remaining {$remaining}");
        }
    }

    // ── Push entire batch in one request ─────────────────────────────────────
    private function pushBatch(int $tenantId, $rows): array
    {
        $payload = $rows->map(fn($r) => [
            'id'         => $r->id,
            'table_name' => $r->table_name,
            'row_id'     => $r->row_id,
            'operation'  => $r->operation,
            'payload'    => $r->payload,
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

            $result     = $response->json();
            $pushed     = (int) ($result['pushed'] ?? 0);
            $errList    = (array) ($result['errors'] ?? []);
            $failedIds  = (array) ($result['failed_ids'] ?? []);

            // 1. Mark SUCCESSFUL rows as synced
            // All rows in this batch EXCEPT the failed ones are considered successful
            $allIds = $rows->pluck('id')->all();
            $successIds = array_diff($allIds, $failedIds);

            if (!empty($successIds)) {
                DB::table('change_log')
                    ->whereIn('id', $successIds)
                    ->update(['synced_at' => now(), 'sync_error' => null]);
            }

            // 2. Handle FAILED rows
            if (!empty($failedIds)) {
                // Log the errors explicitly
                Log::warning("pos:sync tenant#{$tenantId} Failures:", $errList);

                DB::table('change_log')
                    ->whereIn('id', $failedIds)
                    ->increment('retry_count', 1, [
                        'sync_error' => implode('; ', array_slice($errList, 0, 3)) // Save first 3 errors
                    ]);
            }

            return [$pushed, count($failedIds)];

        } catch (\Exception $e) {
            Log::error("pos:sync pushBatch failed tenant#{$tenantId}: " . $e->getMessage());

            // If HTTP fails completely, mark ALL as retry
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

        $this->info("⬇️ Pulling master data since: {$lastSync}");

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Sync-Token' => $this->syncToken,
                    'X-Tenant-Id'  => (string) $tenantId,
                    'Accept'       => 'application/json',
                ])
                ->post($this->remoteUrl . '/sync/pull', ['since' => $lastSync]);

            if (!$response->successful()) {
                Log::warning("pos:sync pull failed: HTTP {$response->status()}");
                return;
            }

            $result = $response->json();
            $data = $result['data'] ?? [];
            $pulledCount = $result['pulled_count'] ?? 0;

            if (empty($data)) {
                $this->info("✨ No master data updates since {$lastSync}");
                return;
            }

            $this->info("📥 Received {$pulledCount} master data rows from {$result['server_time']}");

            $upserted = 0;
            foreach ($data as $table => $remoteRows) {
                foreach ($remoteRows as $row) {
                    $row = (array) $row;
                    
                    // Safety: Ensure tenant isolation
                    if (isset($row['tenant_id']) && (int)$row['tenant_id'] !== $tenantId) {
                        continue;
                    }
                    
                    // Upsert: Insert or update by ID
                    DB::table($table)->upsert($row, ['id'], array_keys($row));
                    $upserted++;
                }
            }

            $this->info("✅ Upserted {$upserted} master data rows locally");
            Log::info("pos:sync pull: Upserted {$upserted} rows for tenant #{$tenantId}");

        } catch (\Exception $e) {
            Log::warning("pos:sync pullMasterData tenant#{$tenantId}: " . $e->getMessage());
            $this->warn("⚠️ Pull failed: " . $e->getMessage());
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