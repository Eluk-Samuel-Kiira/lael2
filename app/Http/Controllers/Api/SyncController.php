<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    // Hardcoded tenant tokens (store in .env or config)
    private $tenantTokens = [
        // tenant_id => token
        1 => 'your-hardcoded-token-here',
        2 => 'another-token-here',
    ];

    public function __construct()
    {
        // Validate tenant token for every request
        $this->middleware(function ($request, $next) {
            $token = $request->header('X-Sync-Token');
            $tenantId = $request->header('X-Tenant-Id');
            
            if (!$token || !$tenantId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing authentication headers'
                ], 401);
            }
            
            if (!isset($this->tenantTokens[$tenantId]) || $this->tenantTokens[$tenantId] !== $token) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid authentication credentials'
                ], 401);
            }
            
            $request->merge(['authenticated_tenant_id' => $tenantId]);
            return $next($request);
        });
    }

    /**
     * Push local changes to remote
     */
    public function push(Request $request)
    {
        $tenantId = $request->authenticated_tenant_id;
        $data = $request->json()->all();
        
        $results = [
            'success' => true,
            'pushed' => 0,
            'errors' => []
        ];
        
        DB::beginTransaction();
        try {
            foreach ($data as $entry) {
                try {
                    $payload = json_decode($entry['payload'], true);
                    
                    // Skip syncing system tables
                    if (in_array($entry['table_name'], ['change_log', 'sync_status'])) {
                        continue;
                    }
                    
                    switch ($entry['operation']) {
                        case 'INSERT':
                            DB::table($entry['table_name'])
                                ->updateOrInsert(
                                    ['id' => $payload['id']],
                                    array_merge($payload, ['tenant_id' => $tenantId])
                                );
                            break;
                            
                        case 'UPDATE':
                            DB::table($entry['table_name'])
                                ->where('id', $entry['row_id'])
                                ->where('tenant_id', $tenantId)
                                ->update($payload);
                            break;
                            
                        case 'DELETE':
                            DB::table($entry['table_name'])
                                ->where('id', $entry['row_id'])
                                ->where('tenant_id', $tenantId)
                                ->delete();
                            break;
                    }
                    
                    $results['pushed']++;
                    
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'table' => $entry['table_name'],
                        'row_id' => $entry['row_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $results['success'] = false;
            $results['error'] = $e->getMessage();
        }
        
        return response()->json($results);
    }

    /**
     * Pull master/catalog data from remote
     */
    public function pull(Request $request)
    {
        $tenantId = $request->authenticated_tenant_id;
        $since = $request->input('since', '2000-01-01 00:00:00');
        
        $masterTables = [
            'categories', 'product_categories', 'products',
            'taxes', 'promotions', 'unit_of_measures',
            'locations', 'departments'
        ];
        
        $data = [];
        
        foreach ($masterTables as $table) {
            try {
                $rows = DB::table($table)
                    ->where('tenant_id', $tenantId)
                    ->where('updated_at', '>', $since)
                    ->get();
                    
                if ($rows->count() > 0) {
                    $data[$table] = $rows;
                }
            } catch (\Exception $e) {
                Log::warning("Pull failed for table {$table}: " . $e->getMessage());
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'server_time' => now()->toDateTimeString()
        ]);
    }

    /**
     * Check connection and get server status
     */
    public function status(Request $request)
    {
        $tenantId = $request->authenticated_tenant_id;
        
        return response()->json([
            'success' => true,
            'status' => 'online',
            'tenant_id' => $tenantId,
            'server_time' => now()->toDateTimeString(),
            'version' => '1.0'
        ]);
    }

    public function getSyncStatus()
    {
        $tenantId = auth()->user()->tenant_id ?? 1;
        
        $status = DB::table('sync_status')
            ->where('tenant_id', $tenantId)
            ->first();
        
        return response()->json([
            'status' => $status->status ?? 'offline',
            'pending_count' => $status->pending_count ?? 0,
            'last_synced_at' => $status->last_synced_at,
            'last_error' => $status->last_error,
            'updated_at' => $status->updated_at,
        ]);
    }
}