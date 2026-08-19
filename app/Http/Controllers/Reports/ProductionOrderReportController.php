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
        ->where('tenant_id', $tenantId);
        
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
        $inventoryLogs = SingleShopInventoryLog::where('order_id', $orderId)
            ->where('source', 'production')
            ->orWhere('reason', 'production_consumption')
            ->orWhere('reason', 'production_output')
            ->orWhere('reason', 'production_output_update')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // ─── Get Batch Logs for this order ─────────────────────────────
        $batchLogs = BatchLog::where('production_order_id', $orderId)
            ->orderBy('event_date', 'desc')
            ->get();
        
        // ─── Calculate Order Metrics ────────────────────────────────────
        $metrics = [
            'total_inputs' => $order->inputs->count(),
            'total_outputs' => $order->outputs->count(),
            'input_yield' => $order->total_input_quantity > 0 
                ? ($order->total_output_quantity / $order->total_input_quantity) * 100 
                : 0,
            'cost_efficiency' => $order->total_cost > 0 
                ? ($order->total_output_cost / $order->total_cost) * 100 
                : 0,
            'duration_hours' => $order->started_at && $order->completed_at 
                ? $order->started_at->diffInHours($order->completed_at) 
                : 0,
            'profit' => $order->total_output_cost - $order->total_input_cost,
            'profit_margin' => $order->total_output_cost > 0 
                ? (($order->total_output_cost - $order->total_input_cost) / $order->total_output_cost) * 100 
                : 0,
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
            'order' => $order,
            'metrics' => $metrics,
            'input_stats' => $inputStats,
            'output_stats' => $outputStats,
            'inventory_logs' => $inventoryLogs,
            'batch_logs' => $batchLogs,
            'is_single_shop' => $isSingleShop,
        ]);
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
     * Comprehensive summary of all production activities
     */
    public function summary(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $status = $request->get('status', 'all');
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get All Production Orders ──────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
            'paymentMethod'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        $orders = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        $inProgressOrders = $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS);
        $draftOrders = $orders->where('status', ProductionOrder::STATUS_DRAFT);
        $cancelledOrders = $orders->where('status', ProductionOrder::STATUS_CANCELLED);
        
        // ─── Summary Statistics ─────────────────────────────────────────
        $summary = [
            // Order Counts
            'total_orders' => $orders->count(),
            'completed_orders' => $completedOrders->count(),
            'in_progress_orders' => $inProgressOrders->count(),
            'draft_orders' => $draftOrders->count(),
            'cancelled_orders' => $cancelledOrders->count(),
            'completion_rate' => $orders->count() > 0 ? ($completedOrders->count() / $orders->count()) * 100 : 0,
            
            // Quantity Metrics
            'total_input_quantity' => $orders->sum('total_input_quantity'),
            'total_output_quantity' => $orders->sum('total_output_quantity'),
            'total_waste' => $orders->sum(function($order) {
                return $order->inputs->sum('waste_quantity');
            }),
            'overall_yield' => $orders->sum('total_input_quantity') > 0 
                ? ($orders->sum('total_output_quantity') / $orders->sum('total_input_quantity')) * 100 
                : 0,
            
            // Cost Metrics
            'total_input_cost' => $orders->sum('total_input_cost'),
            'total_output_cost' => $orders->sum('total_output_cost'),
            'total_cost' => $orders->sum('total_cost'),
            'total_profit' => $orders->sum(function($order) {
                return $order->total_output_cost - $order->total_input_cost;
            }),
            'avg_cost_per_order' => $orders->count() > 0 ? $orders->avg('total_cost') : 0,
            'avg_profit_per_order' => $orders->count() > 0 ? $orders->avg(function($order) {
                return $order->total_output_cost - $order->total_input_cost;
            }) : 0,
            
            // Quality Metrics
            'total_defective' => $orders->sum(function($order) {
                return $order->outputs->sum('defective_quantity');
            }),
            'total_quality_accepted' => $orders->sum(function($order) {
                return $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_ACCEPTED)->count();
            }),
            'total_quality_rejected' => $orders->sum(function($order) {
                return $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_REJECTED)->count();
            }),
            'quality_acceptance_rate' => ($orders->sum(function($order) {
                return $order->inputs->count();
            }) > 0) ? ($orders->sum(function($order) {
                return $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_ACCEPTED)->count();
            }) / $orders->sum(function($order) {
                return $order->inputs->count();
            })) * 100 : 0,
            
            // Payment Metrics
            'orders_with_payment' => $orders->whereNotNull('payment_method_id')->count(),
            'orders_without_payment' => $orders->whereNull('payment_method_id')->count(),
            'total_payment_amount' => $orders->sum(function($order) {
                return $order->paymentMethod ? $order->total_cost : 0;
            }),
            
            // Time Metrics
            'avg_duration_hours' => $completedOrders->count() > 0 ? $completedOrders->avg(function($order) {
                return $order->started_at && $order->completed_at 
                    ? $order->started_at->diffInHours($order->completed_at) 
                    : 0;
            }) : 0,
        ];
        
        // ─── Monthly Trends ─────────────────────────────────────────────
        $monthlyTrends = $orders->groupBy(function($order) {
            return $order->created_at->format('Y-m');
        })->map(function($items, $month) {
            return (object)[
                'month' => Carbon::parse($month . '-01')->format('M Y'),
                'orders' => $items->count(),
                'completed' => $items->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
                'input_cost' => $items->sum('total_input_cost'),
                'output_cost' => $items->sum('total_output_cost'),
                'profit' => $items->sum(function($order) {
                    return $order->total_output_cost - $order->total_input_cost;
                }),
                'yield' => $items->sum('total_input_quantity') > 0 
                    ? ($items->sum('total_output_quantity') / $items->sum('total_input_quantity')) * 100 
                    : 0,
            ];
        })->sortKeys()->values();
        
        // ─── Top Products (Most Produced) ───────────────────────────────
        $productOutputs = collect();
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $productOutputs->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'sku' => $variant->sku,
                        'quantity' => $output->actual_quantity,
                        'cost' => $output->production_cost,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                    ]);
                }
            }
        }
        
        $topProducts = $productOutputs->groupBy('variant_id')->map(function($items, $variantId) {
            $first = $items->first();
            return (object)[
                'variant_id' => $variantId,
                'variant_name' => $first->variant_name,
                'sku' => $first->sku,
                'category' => $first->category,
                'total_quantity' => $items->sum('quantity'),
                'total_cost' => $items->sum('cost'),
                'avg_cost_per_unit' => $items->sum('quantity') > 0 ? $items->sum('cost') / $items->sum('quantity') : 0,
                'order_count' => $items->count(),
            ];
        })->sortByDesc('total_quantity')->values()->take(10);
        
        // ─── Top Materials (Most Consumed) ──────────────────────────────
        $materialInputs = collect();
        foreach ($orders as $order) {
            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                if ($variant) {
                    $materialInputs->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'sku' => $variant->sku,
                        'quantity' => $input->actual_quantity,
                        'cost' => $input->actual_cost,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                    ]);
                }
            }
        }
        
        $topMaterials = $materialInputs->groupBy('variant_id')->map(function($items, $variantId) {
            $first = $items->first();
            return (object)[
                'variant_id' => $variantId,
                'variant_name' => $first->variant_name,
                'sku' => $first->sku,
                'category' => $first->category,
                'total_quantity' => $items->sum('quantity'),
                'total_cost' => $items->sum('cost'),
                'avg_cost_per_unit' => $items->sum('quantity') > 0 ? $items->sum('cost') / $items->sum('quantity') : 0,
                'order_count' => $items->count(),
            ];
        })->sortByDesc('total_quantity')->values()->take(10);
        
        // ─── Status Breakdown ────────────────────────────────────────────
        $statusBreakdown = collect([
            (object)['status' => 'draft', 'label' => __('pagination.draft'), 'count' => $summary['draft_orders'], 'color' => 'secondary'],
            (object)['status' => 'in_progress', 'label' => __('pagination.in_progress'), 'count' => $summary['in_progress_orders'], 'color' => 'warning'],
            (object)['status' => 'completed', 'label' => __('pagination.completed'), 'count' => $summary['completed_orders'], 'color' => 'success'],
            (object)['status' => 'cancelled', 'label' => __('pagination.cancelled'), 'count' => $summary['cancelled_orders'], 'color' => 'danger'],
        ])->filter(fn($item) => $item->count > 0)->values();
        
        // ─── Location Breakdown ──────────────────────────────────────────
        $locationBreakdown = $orders->groupBy('location_id')->map(function($items, $locationId) {
            $location = $items->first()->location;
            return (object)[
                'location_id' => $locationId,
                'location_name' => $location ? $location->name : 'Unknown',
                'orders' => $items->count(),
                'completed' => $items->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
                'total_cost' => $items->sum('total_cost'),
                'profit' => $items->sum(function($order) {
                    return $order->total_output_cost - $order->total_input_cost;
                }),
            ];
        })->values();
        
        // ─── Get Filter Options ──────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $statuses = [
            ['value' => 'all', 'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_DRAFT, 'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_COMPLETED, 'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_CANCELLED, 'label' => __('pagination.cancelled')],
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
     * Detailed analysis of costs across all production orders
     */
    public function costAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $status = $request->get('status', 'all');
        $costType = $request->get('cost_type', 'all'); // input, output, all
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get Production Orders ──────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($variantId) {
            $query->whereHas('inputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            })->orWhereHas('outputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            });
        }
        
        $orders = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        
        // ─── Cost Summary ──────────────────────────────────────────────
        $costSummary = [
            // Input Costs
            'total_input_cost' => $orders->sum('total_input_cost'),
            'avg_input_cost_per_order' => $orders->count() > 0 ? $orders->avg('total_input_cost') : 0,
            'min_input_cost' => $orders->min('total_input_cost') ?? 0,
            'max_input_cost' => $orders->max('total_input_cost') ?? 0,
            
            // Output Costs
            'total_output_cost' => $orders->sum('total_output_cost'),
            'avg_output_cost_per_order' => $orders->count() > 0 ? $orders->avg('total_output_cost') : 0,
            'min_output_cost' => $orders->min('total_output_cost') ?? 0,
            'max_output_cost' => $orders->max('total_output_cost') ?? 0,
            
            // Total Costs
            'total_cost' => $orders->sum('total_cost'),
            'avg_cost_per_order' => $orders->count() > 0 ? $orders->avg('total_cost') : 0,
            
            // Profit
            'total_profit' => $orders->sum(function($order) {
                return $order->total_output_cost - $order->total_input_cost;
            }),
            'avg_profit_per_order' => $orders->count() > 0 ? $orders->avg(function($order) {
                return $order->total_output_cost - $order->total_input_cost;
            }) : 0,
            
            // Cost Efficiency
            'cost_efficiency' => $orders->sum('total_cost') > 0 
                ? ($orders->sum('total_output_cost') / $orders->sum('total_cost')) * 100 
                : 0,
            'profit_margin' => $orders->sum('total_output_cost') > 0 
                ? ($orders->sum(function($order) {
                    return $order->total_output_cost - $order->total_input_cost;
                }) / $orders->sum('total_output_cost')) * 100 
                : 0,
        ];
        
        // ─── Input Cost Breakdown by Category ──────────────────────────
        $inputCostByCategory = collect();
        foreach ($orders as $order) {
            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                if ($variant) {
                    $categoryName = $variant->product->category->name ?? 'Uncategorized';
                    $inputCostByCategory->push((object)[
                        'category' => $categoryName,
                        'cost' => $input->actual_cost,
                        'quantity' => $input->actual_quantity,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                    ]);
                }
            }
        }
        
        $categoryInputCosts = $inputCostByCategory->groupBy('category')->map(function($items, $category) {
            return (object)[
                'category' => $category,
                'total_cost' => $items->sum('cost'),
                'total_quantity' => $items->sum('quantity'),
                'avg_cost_per_unit' => $items->sum('quantity') > 0 ? $items->sum('cost') / $items->sum('quantity') : 0,
                'item_count' => $items->count(),
            ];
        })->sortByDesc('total_cost')->values();
        
        // ─── Output Cost Breakdown by Category ─────────────────────────
        $outputCostByCategory = collect();
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $categoryName = $variant->product->category->name ?? 'Uncategorized';
                    $outputCostByCategory->push((object)[
                        'category' => $categoryName,
                        'cost' => $output->production_cost,
                        'quantity' => $output->actual_quantity,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'selling_price' => $output->selling_price,
                    ]);
                }
            }
        }
        
        $categoryOutputCosts = $outputCostByCategory->groupBy('category')->map(function($items, $category) {
            return (object)[
                'category' => $category,
                'total_cost' => $items->sum('cost'),
                'total_quantity' => $items->sum('quantity'),
                'avg_cost_per_unit' => $items->sum('quantity') > 0 ? $items->sum('cost') / $items->sum('quantity') : 0,
                'item_count' => $items->count(),
            ];
        })->sortByDesc('total_cost')->values();
        
        // ─── Cost Per Unit Analysis ────────────────────────────────────
        $costPerUnitAnalysis = collect();
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant && $output->actual_quantity > 0) {
                    $totalInputCostForOutput = $order->total_input_cost;
                    $outputCost = $output->production_cost;
                    $quantity = $output->actual_quantity;
                    
                    $costPerUnitAnalysis->push((object)[
                        'order_number' => $order->production_number,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'quantity' => $quantity,
                        'input_cost' => $totalInputCostForOutput,
                        'output_cost' => $outputCost,
                        'total_cost' => $totalInputCostForOutput + $outputCost,
                        'cost_per_unit' => $quantity > 0 ? ($totalInputCostForOutput + $outputCost) / $quantity : 0,
                        'selling_price' => $output->selling_price ?? 0,
                        'profit_per_unit' => $quantity > 0 ? (($output->selling_price ?? 0) - (($totalInputCostForOutput + $outputCost) / $quantity)) : 0,
                        'profit_margin' => ($output->selling_price ?? 0) > 0 
                            ? ((($output->selling_price ?? 0) - (($totalInputCostForOutput + $outputCost) / $quantity)) / ($output->selling_price ?? 0)) * 100 
                            : 0,
                    ]);
                }
            }
        }
        
        $costPerUnitSummary = $costPerUnitAnalysis->groupBy('variant_sku')->map(function($items, $sku) {
            $first = $items->first();
            return (object)[
                'variant_name' => $first->variant_name,
                'variant_sku' => $sku,
                'total_quantity' => $items->sum('quantity'),
                'avg_cost_per_unit' => $items->avg('cost_per_unit'),
                'avg_selling_price' => $items->avg('selling_price'),
                'avg_profit_per_unit' => $items->avg('profit_per_unit'),
                'avg_profit_margin' => $items->avg('profit_margin'),
                'order_count' => $items->count(),
            ];
        })->sortByDesc('avg_profit_margin')->values();
        
        // ─── Monthly Cost Trends ────────────────────────────────────────
        $monthlyCostTrends = $orders->groupBy(function($order) {
            return $order->created_at->format('Y-m');
        })->map(function($items, $month) {
            return (object)[
                'month' => Carbon::parse($month . '-01')->format('M Y'),
                'input_cost' => $items->sum('total_input_cost'),
                'output_cost' => $items->sum('total_output_cost'),
                'total_cost' => $items->sum('total_cost'),
                'profit' => $items->sum(function($order) {
                    return $order->total_output_cost - $order->total_input_cost;
                }),
                'orders' => $items->count(),
                'avg_cost_per_order' => $items->count() > 0 ? $items->avg('total_cost') : 0,
            ];
        })->sortKeys()->values();
        
        // ─── Cost Distribution by Status ──────────────────────────────
        $costByStatus = collect([
            'draft' => (object)[
                'status' => __('pagination.draft'),
                'orders' => $orders->where('status', ProductionOrder::STATUS_DRAFT)->count(),
                'total_cost' => $orders->where('status', ProductionOrder::STATUS_DRAFT)->sum('total_cost'),
                'avg_cost' => $orders->where('status', ProductionOrder::STATUS_DRAFT)->count() > 0 
                    ? $orders->where('status', ProductionOrder::STATUS_DRAFT)->avg('total_cost') 
                    : 0,
            ],
            'in_progress' => (object)[
                'status' => __('pagination.in_progress'),
                'orders' => $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->count(),
                'total_cost' => $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->sum('total_cost'),
                'avg_cost' => $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->count() > 0 
                    ? $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->avg('total_cost') 
                    : 0,
            ],
            'completed' => (object)[
                'status' => __('pagination.completed'),
                'orders' => $orders->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
                'total_cost' => $orders->where('status', ProductionOrder::STATUS_COMPLETED)->sum('total_cost'),
                'avg_cost' => $orders->where('status', ProductionOrder::STATUS_COMPLETED)->count() > 0 
                    ? $orders->where('status', ProductionOrder::STATUS_COMPLETED)->avg('total_cost') 
                    : 0,
            ],
            'cancelled' => (object)[
                'status' => __('pagination.cancelled'),
                'orders' => $orders->where('status', ProductionOrder::STATUS_CANCELLED)->count(),
                'total_cost' => $orders->where('status', ProductionOrder::STATUS_CANCELLED)->sum('total_cost'),
                'avg_cost' => $orders->where('status', ProductionOrder::STATUS_CANCELLED)->count() > 0 
                    ? $orders->where('status', ProductionOrder::STATUS_CANCELLED)->avg('total_cost') 
                    : 0,
            ],
        ])->filter(fn($item) => $item->orders > 0)->values();
        
        // ─── Get Filter Options ──────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        
        $statuses = [
            ['value' => 'all', 'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_DRAFT, 'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_COMPLETED, 'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_CANCELLED, 'label' => __('pagination.cancelled')],
        ];
        
        $costTypes = [
            ['value' => 'all', 'label' => __('pagination.all_costs')],
            ['value' => 'input', 'label' => __('pagination.input_costs')],
            ['value' => 'output', 'label' => __('pagination.output_costs')],
        ];
        
        // ─── Pagination for Cost Per Unit Analysis ──────────────────────
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
     * Analyzes efficiency metrics across all production orders
     */
    public function efficiency(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $status = $request->get('status', 'completed');
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get Production Orders ──────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
            'startedBy',
            'completedBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($variantId) {
            $query->whereHas('inputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            })->orWhereHas('outputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            });
        }
        
        $orders = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        $inProgressOrders = $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS);
        
        // ─── Efficiency Metrics ──────────────────────────────────────────
        
        // 1. Yield Efficiency (Output / Input)
        $totalInputQty = $orders->sum('total_input_quantity');
        $totalOutputQty = $orders->sum('total_output_quantity');
        $overallYield = $totalInputQty > 0 ? ($totalOutputQty / $totalInputQty) * 100 : 0;
        
        // 2. Cost Efficiency (Output Cost / Total Cost)
        $totalInputCost = $orders->sum('total_input_cost');
        $totalOutputCost = $orders->sum('total_output_cost');
        $totalCost = $orders->sum('total_cost');
        $overallCostEfficiency = $totalCost > 0 ? ($totalOutputCost / $totalCost) * 100 : 0;
        
        // 3. Time Efficiency
        $totalDurationHours = $completedOrders->sum(function($order) {
            return $order->started_at && $order->completed_at 
                ? $order->started_at->diffInHours($order->completed_at) 
                : 0;
        });
        $avgDurationHours = $completedOrders->count() > 0 ? $totalDurationHours / $completedOrders->count() : 0;
        
        // 4. Quality Efficiency
        $totalDefective = $orders->sum(function($order) {
            return $order->outputs->sum('defective_quantity');
        });
        $totalOutputQtyAll = $orders->sum(function($order) {
            return $order->outputs->sum('actual_quantity');
        });
        $qualityRate = $totalOutputQtyAll > 0 ? (($totalOutputQtyAll - $totalDefective) / $totalOutputQtyAll) * 100 : 0;
        
        // 5. Waste Efficiency
        $totalWaste = $orders->sum(function($order) {
            return $order->inputs->sum('waste_quantity');
        });
        $wasteRate = $totalInputQty > 0 ? ($totalWaste / $totalInputQty) * 100 : 0;
        
        // 6. Profit Efficiency
        $totalProfit = $orders->sum(function($order) {
            return $order->total_output_cost - $order->total_input_cost;
        });
        $profitMargin = $totalOutputCost > 0 ? ($totalProfit / $totalOutputCost) * 100 : 0;
        
        // ─── Efficiency Summary ──────────────────────────────────────────
        $efficiencySummary = [
            'total_orders' => $orders->count(),
            'completed_orders' => $completedOrders->count(),
            'in_progress_orders' => $inProgressOrders->count(),
            'overall_yield' => $overallYield,
            'overall_cost_efficiency' => $overallCostEfficiency,
            'avg_duration_hours' => $avgDurationHours,
            'quality_rate' => $qualityRate,
            'waste_rate' => $wasteRate,
            'profit_margin' => $profitMargin,
            'total_input_qty' => $totalInputQty,
            'total_output_qty' => $totalOutputQty,
            'total_waste' => $totalWaste,
            'total_defective' => $totalDefective,
            'total_profit' => $totalProfit,
            'total_input_cost' => $totalInputCost,
            'total_output_cost' => $totalOutputCost,
            'total_cost' => $totalCost,
        ];
        
        // ─── Efficiency by Order ─────────────────────────────────────────
        $efficiencyByOrder = $orders->map(function($order) {
            $inputQty = $order->total_input_quantity;
            $outputQty = $order->total_output_quantity;
            $inputCost = $order->total_input_cost;
            $outputCost = $order->total_output_cost;
            $totalCost = $order->total_cost;
            
            $duration = $order->started_at && $order->completed_at 
                ? $order->started_at->diffInHours($order->completed_at) 
                : 0;
            
            $defective = $order->outputs->sum('defective_quantity');
            $waste = $order->inputs->sum('waste_quantity');
            $profit = $outputCost - $inputCost;
            
            return (object)[
                'id' => $order->id,
                'production_number' => $order->production_number,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'status_badge' => $order->status_badge,
                'location' => $order->location->name ?? '-',
                'created_at' => $order->created_at,
                'started_at' => $order->started_at,
                'completed_at' => $order->completed_at,
                'input_quantity' => $inputQty,
                'output_quantity' => $outputQty,
                'yield' => $inputQty > 0 ? ($outputQty / $inputQty) * 100 : 0,
                'input_cost' => $inputCost,
                'output_cost' => $outputCost,
                'total_cost' => $totalCost,
                'cost_efficiency' => $totalCost > 0 ? ($outputCost / $totalCost) * 100 : 0,
                'duration_hours' => $duration,
                'defective' => $defective,
                'waste' => $waste,
                'quality_rate' => $outputQty > 0 ? (($outputQty - $defective) / $outputQty) * 100 : 0,
                'waste_rate' => $inputQty > 0 ? ($waste / $inputQty) * 100 : 0,
                'profit' => $profit,
                'profit_margin' => $outputCost > 0 ? ($profit / $outputCost) * 100 : 0,
                'created_by' => $order->createdBy->name ?? '-',
            ];
        });
        
        // ─── Apply Pagination ────────────────────────────────────────────
        $paginatedEfficiency = $this->paginateCollection($efficiencyByOrder, $perPage, 'page');
        
        // ─── Efficiency Trends (Monthly) ─────────────────────────────────
        $monthlyEfficiency = $completedOrders->groupBy(function($order) {
            return $order->completed_at ? $order->completed_at->format('Y-m') : $order->created_at->format('Y-m');
        })->map(function($items, $month) {
            $inputQty = $items->sum('total_input_quantity');
            $outputQty = $items->sum('total_output_quantity');
            $inputCost = $items->sum('total_input_cost');
            $outputCost = $items->sum('total_output_cost');
            $totalCost = $items->sum('total_cost');
            $profit = $outputCost - $inputCost;
            $defective = $items->sum(function($order) {
                return $order->outputs->sum('defective_quantity');
            });
            $waste = $items->sum(function($order) {
                return $order->inputs->sum('waste_quantity');
            });
            $duration = $items->sum(function($order) {
                return $order->started_at && $order->completed_at 
                    ? $order->started_at->diffInHours($order->completed_at) 
                    : 0;
            });
            
            return (object)[
                'month' => Carbon::parse($month . '-01')->format('M Y'),
                'orders' => $items->count(),
                'yield' => $inputQty > 0 ? ($outputQty / $inputQty) * 100 : 0,
                'cost_efficiency' => $totalCost > 0 ? ($outputCost / $totalCost) * 100 : 0,
                'quality_rate' => $outputQty > 0 ? (($outputQty - $defective) / $outputQty) * 100 : 0,
                'waste_rate' => $inputQty > 0 ? ($waste / $inputQty) * 100 : 0,
                'profit_margin' => $outputCost > 0 ? ($profit / $outputCost) * 100 : 0,
                'avg_duration' => $items->count() > 0 ? $duration / $items->count() : 0,
                'profit' => $profit,
            ];
        })->sortKeys()->values();
        
        // ─── Efficiency by Location ──────────────────────────────────────
        $efficiencyByLocation = $completedOrders->groupBy('location_id')->map(function($items, $locationId) {
            $location = $items->first()->location;
            $inputQty = $items->sum('total_input_quantity');
            $outputQty = $items->sum('total_output_quantity');
            $inputCost = $items->sum('total_input_cost');
            $outputCost = $items->sum('total_output_cost');
            $totalCost = $items->sum('total_cost');
            $profit = $outputCost - $inputCost;
            $defective = $items->sum(function($order) {
                return $order->outputs->sum('defective_quantity');
            });
            $waste = $items->sum(function($order) {
                return $order->inputs->sum('waste_quantity');
            });
            $duration = $items->sum(function($order) {
                return $order->started_at && $order->completed_at 
                    ? $order->started_at->diffInHours($order->completed_at) 
                    : 0;
            });
            
            return (object)[
                'location_name' => $location ? $location->name : 'Unknown',
                'orders' => $items->count(),
                'yield' => $inputQty > 0 ? ($outputQty / $inputQty) * 100 : 0,
                'cost_efficiency' => $totalCost > 0 ? ($outputCost / $totalCost) * 100 : 0,
                'quality_rate' => $outputQty > 0 ? (($outputQty - $defective) / $outputQty) * 100 : 0,
                'waste_rate' => $inputQty > 0 ? ($waste / $inputQty) * 100 : 0,
                'profit_margin' => $outputCost > 0 ? ($profit / $outputCost) * 100 : 0,
                'avg_duration' => $items->count() > 0 ? $duration / $items->count() : 0,
                'profit' => $profit,
            ];
        })->values();
        
        // ─── Efficiency by Product ───────────────────────────────────────
        $productEfficiency = collect();
        foreach ($completedOrders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $productEfficiency->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                        'total_quantity' => $output->actual_quantity,
                        'total_cost' => $output->production_cost,
                        'input_cost' => $order->total_input_cost,
                        'profit' => $output->production_cost - $order->total_input_cost,
                        'defective' => $output->defective_quantity,
                        'order_count' => 1,
                    ]);
                }
            }
        }
        
        $productEfficiencySummary = $productEfficiency->groupBy('variant_id')->map(function($items, $variantId) {
            $first = $items->first();
            $totalQuantity = $items->sum('total_quantity');
            $totalCost = $items->sum('total_cost');
            $totalInputCost = $items->sum('input_cost');
            $totalProfit = $items->sum('profit');
            $totalDefective = $items->sum('defective');
            
            return (object)[
                'variant_name' => $first->variant_name,
                'variant_sku' => $first->variant_sku,
                'category' => $first->category,
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
                'total_input_cost' => $totalInputCost,
                'total_profit' => $totalProfit,
                'total_defective' => $totalDefective,
                'profit_margin' => $totalCost > 0 ? ($totalProfit / $totalCost) * 100 : 0,
                'quality_rate' => $totalQuantity > 0 ? (($totalQuantity - $totalDefective) / $totalQuantity) * 100 : 0,
                'cost_per_unit' => $totalQuantity > 0 ? $totalCost / $totalQuantity : 0,
                'profit_per_unit' => $totalQuantity > 0 ? $totalProfit / $totalQuantity : 0,
                'order_count' => $items->count(),
            ];
        })->sortByDesc('total_profit')->values()->take(10);
        
        // ─── Get Filter Options ──────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        
        $statuses = [
            ['value' => 'all', 'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED, 'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_DRAFT, 'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_CANCELLED, 'label' => __('pagination.cancelled')],
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
     * Analyzes how production orders affect inventory levels
     */
    public function inventoryImpact(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $status = $request->get('status', 'completed');
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get Production Orders ──────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($variantId) {
            $query->whereHas('inputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            })->orWhereHas('outputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            });
        }
        
        $orders = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        
        // ─── Inventory Impact Summary ──────────────────────────────────
        
        // 1. Total Inventory Movement
        $totalInputQty = $orders->sum('total_input_quantity');
        $totalOutputQty = $orders->sum('total_output_quantity');
        $netInventoryChange = $totalOutputQty - $totalInputQty;
        
        // 2. Inventory Value Impact
        $totalInputValue = $orders->sum('total_input_cost');
        $totalOutputValue = $orders->sum('total_output_cost');
        $netValueChange = $totalOutputValue - $totalInputValue;
        
        // 3. Stock Consumption by Product
        $consumedProducts = collect();
        foreach ($orders as $order) {
            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                if ($variant) {
                    $consumedProducts->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                        'quantity' => $input->actual_quantity,
                        'cost' => $input->actual_cost,
                        'order_number' => $order->production_number,
                        'order_date' => $order->created_at,
                        'location' => $order->location->name ?? '-',
                    ]);
                }
            }
        }
        
        $consumedSummary = $consumedProducts->groupBy('variant_id')->map(function($items, $variantId) {
            $first = $items->first();
            return (object)[
                'variant_id' => $variantId,
                'variant_name' => $first->variant_name,
                'variant_sku' => $first->variant_sku,
                'category' => $first->category,
                'total_quantity' => $items->sum('quantity'),
                'total_cost' => $items->sum('cost'),
                'order_count' => $items->count(),
            ];
        })->sortByDesc('total_quantity')->values();
        
        // 4. Stock Production by Product
        $producedProducts = collect();
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $producedProducts->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                        'quantity' => $output->actual_quantity,
                        'cost' => $output->production_cost,
                        'order_number' => $order->production_number,
                        'order_date' => $order->created_at,
                        'location' => $order->location->name ?? '-',
                    ]);
                }
            }
        }
        
        $producedSummary = $producedProducts->groupBy('variant_id')->map(function($items, $variantId) {
            $first = $items->first();
            return (object)[
                'variant_id' => $variantId,
                'variant_name' => $first->variant_name,
                'variant_sku' => $first->variant_sku,
                'category' => $first->category,
                'total_quantity' => $items->sum('quantity'),
                'total_cost' => $items->sum('cost'),
                'order_count' => $items->count(),
            ];
        })->sortByDesc('total_quantity')->values();
        
        // 5. Net Inventory Impact by Product
        $allVariantIds = $consumedProducts->pluck('variant_id')
            ->merge($producedProducts->pluck('variant_id'))
            ->unique();
        
        $netImpactByProduct = collect();
        foreach ($allVariantIds as $vid) {
            $consumed = $consumedProducts->where('variant_id', $vid);
            $produced = $producedProducts->where('variant_id', $vid);
            
            $consumedQty = $consumed->sum('quantity');
            $producedQty = $produced->sum('quantity');
            $consumedCost = $consumed->sum('cost');
            $producedCost = $produced->sum('cost');
            
            $firstConsumed = $consumed->first();
            $firstProduced = $produced->first();
            $variantName = $firstConsumed ? $firstConsumed->variant_name : ($firstProduced ? $firstProduced->variant_name : 'Unknown');
            $variantSku = $firstConsumed ? $firstConsumed->variant_sku : ($firstProduced ? $firstProduced->variant_sku : 'N/A');
            $category = $firstConsumed ? $firstConsumed->category : ($firstProduced ? $firstProduced->category : 'Uncategorized');
            
            $netQty = $producedQty - $consumedQty;
            $netValue = $producedCost - $consumedCost;
            
            if ($consumedQty > 0 || $producedQty > 0) {
                $netImpactByProduct->push((object)[
                    'variant_id' => $vid,
                    'variant_name' => $variantName,
                    'variant_sku' => $variantSku,
                    'category' => $category,
                    'consumed_quantity' => $consumedQty,
                    'produced_quantity' => $producedQty,
                    'net_quantity' => $netQty,
                    'consumed_cost' => $consumedCost,
                    'produced_cost' => $producedCost,
                    'net_value' => $netValue,
                    'order_count' => $consumed->count() + $produced->count(),
                    'impact_type' => $netQty > 0 ? 'net_producer' : ($netQty < 0 ? 'net_consumer' : 'neutral'),
                    'impact_color' => $netQty > 0 ? 'success' : ($netQty < 0 ? 'danger' : 'secondary'),
                ]);
            }
        }
        
        $netImpactSorted = $netImpactByProduct->sortByDesc('net_quantity')->values();
        
        // 6. Inventory Impact by Category
        $categoryImpact = $netImpactByProduct->groupBy('category')->map(function($items, $category) {
            return (object)[
                'category' => $category,
                'consumed_quantity' => $items->sum('consumed_quantity'),
                'produced_quantity' => $items->sum('produced_quantity'),
                'net_quantity' => $items->sum('net_quantity'),
                'consumed_value' => $items->sum('consumed_cost'),
                'produced_value' => $items->sum('produced_cost'),
                'net_value' => $items->sum('net_value'),
                'product_count' => $items->count(),
            ];
        })->values();
        
        // 7. Monthly Inventory Impact
        $monthlyImpact = $completedOrders->groupBy(function($order) {
            return $order->completed_at ? $order->completed_at->format('Y-m') : $order->created_at->format('Y-m');
        })->map(function($items, $month) {
            $inputQty = $items->sum('total_input_quantity');
            $outputQty = $items->sum('total_output_quantity');
            $inputValue = $items->sum('total_input_cost');
            $outputValue = $items->sum('total_output_cost');
            
            return (object)[
                'month' => Carbon::parse($month . '-01')->format('M Y'),
                'orders' => $items->count(),
                'input_quantity' => $inputQty,
                'output_quantity' => $outputQty,
                'net_quantity' => $outputQty - $inputQty,
                'input_value' => $inputValue,
                'output_value' => $outputValue,
                'net_value' => $outputValue - $inputValue,
            ];
        })->sortKeys()->values();
        
        // 8. Top Consumed vs Produced
        $topConsumed = $consumedSummary->take(10);
        $topProduced = $producedSummary->take(10);
        
        // ─── Inventory Impact by Order ──────────────────────────────────
        $impactByOrder = $completedOrders->map(function($order) {
            $netQty = $order->total_output_quantity - $order->total_input_quantity;
            $netValue = $order->total_output_cost - $order->total_input_cost;
            $defective = $order->outputs->sum('defective_quantity');
            $waste = $order->inputs->sum('waste_quantity');
            
            return (object)[
                'id' => $order->id,
                'production_number' => $order->production_number,
                'status' => $order->status_label,
                'status_badge' => $order->status_badge,
                'location' => $order->location->name ?? '-',
                'created_at' => $order->created_at,
                'input_quantity' => $order->total_input_quantity,
                'output_quantity' => $order->total_output_quantity,
                'net_quantity' => $netQty,
                'input_value' => $order->total_input_cost,
                'output_value' => $order->total_output_cost,
                'net_value' => $netValue,
                'defective' => $defective,
                'waste' => $waste,
                'created_by' => $order->createdBy->name ?? '-',
            ];
        });
        
        $paginatedImpact = $this->paginateCollection($impactByOrder, $perPage, 'page');
        
        // ─── Summary Statistics ──────────────────────────────────────────
        $impactSummary = [
            'total_orders' => $orders->count(),
            'completed_orders' => $completedOrders->count(),
            'total_input_quantity' => $totalInputQty,
            'total_output_quantity' => $totalOutputQty,
            'net_inventory_change' => $netInventoryChange,
            'total_input_value' => $totalInputValue,
            'total_output_value' => $totalOutputValue,
            'net_value_change' => $netValueChange,
            'total_consumed_products' => $consumedSummary->count(),
            'total_produced_products' => $producedSummary->count(),
            'total_defective' => $orders->sum(function($order) {
                return $order->outputs->sum('defective_quantity');
            }),
            'total_waste' => $orders->sum(function($order) {
                return $order->inputs->sum('waste_quantity');
            }),
            'inventory_turnover' => $totalInputQty > 0 ? $totalOutputQty / $totalInputQty : 0,
        ];
        
        // ─── Get Filter Options ──────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        
        $statuses = [
            ['value' => 'all', 'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED, 'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_DRAFT, 'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_CANCELLED, 'label' => __('pagination.cancelled')],
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
     * Analyzes quality metrics across all production orders
     */
    public function qualityAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $status = $request->get('status', 'completed');
        $qualityStatus = $request->get('quality_status', 'all');
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get Production Orders ──────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($variantId) {
            $query->whereHas('inputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            })->orWhereHas('outputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            });
        }
        
        $orders = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        
        // ─── Quality Summary ────────────────────────────────────────────
        
        // 1. Input Quality Metrics
        $totalInputs = 0;
        $acceptedInputs = 0;
        $rejectedInputs = 0;
        $pendingInputs = 0;
        $totalInputWaste = 0;
        $totalInputQuantity = 0;
        
        foreach ($orders as $order) {
            foreach ($order->inputs as $input) {
                $totalInputs++;
                $totalInputQuantity += $input->actual_quantity;
                $totalInputWaste += $input->waste_quantity;
                
                switch ($input->quality_status) {
                    case ProductionOrderInput::QUALITY_ACCEPTED:
                        $acceptedInputs++;
                        break;
                    case ProductionOrderInput::QUALITY_REJECTED:
                        $rejectedInputs++;
                        break;
                    case ProductionOrderInput::QUALITY_PENDING:
                    default:
                        $pendingInputs++;
                        break;
                }
            }
        }
        
        // 2. Output Quality Metrics
        $totalOutputs = 0;
        $approvedOutputs = 0;
        $rejectedOutputs = 0;
        $pendingOutputs = 0;
        $totalDefective = 0;
        $totalOutputQuantity = 0;
        
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $totalOutputs++;
                $totalOutputQuantity += $output->actual_quantity;
                $totalDefective += $output->defective_quantity;
                
                switch ($output->quality_status) {
                    case ProductionOrderOutput::QUALITY_APPROVED:
                        $approvedOutputs++;
                        break;
                    case ProductionOrderOutput::QUALITY_REJECTED:
                        $rejectedOutputs++;
                        break;
                    case ProductionOrderOutput::QUALITY_PENDING:
                    default:
                        $pendingOutputs++;
                        break;
                }
            }
        }
        
        // 3. Quality Rates
        $inputAcceptanceRate = $totalInputs > 0 ? ($acceptedInputs / $totalInputs) * 100 : 0;
        $outputApprovalRate = $totalOutputs > 0 ? ($approvedOutputs / $totalOutputs) * 100 : 0;
        $defectiveRate = $totalOutputQuantity > 0 ? ($totalDefective / $totalOutputQuantity) * 100 : 0;
        $wasteRate = $totalInputQuantity > 0 ? ($totalInputWaste / $totalInputQuantity) * 100 : 0;
        $overallQualityScore = ($inputAcceptanceRate + $outputApprovalRate) / 2;
        
        // ─── Quality Summary ─────────────────────────────────────────────
        $qualitySummary = [
            'total_orders' => $orders->count(),
            'completed_orders' => $completedOrders->count(),
            
            // Input Quality
            'total_inputs' => $totalInputs,
            'accepted_inputs' => $acceptedInputs,
            'rejected_inputs' => $rejectedInputs,
            'pending_inputs' => $pendingInputs,
            'input_acceptance_rate' => $inputAcceptanceRate,
            'total_input_waste' => $totalInputWaste,
            'total_input_quantity' => $totalInputQuantity,
            'waste_rate' => $wasteRate,
            
            // Output Quality
            'total_outputs' => $totalOutputs,
            'approved_outputs' => $approvedOutputs,
            'rejected_outputs' => $rejectedOutputs,
            'pending_outputs' => $pendingOutputs,
            'output_approval_rate' => $outputApprovalRate,
            'total_defective' => $totalDefective,
            'total_output_quantity' => $totalOutputQuantity,
            'defective_rate' => $defectiveRate,
            
            // Overall
            'overall_quality_score' => $overallQualityScore,
            'quality_rating' => $this->getQualityRating($overallQualityScore),
            'quality_color' => $this->getQualityColor($overallQualityScore),
        ];
        
        // ─── Quality by Order ────────────────────────────────────────────
        $qualityByOrder = $orders->map(function($order) {
            $inputAccepted = $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_ACCEPTED)->count();
            $inputRejected = $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_REJECTED)->count();
            $inputTotal = $order->inputs->count();
            $inputWaste = $order->inputs->sum('waste_quantity');
            $inputQty = $order->inputs->sum('actual_quantity');
            
            $outputApproved = $order->outputs->where('quality_status', ProductionOrderOutput::QUALITY_APPROVED)->count();
            $outputRejected = $order->outputs->where('quality_status', ProductionOrderOutput::QUALITY_REJECTED)->count();
            $outputTotal = $order->outputs->count();
            $defective = $order->outputs->sum('defective_quantity');
            $outputQty = $order->outputs->sum('actual_quantity');
            
            $inputAcceptance = $inputTotal > 0 ? ($inputAccepted / $inputTotal) * 100 : 0;
            $outputApproval = $outputTotal > 0 ? ($outputApproved / $outputTotal) * 100 : 0;
            $defectiveRate = $outputQty > 0 ? ($defective / $outputQty) * 100 : 0;
            $wasteRate = $inputQty > 0 ? ($inputWaste / $inputQty) * 100 : 0;
            $overallScore = ($inputAcceptance + $outputApproval) / 2;
            
            return (object)[
                'id' => $order->id,
                'production_number' => $order->production_number,
                'status' => $order->status_label,
                'status_badge' => $order->status_badge,
                'location' => $order->location->name ?? '-',
                'created_at' => $order->created_at,
                'input_accepted' => $inputAccepted,
                'input_rejected' => $inputRejected,
                'input_total' => $inputTotal,
                'input_acceptance_rate' => $inputAcceptance,
                'input_waste' => $inputWaste,
                'output_approved' => $outputApproved,
                'output_rejected' => $outputRejected,
                'output_total' => $outputTotal,
                'output_approval_rate' => $outputApproval,
                'defective' => $defective,
                'defective_rate' => $defectiveRate,
                'waste_rate' => $wasteRate,
                'overall_quality_score' => $overallScore,
                'quality_rating' => $this->getQualityRating($overallScore),
                'quality_color' => $this->getQualityColor($overallScore),
                'created_by' => $order->createdBy->name ?? '-',
            ];
        });
        
        // ─── Apply Pagination ────────────────────────────────────────────
        $paginatedQuality = $this->paginateCollection($qualityByOrder, $perPage, 'page');
        
        // ─── Quality by Category ─────────────────────────────────────────
        $categoryQuality = collect();
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $categoryName = $variant->product->category->name ?? 'Uncategorized';
                    $categoryQuality->push((object)[
                        'category' => $categoryName,
                        'total' => $output->actual_quantity,
                        'defective' => $output->defective_quantity,
                        'approved' => $output->quality_status === ProductionOrderOutput::QUALITY_APPROVED ? $output->actual_quantity : 0,
                        'rejected' => $output->quality_status === ProductionOrderOutput::QUALITY_REJECTED ? $output->actual_quantity : 0,
                    ]);
                }
            }
        }
        
        $categoryQualitySummary = $categoryQuality->groupBy('category')->map(function($items, $category) {
            $total = $items->sum('total');
            $defective = $items->sum('defective');
            $approved = $items->sum('approved');
            $rejected = $items->sum('rejected');
            
            return (object)[
                'category' => $category,
                'total_quantity' => $total,
                'defective_quantity' => $defective,
                'approved_quantity' => $approved,
                'rejected_quantity' => $rejected,
                'defective_rate' => $total > 0 ? ($defective / $total) * 100 : 0,
                'approval_rate' => $total > 0 ? ($approved / $total) * 100 : 0,
                'quality_score' => $total > 0 ? (($approved - $defective) / $total) * 100 : 0,
            ];
        })->values();
        
        // ─── Quality Trends (Monthly) ────────────────────────────────────
        $monthlyQuality = $completedOrders->groupBy(function($order) {
            return $order->completed_at ? $order->completed_at->format('Y-m') : $order->created_at->format('Y-m');
        })->map(function($items, $month) {
            $inputAccepted = 0;
            $inputTotal = 0;
            $outputApproved = 0;
            $outputTotal = 0;
            $defective = 0;
            $waste = 0;
            $inputQty = 0;
            $outputQty = 0;
            
            foreach ($items as $order) {
                foreach ($order->inputs as $input) {
                    $inputTotal++;
                    $inputQty += $input->actual_quantity;
                    $waste += $input->waste_quantity;
                    if ($input->quality_status === ProductionOrderInput::QUALITY_ACCEPTED) {
                        $inputAccepted++;
                    }
                }
                foreach ($order->outputs as $output) {
                    $outputTotal++;
                    $outputQty += $output->actual_quantity;
                    $defective += $output->defective_quantity;
                    if ($output->quality_status === ProductionOrderOutput::QUALITY_APPROVED) {
                        $outputApproved++;
                    }
                }
            }
            
            return (object)[
                'month' => Carbon::parse($month . '-01')->format('M Y'),
                'orders' => $items->count(),
                'input_acceptance' => $inputTotal > 0 ? ($inputAccepted / $inputTotal) * 100 : 0,
                'output_approval' => $outputTotal > 0 ? ($outputApproved / $outputTotal) * 100 : 0,
                'defective_rate' => $outputQty > 0 ? ($defective / $outputQty) * 100 : 0,
                'waste_rate' => $inputQty > 0 ? ($waste / $inputQty) * 100 : 0,
                'overall_score' => (($inputTotal > 0 ? ($inputAccepted / $inputTotal) * 100 : 0) + 
                                ($outputTotal > 0 ? ($outputApproved / $outputTotal) * 100 : 0)) / 2,
                'defective' => $defective,
                'waste' => $waste,
            ];
        })->sortKeys()->values();
        
        // ─── Quality by Product ──────────────────────────────────────────
        $productQuality = collect();
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $productQuality->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                        'total_quantity' => $output->actual_quantity,
                        'defective_quantity' => $output->defective_quantity,
                        'approved' => $output->quality_status === ProductionOrderOutput::QUALITY_APPROVED,
                        'order_count' => 1,
                    ]);
                }
            }
        }
        
        $productQualitySummary = $productQuality->groupBy('variant_id')->map(function($items, $variantId) {
            $first = $items->first();
            $totalQty = $items->sum('total_quantity');
            $defectiveQty = $items->sum('defective_quantity');
            $approvedCount = $items->where('approved', true)->count();
            $totalCount = $items->count();
            
            return (object)[
                'variant_name' => $first->variant_name,
                'variant_sku' => $first->variant_sku,
                'category' => $first->category,
                'total_quantity' => $totalQty,
                'defective_quantity' => $defectiveQty,
                'defective_rate' => $totalQty > 0 ? ($defectiveQty / $totalQty) * 100 : 0,
                'approval_rate' => $totalCount > 0 ? ($approvedCount / $totalCount) * 100 : 0,
                'quality_score' => $totalQty > 0 ? (($totalQty - $defectiveQty) / $totalQty) * 100 : 0,
                'order_count' => $totalCount,
            ];
        })->sortByDesc('quality_score')->values()->take(10);
        
        // ─── Get Filter Options ──────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        
        $statuses = [
            ['value' => 'all', 'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED, 'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
        ];
        
        $qualityStatuses = [
            ['value' => 'all', 'label' => __('pagination.all_quality_statuses')],
            ['value' => 'accepted', 'label' => __('pagination.accepted')],
            ['value' => 'rejected', 'label' => __('pagination.rejected')],
            ['value' => 'pending', 'label' => __('pagination.pending')],
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
     * Get quality rating based on score
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
     * Get quality color based on score
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
     * Compares input materials vs output products across production orders
     */
    public function inputOutput(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $status = $request->get('status', 'completed');
        $comparisonType = $request->get('comparison_type', 'quantity'); // quantity, cost, both
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get Production Orders ──────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($variantId) {
            $query->whereHas('inputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            })->orWhereHas('outputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            });
        }
        
        $orders = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        
        // ─── Overall Input vs Output Summary ────────────────────────────
        $totalInputQty = $orders->sum('total_input_quantity');
        $totalOutputQty = $orders->sum('total_output_quantity');
        $totalInputCost = $orders->sum('total_input_cost');
        $totalOutputCost = $orders->sum('total_output_cost');
        
        $netQty = $totalOutputQty - $totalInputQty;
        $netCost = $totalOutputCost - $totalInputCost;
        
        $inputOutputSummary = [
            'total_orders' => $orders->count(),
            'completed_orders' => $completedOrders->count(),
            
            // Quantity
            'total_input_quantity' => $totalInputQty,
            'total_output_quantity' => $totalOutputQty,
            'net_quantity' => $netQty,
            'quantity_ratio' => $totalInputQty > 0 ? $totalOutputQty / $totalInputQty : 0,
            'quantity_efficiency' => $totalInputQty > 0 ? ($totalOutputQty / $totalInputQty) * 100 : 0,
            
            // Cost
            'total_input_cost' => $totalInputCost,
            'total_output_cost' => $totalOutputCost,
            'net_cost' => $netCost,
            'cost_ratio' => $totalInputCost > 0 ? $totalOutputCost / $totalInputCost : 0,
            'cost_efficiency' => $totalInputCost > 0 ? ($totalOutputCost / $totalInputCost) * 100 : 0,
            
            // Average per Order
            'avg_input_qty_per_order' => $orders->count() > 0 ? $totalInputQty / $orders->count() : 0,
            'avg_output_qty_per_order' => $orders->count() > 0 ? $totalOutputQty / $orders->count() : 0,
            'avg_input_cost_per_order' => $orders->count() > 0 ? $totalInputCost / $orders->count() : 0,
            'avg_output_cost_per_order' => $orders->count() > 0 ? $totalOutputCost / $orders->count() : 0,
        ];
        
        // ─── Input vs Output by Order ────────────────────────────────────
        $comparisonByOrder = $orders->map(function($order) {
            $inputQty = $order->total_input_quantity;
            $outputQty = $order->total_output_quantity;
            $inputCost = $order->total_input_cost;
            $outputCost = $order->total_output_cost;
            
            $qtyDiff = $outputQty - $inputQty;
            $costDiff = $outputCost - $inputCost;
            $qtyRatio = $inputQty > 0 ? $outputQty / $inputQty : 0;
            $costRatio = $inputCost > 0 ? $outputCost / $inputCost : 0;
            
            return (object)[
                'id' => $order->id,
                'production_number' => $order->production_number,
                'status' => $order->status_label,
                'status_badge' => $order->status_badge,
                'location' => $order->location->name ?? '-',
                'created_at' => $order->created_at,
                'input_quantity' => $inputQty,
                'output_quantity' => $outputQty,
                'qty_difference' => $qtyDiff,
                'qty_ratio' => $qtyRatio,
                'qty_efficiency' => $inputQty > 0 ? ($outputQty / $inputQty) * 100 : 0,
                'input_cost' => $inputCost,
                'output_cost' => $outputCost,
                'cost_difference' => $costDiff,
                'cost_ratio' => $costRatio,
                'cost_efficiency' => $inputCost > 0 ? ($outputCost / $inputCost) * 100 : 0,
                'created_by' => $order->createdBy->name ?? '-',
            ];
        });
        
        // ─── Apply Pagination ────────────────────────────────────────────
        $paginatedComparison = $this->paginateCollection($comparisonByOrder, $perPage, 'page');
        
        // ─── Input vs Output by Category ─────────────────────────────────
        $categoryData = collect();
        
        // Inputs by Category
        foreach ($orders as $order) {
            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                if ($variant) {
                    $categoryName = $variant->product->category->name ?? 'Uncategorized';
                    $categoryData->push((object)[
                        'category' => $categoryName,
                        'type' => 'input',
                        'quantity' => $input->actual_quantity,
                        'cost' => $input->actual_cost,
                    ]);
                }
            }
        }
        
        // Outputs by Category
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $categoryName = $variant->product->category->name ?? 'Uncategorized';
                    $categoryData->push((object)[
                        'category' => $categoryName,
                        'type' => 'output',
                        'quantity' => $output->actual_quantity,
                        'cost' => $output->production_cost,
                    ]);
                }
            }
        }
        
        $categoryComparison = $categoryData->groupBy('category')->map(function($items, $category) {
            $inputs = $items->where('type', 'input');
            $outputs = $items->where('type', 'output');
            
            $inputQty = $inputs->sum('quantity');
            $outputQty = $outputs->sum('quantity');
            $inputCost = $inputs->sum('cost');
            $outputCost = $outputs->sum('cost');
            
            return (object)[
                'category' => $category,
                'input_quantity' => $inputQty,
                'output_quantity' => $outputQty,
                'qty_difference' => $outputQty - $inputQty,
                'qty_ratio' => $inputQty > 0 ? $outputQty / $inputQty : 0,
                'input_cost' => $inputCost,
                'output_cost' => $outputCost,
                'cost_difference' => $outputCost - $inputCost,
                'cost_ratio' => $inputCost > 0 ? $outputCost / $inputCost : 0,
                'item_count' => $items->count(),
            ];
        })->values();
        
        // ─── Monthly Input vs Output Trends ──────────────────────────────
        $monthlyComparison = $completedOrders->groupBy(function($order) {
            return $order->completed_at ? $order->completed_at->format('Y-m') : $order->created_at->format('Y-m');
        })->map(function($items, $month) {
            $inputQty = $items->sum('total_input_quantity');
            $outputQty = $items->sum('total_output_quantity');
            $inputCost = $items->sum('total_input_cost');
            $outputCost = $items->sum('total_output_cost');
            
            return (object)[
                'month' => Carbon::parse($month . '-01')->format('M Y'),
                'orders' => $items->count(),
                'input_quantity' => $inputQty,
                'output_quantity' => $outputQty,
                'qty_diff' => $outputQty - $inputQty,
                'qty_ratio' => $inputQty > 0 ? $outputQty / $inputQty : 0,
                'input_cost' => $inputCost,
                'output_cost' => $outputCost,
                'cost_diff' => $outputCost - $inputCost,
                'cost_ratio' => $inputCost > 0 ? $outputCost / $inputCost : 0,
            ];
        })->sortKeys()->values();
        
        // ─── Top Input vs Output Products ─────────────────────────────────
        $productComparison = collect();
        
        // Input Products
        foreach ($orders as $order) {
            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                if ($variant) {
                    $productComparison->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'type' => 'input',
                        'quantity' => $input->actual_quantity,
                        'cost' => $input->actual_cost,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                    ]);
                }
            }
        }
        
        // Output Products
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $productComparison->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'type' => 'output',
                        'quantity' => $output->actual_quantity,
                        'cost' => $output->production_cost,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                    ]);
                }
            }
        }
        
        $productSummary = $productComparison->groupBy('variant_id')->map(function($items, $variantId) {
            $first = $items->first();
            $inputs = $items->where('type', 'input');
            $outputs = $items->where('type', 'output');
            
            $inputQty = $inputs->sum('quantity');
            $outputQty = $outputs->sum('quantity');
            $inputCost = $inputs->sum('cost');
            $outputCost = $outputs->sum('cost');
            
            return (object)[
                'variant_name' => $first->variant_name,
                'variant_sku' => $first->variant_sku,
                'category' => $first->category,
                'input_quantity' => $inputQty,
                'output_quantity' => $outputQty,
                'qty_difference' => $outputQty - $inputQty,
                'input_cost' => $inputCost,
                'output_cost' => $outputCost,
                'cost_difference' => $outputCost - $inputCost,
                'order_count' => $items->count(),
                'type' => $inputQty > $outputQty ? 'net_consumer' : ($outputQty > $inputQty ? 'net_producer' : 'neutral'),
            ];
        })->sortByDesc('qty_difference')->values()->take(10);
        
        // ─── Get Filter Options ──────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        
        $statuses = [
            ['value' => 'all', 'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED, 'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_DRAFT, 'label' => __('pagination.draft')],
        ];
        
        $comparisonTypes = [
            ['value' => 'quantity', 'label' => __('pagination.quantity_comparison')],
            ['value' => 'cost', 'label' => __('pagination.cost_comparison')],
            ['value' => 'both', 'label' => __('pagination.both_comparison')],
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
     * Analyzes waste and defective items across production orders
     */
    public function waste(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $status = $request->get('status', 'completed');
        $wasteType = $request->get('waste_type', 'all'); // all, input_waste, output_defective
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get Production Orders ──────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($variantId) {
            $query->whereHas('inputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            })->orWhereHas('outputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            });
        }
        
        $orders = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        
        // ─── Waste Summary ──────────────────────────────────────────────
        
        // Input Waste (Waste from raw materials)
        $totalInputWaste = $orders->sum(function($order) {
            return $order->inputs->sum('waste_quantity');
        });
        $totalInputQuantity = $orders->sum(function($order) {
            return $order->inputs->sum('actual_quantity');
        });
        $inputWasteRate = $totalInputQuantity > 0 ? ($totalInputWaste / $totalInputQuantity) * 100 : 0;
        
        // Output Defective (Defective finished goods)
        $totalDefective = $orders->sum(function($order) {
            return $order->outputs->sum('defective_quantity');
        });
        $totalOutputQuantity = $orders->sum(function($order) {
            return $order->outputs->sum('actual_quantity');
        });
        $outputDefectiveRate = $totalOutputQuantity > 0 ? ($totalDefective / $totalOutputQuantity) * 100 : 0;
        
        // Total Waste (Input Waste + Output Defective)
        $totalWaste = $totalInputWaste + $totalDefective;
        $totalWasteRate = ($totalInputQuantity + $totalOutputQuantity) > 0 
            ? ($totalWaste / ($totalInputQuantity + $totalOutputQuantity)) * 100 
            : 0;
        
        // Waste Cost Impact
        $wasteCost = $orders->sum(function($order) {
            $inputWasteCost = $order->inputs->sum(function($input) {
                return $input->waste_quantity * ($input->productVariant->grand_total_cost_price ?? 0);
            });
            $defectiveCost = $order->outputs->sum(function($output) {
                return $output->defective_quantity * ($output->productVariant->grand_total_cost_price ?? 0);
            });
            return $inputWasteCost + $defectiveCost;
        });
        
        $totalCost = $orders->sum('total_cost');
        $wasteCostPercentage = $totalCost > 0 ? ($wasteCost / $totalCost) * 100 : 0;
        
        // ─── Waste Summary ──────────────────────────────────────────────
        $wasteSummary = [
            'total_orders' => $orders->count(),
            'completed_orders' => $completedOrders->count(),
            
            // Input Waste
            'total_input_waste' => $totalInputWaste,
            'total_input_quantity' => $totalInputQuantity,
            'input_waste_rate' => $inputWasteRate,
            
            // Output Defective
            'total_defective' => $totalDefective,
            'total_output_quantity' => $totalOutputQuantity,
            'output_defective_rate' => $outputDefectiveRate,
            
            // Combined
            'total_waste' => $totalWaste,
            'total_waste_rate' => $totalWasteRate,
            'waste_cost' => $wasteCost,
            'waste_cost_percentage' => $wasteCostPercentage,
            'total_cost' => $totalCost,
            
            // Quality Metrics
            'good_output' => $totalOutputQuantity - $totalDefective,
            'good_rate' => $totalOutputQuantity > 0 ? (($totalOutputQuantity - $totalDefective) / $totalOutputQuantity) * 100 : 0,
        ];
        
        // ─── Waste by Order ─────────────────────────────────────────────
        $wasteByOrder = $orders->map(function($order) {
            $inputWaste = $order->inputs->sum('waste_quantity');
            $inputQty = $order->inputs->sum('actual_quantity');
            $defective = $order->outputs->sum('defective_quantity');
            $outputQty = $order->outputs->sum('actual_quantity');
            
            $inputWasteRate = $inputQty > 0 ? ($inputWaste / $inputQty) * 100 : 0;
            $defectiveRate = $outputQty > 0 ? ($defective / $outputQty) * 100 : 0;
            $totalWaste = $inputWaste + $defective;
            $totalRate = ($inputQty + $outputQty) > 0 ? ($totalWaste / ($inputQty + $outputQty)) * 100 : 0;
            
            $wasteCost = $order->inputs->sum(function($input) {
                return $input->waste_quantity * ($input->productVariant->grand_total_cost_price ?? 0);
            }) + $order->outputs->sum(function($output) {
                return $output->defective_quantity * ($output->productVariant->grand_total_cost_price ?? 0);
            });
            
            return (object)[
                'id' => $order->id,
                'production_number' => $order->production_number,
                'status' => $order->status_label,
                'status_badge' => $order->status_badge,
                'location' => $order->location->name ?? '-',
                'created_at' => $order->created_at,
                'input_waste' => $inputWaste,
                'input_quantity' => $inputQty,
                'input_waste_rate' => $inputWasteRate,
                'defective' => $defective,
                'output_quantity' => $outputQty,
                'defective_rate' => $defectiveRate,
                'total_waste' => $totalWaste,
                'total_waste_rate' => $totalRate,
                'waste_cost' => $wasteCost,
                'created_by' => $order->createdBy->name ?? '-',
            ];
        });
        
        // ─── Apply Pagination ────────────────────────────────────────────
        $paginatedWaste = $this->paginateCollection($wasteByOrder, $perPage, 'page');
        
        // ─── Waste by Category ───────────────────────────────────────────
        $categoryWaste = collect();
        
        // Input Waste by Category
        foreach ($orders as $order) {
            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                if ($variant) {
                    $categoryName = $variant->product->category->name ?? 'Uncategorized';
                    $categoryWaste->push((object)[
                        'category' => $categoryName,
                        'type' => 'input_waste',
                        'waste_quantity' => $input->waste_quantity,
                        'total_quantity' => $input->actual_quantity,
                    ]);
                }
            }
        }
        
        // Output Defective by Category
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $categoryName = $variant->product->category->name ?? 'Uncategorized';
                    $categoryWaste->push((object)[
                        'category' => $categoryName,
                        'type' => 'output_defective',
                        'waste_quantity' => $output->defective_quantity,
                        'total_quantity' => $output->actual_quantity,
                    ]);
                }
            }
        }
        
        $categoryWasteSummary = $categoryWaste->groupBy('category')->map(function($items, $category) {
            $inputWaste = $items->where('type', 'input_waste');
            $outputDefective = $items->where('type', 'output_defective');
            
            $inputWasteQty = $inputWaste->sum('waste_quantity');
            $inputTotalQty = $inputWaste->sum('total_quantity');
            $outputDefectiveQty = $outputDefective->sum('waste_quantity');
            $outputTotalQty = $outputDefective->sum('total_quantity');
            
            return (object)[
                'category' => $category,
                'input_waste' => $inputWasteQty,
                'input_total' => $inputTotalQty,
                'input_waste_rate' => $inputTotalQty > 0 ? ($inputWasteQty / $inputTotalQty) * 100 : 0,
                'defective' => $outputDefectiveQty,
                'output_total' => $outputTotalQty,
                'defective_rate' => $outputTotalQty > 0 ? ($outputDefectiveQty / $outputTotalQty) * 100 : 0,
                'total_waste' => $inputWasteQty + $outputDefectiveQty,
                'total_rate' => ($inputTotalQty + $outputTotalQty) > 0 
                    ? (($inputWasteQty + $outputDefectiveQty) / ($inputTotalQty + $outputTotalQty)) * 100 
                    : 0,
            ];
        })->values();
        
        // ─── Monthly Waste Trends ────────────────────────────────────────
        $monthlyWaste = $completedOrders->groupBy(function($order) {
            return $order->completed_at ? $order->completed_at->format('Y-m') : $order->created_at->format('Y-m');
        })->map(function($items, $month) {
            $inputWaste = $items->sum(function($order) {
                return $order->inputs->sum('waste_quantity');
            });
            $inputQty = $items->sum(function($order) {
                return $order->inputs->sum('actual_quantity');
            });
            $defective = $items->sum(function($order) {
                return $order->outputs->sum('defective_quantity');
            });
            $outputQty = $items->sum(function($order) {
                return $order->outputs->sum('actual_quantity');
            });
            
            return (object)[
                'month' => Carbon::parse($month . '-01')->format('M Y'),
                'orders' => $items->count(),
                'input_waste' => $inputWaste,
                'input_waste_rate' => $inputQty > 0 ? ($inputWaste / $inputQty) * 100 : 0,
                'defective' => $defective,
                'defective_rate' => $outputQty > 0 ? ($defective / $outputQty) * 100 : 0,
                'total_waste' => $inputWaste + $defective,
                'total_rate' => ($inputQty + $outputQty) > 0 
                    ? (($inputWaste + $defective) / ($inputQty + $outputQty)) * 100 
                    : 0,
            ];
        })->sortKeys()->values();
        
        // ─── Waste by Product ────────────────────────────────────────────
        $productWaste = collect();
        
        foreach ($orders as $order) {
            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                if ($variant) {
                    $productWaste->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                        'type' => 'input',
                        'waste_quantity' => $input->waste_quantity,
                        'total_quantity' => $input->actual_quantity,
                    ]);
                }
            }
            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if ($variant) {
                    $productWaste->push((object)[
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'variant_sku' => $variant->sku,
                        'category' => $variant->product->category->name ?? 'Uncategorized',
                        'type' => 'output',
                        'waste_quantity' => $output->defective_quantity,
                        'total_quantity' => $output->actual_quantity,
                    ]);
                }
            }
        }
        
        $productWasteSummary = $productWaste->groupBy('variant_id')->map(function($items, $variantId) {
            $first = $items->first();
            $inputWaste = $items->where('type', 'input')->sum('waste_quantity');
            $inputTotal = $items->where('type', 'input')->sum('total_quantity');
            $outputWaste = $items->where('type', 'output')->sum('waste_quantity');
            $outputTotal = $items->where('type', 'output')->sum('total_quantity');
            
            return (object)[
                'variant_name' => $first->variant_name,
                'variant_sku' => $first->variant_sku,
                'category' => $first->category,
                'input_waste' => $inputWaste,
                'input_total' => $inputTotal,
                'input_waste_rate' => $inputTotal > 0 ? ($inputWaste / $inputTotal) * 100 : 0,
                'output_waste' => $outputWaste,
                'output_total' => $outputTotal,
                'output_waste_rate' => $outputTotal > 0 ? ($outputWaste / $outputTotal) * 100 : 0,
                'total_waste' => $inputWaste + $outputWaste,
                'total_rate' => ($inputTotal + $outputTotal) > 0 
                    ? (($inputWaste + $outputWaste) / ($inputTotal + $outputTotal)) * 100 
                    : 0,
                'order_count' => $items->count(),
            ];
        })->sortByDesc('total_waste')->values()->take(10);
        
        // ─── Waste Severity Distribution ────────────────────────────────
        $severityDistribution = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'critical' => 0,
        ];
        
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
        
        // ─── Get Filter Options ──────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        
        $statuses = [
            ['value' => 'all', 'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED, 'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
        ];
        
        $wasteTypes = [
            ['value' => 'all', 'label' => __('pagination.all_waste')],
            ['value' => 'input_waste', 'label' => __('pagination.input_waste')],
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
     * Tracks batches produced and consumed in production orders
     */
    public function batchTracking(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $batchType = $request->get('batch_type', 'all'); // all, produced, consumed
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get Production Orders ──────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($variantId) {
            $query->whereHas('inputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            })->orWhereHas('outputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            });
        }
        
        $orders = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        
        // ─── Get Batch Logs ─────────────────────────────────────────────
        $batchLogs = BatchLog::where('tenant_id', $tenantId)
            ->whereIn('production_order_id', $orders->pluck('id'))
            ->whereIn('type', ['produced', 'consumed'])
            ->orderBy('event_date', 'desc')
            ->get();
        
        // ─── Filter Batch Logs by Type ──────────────────────────────────
        if ($batchType === 'produced') {
            $batchLogs = $batchLogs->where('type', 'produced');
        } elseif ($batchType === 'consumed') {
            $batchLogs = $batchLogs->where('type', 'consumed');
        }
        
        // ─── Batch Summary ──────────────────────────────────────────────
        $producedBatches = $batchLogs->where('type', 'produced');
        $consumedBatches = $batchLogs->where('type', 'consumed');
        
        $totalProducedQty = $producedBatches->sum('quantity_change');
        $totalConsumedQty = abs($consumedBatches->sum('quantity_change'));
        $netBatchQty = $totalProducedQty - $totalConsumedQty;
        
        $totalProducedCost = $producedBatches->sum('total_cost');
        $totalConsumedCost = $consumedBatches->sum('total_cost');
        $netBatchCost = $totalProducedCost - $totalConsumedCost;
        
        $batchSummary = [
            'total_batches' => $batchLogs->count(),
            'produced_batches' => $producedBatches->count(),
            'consumed_batches' => $consumedBatches->count(),
            'total_produced_quantity' => $totalProducedQty,
            'total_consumed_quantity' => $totalConsumedQty,
            'net_batch_quantity' => $netBatchQty,
            'total_produced_cost' => $totalProducedCost,
            'total_consumed_cost' => $totalConsumedCost,
            'net_batch_cost' => $netBatchCost,
            'unique_batch_numbers' => $batchLogs->pluck('batch_number')->unique()->count(),
            'unique_variants' => $batchLogs->pluck('variant_id')->unique()->count(),
        ];
        
        // ─── Batch Logs with Details ────────────────────────────────────
        $batchLogsWithDetails = $batchLogs->map(function($log) {
            $order = ProductionOrder::find($log->production_order_id);
            $variant = ProductVariant::find($log->variant_id);
            
            return (object)[
                'id' => $log->id,
                'batch_number' => $log->batch_number,
                'batch_id' => $log->batch_id,
                'type' => $log->type,
                'type_label' => $log->type === 'produced' ? __('pagination.produced') : __('pagination.consumed'),
                'type_color' => $log->type === 'produced' ? 'success' : 'danger',
                'type_icon' => $log->type === 'produced' ? 'ki-exit' : 'ki-enter',
                'variant_id' => $log->variant_id,
                'variant_name' => $variant ? $variant->name : $log->variant_name,
                'variant_sku' => $variant ? $variant->sku : $log->variant_sku,
                'quantity_change' => $log->quantity_change,
                'quantity_before' => $log->quantity_before,
                'quantity_after' => $log->quantity_after,
                'unit_cost' => $log->unit_cost,
                'total_cost' => $log->total_cost,
                'expiry_date' => $log->expiry_date,
                'event_date' => $log->event_date,
                'production_order_id' => $log->production_order_id,
                'production_number' => $order ? $order->production_number : 'N/A',
                'location_id' => $log->location_id,
                'department_id' => $log->department_id,
                'performed_by' => $log->performedBy ? $log->performedBy->name : 'System',
                'metadata' => $log->metadata,
            ];
        });
        
        // ─── Apply Pagination ────────────────────────────────────────────
        $paginatedBatches = $this->paginateCollection($batchLogsWithDetails, $perPage, 'page');
        
        // ─── Batch by Variant ────────────────────────────────────────────
        $batchByVariant = $batchLogsWithDetails->groupBy('variant_id')->map(function($items, $variantId) {
            $first = $items->first();
            $produced = $items->where('type', 'produced');
            $consumed = $items->where('type', 'consumed');
            
            return (object)[
                'variant_id' => $variantId,
                'variant_name' => $first->variant_name,
                'variant_sku' => $first->variant_sku,
                'produced_count' => $produced->count(),
                'consumed_count' => $consumed->count(),
                'produced_quantity' => $produced->sum('quantity_change'),
                'consumed_quantity' => abs($consumed->sum('quantity_change')),
                'net_quantity' => $produced->sum('quantity_change') - abs($consumed->sum('quantity_change')),
                'produced_cost' => $produced->sum('total_cost'),
                'consumed_cost' => $consumed->sum('total_cost'),
                'net_cost' => $produced->sum('total_cost') - $consumed->sum('total_cost'),
                'unique_batches' => $items->pluck('batch_number')->unique()->count(),
            ];
        })->values();
        
        // ─── Batch by Month ──────────────────────────────────────────────
        $batchByMonth = $batchLogsWithDetails->groupBy(function($log) {
            return $log->event_date ? Carbon::parse($log->event_date)->format('Y-m') : 'unknown';
        })->map(function($items, $month) {
            $produced = $items->where('type', 'produced');
            $consumed = $items->where('type', 'consumed');
            
            return (object)[
                'month' => $month !== 'unknown' ? Carbon::parse($month . '-01')->format('M Y') : 'Unknown',
                'produced_count' => $produced->count(),
                'consumed_count' => $consumed->count(),
                'produced_quantity' => $produced->sum('quantity_change'),
                'consumed_quantity' => abs($consumed->sum('quantity_change')),
                'produced_cost' => $produced->sum('total_cost'),
                'consumed_cost' => $consumed->sum('total_cost'),
            ];
        })->sortKeys()->values();
        
        // ─── Top Batches ──────────────────────────────────────────────────
        $topProducedBatches = $batchLogsWithDetails
            ->where('type', 'produced')
            ->sortByDesc('quantity_change')
            ->take(10)
            ->values();
        
        $topConsumedBatches = $batchLogsWithDetails
            ->where('type', 'consumed')
            ->sortByDesc('quantity_change')
            ->take(10)
            ->values();
        
        // ─── Batch Status Summary ───────────────────────────────────────
        $batchStatus = [
            'active' => $batchLogsWithDetails->where('quantity_after', '>', 0)->count(),
            'depleted' => $batchLogsWithDetails->where('quantity_after', '<=', 0)->count(),
            'expired' => $batchLogsWithDetails->filter(function($log) {
                return $log->expiry_date && Carbon::parse($log->expiry_date)->lt(now());
            })->count(),
        ];
        
        // ─── Get Filter Options ──────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        
        $batchTypes = [
            ['value' => 'all', 'label' => __('pagination.all_batches')],
            ['value' => 'produced', 'label' => __('pagination.produced_batches')],
            ['value' => 'consumed', 'label' => __('pagination.consumed_batches')],
        ];
        
        return view('reports.production.batch-tracking', compact(
            'batchSummary',
            'paginatedBatches',
            'batchLogsWithDetails',
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
            'perPage',
            'isSingleShop'
        ));
    }



}