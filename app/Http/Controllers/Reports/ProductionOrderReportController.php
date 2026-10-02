<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderInput;
use App\Models\ProductionOrderOutput;
use App\Models\ProductVariant;
use App\Models\ProductCategory;
use App\Models\Location;
use App\Models\Department;
use App\Models\SingleShopInventoryLog;
use App\Models\BatchLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\PurchaseReceiptItem;

class ProductionOrderReportController extends Controller
{
    /**
     * Get current tenant ID and check permissions
     */
    private function getTenantId()
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('production_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        return $user->tenant_id;
    }

    /**
     * Check if tenant is single shop
     */
    private function isTenantSingleShop($tenantId)
    {
        $locationCount = Location::where('tenant_id', $tenantId)->count();
        return $locationCount <= 1;
    }

    /**
     * ✅ Reusable pagination method
     */
    private function paginateCollection($collection, $perPage = 15, $pageName = 'page')
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $currentPageItems = $collection->slice(($page - 1) * $perPage, $perPage)->values();
        
        return new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->except($pageName, 'per_page')
            ]
        );
    }

    /**
     * Main Production Order Report
     */
    public function index(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status', 'all');
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $categoryId = $request->get('category_id');
        $search = $request->get('search');
        $hasPayment = $request->get('has_payment', 'all');
        $minCost = $request->get('min_cost');
        $maxCost = $request->get('max_cost');
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Build Query ─────────────────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
            'startedBy',
            'completedBy',
            'paymentMethod'
        ])
        ->where('tenant_id', $tenantId)->latest();
        
        // Date range filter
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }
        
        // Status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        // Location filter
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        // Variant filter (through inputs or outputs)
        if ($variantId) {
            $query->whereHas('inputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            })->orWhereHas('outputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            });
        }
        
        // Category filter (through inputs or outputs)
        if ($categoryId) {
            $query->whereHas('inputs.productVariant.product', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })->orWhereHas('outputs.productVariant.product', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        
        // Payment filter
        if ($hasPayment === 'with_payment') {
            $query->whereNotNull('payment_method_id');
        } elseif ($hasPayment === 'without_payment') {
            $query->whereNull('payment_method_id');
        }
        
        // Cost range filter
        if ($minCost !== null) {
            $query->where('total_cost', '>=', $minCost);
        }
        if ($maxCost !== null) {
            $query->where('total_cost', '<=', $maxCost);
        }
        
        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('production_number', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhereHas('createdBy', function($c) use ($search) {
                      $c->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('inputs.productVariant', function($v) use ($search) {
                      $v->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('sku', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('outputs.productVariant', function($v) use ($search) {
                      $v->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('sku', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        // ─── Get All Orders for Summary ─────────────────────────────────
        $allOrders = $query->get();
        
        // ─── Calculate Summary Statistics ───────────────────────────────
        $summary = [
            'total_orders' => $allOrders->count(),
            'draft_count' => $allOrders->where('status', ProductionOrder::STATUS_DRAFT)->count(),
            'in_progress_count' => $allOrders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->count(),
            'completed_count' => $allOrders->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
            'cancelled_count' => $allOrders->where('status', ProductionOrder::STATUS_CANCELLED)->count(),
            'total_input_cost' => $allOrders->sum('total_input_cost'),
            'total_output_cost' => $allOrders->sum('total_output_cost'),
            'total_cost' => $allOrders->sum('total_cost'),
            'total_input_quantity' => $allOrders->sum('total_input_quantity'),
            'total_output_quantity' => $allOrders->sum('total_output_quantity'),
            'with_payment' => $allOrders->whereNotNull('payment_method_id')->count(),
            'without_payment' => $allOrders->whereNull('payment_method_id')->count(),
            'avg_cost' => $allOrders->count() > 0 ? $allOrders->avg('total_cost') : 0,
            'total_profit' => $allOrders->sum(function($order) {
                return $order->total_output_cost - $order->total_input_cost;
            }),
        ];
        
        // ─── Apply Pagination ────────────────────────────────────────────
        $paginatedOrders = $this->paginateCollection($allOrders, $perPage, 'page');
        
        // ─── Get Daily Trend Data ───────────────────────────────────────
        $dailyTrend = $allOrders->groupBy(function($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function($items, $date) {
            return (object)[
                'date' => Carbon::parse($date)->format('M d'),
                'count' => $items->count(),
                'total_cost' => $items->sum('total_cost'),
            ];
        })->sortKeys()->values();
        
        // ─── Get Status Breakdown ───────────────────────────────────────
        $statusBreakdown = collect([
            (object)['status' => 'draft', 'label' => __('pagination.draft'), 'count' => $summary['draft_count'], 'color' => 'secondary'],
            (object)['status' => 'in_progress', 'label' => __('pagination.in_progress'), 'count' => $summary['in_progress_count'], 'color' => 'warning'],
            (object)['status' => 'completed', 'label' => __('pagination.completed'), 'count' => $summary['completed_count'], 'color' => 'success'],
            (object)['status' => 'cancelled', 'label' => __('pagination.cancelled'), 'count' => $summary['cancelled_count'], 'color' => 'danger'],
        ])->filter(fn($item) => $item->count > 0)->values();
        
        // ─── Get Filter Options ─────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $statuses = [
            ['value' => 'all', 'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_DRAFT, 'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_COMPLETED, 'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_CANCELLED, 'label' => __('pagination.cancelled')],
        ];
        
        return view('reports.production.index', compact(
            'paginatedOrders',
            'allOrders',
            'summary',
            'dailyTrend',
            'statusBreakdown',
            'locations',
            'variants',
            'categories',
            'statuses',
            'startDate',
            'endDate',
            'status',
            'locationId',
            'variantId',
            'categoryId',
            'search',
            'hasPayment',
            'minCost',
            'maxCost',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Get detailed view for a specific production order
     */
    public function detail($orderId)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        $purchaseOrderItems = $this->resolveProductionPOItems($orderId);
        
        $order = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
            'startedBy',
            'completedBy',
            'cancelledBy',
            'paymentMethod'
        ])
        ->where('tenant_id', $tenantId)
        ->where('id', $orderId)
        ->firstOrFail();
        
        // ─── Get Inventory Logs for this order ──────────────────────────
        $inventoryLogs = SingleShopInventoryLog::with('variant')
            ->where('order_id', $orderId)
            ->where(function ($q) {
                $q->where('source', 'production')
                ->orWhereIn('reason', [
                    'production_consumption',
                    'production_output',
                    'production_output_update',
                ]);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        // ─── Get Batch Logs for this order ─────────────────────────────
        $batchLogs = BatchLog::where('production_order_id', $orderId)
            ->orderBy('event_date', 'desc')
            ->get();
        
        // ─── Determine if this order mixes weight-units (bagged) ───────
        $hasWeightedOutputs = $order->outputs->contains(function ($o) {
            return $o->productVariant && (float) $o->productVariant->weight > 0;
        });

        // ─── Input KG (assume inputs are raw, weight = per-unit kg) ────
        $totalInputKg = $order->inputs->sum(function ($i) {
            return $i->productVariant
                ? $i->productVariant->toKilograms($i->actual_quantity)
                : $i->actual_quantity;
        });

        // ─── Output KG (converted via weight × bags) ───────────────────
        $totalOutputKg = $order->outputs->sum(function ($o) {
            return $o->productVariant
                ? $o->productVariant->toKilograms($o->actual_quantity)
                : $o->actual_quantity;
        });

        // ─── Revenue from outputs ──────────────────────────────────────
        $totalRevenue = $order->outputs->sum(function ($o) {
            return $o->productVariant
                ? $o->productVariant->toRevenue($o->actual_quantity)
                : 0;
        });

        // ─── Loss = input − sum of all outputs (converted) ─────────────
        $lossKg  = max(0, $totalInputKg - $totalOutputKg);
        $lossPct = $totalInputKg > 0 ? ($lossKg / $totalInputKg) * 100 : 0;

        // ─── Per-category breakdown (flour vs bran vs …) ───────────────
        $outputBreakdown = $order->outputs->map(function ($o) use ($totalInputKg) {
            $kg  = $o->productVariant
                ? $o->productVariant->toKilograms($o->actual_quantity)
                : $o->actual_quantity;
            $rev = $o->productVariant
                ? $o->productVariant->toRevenue($o->actual_quantity)
                : 0;

            return [
                'variant_name' => $o->productVariant->name ?? 'N/A',
                'sku'          => $o->productVariant->sku  ?? null,
                'bags'         => (float) $o->actual_quantity,
                'weight'       => (float) ($o->productVariant->weight ?? 0),
                'total_kg'     => round($kg, 2),
                'share_pct'    => $totalInputKg > 0 ? round(($kg / $totalInputKg) * 100, 2) : 0,
                'revenue'      => round($rev, 2),
            ];
        })->values();

        // ─── Real metrics ──────────────────────────────────────────────
        $batchCost = (float) $order->total_input_cost;   // already computed

        $metrics = [
            'total_inputs'    => $order->inputs->count(),
            'total_outputs'   => $order->outputs->count(),

            // ★ New: everything in KG
            'total_input_kg'  => round($totalInputKg, 2),
            'total_output_kg' => round($totalOutputKg, 2),
            'loss_kg'         => round($lossKg, 2),
            'loss_pct'        => round($lossPct, 2),

            // ★ True yield (kg of finished product / kg of raw input)
            'input_yield'     => $totalInputKg > 0
                ? round(($totalOutputKg / $totalInputKg) * 100, 2)
                : 0,

            // Backwards-compat: keep old key for anything still reading it
            'yield_mode'      => $hasWeightedOutputs ? 'weight_based' : 'quantity_based',

            // ★ Financials
            'batch_cost'      => round($batchCost, 2),
            'total_revenue'   => round($totalRevenue, 2),
            'profit'          => round($totalRevenue - $batchCost, 2),
            'profit_margin'   => $totalRevenue > 0
                ? round((($totalRevenue - $batchCost) / $totalRevenue) * 100, 2)
                : 0,

            // Cost efficiency = output value / input value
            'cost_efficiency' => $batchCost > 0
                ? round(($totalRevenue / $batchCost) * 100, 2)
                : 0,

            // Duration (unchanged)
            'duration_hours'  => $order->started_at && $order->completed_at
                ? $order->started_at->diffInHours($order->completed_at)
                : 0,

            // ★ Per-output breakdown for the modal
            'output_breakdown' => $outputBreakdown,
        ];
        
        // ─── Get Inputs with Quality Stats ─────────────────────────────
        $inputStats = [
            'total_planned' => $order->inputs->sum('planned_quantity'),
            'total_actual' => $order->inputs->sum('actual_quantity'),
            'total_waste' => $order->inputs->sum('waste_quantity'),
            'accepted' => $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_ACCEPTED)->count(),
            'rejected' => $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_REJECTED)->count(),
            'pending' => $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_PENDING)->count(),
        ];
        
        // ─── Get Outputs with Quality Stats ────────────────────────────
        $outputStats = [
            'total_planned' => $order->outputs->sum('planned_quantity'),
            'total_actual' => $order->outputs->sum('actual_quantity'),
            'total_defective' => $order->outputs->sum('defective_quantity'),
            'approved' => $order->outputs->where('quality_status', ProductionOrderOutput::QUALITY_APPROVED)->count(),
            'rejected' => $order->outputs->where('quality_status', ProductionOrderOutput::QUALITY_REJECTED)->count(),
            'pending' => $order->outputs->where('quality_status', ProductionOrderOutput::QUALITY_PENDING)->count(),
            'yield_rate' => $order->outputs->sum('planned_quantity') > 0 
                ? ($order->outputs->sum('actual_quantity') / $order->outputs->sum('planned_quantity')) * 100 
                : 0,
        ];
        
        return response()->json([
            'order'                => $order,
            'metrics'              => $metrics,
            'input_stats'          => $inputStats,
            'output_stats'         => $outputStats,
            'inventory_logs'       => $inventoryLogs,
            'batch_logs'           => $batchLogs,
            'purchase_order_items' => $purchaseOrderItems,   // ← new
            'is_single_shop'       => $isSingleShop,
        ]);
    }

    /**
     * Walk the batch logs of this production order and resolve every
     * purchase order item that contributed raw material to it.
     *
     * Groups by PO item, sums the consumed quantity, and computes a
     * subtotal at the PO's unit cost. Returns a flat list with the
     * PO context attached for display.
     */
    private function resolveProductionPOItems(int $productionOrderId): array
    {
        // 1. Get all depletion logs for this production order that
        //    carry a purchase_order_id (i.e. came from a purchased batch).
        $depletionLogs = BatchLog::query()
            ->where('production_order_id', $productionOrderId)
            ->where('type', BatchLog::TYPE_DEPLETED)
            ->whereNotNull('purchase_order_id')
            ->get();

        if ($depletionLogs->isEmpty()) {
            return [];
        }

        // 2. Resolve the receipt item → PO item for each depletion log.
        //    The batch_id on the log points to purchase_receipt_items.
        $receiptItemIds = $depletionLogs->pluck('batch_id')->filter()->unique()->values();

        $receiptItems = PurchaseReceiptItem::query()
            ->whereIn('id', $receiptItemIds)
            ->with([
                'purchaseOrderItem.productVariant',
                'purchaseOrderItem.purchaseOrder.supplier',
            ])
            ->get()
            ->keyBy('id');

        // 3. Group the depletion logs by PO item and sum quantities.
        $grouped = [];

        foreach ($depletionLogs as $log) {
            $receiptItem = $receiptItems->get($log->batch_id);
            $poItem      = $receiptItem?->purchaseOrderItem;

            if (!$poItem) {
                continue;
            }

            $key = $poItem->id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'po_item_id'          => $poItem->id,
                    'purchase_order_id'   => $poItem->purchase_order_id,
                    'po_number'           => $poItem->purchaseOrder?->po_number,
                    'supplier_id'         => $poItem->purchaseOrder?->supplier_id,
                    'supplier_name'       => $poItem->purchaseOrder?->supplier?->name,
                    'product_name'        => $poItem->product_name,
                    'sku'                 => $poItem->sku,
                    'unit_cost'           => (float) $poItem->unit_cost,
                    'ordered_quantity'    => (int) $poItem->quantity,
                    'received_quantity'   => (int) $poItem->received_quantity,
                    'consumed_quantity'   => 0.0,
                    'batches'             => [],
                ];
            }

            $consumed = abs((float) $log->quantity_change);
            $grouped[$key]['consumed_quantity'] += $consumed;
            $grouped[$key]['batches'][] = [
                'batch_id'         => $log->batch_id,
                'batch_number'     => $log->batch_number,
                'quantity_consumed'=> $consumed,
                'unit_cost'        => (float) $log->unit_cost,
                'total_cost'       => (float) $log->total_cost,
                'event_date'       => optional($log->event_date)->toDateTimeString(),
            ];
        }

        // 4. Compute totals per PO item.
        return collect($grouped)
            ->map(function ($row) {
                $row['subtotal'] = round($row['consumed_quantity'] * $row['unit_cost'], 2);
                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * Export production orders to Excel/CSV
     */
    public function export(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status', 'all');
        
        $query = ProductionOrder::with(['inputs', 'outputs', 'location', 'createdBy'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        $orders = $query->get();
        
        // ─── Build Export Data ──────────────────────────────────────────
        $exportData = $orders->map(function($order) {
            return [
                'Production Number' => $order->production_number,
                'Status' => $order->status_label,
                'Created At' => $order->created_at->format('Y-m-d H:i'),
                'Started At' => $order->started_at ? $order->started_at->format('Y-m-d H:i') : '-',
                'Completed At' => $order->completed_at ? $order->completed_at->format('Y-m-d H:i') : '-',
                'Location' => $order->location->name ?? '-',
                'Created By' => $order->createdBy->name ?? '-',
                'Input Quantity' => number_format($order->total_input_quantity, 2),
                'Output Quantity' => number_format($order->total_output_quantity, 2),
                'Input Cost' => number_format($order->total_input_cost, 2),
                'Output Cost' => number_format($order->total_output_cost, 2),
                'Total Cost' => number_format($order->total_cost, 2),
                'Notes' => $order->notes ?? '-',
            ];
        });
        
        // Return as JSON for export
        return response()->json([
            'success' => true,
            'data' => $exportData,
            'filename' => 'production_orders_' . date('Y_m_d') . '.csv',
        ]);
    }


    /**
     * Production Summary Report
     * Comprehensive summary of all production activities — KG-normalized
     */
    public function summary(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);

        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate  = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $status     = $request->get('status', 'all');
        $perPage    = (int) $request->get('per_page', 15);

        // ─── Query ──────────────────────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
            'paymentMethod',
        ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $orders            = $query->get();
        $completedOrders   = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        $inProgressOrders  = $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS);
        $draftOrders       = $orders->where('status', ProductionOrder::STATUS_DRAFT);
        $cancelledOrders   = $orders->where('status', ProductionOrder::STATUS_CANCELLED);

        // ─── KG-normalized rollups ──────────────────────────────────────
        $totalInputKg   = 0.0;
        $totalOutputKg  = 0.0;
        $totalRevenue   = 0.0;
        $totalBatchCost = 0.0;
        $totalLossKg    = 0.0;

        foreach ($orders as $order) {
            $orderInKg  = 0.0;
            $orderOutKg = 0.0;

            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                $weight  = $variant ? (float) $variant->weight : 0;
                $kg      = $weight > 0
                    ? (float) $input->actual_quantity * $weight
                    : (float) $input->actual_quantity;

                $orderInKg += $kg;
            }

            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                $weight  = $variant ? (float) $variant->weight : 0;
                $sell    = $variant
                    ? (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0)
                    : 0;

                $kg = $weight > 0
                    ? (float) $output->actual_quantity * $weight
                    : (float) $output->actual_quantity;

                $orderOutKg += $kg;
                $totalRevenue += (float) $output->actual_quantity * $sell;
            }

            $totalInputKg   += $orderInKg;
            $totalOutputKg  += $orderOutKg;
            $totalBatchCost += (float) $order->total_input_cost;
            $totalLossKg    += max(0, $orderInKg - $orderOutKg);
        }

        // ─── Summary Statistics ─────────────────────────────────────────
        $summary = [
            // Order counts
            'total_orders'       => $orders->count(),
            'completed_orders'   => $completedOrders->count(),
            'in_progress_orders' => $inProgressOrders->count(),
            'draft_orders'       => $draftOrders->count(),
            'cancelled_orders'   => $cancelledOrders->count(),
            'completion_rate'    => $orders->count() > 0
                ? ($completedOrders->count() / $orders->count()) * 100
                : 0,

            // ★ Quantity metrics — everything in KG
            'total_input_quantity'  => $totalInputKg,
            'total_output_quantity' => $totalOutputKg,
            'total_loss_quantity'   => $totalLossKg,

            'total_waste' => $orders->sum(fn($order) =>
                $order->inputs->sum('waste_quantity')
            ),

            // ★ True yield = kg out / kg in
            'overall_yield' => $totalInputKg > 0
                ? ($totalOutputKg / $totalInputKg) * 100
                : 0,

            // ★ Loss %
            'loss_rate' => $totalInputKg > 0
                ? ($totalLossKg / $totalInputKg) * 100
                : 0,

            // ★ Cost metrics
            'total_input_cost'  => $totalBatchCost,
            'total_output_cost' => $totalRevenue,
            'total_cost'        => $totalBatchCost,
            'total_profit'      => $totalRevenue - $totalBatchCost,
            'profit_margin'     => $totalRevenue > 0
                ? (($totalRevenue - $totalBatchCost) / $totalRevenue) * 100
                : 0,
            'cost_efficiency'   => $totalBatchCost > 0
                ? ($totalRevenue / $totalBatchCost) * 100
                : 0,

            'avg_cost_per_order'   => $orders->count() > 0
                ? $totalBatchCost / $orders->count()
                : 0,
            'avg_profit_per_order' => $orders->count() > 0
                ? ($totalRevenue - $totalBatchCost) / $orders->count()
                : 0,

            // Quality metrics
            'total_defective' => $orders->sum(fn($order) =>
                $order->outputs->sum('defective_quantity')
            ),
            'total_quality_accepted' => $orders->sum(fn($order) =>
                $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_ACCEPTED)->count()
            ),
            'total_quality_rejected' => $orders->sum(fn($order) =>
                $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_REJECTED)->count()
            ),
            'quality_acceptance_rate' => (function () use ($orders) {
                $total = $orders->sum(fn($o) => $o->inputs->count());
                if ($total === 0) return 0;
                $accepted = $orders->sum(fn($o) =>
                    $o->inputs->where('quality_status', ProductionOrderInput::QUALITY_ACCEPTED)->count()
                );
                return ($accepted / $total) * 100;
            })(),

            // Payment metrics
            'orders_with_payment'    => $orders->whereNotNull('payment_method_id')->count(),
            'orders_without_payment' => $orders->whereNull('payment_method_id')->count(),
            'total_payment_amount'   => $orders->sum(fn($order) =>
                $order->paymentMethod ? $order->total_input_cost : 0
            ),

            // Time metrics
            'avg_duration_hours' => $completedOrders->count() > 0
                ? $completedOrders->avg(fn($order) =>
                    $order->started_at && $order->completed_at
                        ? $order->started_at->diffInHours($order->completed_at)
                        : 0
                )
                : 0,
        ];

        // ─── Monthly Trends (KG-normalized) ─────────────────────────────
        $monthlyTrends = $orders->groupBy(fn($order) =>
            $order->created_at->format('Y-m')
        )->map(function ($items, $month) {
            $inKg      = 0.0;
            $outKg     = 0.0;
            $revenue   = 0.0;
            $batchCost = 0.0;

            foreach ($items as $order) {
                foreach ($order->inputs as $input) {
                    $v = $input->productVariant;
                    $w = $v ? (float) $v->weight : 0;
                    $inKg += $w > 0
                        ? (float) $input->actual_quantity * $w
                        : (float) $input->actual_quantity;
                }
                foreach ($order->outputs as $output) {
                    $v    = $output->productVariant;
                    $w    = $v ? (float) $v->weight : 0;
                    $sell = $v
                        ? (float) ($v->discount_selling_price ?? $v->selling_price ?? 0)
                        : 0;

                    $outKg += $w > 0
                        ? (float) $output->actual_quantity * $w
                        : (float) $output->actual_quantity;

                    $revenue += (float) $output->actual_quantity * $sell;
                }
                $batchCost += (float) $order->total_input_cost;
            }

            return (object) [
                'month'       => Carbon::parse($month . '-01')->format('M Y'),
                'orders'      => $items->count(),
                'completed'   => $items->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
                'input_kg'    => round($inKg, 2),
                'output_kg'   => round($outKg, 2),
                'input_cost'  => round($batchCost, 2),
                'output_cost' => round($revenue, 2),
                'profit'      => round($revenue - $batchCost, 2),
                'yield'       => $inKg > 0 ? round(($outKg / $inKg) * 100, 2) : 0,
            ];
        })->sortKeys()->values();

        // ─── Top Products (Most Produced) ───────────────────────────────
        $productOutputs = collect();
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if (!$variant) continue;

                $weight = (float) ($variant->weight ?? 0);
                $sell   = (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0);
                $qty    = (float) $output->actual_quantity;

                $productOutputs->push((object) [
                    'variant_id'   => $variant->id,
                    'variant_name' => $variant->name,
                    'sku'          => $variant->sku,
                    'category'     => $variant->product->category->name ?? 'Uncategorized',
                    'quantity'     => $qty,
                    'weight'       => $weight,
                    'total_kg'     => $weight > 0 ? $qty * $weight : $qty,
                    'revenue'      => $qty * $sell,
                ]);
            }
        }

        $topProducts = $productOutputs->groupBy('variant_id')->map(function ($items) {
            $first = $items->first();
            return (object) [
                'variant_id'     => $first->variant_id,
                'variant_name'   => $first->variant_name,
                'sku'            => $first->sku,
                'category'       => $first->category,
                'total_quantity' => $items->sum('quantity'),
                'total_kg'       => $items->sum('total_kg'),
                'total_revenue'  => $items->sum('revenue'),
                'avg_cost_per_kg' => $items->sum('total_kg') > 0
                    ? $items->sum('revenue') / $items->sum('total_kg')
                    : 0,
                'order_count'    => $items->count(),
            ];
        })->sortByDesc('total_kg')->values()->take(10);

        // ─── Top Materials (Most Consumed) ──────────────────────────────
        $materialInputs = collect();
        foreach ($orders as $order) {
            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                if (!$variant) continue;

                $weight = (float) ($variant->weight ?? 0);
                $qty    = (float) $input->actual_quantity;

                $materialInputs->push((object) [
                    'variant_id'   => $variant->id,
                    'variant_name' => $variant->name,
                    'sku'          => $variant->sku,
                    'category'     => $variant->product->category->name ?? 'Uncategorized',
                    'quantity'     => $qty,
                    'weight'       => $weight,
                    'total_kg'     => $weight > 0 ? $qty * $weight : $qty,
                    'cost'         => (float) $input->actual_cost,
                ]);
            }
        }

        $topMaterials = $materialInputs->groupBy('variant_id')->map(function ($items) {
            $first = $items->first();
            return (object) [
                'variant_id'     => $first->variant_id,
                'variant_name'   => $first->variant_name,
                'sku'            => $first->sku,
                'category'       => $first->category,
                'total_quantity' => $items->sum('quantity'),
                'total_kg'       => $items->sum('total_kg'),
                'total_cost'     => $items->sum('cost'),
                'avg_cost_per_kg' => $items->sum('total_kg') > 0
                    ? $items->sum('cost') / $items->sum('total_kg')
                    : 0,
                'order_count'    => $items->count(),
            ];
        })->sortByDesc('total_kg')->values()->take(10);

        // ─── Status Breakdown ───────────────────────────────────────────
        $statusBreakdown = collect([
            (object) ['status' => 'draft',       'label' => __('pagination.draft'),       'count' => $summary['draft_orders'],       'color' => 'secondary'],
            (object) ['status' => 'in_progress', 'label' => __('pagination.in_progress'), 'count' => $summary['in_progress_orders'], 'color' => 'warning'],
            (object) ['status' => 'completed',   'label' => __('pagination.completed'),   'count' => $summary['completed_orders'],   'color' => 'success'],
            (object) ['status' => 'cancelled',   'label' => __('pagination.cancelled'),   'count' => $summary['cancelled_orders'],   'color' => 'danger'],
        ])->filter(fn($item) => $item->count > 0)->values();

        // ─── Location Breakdown (KG-normalized) ─────────────────────────
        $locationBreakdown = $orders->groupBy('location_id')->map(function ($items, $locId) {
            $location = $items->first()->location;

            $inKg = 0.0; $outKg = 0.0; $revenue = 0.0; $batchCost = 0.0;

            foreach ($items as $order) {
                foreach ($order->inputs as $input) {
                    $v = $input->productVariant;
                    $w = $v ? (float) $v->weight : 0;
                    $inKg += $w > 0
                        ? (float) $input->actual_quantity * $w
                        : (float) $input->actual_quantity;
                }
                foreach ($order->outputs as $output) {
                    $v    = $output->productVariant;
                    $w    = $v ? (float) $v->weight : 0;
                    $sell = $v
                        ? (float) ($v->discount_selling_price ?? $v->selling_price ?? 0)
                        : 0;

                    $outKg += $w > 0
                        ? (float) $output->actual_quantity * $w
                        : (float) $output->actual_quantity;

                    $revenue += (float) $output->actual_quantity * $sell;
                }
                $batchCost += (float) $order->total_input_cost;
            }

            return (object) [
                'location_id'   => $locId,
                'location_name' => $location ? $location->name : 'Unknown',
                'orders'        => $items->count(),
                'completed'     => $items->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
                'input_kg'      => round($inKg, 2),
                'output_kg'     => round($outKg, 2),
                'total_cost'    => round($batchCost, 2),
                'revenue'       => round($revenue, 2),
                'profit'        => round($revenue - $batchCost, 2),
                'yield'         => $inKg > 0 ? round(($outKg / $inKg) * 100, 2) : 0,
            ];
        })->values();

        // ─── Filter Options ─────────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $statuses  = [
            ['value' => 'all',                                'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_DRAFT,        'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS,  'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_COMPLETED,    'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_CANCELLED,    'label' => __('pagination.cancelled')],
        ];

        return view('reports.production.summary', compact(
            'summary',
            'monthlyTrends',
            'topProducts',
            'topMaterials',
            'statusBreakdown',
            'locationBreakdown',
            'locations',
            'statuses',
            'startDate',
            'endDate',
            'locationId',
            'status',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Production Cost Analysis Report
     * Detailed analysis of costs, revenue, and per-unit economics — KG-normalized
     */
    public function costAnalysis(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);

        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'all');
        $costType   = $request->get('cost_type', 'all');
        $perPage    = (int) $request->get('per_page', 15);

        // ─── Query ──────────────────────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
        ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                  ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orders          = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);

        // ─── Per-Order Rollups ──────────────────────────────────────────
        // Pre-compute per-order: input KG, output KG, revenue, batch cost,
        // and per-output kg/revenue so we reuse them everywhere.
        $orderRollups = [];
        $allOutputRows = collect();
        $allInputRows  = collect();

        $totalInputCost  = 0.0;
        $totalRevenue    = 0.0;
        $totalInputKg    = 0.0;
        $totalOutputKg   = 0.0;

        foreach ($orders as $order) {
            $orderInputCost = (float) $order->total_input_cost;
            $orderInputKg   = 0.0;
            $orderOutputKg  = 0.0;
            $orderRevenue   = 0.0;

            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                $weight  = $variant ? (float) $variant->weight : 0;
                $qty     = (float) $input->actual_quantity;
                $kg      = $weight > 0 ? $qty * $weight : $qty;
                $cost    = (float) $input->actual_cost;

                $orderInputKg += $kg;

                $allInputRows->push((object) [
                    'order_id'       => $order->id,
                    'order_number'   => $order->production_number,
                    'location_id'    => $order->location_id,
                    'variant_id'     => $variant?->id,
                    'variant_name'   => $variant?->name,
                    'variant_sku'    => $variant?->sku,
                    'category'       => $variant?->product?->category?->name ?? 'Uncategorized',
                    'quantity'       => $qty,
                    'weight'         => $weight,
                    'total_kg'       => $kg,
                    'cost'           => $cost,
                ]);
            }

            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                $weight  = $variant ? (float) $variant->weight : 0;
                $sell    = $variant
                    ? (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0)
                    : 0;
                $qty     = (float) $output->actual_quantity;
                $kg      = $weight > 0 ? $qty * $weight : $qty;
                $revenue = $qty * $sell;

                $orderOutputKg += $kg;
                $orderRevenue  += $revenue;

                $allOutputRows->push((object) [
                    'order_id'        => $order->id,
                    'order_number'    => $order->production_number,
                    'location_id'     => $order->location_id,
                    'variant_id'      => $variant?->id,
                    'variant_name'    => $variant?->name,
                    'variant_sku'     => $variant?->sku,
                    'category'        => $variant?->product?->category?->name ?? 'Uncategorized',
                    'quantity'        => $qty,
                    'weight'          => $weight,
                    'total_kg'        => $kg,
                    'selling_price'   => $sell,
                    'revenue'         => $revenue,
                ]);
            }

            $orderRollups[$order->id] = [
                'input_kg'      => $orderInputKg,
                'output_kg'     => $orderOutputKg,
                'input_cost'    => $orderInputCost,
                'revenue'       => $orderRevenue,
                'profit'        => $orderRevenue - $orderInputCost,
                'yield'         => $orderInputKg > 0 ? ($orderOutputKg / $orderInputKg) * 100 : 0,
                'loss_kg'       => max(0, $orderInputKg - $orderOutputKg),
            ];

            $totalInputCost += $orderInputCost;
            $totalRevenue   += $orderRevenue;
            $totalInputKg   += $orderInputKg;
            $totalOutputKg  += $orderOutputKg;
        }

        $totalProfit   = $totalRevenue - $totalInputCost;
        $totalLossKg   = max(0, $totalInputKg - $totalOutputKg);
        $overallYield  = $totalInputKg > 0 ? ($totalOutputKg / $totalInputKg) * 100 : 0;

        // ─── Cost Summary ──────────────────────────────────────────────
        $costSummary = [
            // Input
            'total_input_cost'         => $totalInputCost,
            'avg_input_cost_per_order' => $orders->count() > 0 ? $totalInputCost / $orders->count() : 0,
            'min_input_cost'           => $orders->min('total_input_cost') ?? 0,
            'max_input_cost'           => $orders->max('total_input_cost') ?? 0,
            'total_input_kg'           => $totalInputKg,

            // Output / Revenue
            'total_output_cost'         => $totalRevenue,       // REVENUE (kept name for BC)
            'avg_output_cost_per_order' => $orders->count() > 0 ? $totalRevenue / $orders->count() : 0,
            'min_output_cost'           => $orders->count() > 0
                ? collect($orderRollups)->min('revenue')
                : 0,
            'max_output_cost'           => $orders->count() > 0
                ? collect($orderRollups)->max('revenue')
                : 0,
            'total_output_kg'           => $totalOutputKg,
            'total_loss_kg'             => $totalLossKg,

            // Totals (batch cost == input cost in this model)
            'total_cost'         => $totalInputCost,
            'avg_cost_per_order' => $orders->count() > 0 ? $totalInputCost / $orders->count() : 0,

            // Profit
            'total_profit'         => $totalProfit,
            'avg_profit_per_order' => $orders->count() > 0 ? $totalProfit / $orders->count() : 0,

            // Ratios
            'cost_efficiency' => $totalInputCost > 0
                ? ($totalRevenue / $totalInputCost) * 100
                : 0,
            'profit_margin' => $totalRevenue > 0
                ? ($totalProfit / $totalRevenue) * 100
                : 0,
            'overall_yield' => $overallYield,
            'loss_rate'     => $totalInputKg > 0
                ? ($totalLossKg / $totalInputKg) * 100
                : 0,

            // Cost per kg produced / cost per kg consumed
            'cost_per_kg_input'  => $totalInputKg  > 0 ? $totalInputCost / $totalInputKg  : 0,
            'cost_per_kg_output' => $totalOutputKg > 0 ? $totalInputCost / $totalOutputKg : 0,
            'revenue_per_kg'     => $totalOutputKg > 0 ? $totalRevenue   / $totalOutputKg : 0,
        ];

        // ─── Input Cost by Category ─────────────────────────────────────
        $categoryInputCosts = $allInputRows
            ->groupBy('category')
            ->map(function ($items, $category) {
                $cost = $items->sum('cost');
                $kg   = $items->sum('total_kg');
                return (object) [
                    'category'          => $category,
                    'total_cost'        => $cost,
                    'total_quantity'    => $items->sum('quantity'),
                    'total_kg'          => $kg,
                    'avg_cost_per_unit' => $items->sum('quantity') > 0
                        ? $cost / $items->sum('quantity')
                        : 0,
                    'avg_cost_per_kg'   => $kg > 0 ? $cost / $kg : 0,
                    'item_count'        => $items->count(),
                ];
            })
            ->sortByDesc('total_cost')
            ->values();

        // ─── Output Revenue by Category ─────────────────────────────────
        $categoryOutputCosts = $allOutputRows
            ->groupBy('category')
            ->map(function ($items, $category) {
                $rev = $items->sum('revenue');
                $kg  = $items->sum('total_kg');
                return (object) [
                    'category'          => $category,
                    'total_cost'        => $rev,
                    'total_quantity'    => $items->sum('quantity'),
                    'total_kg'          => $kg,
                    'avg_cost_per_unit' => $items->sum('quantity') > 0
                        ? $rev / $items->sum('quantity')
                        : 0,
                    'avg_cost_per_kg'   => $kg > 0 ? $rev / $kg : 0,
                    'item_count'        => $items->count(),
                ];
            })
            ->sortByDesc('total_cost')
            ->values();

        // ─── Cost Per Unit / Per KG by Variant ──────────────────────────
        // Group every output row by variant, then compute:
        //  - Allocated batch cost (share of input cost by this variant's kg
        //    of the parent order's total output kg)
        //  - Avg selling price per unit
        //  - Avg revenue per unit
        //  - Avg profit per unit and per kg
        $costPerUnitRaw = $allOutputRows->map(function ($row) use ($orderRollups) {
            $rollup      = $orderRollups[$row->order_id] ?? null;
            $orderOutKg  = $rollup['output_kg'] ?? 0;
            $orderInCost = $rollup['input_cost'] ?? 0;

            // Allocate batch cost by this variant's share of the order's output kg
            $allocatedCost = $orderOutKg > 0
                ? ($row->total_kg / $orderOutKg) * $orderInCost
                : 0;

            $qty        = (float) $row->quantity;
            $kg         = (float) $row->total_kg;
            $revenue    = (float) $row->revenue;
            $unitCost   = $qty > 0 ? $allocatedCost / $qty : 0;
            $costPerKg  = $kg  > 0 ? $allocatedCost / $kg  : 0;
            $revPerUnit = $qty > 0 ? $revenue / $qty : 0;
            $revPerKg   = $kg  > 0 ? $revenue / $kg  : 0;
            $profitUnit = $revPerUnit - $unitCost;
            $profitKg   = $revPerKg   - $costPerKg;
            $margin     = $revenue > 0
                ? (($revenue - $allocatedCost) / $revenue) * 100
                : 0;

            return (object) [
                'order_number'      => $row->order_number,
                'variant_id'        => $row->variant_id,
                'variant_name'      => $row->variant_name,
                'variant_sku'       => $row->variant_sku,
                'category'          => $row->category,
                'quantity'          => $qty,
                'weight'            => $row->weight,
                'total_kg'          => $kg,
                'allocated_cost'    => $allocatedCost,
                'revenue'           => $revenue,
                'cost_per_unit'     => $unitCost,
                'cost_per_kg'       => $costPerKg,
                'selling_price'     => $row->selling_price,
                'revenue_per_unit'  => $revPerUnit,
                'revenue_per_kg'    => $revPerKg,
                'profit_per_unit'   => $profitUnit,
                'profit_per_kg'     => $profitKg,
                'profit_margin'     => $margin,
            ];
        });

        $costPerUnitSummary = $costPerUnitRaw
            ->groupBy('variant_id')
            ->map(function ($items) {
                $first = $items->first();
                $qty   = $items->sum('quantity');
                $kg    = $items->sum('total_kg');
                $cost  = $items->sum('allocated_cost');
                $rev   = $items->sum('revenue');

                return (object) [
                    'variant_id'          => $first->variant_id,
                    'variant_name'        => $first->variant_name,
                    'variant_sku'         => $first->variant_sku,
                    'category'            => $first->category,
                    'weight'              => $first->weight,
                    'total_quantity'      => $qty,
                    'total_kg'            => $kg,
                    'allocated_cost'      => $cost,
                    'revenue'             => $rev,
                    'avg_cost_per_unit'   => $qty > 0 ? $cost / $qty : 0,
                    'avg_cost_per_kg'     => $kg  > 0 ? $cost / $kg  : 0,
                    'avg_selling_price'   => $items->avg('selling_price'),
                    'avg_revenue_per_kg'  => $kg  > 0 ? $rev  / $kg  : 0,
                    'avg_profit_per_unit' => $qty > 0 ? ($rev - $cost) / $qty : 0,
                    'avg_profit_per_kg'   => $kg  > 0 ? ($rev - $cost) / $kg  : 0,
                    'avg_profit_margin'   => $rev > 0 ? (($rev - $cost) / $rev) * 100 : 0,
                    'order_count'         => $items->pluck('order_number')->unique()->count(),
                ];
            })
            ->sortByDesc('avg_profit_margin')
            ->values();

        // ─── Monthly Trends ─────────────────────────────────────────────
        $monthlyCostTrends = $orders->groupBy(fn($order) =>
            $order->created_at->format('Y-m')
        )->map(function ($items, $month) use ($orderRollups) {
            $inCost  = 0.0;
            $rev     = 0.0;
            $inKg    = 0.0;
            $outKg   = 0.0;

            foreach ($items as $order) {
                $r = $orderRollups[$order->id] ?? null;
                if (!$r) continue;

                $inCost += $r['input_cost'];
                $rev    += $r['revenue'];
                $inKg   += $r['input_kg'];
                $outKg  += $r['output_kg'];
            }

            return (object) [
                'month'              => Carbon::parse($month . '-01')->format('M Y'),
                'input_cost'         => round($inCost, 2),
                'output_cost'        => round($rev, 2),
                'total_cost'         => round($inCost, 2),
                'profit'             => round($rev - $inCost, 2),
                'orders'             => $items->count(),
                'input_kg'           => round($inKg, 2),
                'output_kg'          => round($outKg, 2),
                'yield'              => $inKg > 0 ? round(($outKg / $inKg) * 100, 2) : 0,
                'avg_cost_per_order' => $items->count() > 0
                    ? round($inCost / $items->count(), 2)
                    : 0,
            ];
        })->sortKeys()->values();

        // ─── Cost by Status ────────────────────────────────────────────
        $costByStatus = collect([
            'draft' => (object) [
                'status'     => __('pagination.draft'),
                'orders'     => $orders->where('status', ProductionOrder::STATUS_DRAFT)->count(),
                'total_cost' => $orders->where('status', ProductionOrder::STATUS_DRAFT)->sum('total_input_cost'),
            ],
            'in_progress' => (object) [
                'status'     => __('pagination.in_progress'),
                'orders'     => $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->count(),
                'total_cost' => $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->sum('total_input_cost'),
            ],
            'completed' => (object) [
                'status'     => __('pagination.completed'),
                'orders'     => $orders->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
                'total_cost' => $orders->where('status', ProductionOrder::STATUS_COMPLETED)->sum('total_input_cost'),
            ],
            'cancelled' => (object) [
                'status'     => __('pagination.cancelled'),
                'orders'     => $orders->where('status', ProductionOrder::STATUS_CANCELLED)->count(),
                'total_cost' => $orders->where('status', ProductionOrder::STATUS_CANCELLED)->sum('total_input_cost'),
            ],
        ])->filter(fn($item) => $item->orders > 0)->values();

        // ─── Filter Options ────────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants  = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_DRAFT,       'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_CANCELLED,   'label' => __('pagination.cancelled')],
        ];

        $costTypes = [
            ['value' => 'all',    'label' => __('pagination.all_costs')],
            ['value' => 'input',  'label' => __('pagination.input_costs')],
            ['value' => 'output', 'label' => __('pagination.output_costs')],
        ];

        // ─── Pagination ────────────────────────────────────────────────
        $paginatedCostPerUnit = $this->paginateCollection($costPerUnitSummary, $perPage, 'page');

        return view('reports.production.cost-analysis', compact(
            'costSummary',
            'categoryInputCosts',
            'categoryOutputCosts',
            'costPerUnitSummary',
            'paginatedCostPerUnit',
            'monthlyCostTrends',
            'costByStatus',
            'locations',
            'variants',
            'statuses',
            'costTypes',
            'startDate',
            'endDate',
            'locationId',
            'variantId',
            'status',
            'costType',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Production Efficiency Report
     * KG-normalized efficiency across all production orders
     */
    public function efficiency(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);

        // ─── Filters ────────────────────────────────────────────────────
        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'completed');
        $perPage    = (int) $request->get('per_page', 15);

        // ─── Query ──────────────────────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
            'startedBy',
            'completedBy',
        ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                  ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orders          = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        $inProgressOrders = $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS);

        // ─── Per-order rollup (KG-normalized) ──────────────────────────
        $orderRollups = [];

        foreach ($orders as $order) {
            $inKg  = 0.0;
            $outKg = 0.0;
            $inCost = (float) $order->total_input_cost;
            $revenue = 0.0;

            $defective = 0.0;   // sum of defective qty across outputs (bags)
            $defectiveKg = 0.0; // kg of defective outputs
            $waste    = 0.0;    // input waste (in variant units)
            $wasteKg  = 0.0;    // input waste in kg

            foreach ($order->inputs as $input) {
                $v = $input->productVariant;
                $w = $v ? (float) $v->weight : 0;
                $q = (float) $input->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;

                $inKg += $kg;

                $wq = (float) $input->waste_quantity;
                $waste += $wq;
                $wasteKg += $w > 0 ? $wq * $w : $wq;
            }

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                $w = $v ? (float) $v->weight : 0;
                $q = (float) $output->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;

                $sell = $v
                    ? (float) ($v->discount_selling_price ?? $v->selling_price ?? 0)
                    : 0;

                $outKg   += $kg;
                $revenue += $q * $sell;

                $dq = (float) $output->defective_quantity;
                $defective += $dq;
                $defectiveKg += $w > 0 ? $dq * $w : $dq;
            }

            $duration = ($order->started_at && $order->completed_at)
                ? $order->started_at->diffInMinutes($order->completed_at) / 60
                : 0;

            $orderRollups[$order->id] = [
                'input_kg'      => $inKg,
                'output_kg'     => $outKg,
                'input_cost'    => $inCost,
                'revenue'       => $revenue,
                'profit'        => $revenue - $inCost,
                'yield'         => $inKg > 0 ? ($outKg / $inKg) * 100 : 0,
                'loss_kg'       => max(0, $inKg - $outKg),
                'defective'     => $defective,
                'defective_kg'  => $defectiveKg,
                'waste'         => $waste,
                'waste_kg'      => $wasteKg,
                'duration'      => $duration,
            ];
        }

        // ─── Aggregate Totals ──────────────────────────────────────────
        $totalInputKg    = 0.0;
        $totalOutputKg   = 0.0;
        $totalInputCost  = 0.0;
        $totalRevenue    = 0.0;
        $totalDefectiveKg = 0.0;
        $totalWasteKg    = 0.0;
        $totalDuration   = 0.0;

        foreach ($orderRollups as $r) {
            $totalInputKg     += $r['input_kg'];
            $totalOutputKg    += $r['output_kg'];
            $totalInputCost   += $r['input_cost'];
            $totalRevenue     += $r['revenue'];
            $totalDefectiveKg += $r['defective_kg'];
            $totalWasteKg     += $r['waste_kg'];
            $totalDuration    += $r['duration'];
        }

        $totalProfit = $totalRevenue - $totalInputCost;

        $overallYield = $totalInputKg > 0
            ? ($totalOutputKg / $totalInputKg) * 100
            : 0;

        $overallCostEfficiency = $totalInputCost > 0
            ? ($totalRevenue / $totalInputCost) * 100
            : 0;

        $qualityRate = $totalOutputKg > 0
            ? (($totalOutputKg - $totalDefectiveKg) / $totalOutputKg) * 100
            : 0;

        $wasteRate = $totalInputKg > 0
            ? ($totalWasteKg / $totalInputKg) * 100
            : 0;

        $profitMargin = $totalRevenue > 0
            ? ($totalProfit / $totalRevenue) * 100
            : 0;

        $avgDurationHours = $completedOrders->count() > 0
            ? $totalDuration / $completedOrders->count()
            : 0;

        // ─── Efficiency Summary ─────────────────────────────────────────
        $efficiencySummary = [
            'total_orders'             => $orders->count(),
            'completed_orders'         => $completedOrders->count(),
            'in_progress_orders'       => $inProgressOrders->count(),
            'overall_yield'            => $overallYield,
            'overall_cost_efficiency'  => $overallCostEfficiency,
            'avg_duration_hours'       => $avgDurationHours,
            'quality_rate'             => $qualityRate,
            'waste_rate'               => $wasteRate,
            'profit_margin'            => $profitMargin,

            'total_input_qty'   => $totalInputKg,      // kg
            'total_output_qty'  => $totalOutputKg,     // kg
            'total_input_kg'    => $totalInputKg,
            'total_output_kg'   => $totalOutputKg,
            'total_loss_kg'     => max(0, $totalInputKg - $totalOutputKg),
            'loss_rate'         => $totalInputKg > 0
                ? (max(0, $totalInputKg - $totalOutputKg) / $totalInputKg) * 100
                : 0,

            'total_waste'       => $totalWasteKg,      // kg
            'total_waste_kg'    => $totalWasteKg,
            'total_defective'   => $totalDefectiveKg,  // kg
            'total_defective_kg'=> $totalDefectiveKg,

            'total_profit'      => $totalProfit,
            'total_input_cost'  => $totalInputCost,
            'total_output_cost' => $totalRevenue,      // revenue
            'total_cost'        => $totalInputCost,

            'cost_per_kg_input'  => $totalInputKg  > 0 ? $totalInputCost / $totalInputKg  : 0,
            'cost_per_kg_output' => $totalOutputKg > 0 ? $totalInputCost / $totalOutputKg : 0,
            'revenue_per_kg'     => $totalOutputKg > 0 ? $totalRevenue   / $totalOutputKg : 0,
        ];

        // ─── Efficiency by Order ────────────────────────────────────────
        $efficiencyByOrder = $orders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];

            $yield     = $r['yield'];
            $costEff   = $r['input_cost'] > 0 ? ($r['revenue'] / $r['input_cost']) * 100 : 0;
            $quality   = $r['output_kg'] > 0
                ? (($r['output_kg'] - $r['defective_kg']) / $r['output_kg']) * 100
                : 0;
            $wastePct  = $r['input_kg'] > 0
                ? ($r['waste_kg'] / $r['input_kg']) * 100
                : 0;
            $margin    = $r['revenue'] > 0 ? ($r['profit'] / $r['revenue']) * 100 : 0;

            return (object) [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'status'            => $order->status,
                'status_label'      => $order->status_label,
                'status_badge'      => $order->status_badge,
                'location'          => $order->location->name ?? '-',
                'created_at'        => $order->created_at,
                'started_at'        => $order->started_at,
                'completed_at'      => $order->completed_at,

                'input_quantity'    => $r['input_kg'],
                'output_quantity'   => $r['output_kg'],
                'loss_kg'           => $r['loss_kg'],

                'input_cost'        => $r['input_cost'],
                'output_cost'       => $r['revenue'],
                'total_cost'        => $r['input_cost'],
                'profit'            => $r['profit'],

                'yield'             => $yield,
                'cost_efficiency'   => $costEff,
                'duration_hours'    => round($r['duration'], 2),
                'defective'         => $r['defective'],
                'defective_kg'      => $r['defective_kg'],
                'waste'             => $r['waste'],
                'waste_kg'          => $r['waste_kg'],
                'quality_rate'      => $quality,
                'waste_rate'        => $wastePct,
                'profit_margin'     => $margin,
                'created_by'        => $order->createdBy->name ?? '-',
            ];
        });

        $paginatedEfficiency = $this->paginateCollection($efficiencyByOrder, $perPage, 'page');

        // ─── Monthly Trends ─────────────────────────────────────────────
        $monthlyEfficiency = $completedOrders->groupBy(function ($order) {
            return $order->completed_at
                ? $order->completed_at->format('Y-m')
                : $order->created_at->format('Y-m');
        })->map(function ($items, $month) use ($orderRollups) {
            $inKg = 0.0; $outKg = 0.0; $inCost = 0.0; $rev = 0.0;
            $defKg = 0.0; $wasteKg = 0.0; $dur = 0.0;

            foreach ($items as $order) {
                $r = $orderRollups[$order->id] ?? null;
                if (!$r) continue;

                $inKg    += $r['input_kg'];
                $outKg   += $r['output_kg'];
                $inCost  += $r['input_cost'];
                $rev     += $r['revenue'];
                $defKg   += $r['defective_kg'];
                $wasteKg += $r['waste_kg'];
                $dur     += $r['duration'];
            }

            return (object) [
                'month'           => Carbon::parse($month . '-01')->format('M Y'),
                'orders'          => $items->count(),
                'yield'           => $inKg > 0 ? round(($outKg / $inKg) * 100, 2) : 0,
                'cost_efficiency' => $inCost > 0 ? round(($rev / $inCost) * 100, 2) : 0,
                'quality_rate'    => $outKg > 0 ? round((($outKg - $defKg) / $outKg) * 100, 2) : 0,
                'waste_rate'      => $inKg > 0 ? round(($wasteKg / $inKg) * 100, 2) : 0,
                'profit_margin'   => $rev > 0 ? round((($rev - $inCost) / $rev) * 100, 2) : 0,
                'avg_duration'    => $items->count() > 0
                    ? round($dur / $items->count(), 2)
                    : 0,
                'profit'          => round($rev - $inCost, 2),
                'loss_kg'         => round(max(0, $inKg - $outKg), 2),
            ];
        })->sortKeys()->values();

        // ─── Efficiency by Location ─────────────────────────────────────
        $efficiencyByLocation = $completedOrders->groupBy('location_id')->map(function ($items) use ($orderRollups) {
            $location = $items->first()->location;

            $inKg = 0.0; $outKg = 0.0; $inCost = 0.0; $rev = 0.0;
            $defKg = 0.0; $wasteKg = 0.0; $dur = 0.0;

            foreach ($items as $order) {
                $r = $orderRollups[$order->id] ?? null;
                if (!$r) continue;

                $inKg    += $r['input_kg'];
                $outKg   += $r['output_kg'];
                $inCost  += $r['input_cost'];
                $rev     += $r['revenue'];
                $defKg   += $r['defective_kg'];
                $wasteKg += $r['waste_kg'];
                $dur     += $r['duration'];
            }

            return (object) [
                'location_name'   => $location ? $location->name : 'Unknown',
                'orders'          => $items->count(),
                'yield'           => $inKg > 0 ? round(($outKg / $inKg) * 100, 2) : 0,
                'cost_efficiency' => $inCost > 0 ? round(($rev / $inCost) * 100, 2) : 0,
                'quality_rate'    => $outKg > 0 ? round((($outKg - $defKg) / $outKg) * 100, 2) : 0,
                'waste_rate'      => $inKg > 0 ? round(($wasteKg / $inKg) * 100, 2) : 0,
                'profit_margin'   => $rev > 0 ? round((($rev - $inCost) / $rev) * 100, 2) : 0,
                'avg_duration'    => $items->count() > 0
                    ? round($dur / $items->count(), 2)
                    : 0,
                'profit'          => round($rev - $inCost, 2),
            ];
        })->values();

        // ─── Efficiency by Product ──────────────────────────────────────
        // Allocate batch cost by output-kg share per order, then aggregate per variant.
        $productEfficiencyRaw = collect();

        foreach ($completedOrders as $order) {
            $rollup = $orderRollups[$order->id] ?? null;
            if (!$rollup || $rollup['output_kg'] <= 0) continue;

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;

                $w = (float) ($v->weight ?? 0);
                $q = (float) $output->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;
                $dq = (float) $output->defective_quantity;
                $defKg = $w > 0 ? $dq * $w : $dq;

                $sell    = (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);
                $revenue = $q * $sell;

                // Allocate batch cost by this output's share of the order's total output kg
                $allocatedCost = $rollup['output_kg'] > 0
                    ? ($kg / $rollup['output_kg']) * $rollup['input_cost']
                    : 0;

                $productEfficiencyRaw->push((object) [
                    'variant_id'      => $v->id,
                    'variant_name'    => $v->name,
                    'variant_sku'     => $v->sku,
                    'weight'          => $w,
                    'category'        => $v->product->category->name ?? 'Uncategorized',
                    'quantity'        => $q,
                    'total_kg'        => $kg,
                    'defective'       => $dq,
                    'defective_kg'    => $defKg,
                    'revenue'         => $revenue,
                    'allocated_cost'  => $allocatedCost,
                    'selling_price'   => $sell,
                ]);
            }
        }

        $productEfficiencySummary = $productEfficiencyRaw
            ->groupBy('variant_id')
            ->map(function ($items) {
                $first = $items->first();
                $qty   = $items->sum('quantity');
                $kg    = $items->sum('total_kg');
                $defKg = $items->sum('defective_kg');
                $rev   = $items->sum('revenue');
                $cost  = $items->sum('allocated_cost');
                $profit = $rev - $cost;

                return (object) [
                    'variant_name'      => $first->variant_name,
                    'variant_sku'       => $first->variant_sku,
                    'category'          => $first->category,
                    'total_quantity'    => $qty,
                    'total_kg'          => $kg,
                    'total_cost'        => $cost,
                    'total_input_cost'  => $cost,        // alias for BC
                    'total_revenue'     => $rev,
                    'total_profit'      => $profit,
                    'total_defective'   => $defKg,
                    'profit_margin'     => $rev > 0 ? ($profit / $rev) * 100 : 0,
                    'quality_rate'      => $kg > 0 ? (($kg - $defKg) / $kg) * 100 : 0,
                    'cost_per_unit'     => $qty > 0 ? $cost / $qty : 0,
                    'cost_per_kg'       => $kg  > 0 ? $cost / $kg  : 0,
                    'profit_per_unit'   => $qty > 0 ? $profit / $qty : 0,
                    'profit_per_kg'     => $kg  > 0 ? $profit / $kg  : 0,
                    'order_count'       => $items->count(),
                ];
            })
            ->sortByDesc('total_profit')
            ->values()
            ->take(10);

        // ─── Filter Options ─────────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants  = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_DRAFT,       'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_CANCELLED,   'label' => __('pagination.cancelled')],
        ];

        return view('reports.production.efficiency', compact(
            'efficiencySummary',
            'paginatedEfficiency',
            'monthlyEfficiency',
            'efficiencyByLocation',
            'productEfficiencySummary',
            'locations',
            'variants',
            'statuses',
            'startDate',
            'endDate',
            'locationId',
            'variantId',
            'status',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Production Inventory Impact Report
     * KG-normalized view of how production orders move inventory
     */
    public function inventoryImpact(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);

        // ─── Filters ────────────────────────────────────────────────────
        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'completed');
        $perPage    = (int) $request->get('per_page', 15);

        // ─── Query ──────────────────────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
        ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                  ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orders          = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);

        // ─── Per-order + per-row rollups (all KG-normalized) ────────────
        $orderRollups  = [];
        $consumedRows  = collect();   // inputs
        $producedRows  = collect();   // outputs

        foreach ($orders as $order) {
            $inKg    = 0.0;
            $outKg   = 0.0;
            $inCost  = (float) $order->total_input_cost;
            $revenue = 0.0;
            $defKg   = 0.0;
            $wasteKg = 0.0;

            foreach ($order->inputs as $input) {
                $v = $input->productVariant;
                if (!$v) continue;

                $w = (float) ($v->weight ?? 0);
                $q = (float) $input->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;

                $inKg += $kg;

                $wq = (float) $input->waste_quantity;
                $wasteKg += $w > 0 ? $wq * $w : $wq;

                $consumedRows->push((object) [
                    'order_id'       => $order->id,
                    'order_number'   => $order->production_number,
                    'order_date'     => $order->created_at,
                    'location_id'    => $order->location_id,
                    'location'       => $order->location->name ?? '-',
                    'variant_id'     => $v->id,
                    'variant_name'   => $v->name,
                    'variant_sku'    => $v->sku,
                    'weight'         => $w,
                    'category'       => $v->product->category->name ?? 'Uncategorized',
                    'quantity'       => $q,             // bags/units
                    'total_kg'       => $kg,
                    'cost'           => (float) $input->actual_cost,
                ]);
            }

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;

                $w = (float) ($v->weight ?? 0);
                $q = (float) $output->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;
                $sell = (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);
                $rev  = $q * $sell;

                $outKg   += $kg;
                $revenue += $rev;

                $dq = (float) $output->defective_quantity;
                $defKg += $w > 0 ? $dq * $w : $dq;

                $producedRows->push((object) [
                    'order_id'       => $order->id,
                    'order_number'   => $order->production_number,
                    'order_date'     => $order->created_at,
                    'location_id'    => $order->location_id,
                    'location'       => $order->location->name ?? '-',
                    'variant_id'     => $v->id,
                    'variant_name'   => $v->name,
                    'variant_sku'    => $v->sku,
                    'weight'         => $w,
                    'category'       => $v->product->category->name ?? 'Uncategorized',
                    'quantity'       => $q,
                    'total_kg'       => $kg,
                    'revenue'        => $rev,
                    'selling_price'  => $sell,
                    'defective'      => $dq,
                    'defective_kg'   => $w > 0 ? $dq * $w : $dq,
                ]);
            }

            $orderRollups[$order->id] = [
                'input_kg'   => $inKg,
                'output_kg'  => $outKg,
                'input_cost' => $inCost,
                'revenue'    => $revenue,
                'net_kg'     => $outKg - $inKg,
                'net_value'  => $revenue - $inCost,
                'defective'  => $defKg,
                'waste'      => $wasteKg,
            ];
        }

        // ─── Totals ─────────────────────────────────────────────────────
        $totalInputKg    = 0.0;
        $totalOutputKg   = 0.0;
        $totalInputValue = 0.0;
        $totalRevenue    = 0.0;
        $totalDefKg      = 0.0;
        $totalWasteKg    = 0.0;

        foreach ($orderRollups as $r) {
            $totalInputKg    += $r['input_kg'];
            $totalOutputKg   += $r['output_kg'];
            $totalInputValue += $r['input_cost'];
            $totalRevenue    += $r['revenue'];
            $totalDefKg      += $r['defective'];
            $totalWasteKg    += $r['waste'];
        }

        $netInventoryChange = $totalOutputKg - $totalInputKg;
        $netValueChange     = $totalRevenue - $totalInputValue;

        // ─── Consumed (inputs) by variant ──────────────────────────────
        $consumedSummary = $consumedRows
            ->groupBy('variant_id')
            ->map(function ($items) {
                $first = $items->first();
                return (object) [
                    'variant_id'     => $first->variant_id,
                    'variant_name'   => $first->variant_name,
                    'variant_sku'    => $first->variant_sku,
                    'category'       => $first->category,
                    'weight'         => $first->weight,
                    'total_quantity' => $items->sum('quantity'),
                    'total_kg'       => $items->sum('total_kg'),
                    'total_cost'     => $items->sum('cost'),
                    'avg_cost_per_kg'=> $items->sum('total_kg') > 0
                        ? $items->sum('cost') / $items->sum('total_kg')
                        : 0,
                    'order_count'    => $items->pluck('order_id')->unique()->count(),
                ];
            })
            ->sortByDesc('total_kg')
            ->values();

        // ─── Produced (outputs) by variant ─────────────────────────────
        $producedSummary = $producedRows
            ->groupBy('variant_id')
            ->map(function ($items) {
                $first = $items->first();
                return (object) [
                    'variant_id'     => $first->variant_id,
                    'variant_name'   => $first->variant_name,
                    'variant_sku'    => $first->variant_sku,
                    'category'       => $first->category,
                    'weight'         => $first->weight,
                    'total_quantity' => $items->sum('quantity'),
                    'total_kg'       => $items->sum('total_kg'),
                    'total_revenue'  => $items->sum('revenue'),
                    'avg_revenue_per_kg' => $items->sum('total_kg') > 0
                        ? $items->sum('revenue') / $items->sum('total_kg')
                        : 0,
                    'order_count'    => $items->pluck('order_id')->unique()->count(),
                ];
            })
            ->sortByDesc('total_kg')
            ->values();

        // ─── Net impact by product ─────────────────────────────────────
        $allVariantIds = $consumedRows->pluck('variant_id')
            ->merge($producedRows->pluck('variant_id'))
            ->unique()
            ->filter();

        $netImpactByProduct = collect();

        foreach ($allVariantIds as $vid) {
            $consumed = $consumedRows->where('variant_id', $vid);
            $produced = $producedRows->where('variant_id', $vid);

            $consumedKg  = (float) $consumed->sum('total_kg');
            $producedKg  = (float) $produced->sum('total_kg');
            $consumedVal = (float) $consumed->sum('cost');
            $producedVal = (float) $produced->sum('revenue');

            $firstConsumed = $consumed->first();
            $firstProduced = $produced->first();
            $src = $firstConsumed ?: $firstProduced;
            if (!$src) continue;

            $netKg    = $producedKg - $consumedKg;
            $netValue = $producedVal - $consumedVal;

            $netImpactByProduct->push((object) [
                'variant_id'        => $vid,
                'variant_name'      => $src->variant_name,
                'variant_sku'       => $src->variant_sku,
                'category'          => $src->category,
                'consumed_quantity' => $consumedKg,
                'produced_quantity' => $producedKg,
                'net_quantity'      => $netKg,
                'consumed_cost'     => $consumedVal,
                'produced_cost'     => $producedVal,
                'net_value'         => $netValue,
                'order_count'       => $consumed->pluck('order_id')
                                            ->merge($produced->pluck('order_id'))
                                            ->unique()->count(),
                'impact_type'       => $netKg > 0 ? 'net_producer'
                    : ($netKg < 0 ? 'net_consumer' : 'neutral'),
                'impact_color'      => $netKg > 0 ? 'success'
                    : ($netKg < 0 ? 'danger' : 'secondary'),
            ]);
        }

        $netImpactSorted = $netImpactByProduct->sortByDesc('net_quantity')->values();

        // ─── Impact by category ────────────────────────────────────────
        $categoryImpact = $netImpactByProduct
            ->groupBy('category')
            ->map(function ($items, $category) {
                return (object) [
                    'category'          => $category,
                    'consumed_quantity' => $items->sum('consumed_quantity'),
                    'produced_quantity' => $items->sum('produced_quantity'),
                    'net_quantity'      => $items->sum('net_quantity'),
                    'consumed_value'    => $items->sum('consumed_cost'),
                    'produced_value'    => $items->sum('produced_cost'),
                    'net_value'         => $items->sum('net_value'),
                    'product_count'     => $items->count(),
                ];
            })
            ->values();

        // ─── Monthly impact ────────────────────────────────────────────
        $monthlyImpact = $completedOrders
            ->groupBy(fn($order) => $order->completed_at
                ? $order->completed_at->format('Y-m')
                : $order->created_at->format('Y-m'))
            ->map(function ($items) use ($orderRollups) {
                $inKg = 0.0; $outKg = 0.0;
                $inVal = 0.0; $rev = 0.0;

                foreach ($items as $order) {
                    $r = $orderRollups[$order->id] ?? null;
                    if (!$r) continue;

                    $inKg  += $r['input_kg'];
                    $outKg += $r['output_kg'];
                    $inVal += $r['input_cost'];
                    $rev   += $r['revenue'];
                }

                return (object) [
                    'month'           => Carbon::parse($r['input_kg'] > 0 ? $items->first()->created_at->startOfMonth() : $items->first()->created_at)->format('M Y'),
                    'orders'          => $items->count(),
                    'input_quantity'  => round($inKg, 2),
                    'output_quantity' => round($outKg, 2),
                    'net_quantity'    => round($outKg - $inKg, 2),
                    'input_value'     => round($inVal, 2),
                    'output_value'    => round($rev, 2),
                    'net_value'       => round($rev - $inVal, 2),
                ];
            })
            ->sortKeys()
            ->values();

        // ─── Monthly impact ────────────────────────────────────────────
        $monthlyImpact = $completedOrders
            ->groupBy(fn($order) => $order->completed_at
                ? $order->completed_at->format('Y-m')
                : $order->created_at->format('Y-m'))
            ->map(function ($items, $month) use ($orderRollups) {
                $inKg = 0.0; $outKg = 0.0;
                $inVal = 0.0; $rev = 0.0;

                foreach ($items as $order) {
                    $r = $orderRollups[$order->id] ?? null;
                    if (!$r) continue;

                    $inKg  += $r['input_kg'];
                    $outKg += $r['output_kg'];
                    $inVal += $r['input_cost'];
                    $rev   += $r['revenue'];
                }

                return (object) [
                    'month'           => Carbon::parse($month . '-01')->format('M Y'),
                    'orders'          => $items->count(),
                    'input_quantity'  => round($inKg, 2),
                    'output_quantity' => round($outKg, 2),
                    'net_quantity'    => round($outKg - $inKg, 2),
                    'input_value'     => round($inVal, 2),
                    'output_value'    => round($rev, 2),
                    'net_value'       => round($rev - $inVal, 2),
                ];
            })
            ->sortKeys()
            ->values();

        
            // ─── Top consumed / produced ───────────────────────────────────
        $topConsumed = $consumedSummary->take(10);
        $topProduced = $producedSummary->take(10);

        // ─── Impact by order ───────────────────────────────────────────
        $impactByOrder = $completedOrders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];

            return (object) [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'status'            => $order->status_label,
                'status_badge'      => $order->status_badge,
                'location'          => $order->location->name ?? '-',
                'created_at'        => $order->created_at,

                'input_quantity'    => $r['input_kg'],
                'output_quantity'   => $r['output_kg'],
                'net_quantity'      => $r['net_kg'],

                'input_value'       => $r['input_cost'],
                'output_value'      => $r['revenue'],
                'net_value'         => $r['net_value'],

                'defective'         => $r['defective'],
                'waste'             => $r['waste'],

                'created_by'        => $order->createdBy->name ?? '-',
            ];
        });

        $paginatedImpact = $this->paginateCollection($impactByOrder, $perPage, 'page');

        // ─── Summary ───────────────────────────────────────────────────
        $impactSummary = [
            'total_orders'             => $orders->count(),
            'completed_orders'         => $completedOrders->count(),

            'total_input_quantity'     => $totalInputKg,
            'total_output_quantity'    => $totalOutputKg,
            'net_inventory_change'     => $netInventoryChange,

            'total_input_value'        => $totalInputValue,
            'total_output_value'       => $totalRevenue,
            'net_value_change'         => $netValueChange,

            'total_consumed_products'  => $consumedSummary->count(),
            'total_produced_products'  => $producedSummary->count(),

            'total_defective'          => $totalDefKg,
            'total_waste'              => $totalWasteKg,

            'inventory_turnover' => $totalInputKg > 0
                ? $totalOutputKg / $totalInputKg
                : 0,

            'overall_yield' => $totalInputKg > 0
                ? ($totalOutputKg / $totalInputKg) * 100
                : 0,

            'loss_kg' => max(0, $totalInputKg - $totalOutputKg),
            'loss_rate' => $totalInputKg > 0
                ? (max(0, $totalInputKg - $totalOutputKg) / $totalInputKg) * 100
                : 0,
        ];

        // ─── Filter Options ────────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants  = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_DRAFT,       'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_CANCELLED,   'label' => __('pagination.cancelled')],
        ];

        return view('reports.production.inventory-impact', compact(
            'impactSummary',
            'paginatedImpact',
            'netImpactSorted',
            'categoryImpact',
            'monthlyImpact',
            'topConsumed',
            'topProduced',
            'consumedSummary',
            'producedSummary',
            'locations',
            'variants',
            'statuses',
            'startDate',
            'endDate',
            'locationId',
            'variantId',
            'status',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Production Quality Analysis Report
     * KG-normalized quality metrics across all production orders
     */
    public function qualityAnalysis(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);

        // ─── Filters ────────────────────────────────────────────────────
        $startDate     = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate       = $request->get('end_date', now()->format('Y-m-d'));
        $locationId    = $request->get('location_id');
        $variantId     = $request->get('variant_id');
        $status        = $request->get('status', 'completed');
        $qualityStatus = $request->get('quality_status', 'all');
        $perPage       = (int) $request->get('per_page', 15);

        // ─── Query ──────────────────────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
        ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                  ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        // ─── Quality-status filter (was ignored in the old version) ────
        $filterInputStatuses  = [];
        $filterOutputStatuses = [];

        if ($qualityStatus !== 'all') {
            switch ($qualityStatus) {
                case 'accepted':
                    $filterInputStatuses  = [ProductionOrderInput::QUALITY_ACCEPTED];
                    $filterOutputStatuses = [ProductionOrderOutput::QUALITY_APPROVED];
                    break;
                case 'rejected':
                    $filterInputStatuses  = [ProductionOrderInput::QUALITY_REJECTED];
                    $filterOutputStatuses = [ProductionOrderOutput::QUALITY_REJECTED];
                    break;
                case 'pending':
                    $filterInputStatuses  = [ProductionOrderInput::QUALITY_PENDING];
                    $filterOutputStatuses = [ProductionOrderOutput::QUALITY_PENDING];
                    break;
            }
        }

        $orders          = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);

        // ─── Per-order KG-normalized quality rollup ────────────────────
        $orderRollups = [];

        foreach ($orders as $order) {
            $inputKgTotal   = 0.0;
            $inputKgAccepted = 0.0;
            $inputKgRejected = 0.0;
            $inputKgPending  = 0.0;
            $inputWasteKg    = 0.0;

            $inputCountTotal    = 0;
            $inputCountAccepted = 0;
            $inputCountRejected = 0;
            $inputCountPending  = 0;

            foreach ($order->inputs as $input) {
                $v = $input->productVariant;
                $w = $v ? (float) $v->weight : 0;
                $q = (float) $input->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;

                $wq = (float) $input->waste_quantity;
                $inputWasteKg += $w > 0 ? $wq * $w : $wq;

                // Apply quality_status filter if set
                if (!empty($filterInputStatuses)
                    && !in_array($input->quality_status, $filterInputStatuses, true)) {
                    continue;
                }

                $inputKgTotal += $kg;
                $inputCountTotal++;

                switch ($input->quality_status) {
                    case ProductionOrderInput::QUALITY_ACCEPTED:
                        $inputKgAccepted += $kg;
                        $inputCountAccepted++;
                        break;
                    case ProductionOrderInput::QUALITY_REJECTED:
                        $inputKgRejected += $kg;
                        $inputCountRejected++;
                        break;
                    default:
                        $inputKgPending += $kg;
                        $inputCountPending++;
                        break;
                }
            }

            $outputKgTotal    = 0.0;
            $outputKgApproved = 0.0;
            $outputKgRejected = 0.0;
            $outputKgPending  = 0.0;
            $outputDefectiveKg = 0.0;

            $outputCountTotal    = 0;
            $outputCountApproved = 0;
            $outputCountRejected = 0;
            $outputCountPending  = 0;

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                $w = $v ? (float) $v->weight : 0;
                $q = (float) $output->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;

                $dq = (float) $output->defective_quantity;
                $outputDefectiveKg += $w > 0 ? $dq * $w : $dq;

                if (!empty($filterOutputStatuses)
                    && !in_array($output->quality_status, $filterOutputStatuses, true)) {
                    continue;
                }

                $outputKgTotal += $kg;
                $outputCountTotal++;

                switch ($output->quality_status) {
                    case ProductionOrderOutput::QUALITY_APPROVED:
                        $outputKgApproved += $kg;
                        $outputCountApproved++;
                        break;
                    case ProductionOrderOutput::QUALITY_REJECTED:
                        $outputKgRejected += $kg;
                        $outputCountRejected++;
                        break;
                    default:
                        $outputKgPending += $kg;
                        $outputCountPending++;
                        break;
                }
            }

            $orderRollups[$order->id] = [
                'input_kg_total'      => $inputKgTotal,
                'input_kg_accepted'   => $inputKgAccepted,
                'input_kg_rejected'   => $inputKgRejected,
                'input_kg_pending'    => $inputKgPending,
                'input_waste_kg'      => $inputWasteKg,
                'input_count_total'   => $inputCountTotal,
                'input_count_accepted'=> $inputCountAccepted,
                'input_count_rejected'=> $inputCountRejected,
                'input_count_pending' => $inputCountPending,

                'output_kg_total'      => $outputKgTotal,
                'output_kg_approved'   => $outputKgApproved,
                'output_kg_rejected'   => $outputKgRejected,
                'output_kg_pending'    => $outputKgPending,
                'output_defective_kg'  => $outputDefectiveKg,
                'output_count_total'   => $outputCountTotal,
                'output_count_approved'=> $outputCountApproved,
                'output_count_rejected'=> $outputCountRejected,
                'output_count_pending' => $outputCountPending,
            ];
        }

        // ─── Aggregate ─────────────────────────────────────────────────
        $agg = [
            'in_kg' => 0.0, 'in_kg_ok' => 0.0, 'in_kg_rej' => 0.0, 'in_kg_pend' => 0.0,
            'in_waste_kg' => 0.0,
            'in_cnt' => 0, 'in_cnt_ok' => 0, 'in_cnt_rej' => 0, 'in_cnt_pend' => 0,

            'out_kg' => 0.0, 'out_kg_ok' => 0.0, 'out_kg_rej' => 0.0, 'out_kg_pend' => 0.0,
            'out_def_kg' => 0.0,
            'out_cnt' => 0, 'out_cnt_ok' => 0, 'out_cnt_rej' => 0, 'out_cnt_pend' => 0,
        ];

        foreach ($orderRollups as $r) {
            $agg['in_kg']      += $r['input_kg_total'];
            $agg['in_kg_ok']   += $r['input_kg_accepted'];
            $agg['in_kg_rej']  += $r['input_kg_rejected'];
            $agg['in_kg_pend'] += $r['input_kg_pending'];
            $agg['in_waste_kg']+= $r['input_waste_kg'];
            $agg['in_cnt']     += $r['input_count_total'];
            $agg['in_cnt_ok']  += $r['input_count_accepted'];
            $agg['in_cnt_rej'] += $r['input_count_rejected'];
            $agg['in_cnt_pend']+= $r['input_count_pending'];

            $agg['out_kg']      += $r['output_kg_total'];
            $agg['out_kg_ok']   += $r['output_kg_approved'];
            $agg['out_kg_rej']  += $r['output_kg_rejected'];
            $agg['out_kg_pend'] += $r['output_kg_pending'];
            $agg['out_def_kg']  += $r['output_defective_kg'];
            $agg['out_cnt']     += $r['output_count_total'];
            $agg['out_cnt_ok']  += $r['output_count_approved'];
            $agg['out_cnt_rej'] += $r['output_count_rejected'];
            $agg['out_cnt_pend']+= $r['output_count_pending'];
        }

        // ─── Rates (KG-weighted, not count-weighted) ───────────────────
        $inputAcceptanceRate = $agg['in_kg'] > 0
            ? ($agg['in_kg_ok'] / $agg['in_kg']) * 100
            : 0;

        $outputApprovalRate = $agg['out_kg'] > 0
            ? ($agg['out_kg_ok'] / $agg['out_kg']) * 100
            : 0;

        $defectiveRate = $agg['out_kg'] > 0
            ? ($agg['out_def_kg'] / $agg['out_kg']) * 100
            : 0;

        $wasteRate = $agg['in_kg'] > 0
            ? ($agg['in_waste_kg'] / $agg['in_kg']) * 100
            : 0;

        // Overall score = weighted by kg, not a plain average of two rates
        $totalKgConsidered = $agg['in_kg'] + $agg['out_kg'];
        $totalKgGood       = $agg['in_kg_ok'] + $agg['out_kg_ok'];
        $overallQualityScore = $totalKgConsidered > 0
            ? ($totalKgGood / $totalKgConsidered) * 100
            : 0;

        // ─── Quality Summary ───────────────────────────────────────────
        $qualitySummary = [
            'total_orders'     => $orders->count(),
            'completed_orders' => $completedOrders->count(),

            // Input (both count + kg)
            'total_inputs'           => $agg['in_cnt'],
            'accepted_inputs'        => $agg['in_cnt_ok'],
            'rejected_inputs'        => $agg['in_cnt_rej'],
            'pending_inputs'         => $agg['in_cnt_pend'],
            'input_acceptance_rate'  => $inputAcceptanceRate,

            'total_input_kg'         => $agg['in_kg'],
            'accepted_input_kg'      => $agg['in_kg_ok'],
            'rejected_input_kg'      => $agg['in_kg_rej'],
            'pending_input_kg'       => $agg['in_kg_pend'],

            'total_input_waste'      => $agg['in_waste_kg'],
            'total_input_waste_kg'   => $agg['in_waste_kg'],
            'total_input_quantity'   => $agg['in_kg'],  // BC alias
            'waste_rate'             => $wasteRate,

            // Output
            'total_outputs'          => $agg['out_cnt'],
            'approved_outputs'       => $agg['out_cnt_ok'],
            'rejected_outputs'       => $agg['out_cnt_rej'],
            'pending_outputs'        => $agg['out_cnt_pend'],
            'output_approval_rate'   => $outputApprovalRate,

            'total_output_kg'        => $agg['out_kg'],
            'approved_output_kg'     => $agg['out_kg_ok'],
            'rejected_output_kg'     => $agg['out_kg_rej'],
            'pending_output_kg'      => $agg['out_kg_pend'],

            'total_defective'        => $agg['out_def_kg'],
            'total_defective_kg'     => $agg['out_def_kg'],
            'total_output_quantity'  => $agg['out_kg'], // BC alias
            'defective_rate'         => $defectiveRate,

            // Overall
            'overall_quality_score'  => $overallQualityScore,
            'quality_rating'         => $this->getQualityRating($overallQualityScore),
            'quality_color'          => $this->getQualityColor($overallQualityScore),
        ];

        // ─── Quality by Order ──────────────────────────────────────────
        $qualityByOrder = $orders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];

            $inputAcceptance = $r['input_kg_total'] > 0
                ? ($r['input_kg_accepted'] / $r['input_kg_total']) * 100
                : 0;

            $outputApproval = $r['output_kg_total'] > 0
                ? ($r['output_kg_approved'] / $r['output_kg_total']) * 100
                : 0;

            $defectiveRate = $r['output_kg_total'] > 0
                ? ($r['output_defective_kg'] / $r['output_kg_total']) * 100
                : 0;

            $wasteRate = $r['input_kg_total'] > 0
                ? ($r['input_waste_kg'] / $r['input_kg_total']) * 100
                : 0;

            $totalKg = $r['input_kg_total'] + $r['output_kg_total'];
            $goodKg  = $r['input_kg_accepted'] + $r['output_kg_approved'];
            $overallScore = $totalKg > 0 ? ($goodKg / $totalKg) * 100 : 0;

            return (object) [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'status'            => $order->status_label,
                'status_badge'      => $order->status_badge,
                'location'          => $order->location->name ?? '-',
                'created_at'        => $order->created_at,

                'input_kg_total'    => $r['input_kg_total'],
                'input_kg_accepted' => $r['input_kg_accepted'],
                'input_kg_rejected' => $r['input_kg_rejected'],
                'input_kg_pending'  => $r['input_kg_pending'],
                'input_waste_kg'    => $r['input_waste_kg'],
                'input_total'       => $r['input_count_total'],
                'input_accepted'    => $r['input_count_accepted'],
                'input_rejected'    => $r['input_count_rejected'],
                'input_acceptance_rate' => $inputAcceptance,
                'input_waste'       => $r['input_waste_kg'],

                'output_kg_total'    => $r['output_kg_total'],
                'output_kg_approved' => $r['output_kg_approved'],
                'output_kg_rejected' => $r['output_kg_rejected'],
                'output_kg_pending'  => $r['output_kg_pending'],
                'output_defective_kg'=> $r['output_defective_kg'],
                'output_total'       => $r['output_count_total'],
                'output_approved'    => $r['output_count_approved'],
                'output_rejected'    => $r['output_count_rejected'],
                'output_approval_rate' => $outputApproval,
                'defective'          => $r['output_defective_kg'],

                'defective_rate'     => $defectiveRate,
                'waste_rate'         => $wasteRate,

                'overall_quality_score' => $overallScore,
                'quality_rating'        => $this->getQualityRating($overallScore),
                'quality_color'         => $this->getQualityColor($overallScore),
                'created_by'            => $order->createdBy->name ?? '-',
            ];
        });

        $paginatedQuality = $this->paginateCollection($qualityByOrder, $perPage, 'page');

        // ─── Quality by Category (outputs, KG-weighted) ────────────────
        $categoryQuality = collect();

        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if (!$variant) continue;

                if (!empty($filterOutputStatuses)
                    && !in_array($output->quality_status, $filterOutputStatuses, true)) {
                    continue;
                }

                $w = (float) ($variant->weight ?? 0);
                $q = (float) $output->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;

                $dq = (float) $output->defective_quantity;
                $defKg = $w > 0 ? $dq * $w : $dq;

                $categoryQuality->push((object) [
                    'category'   => $variant->product->category->name ?? 'Uncategorized',
                    'total_kg'   => $kg,
                    'defective_kg' => $defKg,
                    'approved_kg'  => $output->quality_status === ProductionOrderOutput::QUALITY_APPROVED ? $kg : 0,
                    'rejected_kg'  => $output->quality_status === ProductionOrderOutput::QUALITY_REJECTED ? $kg : 0,
                ]);
            }
        }

        $categoryQualitySummary = $categoryQuality
            ->groupBy('category')
            ->map(function ($items, $category) {
                $totalKg    = (float) $items->sum('total_kg');
                $defectiveKg= (float) $items->sum('defective_kg');
                $approvedKg = (float) $items->sum('approved_kg');
                $rejectedKg = (float) $items->sum('rejected_kg');
                $goodKg     = max(0, $totalKg - $defectiveKg);

                return (object) [
                    'category'           => $category,
                    'total_quantity'     => $totalKg,       // BC alias — now kg
                    'total_kg'           => $totalKg,
                    'defective_quantity' => $defectiveKg,
                    'defective_kg'       => $defectiveKg,
                    'approved_quantity'  => $approvedKg,
                    'approved_kg'        => $approvedKg,
                    'rejected_quantity'  => $rejectedKg,
                    'rejected_kg'        => $rejectedKg,
                    'defective_rate'     => $totalKg > 0 ? ($defectiveKg / $totalKg) * 100 : 0,
                    'approval_rate'      => $totalKg > 0 ? ($approvedKg  / $totalKg) * 100 : 0,
                    // Proper quality score — share of good kg, bounded 0–100
                    'quality_score'      => $totalKg > 0 ? ($goodKg / $totalKg) * 100 : 0,
                ];
            })
            ->values();

        // ─── Monthly Quality Trends ────────────────────────────────────
        $monthlyQuality = $completedOrders
            ->groupBy(fn($order) => $order->completed_at
                ? $order->completed_at->format('Y-m')
                : $order->created_at->format('Y-m'))
            ->map(function ($items, $month) use ($orderRollups) {
                $inKg=0; $inOk=0; $wasteKg=0;
                $outKg=0; $outOk=0; $defKg=0;

                foreach ($items as $order) {
                    $r = $orderRollups[$order->id] ?? null;
                    if (!$r) continue;

                    $inKg    += $r['input_kg_total'];
                    $inOk    += $r['input_kg_accepted'];
                    $wasteKg += $r['input_waste_kg'];

                    $outKg   += $r['output_kg_total'];
                    $outOk   += $r['output_kg_approved'];
                    $defKg   += $r['output_defective_kg'];
                }

                $totalKg = $inKg + $outKg;
                $goodKg  = $inOk + $outOk;

                return (object) [
                    'month'             => Carbon::parse($month . '-01')->format('M Y'),
                    'orders'            => $items->count(),
                    'input_acceptance'  => $inKg  > 0 ? round(($inOk  / $inKg)  * 100, 2) : 0,
                    'output_approval'   => $outKg > 0 ? round(($outOk / $outKg) * 100, 2) : 0,
                    'defective_rate'    => $outKg > 0 ? round(($defKg / $outKg) * 100, 2) : 0,
                    'waste_rate'        => $inKg  > 0 ? round(($wasteKg / $inKg) * 100, 2) : 0,
                    'overall_score'     => $totalKg > 0 ? round(($goodKg / $totalKg) * 100, 2) : 0,
                    'defective'         => round($defKg, 2),
                    'waste'             => round($wasteKg, 2),
                ];
            })
            ->sortKeys()
            ->values();

        // ─── Quality by Product (outputs, KG-weighted) ─────────────────
        $productQuality = collect();

        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if (!$variant) continue;

                if (!empty($filterOutputStatuses)
                    && !in_array($output->quality_status, $filterOutputStatuses, true)) {
                    continue;
                }

                $w = (float) ($variant->weight ?? 0);
                $q = (float) $output->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;

                $dq = (float) $output->defective_quantity;
                $defKg = $w > 0 ? $dq * $w : $dq;

                $productQuality->push((object) [
                    'variant_id'    => $variant->id,
                    'variant_name'  => $variant->name,
                    'variant_sku'   => $variant->sku,
                    'category'      => $variant->product->category->name ?? 'Uncategorized',
                    'weight'        => $w,
                    'total_quantity'=> $q,
                    'total_kg'      => $kg,
                    'defective_quantity' => $defKg,
                    'defective_kg'  => $defKg,
                    'approved'      => $output->quality_status === ProductionOrderOutput::QUALITY_APPROVED,
                    'order_id'      => $order->id,
                ]);
            }
        }

        $productQualitySummary = $productQuality
            ->groupBy('variant_id')
            ->map(function ($items) {
                $first = $items->first();
                $qty   = (float) $items->sum('total_quantity');
                $kg    = (float) $items->sum('total_kg');
                $defKg = (float) $items->sum('defective_kg');
                $goodKg= max(0, $kg - $defKg);

                return (object) [
                    'variant_id'         => $first->variant_id,
                    'variant_name'       => $first->variant_name,
                    'variant_sku'        => $first->variant_sku,
                    'category'           => $first->category,
                    'weight'             => $first->weight,
                    'total_quantity'     => $qty,       // bags
                    'total_kg'           => $kg,
                    'defective_quantity' => $defKg,     // kg
                    'defective_kg'       => $defKg,
                    'defective_rate'     => $kg > 0 ? ($defKg / $kg) * 100 : 0,
                    'approval_rate'      => $kg > 0 ? ($goodKg / $kg) * 100 : 0,
                    'quality_score'      => $kg > 0 ? ($goodKg / $kg) * 100 : 0,
                    'order_count'        => $items->pluck('order_id')->unique()->count(),
                ];
            })
            ->sortByDesc('quality_score')
            ->values()
            ->take(10);

        // ─── Filter Options ────────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants  = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
        ];

        $qualityStatuses = [
            ['value' => 'all',      'label' => __('pagination.all_quality_statuses')],
            ['value' => 'accepted', 'label' => __('pagination.accepted')],
            ['value' => 'rejected', 'label' => __('pagination.rejected')],
            ['value' => 'pending',  'label' => __('pagination.pending')],
        ];

        return view('reports.production.quality-analysis', compact(
            'qualitySummary',
            'paginatedQuality',
            'categoryQualitySummary',
            'monthlyQuality',
            'productQualitySummary',
            'locations',
            'variants',
            'statuses',
            'qualityStatuses',
            'startDate',
            'endDate',
            'locationId',
            'variantId',
            'status',
            'qualityStatus',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Quality rating (KG-weighted score, 0–100)
     */
    private function getQualityRating($score)
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Average';
        if ($score >= 40) return 'Below Average';
        return 'Poor';
    }

    /**
     * Quality color (KG-weighted score, 0–100)
     */
    private function getQualityColor($score)
    {
        if ($score >= 90) return 'success';
        if ($score >= 75) return 'info';
        if ($score >= 60) return 'warning';
        if ($score >= 40) return 'danger';
        return 'dark';
    }


    /**
     * Production Input vs Output Report
     * KG-normalized comparison of inputs (raw materials) vs outputs (finished goods)
     */
    public function inputOutput(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);

        // ─── Filters ────────────────────────────────────────────────────
        $startDate      = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate        = $request->get('end_date', now()->format('Y-m-d'));
        $locationId     = $request->get('location_id');
        $variantId      = $request->get('variant_id');
        $status         = $request->get('status', 'completed');
        $comparisonType = $request->get('comparison_type', 'quantity'); // quantity | cost | both
        $perPage        = (int) $request->get('per_page', 15);

        // ─── Query ──────────────────────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
        ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                  ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orders          = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);

        // ─── Per-order rollup (KG + value) ─────────────────────────────
        $orderRollups = [];
        $allInputRows = collect();
        $allOutputRows = collect();

        foreach ($orders as $order) {
            $inKg    = 0.0;
            $outKg   = 0.0;
            $inCost  = (float) $order->total_input_cost;
            $revenue = 0.0;

            foreach ($order->inputs as $input) {
                $v = $input->productVariant;
                if (!$v) continue;

                $w = (float) ($v->weight ?? 0);
                $q = (float) $input->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;

                $inKg += $kg;

                $allInputRows->push((object) [
                    'order_id'     => $order->id,
                    'variant_id'   => $v->id,
                    'variant_name' => $v->name,
                    'variant_sku'  => $v->sku,
                    'category'     => $v->product->category->name ?? 'Uncategorized',
                    'weight'       => $w,
                    'quantity'     => $q,
                    'total_kg'     => $kg,
                    'cost'         => (float) $input->actual_cost,
                ]);
            }

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;

                $w = (float) ($v->weight ?? 0);
                $q = (float) $output->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;
                $sell = (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);

                $outKg   += $kg;
                $revenue += $q * $sell;

                $allOutputRows->push((object) [
                    'order_id'     => $order->id,
                    'variant_id'   => $v->id,
                    'variant_name' => $v->name,
                    'variant_sku'  => $v->sku,
                    'category'     => $v->product->category->name ?? 'Uncategorized',
                    'weight'       => $w,
                    'quantity'     => $q,
                    'total_kg'     => $kg,
                    'revenue'      => $q * $sell,
                    'selling_price'=> $sell,
                ]);
            }

            $orderRollups[$order->id] = [
                'input_kg'    => $inKg,
                'output_kg'   => $outKg,
                'input_cost'  => $inCost,
                'revenue'     => $revenue,
                'qty_diff'    => $outKg - $inKg,
                'cost_diff'   => $revenue - $inCost,
                'yield'       => $inKg > 0 ? ($outKg / $inKg) * 100 : 0,
                'loss_kg'     => max(0, $inKg - $outKg),
            ];
        }

        // ─── Totals ─────────────────────────────────────────────────────
        $totalInputKg    = 0.0;
        $totalOutputKg   = 0.0;
        $totalInputCost  = 0.0;
        $totalRevenue    = 0.0;

        foreach ($orderRollups as $r) {
            $totalInputKg   += $r['input_kg'];
            $totalOutputKg  += $r['output_kg'];
            $totalInputCost += $r['input_cost'];
            $totalRevenue   += $r['revenue'];
        }

        $netQty   = $totalOutputKg - $totalInputKg;
        $netCost  = $totalRevenue  - $totalInputCost;

        // ─── Summary ────────────────────────────────────────────────────
        $inputOutputSummary = [
            'total_orders'     => $orders->count(),
            'completed_orders' => $completedOrders->count(),

            // Quantity (all KG)
            'total_input_quantity'  => $totalInputKg,
            'total_output_quantity' => $totalOutputKg,
            'total_input_kg'        => $totalInputKg,
            'total_output_kg'       => $totalOutputKg,
            'net_quantity'          => $netQty,
            'net_kg'                => $netQty,
            'loss_kg'               => max(0, $totalInputKg - $totalOutputKg),

            'quantity_ratio'      => $totalInputKg > 0 ? $totalOutputKg / $totalInputKg : 0,
            'quantity_efficiency' => $totalInputKg > 0 ? ($totalOutputKg / $totalInputKg) * 100 : 0,

            // Cost / revenue
            'total_input_cost'  => $totalInputCost,
            'total_output_cost' => $totalRevenue,
            'total_revenue'     => $totalRevenue,
            'net_cost'          => $netCost,
            'net_value'         => $netCost,

            'cost_ratio'      => $totalInputCost > 0 ? $totalRevenue / $totalInputCost : 0,
            'cost_efficiency' => $totalInputCost > 0 ? ($totalRevenue / $totalInputCost) * 100 : 0,

            // Per order
            'avg_input_qty_per_order'  => $orders->count() > 0 ? $totalInputKg  / $orders->count() : 0,
            'avg_output_qty_per_order' => $orders->count() > 0 ? $totalOutputKg / $orders->count() : 0,
            'avg_input_cost_per_order' => $orders->count() > 0 ? $totalInputCost / $orders->count() : 0,
            'avg_output_cost_per_order'=> $orders->count() > 0 ? $totalRevenue   / $orders->count() : 0,

            // Derived
            'overall_yield' => $totalInputKg > 0 ? ($totalOutputKg / $totalInputKg) * 100 : 0,
            'loss_rate'     => $totalInputKg > 0
                ? (max(0, $totalInputKg - $totalOutputKg) / $totalInputKg) * 100
                : 0,
            'cost_per_kg_input'  => $totalInputKg  > 0 ? $totalInputCost / $totalInputKg  : 0,
            'cost_per_kg_output' => $totalOutputKg > 0 ? $totalInputCost / $totalOutputKg : 0,
            'revenue_per_kg'     => $totalOutputKg > 0 ? $totalRevenue   / $totalOutputKg : 0,
            'profit_per_kg'      => $totalOutputKg > 0 ? $netCost        / $totalOutputKg : 0,
        ];

        // ─── Comparison by Order ────────────────────────────────────────
        $comparisonByOrder = $orders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];

            return (object) [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'status'            => $order->status_label,
                'status_badge'      => $order->status_badge,
                'location'          => $order->location->name ?? '-',
                'created_at'        => $order->created_at,

                'input_quantity'    => $r['input_kg'],
                'output_quantity'   => $r['output_kg'],
                'qty_difference'    => $r['qty_diff'],
                'qty_ratio'         => $r['input_kg'] > 0 ? $r['output_kg'] / $r['input_kg'] : 0,
                'qty_efficiency'    => $r['yield'],

                'input_cost'        => $r['input_cost'],
                'output_cost'       => $r['revenue'],
                'cost_difference'   => $r['cost_diff'],
                'cost_ratio'        => $r['input_cost'] > 0 ? $r['revenue'] / $r['input_cost'] : 0,
                'cost_efficiency'   => $r['input_cost'] > 0 ? ($r['revenue'] / $r['input_cost']) * 100 : 0,

                'loss_kg'           => $r['loss_kg'],

                'created_by'        => $order->createdBy->name ?? '-',
            ];
        });

        $paginatedComparison = $this->paginateCollection($comparisonByOrder, $perPage, 'page');

        // ─── Category Comparison ───────────────────────────────────────
        // Compare per-category: how many kg of that category went IN vs came OUT.
        // Also value.
        $categories = collect()
            ->merge($allInputRows->pluck('category'))
            ->merge($allOutputRows->pluck('category'))
            ->unique()
            ->filter();

        $categoryComparison = $categories->map(function ($cat) use ($allInputRows, $allOutputRows) {
            $inputs  = $allInputRows->where('category', $cat);
            $outputs = $allOutputRows->where('category', $cat);

            $inKg    = (float) $inputs->sum('total_kg');
            $outKg   = (float) $outputs->sum('total_kg');
            $inCost  = (float) $inputs->sum('cost');
            $outRev  = (float) $outputs->sum('revenue');

            return (object) [
                'category'          => $cat,
                'input_quantity'    => $inKg,
                'output_quantity'   => $outKg,
                'input_kg'          => $inKg,
                'output_kg'         => $outKg,
                'qty_difference'    => $outKg - $inKg,
                'qty_ratio'         => $inKg > 0 ? $outKg / $inKg : 0,
                'input_cost'        => $inCost,
                'output_cost'       => $outRev,
                'output_revenue'    => $outRev,
                'cost_difference'   => $outRev - $inCost,
                'cost_ratio'        => $inCost > 0 ? $outRev / $inCost : 0,
                'item_count'        => $inputs->count() + $outputs->count(),
            ];
        })->values()->sortByDesc('input_kg')->values();

        // ─── Monthly Trends ────────────────────────────────────────────
        $monthlyComparison = $completedOrders
            ->groupBy(fn($order) => $order->completed_at
                ? $order->completed_at->format('Y-m')
                : $order->created_at->format('Y-m'))
            ->map(function ($items, $month) use ($orderRollups) {
                $inKg = 0.0; $outKg = 0.0;
                $inCost = 0.0; $rev = 0.0;

                foreach ($items as $order) {
                    $r = $orderRollups[$order->id] ?? null;
                    if (!$r) continue;

                    $inKg   += $r['input_kg'];
                    $outKg  += $r['output_kg'];
                    $inCost += $r['input_cost'];
                    $rev    += $r['revenue'];
                }

                return (object) [
                    'month'           => Carbon::parse($month . '-01')->format('M Y'),
                    'orders'          => $items->count(),
                    'input_quantity'  => round($inKg, 2),
                    'output_quantity' => round($outKg, 2),
                    'qty_diff'        => round($outKg - $inKg, 2),
                    'qty_ratio'       => $inKg > 0 ? round($outKg / $inKg, 3) : 0,
                    'yield'           => $inKg > 0 ? round(($outKg / $inKg) * 100, 2) : 0,
                    'input_cost'      => round($inCost, 2),
                    'output_cost'     => round($rev, 2),
                    'cost_diff'       => round($rev - $inCost, 2),
                    'cost_ratio'      => $inCost > 0 ? round($rev / $inCost, 3) : 0,
                ];
            })
            ->sortKeys()
            ->values();

        // ─── Product Summary (net producers / consumers by KG) ─────────
        $allVariantIds = $allInputRows->pluck('variant_id')
            ->merge($allOutputRows->pluck('variant_id'))
            ->unique()
            ->filter();

        $productSummary = $allVariantIds->map(function ($vid) use ($allInputRows, $allOutputRows) {
            $inputs  = $allInputRows->where('variant_id', $vid);
            $outputs = $allOutputRows->where('variant_id', $vid);

            $inKg  = (float) $inputs->sum('total_kg');
            $outKg = (float) $outputs->sum('total_kg');
            $inCost= (float) $inputs->sum('cost');
            $outRev= (float) $outputs->sum('revenue');

            $src = $inputs->first() ?: $outputs->first();

            return (object) [
                'variant_id'        => $vid,
                'variant_name'      => $src->variant_name,
                'variant_sku'       => $src->variant_sku,
                'category'          => $src->category,
                'weight'            => $src->weight,
                'input_quantity'    => $inKg,
                'output_quantity'   => $outKg,
                'input_kg'          => $inKg,
                'output_kg'         => $outKg,
                'qty_difference'    => $outKg - $inKg,
                'input_cost'        => $inCost,
                'output_cost'       => $outRev,
                'output_revenue'    => $outRev,
                'cost_difference'   => $outRev - $inCost,
                'order_count'       => $inputs->pluck('order_id')
                                            ->merge($outputs->pluck('order_id'))
                                            ->unique()->count(),
                'type'              => ($outKg - $inKg) > 0
                    ? 'net_producer'
                    : (($outKg - $inKg) < 0 ? 'net_consumer' : 'neutral'),
            ];
        })
            ->sortByDesc('qty_difference')
            ->values()
            ->take(10);

        // ─── Filter Options ────────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants  = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_DRAFT,       'label' => __('pagination.draft')],
        ];

        $comparisonTypes = [
            ['value' => 'quantity', 'label' => __('pagination.quantity_comparison')],
            ['value' => 'cost',     'label' => __('pagination.cost_comparison')],
            ['value' => 'both',     'label' => __('pagination.both_comparison')],
        ];

        return view('reports.production.input-output', compact(
            'inputOutputSummary',
            'paginatedComparison',
            'categoryComparison',
            'monthlyComparison',
            'productSummary',
            'locations',
            'variants',
            'statuses',
            'comparisonTypes',
            'startDate',
            'endDate',
            'locationId',
            'variantId',
            'status',
            'comparisonType',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Production Waste Report
     * KG-normalized waste + defective analysis across production orders
     */
    public function waste(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);

        // ─── Filters ────────────────────────────────────────────────────
        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'completed');
        $wasteType  = $request->get('waste_type', 'all'); // all | input_waste | output_defective
        $perPage    = (int) $request->get('per_page', 15);

        // ─── Query ──────────────────────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
        ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                  ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        // Apply waste_type at SQL level so filtered rows don't contribute
        $considerInputs  = in_array($wasteType, ['all', 'input_waste'], true);
        $considerOutputs = in_array($wasteType, ['all', 'output_defective'], true);

        $orders          = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);

        // ─── Per-order rollup (KG + waste cost) ────────────────────────
        $orderRollups = [];
        $inputWasteRows  = collect();
        $outputWasteRows = collect();

        foreach ($orders as $order) {
            // Input totals
            $inKg        = 0.0;
            $inWasteKg   = 0.0;
            $inWasteCost = 0.0;
            $inCost      = (float) $order->total_input_cost;

            if ($considerInputs) {
                foreach ($order->inputs as $input) {
                    $v = $input->productVariant;
                    if (!$v) continue;

                    $w = (float) ($v->weight ?? 0);
                    $q = (float) $input->actual_quantity;
                    $kg = $w > 0 ? $q * $w : $q;

                    $wq = (float) $input->waste_quantity;
                    $wKg = $w > 0 ? $wq * $w : $wq;

                    $inKg      += $kg;
                    $inWasteKg += $wKg;

                    // Per-kg cost so waste cost is correct for bagged products
                    $costPerKg = $w > 0
                        ? (float) ($v->grand_total_cost_price ?? 0) / max($w, 0.0001)
                        : (float) ($v->grand_total_cost_price ?? 0);

                    $rowWasteCost = $wKg * $costPerKg;
                    $inWasteCost += $rowWasteCost;

                    if ($wKg > 0) {
                        $inputWasteRows->push((object) [
                            'order_id'     => $order->id,
                            'variant_id'   => $v->id,
                            'variant_name' => $v->name,
                            'variant_sku'  => $v->sku,
                            'category'     => $v->product->category->name ?? 'Uncategorized',
                            'weight'       => $w,
                            'total_kg'     => $kg,
                            'waste_kg'     => $wKg,
                            'waste_cost'   => $rowWasteCost,
                        ]);
                    }
                }
            }

            // Output totals
            $outKg       = 0.0;
            $defKg       = 0.0;
            $defCost     = 0.0;
            $revenue     = 0.0;

            if ($considerOutputs) {
                foreach ($order->outputs as $output) {
                    $v = $output->productVariant;
                    if (!$v) continue;

                    $w = (float) ($v->weight ?? 0);
                    $q = (float) $output->actual_quantity;
                    $kg = $w > 0 ? $q * $w : $q;

                    $dq = (float) $output->defective_quantity;
                    $dKg = $w > 0 ? $dq * $w : $dq;

                    $sell = (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);

                    $outKg   += $kg;
                    $defKg   += $dKg;
                    $revenue += $q * $sell;

                    // Cost of a defective unit ≈ its per-kg production cost,
                    // approximated by grand_total_cost_price per kg
                    $costPerKg = $w > 0
                        ? (float) ($v->grand_total_cost_price ?? 0) / max($w, 0.0001)
                        : (float) ($v->grand_total_cost_price ?? 0);

                    $rowDefCost = $dKg * $costPerKg;
                    $defCost += $rowDefCost;

                    if ($dKg > 0) {
                        $outputWasteRows->push((object) [
                            'order_id'     => $order->id,
                            'variant_id'   => $v->id,
                            'variant_name' => $v->name,
                            'variant_sku'  => $v->sku,
                            'category'     => $v->product->category->name ?? 'Uncategorized',
                            'weight'       => $w,
                            'total_kg'     => $kg,
                            'defective_kg' => $dKg,
                            'defective_cost' => $rowDefCost,
                            'revenue'      => $q * $sell,
                        ]);
                    }
                }
            }

            $orderRollups[$order->id] = [
                'input_kg'        => $inKg,
                'input_waste_kg'  => $inWasteKg,
                'input_waste_cost'=> $inWasteCost,
                'input_cost'      => $inCost,

                'output_kg'       => $outKg,
                'defective_kg'    => $defKg,
                'defective_cost'  => $defCost,
                'revenue'         => $revenue,

                'total_waste_kg'  => $inWasteKg + $defKg,
                'total_waste_cost'=> $inWasteCost + $defCost,
            ];
        }

        // ─── Aggregate ─────────────────────────────────────────────────
        $totalInputKg     = 0.0;
        $totalInputWasteKg= 0.0;
        $totalInputWasteCost = 0.0;

        $totalOutputKg    = 0.0;
        $totalDefectiveKg = 0.0;
        $totalDefectiveCost = 0.0;

        $totalBatchCost   = 0.0;
        $totalRevenue     = 0.0;

        foreach ($orderRollups as $r) {
            $totalInputKg         += $r['input_kg'];
            $totalInputWasteKg    += $r['input_waste_kg'];
            $totalInputWasteCost  += $r['input_waste_cost'];

            $totalOutputKg        += $r['output_kg'];
            $totalDefectiveKg     += $r['defective_kg'];
            $totalDefectiveCost   += $r['defective_cost'];

            $totalBatchCost       += $r['input_cost'];
            $totalRevenue         += $r['revenue'];
        }

        $inputWasteRate   = $totalInputKg > 0
            ? ($totalInputWasteKg / $totalInputKg) * 100
            : 0;
        $defectiveRate    = $totalOutputKg > 0
            ? ($totalDefectiveKg / $totalOutputKg) * 100
            : 0;

        $totalWasteKg     = $totalInputWasteKg + $totalDefectiveKg;
        $totalWasteCost   = $totalInputWasteCost + $totalDefectiveCost;

        $totalWasteBaseKg = $totalInputKg + $totalOutputKg;
        $totalWasteRate   = $totalWasteBaseKg > 0
            ? ($totalWasteKg / $totalWasteBaseKg) * 100
            : 0;

        $wasteCostPct = $totalBatchCost > 0
            ? ($totalWasteCost / $totalBatchCost) * 100
            : 0;

        // ─── Summary ───────────────────────────────────────────────────
        $wasteSummary = [
            'total_orders'     => $orders->count(),
            'completed_orders' => $completedOrders->count(),

            // Input
            'total_input_waste'      => $totalInputWasteKg,
            'total_input_waste_kg'   => $totalInputWasteKg,
            'total_input_quantity'   => $totalInputKg,
            'total_input_kg'         => $totalInputKg,
            'input_waste_rate'       => $inputWasteRate,
            'input_waste_cost'       => $totalInputWasteCost,

            // Output
            'total_defective'        => $totalDefectiveKg,
            'total_defective_kg'     => $totalDefectiveKg,
            'total_output_quantity'  => $totalOutputKg,
            'total_output_kg'        => $totalOutputKg,
            'output_defective_rate'  => $defectiveRate,
            'defective_cost'         => $totalDefectiveCost,

            // Combined
            'total_waste'            => $totalWasteKg,
            'total_waste_kg'         => $totalWasteKg,
            'total_waste_rate'       => $totalWasteRate,
            'waste_cost'             => $totalWasteCost,
            'waste_cost_percentage'  => $wasteCostPct,
            'total_cost'             => $totalBatchCost,

            // Good output
            'good_output'            => max(0, $totalOutputKg - $totalDefectiveKg),
            'good_rate'              => $totalOutputKg > 0
                ? (max(0, $totalOutputKg - $totalDefectiveKg) / $totalOutputKg) * 100
                : 0,
        ];

        // ─── Waste by Order ────────────────────────────────────────────
        $wasteByOrder = $orders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];

            $inputWasteRate = $r['input_kg'] > 0
                ? ($r['input_waste_kg'] / $r['input_kg']) * 100
                : 0;
            $defectiveRate = $r['output_kg'] > 0
                ? ($r['defective_kg'] / $r['output_kg']) * 100
                : 0;

            $baseKg = $r['input_kg'] + $r['output_kg'];
            $totalRate = $baseKg > 0
                ? ($r['total_waste_kg'] / $baseKg) * 100
                : 0;

            return (object) [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'status'            => $order->status_label,
                'status_badge'      => $order->status_badge,
                'location'          => $order->location->name ?? '-',
                'created_at'        => $order->created_at,

                'input_waste'       => $r['input_waste_kg'],
                'input_quantity'    => $r['input_kg'],
                'input_waste_rate'  => $inputWasteRate,

                'defective'         => $r['defective_kg'],
                'output_quantity'   => $r['output_kg'],
                'defective_rate'    => $defectiveRate,

                'total_waste'       => $r['total_waste_kg'],
                'total_waste_rate'  => $totalRate,
                'waste_cost'        => $r['total_waste_cost'],

                'created_by'        => $order->createdBy->name ?? '-',
            ];
        });

        $paginatedWaste = $this->paginateCollection($wasteByOrder, $perPage, 'page');

        // ─── Category Waste (KG) ───────────────────────────────────────
        $categoryWasteRows = collect();

        foreach ($inputWasteRows as $row) {
            $categoryWasteRows->push((object) [
                'category'     => $row->category,
                'type'         => 'input_waste',
                'waste_kg'     => $row->waste_kg,
                'total_kg'     => $row->total_kg,
                'waste_cost'   => $row->waste_cost,
            ]);
        }
        foreach ($outputWasteRows as $row) {
            $categoryWasteRows->push((object) [
                'category'     => $row->category,
                'type'         => 'output_defective',
                'waste_kg'     => $row->defective_kg,
                'total_kg'     => $row->total_kg,
                'waste_cost'   => $row->defective_cost,
            ]);
        }

        $categoryWasteSummary = $categoryWasteRows
            ->groupBy('category')
            ->map(function ($items, $category) {
                $inputWaste  = $items->where('type', 'input_waste');
                $outputWaste = $items->where('type', 'output_defective');

                $inWasteKg  = (float) $inputWaste->sum('waste_kg');
                $inTotalKg  = (float) $inputWaste->sum('total_kg');
                $outDefKg   = (float) $outputWaste->sum('waste_kg');
                $outTotKg   = (float) $outputWaste->sum('total_kg');

                $inWasteCost  = (float) $inputWaste->sum('waste_cost');
                $outDefCost   = (float) $outputWaste->sum('waste_cost');

                return (object) [
                    'category'         => $category,
                    'input_waste'      => $inWasteKg,
                    'input_total'      => $inTotalKg,
                    'input_waste_rate' => $inTotalKg > 0 ? ($inWasteKg / $inTotalKg) * 100 : 0,
                    'defective'        => $outDefKg,
                    'output_total'     => $outTotKg,
                    'defective_rate'   => $outTotKg > 0 ? ($outDefKg / $outTotKg) * 100 : 0,
                    'total_waste'      => $inWasteKg + $outDefKg,
                    'total_rate'       => ($inTotalKg + $outTotKg) > 0
                        ? (($inWasteKg + $outDefKg) / ($inTotalKg + $outTotKg)) * 100
                        : 0,
                    'waste_cost'       => $inWasteCost + $outDefCost,
                ];
            })
            ->values()
            ->sortByDesc('total_waste')
            ->values();

        // ─── Monthly Waste Trends (KG) ─────────────────────────────────
        $monthlyWaste = $completedOrders
            ->groupBy(fn($order) => $order->completed_at
                ? $order->completed_at->format('Y-m')
                : $order->created_at->format('Y-m'))
            ->map(function ($items, $month) use ($orderRollups) {
                $inWasteKg = 0.0; $inKg = 0.0;
                $defKg = 0.0; $outKg = 0.0;
                $inWasteCost = 0.0; $defCost = 0.0;

                foreach ($items as $order) {
                    $r = $orderRollups[$order->id] ?? null;
                    if (!$r) continue;

                    $inWasteKg += $r['input_waste_kg'];
                    $inKg      += $r['input_kg'];
                    $defKg     += $r['defective_kg'];
                    $outKg     += $r['output_kg'];
                    $inWasteCost += $r['input_waste_cost'];
                    $defCost     += $r['defective_cost'];
                }

                $baseKg = $inKg + $outKg;
                $totWasteKg = $inWasteKg + $defKg;

                return (object) [
                    'month'            => Carbon::parse($month . '-01')->format('M Y'),
                    'orders'           => $items->count(),
                    'input_waste'      => round($inWasteKg, 2),
                    'input_waste_rate' => $inKg > 0 ? round(($inWasteKg / $inKg) * 100, 2) : 0,
                    'defective'        => round($defKg, 2),
                    'defective_rate'   => $outKg > 0 ? round(($defKg / $outKg) * 100, 2) : 0,
                    'total_waste'      => round($totWasteKg, 2),
                    'total_rate'       => $baseKg > 0 ? round(($totWasteKg / $baseKg) * 100, 2) : 0,
                    'waste_cost'       => round($inWasteCost + $defCost, 2),
                ];
            })
            ->sortKeys()
            ->values();

        // ─── Waste by Product (KG) ─────────────────────────────────────
        $productWasteRows = collect();

        foreach ($inputWasteRows as $row) {
            $productWasteRows->push((object) [
                'variant_id'   => $row->variant_id,
                'variant_name' => $row->variant_name,
                'variant_sku'  => $row->variant_sku,
                'category'     => $row->category,
                'weight'       => $row->weight,
                'type'         => 'input',
                'waste_kg'     => $row->waste_kg,
                'total_kg'     => $row->total_kg,
                'waste_cost'   => $row->waste_cost,
            ]);
        }
        foreach ($outputWasteRows as $row) {
            $productWasteRows->push((object) [
                'variant_id'   => $row->variant_id,
                'variant_name' => $row->variant_name,
                'variant_sku'  => $row->variant_sku,
                'category'     => $row->category,
                'weight'       => $row->weight,
                'type'         => 'output',
                'waste_kg'     => $row->defective_kg,
                'total_kg'     => $row->total_kg,
                'waste_cost'   => $row->defective_cost,
            ]);
        }

        $productWasteSummary = $productWasteRows
            ->groupBy('variant_id')
            ->map(function ($items) {
                $first = $items->first();

                $inWasteKg = (float) $items->where('type', 'input')->sum('waste_kg');
                $inTotalKg = (float) $items->where('type', 'input')->sum('total_kg');
                $outWasteKg= (float) $items->where('type', 'output')->sum('waste_kg');
                $outTotalKg= (float) $items->where('type', 'output')->sum('total_kg');
                $cost      = (float) $items->sum('waste_cost');

                return (object) [
                    'variant_id'       => $first->variant_id,
                    'variant_name'     => $first->variant_name,
                    'variant_sku'      => $first->variant_sku,
                    'category'         => $first->category,
                    'weight'           => $first->weight,
                    'input_waste'      => $inWasteKg,
                    'input_total'      => $inTotalKg,
                    'input_waste_rate' => $inTotalKg > 0 ? ($inWasteKg / $inTotalKg) * 100 : 0,
                    'output_waste'     => $outWasteKg,
                    'output_total'     => $outTotalKg,
                    'output_waste_rate'=> $outTotalKg > 0 ? ($outWasteKg / $outTotalKg) * 100 : 0,
                    'total_waste'      => $inWasteKg + $outWasteKg,
                    'total_rate'       => ($inTotalKg + $outTotalKg) > 0
                        ? (($inWasteKg + $outWasteKg) / ($inTotalKg + $outTotalKg)) * 100
                        : 0,
                    'waste_cost'       => $cost,
                    'order_count'      => $items->count(),
                ];
            })
            ->sortByDesc('total_waste')
            ->values()
            ->take(10);

        // ─── Severity Distribution ─────────────────────────────────────
        $severityDistribution = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];

        foreach ($wasteByOrder as $order) {
            $rate = $order->total_waste_rate;
            if ($rate <= 5) {
                $severityDistribution['low']++;
            } elseif ($rate <= 15) {
                $severityDistribution['medium']++;
            } elseif ($rate <= 30) {
                $severityDistribution['high']++;
            } else {
                $severityDistribution['critical']++;
            }
        }

        // ─── Filter Options ────────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants  = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
        ];

        $wasteTypes = [
            ['value' => 'all',              'label' => __('pagination.all_waste')],
            ['value' => 'input_waste',      'label' => __('pagination.input_waste')],
            ['value' => 'output_defective', 'label' => __('pagination.output_defective')],
        ];

        return view('reports.production.waste', compact(
            'wasteSummary',
            'paginatedWaste',
            'categoryWasteSummary',
            'monthlyWaste',
            'productWasteSummary',
            'severityDistribution',
            'locations',
            'variants',
            'statuses',
            'wasteTypes',
            'startDate',
            'endDate',
            'locationId',
            'variantId',
            'status',
            'wasteType',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Production Batch Tracking Report
     * Tracks batches produced and consumed, KG-normalized for cross-variant aggregation
     */
    public function batchTracking(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);

        // ─── Filters ────────────────────────────────────────────────────
        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $batchType  = $request->get('batch_type', 'all');
        $search     = trim((string) $request->get('search', ''));
        $perPage    = (int) $request->get('per_page', 15);

        // ─── Base order query (for ID filtering) ────────────────────────
        $orderQuery = ProductionOrder::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId) {
            $orderQuery->where('location_id', $locationId);
        }
        if ($variantId) {
            $orderQuery->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                  ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orderIds = $orderQuery->pluck('id');

        // ─── Batch logs ─────────────────────────────────────────────────
        $batchLogsQuery = BatchLog::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('production_order_id', $orderIds)
            ->with([
                'variant',       // used to obtain weight for KG conversion
                'performedBy',
            ])
            ->orderByDesc('event_date')
            ->orderByDesc('id');

        // Type filter
        if ($batchType === 'produced') {
            $batchLogsQuery->where('type', BatchLog::TYPE_PRODUCED);
        } elseif ($batchType === 'consumed') {
            $batchLogsQuery->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed']);
        } elseif ($batchType === 'received') {
            $batchLogsQuery->where('type', BatchLog::TYPE_RECEIVED);
        } elseif ($batchType === 'transferred') {
            $batchLogsQuery->whereIn('type', [
                BatchLog::TYPE_TRANSFERRED,
                BatchLog::TYPE_ASSIGNED,
                BatchLog::TYPE_UNASSIGNED,
            ]);
        } elseif ($batchType === 'adjusted') {
            $batchLogsQuery->where('type', BatchLog::TYPE_ADJUSTED);
        }

        // Search
        if ($search !== '') {
            $batchLogsQuery->where(function ($q) use ($search) {
                $q->where('batch_number',           'like', "%{$search}%")
                  ->orWhere('variant_name',         'like', "%{$search}%")
                  ->orWhere('variant_sku',          'like', "%{$search}%")
                  ->orWhere('purchase_order_number','like', "%{$search}%")
                  ->orWhere('supplier_name',        'like', "%{$search}%");
            });
        }

        $allBatchLogs = $batchLogsQuery->get();

        // ─── Pre-load variant weights for KG conversion ────────────────
        //    We use the current variant weight (best guess). If a variant
        //    has been archived, weight falls back to null → 1:1.
        $variantIds = $allBatchLogs->pluck('variant_id')->filter()->unique()->values();

        $variantWeights = $variantIds->isNotEmpty()
            ? ProductVariant::whereIn('id', $variantIds)->pluck('weight', 'id')->toArray()
            : [];

        $toKg = function ($qty, $variantId) use ($variantWeights) {
            $w = (float) ($variantWeights[$variantId] ?? 0);
            $q = (float) $qty;
            return $w > 0 ? $q * $w : $q;
        };

        // ─── Summary (KG-normalized) ───────────────────────────────────
        $producedLogs = $allBatchLogs->where('type', BatchLog::TYPE_PRODUCED);
        $consumedLogs = $allBatchLogs->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed']);

        $totalProducedQty   = 0.0;  // raw units
        $totalConsumedQty   = 0.0;
        $totalProducedKg    = 0.0;
        $totalConsumedKg    = 0.0;

        foreach ($producedLogs as $log) {
            $q = (float) $log->quantity_change;
            $totalProducedQty += $q;
            $totalProducedKg  += $toKg($q, $log->variant_id);
        }
        foreach ($consumedLogs as $log) {
            $q = abs((float) $log->quantity_change);
            $totalConsumedQty += $q;
            $totalConsumedKg  += $toKg($q, $log->variant_id);
        }

        $totalProducedCost = (float) $producedLogs->sum('total_cost');
        $totalConsumedCost = (float) $consumedLogs->sum('total_cost');

        // Avg unit cost must be computed per kg to be comparable across variants
        $avgUnitCostPerKg = 0.0;
        $allLogsWithCostKg = $allBatchLogs->filter(function ($log) use ($toKg) {
            return (float) $log->unit_cost > 0 && (float) $log->quantity_change !== 0;
        });
        if ($allLogsWithCostKg->count() > 0) {
            $totalWeighted = 0.0;
            foreach ($allLogsWithCostKg as $log) {
                $kg = abs($toKg($log->quantity_change, $log->variant_id));
                if ($kg > 0) {
                    $totalWeighted += (float) $log->unit_cost * $kg;
                }
            }
            $totalWeightedKg = $allLogsWithCostKg->sum(function ($log) use ($toKg) {
                return abs($toKg($log->quantity_change, $log->variant_id));
            });
            $avgUnitCostPerKg = $totalWeightedKg > 0
                ? $totalWeighted / $totalWeightedKg
                : 0;
        }

        $batchSummary = [
            'total_batches'            => $allBatchLogs->count(),
            'produced_batches'         => $producedLogs->count(),
            'consumed_batches'         => $consumedLogs->count(),
            'received_batches'         => $allBatchLogs->where('type', BatchLog::TYPE_RECEIVED)->count(),
            'transferred_batches'      => $allBatchLogs->whereIn('type', [
                BatchLog::TYPE_TRANSFERRED,
                BatchLog::TYPE_ASSIGNED,
                BatchLog::TYPE_UNASSIGNED,
            ])->count(),
            'adjusted_batches'         => $allBatchLogs->where('type', BatchLog::TYPE_ADJUSTED)->count(),

            // ★ Raw quantities (units, unchanged)
            'total_produced_quantity'  => $totalProducedQty,
            'total_consumed_quantity'  => $totalConsumedQty,
            'net_batch_quantity'       => $totalProducedQty - $totalConsumedQty,

            // ★ KG-normalized quantities
            'total_produced_kg'        => $totalProducedKg,
            'total_consumed_kg'        => $totalConsumedKg,
            'net_batch_kg'             => $totalProducedKg - $totalConsumedKg,

            'total_produced_cost'      => $totalProducedCost,
            'total_consumed_cost'      => $totalConsumedCost,
            'net_batch_cost'           => $totalProducedCost - $totalConsumedCost,

            'unique_batch_numbers'     => $allBatchLogs->pluck('batch_number')->filter()->unique()->count(),
            'unique_variants'          => $allBatchLogs->pluck('variant_id')->filter()->unique()->count(),
            'unique_suppliers'         => $allBatchLogs->pluck('supplier_id')->filter()->unique()->count(),
            'unique_purchase_orders'   => $allBatchLogs->pluck('purchase_order_id')->filter()->unique()->count(),

            // ★ Now truly comparable — per kg
            'avg_unit_cost'            => $avgUnitCostPerKg,
            'avg_unit_cost_per_kg'     => $avgUnitCostPerKg,
        ];

        // ─── Enrich each log row ────────────────────────────────────────
        $enrichedLogs = $allBatchLogs->map(function ($log) use ($toKg) {
            $change    = (float) $log->quantity_change;
            $direction = $change > 0 ? 'in' : ($change < 0 ? 'out' : 'neutral');

            $sourceType  = 'unknown';
            $sourceLabel = null;
            $sourceSub   = null;

            if ($log->purchase_order_id) {
                $sourceType  = 'purchase_order';
                $sourceLabel = $log->purchase_order_number ?: "#{$log->purchase_order_id}";
                $sourceSub   = $log->supplier_name;
            } elseif ($log->production_order_id) {
                $sourceType  = 'production_order';
                $sourceLabel = "PRD-#{$log->production_order_id}";
                $sourceSub   = $log->metadata['production_number'] ?? null;
            }

            $unitCost  = (float) ($log->unit_cost ?? 0);
            $totalCost = (float) ($log->total_cost ?? 0);

            // ★ KG-normalized quantities (per-row) — logged unit count stays intact
            $changeKg = $toKg($change, $log->variant_id);
            $beforeKg = $toKg((float) $log->quantity_before, $log->variant_id);
            $afterKg  = $toKg((float) $log->quantity_after,  $log->variant_id);

            $weight = (float) ($log->variant?->weight ?? 0);

            return (object) [
                'id'                 => $log->id,
                'batch_id'           => $log->batch_id,
                'batch_number'       => $log->batch_number,
                'type'               => $log->type,
                'type_label'         => $log->type_label,
                'type_color'         => $log->type_color,
                'type_icon'          => $log->type_icon,
                'direction'          => $direction,

                'variant_id'         => $log->variant_id,
                'variant_name'       => $log->variant_name ?: $log->variant?->name,
                'variant_sku'        => $log->variant_sku  ?: $log->variant?->sku,
                'weight'             => $weight,

                // Raw units (for auditing)
                'quantity_change'    => $change,
                'quantity_before'    => (float) $log->quantity_before,
                'quantity_after'     => (float) $log->quantity_after,

                // ★ KG equivalents
                'quantity_change_kg' => $changeKg,
                'quantity_before_kg' => $beforeKg,
                'quantity_after_kg'  => $afterKg,

                'unit_cost'          => $unitCost,
                'unit_cost_per_kg'   => $weight > 0 ? $unitCost / $weight : $unitCost,
                'total_cost'         => $totalCost,

                'expiry_date'        => $log->expiry_date,
                'event_date'         => $log->event_date,
                'performed_by'       => $log->performedBy?->name,
                'source_type'        => $sourceType,
                'source_label'       => $sourceLabel,
                'source_sub'         => $sourceSub,

                'production_order_id'        => $log->production_order_id,
                'production_order_input_id'  => $log->production_order_input_id,
                'production_order_output_id' => $log->production_order_output_id,
                'production_number'          => $log->metadata['production_number'] ?? null,
                'purchase_order_id'          => $log->purchase_order_id,
                'location_id'                => $log->location_id,
                'department_id'              => $log->department_id,
                'metadata'                   => $log->metadata,
            ];
        });

        // ─── Top produced / consumed (by KG) ───────────────────────────
        $topProducedBatches = $enrichedLogs
            ->where('type', BatchLog::TYPE_PRODUCED)
            ->sortByDesc('quantity_change_kg')
            ->take(10)
            ->values();

        $topConsumedBatches = $enrichedLogs
            ->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed'])
            ->sortByDesc(fn($r) => abs($r->quantity_change_kg))
            ->take(10)
            ->values();

        // ─── Variant summary (KG-normalized) ───────────────────────────
        $batchByVariant = $enrichedLogs
            ->groupBy('variant_id')
            ->map(function ($items, $variantId) {
                $first    = $items->first();
                $produced = $items->where('type', BatchLog::TYPE_PRODUCED);
                $consumed = $items->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed']);

                $producedQty  = (float) $produced->sum('quantity_change');
                $consumedQty  = (float) abs($consumed->sum('quantity_change'));
                $producedKg   = (float) $produced->sum('quantity_change_kg');
                $consumedKg   = (float) abs($consumed->sum('quantity_change_kg'));
                $producedCost = (float) $produced->sum('total_cost');
                $consumedCost = (float) $consumed->sum('total_cost');

                // Avg unit cost per KG across this variant
                $avgPerKgItems = $items->filter(fn($r) => $r->unit_cost_per_kg > 0 && $r->weight > 0);
                $avgUnitCostPerKg = $avgPerKgItems->isNotEmpty()
                    ? $avgPerKgItems->avg('unit_cost_per_kg')
                    : ($items->avg('unit_cost') ?? 0);

                return (object) [
                    'variant_id'        => $variantId,
                    'variant_name'      => $first->variant_name,
                    'variant_sku'       => $first->variant_sku,
                    'weight'            => $first->weight,

                    'produced_count'    => $produced->count(),
                    'consumed_count'    => $consumed->count(),

                    // Raw
                    'produced_quantity' => $producedQty,
                    'consumed_quantity' => $consumedQty,
                    'net_quantity'      => $producedQty - $consumedQty,

                    // ★ KG
                    'produced_kg'       => $producedKg,
                    'consumed_kg'       => $consumedKg,
                    'net_kg'            => $producedKg - $consumedKg,

                    'produced_cost'     => $producedCost,
                    'consumed_cost'     => $consumedCost,
                    'net_cost'          => $producedCost - $consumedCost,

                    'avg_unit_cost'     => $items->where('unit_cost', '>', 0)->avg('unit_cost') ?? 0,
                    'avg_unit_cost_per_kg' => $avgUnitCostPerKg,

                    'unique_batches'    => $items->pluck('batch_number')->filter()->unique()->count(),
                    'order_count'       => $items->pluck('production_order_id')->filter()->unique()->count(),
                ];
            })
            ->sortByDesc(fn($r) => abs($r->net_kg))
            ->values();

        // ─── Monthly summary (KG-normalized) ───────────────────────────
        $batchByMonth = $enrichedLogs
            ->filter(fn($r) => $r->event_date)
            ->groupBy(fn($r) => Carbon::parse($r->event_date)->format('Y-m'))
            ->map(function ($items, $monthKey) {
                $produced = $items->where('type', BatchLog::TYPE_PRODUCED);
                $consumed = $items->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed']);

                $producedQty = (float) $produced->sum('quantity_change');
                $consumedQty = (float) abs($consumed->sum('quantity_change'));
                $producedKg  = (float) $produced->sum('quantity_change_kg');
                $consumedKg  = (float) abs($consumed->sum('quantity_change_kg'));

                return (object) [
                    'month'             => Carbon::parse($monthKey . '-01')->format('M Y'),
                    'month_key'         => $monthKey,
                    'produced_count'    => $produced->count(),
                    'consumed_count'    => $consumed->count(),

                    'produced_quantity' => $producedQty,
                    'consumed_quantity' => $consumedQty,
                    'net_quantity'      => $producedQty - $consumedQty,

                    'produced_kg'       => round($producedKg, 2),
                    'consumed_kg'       => round($consumedKg, 2),
                    'net_kg'            => round($producedKg - $consumedKg, 2),

                    'produced_cost'     => (float) $produced->sum('total_cost'),
                    'consumed_cost'     => (float) $consumed->sum('total_cost'),
                    'net_cost'          => (float) $produced->sum('total_cost')
                                         - (float) $consumed->sum('total_cost'),
                ];
            })
            ->sortKeysDesc()
            ->values();

        // ─── Batch status ───────────────────────────────────────────────
        $latestPerBatch = $enrichedLogs
            ->sortByDesc('event_date')
            ->unique(fn($r) => $r->batch_id);

        $batchStatus = [
            'active'   => $latestPerBatch->filter(fn($r) => $r->quantity_after > 0)->count(),
            'depleted' => $latestPerBatch->filter(fn($r) => $r->quantity_after <= 0)->count(),
            'expired'  => $latestPerBatch->filter(function ($r) {
                return $r->expiry_date && Carbon::parse($r->expiry_date)->isPast();
            })->count(),
        ];

        // ─── Paginate ───────────────────────────────────────────────────
        $paginatedBatches = $this->paginateCollection($enrichedLogs, $perPage, 'page');

        // ─── Filter options ─────────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants  = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $batchTypes = [
            ['value' => 'all',         'label' => __('pagination.all_batches')],
            ['value' => 'produced',    'label' => __('pagination.produced_batches')],
            ['value' => 'consumed',    'label' => __('pagination.consumed_batches')],
            ['value' => 'received',    'label' => __('pagination.received_batches')],
            ['value' => 'transferred', 'label' => __('pagination.transferred_batches')],
            ['value' => 'adjusted',    'label' => __('pagination.adjusted_batches')],
        ];

        return view('reports.production.batch-tracking', compact(
            'batchSummary',
            'paginatedBatches',
            'allBatchLogs',
            'enrichedLogs',
            'batchByVariant',
            'batchByMonth',
            'topProducedBatches',
            'topConsumedBatches',
            'batchStatus',
            'locations',
            'variants',
            'batchTypes',
            'startDate',
            'endDate',
            'locationId',
            'variantId',
            'batchType',
            'search',
            'perPage',
            'isSingleShop'
        ));
    }



}