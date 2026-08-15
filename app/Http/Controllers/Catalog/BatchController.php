<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PurchaseReceiptItem;
use App\Models\Location;
use App\Models\Department;

class BatchController extends Controller
{
    /**
     * Display batches for products that use batch strategy ONLY
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('view inventory')) {
            abort(403, __('payments.not_authorized'));
        }

        $search = $request->get('search');
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $perPage = $request->get('per_page', 15);

        $query = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->leftJoin('product_variants', 'purchase_order_items.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('products', 'product_variants.product_id', '=', 'products.id')
            ->leftJoin('locations', 'purchase_receipt_items.location_id', '=', 'locations.id')
            ->leftJoin('departments', 'purchase_receipt_items.department_id', '=', 'departments.id')
            ->where('purchase_orders.tenant_id', $tenantId)
            ->where('products.inventory_strategy', 'batch') // ✅ ONLY BATCH STRATEGY
            ->where(function($q) {
                $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                  ->orWhereNull('purchase_receipt_items.quantity_remaining');
            })
            ->select(
                'purchase_receipt_items.*',
                'product_variants.name as variant_name',
                'product_variants.sku',
                'products.name as product_name',
                'products.inventory_strategy',
                'locations.name as location_name',
                'departments.name as department_name'
            );

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('purchase_receipt_items.batch_number', 'like', "%{$search}%")
                  ->orWhere('product_variants.name', 'like', "%{$search}%")
                  ->orWhere('product_variants.sku', 'like', "%{$search}%")
                  ->orWhere('products.name', 'like', "%{$search}%");
            });
        }

        if ($locationId) {
            $query->where('purchase_receipt_items.location_id', $locationId);
        }

        if ($departmentId) {
            $query->where('purchase_receipt_items.department_id', $departmentId);
        }

        $batches = $query->orderBy('purchase_receipt_items.created_at', 'desc')->paginate($perPage);

        // Debug log to verify only batch products are returned
        // Log::info('Batch Products Only:', [
        //     'count' => $batches->count(),
        //     'strategies' => $query->pluck('inventory_strategy')->unique()->toArray()
        // ]);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get();

        $summary = [
            'total_batches' => $query->count(),
            'total_quantity' => $query->sum('purchase_receipt_items.quantity_remaining') ?? 0,
        ];

        return view('inventory.batch.index', compact(
            'batches',
            'locations',
            'departments',
            'summary',
            'search',
            'locationId',
            'departmentId',
            'perPage'
        ));
    }

    /**
     * Assign batches to location and department
     */
    public function assign(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $request->validate([
            'batch_ids' => 'required|array',
            'batch_ids.*' => 'exists:purchase_receipt_items,id',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (!$request->location_id && !$request->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least a location or department.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $assigned = 0;
            $skipped = 0;
            $assignmentDetails = [];

            foreach ($request->batch_ids as $batchId) {
                $batch = PurchaseReceiptItem::query()
                    ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                    ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                    ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
                    ->join('product_variants', 'purchase_order_items.product_variant_id', '=', 'product_variants.id')
                    ->join('products', 'product_variants.product_id', '=', 'products.id')
                    ->where('purchase_orders.tenant_id', $tenantId)
                    ->where('products.inventory_strategy', 'batch')
                    ->where('purchase_receipt_items.id', $batchId)
                    ->where(function($q) {
                        $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                        ->orWhereNull('purchase_receipt_items.quantity_remaining');
                    })
                    ->select('purchase_receipt_items.*')
                    ->first();

                if (!$batch) {
                    $skipped++;
                    continue;
                }

                // Store old values for logging
                $oldLocationId = $batch->location_id;
                $oldDepartmentId = $batch->department_id;

                $batch->location_id = $request->location_id;
                $batch->department_id = $request->department_id;
                $batch->save();

                $assigned++;

                // ✅ LOG ASSIGNMENT TO BATCHLOG TABLE
                $this->logBatchAssignment(
                    $batch,
                    $oldLocationId,
                    $oldDepartmentId,
                    $request->location_id,
                    $request->department_id,
                    $user,
                    $tenantId
                );

                $assignmentDetails[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'old_location_id' => $oldLocationId,
                    'old_department_id' => $oldDepartmentId,
                    'new_location_id' => $batch->location_id,
                    'new_department_id' => $batch->department_id,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$assigned} batch(es) assigned successfully." . ($skipped > 0 ? " {$skipped} skipped." : ""),
                'reload' => true,
                'data' => [
                    'assigned' => $assigned,
                    'skipped' => $skipped,
                    'details' => $assignmentDetails,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Batch assignment failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign batches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unassign batches (remove location/department)
     */
    public function unassign(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $request->validate([
            'batch_ids' => 'required|array',
            'batch_ids.*' => 'exists:purchase_receipt_items,id',
        ]);

        DB::beginTransaction();

        try {
            $unassigned = 0;
            $skipped = 0;
            $unassignmentDetails = [];

            foreach ($request->batch_ids as $batchId) {
                $batch = PurchaseReceiptItem::query()
                    ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                    ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                    ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
                    ->join('product_variants', 'purchase_order_items.product_variant_id', '=', 'product_variants.id')
                    ->join('products', 'product_variants.product_id', '=', 'products.id')
                    ->where('purchase_orders.tenant_id', $tenantId)
                    ->where('products.inventory_strategy', 'batch')
                    ->where('purchase_receipt_items.id', $batchId)
                    ->select('purchase_receipt_items.*')
                    ->first();

                if (!$batch) {
                    $skipped++;
                    continue;
                }

                // Store old values for logging
                $oldLocationId = $batch->location_id;
                $oldDepartmentId = $batch->department_id;

                $batch->location_id = null;
                $batch->department_id = null;
                $batch->save();

                $unassigned++;

                // ✅ LOG UNASSIGNMENT TO BATCHLOG TABLE
                $this->logBatchUnassignment(
                    $batch,
                    $oldLocationId,
                    $oldDepartmentId,
                    $user,
                    $tenantId
                );

                $unassignmentDetails[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'old_location_id' => $oldLocationId,
                    'old_department_id' => $oldDepartmentId,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$unassigned} batch(es) unassigned successfully." . ($skipped > 0 ? " {$skipped} skipped." : ""),
                'reload' => true,
                'data' => [
                    'unassigned' => $unassigned,
                    'skipped' => $skipped,
                    'details' => $unassignmentDetails,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Batch unassignment failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unassign batches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log batch assignment to BatchLog table
     */
    private function logBatchAssignment($batch, $oldLocationId, $oldDepartmentId, $newLocationId, $newDepartmentId, $user, $tenantId)
    {
        try {
            // Get variant details
            $variant = $batch->purchaseOrderItem->productVariant ?? null;
            
            // Get purchase order details
            $purchaseOrder = $batch->purchaseReceipt->purchaseOrder ?? null;
            
            BatchLog::create([
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'variant_id' => $variant ? $variant->id : null,
                'variant_name' => $variant ? $variant->name : 'Unknown',
                'variant_sku' => $variant ? $variant->sku : 'Unknown',
                'type' => BatchLog::TYPE_TRANSFERRED,
                'quantity_change' => 0, // No quantity change, just location change
                'quantity_before' => $batch->quantity_remaining ?? $batch->quantity_received,
                'quantity_after' => $batch->quantity_remaining ?? $batch->quantity_received,
                'unit_cost' => $batch->unit_cost ?? 0,
                'total_cost' => 0,
                'order_id' => null,
                'order_number' => null,
                'purchase_order_id' => $purchaseOrder ? $purchaseOrder->id : null,
                'purchase_order_number' => $purchaseOrder ? $purchaseOrder->po_number : null,
                'purchase_receipt_id' => $batch->purchase_receipt_id,
                'supplier_id' => $purchaseOrder ? $purchaseOrder->supplier_id : null,
                'supplier_name' => $purchaseOrder && $purchaseOrder->supplier ? $purchaseOrder->supplier->name : null,
                'tenant_id' => $tenantId,
                'location_id' => $newLocationId,
                'department_id' => $newDepartmentId,
                'expiry_date' => $batch->expiry_date,
                'event_date' => now(),
                'performed_by' => $user->id,
                'metadata' => [
                    'action' => 'assigned',
                    'old_location_id' => $oldLocationId,
                    'old_department_id' => $oldDepartmentId,
                    'new_location_id' => $newLocationId,
                    'new_department_id' => $newDepartmentId,
                    'assigned_by' => $user->name,
                    'assigned_at' => now()->toDateTimeString(),
                ],
            ]);

            \Log::info('Batch assignment logged to BatchLog', [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'old_location' => $oldLocationId,
                'new_location' => $newLocationId,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to log batch assignment: ' . $e->getMessage(), [
                'batch_id' => $batch->id,
            ]);
        }
    }

    /**
     * Log batch unassignment to BatchLog table
     */
    private function logBatchUnassignment($batch, $oldLocationId, $oldDepartmentId, $user, $tenantId)
    {
        try {
            // Get variant details
            $variant = $batch->purchaseOrderItem->productVariant ?? null;
            
            // Get purchase order details
            $purchaseOrder = $batch->purchaseReceipt->purchaseOrder ?? null;
            
            BatchLog::create([
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'variant_id' => $variant ? $variant->id : null,
                'variant_name' => $variant ? $variant->name : 'Unknown',
                'variant_sku' => $variant ? $variant->sku : 'Unknown',
                'type' => BatchLog::TYPE_TRANSFERRED,
                'quantity_change' => 0, // No quantity change, just location change
                'quantity_before' => $batch->quantity_remaining ?? $batch->quantity_received,
                'quantity_after' => $batch->quantity_remaining ?? $batch->quantity_received,
                'unit_cost' => $batch->unit_cost ?? 0,
                'total_cost' => 0,
                'order_id' => null,
                'order_number' => null,
                'purchase_order_id' => $purchaseOrder ? $purchaseOrder->id : null,
                'purchase_order_number' => $purchaseOrder ? $purchaseOrder->po_number : null,
                'purchase_receipt_id' => $batch->purchase_receipt_id,
                'supplier_id' => $purchaseOrder ? $purchaseOrder->supplier_id : null,
                'supplier_name' => $purchaseOrder && $purchaseOrder->supplier ? $purchaseOrder->supplier->name : null,
                'tenant_id' => $tenantId,
                'location_id' => null,
                'department_id' => null,
                'expiry_date' => $batch->expiry_date,
                'event_date' => now(),
                'performed_by' => $user->id,
                'metadata' => [
                    'action' => 'unassigned',
                    'old_location_id' => $oldLocationId,
                    'old_department_id' => $oldDepartmentId,
                    'unassigned_by' => $user->name,
                    'unassigned_at' => now()->toDateTimeString(),
                ],
            ]);

            \Log::info('Batch unassignment logged to BatchLog', [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'old_location' => $oldLocationId,
                'old_department' => $oldDepartmentId,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to log batch unassignment: ' . $e->getMessage(), [
                'batch_id' => $batch->id,
            ]);
        }
    }

    /**
     * Get batch details for selected IDs
     */
    public function details(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $request->validate([
            'batch_ids' => 'required|array',
            'batch_ids.*' => 'exists:purchase_receipt_items,id',
        ]);

        $batches = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->join('product_variants', 'purchase_order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('purchase_orders.tenant_id', $tenantId)
            ->where('products.inventory_strategy', 'batch')
            ->whereIn('purchase_receipt_items.id', $request->batch_ids)
            ->select(
                'purchase_receipt_items.*',
                'product_variants.name as variant_name',
                'products.name as product_name'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $batches->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity_remaining' => $batch->quantity_remaining ?? $batch->quantity_received,
                    'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : null,
                    'product_name' => $batch->variant_name ?? $batch->product_name ?? 'N/A',
                    'location_id' => $batch->location_id,
                    'department_id' => $batch->department_id,
                ];
            })
        ]);
    }
}