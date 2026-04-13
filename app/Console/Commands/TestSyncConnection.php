<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestSyncConnection extends Command
{
    protected $signature = 'sync:test-connection';
    protected $description = 'Test remote sync database connection';

    public function handle()
    {
        $this->info('Testing remote sync connection...');
        
        // Test 1: Check if connection exists in config
        $connections = config('database.connections');
        if (!isset($connections['sync_remote'])) {
            $this->error('❌ sync_remote connection not found in config/database.php');
            return 1;
        }
        $this->info('✅ Connection configuration found');
        
        // Test 2: Try to connect
        try {
            $pdo = DB::connection('sync_remote')->getPdo();
            $this->info('✅ Successfully connected to remote database!');
            
            // Test 3: Run a simple query
            $result = DB::connection('sync_remote')->select('SELECT 1 as test, NOW() as time, DATABASE() as db');
            $this->info('✅ Query executed successfully');
            $this->table(['Test', 'Time', 'Database'], [
                [$result[0]->test, $result[0]->time, $result[0]->db]
            ]);
            
            // Test 4: Check if sync tables exist
            $tables = DB::connection('sync_remote')->select('SHOW TABLES');
            $tableNames = array_map('current', $tables);
            $this->info('📋 Tables in remote database: ' . implode(', ', $tableNames));
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Connection failed: ' . $e->getMessage());
            Log::error('Remote sync connection failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }
}