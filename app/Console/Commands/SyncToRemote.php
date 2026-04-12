<?php
// app/Console/Commands/SyncToRemote.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{ DB, Log };
use PDO;

class SyncToRemote extends Command
{
    protected $signature   = 'pos:sync';
    protected $description = 'Push pending change_log entries to remote MySQL';

    public function handle(): void
    {
        // 1. Test remote connectivity
        try {
            $remote = $this->remoteConnection();
            $remote->getPdo(); // force connect
        } catch (\Exception $e) {
            $this->updateStatus('offline');
            return; // silent — will retry next run
        }

        $this->updateStatus('syncing');

        // 2. Grab up to 500 unsynced rows, oldest first
        $pending = DB::table('change_log')
            ->whereNull('synced_at')
            ->where('retry_count', '<', 5)
            ->orderBy('logged_at')
            ->limit(500)
            ->get();

        if ($pending->isEmpty()) {
            $this->updateStatus('online');
            return;
        }

        $synced = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($pending as $entry) {
                try {
                    $payload = json_decode($entry->payload, true);

                    match ($entry->operation) {
                        'INSERT' => $remote->table($entry->table_name)
                                          ->insertOrIgnore($payload),

                        'UPDATE' => $remote->table($entry->table_name)
                                          ->where('id', $entry->row_id)
                                          ->where('updated_at', '<=', $payload['updated_at'] ?? now())
                                          ->update($payload), // last-write-wins

                        'DELETE' => $remote->table($entry->table_name)
                                          ->where('id', $entry->row_id)
                                          ->delete(),
                    };

                    DB::table('change_log')
                      ->where('id', $entry->id)
                      ->update(['synced_at' => now(), 'sync_error' => null]);

                    $synced++;

                } catch (\Exception $e) {
                    DB::table('change_log')
                      ->where('id', $entry->id)
                      ->increment('retry_count', 1, ['sync_error' => substr($e->getMessage(), 0, 255)]);

                    Log::error('Sync row failed', [
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
            $this->updateStatus('error', $e->getMessage());
            return;
        }

        // 3. Pull master data changes from remote → local
        // (catalog, taxes, promotions that staff may update on cPanel)
        $this->pullMasterData($remote);

        $this->updateStatus($errors > 0 ? 'error' : 'online');
        Log::info("Sync complete — pushed: {$synced}, errors: {$errors}");
    }

    private function pullMasterData($remote): void
    {
        // Only pull "master" tables — never pull transaction tables down
        $masterTables = ['categories', 'product_categories', 'products',
                         'taxes', 'promotions', 'unit_of_measures', 'locations'];

        $lastSync = DB::table('sync_status')
                      ->value('last_synced_at') ?? '2000-01-01';

        foreach ($masterTables as $table) {
            try {
                $remoteRows = $remote->table($table)
                                     ->where('updated_at', '>', $lastSync)
                                     ->get()
                                     ->toArray();

                foreach ($remoteRows as $row) {
                    DB::table($table)->upsert(
                        (array) $row,
                        ['id'],           // unique key
                        array_keys((array) $row) // update all columns
                    );
                }
            } catch (\Exception $e) {
                Log::warning("Pull failed for {$table}: " . $e->getMessage());
            }
        }
    }

    private function remoteConnection(): \Illuminate\Database\Connection
    {
        return DB::connection('mysql_remote');
    }

    private function updateStatus(string $status, ?string $error = null): void
    {
        DB::table('sync_status')->updateOrInsert(
            ['tenant_id' => current_tenant_id()],
            [
                'status'         => $status,
                'last_synced_at' => $status === 'online' ? now() : DB::raw('last_synced_at'),
                'pending_count'  => DB::table('change_log')->whereNull('synced_at')->count(),
                'last_error'     => $error,
                'updated_at'     => now(),
            ]
        );
    }
}