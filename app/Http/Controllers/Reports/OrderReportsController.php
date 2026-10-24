<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Product, SerialNumber, Order,InventoryTransactions, SingleShopInventoryLog, Invoice };
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Location;
use App\Models\Department;
use App\Models\ProductVariant;
use App\Models\InventoryItems;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use App\Services\PricingResolverService;

class OrderReportsController extends Controller
{
    public function __construct(
        protected PricingResolverService $pricingResolver
    ) {}

    /**
     * @return array{0: float, 1: float, 2: string}  [unitCost, unitSell, source]
     */
    private function resolveLinePricing(\App\Models\OrderItem $item): array
    {
        $r = $this->pricingResolver->resolveLinePricing($item);
        return [$r['unit_cost'], $r['unit_sell'], $r['source']];
    }


    public function summary(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate    = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType    = $request->get('order_type');
        $orderStatus  = $request->get('order_status');
        $perPage      = (int) $request->get('per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.orders.summary', compact(
            'locations', 'departments',
            'startDate', 'endDate',
            'locationId', 'departmentId', 'orderType', 'orderStatus', 'perPage'
        ));
    }

    public function summaryData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate    = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType    = $request->get('order_type');
        $orderStatus  = $request->get('order_status');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        $query = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['location:id,name', 'department:id,name']);

        if ($locationId)   $query->where('location_id', $locationId);
        if ($departmentId) $query->where('department_id', $departmentId);
        if ($orderType)    $query->where('type', $orderType);
        if ($orderStatus)  $query->where('status', $orderStatus);

        $allOrders = $query->get();

        // ─── Summary ────────────────────────────────────────────
        $summary = $this->getOrderSummary($allOrders);

        // ─── Daily breakdown (paginated) ────────────────────────
        $dailyAll = $this->getDailyBreakdown($allOrders)
            ->map(function ($item) {
                $date = Carbon::parse($item['date']);
                $item['day_name']       = $date->format('l');
                $item['is_weekend']     = in_array($item['day_name'], ['Saturday', 'Sunday']);
                $item['formatted_date'] = $date->format('M d, Y');
                return $item;
            })
            ->sortKeysDesc()
            ->values();

        $dailyTotal    = $dailyAll->count();
        $dailyLastPage = max(1, (int) ceil($dailyTotal / $perPage));
        $page          = min($page, $dailyLastPage);
        $dailyOffset   = ($page - 1) * $perPage;

        $dailyPage = $dailyAll->slice($dailyOffset, $perPage)->values();

        $dailyTotals = [
            'order_count'    => (float) $dailyAll->sum('order_count'),
            'daily_total'    => (float) $dailyAll->sum('daily_total'),
            'daily_tax'      => (float) $dailyAll->sum('daily_tax'),
            'daily_discount' => (float) $dailyAll->sum('daily_discount'),
            'daily_average'  => (float) ($dailyAll->avg('daily_average') ?? 0),
        ];

        // ─── Hourly breakdown ───────────────────────────────────
        $hourly = $this->getHourlyBreakdown($allOrders)
            ->map(function ($item) {
                $hour = (int) $item['hour'];
                $item['hour_formatted'] = date('g:00 A', mktime($hour, 0, 0));
                $item['period']         = $this->getPeriodLabel($hour);
                return $item;
            })
            ->sortBy('hour')->values();

        $hourlyTotals = [
            'order_count'   => (float) $hourly->sum('order_count'),
            'hourly_total'  => (float) $hourly->sum('hourly_total'),
            'hourly_average'=> (float) ($hourly->avg('hourly_average') ?? 0),
        ];

        // ─── Type breakdown ─────────────────────────────────────
        $type = $this->getTypeBreakdown($allOrders)
            ->map(function ($item) {
                $item['color'] = $this->getOrderTypeColor($item['type']);
                return $item;
            })
            ->values();

        // ─── Status breakdown ───────────────────────────────────
        $status = $this->getStatusBreakdown($allOrders)
            ->map(function ($item) {
                $item['color'] = $this->getOrderStatusColor($item['status']);
                return $item;
            })
            ->values();

        // ─── Derived percentages for payment/status cards ───────
        $paidPercent      = $summary->total_sales > 0 ? ($summary->total_paid    / $summary->total_sales) * 100 : 0;
        $balancePercent   = $summary->total_sales > 0 ? ($summary->total_balance / $summary->total_sales) * 100 : 0;
        $completedPercent = $summary->total_orders > 0 ? ($summary->completed_orders / $summary->total_orders) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'summary'          => $summary,
                'daily_breakdown'  => $dailyPage,
                'daily_totals'     => $dailyTotals,
                'daily_pagination' => [
                    'current_page' => $page,
                    'last_page'    => $dailyLastPage,
                    'total'        => $dailyTotal,
                    'from'         => $dailyTotal > 0 ? $dailyOffset + 1 : 0,
                    'to'           => min($dailyOffset + $perPage, $dailyTotal),
                    'per_page'     => $perPage,
                ],
                'hourly_breakdown' => $hourly,
                'hourly_totals'    => $hourlyTotals,
                'type_breakdown'   => $type,
                'status_breakdown' => $status,
                'derived' => [
                    'paid_percent'      => round($paidPercent, 2),
                    'balance_percent'   => round($balancePercent, 2),
                    'completed_percent' => round($completedPercent, 2),
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'order_type'    => $orderType,
                    'order_status'  => $orderStatus,
                ],
            ],
        ]);
    }


    /**
     * Profit Analysis Report - Calculate profits on completed orders
     */
    public function profitAnalysis(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType    = $request->get('order_type');
        $minProfit    = $request->get('min_profit');
        $maxProfit    = $request->get('max_profit');
        $perPage      = (int) $request->get('per_page', 25);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.orders.profit-analysis', compact(
            'locations', 'departments',
            'startDate', 'endDate',
            'locationId', 'departmentId', 'orderType', 'minProfit', 'maxProfit', 'perPage'
        ));
    }

    public function profitAnalysisData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType    = $request->get('order_type');
        $minProfit    = $request->get('min_profit');
        $maxProfit    = $request->get('max_profit');
        $perPage      = (int) $request->get('per_page', 25);
        $page         = max(1, (int) $request->get('page', 1));

        // ─── Build orders query ─────────────────────────────────
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->with([
                'items.productVariant:id,name,sku',
                'location:id,name',
                'department:id,name',
                'customer:id,first_name,last_name',
            ]);

        if ($locationId)   $ordersQuery->where('location_id', $locationId);
        if ($departmentId) $ordersQuery->where('department_id', $departmentId);
        if ($orderType && $orderType !== 'all') $ordersQuery->where('type', $orderType);

        $orders = $ordersQuery->get();

        // ─── Build profit rows ──────────────────────────────────
        $profitData = $orders->map(function ($order) {
            $totalRevenue  = (float) $order->total;
            $totalDiscount = (float) ($order->discount_total ?? 0);
            $totalTax      = (float) ($order->tax_total ?? 0);

            $totalCost   = 0.0;
            $itemProfits = [];

            foreach ($order->items as $item) {
                $variant = $item->productVariant;
                if (!$variant) continue;

                [$unitCost, $unitSell, $source] = $this->resolveLinePricing($item);

                $qty        = (float) $item->quantity;
                $itemCost   = $unitCost * $qty;
                $totalCost += $itemCost;

                $itemRevenue = (float) ($item->total_price ?? 0);
                $itemProfit  = $itemRevenue - $itemCost;
                $itemMargin  = $itemRevenue > 0 ? ($itemProfit / $itemRevenue) * 100 : 0;

                $itemProfits[] = [
                    'variant_name'   => $variant->name ?? 'Unknown',
                    'sku'            => $variant->sku  ?? 'N/A',
                    'quantity'       => round($qty, 2),
                    'unit_cost'      => round($unitCost, 2),
                    'unit_price'     => round((float) ($item->unit_price ?? 0), 2),
                    'total_cost'     => round($itemCost, 2),
                    'total_revenue'  => round($itemRevenue, 2),
                    'profit'         => round($itemProfit, 2),
                    'profit_margin'  => round($itemMargin, 2),
                    'pricing_source' => $source,
                ];
            }

            $grossProfit  = $totalRevenue - $totalCost;
            $netProfit    = $grossProfit - $totalDiscount;
            $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

            return [
                'order_id'       => $order->id,
                'order_number'   => $order->order_number ?? (string) $order->id,
                'order_type'     => $order->type ?? 'sale',
                'created_at'     => $order->created_at?->format('Y-m-d'),
                'created_time'   => $order->created_at?->format('H:i A'),
                'location'       => $order->location?->name ?? 'N/A',
                'department'     => $order->department?->name ?? 'N/A',
                'customer'       => $order->customer?->full_name
                                    ?? ($order->customer_name ?? 'Guest'),
                'total_revenue'  => round($totalRevenue, 2),
                'total_cost'     => round($totalCost, 2),
                'total_discount' => round($totalDiscount, 2),
                'total_tax'      => round($totalTax, 2),
                'gross_profit'   => round($grossProfit, 2),
                'net_profit'     => round($netProfit, 2),
                'profit_margin'  => round($profitMargin, 2),
                'item_count'     => count($itemProfits),
                'item_profits'   => $itemProfits,
            ];
        })->sortByDesc('net_profit')->values();

        // ─── Profit range filters ───────────────────────────────
        if ($minProfit !== null && $minProfit !== '' && is_numeric($minProfit)) {
            $profitData = $profitData->filter(fn($o) => $o['net_profit'] >= (float) $minProfit)->values();
        }
        if ($maxProfit !== null && $maxProfit !== '' && is_numeric($maxProfit)) {
            $profitData = $profitData->filter(fn($o) => $o['net_profit'] <= (float) $maxProfit)->values();
        }

        // ─── Summary ────────────────────────────────────────────
        $first = $profitData->first();
        $summary = [
            'total_orders'            => $profitData->count(),
            'total_revenue'           => round($profitData->sum('total_revenue'), 2),
            'total_cost'              => round($profitData->sum('total_cost'), 2),
            'total_discount'          => round($profitData->sum('total_discount'), 2),
            'total_gross_profit'      => round($profitData->sum('gross_profit'), 2),
            'total_net_profit'        => round($profitData->sum('net_profit'), 2),
            'average_net_profit'      => $profitData->count() > 0 ? round($profitData->sum('net_profit') / $profitData->count(), 2) : 0,
            'average_profit_margin'   => $profitData->count() > 0 ? round($profitData->avg('profit_margin'), 2) : 0,
            'max_profit_order'        => $first['order_number'] ?? 'N/A',
            'max_profit_amount'       => $first['net_profit']   ?? 0,
            'profitable_orders'       => $profitData->where('net_profit', '>', 0)->count(),
            'loss_making_orders'      => $profitData->where('net_profit', '<', 0)->count(),
            'break_even_orders'       => $profitData->where('net_profit', '=', 0)->count(),
        ];

        $profitabilityRate = $summary['total_orders'] > 0
            ? ($summary['profitable_orders'] / $summary['total_orders']) * 100
            : 0;

        // ─── By location ────────────────────────────────────────
        $profitByLocation = $profitData->groupBy('location')->map(function ($rows, $loc) {
            return [
                'location'         => $loc,
                'order_count'      => $rows->count(),
                'total_revenue'    => round($rows->sum('total_revenue'), 2),
                'total_cost'       => round($rows->sum('total_cost'), 2),
                'total_net_profit' => round($rows->sum('net_profit'), 2),
                'average_margin'   => round($rows->avg('profit_margin') ?? 0, 2),
            ];
        })->sortByDesc('total_net_profit')->values();

        // ─── By department ──────────────────────────────────────
        $profitByDepartment = $profitData->groupBy('department')->map(function ($rows, $dep) {
            return [
                'department'       => $dep,
                'order_count'      => $rows->count(),
                'total_revenue'    => round($rows->sum('total_revenue'), 2),
                'total_cost'       => round($rows->sum('total_cost'), 2),
                'total_net_profit' => round($rows->sum('net_profit'), 2),
                'average_margin'   => round($rows->avg('profit_margin') ?? 0, 2),
            ];
        })->sortByDesc('total_net_profit')->values();

        // ─── Top / worst products by profit ─────────────────────
        $productProfits = collect();
        foreach ($profitData as $order) {
            foreach ($order['item_profits'] as $item) {
                $key = $item['sku'];
                if ($productProfits->has($key)) {
                    $existing = $productProfits->get($key);
                    $existing['quantity_sold'] += $item['quantity'];
                    $existing['total_revenue'] += $item['total_revenue'];
                    $existing['total_cost']    += $item['total_cost'];
                    $existing['total_profit']  += $item['profit'];
                    $productProfits->put($key, $existing);
                } else {
                    $productProfits->put($key, [
                        'sku'           => $item['sku'],
                        'variant_name'  => $item['variant_name'],
                        'quantity_sold' => $item['quantity'],
                        'total_revenue' => $item['total_revenue'],
                        'total_cost'    => $item['total_cost'],
                        'total_profit'  => $item['profit'],
                        'profit_margin' => $item['profit_margin'],
                    ]);
                }
            }
        }

        $topProducts   = $productProfits->sortByDesc('total_profit')->take(10)->values();
        $worstProducts = $productProfits->sortBy('total_profit')->take(10)->values();

        // ─── Daily profit trend (paginated) ─────────────────────
        $trendAll = $profitData->groupBy('created_at')->map(function ($rows, $date) {
            return [
                'date'             => $date,
                'order_count'      => $rows->count(),
                'total_revenue'    => round($rows->sum('total_revenue'), 2),
                'total_cost'       => round($rows->sum('total_cost'), 2),
                'total_net_profit' => round($rows->sum('net_profit'), 2),
                'average_margin'   => round($rows->avg('profit_margin') ?? 0, 2),
            ];
        })->sortKeys()->values();

        $trendTotal    = $trendAll->count();
        $trendLastPage = max(1, (int) ceil($trendTotal / $perPage));
        $page          = min($page, $trendLastPage);
        $trendOffset   = ($page - 1) * $perPage;
        $trendPage     = $trendAll->slice($trendOffset, $perPage)->values();

        $trendTotals = [
            'order_count'      => (float) $trendAll->sum('order_count'),
            'total_revenue'    => (float) $trendAll->sum('total_revenue'),
            'total_cost'       => (float) $trendAll->sum('total_cost'),
            'total_net_profit' => (float) $trendAll->sum('total_net_profit'),
            'average_margin'   => (float) ($trendAll->avg('average_margin') ?? 0),
        ];

        // ─── Orders table pagination ────────────────────────────
        $ordersTotal    = $profitData->count();
        $ordersLastPage = max(1, (int) ceil($ordersTotal / $perPage));
        $ordersOffset   = ($page - 1) * $perPage;
        $ordersPage     = $profitData->slice($ordersOffset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'            => $summary,
                'profitability_rate' => round($profitabilityRate, 2),
                'by_location'        => $profitByLocation,
                'by_department'      => $profitByDepartment,
                'top_products'       => $topProducts,
                'worst_products'     => $worstProducts,
                'daily_trend'        => $trendPage,
                'daily_totals'       => $trendTotals,
                'daily_pagination'   => [
                    'current_page' => $page,
                    'last_page'    => $trendLastPage,
                    'total'        => $trendTotal,
                    'from'         => $trendTotal > 0 ? $trendOffset + 1 : 0,
                    'to'           => min($trendOffset + $perPage, $trendTotal),
                    'per_page'     => $perPage,
                ],
                'orders'             => $ordersPage,
                'orders_totals'      => [
                    'total_revenue'  => (float) $profitData->sum('total_revenue'),
                    'total_cost'     => (float) $profitData->sum('total_cost'),
                    'total_discount' => (float) $profitData->sum('total_discount'),
                    'gross_profit'   => (float) $profitData->sum('gross_profit'),
                    'net_profit'     => (float) $profitData->sum('net_profit'),
                    'profit_margin'  => (float) ($profitData->avg('profit_margin') ?? 0),
                ],
                'orders_pagination'  => [
                    'current_page' => $page,
                    'last_page'    => $ordersLastPage,
                    'total'        => $ordersTotal,
                    'from'         => $ordersTotal > 0 ? $ordersOffset + 1 : 0,
                    'to'           => min($ordersOffset + $perPage, $ordersTotal),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'order_type'    => $orderType,
                    'min_profit'    => $minProfit,
                    'max_profit'    => $maxProfit,
                ],
            ],
        ]);
    }
        
    // Helper Methods
    private function validateAndFormatDates($startDate, $endDate)
    {
        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Exception $e) {
            $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
            $endDate = Carbon::now()->format('Y-m-d');
        }
        
        return [$startDate, $endDate];
    }
    
    private function getOrderSummary($orders)
    {
        $totalOrders = $orders->count();
        $totalAmount = $orders->sum('total');
        $totalTax = $orders->sum('tax_total');
        $totalDiscount = $orders->sum('discount_total');
        $totalPaid = $orders->sum('paid_amount');
        $totalBalance = $orders->sum('balance_due');
        
        return (object)[
            'total_orders' => $totalOrders,
            'total_sales' => $totalAmount,
            'total_tax' => $totalTax,
            'total_discount' => $totalDiscount,
            'average_order_value' => $totalOrders > 0 ? $totalAmount / $totalOrders : 0,
            'max_order_value' => $orders->max('total') ?? 0,
            'min_order_value' => $orders->min('total') ?? 0,
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'processing_orders' => $orders->where('status', 'processing')->count(),
            'confirmed_orders' => $orders->where('status', 'confirmed')->count(),
        ];
    }
    
    private function getDailyBreakdown($orders)
    {
        return $orders->groupBy(function($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function($dailyOrders, $date) {
            $totalAmount = $dailyOrders->sum('total');
            $totalTax = $dailyOrders->sum('tax_total');
            $totalDiscount = $dailyOrders->sum('discount_total');
            
            return [
                'date' => $date,
                'order_count' => $dailyOrders->count(),
                'daily_total' => $totalAmount,
                'daily_average' => $dailyOrders->count() > 0 ? $totalAmount / $dailyOrders->count() : 0,
                'daily_tax' => $totalTax,
                'daily_discount' => $totalDiscount,
            ];
        })->sortKeys();
    }
    
    private function getHourlyBreakdown($orders)
    {
        return $orders->whereIn('status', ['completed', 'processing', 'confirmed'])
            ->groupBy(function($order) {
                return $order->created_at->hour;
            })
            ->map(function($hourlyOrders, $hour) {
                $totalAmount = $hourlyOrders->sum('total');
                
                return [
                    'hour' => (int)$hour,
                    'order_count' => $hourlyOrders->count(),
                    'hourly_total' => $totalAmount,
                    'hourly_average' => $hourlyOrders->count() > 0 ? $totalAmount / $hourlyOrders->count() : 0,
                ];
            });
    }
    
    private function getTypeBreakdown($orders)
    {
        $totalCount = $orders->count();
        $totalAmount = $orders->sum('total');
        
        return $orders->groupBy('type')
            ->map(function($typeOrders, $type) use ($totalCount, $totalAmount) {
                $count = $typeOrders->count();
                $amount = $typeOrders->sum('total');
                $tax = $typeOrders->sum('tax_total');
                $discount = $typeOrders->sum('discount_total');
                
                return [
                    'type' => $type,
                    'count' => $count,
                    'total_amount' => $amount,
                    'average_amount' => $count > 0 ? $amount / $count : 0,
                    'total_tax' => $tax,
                    'total_discount' => $discount,
                    'percentage_count' => $totalCount > 0 ? ($count / $totalCount) * 100 : 0,
                    'percentage_amount' => $totalAmount > 0 ? ($amount / $totalAmount) * 100 : 0,
                ];
            })
            ->sortByDesc('total_amount');
    }
    
    private function getStatusBreakdown($orders)
    {
        $totalCount = $orders->count();
        $totalAmount = $orders->sum('total');
        
        return $orders->groupBy('status')
            ->map(function($statusOrders, $status) use ($totalCount, $totalAmount) {
                $count = $statusOrders->count();
                $amount = $statusOrders->sum('total');
                $tax = $statusOrders->sum('tax_total');
                $discount = $statusOrders->sum('discount_total');
                
                return [
                    'status' => $status,
                    'count' => $count,
                    'total_amount' => $amount,
                    'average_amount' => $count > 0 ? $amount / $count : 0,
                    'total_tax' => $tax,
                    'total_discount' => $discount,
                    'percentage_count' => $totalCount > 0 ? ($count / $totalCount) * 100 : 0,
                    'percentage_amount' => $totalAmount > 0 ? ($amount / $totalAmount) * 100 : 0,
                ];
            })
            ->sortByDesc('total_amount');
    }
    
    private function getPeriodLabel($hour)
    {
        if ($hour >= 5 && $hour < 12) return 'Morning';
        if ($hour >= 12 && $hour < 17) return 'Afternoon';
        if ($hour >= 17 && $hour < 21) return 'Evening';
        return 'Night';
    }
    
    private function getOrderTypeColor($type)
    {
        $colors = [
            'dine_in' => 'primary',
            'takeaway' => 'warning',
            'delivery' => 'success',
            'online' => 'info',
        ];
        return $colors[$type] ?? 'secondary';
    }
    
    private function getOrderStatusColor($status)
    {
        $colors = [
            'pending' => 'warning',
            'confirmed' => 'primary',
            'processing' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            'refunded' => 'secondary',
        ];
        return $colors[$status] ?? 'secondary';
    }

    /**
     * Sales by Customer Report
     * Shows customer performance metrics including total spent, order count, and loyalty segments
     */
    public function byCustomer(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId    = $request->get('location_id');
        $departmentId  = $request->get('department_id');
        $minSpent      = $request->get('min_spent');
        $maxSpent      = $request->get('max_spent');
        $minOrders     = $request->get('min_orders');
        $maxOrders     = $request->get('max_orders');
        $customerType  = $request->get('customer_type', 'all');
        $segmentFilter = $request->get('segment_filter');
        $perPage       = (int) $request->get('per_page', 25);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.orders.by-customer', compact(
            'locations', 'departments',
            'startDate', 'endDate',
            'locationId', 'departmentId',
            'minSpent', 'maxSpent', 'minOrders', 'maxOrders',
            'customerType', 'segmentFilter', 'perPage'
        ));
    }

    public function byCustomerData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId    = $request->get('location_id');
        $departmentId  = $request->get('department_id');
        $minSpent      = $request->get('min_spent');
        $maxSpent      = $request->get('max_spent');
        $minOrders     = $request->get('min_orders');
        $maxOrders     = $request->get('max_orders');
        $customerType  = $request->get('customer_type', 'all');
        $segmentFilter = $request->get('segment_filter');
        $perPage       = (int) $request->get('per_page', 25);
        $page          = max(1, (int) $request->get('page', 1));

        // ─── Query orders ──────────────────────────────────────
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['customer:id,first_name,last_name,email,phone,city', 'location:id,name', 'department:id,name']);

        if ($customerType === 'registered') {
            $ordersQuery->whereNotNull('customer_id');
        } elseif ($customerType === 'guest') {
            $ordersQuery->whereNull('customer_id')->whereNotNull('customer_name');
        }
        if ($locationId)   $ordersQuery->where('location_id', $locationId);
        if ($departmentId) $ordersQuery->where('department_id', $departmentId);

        $allOrders = $ordersQuery->get();

        // ─── Group by customer ─────────────────────────────────
        $customerSales = $allOrders->groupBy(function ($order) {
            return $order->customer_id ?? 'guest_' . ($order->customer_name ?? 'unknown');
        })->map(function ($customerOrders, $key) {
            $firstOrder = $customerOrders->first();
            $customer   = $firstOrder->customer;
            $isGuest    = is_null($firstOrder->customer_id);

            $sortedOrders = $customerOrders->sortBy('created_at');
            $firstDate    = $sortedOrders->first()->created_at;
            $lastOrder    = $sortedOrders->last();

            $orderCount     = $customerOrders->count();
            $totalSpent     = (float) $customerOrders->sum('total');
            $totalTax       = (float) $customerOrders->sum('tax_total');
            $totalDiscount  = (float) $customerOrders->sum('discount_total');
            $avgOrderValue  = $orderCount > 0 ? $totalSpent / $orderCount : 0;
            $maxOrderValue  = (float) ($customerOrders->max('total') ?? 0);
            $minOrderValue  = (float) ($customerOrders->min('total') ?? 0);
            $daysSinceLast  = $lastOrder->created_at ? Carbon::now()->diffInDays($lastOrder->created_at) : null;

            $segment = $this->getCustomerSegment($orderCount, $totalSpent);

            return [
                'key'                    => (string) $key,
                'id'                     => $isGuest ? null : $firstOrder->customer_id,
                'customer_id'            => $firstOrder->customer_id,
                'is_guest'               => $isGuest,
                'customer_name'          => $isGuest
                                            ? ($firstOrder->customer_name ?? 'Guest')
                                            : ($customer->full_name ?? 'Unknown'),
                'full_name'              => $isGuest
                                            ? ($firstOrder->customer_name ?? 'Guest')
                                            : trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                'email'                  => $isGuest ? null : ($customer->email ?? ''),
                'phone'                  => $isGuest ? null : ($customer->phone ?? ''),
                'city'                   => $isGuest ? null : ($customer->city ?? ''),
                'order_count'            => $orderCount,
                'total_spent'            => round($totalSpent, 2),
                'total_tax'              => round($totalTax, 2),
                'total_discount'         => round($totalDiscount, 2),
                'average_order_value'    => round($avgOrderValue, 2),
                'max_order_value'        => round($maxOrderValue, 2),
                'min_order_value'        => round($minOrderValue, 2),
                'first_order_date'       => $firstDate?->format('Y-m-d'),
                'last_order_date'        => $lastOrder->created_at?->format('Y-m-d'),
                'last_order_amount'      => round((float) ($lastOrder->total ?? 0), 2),
                'clv'                    => round($totalSpent, 2),
                'days_since_last_purchase'=> $daysSinceLast,
                'segment'                => $segment['label'],
                'segment_color'          => $segment['color'],
                'segment_icon'           => $segment['icon'],
            ];
        })->sortByDesc('total_spent')->values();

        // ─── Range / segment filters ───────────────────────────
        if ($minSpent !== null && $minSpent !== '' && is_numeric($minSpent)) {
            $customerSales = $customerSales->filter(fn($c) => $c['total_spent'] >= (float) $minSpent)->values();
        }
        if ($maxSpent !== null && $maxSpent !== '' && is_numeric($maxSpent)) {
            $customerSales = $customerSales->filter(fn($c) => $c['total_spent'] <= (float) $maxSpent)->values();
        }
        if ($minOrders !== null && $minOrders !== '' && is_numeric($minOrders)) {
            $customerSales = $customerSales->filter(fn($c) => $c['order_count'] >= (int) $minOrders)->values();
        }
        if ($maxOrders !== null && $maxOrders !== '' && is_numeric($maxOrders)) {
            $customerSales = $customerSales->filter(fn($c) => $c['order_count'] <= (int) $maxOrders)->values();
        }
        if ($segmentFilter) {
            $customerSales = $customerSales->filter(fn($c) => $c['segment'] === $segmentFilter)->values();
        }

        // ─── Percentages ───────────────────────────────────────
        $totalSpentAll = (float) $customerSales->sum('total_spent');
        $customerSales = $customerSales->map(function ($c) use ($totalSpentAll) {
            $c['percentage'] = $totalSpentAll > 0 ? round(($c['total_spent'] / $totalSpentAll) * 100, 2) : 0;
            return $c;
        })->values();

        // ─── Segments ──────────────────────────────────────────
        $segments = [
            'new'       => $customerSales->where('segment', 'New')->count(),
            'returning' => $customerSales->where('segment', 'Returning')->count(),
            'regular'   => $customerSales->where('segment', 'Regular')->count(),
            'vip'       => $customerSales->where('segment', 'VIP')->count(),
        ];

        // ─── Top 10 for chart ──────────────────────────────────
        $topCustomers = $customerSales->take(10)->values();

        // ─── Top 5 / Bottom 5 ──────────────────────────────────
        $topFive    = $customerSales->take(5)->values();
        $bottomFive = $customerSales->sortBy('total_spent')->take(5)->values();

        // ─── Summary ───────────────────────────────────────────
        $summary = [
            'total_customers'              => $customerSales->count(),
            'total_orders'                 => (int) $customerSales->sum('order_count'),
            'total_revenue'                => round($totalSpentAll, 2),
            'total_tax'                    => round((float) $customerSales->sum('total_tax'), 2),
            'total_discount'               => round((float) $customerSales->sum('total_discount'), 2),
            'average_per_customer'         => $customerSales->count() > 0
                                                ? round($totalSpentAll / $customerSales->count(), 2) : 0,
            'average_orders_per_customer'  => $customerSales->count() > 0
                                                ? round($customerSales->sum('order_count') / $customerSales->count(), 2) : 0,
            'average_order_value'          => $customerSales->sum('order_count') > 0
                                                ? round($totalSpentAll / $customerSales->sum('order_count'), 2) : 0,
            'registered_customers'         => $customerSales->where('is_guest', false)->count(),
            'guest_customers'              => $customerSales->where('is_guest', true)->count(),
        ];

        // ─── Pagination ────────────────────────────────────────
        $total    = $customerSales->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageRows = $customerSales->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'        => $summary,
                'segments'       => $segments,
                'top_customers'  => $topCustomers,
                'top_five'       => $topFive,
                'bottom_five'    => $bottomFive,
                'customers'      => $pageRows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'customer_type' => $customerType,
                    'segment_filter'=> $segmentFilter,
                    'min_spent'     => $minSpent,
                    'max_spent'     => $maxSpent,
                    'min_orders'    => $minOrders,
                    'max_orders'    => $maxOrders,
                ],
            ],
        ]);
    }

    public function byCustomerOrders(Request $request, $customerKey)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');

        $query = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['location:id,name', 'department:id,name', 'items.productVariant:id,name,sku']);

        if ($locationId)   $query->where('location_id', $locationId);
        if ($departmentId) $query->where('department_id', $departmentId);

        // customerKey: numeric id OR 'guest_<name>'
        if (is_numeric($customerKey)) {
            $query->where('customer_id', (int) $customerKey);
        } else {
            $guestName = preg_replace('/^guest_/', '', $customerKey);
            $query->whereNull('customer_id')->where('customer_name', $guestName);
        }

        $orders = $query->orderByDesc('created_at')->get();

        $rows = $orders->map(function ($o) {
            return [
                'id'           => $o->id,
                'order_number' => $o->order_number ?? (string) $o->id,
                'created_at'   => $o->created_at?->format('Y-m-d'),
                'created_time' => $o->created_at?->format('H:i A'),
                'status'       => $o->status,
                'status_color' => $this->getOrderStatusColor($o->status),
                'location'     => $o->location?->name ?? '—',
                'department'   => $o->department?->name ?? '—',
                'items_count'  => $o->items->count(),
                'subtotal'     => round((float) ($o->subtotal ?? 0), 2),
                'tax_total'    => round((float) ($o->tax_total ?? 0), 2),
                'discount'     => round((float) ($o->discount_total ?? 0), 2),
                'total'        => round((float) $o->total, 2),
                'paid'         => round((float) ($o->paid_amount ?? 0), 2),
                'balance'      => round((float) ($o->balance_due ?? 0), 2),
            ];
        });

        $totalSpent = (float) $orders->sum('total');

        return response()->json([
            'success' => true,
            'data' => [
                'customer_key' => (string) $customerKey,
                'order_count'  => $orders->count(),
                'total_spent'  => round($totalSpent, 2),
                'orders'       => $rows,
            ],
        ]);
    }
        
    /**
     * Get customer segment based on order count and total spent
     */
    private function getCustomerSegment(int $orderCount, float $totalSpent): array
    {
        if ($orderCount == 1) {
            return ['label' => 'New', 'color' => 'info', 'icon' => '🌟'];
        }
        
        if ($orderCount >= 2 && $orderCount <= 5) {
            return ['label' => 'Returning', 'color' => 'primary', 'icon' => '🔄'];
        }
        
        if ($orderCount > 5 && $orderCount <= 20 && $totalSpent >= 1000) {
            return ['label' => 'Regular', 'color' => 'success', 'icon' => '⭐'];
        }
        
        if ($orderCount > 20 && $totalSpent >= 5000) {
            return ['label' => 'VIP', 'color' => 'warning', 'icon' => '👑'];
        }
        
        if ($orderCount > 5) {
            return ['label' => 'Regular', 'color' => 'success', 'icon' => '⭐'];
        }
        
        return ['label' => 'Returning', 'color' => 'primary', 'icon' => '🔄'];
    }
    


    /**
     * Sales by Product/Variant Report
     * Shows product performance metrics including revenue, quantity sold, velocity, and profitability
     */
    public function byProduct(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId     = $request->get('location_id');
        $departmentId   = $request->get('department_id');
        $minQuantity    = $request->get('min_quantity');
        $maxQuantity    = $request->get('max_quantity');
        $minRevenue     = $request->get('min_revenue');
        $maxRevenue     = $request->get('max_revenue');
        $minProfit      = $request->get('min_profit');
        $velocityFilter = $request->get('velocity_filter');
        $perPage        = (int) $request->get('per_page', 25);

        $isSingleShop = tenant_is_single_shop($tenantId);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.orders.by-product', compact(
            'locations', 'departments',
            'startDate', 'endDate',
            'locationId', 'departmentId',
            'minQuantity', 'maxQuantity',
            'minRevenue', 'maxRevenue',
            'minProfit', 'velocityFilter', 'perPage', 'isSingleShop'
        ));
    }
   
    public function byProductData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId     = $request->get('location_id');
        $departmentId   = $request->get('department_id');
        $minQuantity    = $request->get('min_quantity');
        $maxQuantity    = $request->get('max_quantity');
        $minRevenue     = $request->get('min_revenue');
        $maxRevenue     = $request->get('max_revenue');
        $minProfit      = $request->get('min_profit');
        $velocityFilter = $request->get('velocity_filter');
        $perPage        = (int) $request->get('per_page', 25);
        $page           = max(1, (int) $request->get('page', 1));

        $daysInPeriod = $this->getDaysInPeriod($startDate, $endDate);

        // ─── Build order items ────────────────────────────────
        $query = OrderItem::with([
                'order:id,tenant_id,status,created_at,location_id,department_id',
                'productVariant:id,name,sku,image_url,product_id',
                'productVariant.product:id,name,inventory_strategy',
            ])
            ->whereHas('order', function ($q) use ($tenantId, $startDate, $endDate, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                if ($locationId)   $q->where('location_id', $locationId);
                if ($departmentId) $q->where('department_id', $departmentId);
            });

        $orderItems = $query->get();
        $variantIds = $orderItems->pluck('variant_id')->unique()->filter()->values()->toArray();

        $variants = ProductVariant::whereIn('id', $variantIds)
            ->with(['product:id,name,inventory_strategy'])
            ->get()
            ->keyBy('id');

        $variantStockMap = $this->getVariantStockMap($variantIds, $tenantId, $isSingleShop, $locationId, $departmentId);

        // ─── Group by variant ─────────────────────────────────
        $productsRaw = $orderItems->groupBy('variant_id')->map(function ($items, $variantId) use ($variants, $variantStockMap) {
            $variant = $variants[$variantId] ?? null;
            if (!$variant) return null;

            $totalQuantity   = (float) $items->sum('quantity');
            $totalRevenue    = (float) $items->sum('total_price');
            $totalTax        = (float) $items->sum('tax_amount');
            $totalDiscount   = (float) $items->sum('discount');
            $orderCount      = $items->pluck('order_id')->unique()->count();
            $avgSellingPrice = $totalQuantity > 0 ? $totalRevenue / $totalQuantity : 0;

            $lastSold = $items->sortByDesc(fn($i) => $i->order?->created_at)->first();
            $currentStock = $variantStockMap[$variantId] ?? 0;

            $totalCostFromLogs = 0.0;
            $totalSellFromLogs = 0.0;
            foreach ($items as $item) {
                [$unitCost, $unitSell] = $this->resolveLinePricing($item);
                $totalCostFromLogs += $unitCost * $item->quantity;
                $totalSellFromLogs += $unitSell * $item->quantity;
            }
            $totalRevenueFromLogs = $totalSellFromLogs > 0 ? $totalSellFromLogs : $totalRevenue;

            $totalProfit   = $totalRevenue - $totalCostFromLogs;
            $avgUnitCost   = $totalQuantity > 0 ? $totalCostFromLogs / $totalQuantity : 0;
            $avgUnitSell   = $totalQuantity > 0 ? $totalRevenueFromLogs / $totalQuantity : 0;
            $profitPerUnit = $avgUnitSell - $avgUnitCost;
            $profitMargin  = $avgUnitSell > 0 ? ($profitPerUnit / $avgUnitSell) * 100 : 0;

            return [
                'variant_id'             => $variantId,
                'sku'                    => $variant->sku  ?? 'N/A',
                'variant_name'           => $variant->name ?? 'Unknown',
                'image_url'              => $variant->image_url ?? null,
                'product_name'           => $variant->product?->name ?? $variant->name ?? 'Unknown',
                'inventory_strategy'     => $variant->product?->inventory_strategy ?? 'quantity',
                'has_serials'            => ($variant->product?->inventory_strategy === 'serial'),
                'has_recipe'             => ($variant->product?->inventory_strategy === 'recipe'),
                'current_price'          => round($avgUnitSell, 2),
                'discount_price'         => round($avgUnitSell, 2),
                'current_stock'          => $currentStock,
                'unit_cost'              => round($avgUnitCost, 2),
                'profit_per_unit'        => round($profitPerUnit, 2),
                'profit_margin'          => round($profitMargin, 2),
                'total_profit'           => round($totalProfit, 2),
                'total_quantity_sold'    => round($totalQuantity, 2),
                'total_revenue'          => round($totalRevenue, 2),
                'total_tax'              => round($totalTax, 2),
                'total_discount'         => round($totalDiscount, 2),
                'average_selling_price'  => round($avgSellingPrice, 2),
                'order_count'            => $orderCount,
                'last_sold_date'         => $lastSold?->order?->created_at?->format('Y-m-d'),
            ];
        })->filter()->values();

        // ─── Filters ──────────────────────────────────────────
        $products = $productsRaw->filter(function ($p) use ($minQuantity, $maxQuantity, $minRevenue, $maxRevenue, $minProfit, $velocityFilter, $daysInPeriod) {
            if ($minQuantity && $p['total_quantity_sold'] < (int) $minQuantity) return false;
            if ($maxQuantity && $p['total_quantity_sold'] > (int) $maxQuantity) return false;
            if ($minRevenue && $p['total_revenue'] < (float) $minRevenue) return false;
            if ($maxRevenue && $p['total_revenue'] > (float) $maxRevenue) return false;
            if ($minProfit && $p['total_profit'] < (float) $minProfit) return false;
            if ($velocityFilter) {
                $dailyRate = $p['total_quantity_sold'] / max($daysInPeriod, 1);
                if ($this->getVelocityCategory($dailyRate) !== $velocityFilter) return false;
            }
            return true;
        })->sortByDesc('total_revenue')->values();

        // ─── Velocity ─────────────────────────────────────────
        $products = $products->map(function ($p) use ($daysInPeriod) {
            $dailyRate    = $p['total_quantity_sold'] / max($daysInPeriod, 1);
            $dailyRevenue = $p['total_revenue'] / max($daysInPeriod, 1);
            $velocity     = $this->getVelocityData($dailyRate);

            $p['daily_sales_rate']   = round($dailyRate, 2);
            $p['daily_revenue_rate'] = round($dailyRevenue, 2);
            $p['velocity_category']  = $velocity['category'];
            $p['velocity_color']     = $velocity['color'];
            $p['velocity_icon']      = $velocity['icon'];
            $p['stock_coverage_days']= $dailyRate > 0 ? round($p['current_stock'] / $dailyRate, 1) : 0;
            return $p;
        })->values();

        // ─── Summary ──────────────────────────────────────────
        $totalRevenue  = (float) $products->sum('total_revenue');
        $totalQuantity = (float) $products->sum('total_quantity_sold');
        $totalProfit   = (float) $products->sum('total_profit');
        $topProduct    = $products->first();

        $summary = [
            'total_products'       => $products->count(),
            'total_revenue'        => round($totalRevenue, 2),
            'total_quantity'       => round($totalQuantity, 2),
            'total_profit'         => round($totalProfit, 2),
            'avg_profit_margin'    => round($products->avg('profit_margin') ?? 0, 2),
            'avg_daily_sales'      => $daysInPeriod > 0 ? round($totalQuantity / $daysInPeriod, 2) : 0,
            'total_stock'          => (int) $products->sum('current_stock'),
            'top_product'          => $topProduct['variant_name']  ?? 'N/A',
            'top_revenue'          => $topProduct['total_revenue'] ?? 0,
            'fast_movers'          => $products->where('velocity_category', 'Fast Mover')->count(),
            'medium_movers'        => $products->where('velocity_category', 'Medium Mover')->count(),
            'slow_movers'          => $products->where('velocity_category', 'Slow Mover')->count(),
            'is_single_shop'       => $isSingleShop,
        ];

        $velocityBreakdown = [
            'Fast Mover'   => $products->where('velocity_category', 'Fast Mover')->count(),
            'Medium Mover' => $products->where('velocity_category', 'Medium Mover')->count(),
            'Slow Mover'   => $products->where('velocity_category', 'Slow Mover')->count(),
        ];

        // ─── Top 10 / Top 5 / Bottom 5 ────────────────────────
        $topProducts    = $products->take(10)->values();
        $topFive        = $products->take(5)->values();
        $bottomFive     = $products->sortBy('total_revenue')->take(5)->values();

        // ─── Percentages ──────────────────────────────────────
        $productsWithPct = $products->map(function ($p) use ($totalRevenue) {
            $p['percentage'] = $totalRevenue > 0 ? round(($p['total_revenue'] / $totalRevenue) * 100, 2) : 0;
            return $p;
        })->values();

        // ─── Pagination ───────────────────────────────────────
        $total    = $productsWithPct->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageRows = $productsWithPct->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'           => $summary,
                'velocity_breakdown'=> $velocityBreakdown,
                'top_products'      => $topProducts,
                'top_five'          => $topFive,
                'bottom_five'       => $bottomFive,
                'products'          => $pageRows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'      => $startDate,
                    'end_date'        => $endDate,
                    'days_in_period'  => $daysInPeriod,
                    'is_single_shop'  => $isSingleShop,
                ],
            ],
        ]);
    }


    /**
     * Get variant stock map based on shop type
     * 
     * @param array $variantIds
     * @param int $tenantId
     * @param bool $isSingleShop
     * @param int|null $locationId
     * @param int|null $departmentId
     * @return array [variant_id => stock_quantity]
     */
    private function getVariantStockMap(array $variantIds, int $tenantId, bool $isSingleShop, $locationId = null, $departmentId = null): array
    {
        $stockMap = [];
        
        if (empty($variantIds)) {
            return $stockMap;
        }
        
        if ($isSingleShop) {
            // ─── SINGLE SHOP: Get stock from ProductVariant directly ──
            $variants = ProductVariant::whereIn('id', $variantIds)
                ->where('tenant_id', $tenantId)
                ->get(['id', 'overal_quantity_at_hand']);
            
            foreach ($variants as $variant) {
                $stockMap[$variant->id] = (int)($variant->overal_quantity_at_hand ?? 0);
            }
            
        } else {
            // ─── MULTI-SHOP: quantity_allocated is authoritative ────────
            $inventoryQuery = InventoryItems::whereIn('variant_id', $variantIds)
                ->where('tenant_id', $tenantId);

            if ($locationId)   $inventoryQuery->where('location_id', $locationId);
            if ($departmentId) $inventoryQuery->where('department_id', $departmentId);

            $inventoryRecords = $inventoryQuery->get();

            foreach ($inventoryRecords as $record) {
                if (!isset($stockMap[$record->variant_id])) {
                    $stockMap[$record->variant_id] = 0;
                }
                $stockMap[$record->variant_id] += (int) ($record->quantity_allocated ?? 0);
            }

            foreach ($variantIds as $variantId) {
                if (!isset($stockMap[$variantId])) {
                    $stockMap[$variantId] = 0;
                }
            }
        }
        
        return $stockMap;
    }
    
    /**
     * Get velocity category based on daily sales rate
     */
    private function getVelocityCategory(float $dailyRate): string
    {
        if ($dailyRate >= 5) return 'Fast Mover';
        if ($dailyRate >= 1) return 'Medium Mover';
        return 'Slow Mover';
    }
    
    /**
     * Get velocity data including color and icon
     */
    private function getVelocityData(float $dailyRate): array
    {
        if ($dailyRate >= 5) {
            return ['category' => 'Fast Mover', 'color' => 'success', 'icon' => 'rocket'];
        }
        if ($dailyRate >= 1) {
            return ['category' => 'Medium Mover', 'color' => 'warning', 'icon' => 'truck'];
        }
        return ['category' => 'Slow Mover', 'color' => 'danger', 'icon' => 'clock'];
    }
    
    /**
     * Get days in period
     */
    private function getDaysInPeriod(string $startDate, string $endDate): int
    {
        return Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
    }
    

    /**
     * Payment Method Analysis Report
     * Shows payment method performance including transaction counts, amounts, and success rates
     */
    public function byPaymentMethod(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId           = $request->get('location_id');
        $departmentId         = $request->get('department_id');
        $paymentType          = $request->get('payment_type');
        $paymentStatus        = $request->get('payment_status', 'all');
        $paymentMethodStatus  = $request->get('payment_method_status', 'all');
        $minAmount            = $request->get('min_amount');
        $maxAmount            = $request->get('max_amount');
        $perPage              = (int) $request->get('per_page', 25);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        $paymentTypes = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)->distinct()->pluck('type')->filter()->values();

        return view('reports.orders.by-payment-method', compact(
            'locations', 'departments', 'paymentTypes',
            'startDate', 'endDate',
            'locationId', 'departmentId',
            'paymentType', 'paymentStatus', 'paymentMethodStatus',
            'minAmount', 'maxAmount', 'perPage'
        ));
    }

    public function byPaymentMethodData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId          = $request->get('location_id');
        $departmentId        = $request->get('department_id');
        $paymentType         = $request->get('payment_type');
        $paymentStatus       = $request->get('payment_status', 'all');
        $paymentMethodStatus = $request->get('payment_method_status', 'all');
        $minAmount           = $request->get('min_amount');
        $maxAmount           = $request->get('max_amount');
        $perPage             = (int) $request->get('per_page', 25);
        $page                = max(1, (int) $request->get('page', 1));

        $query = OrderPayment::with([
                'order:id,tenant_id,order_number,location_id,department_id',
                'paymentMethod:id,name,type,is_active',
            ])
            ->whereHas('order', function ($q) use ($tenantId, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId);
                if ($locationId)   $q->where('location_id', $locationId);
                if ($departmentId) $q->where('department_id', $departmentId);
            })
            ->whereBetween('processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($paymentStatus !== 'all') {
            $query->where('status', $paymentStatus);
        } else {
            $query->whereIn('status', ['completed', 'failed']);
        }
        if ($paymentType && $paymentType !== 'all') {
            $query->whereHas('paymentMethod', fn($q) => $q->where('type', $paymentType));
        }
        if ($paymentMethodStatus !== 'all') {
            $isActive = $paymentMethodStatus === 'active';
            $query->whereHas('paymentMethod', fn($q) => $q->where('is_active', $isActive));
        }

        $allPayments      = $query->get();
        $completedPayments= $allPayments->where('status', 'completed');
        $failedPayments   = $allPayments->where('status', 'failed');

        // ─── Group by payment method ────────────────────────────
        $methods = $completedPayments->groupBy('payment_method_id')->map(function ($payments, $methodId) use ($failedPayments) {
            $method = $payments->first()->paymentMethod;

            $totalAmount    = (float) $payments->sum('amount');
            $txnCount       = $payments->count();
            $avgTxn         = $txnCount > 0 ? $totalAmount / $txnCount : 0;
            $lastTxn        = $payments->sortByDesc('processed_at')->first();

            $methodFailed   = $failedPayments->where('payment_method_id', $methodId);
            $failedCount    = $methodFailed->count();
            $failedAmount   = (float) $methodFailed->sum('amount');
            $failureRate    = ($txnCount + $failedCount) > 0
                                ? ($failedCount / ($txnCount + $failedCount)) * 100 : 0;

            return [
                'id'                   => $methodId,
                'method_name'          => $method?->name ?? 'Unknown',
                'method_type'          => $method?->type ?? 'unknown',
                'is_active'            => (bool) ($method?->is_active ?? true),
                'transaction_count'    => $txnCount,
                'total_amount'         => round($totalAmount, 2),
                'average_transaction'  => round($avgTxn, 2),
                'largest_transaction'  => round((float) ($payments->max('amount') ?? 0), 2),
                'smallest_transaction' => round((float) ($payments->min('amount') ?? 0), 2),
                'last_transaction_date'=> $lastTxn?->processed_at?->format('Y-m-d H:i'),
                'failed_count'         => $failedCount,
                'failed_amount'        => round($failedAmount, 2),
                'failure_rate'         => round($failureRate, 2),
                'success_rate'         => round(100 - $failureRate, 2),
            ];
        })->sortByDesc('total_amount')->values();

        // ─── Amount filter ──────────────────────────────────────
        if ($minAmount !== null && $minAmount !== '' && is_numeric($minAmount)) {
            $methods = $methods->filter(fn($m) => $m['total_amount'] >= (float) $minAmount)->values();
        }
        if ($maxAmount !== null && $maxAmount !== '' && is_numeric($maxAmount)) {
            $methods = $methods->filter(fn($m) => $m['total_amount'] <= (float) $maxAmount)->values();
        }

        // ─── Percentages ────────────────────────────────────────
        $totalAmountAll = (float) $methods->sum('total_amount');
        $methods = $methods->map(function ($m) use ($totalAmountAll) {
            $m['share'] = $totalAmountAll > 0 ? round(($m['total_amount'] / $totalAmountAll) * 100, 2) : 0;
            return $m;
        })->values();

        // ─── Trends (by date × type) ────────────────────────────
        $trendsRaw = $completedPayments->groupBy(fn($p) => $p->processed_at->format('Y-m-d'))
            ->flatMap(function ($daily, $date) {
                return $daily->groupBy(fn($p) => $p->paymentMethod?->type ?? 'unknown')
                    ->map(fn($tp, $type) => [
                        'date'        => $date,
                        'type'        => $type,
                        'daily_count' => $tp->count(),
                        'daily_total' => round((float) $tp->sum('amount'), 2),
                    ])->values();
            });

        $trends = $trendsRaw->groupBy('type')->map(fn($g) => $g->values());

        // ─── Failed transactions per method ─────────────────────
        $failed = $failedPayments->groupBy('payment_method_id')->map(function ($failures, $methodId) {
            $method = $failures->first()->paymentMethod;
            $totalForMethod = OrderPayment::where('payment_method_id', $methodId)
                ->whereIn('status', ['completed', 'failed'])->count();
            return [
                'id'             => $methodId,
                'name'           => $method?->name ?? 'Unknown',
                'type'           => $method?->type ?? 'unknown',
                'failed_count'   => $failures->count(),
                'failed_amount'  => round((float) $failures->sum('amount'), 2),
                'total_transactions' => $totalForMethod,
                'failure_rate'   => $totalForMethod > 0 ? round(($failures->count() / $totalForMethod) * 100, 2) : 100,
            ];
        })->sortByDesc('failed_count')->values();

        // ─── Summary ────────────────────────────────────────────
        $totalCompletedTxns = (int) $methods->sum('transaction_count');
        $totalFailedTxns    = (int) $failed->sum('failed_count');
        $totalFailedAmount  = (float) $failed->sum('failed_amount');
        $overallFailureRate = ($totalCompletedTxns + $totalFailedTxns) > 0
                                ? ($totalFailedTxns / ($totalCompletedTxns + $totalFailedTxns)) * 100 : 0;
        $topMethod = $methods->first();

        $summary = [
            'total_methods'            => $methods->count(),
            'total_transactions'       => $totalCompletedTxns,
            'total_amount'             => round($totalAmountAll, 2),
            'avg_transaction'          => $totalCompletedTxns > 0 ? round($totalAmountAll / $totalCompletedTxns, 2) : 0,
            'most_used_method'         => $topMethod['method_name'] ?? 'N/A',
            'most_used_amount'         => $topMethod['total_amount'] ?? 0,
            'total_failed_transactions'=> $totalFailedTxns,
            'total_failed_amount'      => round($totalFailedAmount, 2),
            'overall_failure_rate'     => round($overallFailureRate, 2),
            'success_rate'             => round(100 - $overallFailureRate, 2),
        ];

        // ─── Pagination ─────────────────────────────────────────
        $total    = $methods->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageRows = $methods->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'            => $summary,
                'methods'            => $pageRows,
                'failed_transactions'=> $failed,
                'trends'             => $trends,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'  => $startDate,
                    'end_date'    => $endDate,
                ],
            ],
        ]);
    }
  
    public function byPaymentMethodTransactions(Request $request, $methodId)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $payments = OrderPayment::with([
                'order:id,order_number',
                'paymentMethod:id,name,type',
                'processor:id,first_name,last_name',
            ])
            ->whereHas('order', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('payment_method_id', $methodId)
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderByDesc('processed_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'method_id'    => (int) $methodId,
                'total_amount' => round((float) $payments->sum('amount'), 2),
                'count'        => $payments->count(),
                'transactions' => $payments->map(fn($p) => [
                    'id'             => $p->id,
                    'amount'         => round((float) $p->amount, 2),
                    'status'         => $p->status,
                    'order_number'   => $p->order?->order_number,
                    'transaction_id' => $p->transaction_id,
                    'processed_at'   => $p->processed_at?->format('Y-m-d H:i'),
                    'processor'      => trim(($p->processor?->first_name ?? '') . ' ' . ($p->processor?->last_name ?? '')) ?: null,
                    'recorded_via'   => $p->recorded_via,
                ]),
            ],
        ]);
    }

    public function byPaymentMethodFailedTransactions(Request $request, $methodId)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $payments = OrderPayment::with(['order:id,order_number', 'paymentMethod:id,name,type'])
            ->whereHas('order', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('payment_method_id', $methodId)
            ->where('status', 'failed')
            ->whereBetween('processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderByDesc('processed_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'method_id'      => (int) $methodId,
                'total_amount'   => round((float) $payments->sum('amount'), 2),
                'count'          => $payments->count(),
                'transactions'   => $payments->map(fn($p) => [
                    'id'             => $p->id,
                    'amount'         => round((float) $p->amount, 2),
                    'order_number'   => $p->order?->order_number,
                    'transaction_id' => $p->transaction_id,
                    'processed_at'   => $p->processed_at?->format('Y-m-d H:i'),
                    'notes'          => $p->notes,
                ]),
            ],
        ]);
    }


    /**
     * Get total transactions for a payment method
     */
    private function getTotalTransactionsForMethod($methodId): int
    {
        return OrderPayment::where('payment_method_id', $methodId)
            ->whereIn('status', ['completed', 'failed'])
            ->count();
    }
    


        
    // Employee Performance Report
    public function byEmployee(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $employeeId   = $request->get('employee_id');
        $minSales     = $request->get('min_sales');
        $maxSales     = $request->get('max_sales');
        $perPage      = (int) $request->get('per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        $employeesList = User::where('tenant_id', $tenantId)
            ->where('status', 'active')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('reports.orders.by-employee', compact(
            'locations', 'departments', 'employeesList',
            'startDate', 'endDate',
            'locationId', 'departmentId',
            'employeeId', 'minSales', 'maxSales', 'perPage'
        ));
    }

    public function byEmployeeData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $employeeId   = $request->get('employee_id');
        $minSales     = $request->get('min_sales');
        $maxSales     = $request->get('max_sales');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        // ─── Employees query ────────────────────────────────────
        $employeesQuery = User::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with(['orders' => function ($q) use ($startDate, $endDate, $locationId, $departmentId, $tenantId) {
                $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->where('tenant_id', $tenantId);
                if ($locationId)   $q->where('location_id', $locationId);
                if ($departmentId) $q->where('department_id', $departmentId);
            }]);

        if ($employeeId) $employeesQuery->where('id', $employeeId);

        $employees = $employeesQuery->get();

        // ─── Build performance rows ─────────────────────────────
        $rows = $employees->map(function ($employee) use ($daysInPeriod) {
            $orders          = $employee->orders;
            $orderCount      = $orders->count();
            $totalSales      = (float) $orders->sum('total');
            $totalTax        = (float) $orders->sum('tax_total');
            $totalDiscount   = (float) $orders->sum('discount_total');
            $avgOrderValue   = $orderCount > 0 ? $totalSales / $orderCount : 0;
            $largestSale     = (float) ($orders->max('total') ?? 0);
            $smallestSale    = (float) ($orders->min('total') ?? 0);
            $uniqueCustomers = $orders->unique('customer_id')->count();
            $lastSale        = $orders->max('created_at');

            $ordersPerDay = $orderCount / max($daysInPeriod, 1);
            $salesPerDay  = $totalSales / max($daysInPeriod, 1);
            $rating       = $this->calculatePerformanceRating($salesPerDay);

            return [
                'id'                   => $employee->id,
                'first_name'           => $employee->first_name,
                'last_name'            => $employee->last_name,
                'email'                => $employee->email,
                'order_count'          => $orderCount,
                'total_sales'          => round($totalSales, 2),
                'total_tax_collected'  => round($totalTax, 2),
                'total_discount_given' => round($totalDiscount, 2),
                'average_order_value'  => round($avgOrderValue, 2),
                'largest_sale'         => round($largestSale, 2),
                'smallest_sale'        => round($smallestSale, 2),
                'unique_customers'     => $uniqueCustomers,
                'last_sale_date'       => $lastSale?->format('Y-m-d'),
                'orders_per_day'       => round($ordersPerDay, 2),
                'sales_per_day'        => round($salesPerDay, 2),
                'performance_rating'   => $rating['rating'],
                'rating_color'         => $rating['color'],
            ];
        })->filter(fn($r) => $r['order_count'] > 0);

        // ─── Sales filters ──────────────────────────────────────
        if ($minSales !== null && $minSales !== '' && is_numeric($minSales)) {
            $rows = $rows->filter(fn($r) => $r['total_sales'] >= (float) $minSales)->values();
        }
        if ($maxSales !== null && $maxSales !== '' && is_numeric($maxSales)) {
            $rows = $rows->filter(fn($r) => $r['total_sales'] <= (float) $maxSales)->values();
        }
        $rows = $rows->sortByDesc('total_sales')->values();

        // ─── Rating distribution ────────────────────────────────
        $ratingCounts = ['Excellent' => 0, 'Good' => 0, 'Average' => 0, 'Needs Improvement' => 0];
        foreach ($rows as $r) {
            if (isset($ratingCounts[$r['performance_rating']])) $ratingCounts[$r['performance_rating']]++;
        }

        // ─── Percentages ────────────────────────────────────────
        $totalSalesAll = (float) $rows->sum('total_sales');
        $rows = $rows->map(function ($r) use ($totalSalesAll) {
            $r['percentage'] = $totalSalesAll > 0 ? round(($r['total_sales'] / $totalSalesAll) * 100, 2) : 0;
            return $r;
        })->values();

        // ─── Top / bottom 5 ─────────────────────────────────────
        $topFive    = $rows->take(5)->values();
        $bottomFive = $rows->sortBy('total_sales')->take(5)->values();

        // ─── Summary ────────────────────────────────────────────
        $totalOrders = (int) $rows->sum('order_count');
        $topEmployee = $rows->first();

        $summary = [
            'total_employees'  => $rows->count(),
            'total_sales'      => round($totalSalesAll, 2),
            'total_orders'     => $totalOrders,
            'avg_sales_per_day'=> $daysInPeriod > 0 ? round($totalSalesAll / $daysInPeriod, 2) : 0,
            'avg_order_value'  => $totalOrders > 0 ? round($totalSalesAll / $totalOrders, 2) : 0,
            'top_employee'     => $topEmployee
                ? trim(($topEmployee['first_name'] ?? '') . ' ' . ($topEmployee['last_name'] ?? ''))
                : null,
            'top_employee_sales' => $topEmployee['total_sales'] ?? 0,
            'total_tax'          => round((float) $rows->sum('total_tax_collected'), 2),
            'total_discount'     => round((float) $rows->sum('total_discount_given'), 2),
        ];

        // ─── Chart: top 10 ──────────────────────────────────────
        $chart = $rows->take(10)->map(fn($r) => [
            'name'       => trim($r['first_name'] . ' ' . substr($r['last_name'] ?? '', 0, 1) . '.'),
            'sales'      => $r['total_sales'],
            'orders'     => $r['order_count'],
        ])->values();

        // ─── Pagination ─────────────────────────────────────────
        $total    = $rows->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageRows = $rows->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'      => $summary,
                'rating_counts'=> $ratingCounts,
                'chart'        => $chart,
                'top_five'     => $topFive,
                'bottom_five'  => $bottomFive,
                'employees'    => $pageRows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'days_in_period'=> $daysInPeriod,
                ],
            ],
        ]);
    }

    public function byEmployeeOrders(Request $request, $employeeId)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');

        $employee = User::where('tenant_id', $tenantId)->where('id', $employeeId)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $query = Order::where('tenant_id', $tenantId)
            ->where('created_by', $employeeId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['location:id,name', 'department:id,name', 'customer:id,first_name,last_name']);

        if ($locationId)   $query->where('location_id', $locationId);
        if ($departmentId) $query->where('department_id', $departmentId);

        $orders = $query->orderByDesc('created_at')->get();

        $rows = $orders->map(fn($o) => [
            'id'           => $o->id,
            'order_number' => $o->order_number ?? (string) $o->id,
            'created_at'   => $o->created_at?->format('Y-m-d'),
            'created_time' => $o->created_at?->format('H:i A'),
            'status'       => $o->status,
            'status_color' => $this->getOrderStatusColor($o->status),
            'customer'     => $o->customer
                ? trim(($o->customer->first_name ?? '') . ' ' . ($o->customer->last_name ?? ''))
                : ($o->customer_name ?? 'Guest'),
            'location'     => $o->location?->name ?? '—',
            'department'   => $o->department?->name ?? '—',
            'total'        => round((float) $o->total, 2),
            'paid'         => round((float) ($o->paid_amount ?? 0), 2),
            'balance'      => round((float) ($o->balance_due ?? 0), 2),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => [
                    'id'         => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name'  => $employee->last_name,
                    'email'      => $employee->email,
                ],
                'order_count' => $orders->count(),
                'total_sales' => round((float) $orders->sum('total'), 2),
                'orders'      => $rows,
            ],
        ]);
    }


    /**
     * Calculate performance rating based on sales per day
     */
    private function calculatePerformanceRating($salesPerDay)
    {
        if ($salesPerDay >= 1000) {
            return ['rating' => 'Excellent', 'color' => 'success'];
        } elseif ($salesPerDay >= 500) {
            return ['rating' => 'Good', 'color' => 'primary'];
        } elseif ($salesPerDay >= 200) {
            return ['rating' => 'Average', 'color' => 'warning'];
        } else {
            return ['rating' => 'Needs Improvement', 'color' => 'danger'];
        }
    }

            
    /**
     * Time-based Sales Analysis Report
     * Shows sales trends by hour, day, week, or month
     */
    public function timeAnalysis(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType    = $request->get('order_type', 'all');
        $groupBy      = $request->get('group_by', 'daily');
        $perPage      = (int) $request->get('per_page', 25);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.orders.time-analysis', compact(
            'locations', 'departments',
            'startDate', 'endDate',
            'locationId', 'departmentId', 'orderType', 'groupBy', 'perPage'
        ));
    }
   
    public function timeAnalysisData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType    = $request->get('order_type', 'all');
        $groupBy      = $request->get('group_by', 'daily');
        $perPage      = (int) $request->get('per_page', 25);
        $page         = max(1, (int) $request->get('page', 1));

        // ─── Orders query ────────────────────────────────────────
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed');

        if ($locationId)   $ordersQuery->where('location_id', $locationId);
        if ($departmentId) $ordersQuery->where('department_id', $departmentId);
        if ($orderType && $orderType !== 'all') $ordersQuery->where('type', $orderType);

        $orders = $ordersQuery->get();

        // ─── Group by period ─────────────────────────────────────
        $buckets = [];

        switch ($groupBy) {
            case 'hourly':
                foreach ($orders as $o) {
                    $h = (int) $o->created_at->format('H');
                    $buckets[$h] ??= ['time_period' => $h, 'orders' => collect()];
                    $buckets[$h]['orders']->push($o);
                }
                ksort($buckets);
                break;

            case 'weekly':
                foreach ($orders as $o) {
                    $key = $o->created_at->format('o-\WW');   // ISO year-week
                    $buckets[$key] ??= ['key' => $key, 'orders' => collect()];
                    $buckets[$key]['orders']->push($o);
                }
                ksort($buckets);
                break;

            case 'monthly':
                foreach ($orders as $o) {
                    $key = $o->created_at->format('Y-m');
                    $buckets[$key] ??= ['key' => $key, 'orders' => collect()];
                    $buckets[$key]['orders']->push($o);
                }
                ksort($buckets);
                break;

            default: // daily
                foreach ($orders as $o) {
                    $key = $o->created_at->format('Y-m-d');
                    $buckets[$key] ??= ['key' => $key, 'orders' => collect()];
                    $buckets[$key]['orders']->push($o);
                }
                ksort($buckets);
        }

        // ─── Build period rows (sorted desc, most recent first) ─
        $periods = collect($buckets)->map(function ($bucket) use ($groupBy) {
            $bucketOrders = $bucket['orders'];
            $first        = $bucketOrders->first();

            $base = [
                'order_count'    => $bucketOrders->count(),
                'total_sales'    => round((float) $bucketOrders->sum('total'), 2),
                'average_sale'   => round((float) ($bucketOrders->avg('total') ?? 0), 2),
                'total_tax'      => round((float) $bucketOrders->sum('tax_total'), 2),
                'total_discount' => round((float) $bucketOrders->sum('discount_total'), 2),
            ];

            if ($groupBy === 'hourly') {
                $hour = (int) $bucket['time_period'];
                $base += [
                    'hour'         => $hour,
                    'hour_display' => date('g:00 A', mktime($hour, 0, 0)),
                    'period_label' => $hour >= 5 && $hour < 12 ? 'Morning'
                                    : ($hour >= 12 && $hour < 17 ? 'Afternoon'
                                    : ($hour >= 17 && $hour < 21 ? 'Evening' : 'Night')),
                    'sort_key'     => $hour,
                ];
            } elseif ($groupBy === 'weekly') {
                $weekStart = $first->created_at->copy()->startOfWeek();
                $weekEnd   = $first->created_at->copy()->endOfWeek();
                $base += [
                    'week_number'  => (int) $first->created_at->weekOfYear,
                    'year'         => (int) $first->created_at->year,
                    'week_start'   => $weekStart->format('Y-m-d'),
                    'week_end'     => $weekEnd->format('Y-m-d'),
                    'date_range'   => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
                    'sort_key'     => $first->created_at->format('o-\WW'),
                ];
            } elseif ($groupBy === 'monthly') {
                $base += [
                    'year'         => (int) $first->created_at->year,
                    'month_number' => (int) $first->created_at->month,
                    'month_name'   => $first->created_at->format('F'),
                    'month_short'  => $first->created_at->format('M'),
                    'sort_key'     => $first->created_at->format('Y-m'),
                ];
            } else {
                $dayName = $first->created_at->format('l');
                $base += [
                    'date'          => $first->created_at->format('Y-m-d'),
                    'day_name'      => $dayName,
                    'is_weekend'    => in_array($dayName, ['Saturday', 'Sunday']),
                    'formatted_date'=> $first->created_at->format('M d, Y'),
                    'sort_key'      => $first->created_at->format('Y-m-d'),
                ];
            }

            return $base;
        })->sortByDesc('sort_key')->values();

        // ─── Compute trend per row (desc order — compare to next row) ─
        $periods = $periods->map(function ($row, $i) use ($periods) {
            $prev = $periods[$i + 1] ?? null;    // next in desc order = previous in time
            if ($prev && $prev['total_sales'] > 0) {
                $delta   = $row['total_sales'] - $prev['total_sales'];
                $pct     = ($delta / $prev['total_sales']) * 100;
                $row['trend_delta']   = round($delta, 2);
                $row['trend_percent'] = round($pct, 2);
                $row['trend_dir']     = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
            } else {
                $row['trend_delta']   = null;
                $row['trend_percent'] = null;
                $row['trend_dir']     = null;
            }
            return $row;
        })->values();

        // ─── Summary ────────────────────────────────────────────
        $summary = [
            'total_periods'   => $periods->count(),
            'total_orders'    => (int) $periods->sum('order_count'),
            'total_sales'     => round((float) $periods->sum('total_sales'), 2),
            'total_tax'       => round((float) $periods->sum('total_tax'), 2),
            'total_discount'  => round((float) $periods->sum('total_discount'), 2),
            'average_sale'    => round((float) ($periods->avg('average_sale') ?? 0), 2),
        ];

        // ─── Growth ─────────────────────────────────────────────
        $growth = $this->calculateGrowthMetrics($periods, $groupBy);

        // ─── Peak days / hours ──────────────────────────────────
        $peak = $this->analyzePeakTimes($orders, $groupBy, $startDate, $endDate);

        $peakDaysFormatted = $peak['peak_days']->map(function ($p) {
            $date = Carbon::parse($p->date ?? $p['date'] ?? null);
            $dayName = $date->format('l');
            return [
                'date'       => $date->format('Y-m-d'),
                'formatted'  => $date->format('M d, Y'),
                'day_name'   => $dayName,
                'is_weekend' => in_array($dayName, ['Saturday', 'Sunday']),
                'order_count'=> $p->order_count ?? $p['order_count'] ?? 0,
                'total_sales'=> round((float) ($p->total_sales ?? $p['total_sales'] ?? 0), 2),
            ];
        })->values();

        $peakHoursFormatted = collect($peak['peak_hours'])->map(function ($p) {
            $h = (int) ($p->hour ?? $p['hour'] ?? 0);
            return [
                'hour'       => $h,
                'display'    => date('g:00 A', mktime($h, 0, 0)),
                'order_count'=> $p->order_count ?? $p['order_count'] ?? 0,
                'hourly_total'=> round((float) ($p->hourly_total ?? $p['hourly_total'] ?? 0), 2),
            ];
        })->values();

        // ─── Chart data (full periods, not paginated) ──────────
        $chart = $periods->map(function ($row) use ($groupBy) {
            if ($groupBy === 'hourly') {
                $label = $row['hour_display'];
            } elseif ($groupBy === 'weekly') {
                $label = 'W' . $row['week_number'];
            } elseif ($groupBy === 'monthly') {
                $label = $row['month_short'] . ' ' . $row['year'];
            } else {
                $label = Carbon::parse($row['date'])->format('M d');
            }
            return [
                'label'   => $label,
                'sales'   => $row['total_sales'],
                'orders'  => $row['order_count'],
            ];
        })->reverse()->values();  // chronological for chart

        // ─── Paginate ──────────────────────────────────────────
        $total    = $periods->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageRows = $periods->slice($offset, $perPage)->values();

        $pageTotals = [
            'order_count'    => (int) $pageRows->sum('order_count'),
            'total_sales'    => round((float) $pageRows->sum('total_sales'), 2),
            'total_tax'      => round((float) $pageRows->sum('total_tax'), 2),
            'total_discount' => round((float) $pageRows->sum('total_discount'), 2),
            'average_sale'   => round((float) ($pageRows->avg('average_sale') ?? 0), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'group_by'      => $groupBy,
                'summary'       => $summary,
                'growth'        => $growth,
                'chart'         => $chart,
                'peak_days'     => $peakDaysFormatted,
                'peak_hours'    => $peakHoursFormatted,
                'periods'       => $pageRows,
                'page_totals'   => $pageTotals,
                'grand_totals'  => [
                    'order_count'    => $summary['total_orders'],
                    'total_sales'    => $summary['total_sales'],
                    'total_tax'      => $summary['total_tax'],
                    'total_discount' => $summary['total_discount'],
                    'average_sale'   => $summary['average_sale'],
                ],
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ],
            ],
        ]);
    }


    /**
     * Calculate growth metrics
     */
    private function calculateGrowthMetrics($timeAnalysis, $groupBy)
    {
        if ($timeAnalysis->count() < 2) {
            return [
                'trend'           => 'stable',
                'daily_growth'    => 0,
                'weekly_growth'   => 0,
                'current_average' => $this->periodValue($timeAnalysis->first(), 'average_sale') ?? 0,
                'total_growth'    => 0,
            ];
        }

        $firstPeriod = $timeAnalysis->last();
        $lastPeriod  = $timeAnalysis->first();

        $firstSales = (float) $this->periodValue($firstPeriod, 'total_sales');
        $lastSales  = (float) $this->periodValue($lastPeriod,  'total_sales');

        $growth = $firstSales > 0 ? (($lastSales - $firstSales) / $firstSales) * 100 : 0;

        $trend = 'stable';
        if ($growth > 10)  $trend = 'upward';
        elseif ($growth < -10) $trend = 'downward';

        $totalGrowth  = 0;
        $growthCount  = 0;

        for ($i = 0; $i < $timeAnalysis->count() - 1; $i++) {
            $current = $timeAnalysis[$i];
            $next    = $timeAnalysis[$i + 1];

            $curSales  = (float) $this->periodValue($current, 'total_sales');
            $nextSales = (float) $this->periodValue($next,    'total_sales');

            if ($nextSales > 0) {
                $totalGrowth += (($curSales - $nextSales) / $nextSales) * 100;
                $growthCount++;
            }
        }

        $dailyGrowth = $growthCount > 0 ? $totalGrowth / $growthCount : 0;

        $weeklyGrowth = 0;
        if ($groupBy === 'daily' && $timeAnalysis->count() >= 8) {
            $currentWeek  = (float) $timeAnalysis->slice(0, 7)->sum(fn($p) => $this->periodValue($p, 'total_sales'));
            $previousWeek = (float) $timeAnalysis->slice(7, 7)->sum(fn($p) => $this->periodValue($p, 'total_sales'));

            if ($previousWeek > 0) {
                $weeklyGrowth = (($currentWeek - $previousWeek) / $previousWeek) * 100;
            }
        }

        return [
            'trend'           => $trend,
            'daily_growth'    => round($dailyGrowth, 2),
            'weekly_growth'   => round($weeklyGrowth, 2),
            'current_average' => (float) $this->periodValue($lastPeriod, 'average_sale'),
            'total_growth'    => round($growth, 2),
        ];
    }

    /**
     * Read a key from an array or object uniformly.
     */
    private function periodValue($row, string $key, $default = 0)
    {
        if (is_array($row))  return $row[$key] ?? $default;
        if (is_object($row)) return $row->{$key} ?? $default;
        return $default;
    }
    
    /**
     * Analyze peak times
     */
    private function analyzePeakTimes($orders, $groupBy, $startDate, $endDate)
    {
        $peakHours = collect();
        $peakDays = collect();
        
        // ─── PEAK HOURS (only for hourly grouping) ──────────────────
        if ($groupBy == 'hourly') {
            $peakHours = $orders->groupBy(function($order) {
                return (int)$order->created_at->format('H');
            })->map(function($hourOrders, $hour) {
                return (object)[
                    'hour' => (int)$hour,
                    'order_count' => $hourOrders->count(),
                    'hourly_total' => $hourOrders->sum('total'),
                    'hourly_average' => $hourOrders->count() > 0 ? $hourOrders->sum('total') / $hourOrders->count() : 0,
                ];
            })->sortByDesc('hourly_total')->take(5)->values();
        }
        
        // ─── PEAK DAYS ──────────────────────────────────────────────────
        if ($groupBy == 'hourly' || $groupBy == 'daily') {
            $peakDays = $orders->groupBy(function($order) {
                return $order->created_at->format('Y-m-d');
            })->map(function($dayOrders, $date) {
                return (object)[
                    'date' => $date,
                    'order_count' => $dayOrders->count(),
                    'total_sales' => $dayOrders->sum('total'),
                    'average_sale' => $dayOrders->count() > 0 ? $dayOrders->sum('total') / $dayOrders->count() : 0,
                ];
            })->sortByDesc('total_sales')->take(5)->values();
        } elseif ($groupBy == 'weekly') {
            $peakDays = $orders->groupBy(function($order) {
                return $order->created_at->weekOfYear . '-' . $order->created_at->year;
            })->map(function($weekOrders, $key) {
                $firstOrder = $weekOrders->first();
                $weekStart = $firstOrder->created_at->copy()->startOfWeek();
                $weekEnd = $firstOrder->created_at->copy()->endOfWeek();
                
                return (object)[
                    'date' => $weekStart->format('Y-m-d'),
                    'week_range' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
                    'order_count' => $weekOrders->count(),
                    'total_sales' => $weekOrders->sum('total'),
                    'average_sale' => $weekOrders->count() > 0 ? $weekOrders->sum('total') / $weekOrders->count() : 0,
                ];
            })->sortByDesc('total_sales')->take(5)->values();
            
        } elseif ($groupBy == 'monthly') {
            $peakDays = $orders->groupBy(function($order) {
                return $order->created_at->format('Y-m');
            })->map(function($monthOrders, $key) {
                $firstOrder = $monthOrders->first();
                
                return (object)[
                    'date' => $key,
                    'month_name' => $firstOrder->created_at->format('F Y'),
                    'order_count' => $monthOrders->count(),
                    'total_sales' => $monthOrders->sum('total'),
                    'average_sale' => $monthOrders->count() > 0 ? $monthOrders->sum('total') / $monthOrders->count() : 0,
                ];
            })->sortByDesc('total_sales')->take(5)->values();
        }
        
        return [
            'peak_hours' => $peakHours,
            'peak_days' => $peakDays,
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total'),
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }


    /**
     * Returns and Refunds Report
     * Shows return orders, refund payments, and return analysis
     */
    public function returnsRefunds(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate    = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $statusFilter = $request->get('status_filter', 'all');
        $perPage      = (int) $request->get('per_page', 25);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.orders.returns-refunds', compact(
            'locations', 'departments',
            'startDate', 'endDate',
            'locationId', 'departmentId', 'statusFilter', 'perPage'
        ));
    }

    public function returnsRefundsData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $statusFilter = $request->get('status_filter', 'all');
        $perPage      = (int) $request->get('per_page', 25);
        $returnsPage  = max(1, (int) $request->get('returns_page', 1));
        $refundsPage  = max(1, (int) $request->get('refunds_page', 1));

        // ── Orders that have at least one partially/fully refunded line ──
        $returnQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereHas('orderItems', fn($q) => $q->where('refunded_quantity', '>', 0))
            ->with([
                'customer:id,first_name,last_name,email',
                'orderCreater:id,name',
                'orderItems.productVariant:id,name,sku',
                'orderPayments.paymentMethod:id,name,type',
            ]);

        if ($locationId)   $returnQuery->where('location_id', $locationId);
        if ($departmentId) $returnQuery->where('department_id', $departmentId);
        if ($statusFilter && $statusFilter !== 'all') $returnQuery->where('status', $statusFilter);

        $refundedOrders = $returnQuery->orderByDesc('created_at')->get();

        // ── Refund reason from the appended note ───────────────
        $reasonFromNote = function (?string $notes): ?string {
            if (!$notes) return null;
            if (preg_match('/Reason:\s*(.+?)\./s', $notes, $m)) return trim($m[1]);
            return null;
        };

        // ── Total refunded per order + per order amount ────────
        $orderRefundedTotal = fn(Order $o) => (float) $o->orderPayments->sum('refunded_amount');

        // ── Refund payments: refunded_amount > 0 (partial or full) ──
        $refundPaymentsRaw = OrderPayment::whereHas('order', function ($q) use ($tenantId, $startDate, $endDate, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                if ($locationId)   $q->where('location_id', $locationId);
                if ($departmentId) $q->where('department_id', $departmentId);
            })
            ->where('refunded_amount', '>', 0)
            ->with(['order:id,order_number,total', 'paymentMethod:id,name,type', 'processor:id,first_name,last_name'])
            ->orderByDesc('processed_at')
            ->get();

        $refundPayments = $refundPaymentsRaw->map(fn($p) => [
            'id'                  => $p->id,
            'order_id'            => $p->order_id,
            'order_number'        => $p->order?->order_number ?? 'N/A',
            'order_total'         => round((float) ($p->order?->total ?? 0), 2),
            'amount'              => round((float) ($p->refunded_amount ?? 0), 2),  // ← refunded portion
            'original_amount'     => round((float) $p->amount, 2),
            'payment_method'      => $p->paymentMethod?->name ?? 'Unknown',
            'payment_method_type' => $p->paymentMethod?->type ?? 'other',
            'processed_at'        => $p->processed_at?->format('Y-m-d H:i'),
            'status'              => $p->status,                                     // may still be 'completed'
            'reference'           => $p->transaction_id,
            'processed_by'        => trim(($p->processor?->first_name ?? '') . ' ' . ($p->processor?->last_name ?? '')) ?: 'System',
        ])->values();

        // ── Refund reasons aggregated across ALL refunded lines ──
        $reasonsCollection = $refundedOrders
            ->map(function (Order $o) use ($reasonFromNote, $orderRefundedTotal) {
                return [
                    'reason' => $reasonFromNote($o->notes) ?? 'Unspecified',
                    'order'  => $o,
                    'amount' => $orderRefundedTotal($o),
                ];
            })
            ->groupBy('reason')
            ->map(fn($rows, $reason) => [
                'reason'       => $reason,
                'count'        => $rows->count(),
                'total_amount' => round((float) $rows->sum('amount'), 2),
                'percentage'   => $refundedOrders->count() > 0
                                    ? round(($rows->count() / $refundedOrders->count()) * 100, 2) : 0,
            ])
            ->sortByDesc('count')->values();

        // ── Rates ──────────────────────────────────────────────
        $totalSalesOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sales()->count();

        $totalRefundedOrders = $refundedOrders->count();
        $returnRate          = $totalSalesOrders > 0 ? ($totalRefundedOrders / $totalSalesOrders) * 100 : 0;

        $totalSalesValue  = (float) Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sales()->sum('total');
        $totalRefundValue = (float) $refundedOrders->sum(fn($o) => $orderRefundedTotal($o));
        $refundRate       = $totalSalesValue > 0 ? ($totalRefundValue / $totalSalesValue) * 100 : 0;

        // ── Top refunded products (by refunded_quantity) ──────
        $topReturned = collect();
        foreach ($refundedOrders as $o) {
            foreach ($o->orderItems as $item) {
                $refunded = (float) ($item->refunded_quantity ?? 0);
                if ($refunded <= 0) continue;
                $variant = $item->productVariant;
                if (!$variant) continue;

                $key = $variant->id;
                $value = $refunded * (float) $item->unit_price;

                if ($topReturned->has($key)) {
                    $existing = $topReturned->get($key);
                    $existing->return_quantity += $refunded;
                    $existing->return_value    += $value;
                    $existing->return_count    += 1;
                } else {
                    $topReturned->put($key, (object) [
                        'sku'             => $variant->sku ?? 'N/A',
                        'name'            => $variant->name ?? 'Unknown',
                        'return_quantity' => $refunded,
                        'return_value'    => $value,
                        'return_count'    => 1,
                    ]);
                }
            }
        }
        $topReturned = $topReturned->sortByDesc('return_quantity')->take(10)->values();

        // ── Metrics ────────────────────────────────────────────
        $metrics = [
            'total_return_orders'  => $totalRefundedOrders,
            'total_refund_amount'  => round($totalRefundValue, 2),
            'average_return_value' => $totalRefundedOrders > 0 ? round($totalRefundValue / $totalRefundedOrders, 2) : 0,
            'return_rate'          => round($returnRate, 2),
            'refund_rate'          => round($refundRate, 2),
            'total_sales_value'    => round($totalSalesValue, 2),
            'total_orders'         => $totalSalesOrders,
            'items_returned'       => (int) $topReturned->sum('return_quantity'),
            'unique_products'      => $topReturned->count(),
        ];

        // ── Monthly trends (by refunded money) ─────────────────
        $monthlyTrends = $refundedOrders->groupBy(fn($o) => $o->created_at->format('Y-m'))
            ->map(fn($orders, $month) => [
                'month'               => $month,
                'return_count'        => $orders->count(),
                'return_value'        => round((float) $orders->sum(fn($o) => $orderRefundedTotal($o)), 2),
                'percentage_of_total' => $refundedOrders->count() > 0
                                            ? round(($orders->count() / $refundedOrders->count()) * 100, 2) : 0,
            ])->sortKeys()->values();

        // ── Returns by payment method (by refunded amount) ─────
        $byPaymentMethod = $refundPayments
            ->groupBy('payment_method')
            ->map(fn($rows, $method) => [
                'payment_method' => $method,
                'return_count'   => $rows->count(),
                'return_value'   => round((float) $rows->sum('amount'), 2),
            ])
            ->filter(fn($r) => $r['return_count'] > 0)
            ->sortByDesc('return_value')->values();

        // ── Paginate refunded orders ───────────────────────────
        $returnsTotal    = $refundedOrders->count();
        $returnsLastPage = max(1, (int) ceil($returnsTotal / $perPage));
        $returnsPage     = min($returnsPage, $returnsLastPage);
        $returnsOffset   = ($returnsPage - 1) * $perPage;

        $returnOrdersPage = $refundedOrders->slice($returnsOffset, $perPage)->values()
            ->map(function ($o) use ($reasonFromNote, $orderRefundedTotal) {
                $statusColors = [
                    'pending'    => 'warning', 'refunded'   => 'dark',
                    'rejected'   => 'danger',  'processing' => 'info',
                    'completed'  => 'success', 'cancelled'  => 'secondary',
                ];

                // Count fully/partially refunded lines for the row display
                $refundedLines = $o->orderItems->filter(fn($i) => (float) ($i->refunded_quantity ?? 0) > 0)->count();
                $totalLines    = $o->orderItems->count();

                return [
                    'id'            => $o->id,
                    'order_number'  => $o->order_number ?? (string) $o->id,
                    'status'        => $o->status,
                    'status_color'  => $statusColors[$o->status] ?? 'secondary',
                    'customer'      => [
                        'name'  => trim(($o->customer?->first_name ?? '') . ' ' . ($o->customer?->last_name ?? '')) ?: ($o->customer_name ?? 'N/A'),
                        'email' => $o->customer?->email,
                    ],
                    'order_total'   => round((float) $o->total, 2),
                    'return_amount' => round($orderRefundedTotal($o), 2),
                    'reason'        => $reasonFromNote($o->notes) ?? '—',
                    'refunded_lines'=> $refundedLines,
                    'total_lines'   => $totalLines,
                    'created_at'    => $o->created_at?->format('Y-m-d H:i'),
                    'creator'       => $o->orderCreater?->name ?? '—',
                ];
            });

        // ── Paginate refund payments ───────────────────────────
        $refundsTotal    = $refundPayments->count();
        $refundsLastPage = max(1, (int) ceil($refundsTotal / $perPage));
        $refundsPage     = min($refundsPage, $refundsLastPage);
        $refundsOffset   = ($refundsPage - 1) * $perPage;

        $refundsPageRows = $refundPayments->slice($refundsOffset, $perPage)->values()
            ->map(function ($p) {
                $p['refund_percentage'] = $p['original_amount'] > 0
                    ? round(($p['amount'] / $p['original_amount']) * 100, 2)
                    : 0;
                return $p;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'           => $metrics,
                'reasons'           => $reasonsCollection,
                'monthly_trends'    => $monthlyTrends,
                'by_payment_method' => $byPaymentMethod,
                'top_returned'      => $topReturned,
                'return_orders'     => $returnOrdersPage,
                'return_orders_pagination' => [
                    'current_page' => $returnsPage,
                    'last_page'    => $returnsLastPage,
                    'total'        => $returnsTotal,
                    'from'         => $returnsTotal > 0 ? $returnsOffset + 1 : 0,
                    'to'           => min($returnsOffset + $perPage, $returnsTotal),
                    'per_page'     => $perPage,
                    'page_param'   => 'returns_page',
                ],
                'refund_payments'   => $refundsPageRows,
                'refund_payments_pagination' => [
                    'current_page' => $refundsPage,
                    'last_page'    => $refundsLastPage,
                    'total'        => $refundsTotal,
                    'from'         => $refundsTotal > 0 ? $refundsOffset + 1 : 0,
                    'to'           => min($refundsOffset + $perPage, $refundsTotal),
                    'per_page'     => $perPage,
                    'page_param'   => 'refunds_page',
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'status_filter' => $statusFilter,
                ],
            ],
        ]);
    }

    public function returnsRefundsOrderDetail($orderId)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $order = Order::where('tenant_id', $tenantId)
            ->with([
                'customer:id,first_name,last_name,email,phone',
                'orderCreater:id,name',
                'orderItems.productVariant:id,name,sku',
                'orderPayments.paymentMethod:id,name,type',
                'orderPayments.processor:id,first_name,last_name',
            ])->findOrFail($orderId);

        $items = $order->orderItems->map(function ($i) {
            $qty      = (float) $i->quantity;
            $refunded = (float) ($i->refunded_quantity ?? 0);
            $remaining= max(0, $qty - $refunded);

            // Label mirrors the UI you showed
            if ($refunded <= 0) {
                $refundLabel = '—';
                $refundColor = 'secondary';
            } elseif ($remaining <= 0.0001) {
                $refundLabel = 'Fully refunded';
                $refundColor = 'danger';
            } else {
                $refundLabel = "Partially refunded ({$refunded}/{$qty})";
                $refundColor = 'warning';
            }

            return [
                'id'               => $i->id,
                'sku'              => $i->productVariant?->sku ?? 'N/A',
                'name'             => $i->productVariant?->name ?? 'Unknown',
                'quantity'         => $qty,
                'unit_price'       => round((float) $i->unit_price, 2),
                'discount'         => round((float) $i->discount, 2),
                'total_price'      => round((float) $i->total_price, 2),
                'refunded_qty'     => $refunded,
                'refundable_qty'   => $remaining,
                'refunded_value'   => round($refunded * (float) $i->unit_price, 2),
                'refund_label'     => $refundLabel,
                'refund_color'     => $refundColor,
            ];
        })->values();

        $payments = $order->orderPayments->map(fn($p) => [
            'id'                => $p->id,
            'amount'            => round((float) $p->amount, 2),
            'refunded_amount'   => round((float) ($p->refunded_amount ?? 0), 2),
            'refundable_amount' => max(0, round((float) $p->amount - (float) ($p->refunded_amount ?? 0), 2)),
            'method'            => $p->paymentMethod?->name ?? 'Unknown',
            'method_type'       => $p->paymentMethod?->type ?? 'other',
            'status'            => $p->status,
            'processed_at'      => $p->processed_at?->format('Y-m-d H:i'),
            'reference'         => $p->transaction_id,
            'processor'         => trim(($p->processor?->first_name ?? '') . ' ' . ($p->processor?->last_name ?? '')) ?: 'System',
            'notes'             => $p->notes,
        ])->values();

        $refundedTotal   = round((float) $order->orderPayments->sum('refunded_amount'), 2);
        $refundableCap   = round(max(0, (float) $order->paid_amount), 2);
        $fullyRefunded   = $items->filter(fn($i) => $i['refundable_qty'] <= 0.0001 && $i['refunded_qty'] > 0)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'id'             => $order->id,
                'order_number'   => $order->order_number ?? (string) $order->id,
                'status'         => $order->status,
                'created_at'     => $order->created_at?->format('Y-m-d H:i'),
                'customer'       => [
                    'name'  => trim(($order->customer?->first_name ?? '') . ' ' . ($order->customer?->last_name ?? '')) ?: ($order->customer_name ?? 'N/A'),
                    'email' => $order->customer?->email,
                    'phone' => $order->customer?->phone,
                ],
                'creator'        => $order->orderCreater?->name ?? '—',
                'subtotal'       => round((float) $order->subtotal, 2),
                'discount'       => round((float) $order->discount_total, 2),
                'tax'            => round((float) $order->tax_total, 2),
                'total'          => round((float) $order->total, 2),
                'paid_amount'    => round((float) $order->paid_amount, 2),
                'balance_due'    => round((float) $order->balance_due, 2),
                'refunded_total' => $refundedTotal,
                'refundable_cap' => $refundableCap,
                'fully_refunded' => $fullyRefunded,
                'total_items'    => $items->count(),
                'notes'          => $order->notes,
                'items'          => $items,
                'payments'       => $payments,
            ],
        ]);
    }

    public function returnsRefundsPaymentDetail($paymentId)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $payment = OrderPayment::whereHas('order', fn($q) => $q->where('tenant_id', $tenantId))
            ->with([
                'order:id,order_number,total,paid_amount,balance_due,created_at',
                'paymentMethod:id,name,type',
                'processor:id,first_name,last_name',
            ])
            ->findOrFail($paymentId);

        $trail = OrderPayment::where('order_id', $payment->order_id)
            ->with(['paymentMethod:id,name,type'])
            ->orderBy('processed_at')
            ->get()
            ->map(fn($p) => [
                'id'              => $p->id,
                'amount'          => round((float) $p->amount, 2),
                'refunded_amount' => round((float) ($p->refunded_amount ?? 0), 2),
                'status'          => $p->status,
                'method'          => $p->paymentMethod?->name ?? 'Unknown',
                'processed_at'    => $p->processed_at?->format('Y-m-d H:i'),
                'reference'       => $p->transaction_id,
            ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $payment->id,
                'amount'            => round((float) $payment->amount, 2),
                'refunded_amount'   => round((float) ($payment->refunded_amount ?? 0), 2),
                'refundable_amount' => max(0, round((float) $payment->amount - (float) ($payment->refunded_amount ?? 0), 2)),
                'status'            => $payment->status,
                'payment_method'    => $payment->paymentMethod?->name ?? 'Unknown',
                'payment_method_type'=> $payment->paymentMethod?->type ?? 'other',
                'processed_at'      => $payment->processed_at?->format('Y-m-d H:i'),
                'reference'         => $payment->transaction_id,
                'processed_by'      => trim(($payment->processor?->first_name ?? '') . ' ' . ($payment->processor?->last_name ?? '')) ?: 'System',
                'notes'             => $payment->notes,
                'order' => [
                    'id'           => $payment->order?->id,
                    'order_number' => $payment->order?->order_number ?? 'N/A',
                    'total'        => round((float) ($payment->order?->total ?? 0), 2),
                    'paid_amount'  => round((float) ($payment->order?->paid_amount ?? 0), 2),
                    'balance_due'  => round((float) ($payment->order?->balance_due ?? 0), 2),
                    'created_at'   => $payment->order?->created_at?->format('Y-m-d'),
                ],
                'trail'             => $trail,
            ],
        ]);
    }


    /**
     * Discount Analysis Report
     * Shows discount patterns, employee discount behavior, and discount effectiveness
     */
    public function discountAnalysis(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $employeeId   = $request->get('employee_id');
        $perPage      = (int) $request->get('per_page', 25);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        $employeesList = User::where('tenant_id', $tenantId)
            ->where('status', 'active')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('reports.orders.discount-analysis', compact(
            'locations', 'departments', 'employeesList',
            'startDate', 'endDate',
            'locationId', 'departmentId', 'employeeId', 'perPage'
        ));
    }

    public function discountAnalysisData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $employeeId   = $request->get('employee_id');
        $perPage      = (int) $request->get('per_page', 25);
        $page         = max(1, (int) $request->get('page', 1));

        // ── Base query ──────────────────────────────────────────
        $baseQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($locationId)   $baseQuery->where('location_id', $locationId);
        if ($departmentId) $baseQuery->where('department_id', $departmentId);

        // ── Discounted orders ───────────────────────────────────
        $discountedQuery = (clone $baseQuery)->withDiscounts()
            ->with(['customer:id,first_name,last_name,email', 'orderCreater:id,name']);

        if ($employeeId) $discountedQuery->where('created_by', $employeeId);

        $discountedOrders = $discountedQuery->orderByDesc('discount_total')->get();

        // ── Helper: discount % of pre-discount subtotal ─────────
        $discountPct = function (Order $o) {
            $pre = (float) ($o->subtotal_before_bargain ?: ($o->total + $o->discount_total));
            if ($pre <= 0) return 0;
            return round(((float) $o->discount_total / $pre) * 100, 2);
        };

        // ── Summary ─────────────────────────────────────────────
        $totalDiscountAmount = (float) $discountedOrders->sum('discount_total');
        $preDiscountSum      = (float) $discountedOrders->sum(fn($o) =>
            (float) ($o->subtotal_before_bargain ?: ($o->total + $o->discount_total))
        );

        $discountSummary = [
            'total_discounted_orders'   => $discountedOrders->count(),
            'total_discount_amount'     => round($totalDiscountAmount, 2),
            'average_discount_per_order'=> $discountedOrders->count() > 0
                                            ? round($totalDiscountAmount / $discountedOrders->count(), 2) : 0,
            'max_discount'              => round((float) ($discountedOrders->max('discount_total') ?? 0), 2),
            'min_discount'              => round((float) ($discountedOrders->min('discount_total') ?? 0), 2),
            'discount_rate'             => $preDiscountSum > 0
                                            ? round(($totalDiscountAmount / $preDiscountSum) * 100, 2) : 0,
        ];

        // ── By employee ─────────────────────────────────────────
        $discountByEmployee = $discountedOrders->groupBy('created_by')->map(function ($orders, $uid) {
            $first = $orders->first();
            $user  = $first->orderCreater;

            $totalDiscount = (float) $orders->sum('discount_total');
            $totalRevenue  = (float) $orders->sum('total');
            $count         = $orders->count();

            return [
                'id'                             => $uid,
                'name'                           => $user?->name ?? 'Unknown',
                'email'                          => $user?->email ?? '',
                'order_count'                    => $count,
                'total_discount_given'           => round($totalDiscount, 2),
                'average_discount'               => round((float) ($orders->avg('discount_total') ?? 0), 2),
                'max_discount_given'             => round((float) ($orders->max('discount_total') ?? 0), 2),
                'discount_per_order'             => $count > 0 ? round($totalDiscount / $count, 2) : 0,
                'discount_as_percentage_of_sales'=> $totalRevenue > 0
                                                    ? round(($totalDiscount / $totalRevenue) * 100, 2) : 0,
            ];
        })
        ->filter(fn($e) => $e['name'] !== 'Unknown')
        ->sortByDesc('total_discount_given')->values();

        // ── By day of week ──────────────────────────────────────
        $discountByDay = $discountedOrders
            ->groupBy(fn($o) => $o->created_at->format('l'))
            ->map(fn($orders, $day) => [
                'day'            => $day,
                'discount_count' => $orders->count(),
                'total_amount'   => round((float) $orders->sum('discount_total'), 2),
                'average_amount' => round((float) ($orders->avg('discount_total') ?? 0), 2),
                'order_count'    => $orders->count(),
            ])
            ->sortBy(fn($item) => array_search($item['day'], ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']))
            ->values();

        // ── By hour ─────────────────────────────────────────────
        $discountByHour = $discountedOrders
            ->groupBy(fn($o) => $o->created_at->format('H'))
            ->map(fn($orders, $hour) => [
                'hour'           => (int) $hour,
                'hour_formatted' => date('g:00 A', mktime((int) $hour, 0, 0)),
                'discount_count' => $orders->count(),
                'total_amount'   => round((float) $orders->sum('discount_total'), 2),
                'average_amount' => round((float) ($orders->avg('discount_total') ?? 0), 2),
            ])
            ->sortKeys()->values();

        // ── With vs without discount ────────────────────────────
        $withQ    = (clone $baseQuery)->withDiscounts();
        $withoutQ = (clone $baseQuery)->withoutDiscounts();
        $withData    = $withQ->get();
        $withoutData = $withoutQ->get();

        $ordersWithDiscount = [
            'order_count'         => $withData->count(),
            'average_order_value' => round((float) ($withData->avg('total') ?? 0), 2),
            'average_discount'    => round((float) ($withData->avg('discount_total') ?? 0), 2),
            'total_revenue'       => round((float) $withData->sum('total'), 2),
        ];
        $ordersWithoutDiscount = [
            'order_count'         => $withoutData->count(),
            'average_order_value' => round((float) ($withoutData->avg('total') ?? 0), 2),
            'total_revenue'       => round((float) $withoutData->sum('total'), 2),
        ];

        $withAvg    = $ordersWithDiscount['average_order_value'];
        $withoutAvg = $ordersWithoutDiscount['average_order_value'];
        $difference = round($withAvg - $withoutAvg, 2);
        $pctDiff    = $withoutAvg > 0 ? round((($withAvg - $withoutAvg) / $withoutAvg) * 100, 2) : 0;

        // ── Range distribution ──────────────────────────────────
        $discountRanges = [
            '0-10%' => 0, '10-20%' => 0, '20-30%' => 0,
            '30-40%' => 0, '40-50%' => 0, '50%+' => 0,
        ];
        foreach ($discountedOrders as $o) {
            $p = $discountPct($o);
            if ($p <= 10)      $discountRanges['0-10%']++;
            elseif ($p <= 20)  $discountRanges['10-20%']++;
            elseif ($p <= 30)  $discountRanges['20-30%']++;
            elseif ($p <= 40)  $discountRanges['30-40%']++;
            elseif ($p <= 50)  $discountRanges['40-50%']++;
            else               $discountRanges['50%+']++;
        }

        // ── Paginate discounted orders ──────────────────────────
        $total    = $discountedOrders->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $statusColors = [
            'completed' => 'success', 'pending' => 'warning', 'processing' => 'info',
            'cancelled' => 'danger',  'refunded' => 'secondary', 'confirmed' => 'primary',
        ];

        $rows = $discountedOrders->slice($offset, $perPage)->values()->map(function ($o) use ($discountPct, $statusColors) {
            $pre = (float) ($o->subtotal_before_bargain ?: ($o->total + $o->discount_total));
            return [
                'id'            => $o->id,
                'order_number'  => $o->order_number ?? (string) $o->id,
                'customer_name' => $o->customer
                    ? trim(($o->customer->first_name ?? '') . ' ' . ($o->customer->last_name ?? ''))
                    : ($o->customer_name ?? 'Guest'),
                'customer_email'=> $o->customer?->email,
                'order_total'   => round((float) $o->total, 2),
                'pre_discount'  => round($pre, 2),
                'discount_total'=> round((float) $o->discount_total, 2),
                'bargain'       => round((float) ($o->bargain_discount_applied ?? 0), 2),
                'discount_pct'  => $discountPct($o),
                'final_amount'  => round((float) ($o->total - $o->discount_total), 2),
                'employee'      => $o->orderCreater?->name ?? '—',
                'created_at'    => $o->created_at?->format('Y-m-d H:i'),
                'status'        => $o->status,
                'status_color'  => $statusColors[$o->status] ?? 'secondary',
            ];
        });

        // ── Page-level + grand totals ───────────────────────────
        $pageTotals = [
            'order_total'    => round((float) $rows->sum('order_total'), 2),
            'discount_total' => round((float) $rows->sum('discount_total'), 2),
        ];
        $grandTotals = [
            'order_total'    => round((float) $discountedOrders->sum('total'), 2),
            'discount_total' => round($totalDiscountAmount, 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary'              => $discountSummary,
                'discount_effectiveness' => [
                    'with_discount_avg'      => $withAvg,
                    'without_discount_avg'   => $withoutAvg,
                    'difference'             => $difference,
                    'percentage_difference'  => $pctDiff,
                    'with_discount_count'    => $ordersWithDiscount['order_count'],
                    'without_discount_count' => $ordersWithoutDiscount['order_count'],
                ],
                'orders_with_discount'    => $ordersWithDiscount,
                'orders_without_discount' => $ordersWithoutDiscount,
                'by_employee'             => $discountByEmployee,
                'by_day'                  => $discountByDay,
                'by_hour'                 => $discountByHour,
                'ranges'                 => $discountRanges,
                'orders'                 => $rows,
                'page_totals'            => $pageTotals,
                'grand_totals'           => $grandTotals,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                    'employee_id'   => $employeeId,
                ],
            ],
        ]);
    }

    public function discountAnalysisOrder($orderId)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $order = Order::where('tenant_id', $tenantId)
            ->with([
                'customer:id,first_name,last_name,email,phone',
                'orderCreater:id,name',
                'orderItems.productVariant:id,name,sku',
            ])
            ->findOrFail($orderId);

        // Per-line discount (line discount is separate from order-level bargain)
        $items = $order->orderItems->map(fn($i) => [
            'id'             => $i->id,
            'sku'            => $i->productVariant?->sku ?? 'N/A',
            'name'           => $i->productVariant?->name ?? 'Unknown',
            'quantity'       => (float) $i->quantity,
            'unit_price'     => round((float) $i->unit_price, 2),
            'line_discount'  => round((float) $i->discount, 2),
            'tax_amount'     => round((float) $i->tax_amount, 2),
            'total_price'    => round((float) $i->total_price, 2),
        ])->values();

        // Sum of line-level discounts across items
        $lineDiscountSum = (float) $order->orderItems->sum('discount');

        // Bargain (order-level) discount tracked separately on the order
        $bargainDiscount = (float) ($order->bargain_discount_applied ?? 0);

        $subtotalBeforeBargain = (float) ($order->subtotal_before_bargain ?: ($order->subtotal + $bargainDiscount));

        $discountTotal = (float) $order->discount_total;
        $discountPct   = $subtotalBeforeBargain > 0
            ? round(($discountTotal / $subtotalBeforeBargain) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'id'            => $order->id,
                'order_number'  => $order->order_number ?? (string) $order->id,
                'status'        => $order->status,
                'created_at'    => $order->created_at?->format('Y-m-d H:i'),
                'customer'      => [
                    'name'  => $order->customer
                        ? trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''))
                        : ($order->customer_name ?? 'Guest'),
                    'email' => $order->customer?->email,
                    'phone' => $order->customer?->phone,
                ],
                'employee'      => $order->orderCreater?->name ?? '—',
                'subtotal'             => round((float) $order->subtotal, 2),
                'subtotal_before_bargain'=> round($subtotalBeforeBargain, 2),
                'discount_total'       => round($discountTotal, 2),
                'line_discount_total'  => round($lineDiscountSum, 2),
                'bargain_discount'     => round($bargainDiscount, 2),
                'discount_pct'         => $discountPct,
                'tax'                  => round((float) $order->tax_total, 2),
                'total'                => round((float) $order->total, 2),
                'final_amount'         => round((float) ($order->total - $order->discount_total), 2),
                'items'                => $items,
            ],
        ]);
    }

    /**
     * Sales Forecast Report
     * Predicts future sales based on historical data using trend analysis and seasonality
     */
    public function salesForecast(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate    = $request->get('start_date', Carbon::now()->subDays(90)->format('Y-m-d'));
        $endDate      = $request->get('end_date',   Carbon::now()->format('Y-m-d'));
        $forecastDays = (int) $request->get('forecast_days', 30);
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId        = $request->get('location_id');
        $departmentId      = $request->get('department_id');
        $historicalPerPage = (int) $request->get('historical_per_page', 15);
        $forecastPerPage   = (int) $request->get('forecast_per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.orders.sales-forecast', compact(
            'locations', 'departments',
            'startDate', 'endDate', 'forecastDays',
            'locationId', 'departmentId',
            'historicalPerPage', 'forecastPerPage'
        ));
    }

    public function salesForecastData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate    = $request->get('start_date', Carbon::now()->subDays(90)->format('Y-m-d'));
        $endDate      = $request->get('end_date',   Carbon::now()->format('Y-m-d'));
        $forecastDays = (int) $request->get('forecast_days', 30);
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId        = $request->get('location_id');
        $departmentId      = $request->get('department_id');
        $historicalPerPage = (int) $request->get('historical_per_page', 15);
        $forecastPerPage   = (int) $request->get('forecast_per_page', 15);
        $historicalPage    = max(1, (int) $request->get('historical_page', 1));
        $forecastPage      = max(1, (int) $request->get('forecast_page', 1));

        // ── Orders ─────────────────────────────────────────────
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed');

        if ($locationId)   $ordersQuery->where('location_id', $locationId);
        if ($departmentId) $ordersQuery->where('department_id', $departmentId);

        $orders = $ordersQuery->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'empty' => true,
                    'meta' => ['start_date' => $startDate, 'end_date' => $endDate, 'forecast_days' => $forecastDays],
                ],
            ]);
        }

        // ── Historical daily aggregation ───────────────────────
        $historical = $orders->groupBy(fn($o) => $o->created_at->format('Y-m-d'))
            ->map(fn($daily, $date) => [
                'date'                => $date,
                'order_count'         => $daily->count(),
                'daily_sales'         => round((float) $daily->sum('total'), 2),
                'average_order_value' => round((float) ($daily->avg('total') ?? 0), 2),
            ])
            ->sortByDesc('date')     // most recent first
            ->values();

        // ── Trend & deviation per row (computed across pagination boundaries) ──
        $historicalAvg = round((float) $historical->avg('daily_sales'), 2);

        $historical = $historical->map(function ($row, $i) use ($historical, $historicalAvg) {
            $prev = $historical[$i + 1] ?? null;    // next in desc order = previous in time

            if ($prev && $prev['daily_sales'] > 0) {
                $delta   = $row['daily_sales'] - $prev['daily_sales'];
                $row['trend']       = round($delta, 2);
                $row['trend_pct']   = round(($delta / $prev['daily_sales']) * 100, 2);
                $row['trend_dir']   = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
            } else {
                $row['trend']     = null;
                $row['trend_pct'] = null;
                $row['trend_dir'] = null;
            }

            $row['deviation_pct'] = $historicalAvg > 0
                ? round((($row['daily_sales'] - $historicalAvg) / $historicalAvg) * 100, 2)
                : 0;

            return $row;
        })->values();

        // ── Trends + Forecast ──────────────────────────────────
        $historicalCollection = $historical->map(fn($r) => (object) $r);

        $trends   = $this->calculateSalesTrends($historicalCollection);
        $forecast = $this->generateForecast($historicalCollection, $trends, $forecastDays);
        $forecastCollection = collect($forecast);

        // ── Seasonality ────────────────────────────────────────
        $seasonality = $orders->groupBy(fn($o) => $o->created_at->dayOfWeek)
            ->map(fn($dayOrders, $dow) => [
                'day_of_week'   => (int) $dow,
                'order_count'   => $dayOrders->count(),
                'average_sales' => round((float) ($dayOrders->avg('total') ?? 0), 2),
                'total_sales'   => round((float) $dayOrders->sum('total'), 2),
                'day_name'      => $this->getDayNameFromNumber($dow),
            ])
            ->sortKeys()->values();

        $growthRate = $this->calculateGrowthRate($historicalCollection);

        // ── Forecast summary ───────────────────────────────────
        $forecastSum = (float) $forecastCollection->sum('forecast_sales');
        $forecastAvg = (float) ($forecastCollection->avg('forecast_sales') ?? 0);
        $forecastMax = (float) ($forecastCollection->max('forecast_sales') ?? 0);
        $forecastMin = (float) ($forecastCollection->min('forecast_sales') ?? 0);

        $growthPct       = $growthRate * 100;
        $confidenceScore = min(95, max(60, 85 - abs($growthPct) * 2));

        // ── Chart series (full, not paginated) ─────────────────
        $chart = [
            'historical' => $historical->sortBy('date')->values()->map(fn($r) => [
                'date'  => $r['date'],
                'sales' => $r['daily_sales'],
            ]),
            'forecast' => collect($forecast)->values()->map(fn($f) => [
                'date'            => $f['date'],
                'sales'           => round((float) $f['forecast_sales'], 2),
                'confidence_low'  => round((float) $f['confidence_low'], 2),
                'confidence_high' => round((float) $f['confidence_high'], 2),
            ]),
        ];

        // ── Historical pagination ──────────────────────────────
        $histTotal    = $historical->count();
        $histLastPage = max(1, (int) ceil($histTotal / $historicalPerPage));
        $historicalPage = min($historicalPage, $histLastPage);
        $histOffset   = ($historicalPage - 1) * $historicalPerPage;
        $histPage     = $historical->slice($histOffset, $historicalPerPage)->values();

        // ── Forecast pagination ────────────────────────────────
        $fcastTotal    = $forecastCollection->count();
        $fcastLastPage = max(1, (int) ceil($fcastTotal / $forecastPerPage));
        $forecastPage  = min($forecastPage, $fcastLastPage);
        $fcastOffset   = ($forecastPage - 1) * $forecastPerPage;
        $fcastPage     = $forecastCollection->slice($fcastOffset, $forecastPerPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'empty' => false,
                'summary' => [
                    'forecast_sum'        => round($forecastSum, 2),
                    'forecast_avg'        => round($forecastAvg, 2),
                    'forecast_max'        => round($forecastMax, 2),
                    'forecast_min'        => round($forecastMin, 2),
                    'growth_rate_pct'     => round($growthPct, 2),
                    'growth_direction'    => $growthPct >= 0 ? 'up' : 'down',
                    'confidence_score'    => round($confidenceScore, 2),
                    'confidence_level'    => $confidenceScore >= 85 ? 'high' : ($confidenceScore >= 70 ? 'medium' : 'low'),
                    'historical_avg'      => $historicalAvg,
                    'historical_days'     => $historical->count(),
                    'forecast_days'       => $forecastDays,
                ],
                'chart'       => $chart,
                'seasonality' => $seasonality,
                'historical'  => $histPage,
                'historical_pagination' => [
                    'current_page' => $historicalPage,
                    'last_page'    => $histLastPage,
                    'total'        => $histTotal,
                    'from'         => $histTotal > 0 ? $histOffset + 1 : 0,
                    'to'           => min($histOffset + $historicalPerPage, $histTotal),
                    'per_page'     => $historicalPerPage,
                    'page_param'   => 'historical_page',
                    'per_page_param' => 'historical_per_page',
                ],
                'forecast'    => $fcastPage,
                'forecast_pagination' => [
                    'current_page' => $forecastPage,
                    'last_page'    => $fcastLastPage,
                    'total'        => $fcastTotal,
                    'from'         => $fcastTotal > 0 ? $fcastOffset + 1 : 0,
                    'to'           => min($fcastOffset + $forecastPerPage, $fcastTotal),
                    'per_page'     => $forecastPerPage,
                    'page_param'   => 'forecast_page',
                    'per_page_param' => 'forecast_per_page',
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'forecast_days' => $forecastDays,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                ],
            ],
        ]);
    }
        
    /**
     * Calculate sales trends from historical data
     */
    private function calculateSalesTrends($historicalData)
    {
        if ($historicalData->count() < 2) {
            return (object)[
                'daily_growth' => 0,
                'weekly_growth' => 0,
                'monthly_growth' => 0,
                'trend_direction' => 'stable',
                'volatility' => 0
            ];
        }
        
        // ─── Daily Growth Rates ─────────────────────────────────────
        $dailyGrowthRates = [];
        for ($i = 1; $i < $historicalData->count(); $i++) {
            $previous = $historicalData[$i - 1]->daily_sales;
            $current = $historicalData[$i]->daily_sales;
            
            if ($previous > 0) {
                $dailyGrowthRates[] = (($current - $previous) / $previous) * 100;
            }
        }
        
        $dailyGrowth = count($dailyGrowthRates) > 0 
            ? array_sum($dailyGrowthRates) / count($dailyGrowthRates) 
            : 0;
        
        // ─── Weekly Growth ──────────────────────────────────────────
        $weeklyGrowth = 0;
        if ($historicalData->count() >= 14) {
            $lastWeek = $historicalData->slice(-7)->sum('daily_sales');
            $previousWeek = $historicalData->slice(-14, 7)->sum('daily_sales');
            
            if ($previousWeek > 0) {
                $weeklyGrowth = (($lastWeek - $previousWeek) / $previousWeek) * 100;
            }
        }
        
        // ─── Monthly Growth ─────────────────────────────────────────
        $monthlyGrowth = 0;
        if ($historicalData->count() >= 60) {
            $lastMonth = $historicalData->slice(-30)->sum('daily_sales');
            $previousMonth = $historicalData->slice(-60, 30)->sum('daily_sales');
            
            if ($previousMonth > 0) {
                $monthlyGrowth = (($lastMonth - $previousMonth) / $previousMonth) * 100;
            }
        }
        
        // ─── Trend Direction ────────────────────────────────────────
        $trendDirection = 'stable';
        if ($dailyGrowth > 2) {
            $trendDirection = 'upward';
        } elseif ($dailyGrowth < -2) {
            $trendDirection = 'downward';
        }
        
        // ─── Volatility ─────────────────────────────────────────────
        $dailySales = $historicalData->pluck('daily_sales')->toArray();
        $mean = array_sum($dailySales) / count($dailySales);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $dailySales)) / count($dailySales);
        $volatility = sqrt($variance);
        $normalizedVolatility = $mean > 0 ? ($volatility / $mean) * 100 : 0;
        
        return (object)[
            'daily_growth' => $dailyGrowth,
            'weekly_growth' => $weeklyGrowth,
            'monthly_growth' => $monthlyGrowth,
            'trend_direction' => $trendDirection,
            'volatility' => $normalizedVolatility
        ];
    }
    
    /**
     * Generate sales forecast for future days
     */
    private function generateForecast($historicalData, $trends, $forecastDays)
    {
        if ($historicalData->count() < 2) {
            return [];
        }
        
        // ─── Baseline Averages ──────────────────────────────────────
        $averageDailySales = $historicalData->avg('daily_sales') ?? 0;
        $averageDailyOrders = $historicalData->avg('order_count') ?? 0;
        $averageOrderValue = $historicalData->avg('average_order_value') ?? 0;
        
        // ─── Seasonality Factors ────────────────────────────────────
        $seasonalityFactors = [];
        foreach ($historicalData as $day) {
            $date = Carbon::parse($day->date);
            $dayOfWeek = $date->dayOfWeek;
            $seasonalityFactors[$dayOfWeek][] = $day->daily_sales;
        }
        
        $avgSeasonality = [];
        foreach ($seasonalityFactors as $dayOfWeek => $values) {
            $dayAverage = array_sum($values) / count($values);
            $avgSeasonality[$dayOfWeek] = $averageDailySales > 0 
                ? $dayAverage / $averageDailySales 
                : 1.0;
        }
        
        // ─── Generate Forecast ──────────────────────────────────────
        $forecast = [];
        $lastDate = $historicalData->isNotEmpty() 
            ? Carbon::parse($historicalData->last()->date) 
            : Carbon::now();
        
        $volatilityFactor = max(0.05, min(0.3, ($trends->volatility ?? 0) / 100));
        
        for ($i = 1; $i <= $forecastDays; $i++) {
            $forecastDate = $lastDate->copy()->addDays($i);
            $dayOfWeek = $forecastDate->dayOfWeek;
            
            $growthMultiplier = 1 + (($trends->daily_growth ?? 0) / 100);
            $seasonality = $avgSeasonality[$dayOfWeek] ?? 1.0;
            
            $forecastSales = $averageDailySales * $growthMultiplier * $seasonality;
            $forecastOrders = $averageDailyOrders * $growthMultiplier * $seasonality;
            $forecastAOV = $forecastOrders > 0 ? $forecastSales / $forecastOrders : $averageOrderValue;
            
            $confidenceMultiplier = $volatilityFactor;
            $confidenceLow = $forecastSales * (1 - $confidenceMultiplier);
            $confidenceHigh = $forecastSales * (1 + $confidenceMultiplier);
            
            $confidence = 'medium';
            if ($volatilityFactor < 0.1) $confidence = 'high';
            elseif ($volatilityFactor > 0.2) $confidence = 'low';
            
            $forecast[$forecastDate->format('Y-m-d')] = [
                'date' => $forecastDate->format('Y-m-d'),
                'day_name' => $forecastDate->format('l'),
                'forecast_sales' => max(0, $forecastSales),
                'forecast_orders' => max(0, $forecastOrders),
                'average_order_value' => $forecastAOV,
                'confidence_low' => max(0, $confidenceLow),
                'confidence_high' => max(0, $confidenceHigh),
                'confidence' => $confidence,
                'trend' => $trends->daily_growth ?? 0,
                'seasonality_factor' => $seasonality,
            ];
        }
        
        return $forecast;
    }
    
    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate($historicalData)
    {
        if ($historicalData->count() < 2) {
            return 0;
        }
        
        $halfCount = floor($historicalData->count() / 2);
        $firstHalf = $historicalData->slice(0, $halfCount);
        $secondHalf = $historicalData->slice($halfCount);
        
        $firstHalfAvg = $firstHalf->avg('daily_sales') ?? 0;
        $secondHalfAvg = $secondHalf->avg('daily_sales') ?? 0;
        
        if ($firstHalfAvg > 0) {
            return ($secondHalfAvg - $firstHalfAvg) / $firstHalfAvg;
        }
        
        return 0;
    }
    
    /**
     * Get day name from day of week number
     */
    private function getDayNameFromNumber($dayOfWeek)
    {
        $days = [
            1 => 'Sunday',
            2 => 'Monday',
            3 => 'Tuesday',
            4 => 'Wednesday',
            5 => 'Thursday',
            6 => 'Friday',
            7 => 'Saturday'
        ];
        
        return $days[$dayOfWeek] ?? 'Unknown';
    }


    /**
     * Inventory Sales Report (Sold vs Unsold)
     * Shows product sales performance, stock levels, and movement analysis
     */
    public function inventorySales(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('order_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId        = $request->get('location_id');
        $departmentId      = $request->get('department_id');
        $soldPerPage       = (int) $request->get('sold_per_page', 15);
        $unsoldPerPage     = (int) $request->get('unsold_per_page', 15);
        $deadStockPerPage  = (int) $request->get('dead_stock_per_page', 10);

        $isSingleShop = tenant_is_single_shop($tenantId);

        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.orders.inventory-sales', compact(
            'locations', 'departments',
            'startDate', 'endDate',
            'locationId', 'departmentId',
            'soldPerPage', 'unsoldPerPage', 'deadStockPerPage', 'isSingleShop'
        ));
    }

    public function inventorySalesData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('order_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId       = $request->get('location_id');
        $departmentId     = $request->get('department_id');
        $soldPerPage      = (int) $request->get('sold_per_page', 15);
        $unsoldPerPage    = (int) $request->get('unsold_per_page', 15);
        $deadStockPerPage = (int) $request->get('dead_stock_per_page', 10);

        $soldPage      = max(1, (int) $request->get('sold_page', 1));
        $unsoldPage    = max(1, (int) $request->get('unsold_page', 1));
        $deadStockPage = max(1, (int) $request->get('dead_stock_page', 1));

        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        // ── Data sources ────────────────────────────────────────
        $allProducts = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->get();

        $soldProductsCollection = $this->getSoldProducts(
            $tenantId, $startDate, $endDate, $locationId, $departmentId, $daysInPeriod, $isSingleShop
        );

        $soldProductIds = $soldProductsCollection->pluck('id')->toArray();

        $unsoldProductsCollection = $this->getUnsoldProducts(
            $allProducts, $soldProductIds, $tenantId, $isSingleShop, $locationId, $departmentId
        );

        // ── Metrics ────────────────────────────────────────────
        $totalInventoryValue = $this->calculateTotalInventoryValue($allProducts, $tenantId, $isSingleShop, $locationId, $departmentId);
        $soldRevenue         = (float) $soldProductsCollection->sum('revenue_generated');
        $soldCost            = (float) $soldProductsCollection->sum('total_cost_generated');
        $soldProfit          = $soldRevenue - $soldCost;
        $turnoverRate        = $totalInventoryValue > 0 ? ($soldRevenue / $totalInventoryValue) * 100 : 0;

        // ── Stock aging ────────────────────────────────────────
        $stockAging = $this->calculateStockAging($soldProductsCollection, $unsoldProductsCollection);

        // ── Dead stock ─────────────────────────────────────────
        $deadStockCollection = $unsoldProductsCollection
            ->filter(fn($p) => $p->current_stock > 10)->values();

        // ── Sold pagination ────────────────────────────────────
        $soldTotal    = $soldProductsCollection->count();
        $soldLastPage = max(1, (int) ceil($soldTotal / $soldPerPage));
        $soldPage     = min($soldPage, $soldLastPage);
        $soldOffset   = ($soldPage - 1) * $soldPerPage;

        $movementColors = ['Fast Mover' => 'success', 'Medium Mover' => 'warning', 'Slow Mover' => 'danger'];

        $soldRows = $soldProductsCollection->slice($soldOffset, $soldPerPage)->values()
            ->map(function ($p, $i) use ($soldOffset, $movementColors) {
                $revenue = (float) ($p->revenue_generated ?? 0);
                $cost    = (float) ($p->total_cost_generated ?? 0);
                $profit  = $revenue - $cost;
                $margin  = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
                $stockVal = (float) ($p->stock_value ?? ($p->current_stock * ($p->price ?? 0)));
                $mc = $movementColors[$p->movement_category ?? ''] ?? 'secondary';

                return [
                    'rank'                => $soldOffset + $i + 1,
                    'id'                  => $p->id,
                    'name'                => $p->name,
                    'sku'                 => $p->sku,
                    'quantity_sold'       => round((float) $p->quantity_sold, 2),
                    'revenue'             => round($revenue, 2),
                    'cost'                => round($cost, 2),
                    'profit'              => round($profit, 2),
                    'margin'              => round($margin, 2),
                    'current_stock'       => (int) $p->current_stock,
                    'stock_value'         => round($stockVal, 2),
                    'times_ordered'       => (int) ($p->times_ordered ?? 0),
                    'daily_sales_rate'    => round((float) ($p->daily_sales_rate ?? 0), 2),
                    'movement_category'   => $p->movement_category ?? 'Slow Mover',
                    'movement_color'      => $mc,
                    'last_sold_date'      => $p->last_sold_date
                        ? Carbon::parse($p->last_sold_date)->format('Y-m-d')
                        : null,
                ];
            });

        // ── Unsold pagination ──────────────────────────────────
        $unsoldTotal    = $unsoldProductsCollection->count();
        $unsoldLastPage = max(1, (int) ceil($unsoldTotal / $unsoldPerPage));
        $unsoldPage     = min($unsoldPage, $unsoldLastPage);
        $unsoldOffset   = ($unsoldPage - 1) * $unsoldPerPage;

        $unsoldRows = $unsoldProductsCollection->slice($unsoldOffset, $unsoldPerPage)->values()
            ->map(function ($p) {
                $stockClass = $p->current_stock <= 5 ? 'danger' : ($p->current_stock <= 10 ? 'warning' : 'info');
                $sv = (float) ($p->stock_value ?? ($p->current_stock * ($p->price ?? 0)));

                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'sku'           => $p->sku,
                    'current_stock' => (int) $p->current_stock,
                    'price'         => round((float) ($p->price ?? 0), 2),
                    'stock_value'   => round($sv, 2),
                    'stock_class'   => $stockClass,
                    'is_dead_stock' => $p->current_stock > 10,
                ];
            });

        // ── Dead stock pagination ──────────────────────────────
        $deadTotal    = $deadStockCollection->count();
        $deadLastPage = max(1, (int) ceil($deadTotal / $deadStockPerPage));
        $deadStockPage = min($deadStockPage, $deadLastPage);
        $deadOffset   = ($deadStockPage - 1) * $deadStockPerPage;

        $deadRows = $deadStockCollection->slice($deadOffset, $deadStockPerPage)->values()
            ->map(function ($p) {
                $sv = (float) ($p->stock_value ?? ($p->current_stock * ($p->price ?? 0)));
                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'sku'           => $p->sku,
                    'current_stock' => (int) $p->current_stock,
                    'price'         => round((float) ($p->price ?? 0), 2),
                    'stock_value'   => round($sv, 2),
                ];
            });

        // ── Movement breakdown ─────────────────────────────────
        $productMovement = $soldProductsCollection;
        $fastMovers   = $productMovement->where('movement_category', 'Fast Mover')->count();
        $mediumMovers = $productMovement->where('movement_category', 'Medium Mover')->count();
        $slowMovers   = $productMovement->where('movement_category', 'Slow Mover')->count();
        $totalMovers  = max($productMovement->count(), 1);

        // ── Sell-through ───────────────────────────────────────
        $soldCount       = $soldProductsCollection->count();
        $unsoldCount     = $unsoldProductsCollection->count();
        $totalProducts   = $soldCount + $unsoldCount;
        $sellThroughRate = $totalProducts > 0 ? ($soldCount / $totalProducts) * 100 : 0;
        $totalUnitsSold  = (float) $soldProductsCollection->sum('quantity_sold');
        $avgDailySales   = $daysInPeriod > 0 ? $totalUnitsSold / $daysInPeriod : 0;
        $soldMargin      = $soldRevenue > 0 ? ($soldProfit / $soldRevenue) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'is_single_shop'  => $isSingleShop,
                'days_in_period'  => $daysInPeriod,
                'summary' => [
                    'total_products'        => $totalProducts,
                    'sold_count'            => $soldCount,
                    'unsold_count'          => $unsoldCount,
                    'sell_through_rate'     => round($sellThroughRate, 2),
                    'turnover_rate'         => round($turnoverRate, 2),
                    'total_units_sold'      => round($totalUnitsSold, 2),
                    'avg_daily_sales'       => round($avgDailySales, 2),
                    'sold_revenue'          => round($soldRevenue, 2),
                    'sold_cost'             => round($soldCost, 2),
                    'sold_profit'           => round($soldProfit, 2),
                    'sold_margin'           => round($soldMargin, 2),
                    'total_inventory_value' => round($totalInventoryValue, 2),
                ],
                'movement' => [
                    'fast_movers'   => $fastMovers,
                    'medium_movers' => $mediumMovers,
                    'slow_movers'   => $slowMovers,
                    'total'         => $productMovement->count(),
                    'fast_pct'      => round(($fastMovers / $totalMovers) * 100, 2),
                    'medium_pct'    => round(($mediumMovers / $totalMovers) * 100, 2),
                    'slow_pct'      => round(($slowMovers / $totalMovers) * 100, 2),
                ],
                'dead_stock' => $deadRows,
                'dead_stock_pagination' => [
                    'current_page' => $deadStockPage,
                    'last_page'    => $deadLastPage,
                    'total'        => $deadTotal,
                    'from'         => $deadTotal > 0 ? $deadOffset + 1 : 0,
                    'to'           => min($deadOffset + $deadStockPerPage, $deadTotal),
                    'per_page'     => $deadStockPerPage,
                    'page_param'   => 'dead_stock_page',
                    'per_page_param' => 'dead_stock_per_page',
                ],
                'sold_products'   => $soldRows,
                'sold_pagination' => [
                    'current_page' => $soldPage,
                    'last_page'    => $soldLastPage,
                    'total'        => $soldTotal,
                    'from'         => $soldTotal > 0 ? $soldOffset + 1 : 0,
                    'to'           => min($soldOffset + $soldPerPage, $soldTotal),
                    'per_page'     => $soldPerPage,
                    'page_param'   => 'sold_page',
                    'per_page_param' => 'sold_per_page',
                ],
                'unsold_products'   => $unsoldRows,
                'unsold_pagination' => [
                    'current_page' => $unsoldPage,
                    'last_page'    => $unsoldLastPage,
                    'total'        => $unsoldTotal,
                    'from'         => $unsoldTotal > 0 ? $unsoldOffset + 1 : 0,
                    'to'           => min($unsoldOffset + $unsoldPerPage, $unsoldTotal),
                    'per_page'     => $unsoldPerPage,
                    'page_param'   => 'unsold_page',
                    'per_page_param' => 'unsold_per_page',
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'location_id'   => $locationId,
                    'department_id' => $departmentId,
                ],
            ],
        ]);
    }

    public function inventorySalesProduct(Request $request, $variantId)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('order reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');

        $variant = ProductVariant::where('tenant_id', $tenantId)
            ->with(['product:id,name'])
            ->find($variantId);

        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Variant not found'], 404);
        }

        // Lines sold in period (from completed orders)
        $orderItemsQuery = OrderItem::where('variant_id', $variantId)
            ->whereHas('order', function ($q) use ($tenantId, $startDate, $endDate, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                if ($locationId)   $q->where('location_id', $locationId);
                if ($departmentId) $q->where('department_id', $departmentId);
            })
            ->with(['order:id,order_number,created_at,customer_name,customer_id,status', 'order.customer:id,first_name,last_name']);

        $items = $orderItemsQuery->orderByDesc('created_at')->limit(50)->get();

        $recentSales = $items->map(function ($item) {
            $revenue = (float) ($item->total_price ?? 0);
            $cost    = (float) ($item->total_cost_price ?? 0);
            $qty     = (float) $item->quantity;

            return [
                'order_id'      => $item->order?->id,
                'order_number'  => $item->order?->order_number ?? '—',
                'order_date'    => $item->order?->created_at?->format('Y-m-d H:i'),
                'customer_name' => $item->order?->customer
                    ? trim(($item->order->customer->first_name ?? '') . ' ' . ($item->order->customer->last_name ?? ''))
                    : ($item->order?->customer_name ?? 'Guest'),
                'quantity'      => round($qty, 2),
                'unit_price'    => round((float) ($item->unit_price ?? 0), 2),
                'total_price'   => round($revenue, 2),
                'total_cost'    => round($cost, 2),
                'profit'        => round($revenue - $cost, 2),
            ];
        });

        $totalQty     = (float) $items->sum('quantity');
        $totalRevenue = (float) $items->sum('total_price');
        $totalCost    = (float) $items->sum('total_cost_price');
        $totalProfit  = $totalRevenue - $totalCost;

        // Current stock snapshot
        $currentStock = $this->getVariantStock($variant->id, $tenantId, $isSingleShop, $locationId, $departmentId);
        $price        = (float) ($variant->selling_price ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'id'           => $variant->id,
                'name'         => $variant->name,
                'sku'          => $variant->sku,
                'product_name' => $variant->product?->name,
                'current_stock'=> $currentStock,
                'price'        => round($price, 2),
                'stock_value'  => round($currentStock * $price, 2),
                'summary' => [
                    'total_units_sold' => round($totalQty, 2),
                    'total_revenue'    => round($totalRevenue, 2),
                    'total_cost'       => round($totalCost, 2),
                    'total_profit'     => round($totalProfit, 2),
                    'margin'           => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0,
                    'sale_count'       => $items->count(),
                ],
                'recent_sales' => $recentSales,
            ],
        ]);
    }
   
    
    /**
     * Get sold products from OrderItems (source of truth for sales)
     * 
     * Criteria for "sold" products:
     * 1. Order status = 'completed' (fully processed and inventory deducted)
     * 2. OR Order status = 'confirmed' AND invoice exists with status in ['sent', 'viewed', 'partially_paid', 'paid']
     * 3. OR Invoice exists with status in ['paid', 'partially_paid'] (POS invoices)
     */
    private function getSoldProducts($tenantId, $startDate, $endDate, $locationId, $departmentId, $daysInPeriod, $isSingleShop)
    {
        // ─── Get Orders that are completed (inventory already deducted) ──
        $completedOrderIds = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->pluck('id')
            ->toArray();

        // ─── Get Orders that are confirmed AND have sent invoices ──────
        $confirmedOrderIds = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'confirmed')
            ->whereHas('invoices', function ($query) {
                $query->whereIn('status', ['sent', 'viewed', 'partially_paid', 'paid']);
            })
            ->pluck('id')
            ->toArray();

        // ─── Get Orders from POS invoices (paid or partially paid) ────
        $posInvoiceOrderIds = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('order_id', '!=', null)
            ->whereIn('status', ['paid', 'partially_paid'])
            ->pluck('order_id')
            ->toArray();

        // ─── Merge all valid order IDs ─────────────────────────────────
        $validOrderIds = array_unique(array_merge(
            $completedOrderIds,
            $confirmedOrderIds,
            $posInvoiceOrderIds
        ));

        // ─── Build OrderItems Query ──────────────────────────────────
        $orderItems = OrderItem::whereHas('order', function ($query) use ($tenantId, $validOrderIds, $locationId, $departmentId) {
                $query->where('tenant_id', $tenantId)
                    ->whereIn('id', $validOrderIds);

                if ($locationId) {
                    $query->where('location_id', $locationId);
                }
                if ($departmentId) {
                    $query->where('department_id', $departmentId);
                }
            })
            ->with(['productVariant', 'order'])
            ->get();

        if ($orderItems->isEmpty()) {
            return collect();
        }

        // ─── Pre-load custom-priced inventory items for all variants ──
        //     Keyed: "{variant_id}|{location_id}|{department_id}"
        //     Used as the primary price source, matching POS behaviour.
        $variantIds = $orderItems->pluck('variant_id')->unique()->filter()->values()->toArray();

        $inventoryPricing = InventoryItems::where('tenant_id', $tenantId)
            ->whereIn('variant_id', $variantIds)
            ->where('has_custom_pricing', 1)
            ->get([
                'variant_id',
                'location_id',
                'department_id',
                'selling_price',
            ])
            ->keyBy(function ($row) {
                return "{$row->variant_id}|{$row->location_id}|{$row->department_id}";
            });

        // ─── Build Sold Products Collection ─────────────────────────
        return $orderItems
            ->groupBy('variant_id')
            ->map(function ($items, $variantId) use (
                $daysInPeriod,
                $tenantId,
                $isSingleShop,
                $locationId,
                $departmentId,
                $inventoryPricing
            ) {
                $firstItem = $items->first();
                $variant   = $firstItem->productVariant;

                if (! $variant) {
                    return null;
                }

                // ── Movement category ────────────────────────────────
                $dailySalesRate = $items->sum('quantity') / max($daysInPeriod, 1);

                if ($dailySalesRate >= 1) {
                    $movementCategory = 'Fast Mover';
                } elseif ($dailySalesRate >= 0.1) {
                    $movementCategory = 'Medium Mover';
                } else {
                    $movementCategory = 'Slow Mover';
                }

                // ── Current stock (reads quantity_allocated) ────────
                $currentStock = $this->getVariantStock(
                    $variant->id,
                    $tenantId,
                    $isSingleShop,
                    $locationId,
                    $departmentId
                );

                // ── Resolve unit price (selling_price only) ─────────
                $unitPrice = 0.0;

                if (! $isSingleShop) {
                    // Prefer the custom-priced inventory row for this
                    // order's location/department, then any custom row
                    // for this variant as a fallback.
                    $scopedKey = $locationId && $departmentId
                        ? "{$variant->id}|{$locationId}|{$departmentId}"
                        : null;

                    $pricedItem = $scopedKey
                        ? $inventoryPricing->get($scopedKey)
                        : null;

                    if (! $pricedItem) {
                        $pricedItem = $inventoryPricing
                            ->first(fn($row) => $row->variant_id === $variant->id);
                    }

                    if ($pricedItem) {
                        $unitPrice = (float) ($pricedItem->selling_price ?? 0);
                    }
                }

                // Fall back to the variant's own selling price
                if ($unitPrice <= 0) {
                    $unitPrice = (float) ($variant->selling_price ?? 0);
                }

                // ── Cost total (frozen pricing via resolveLinePricing) ─
                $totalCost = 0.0;
                foreach ($items as $item) {
                    [$unitCost] = $this->resolveLinePricing($item);
                    $totalCost += $unitCost * $item->quantity;
                }

                return (object) [
                    'id'                    => $variant->id,
                    'sku'                   => $variant->sku ?? 'N/A',
                    'name'                  => $variant->name ?? 'Unknown',
                    'image_url'             => $variant->image_url ?? null,

                    'price'                 => $unitPrice,
                    'current_stock'         => $currentStock,
                    'stock_value'           => $currentStock * $unitPrice,

                    'quantity_sold'         => $items->sum('quantity'),
                    'total_cost_generated'  => $totalCost,
                    'revenue_generated'     => $items->sum('total_price'),
                    'average_selling_price' => $items->avg('unit_price') ?? 0,
                    'times_ordered'         => $items->unique('order_id')->count(),
                    'last_sold_date'        => $items->max('created_at'),
                    'daily_sales_rate'      => $dailySalesRate,
                    'movement_category'     => $movementCategory,
                ];
            })
            ->filter()
            ->sortByDesc('quantity_sold')
            ->values();
    }
        
    /**
     * Get unsold products
     * Products that:
     * 1. Have stock > 0
     * 2. Were NOT sold in the period (not in soldProductIds)
     * 3. Are NOT in orders with status 'confirmed' that might be pending
     */
    private function getUnsoldProducts($allProducts, $soldProductIds, $tenantId, $isSingleShop, $locationId, $departmentId)
    {
        // ─── Get products that are in confirmed but not yet completed orders ──
        $confirmedOrderProductIds = OrderItem::whereHas('order', function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->where('status', 'confirmed');
            })
            ->pluck('variant_id')
            ->unique()
            ->toArray();

        
        return $allProducts
            ->filter(function($product) use ($soldProductIds, $confirmedOrderProductIds, $isSingleShop, $locationId, $departmentId) {
                // Skip if product was sold in the period
                if (in_array($product->id, $soldProductIds)) {
                    return false;
                }
                
                // Skip if product is in confirmed orders (pending sale)
                if (in_array($product->id, $confirmedOrderProductIds)) {
                    return false;
                }
                
                // Only include products with stock > 0
                $stock = $this->getVariantStock($product->id, $product->tenant_id, $isSingleShop, $locationId, $departmentId);
                return $stock > 0;
            })
            ->map(function ($product) use ($isSingleShop, $locationId, $departmentId, $tenantId) {

                $currentStock = $this->getVariantStock(
                    $product->id, $product->tenant_id, $isSingleShop, $locationId, $departmentId
                );

                // ── Resolve the unit price ────────────────────────────────
                $unitPrice = 0.0;

                if (! $isSingleShop) {
                    $inventoryQuery = InventoryItems::where('tenant_id', $tenantId)
                        ->where('variant_id', $product->id)
                        ->where('has_custom_pricing', 1);

                    if ($locationId)   $inventoryQuery->where('location_id', $locationId);
                    if ($departmentId) $inventoryQuery->where('department_id', $departmentId);

                    $pricedItem = $inventoryQuery
                        ->orderByDesc('selling_price')
                        ->first();

                    if ($pricedItem) {
                        $unitPrice = (float) ($pricedItem->selling_price ?? 0);
                    }
                }

                // Fall back to the variant
                if ($unitPrice <= 0) {
                    $unitPrice = (float) ($product->selling_price ?? 0);
                }

                return (object) [
                    'id'            => $product->id,
                    'sku'           => $product->sku ?? 'N/A',
                    'name'          => $product->name ?? 'Unknown',
                    'price'         => $unitPrice,
                    'current_stock' => $currentStock,
                    'stock_value'   => $currentStock * $unitPrice,
                ];
            })
            ->sortByDesc('current_stock')
            ->values();
    }
    
    private function getVariantStock($variantId, $tenantId, $isSingleShop, $locationId = null, $departmentId = null)
    {
        if ($isSingleShop) {
            $latestLog = SingleShopInventoryLog::where('variant_id', $variantId)
                ->where('tenant_id', $tenantId)
                ->latest('created_at')
                ->first();

            return $latestLog ? (int) $latestLog->quantity_after : 0;
        }

        // ─── Multi-shop: quantity_allocated is what POS depletes ────
        $query = InventoryItems::where('variant_id', $variantId)
            ->where('tenant_id', $tenantId);

        if ($locationId)   $query->where('location_id', $locationId);
        if ($departmentId) $query->where('department_id', $departmentId);

        return (int) $query->sum('quantity_allocated');
    }
    
    /**
     * Calculate total inventory value
     */
    private function calculateTotalInventoryValue($allProducts, $tenantId, $isSingleShop, $locationId, $departmentId)
    {
        $total = 0;
        
        foreach ($allProducts as $product) {
            $stock = $this->getVariantStock($product->id, $product->tenant_id, $isSingleShop, $locationId, $departmentId);
            $total += $stock * ($product->selling_price ?? 0);
        }
        
        return $total;
    }

    
    /**
     * Calculate stock aging
     */
    private function calculateStockAging($soldProducts, $unsoldProducts)
    {
        $agingCategories = [
            '0-30 days' => ['sold' => 0, 'unsold' => 0],
            '31-60 days' => ['sold' => 0, 'unsold' => 0],
            '61-90 days' => ['sold' => 0, 'unsold' => 0],
            '91+ days' => ['sold' => 0, 'unsold' => 0],
        ];
        
        foreach ($soldProducts as $product) {
            if (isset($product->last_sold_date) && $product->last_sold_date) {
                $daysSinceSold = Carbon::parse($product->last_sold_date)->diffInDays(Carbon::now());
                
                if ($daysSinceSold <= 30) {
                    $agingCategories['0-30 days']['sold'] += $product->current_stock;
                } elseif ($daysSinceSold <= 60) {
                    $agingCategories['31-60 days']['sold'] += $product->current_stock;
                } elseif ($daysSinceSold <= 90) {
                    $agingCategories['61-90 days']['sold'] += $product->current_stock;
                } else {
                    $agingCategories['91+ days']['sold'] += $product->current_stock;
                }
            }
        }
        
        // For unsold products, consider them as old stock
        $unsoldStock = $unsoldProducts->sum('current_stock');
        $agingCategories['91+ days']['unsold'] = $unsoldStock;
        
        return (object)[
            'fast_movers' => $soldProducts->where('movement_category', 'Fast Mover')->count(),
            'medium_movers' => $soldProducts->where('movement_category', 'Medium Mover')->count(),
            'slow_movers' => $soldProducts->where('movement_category', 'Slow Mover')->count(),
            'dead_stock' => $unsoldProducts->filter(function($product) {
                return $product->current_stock > 10;
            })->count(),
            'low_stock' => $unsoldProducts->filter(function($product) {
                return $product->current_stock <= 5 && $product->current_stock > 0;
            })->count(),
            'aging_categories' => $agingCategories,
        ];
    }


    
    /**
     * Paginate a collection
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
                'query' => request()->except($pageName, 'sold_page', 'unsold_page', 'dead_stock_page')
            ]
        );
    }


    public function getIngredients($variantId)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // ✅ First find the variant
        $variant = ProductVariant::where('tenant_id', $tenantId)
            ->where('id', $variantId)
            ->with(['product.recipe.ingredients.ingredientVariant', 'product.recipe.ingredients.unit'])
            ->first();

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.variant_not_found'),
            ]);
        }

        $product = $variant->product;

        if (!$product || !$product->recipe) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.no_recipe_found'),
            ]);
        }

        $ingredients = $product->recipe->ingredients->map(function($ingredient) {
            return [
                'variant_name' => $ingredient->ingredientVariant->name ?? 'Unknown',
                'quantity_required' => $ingredient->quantity_required,
                'unit' => $ingredient->unit?->name ?? 'N/A',
            ];
        });

        return response()->json([
            'success' => true,
            'ingredients' => $ingredients,
        ]);
    }

    /**
     * Get serial numbers for a variant
     */
    public function getVariantSerials($variantId)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view variant')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }
        

        $variant = ProductVariant::where('tenant_id', $tenantId)
            ->where('id', $variantId)
            ->first();

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found'
            ], 404);
        }

        // Get all serials for this variant with location and department
        $serials = SerialNumber::with(['location', 'department'])
            ->where('variant_id', $variantId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total' => $serials->count(),
            'available' => $serials->where('status', SerialNumber::STATUS_AVAILABLE)->count(),
            'sold' => $serials->where('status', SerialNumber::STATUS_SOLD)->count(),
            'reserved' => $serials->where('status', SerialNumber::STATUS_RESERVED)->count(),
            'returned' => $serials->where('status', SerialNumber::STATUS_RETURNED)->count(),
            'lost' => $serials->where('status', SerialNumber::STATUS_LOST)->count(),
            'damaged' => $serials->where('status', SerialNumber::STATUS_DAMAGED)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'variant' => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                ],
                'serials' => $serials->map(function($serial) {
                    return [
                        'id' => $serial->id,
                        'serial_number' => $serial->serial_number,
                        'status' => $serial->status,
                        'status_label' => $serial->status_label,
                        'location_name' => $serial->location ? $serial->location->name : 'N/A',
                        'department_name' => $serial->department ? $serial->department->name : 'N/A',
                        'order_id' => $serial->order_id,
                        'sold_at' => $serial->sold_at,
                        'created_at' => $serial->created_at,
                        'notes' => $serial->notes,
                    ];
                }),
                'summary' => $summary,
            ]
        ]);
    }


}