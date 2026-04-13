<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    private $tenantTokens = [];

    public function __construct()
    {
        // Load tenant tokens from .env
        $tokensJson = env('TENANT_TOKENS', '{}');
        $this->tenantTokens = json_decode($tokensJson, true) ?: [];
        
        Log::info('SyncController initialized', ['tokens' => array_keys($this->tenantTokens)]);
    }

    private function validateToken(Request $request)
    {
        $token = $request->header('X-Sync-Token');
        $tenantId = $request->header('X-Tenant-Id');
        
        Log::info('Validating token', [
            'has_token' => !empty($token),
            'tenant_id' => $tenantId,
            'url' => $request->fullUrl()
        ]);
        
        if (!$token || !$tenantId) {
            return response()->json([
                'success' => false,
                'error' => 'Missing authentication headers'
            ], 401);
        }
        
        if (!isset($this->tenantTokens[$tenantId]) || $this->tenantTokens[$tenantId] !== $token) {
            Log::warning('Invalid token', [
                'tenant_id' => $tenantId,
                'provided_token' => substr($token, 0, 10) . '...',
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Invalid authentication credentials'
            ], 401);
        }
        
        $request->merge(['authenticated_tenant_id' => $tenantId]);
        return null;
    }

    public function status(Request $request)
    {
        try {
            Log::info('Status endpoint called');
            
            $authError = $this->validateToken($request);
            if ($authError) return $authError;
            
            $tenantId = $request->authenticated_tenant_id;
            
            return response()->json([
                'success' => true,
                'status' => 'online',
                'tenant_id' => $tenantId,
                'server_time' => now()->toDateTimeString(),
                'version' => '1.0'
            ]);
        } catch (\Exception $e) {
            Log::error('Status endpoint error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function push(Request $request)
    {
        try {
            $authError = $this->validateToken($request);
            if ($authError) return $authError;
            
            $tenantId = $request->authenticated_tenant_id;
            $data = $request->json()->all();
            
            Log::info('Push endpoint called', [
                'tenant_id' => $tenantId,
                'entries' => count($data)
            ]);
            
            $results = [
                'success' => true,
                'pushed' => 0,
                'errors' => []
            ];
            
            DB::beginTransaction();
            try {
                foreach ($data as $entry) {
                    $payload = json_decode($entry['payload'], true);
                    
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
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Push endpoint error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function pull(Request $request)
    {
        try {
            $authError = $this->validateToken($request);
            if ($authError) return $authError;
            
            $tenantId = $request->authenticated_tenant_id;
            $since = $request->input('since', '2000-01-01 00:00:00');
            
            Log::info('Pull endpoint called', [
                'tenant_id' => $tenantId,
                'since' => $since
            ]);
            
            $masterTables = [
                'categories', 'product_categories', 'products',
                'taxes', 'promotions', 'unit_of_measures',
                'locations', 'departments'
            ];
            
            $data = [];
            
            foreach ($masterTables as $table) {
                try {
                    if (DB::getSchemaBuilder()->hasTable($table)) {
                        $rows = DB::table($table)
                            ->where('tenant_id', $tenantId)
                            ->where('updated_at', '>', $since)
                            ->get();
                            
                        if ($rows->count() > 0) {
                            $data[$table] = $rows;
                        }
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
        } catch (\Exception $e) {
            Log::error('Pull endpoint error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get sync status for frontend (local database status)
     */
    public function getFrontendStatus(Request $request)
    {
        $tenantId = auth()->user()->tenant_id ?? 2;
        
        $status = DB::table('sync_status')
            ->where('tenant_id', $tenantId)
            ->first();
        
        // Handle case when no status record exists
        if (!$status) {
            return response()->json([
                'status' => 'offline',
                'pending_count' => 0,
                'last_synced_at' => null,
                'last_error' => null,
                'updated_at' => null,
            ]);
        }
        
        return response()->json([
            'status' => $status->status ?? 'offline',
            'pending_count' => $status->pending_count ?? 0,
            'last_synced_at' => $status->last_synced_at,
            'last_error' => $status->last_error,
            'updated_at' => $status->updated_at,
        ]);
    }
}