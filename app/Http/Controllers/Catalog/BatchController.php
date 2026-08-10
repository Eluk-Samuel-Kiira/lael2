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

                if (!$batch) continue;

                $batch->location_id = $request->location_id;
                $batch->department_id = $request->department_id;
                $batch->save();
                $assigned++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$assigned} batch(es) assigned successfully.",
                'reload' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch assignment failed: ' . $e->getMessage());
            
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

                if ($batch) {
                    $batch->location_id = null;
                    $batch->department_id = null;
                    $batch->save();
                    $unassigned++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$unassigned} batch(es) unassigned successfully.",
                'reload' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch unassignment failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unassign batches: ' . $e->getMessage(),
            ], 500);
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