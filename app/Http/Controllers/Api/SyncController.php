<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ DB, Log };
use Illuminate\Support\Arr;

class SyncController extends Controller
{
    private array $tenantTokens = [];

    public function __construct()
    {
        // ─── Read tenant tokens from config ──────────────────────────
        $this->tenantTokens = config('sync.tenant_tokens', []);
        
        // ─── LOG ALL AVAILABLE TOKENS FOR DEBUGGING ──────────────────
        Log::info('SyncController initialized', [
            'tenant_tokens_count' => count($this->tenantTokens),
            'tenant_ids' => array_keys($this->tenantTokens),
            'tokens' => array_map(function($token) {
                return substr($token, 0, 10) . '...';
            }, $this->tenantTokens),
        ]);
    }

    private function validateToken(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $token    = $request->header('X-Sync-Token');
        $tenantId = $request->header('X-Tenant-Id');
    
        Log::info('Token validation attempt', [
            'tenant_id' => $tenantId,
            'token' => substr($token ?? '', 0, 10) . '...',
            'token_length' => strlen($token ?? ''),
            'available_tokens' => array_keys($this->tenantTokens),
        ]);
    
        if (!$token || !$tenantId) {
            Log::warning('Missing authentication headers', [
                'has_token' => !empty($token),
                'has_tenant' => !empty($tenantId),
            ]);
            return response()->json(['success' => false, 'error' => 'Missing authentication headers'], 401);
        }
    
        $expected = $this->tenantTokens[(string) $tenantId] ?? null;
    
        Log::info('Token comparison', [
            'tenant_id' => $tenantId,
            'expected' => $expected ? substr($expected, 0, 10) . '...' : 'null',
            'provided' => substr($token, 0, 10) . '...',
            'match' => $expected && hash_equals($expected, $token),
        ]);
    
        if (!$expected || !hash_equals($expected, $token)) {
            Log::warning('SyncController: Invalid token', [
                'tenant_id' => $tenantId,
                'expected_exists' => !empty($expected),
            ]);
            return response()->json(['success' => false, 'error' => 'Invalid authentication credentials'], 401);
        }
    
        $request->merge(['authenticated_tenant_id' => (int) $tenantId]);
        return null;
    }


    // ── GET /sync/status — remote reachability check ────────────────────────
    public function status(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $authError = $this->validateToken($request);
            if ($authError) return $authError;

            return response()->json([
                'success'     => true,
                'status'      => 'online',
                'tenant_id'   => $request->authenticated_tenant_id,
                'server_time' => now()->toDateTimeString(),
                'version'     => '1.0',
            ]);
        } catch (\Exception $e) {
            Log::error('SyncController@status: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── POST /sync/push — receive change_log rows from local machine ─────────
    public function push(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $authError = $this->validateToken($request);
            if ($authError) return $authError;

            $tenantId = $request->authenticated_tenant_id;
            $data     = $request->json()->all();

            if (empty($data)) {
                return response()->json(['success' => true, 'pushed' => 0, 'errors' => [], 'failed_ids' => []]);
            }

            $pushed = 0;
            $errors = [];
            $failedIds = []; // Track IDs that failed
            $forbidden = ['change_log', 'sync_status', 'migrations', 'password_reset_tokens', 'sessions'];
            
            $schemaCache = [];

            DB::beginTransaction();
            try {
                foreach ($data as $entry) {
                    $entryId = $entry['id'] ?? null; // This is the change_log ID
                    $tableName = $entry['table_name'] ?? null;
                    $operation = $entry['operation'] ?? null;
                    $rowId = $entry['row_id'] ?? null;

                    if (!$tableName || !$operation || !$rowId || !$entryId) {
                        $errors[] = "Unknown Row: Missing fields";
                        if ($entryId) $failedIds[] = $entryId;
                        continue;
                    }

                    if (in_array($tableName, $forbidden, true)) {
                        $pushed++; 
                        continue;
                    }

                    // Decode Payload
                    $rawPayload = $entry['payload'] ?? null;
                    $payload = null;

                    if (is_array($rawPayload)) {
                        $payload = $rawPayload;
                    } elseif (is_string($rawPayload)) {
                        $decoded = json_decode($rawPayload, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $payload = $decoded;
                        }
                    }

                    if (!is_array($payload)) {
                        $errors[] = "Row #{$entryId}: Invalid payload";
                        $failedIds[] = $entryId;
                        continue;
                    }

                    // Dynamic Schema Filtering
                    if (!isset($schemaCache[$tableName])) {
                        $schemaCache[$tableName] = DB::getSchemaBuilder()->getColumnListing($tableName);
                    }
                    $validColumns = $schemaCache[$tableName];

                    $filteredPayload = [];
                    foreach ($payload as $key => $value) {
                        if (in_array($key, $validColumns)) {
                            $filteredPayload[$key] = $value;
                        }
                    }

                    if (in_array('tenant_id', $validColumns)) {
                        $filteredPayload['tenant_id'] = $tenantId;
                    }

                    // Apply Operation
                    try {
                        if ($operation === 'INSERT') {
                            DB::table($tableName)->updateOrInsert(['id' => $rowId], $filteredPayload);
                        } elseif ($operation === 'UPDATE') {
                            $query = DB::table($tableName)->where('id', $rowId);
                            if (in_array('tenant_id', $validColumns)) $query->where('tenant_id', $tenantId);
                            $query->update($filteredPayload);
                        } elseif ($operation === 'DELETE') {
                            $query = DB::table($tableName)->where('id', $rowId);
                            if (in_array('tenant_id', $validColumns)) $query->where('tenant_id', $tenantId);
                            $query->delete();
                        }
                        
                        $pushed++;

                    } catch (\Exception $e) {
                        $errorMsg = "{$tableName} #{$rowId}: " . substr($e->getMessage(), 0, 100);
                        $errors[] = $errorMsg;
                        $failedIds[] = $entryId; // Mark this specific change_log ID as failed
                        
                        Log::error('Sync Row Failed', [
                            'change_log_id' => $entryId,
                            'table' => $tableName,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json([
                'success'    => count($errors) === 0,
                'pushed'     => $pushed,
                'errors'     => $errors,
                'failed_ids' => $failedIds, // Return exact IDs to local
            ]);

        } catch (\Exception $e) {
            Log::error('SyncController@push global error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── POST /sync/pull — send master data back to local machine ─────────────
    public function pull(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $authError = $this->validateToken($request);
            if ($authError) return $authError;

            $tenantId = $request->authenticated_tenant_id;
            $since    = $request->input('since', '2000-01-01 00:00:00');

            // ✅ Use the SAME master_tables list from config/sync.php
            $masterTables = config('sync.master_tables', [
                'categories', 'product_categories', 'products', 'product_variants',
                'variant_taxes', 'unit_of_measures', 'taxes', 'tax_product',
                'promotions', 'promotion_products', 'currencies', 'payment_methods',
                'expense_categories', 'customer_groups', 'billing_plans', 'locations',
                'departments', 'financial_report_templates', 'report_sections',
                'roles', 'permissions', 'role_has_permissions',
            ]);

            $data = [];
            $totalRows = 0;
            
            foreach ($masterTables as $table) {
                try {
                    if (!DB::getSchemaBuilder()->hasTable($table)) {
                        Log::debug("SyncController@pull: Table {$table} not found on remote");
                        continue;
                    }

                    $rows = DB::table($table)
                        ->where('tenant_id', $tenantId)
                        ->where('updated_at', '>', $since)
                        ->get();

                    if ($rows->isNotEmpty()) {
                        $data[$table] = $rows;
                        $totalRows += $rows->count();
                        Log::info("SyncController@pull: Found {$rows->count()} updated rows in {$table}");
                    }
                } catch (\Exception $e) {
                    Log::warning("SyncController@pull table {$table}: " . $e->getMessage());
                }
            }

            Log::info("SyncController@pull: Returning {$totalRows} total rows for tenant #{$tenantId}");

            return response()->json([
                'success'     => true,
                'data'        => $data,
                'server_time' => now()->toDateTimeString(),
                'pulled_count'=> $totalRows,
            ]);

        } catch (\Exception $e) {
            Log::error('SyncController@pull: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── GET /sync/frontend-status — local badge polling ──────────────────────
    // Called by the browser on the LOCAL machine only.
    // FIX: was crashing when sync_status row doesn't exist yet (null->property).
    public function getFrontendStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $tenantId = auth()->check() ? auth()->user()->tenant_id : null;

            if (!$tenantId) {
                return response()->json($this->emptyStatus());
            }

            // ── FIX: use ->first() but guard against null ─────────────────────
            $status = DB::table('sync_status')
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$status) {
                // No sync has ever run — create a baseline row so future runs update it
                DB::table('sync_status')->insertOrIgnore([
                    'tenant_id'      => $tenantId,
                    'status'         => 'offline',
                    'pending_count'  => DB::table('change_log')
                                          ->where('tenant_id', $tenantId)
                                          ->whereNull('synced_at')
                                          ->count(),
                    'last_synced_at' => null,
                    'last_error'     => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                return response()->json($this->emptyStatus(
                    DB::table('change_log')
                        ->where('tenant_id', $tenantId)
                        ->whereNull('synced_at')
                        ->count()
                ));
            }

            return response()->json([
                'status'         => $status->status         ?? 'offline',
                'pending_count'  => $status->pending_count  ?? 0,
                'last_synced_at' => $status->last_synced_at ?? null,
                'last_error'     => $status->last_error     ?? null,
                'updated_at'     => $status->updated_at     ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('SyncController@getFrontendStatus: ' . $e->getMessage());
            return response()->json($this->emptyStatus());
        }
    }

    // ── POST /sync/trigger — fire artisan pos:sync from the browser ──────────
    // Only available on LOCAL machine (APP_ENV=local or IS_LOCAL_POS=true)
    public function trigger(Request $request): \Illuminate\Http\JsonResponse
    {
        // \Log::info('We reached');
        if (!$this->isLocalMachine()) {
            return response()->json(['success' => false, 'error' => 'Only on local POS.'], 403);
        }

        try {
            // We simply create a "flag" file. 
            // The Task Scheduler (or a separate watcher) will see this and run the batch.
            // BUT, for immediate JS feedback, we will try to run the batch file directly 
            // using cmd.exe which is more reliable than popen on Windows.
            
            $batchPath = base_path('sync_now.bat');
            
            if (!file_exists($batchPath)) {
                return response()->json(['success' => false, 'error' => 'sync_now.bat missing.'], 500);
            }

            // Use cmd /c start to run it completely detached and silent
            pclose(popen('start /b cmd /c "' . $batchPath . '"', 'r'));

            return response()->json([
                'success' => true,
                'message' => 'Sync started in background.',
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function emptyStatus(int $pendingCount = 0): array
    {
        return [
            'status'         => 'offline',
            'pending_count'  => $pendingCount,
            'last_synced_at' => null,
            'last_error'     => null,
            'updated_at'     => null,
        ];
    }

    /**
     * Determines if this request is coming from a local POS machine.
     * Set IS_LOCAL_POS=true in the local .env, leave it false/absent on cPanel.
     */
    private function isLocalMachine(): bool
    {
        return filter_var(env('IS_LOCAL_POS', false), FILTER_VALIDATE_BOOLEAN);
    }
}