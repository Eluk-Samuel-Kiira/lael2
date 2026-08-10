<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Order,InventoryTransactions, SingleShopInventoryLog, Invoice };
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

class OrderReportsController extends Controller
{
    // Order Summary Report
    public function summary(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Default date range: Last 30 days
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        
        // Validate and format dates
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Get filter parameters
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType = $request->get('order_type');
        $orderStatus = $request->get('order_status');
        
        // Get all orders with filters using Eloquent
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['location', 'department']);
        
        // Apply filters
        if ($locationId) {
            $ordersQuery->where('location_id', $locationId);
        }
        
        if ($departmentId) {
            $ordersQuery->where('department_id', $departmentId);
        }
        
        if ($orderType) {
            $ordersQuery->where('type', $orderType);
        }
        
        if ($orderStatus) {
            $ordersQuery->where('status', $orderStatus);
        }
        
        $allOrders = $ordersQuery->get();
        
        // Get summary statistics
        $summary = $this->getOrderSummary($allOrders);
        
        // Get daily breakdown for chart
        $dailyBreakdown = $this->getDailyBreakdown($allOrders);
        
        // Get orders by hour
        $hourlyBreakdown = $this->getHourlyBreakdown($allOrders);
        
        // Get orders by type with percentages
        $typeBreakdown = $this->getTypeBreakdown($allOrders);
        
        // Get orders by status with percentages
        $statusBreakdown = $this->getStatusBreakdown($allOrders);
        
        // Format daily breakdown with day names
        $dailyBreakdown = $dailyBreakdown->map(function($item) {
            $date = Carbon::parse($item['date']);
            $item['day_name'] = $date->format('l');
            $item['is_weekend'] = in_array($item['day_name'], ['Saturday', 'Sunday']);
            $item['formatted_date'] = $date->format('M d, Y');
            return (object)$item;
        })->values();
        
        // Format hourly breakdown
        $hourlyBreakdown = $hourlyBreakdown->map(function($item) {
            $hour = (int)$item['hour'];
            $item['hour_formatted'] = date('g:00 A', mktime($hour, 0, 0));
            $item['period'] = $this->getPeriodLabel($hour);
            return (object)$item;
        })->sortBy('hour')->values();
        
        // Add colors to breakdowns
        $typeBreakdown = $typeBreakdown->map(function($item) {
            $item['color'] = $this->getOrderTypeColor($item['type']);
            return (object)$item;
        });
        
        $statusBreakdown = $statusBreakdown->map(function($item) {
            $item['color'] = $this->getOrderStatusColor($item['status']);
            return (object)$item;
        });
        
        // Get filter options
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get();
        
        // For display
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        return view('reports.orders.summary', compact(
            'summary',
            'dailyBreakdown',
            'hourlyBreakdown',
            'typeBreakdown',
            'statusBreakdown',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'displayStartDate',
            'displayEndDate',
            'locationId',
            'departmentId',
            'orderType',
            'orderStatus'
        ));
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
        
        // ─── Date Range ──────────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // ─── Filter Parameters ──────────────────────────────────────
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType = $request->get('order_type');
        $minProfit = $request->get('min_profit');
        $maxProfit = $request->get('max_profit');
        $perPage = $request->get('per_page', 25);
        
        // ─── Build Orders Query ────────────────────────────────────
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->with(['items.productVariant', 'location', 'department', 'customer']);
        
        // Apply location filter
        if ($locationId) {
            $ordersQuery->where('location_id', $locationId);
        }
        
        // Apply department filter
        if ($departmentId) {
            $ordersQuery->where('department_id', $departmentId);
        }
        
        // Apply order type filter
        if ($orderType && $orderType !== 'all') {
            $ordersQuery->where('type', $orderType);
        }
        
        $orders = $ordersQuery->get();
        
        // ─── Calculate Profit for Each Order ──────────────────────
        $profitData = $orders->map(function($order) {
            $totalRevenue = $order->total;
            $totalDiscount = $order->discount_total ?? 0;
            $totalTax = $order->tax_total ?? 0;
            
            // Calculate cost of goods sold (COGS) from order items
            $totalCost = 0;
            $itemProfits = [];
            
            foreach ($order->items as $item) {
                $variant = $item->productVariant;
                
                if ($variant) {
                    // Cost price from variant
                    $unitCost = $variant->grand_total_cost_price ?? 0;
                    $quantity = $item->quantity;
                    $itemCost = $unitCost * $quantity;
                    $totalCost += $itemCost;
                    
                    // Calculate item profit
                    $itemRevenue = $item->total_price ?? 0;
                    $itemProfit = $itemRevenue - $itemCost;
                    $itemProfitMargin = $itemRevenue > 0 ? ($itemProfit / $itemRevenue) * 100 : 0;
                    
                    $itemProfits[] = (object)[
                        'variant_name' => $variant->name ?? 'Unknown',
                        'sku' => $variant->sku ?? 'N/A',
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'unit_price' => $item->unit_price ?? 0,
                        'total_cost' => $itemCost,
                        'total_revenue' => $itemRevenue,
                        'profit' => $itemProfit,
                        'profit_margin' => $itemProfitMargin,
                    ];
                }
            }
            
            // Calculate net profit (revenue - cost - discount)
            $grossProfit = $totalRevenue - $totalCost;
            $netProfit = $grossProfit - $totalDiscount;
            $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;
            
            return (object)[
                'order_id' => $order->id,
                'order_number' => $order->order_number ?? $order->id,
                'order_type' => $order->type ?? 'sale',
                'created_at' => $order->created_at,
                'location' => $order->location->name ?? 'N/A',
                'department' => $order->department->name ?? 'N/A',
                'customer' => $order->customer->full_name ?? ($order->customer_name ?? 'Guest'),
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin,
                'item_count' => $order->items->count(),
                'item_profits' => $itemProfits,
            ];
        })->sortByDesc('net_profit')->values();
        
        // ─── Apply Profit Filters ──────────────────────────────────
        if ($minProfit && is_numeric($minProfit)) {
            $profitData = $profitData->filter(function($order) use ($minProfit) {
                return $order->net_profit >= (float)$minProfit;
            });
        }
        
        if ($maxProfit && is_numeric($maxProfit)) {
            $profitData = $profitData->filter(function($order) use ($maxProfit) {
                return $order->net_profit <= (float)$maxProfit;
            });
        }
        
        $profitData = $profitData->values();
        
        // ─── Summary Statistics ────────────────────────────────────
        $summary = (object)[
            'total_orders' => $profitData->count(),
            'total_revenue' => $profitData->sum('total_revenue'),
            'total_cost' => $profitData->sum('total_cost'),
            'total_discount' => $profitData->sum('total_discount'),
            'total_gross_profit' => $profitData->sum('gross_profit'),
            'total_net_profit' => $profitData->sum('net_profit'),
            'average_net_profit' => $profitData->count() > 0 ? $profitData->sum('net_profit') / $profitData->count() : 0,
            'average_profit_margin' => $profitData->count() > 0 ? $profitData->avg('profit_margin') : 0,
            'max_profit_order' => $profitData->first()->order_number ?? 'N/A',
            'max_profit_amount' => $profitData->first()->net_profit ?? 0,
            'profitable_orders' => $profitData->filter(function($order) {
                return $order->net_profit > 0;
            })->count(),
            'loss_making_orders' => $profitData->filter(function($order) {
                return $order->net_profit < 0;
            })->count(),
            'break_even_orders' => $profitData->filter(function($order) {
                return $order->net_profit == 0;
            })->count(),
        ];
        
        // ─── Profit by Location ────────────────────────────────────
        $profitByLocation = $profitData->groupBy('location')
            ->map(function($orders, $location) {
                return (object)[
                    'location' => $location,
                    'order_count' => $orders->count(),
                    'total_revenue' => $orders->sum('total_revenue'),
                    'total_cost' => $orders->sum('total_cost'),
                    'total_net_profit' => $orders->sum('net_profit'),
                    'average_margin' => $orders->avg('profit_margin') ?? 0,
                ];
            })
            ->sortByDesc('total_net_profit')
            ->values();
        
        // ─── Profit by Department ──────────────────────────────────
        $profitByDepartment = $profitData->groupBy('department')
            ->map(function($orders, $department) {
                return (object)[
                    'department' => $department,
                    'order_count' => $orders->count(),
                    'total_revenue' => $orders->sum('total_revenue'),
                    'total_cost' => $orders->sum('total_cost'),
                    'total_net_profit' => $orders->sum('net_profit'),
                    'average_margin' => $orders->avg('profit_margin') ?? 0,
                ];
            })
            ->sortByDesc('total_net_profit')
            ->values();
        
        // ─── Top Products by Profit ───────────────────────────────
        $productProfits = collect();
        foreach ($profitData as $order) {
            foreach ($order->item_profits as $item) {
                $key = $item->sku;
                if ($productProfits->has($key)) {
                    $existing = $productProfits->get($key);
                    $existing->quantity_sold += $item->quantity;
                    $existing->total_revenue += $item->total_revenue;
                    $existing->total_cost += $item->total_cost;
                    $existing->total_profit += $item->profit;
                } else {
                    $productProfits->put($key, (object)[
                        'sku' => $item->sku,
                        'variant_name' => $item->variant_name,
                        'quantity_sold' => $item->quantity,
                        'total_revenue' => $item->total_revenue,
                        'total_cost' => $item->total_cost,
                        'total_profit' => $item->profit,
                        'profit_margin' => $item->profit_margin,
                    ]);
                }
            }
        }
        
        $topProducts = $productProfits->sortByDesc('total_profit')->take(10)->values();
        $worstProducts = $productProfits->sortBy('total_profit')->take(10)->values();
        
        // ─── Daily Profit Trend ────────────────────────────────────
        $dailyProfitTrend = $profitData->groupBy(function($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function($orders, $date) {
            return (object)[
                'date' => $date,
                'order_count' => $orders->count(),
                'total_revenue' => $orders->sum('total_revenue'),
                'total_cost' => $orders->sum('total_cost'),
                'total_net_profit' => $orders->sum('net_profit'),
                'average_margin' => $orders->avg('profit_margin') ?? 0,
            ];
        })->sortKeys()->values();
        
        // ─── Pagination ─────────────────────────────────────────────
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min($perPage, max($profitData->count(), 1));
        $paginatedData = $profitData->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $profitDataPaginated = new LengthAwarePaginator(
            $paginatedData,
            $profitData->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        
        // ─── Get Filter Options ─────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // ─── Return View ────────────────────────────────────────────
        return view('reports.orders.profit-analysis', compact(
            'profitData',
            'profitDataPaginated',
            'summary',
            'profitByLocation',
            'profitByDepartment',
            'topProducts',
            'worstProducts',
            'dailyProfitTrend',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'orderType',
            'minProfit',
            'maxProfit',
            'perPage'
        ));
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
        
        // ─── Date Range ──────────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // ─── Filter Parameters ──────────────────────────────────────
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $minSpent = $request->get('min_spent');
        $maxSpent = $request->get('max_spent');
        $minOrders = $request->get('min_orders');
        $maxOrders = $request->get('max_orders');
        $customerType = $request->get('customer_type'); // 'registered', 'guest', 'all'
        $segmentFilter = $request->get('segment_filter'); // 'new', 'returning', 'regular', 'vip'
        $perPage = $request->get('per_page', 25);
        
        // ─── Build Orders Query ────────────────────────────────────
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['customer', 'location', 'department']);
        
        // Apply customer type filter
        if ($customerType === 'registered') {
            $ordersQuery->whereNotNull('customer_id');
        } elseif ($customerType === 'guest') {
            $ordersQuery->whereNull('customer_id')->whereNotNull('customer_name');
        }
        // 'all' - include both registered and guest
        
        // Apply location filter
        if ($locationId) {
            $ordersQuery->where('location_id', $locationId);
        }
        
        // Apply department filter
        if ($departmentId) {
            $ordersQuery->where('department_id', $departmentId);
        }
        
        $allOrders = $ordersQuery->get();
        
        // ─── Group by Customer ─────────────────────────────────────
        $customerSalesRaw = $allOrders->groupBy(function($order) {
            // Group by customer_id if exists, otherwise use customer_name as identifier
            return $order->customer_id ?? 'guest_' . $order->customer_name;
        })->map(function($customerOrders, $key) {
            $firstOrder = $customerOrders->first();
            $customer = $firstOrder->customer;
            $isGuest = is_null($firstOrder->customer_id);
            
            $totalSpent = $customerOrders->sum('total');
            $totalTax = $customerOrders->sum('tax_total');
            $totalDiscount = $customerOrders->sum('discount_total');
            $orderCount = $customerOrders->count();
            
            // Get first and last order dates
            $sortedOrders = $customerOrders->sortBy('created_at');
            $firstOrderDate = $sortedOrders->first()->created_at;
            $lastOrder = $sortedOrders->last();
            
            // Calculate average order value
            $averageOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0;
            
            // Get max and min order values
            $maxOrderValue = $customerOrders->max('total') ?? 0;
            $minOrderValue = $customerOrders->min('total') ?? 0;
            
            // Calculate customer lifetime value (CLV)
            $clv = $totalSpent;
            
            // Calculate recency (days since last purchase)
            $daysSinceLastPurchase = $lastOrder->created_at ? Carbon::now()->diffInDays($lastOrder->created_at) : null;
            
            // Determine customer segment
            $segment = $this->getCustomerSegment($orderCount, $totalSpent);
            
            return (object)[
                'id' => $isGuest ? null : $firstOrder->customer_id,
                'customer_id' => $firstOrder->customer_id,
                'is_guest' => $isGuest,
                'customer_name' => $isGuest ? ($firstOrder->customer_name ?? 'Guest') : ($customer->full_name ?? 'Unknown'),
                'first_name' => $isGuest ? null : ($customer->first_name ?? ''),
                'last_name' => $isGuest ? null : ($customer->last_name ?? ''),
                'full_name' => $isGuest ? ($firstOrder->customer_name ?? 'Guest') : trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                'email' => $isGuest ? null : ($customer->email ?? ''),
                'phone' => $isGuest ? null : ($customer->phone ?? ''),
                'city' => $isGuest ? null : ($customer->city ?? ''),
                'order_count' => $orderCount,
                'total_spent' => $totalSpent,
                'total_tax' => $totalTax,
                'total_discount' => $totalDiscount,
                'average_order_value' => $averageOrderValue,
                'max_order_value' => $maxOrderValue,
                'min_order_value' => $minOrderValue,
                'first_order_date' => $firstOrderDate,
                'last_order_date' => $lastOrder->created_at ?? null,
                'last_order_amount' => $lastOrder->total ?? 0,
                'clv' => $clv,
                'days_since_last_purchase' => $daysSinceLastPurchase,
                'segment' => $segment['label'],
                'segment_color' => $segment['color'],
                'segment_icon' => $segment['icon'],
            ];
        })
        ->sortByDesc('total_spent')
        ->values();
        
        // ─── Apply Filters ──────────────────────────────────────────
        $customerSales = $customerSalesRaw;
        
        if ($minSpent && is_numeric($minSpent)) {
            $customerSales = $customerSales->filter(function($customer) use ($minSpent) {
                return $customer->total_spent >= (float)$minSpent;
            });
        }
        
        if ($maxSpent && is_numeric($maxSpent)) {
            $customerSales = $customerSales->filter(function($customer) use ($maxSpent) {
                return $customer->total_spent <= (float)$maxSpent;
            });
        }
        
        if ($minOrders && is_numeric($minOrders)) {
            $customerSales = $customerSales->filter(function($customer) use ($minOrders) {
                return $customer->order_count >= (int)$minOrders;
            });
        }
        
        if ($maxOrders && is_numeric($maxOrders)) {
            $customerSales = $customerSales->filter(function($customer) use ($maxOrders) {
                return $customer->order_count <= (int)$maxOrders;
            });
        }
        
        // Apply segment filter
        if ($segmentFilter) {
            $customerSales = $customerSales->filter(function($customer) use ($segmentFilter) {
                return $customer->segment === $segmentFilter;
            });
        }
        
        // Reset collection keys
        $customerSales = $customerSales->values();
        
        // ─── Calculate Customer Loyalty Segments ──────────────────
        $customerSegments = (object)[
            'new' => $customerSales->filter(function($customer) {
                return $customer->segment === 'New';
            })->count(),
            'returning' => $customerSales->filter(function($customer) {
                return $customer->segment === 'Returning';
            })->count(),
            'regular' => $customerSales->filter(function($customer) {
                return $customer->segment === 'Regular';
            })->count(),
            'vip' => $customerSales->filter(function($customer) {
                return $customer->segment === 'VIP';
            })->count(),
        ];
        
        // ─── Top Customers for Chart ──────────────────────────────
        $topCustomers = $customerSales->take(10);
        
        // ─── Calculate Totals ──────────────────────────────────────
        $totalSpentAll = $customerSales->sum('total_spent');
        $totalOrdersAll = $customerSales->sum('order_count');
        $totalTaxAll = $customerSales->sum('total_tax');
        $totalDiscountAll = $customerSales->sum('total_discount');
        
        // ─── Summary Statistics ────────────────────────────────────
        $summary = (object)[
            'total_customers' => $customerSales->count(),
            'total_orders' => $totalOrdersAll,
            'total_revenue' => $totalSpentAll,
            'total_tax' => $totalTaxAll,
            'total_discount' => $totalDiscountAll,
            'average_per_customer' => $customerSales->count() > 0 ? $totalSpentAll / $customerSales->count() : 0,
            'average_orders_per_customer' => $customerSales->count() > 0 ? $totalOrdersAll / $customerSales->count() : 0,
            'average_order_value' => $totalOrdersAll > 0 ? $totalSpentAll / $totalOrdersAll : 0,
            'registered_customers' => $customerSales->filter(function($c) { return !$c->is_guest; })->count(),
            'guest_customers' => $customerSales->filter(function($c) { return $c->is_guest; })->count(),
        ];
        
        // ─── Add Percentage to Each Customer ──────────────────────
        $customerSales = $customerSales->map(function($customer) use ($totalSpentAll) {
            $customer->percentage = $totalSpentAll > 0 ? ($customer->total_spent / $totalSpentAll) * 100 : 0;
            return $customer;
        });
        
        // ─── Get Filter Options ────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get();
        
        // ─── Pagination ─────────────────────────────────────────────
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min($perPage, max($customerSales->count(), 1));
        $paginatedCustomers = $customerSales->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $customerSalesPaginated = new LengthAwarePaginator(
            $paginatedCustomers,
            $customerSales->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        
        // ─── Return View ────────────────────────────────────────────
        return view('reports.orders.by-customer', compact(
            'customerSales',
            'customerSalesPaginated',
            'customerSegments',
            'topCustomers',
            'summary',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'minSpent',
            'maxSpent',
            'minOrders',
            'maxOrders',
            'customerType',
            'segmentFilter',
            'perPage',
            'totalSpentAll'
        ));
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
        
        // ─── Check if Single Shop or Multi-Shop ──────────────────────
        $isSingleShop = tenant_is_single_shop($tenantId);
        
        // ─── Date Range ──────────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // ─── Filter Parameters ──────────────────────────────────────
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $minQuantity = $request->get('min_quantity');
        $maxQuantity = $request->get('max_quantity');
        $minRevenue = $request->get('min_revenue');
        $maxRevenue = $request->get('max_revenue');
        $minProfit = $request->get('min_profit');
        $velocityFilter = $request->get('velocity_filter');
        $perPage = $request->get('per_page', 25);
        
        // ─── Build Order Items Query ──────────────────────────────
        $query = OrderItem::with(['order', 'productVariant'])
            ->whereHas('order', function($q) use ($tenantId, $startDate, $endDate, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId)
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
                
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
            });
        
        // Get all order items
        $orderItems = $query->get();
        
        // ─── Get Variant IDs from Order Items ──────────────────────
        $variantIds = $orderItems->pluck('variant_id')->unique()->filter()->toArray();
        
        // ─── Get All Variants with Stock Information ──────────────
        $variants = ProductVariant::whereIn('id', $variantIds)
            ->with(['product'])
            ->get()
            ->keyBy('id');
        
        // ─── Get Stock Information Based on Shop Type ──────────────
        $variantStockMap = $this->getVariantStockMap($variantIds, $tenantId, $isSingleShop, $locationId, $departmentId);
        
        // ─── Group by Variant and Calculate Statistics ─────────────
        $productSalesRaw = $orderItems->groupBy('variant_id')
            ->map(function($items, $variantId) use ($variants, $variantStockMap) {
                $variant = $variants[$variantId] ?? null;
                
                if (!$variant) {
                    return null;
                }
                
                $totalQuantity = $items->sum('quantity');
                $totalRevenue = $items->sum('total_price');
                $totalTax = $items->sum('tax_amount');
                $totalDiscount = $items->sum('discount');
                $orderCount = $items->pluck('order_id')->unique()->count();
                $avgSellingPrice = $totalQuantity > 0 ? $totalRevenue / $totalQuantity : 0;
                
                // Get last sold date
                $lastSold = $items->sortByDesc(function($item) {
                    return $item->order->created_at ?? null;
                })->first();
                
                // ─── Get Current Stock ──────────────────────────────────
                $currentStock = $variantStockMap[$variantId] ?? 0;
                
                // ─── Profit Metrics ────────────────────────────────────
                $unitCost = $variant->grand_total_cost_price ?? 0;
                $unitPrice = $variant->selling_price ?? 0;
                $discountPrice = $variant->discount_selling_price ?? $unitPrice;
                $profitPerUnit = $discountPrice - $unitCost;
                $profitMargin = $discountPrice > 0 ? ($profitPerUnit / $discountPrice) * 100 : 0;
                $totalProfit = $profitPerUnit * $totalQuantity;
                
                return (object)[
                    'variant_id' => $variantId,
                    'sku' => $variant->sku ?? 'N/A',
                    'variant_name' => $variant->name ?? 'Unknown',
                    'product_name' => $variant->product->name ?? $variant->name ?? 'Unknown',
                    'current_price' => $unitPrice,
                    'discount_price' => $discountPrice,
                    'current_stock' => $currentStock,
                    'unit_cost' => $unitCost,
                    'profit_per_unit' => $profitPerUnit,
                    'profit_margin' => $profitMargin,
                    'total_profit' => $totalProfit,
                    'total_quantity_sold' => $totalQuantity,
                    'total_revenue' => $totalRevenue,
                    'total_tax' => $totalTax,
                    'total_discount' => $totalDiscount,
                    'average_selling_price' => $avgSellingPrice,
                    'order_count' => $orderCount,
                    'last_sold_date' => $lastSold->order->created_at ?? null,
                ];
            })
            ->filter()
            ->values();
        
        // ─── Apply Filters ──────────────────────────────────────────
        $productSales = $productSalesRaw->filter(function($product) use ($minQuantity, $maxQuantity, $minRevenue, $maxRevenue, $minProfit, $velocityFilter, $startDate, $endDate) {
            if ($minQuantity && $product->total_quantity_sold < (int)$minQuantity) return false;
            if ($maxQuantity && $product->total_quantity_sold > (int)$maxQuantity) return false;
            if ($minRevenue && $product->total_revenue < (float)$minRevenue) return false;
            if ($maxRevenue && $product->total_revenue > (float)$maxRevenue) return false;
            if ($minProfit && $product->total_profit < (float)$minProfit) return false;
            
            if ($velocityFilter) {
                $daysInPeriod = $this->getDaysInPeriod($startDate, $endDate);
                $dailyRate = $product->total_quantity_sold / max($daysInPeriod, 1);
                $category = $this->getVelocityCategory($dailyRate);
                if ($velocityFilter !== $category) return false;
            }
            
            return true;
        })->sortByDesc('total_revenue')->values();
        
        // ─── Calculate Days in Period ──────────────────────────────
        $daysInPeriod = $this->getDaysInPeriod($startDate, $endDate);
        
        // ─── Calculate Sales Velocity ──────────────────────────────
        $productSales = $productSales->map(function($product) use ($daysInPeriod) {
            $dailySalesRate = $product->total_quantity_sold / max($daysInPeriod, 1);
            $dailyRevenueRate = $product->total_revenue / max($daysInPeriod, 1);
            
            $velocityData = $this->getVelocityData($dailySalesRate);
            $product->daily_sales_rate = $dailySalesRate;
            $product->daily_revenue_rate = $dailyRevenueRate;
            $product->velocity_category = $velocityData['category'];
            $product->velocity_color = $velocityData['color'];
            $product->velocity_icon = $velocityData['icon'];
            
            // ─── Stock Coverage (Days of Stock) ──────────────────────
            $product->stock_coverage_days = $dailySalesRate > 0 ? $product->current_stock / $dailySalesRate : 0;
            
            return $product;
        });
        
        // ─── Get Top and Bottom Performers ──────────────────────────
        $topProducts = $productSales->take(10);
        $bottomProducts = $productSales->sortBy('total_revenue')->take(10);
        
        // ─── Summary Statistics ─────────────────────────────────────
        $totalRevenue = $productSales->sum('total_revenue');
        $totalQuantity = $productSales->sum('total_quantity_sold');
        $totalProfit = $productSales->sum('total_profit');
        $avgMargin = $productSales->count() > 0 ? $productSales->avg('profit_margin') : 0;
        $uniqueProducts = $productSales->count();
        $totalStock = $productSales->sum('current_stock');
        
        $summary = (object)[
            'total_products' => $uniqueProducts,
            'total_revenue' => $totalRevenue,
            'total_quantity' => $totalQuantity,
            'total_profit' => $totalProfit,
            'avg_profit_margin' => $avgMargin,
            'avg_daily_sales' => $daysInPeriod > 0 ? $totalQuantity / $daysInPeriod : 0,
            'total_stock' => $totalStock,
            'top_product' => $productSales->first()->variant_name ?? 'N/A',
            'top_revenue' => $productSales->first()->total_revenue ?? 0,
            'fast_movers' => $productSales->where('velocity_category', 'Fast Mover')->count(),
            'medium_movers' => $productSales->where('velocity_category', 'Medium Mover')->count(),
            'slow_movers' => $productSales->where('velocity_category', 'Slow Mover')->count(),
            'is_single_shop' => $isSingleShop,
        ];
        
        // ─── Velocity Breakdown for Chart ──────────────────────────
        $velocityBreakdown = [
            'Fast Mover' => $productSales->where('velocity_category', 'Fast Mover')->count(),
            'Medium Mover' => $productSales->where('velocity_category', 'Medium Mover')->count(),
            'Slow Mover' => $productSales->where('velocity_category', 'Slow Mover')->count(),
        ];
        
        // ─── Get Filter Options ─────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get();
        
        // ─── Pagination ─────────────────────────────────────────────
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min($perPage, max($productSales->count(), 1));
        $paginatedProducts = $productSales->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $productSalesPaginated = new LengthAwarePaginator(
            $paginatedProducts,
            $productSales->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        
        // ─── Return View ────────────────────────────────────────────
        return view('reports.orders.by-product', compact(
            'productSales',
            'productSalesPaginated',
            'topProducts',
            'bottomProducts',
            'summary',
            'velocityBreakdown',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'minQuantity',
            'maxQuantity',
            'minRevenue',
            'maxRevenue',
            'minProfit',
            'velocityFilter',
            'daysInPeriod',
            'perPage',
            'isSingleShop'
        ));
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
            // ─── MULTI-SHOP: Get stock from InventoryItems ──────────
            $inventoryQuery = InventoryItems::whereIn('variant_id', $variantIds)
                ->where('tenant_id', $tenantId);
            
            // Apply location filter if provided
            if ($locationId) {
                $inventoryQuery->where('location_id', $locationId);
            }
            
            // Apply department filter if provided
            if ($departmentId) {
                $inventoryQuery->where('department_id', $departmentId);
            }
            
            // Get inventory records
            $inventoryRecords = $inventoryQuery->get();
            
            // Group by variant_id and sum quantity_on_hand
            foreach ($inventoryRecords as $record) {
                if (!isset($stockMap[$record->variant_id])) {
                    $stockMap[$record->variant_id] = 0;
                }
                $stockMap[$record->variant_id] += (int)($record->quantity_on_hand ?? 0);
            }
            
            // Ensure all variants have a stock value (even if 0)
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
        
        // ─── Date Range ──────────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // ─── Filter Parameters ──────────────────────────────────────
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $paymentType = $request->get('payment_type');
        $paymentMethodStatus = $request->get('payment_method_status', 'all');
        $minAmount = $request->get('min_amount');
        $maxAmount = $request->get('max_amount');
        $perPage = $request->get('per_page', 25);
        
        // ─── Get Order Payments ─────────────────────────────────────
        $query = OrderPayment::with(['order', 'paymentMethod'])
            ->whereHas('order', function($q) use ($tenantId, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId);
                
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
                
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
            })
            ->whereBetween('processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // ─── Apply Payment Status Filter ────────────────────────────
        if ($request->has('payment_status') && $request->payment_status !== 'all') {
            $query->where('status', $request->payment_status);
        } else {
            // Default: include only completed and failed
            $query->whereIn('status', ['completed', 'failed']);
        }
        
        // ─── Apply Payment Type Filter ──────────────────────────────
        if ($paymentType && $paymentType !== 'all') {
            $query->whereHas('paymentMethod', function($q) use ($paymentType) {
                $q->where('type', $paymentType);
            });
        }
        
        // ─── Apply Payment Method Status Filter ─────────────────────
        if ($paymentMethodStatus !== 'all') {
            $isActive = $paymentMethodStatus === 'active';
            $query->whereHas('paymentMethod', function($q) use ($isActive) {
                $q->where('is_active', $isActive);
            });
        }
        
        $allPayments = $query->get();
        
        // ─── Separate Completed and Failed Payments ─────────────────
        $completedPayments = $allPayments->where('status', 'completed');
        $failedPayments = $allPayments->where('status', 'failed');
        
        // ─── Group by Payment Method ────────────────────────────────
        $paymentMethodAnalysis = $completedPayments->groupBy('payment_method_id')
            ->map(function($payments, $methodId) use ($failedPayments) {
                $firstPayment = $payments->first();
                $paymentMethod = $firstPayment->paymentMethod;
                
                $totalAmount = $payments->sum('amount');
                $transactionCount = $payments->count();
                $avgTransaction = $transactionCount > 0 ? $totalAmount / $transactionCount : 0;
                $lastTransaction = $payments->sortByDesc('processed_at')->first();
                
                // ─── Failed Transactions for this Method ──────────────
                $methodFailedPayments = $failedPayments->filter(function($payment) use ($methodId) {
                    return $payment->payment_method_id == $methodId;
                });
                $failedCount = $methodFailedPayments->count();
                $failedAmount = $methodFailedPayments->sum('amount');
                $failureRate = ($transactionCount + $failedCount) > 0 
                    ? ($failedCount / ($transactionCount + $failedCount)) * 100 
                    : 0;
                
                return (object)[
                    'id' => $methodId,
                    'method_name' => $paymentMethod->name ?? 'Unknown',
                    'method_type' => $paymentMethod->type ?? 'unknown',
                    'is_active' => $paymentMethod->is_active ?? true,
                    'transaction_count' => $transactionCount,
                    'total_amount' => $totalAmount,
                    'average_transaction' => $avgTransaction,
                    'largest_transaction' => $payments->max('amount') ?? 0,
                    'smallest_transaction' => $payments->min('amount') ?? 0,
                    'last_transaction_date' => $lastTransaction->processed_at ?? null,
                    'failed_count' => $failedCount,
                    'failed_amount' => $failedAmount,
                    'failure_rate' => $failureRate,
                    'success_rate' => 100 - $failureRate,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();
        
        // ─── Apply Amount Filters ────────────────────────────────────
        if ($minAmount && is_numeric($minAmount)) {
            $paymentMethodAnalysis = $paymentMethodAnalysis->filter(function($method) use ($minAmount) {
                return $method->total_amount >= (float)$minAmount;
            });
        }
        
        if ($maxAmount && is_numeric($maxAmount)) {
            $paymentMethodAnalysis = $paymentMethodAnalysis->filter(function($method) use ($maxAmount) {
                return $method->total_amount <= (float)$maxAmount;
            });
        }
        
        // Reset keys
        $paymentMethodAnalysis = $paymentMethodAnalysis->values();
        
        // ─── Payment Method Trends ──────────────────────────────────
        $paymentTrendsRaw = $completedPayments->groupBy(function($payment) {
            return $payment->processed_at->format('Y-m-d');
        })->flatMap(function($dailyPayments, $date) {
            return $dailyPayments->groupBy(function($payment) {
                return $payment->paymentMethod->type ?? 'unknown';
            })->map(function($typePayments, $type) use ($date) {
                return (object)[
                    'date' => $date,
                    'type' => $type,
                    'daily_count' => $typePayments->count(),
                    'daily_total' => $typePayments->sum('amount'),
                ];
            })->values();
        });
        
        $paymentTrends = $paymentTrendsRaw->groupBy('type');
        
        // ─── Failed Transactions Summary ────────────────────────────
        $failedTransactions = $failedPayments->groupBy('payment_method_id')
            ->map(function($failures, $methodId) {
                $paymentMethod = $failures->first()->paymentMethod;
                $totalTransactions = $this->getTotalTransactionsForMethod($methodId);
                
                return (object)[
                    'id' => $methodId,
                    'name' => $paymentMethod->name ?? 'Unknown',
                    'type' => $paymentMethod->type ?? 'unknown',
                    'failed_count' => $failures->count(),
                    'failed_amount' => $failures->sum('amount'),
                    'total_transactions' => $totalTransactions,
                    'failure_rate' => $totalTransactions > 0 ? ($failures->count() / $totalTransactions) * 100 : 100,
                ];
            })
            ->sortByDesc('failed_count')
            ->values();
        
        // ─── Summary Statistics ─────────────────────────────────────
        $totalCompletedTransactions = $paymentMethodAnalysis->sum('transaction_count');
        $totalAmount = $paymentMethodAnalysis->sum('total_amount');
        $totalFailedTransactions = $failedTransactions->sum('failed_count');
        $totalFailedAmount = $failedTransactions->sum('failed_amount');
        $overallFailureRate = ($totalCompletedTransactions + $totalFailedTransactions) > 0 
            ? ($totalFailedTransactions / ($totalCompletedTransactions + $totalFailedTransactions)) * 100 
            : 0;
        
        $summary = (object)[
            'total_methods' => $paymentMethodAnalysis->count(),
            'total_transactions' => $totalCompletedTransactions,
            'total_amount' => $totalAmount,
            'avg_transaction' => $totalCompletedTransactions > 0 ? $totalAmount / $totalCompletedTransactions : 0,
            'most_used_method' => $paymentMethodAnalysis->first()->method_name ?? 'N/A',
            'most_used_amount' => $paymentMethodAnalysis->first()->total_amount ?? 0,
            'total_failed_transactions' => $totalFailedTransactions,
            'total_failed_amount' => $totalFailedAmount,
            'overall_failure_rate' => $overallFailureRate,
            'success_rate' => 100 - $overallFailureRate,
        ];
        
        // ─── Get Filter Options ─────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // ─── Payment Types for Filter ───────────────────────────────
        $paymentTypes = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();
        
        // ─── Chart Data ─────────────────────────────────────────────
        $chartData = [
            'labels' => $paymentMethodAnalysis->pluck('method_name'),
            'amounts' => $paymentMethodAnalysis->pluck('total_amount'),
            'counts' => $paymentMethodAnalysis->pluck('transaction_count'),
            'failed_counts' => $paymentMethodAnalysis->pluck('failed_count'),
        ];
        
        // ─── Pagination ─────────────────────────────────────────────
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min($perPage, max($paymentMethodAnalysis->count(), 1));
        $paginatedMethods = $paymentMethodAnalysis->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $paymentMethodAnalysisPaginated = new LengthAwarePaginator(
            $paginatedMethods,
            $paymentMethodAnalysis->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        
        // ─── Return View ────────────────────────────────────────────
        return view('reports.orders.by-payment-method', compact(
            'paymentMethodAnalysis',
            'paymentMethodAnalysisPaginated',
            'paymentTrends',
            'failedTransactions',
            'locations',
            'departments',
            'paymentTypes',
            'summary',
            'chartData',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'paymentType',
            'paymentMethodStatus',
            'minAmount',
            'maxAmount',
            'perPage'
        ));
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
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Validate and format dates
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Get filter parameters
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $employeeId = $request->get('employee_id');
        $minSales = $request->get('min_sales');
        $maxSales = $request->get('max_sales');
        $perPage = $request->get('per_page', 15); // Add pagination parameter
        
        // First, get all relevant order IDs with filters
        $orderQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($locationId) {
            $orderQuery->where('location_id', $locationId);
        }
        
        if ($departmentId) {
            $orderQuery->where('department_id', $departmentId);
        }
        
        // Get filtered order IDs
        $filteredOrderIds = $orderQuery->pluck('id');
        
        // Build employee query - NO DB::raw, using Eloquent only
        $employeesQuery = User::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with(['orders' => function($query) use ($startDate, $endDate, $locationId, $departmentId, $tenantId) {
                $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->where('tenant_id', $tenantId);
                
                if ($locationId) {
                    $query->where('location_id', $locationId);
                }
                
                if ($departmentId) {
                    $query->where('department_id', $departmentId);
                }
            }]);
        
        // Apply employee filter
        if ($employeeId) {
            $employeesQuery->where('id', $employeeId);
        }
        
        // Get employees with pagination
        $employees = $employeesQuery->paginate($perPage);
        
        // Calculate days in period
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        
        // Calculate performance metrics for each employee
        $employeePerformance = collect();
        
        foreach ($employees as $employee) {
            $orders = $employee->orders;
            $orderCount = $orders->count();
            
            // Calculate metrics from the collection
            $totalSales = $orders->sum('total');
            $totalTax = $orders->sum('tax_total');
            $totalDiscount = $orders->sum('discount_total');
            $averageOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0;
            $largestSale = $orders->max('total');
            $smallestSale = $orders->min('total');
            $uniqueCustomers = $orders->unique('customer_id')->count();
            $lastSaleDate = $orders->max('created_at');
            
            $ordersPerDay = $orderCount / max($daysInPeriod, 1);
            $salesPerDay = $totalSales / max($daysInPeriod, 1);
            
            // Determine performance rating
            $performanceRating = $this->calculatePerformanceRating($salesPerDay);
            
            $employeePerformance->push((object)[
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'order_count' => $orderCount,
                'total_sales' => $totalSales,
                'total_tax_collected' => $totalTax,
                'total_discount_given' => $totalDiscount,
                'average_order_value' => $averageOrderValue,
                'largest_sale' => $largestSale ?? 0,
                'smallest_sale' => $smallestSale ?? 0,
                'unique_customers' => $uniqueCustomers,
                'last_sale_date' => $lastSaleDate,
                'orders_per_day' => $ordersPerDay,
                'sales_per_day' => $salesPerDay,
                'performance_rating' => $performanceRating['rating'],
                'rating_color' => $performanceRating['color'],
                'days_in_period' => $daysInPeriod,
            ]);
        }
        
        // Filter employees with orders (optional - remove this line to show zero-sales employees)
        $employeePerformance = $employeePerformance->filter(function($employee) {
            return $employee->order_count > 0;
        });
        
        // Apply sales filters
        if ($minSales && is_numeric($minSales)) {
            $employeePerformance = $employeePerformance->filter(function($employee) use ($minSales) {
                return $employee->total_sales >= (float)$minSales;
            });
        }
        
        if ($maxSales && is_numeric($maxSales)) {
            $employeePerformance = $employeePerformance->filter(function($employee) use ($maxSales) {
                return $employee->total_sales <= (float)$maxSales;
            });
        }
        
        // Sort by total sales descending and reset values
        $employeePerformance = $employeePerformance->sortByDesc('total_sales')->values();
        
        // Get filter options for dropdowns
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $employeesList = User::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
        
        return view('reports.orders.by-employee', compact(
            'employeePerformance',
            'locations',
            'departments',
            'employeesList',
            'employees',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'employeeId',
            'minSales',
            'maxSales',
            'daysInPeriod',
            'perPage'
        ));
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
        
        // ─── Date Range ──────────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // ─── Filter Parameters ──────────────────────────────────────
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType = $request->get('order_type');
        $groupBy = $request->get('group_by', 'daily');
        $perPage = $request->get('per_page', 25);
        
        // ─── Build Orders Query ────────────────────────────────────
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed'); // ✅ Fixed: Use 'completed' only
        
        // Apply location filter
        if ($locationId) {
            $ordersQuery->where('location_id', $locationId);
        }
        
        // Apply department filter
        if ($departmentId) {
            $ordersQuery->where('department_id', $departmentId);
        }
        
        // Apply order type filter
        if ($orderType && $orderType !== 'all') {
            $ordersQuery->where('type', $orderType);
        }
        
        // Get all orders
        $orders = $ordersQuery->get();
        
        // ─── Group Orders Based on Group By ──────────────────────
        $timeAnalysis = collect();
        
        switch ($groupBy) {
            case 'hourly':
                $grouped = $orders->groupBy(function($order) {
                    return $order->created_at->format('H');
                })->sortKeys();
                
                foreach ($grouped as $hour => $hourOrders) {
                    $timeAnalysis->push((object)[
                        'time_period' => (int)$hour,
                        'order_count' => $hourOrders->count(),
                        'total_sales' => $hourOrders->sum('total'),
                        'average_sale' => $hourOrders->avg('total'),
                        'total_tax' => $hourOrders->sum('tax_total'),
                        'total_discount' => $hourOrders->sum('discount_total'),
                    ]);
                }
                break;
                
            case 'weekly':
                $grouped = $orders->groupBy(function($order) {
                    return $order->created_at->year . '-W' . $order->created_at->weekOfYear;
                })->sortKeys();
                
                foreach ($grouped as $key => $weekOrders) {
                    $firstOrder = $weekOrders->first();
                    $weekStart = $firstOrder->created_at->copy()->startOfWeek();
                    $weekEnd = $firstOrder->created_at->copy()->endOfWeek();
                    
                    $timeAnalysis->push((object)[
                        'year' => $firstOrder->created_at->year,
                        'week_number' => $firstOrder->created_at->weekOfYear,
                        'week_start' => $weekStart->format('Y-m-d'),
                        'week_end' => $weekEnd->format('Y-m-d'),
                        'order_count' => $weekOrders->count(),
                        'total_sales' => $weekOrders->sum('total'),
                        'average_sale' => $weekOrders->avg('total'),
                        'total_tax' => $weekOrders->sum('tax_total'),
                        'total_discount' => $weekOrders->sum('discount_total'),
                    ]);
                }
                break;
                
            case 'monthly':
                $grouped = $orders->groupBy(function($order) {
                    return $order->created_at->format('Y-m');
                })->sortKeys();
                
                foreach ($grouped as $key => $monthOrders) {
                    $firstOrder = $monthOrders->first();
                    
                    $timeAnalysis->push((object)[
                        'year' => $firstOrder->created_at->year,
                        'month_number' => $firstOrder->created_at->month,
                        'month_period' => $key,
                        'order_count' => $monthOrders->count(),
                        'total_sales' => $monthOrders->sum('total'),
                        'average_sale' => $monthOrders->avg('total'),
                        'total_tax' => $monthOrders->sum('tax_total'),
                        'total_discount' => $monthOrders->sum('discount_total'),
                    ]);
                }
                break;
                
            default: // daily
                $grouped = $orders->groupBy(function($order) {
                    return $order->created_at->format('Y-m-d');
                })->sortKeys();
                
                foreach ($grouped as $date => $dailyOrders) {
                    $timeAnalysis->push((object)[
                        'date' => $date,
                        'order_count' => $dailyOrders->count(),
                        'total_sales' => $dailyOrders->sum('total'),
                        'average_sale' => $dailyOrders->avg('total'),
                        'total_tax' => $dailyOrders->sum('tax_total'),
                        'total_discount' => $dailyOrders->sum('discount_total'),
                    ]);
                }
                break;
        }
        
        // ─── Sort by Date/Period ──────────────────────────────────────
        if ($groupBy == 'daily') {
            $timeAnalysis = $timeAnalysis->sortByDesc('date')->values();
        } elseif ($groupBy == 'weekly') {
            $timeAnalysis = $timeAnalysis->sortByDesc('year')->sortByDesc('week_number')->values();
        } elseif ($groupBy == 'monthly') {
            $timeAnalysis = $timeAnalysis->sortByDesc('year')->sortByDesc('month_number')->values();
        } else {
            $timeAnalysis = $timeAnalysis->sortByDesc('time_period')->values();
        }
        
        // ─── Calculate Growth Metrics ──────────────────────────────
        $growthMetrics = $this->calculateGrowthMetrics($timeAnalysis, $groupBy);
        
        // ─── Peak Analysis ────────────────────────────────────────────
        $peakAnalysis = $this->analyzePeakTimes($orders, $groupBy, $startDate, $endDate);
        
        // ─── Pagination ──────────────────────────────────────────────
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min($perPage, max($timeAnalysis->count(), 1));
        $paginatedData = $timeAnalysis->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $timeAnalysisPaginated = new LengthAwarePaginator(
            $paginatedData,
            $timeAnalysis->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        
        // ─── Get Filter Options ──────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // ─── Return View ──────────────────────────────────────────────
        return view('reports.orders.time-analysis', compact(
            'timeAnalysis',
            'timeAnalysisPaginated',
            'growthMetrics',
            'peakAnalysis',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'groupBy',
            'locationId',
            'departmentId',
            'orderType',
            'perPage'
        ));
    }
    
    /**
     * Calculate growth metrics
     */
    private function calculateGrowthMetrics($timeAnalysis, $groupBy)
    {
        if ($timeAnalysis->count() < 2) {
            return [
                'trend' => 'stable',
                'daily_growth' => 0,
                'weekly_growth' => 0,
                'current_average' => $timeAnalysis->first() ? $timeAnalysis->first()->average_sale : 0,
                'total_growth' => 0
            ];
        }
        
        $firstPeriod = $timeAnalysis->last();
        $lastPeriod = $timeAnalysis->first();
        
        $growth = 0;
        if ($firstPeriod->total_sales > 0) {
            $growth = (($lastPeriod->total_sales - $firstPeriod->total_sales) / $firstPeriod->total_sales) * 100;
        }
        
        $trend = 'stable';
        if ($growth > 10) {
            $trend = 'upward';
        } elseif ($growth < -10) {
            $trend = 'downward';
        }
        
        $totalGrowth = 0;
        $growthCount = 0;
        
        for ($i = 0; $i < $timeAnalysis->count() - 1; $i++) {
            $current = $timeAnalysis[$i];
            $next = $timeAnalysis[$i + 1];
            
            if ($next->total_sales > 0) {
                $periodGrowth = (($current->total_sales - $next->total_sales) / $next->total_sales) * 100;
                $totalGrowth += $periodGrowth;
                $growthCount++;
            }
        }
        
        $dailyGrowth = $growthCount > 0 ? $totalGrowth / $growthCount : 0;
        
        $weeklyGrowth = 0;
        if ($groupBy == 'daily' && $timeAnalysis->count() >= 8) {
            $currentWeek = $timeAnalysis->slice(0, 7)->sum('total_sales');
            $previousWeek = $timeAnalysis->slice(7, 7)->sum('total_sales');
            
            if ($previousWeek > 0) {
                $weeklyGrowth = (($currentWeek - $previousWeek) / $previousWeek) * 100;
            }
        }
        
        return [
            'trend' => $trend,
            'daily_growth' => $dailyGrowth,
            'weekly_growth' => $weeklyGrowth,
            'current_average' => $lastPeriod->average_sale ?? 0,
            'total_growth' => $growth
        ];
    }
    
    /**
     * Analyze peak times
     */
    private function analyzePeakTimes($orders, $groupBy, $startDate, $endDate)
    {
        $peakHours = collect();
        $peakDays = collect();
        
        if ($groupBy == 'hourly') {
            $peakHours = $orders->groupBy(function($order) {
                return $order->created_at->format('H');
            })->map(function($hourOrders, $hour) {
                return (object)[
                    'hour' => (int)$hour,
                    'order_count' => $hourOrders->count(),
                    'hourly_total' => $hourOrders->sum('total'),
                    'hourly_average' => $hourOrders->avg('total'),
                ];
            })->sortByDesc('hourly_total')->take(5)->values();
            
            $peakDays = $orders->groupBy(function($order) {
                return $order->created_at->format('Y-m-d');
            })->map(function($dayOrders, $date) {
                return (object)[
                    'date' => $date,
                    'order_count' => $dayOrders->count(),
                    'total_sales' => $dayOrders->sum('total'),
                    'average_sale' => $dayOrders->avg('total'),
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
                    'average_sale' => $weekOrders->avg('total'),
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
                    'average_sale' => $monthOrders->avg('total'),
                ];
            })->sortByDesc('total_sales')->take(5)->values();
            
        } else {
            $peakDays = $orders->groupBy(function($order) {
                return $order->created_at->format('Y-m-d');
            })->map(function($dayOrders, $date) {
                return (object)[
                    'date' => $date,
                    'order_count' => $dayOrders->count(),
                    'total_sales' => $dayOrders->sum('total'),
                    'average_sale' => $dayOrders->avg('total'),
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
        
        // ─── Date Range ──────────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // ─── Filter Parameters ──────────────────────────────────────
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $statusFilter = $request->get('status_filter');
        $perPage = $request->get('per_page', 25);
        
        // ─── Get Return Orders ─────────────────────────────────────
        $returnOrdersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->returns() // Scope for return orders
            ->with(['customer', 'orderCreater', 'refundPayments', 'items.productVariant']);
        
        // Apply location filter
        if ($locationId) {
            $returnOrdersQuery->where('location_id', $locationId);
        }
        
        // Apply department filter
        if ($departmentId) {
            $returnOrdersQuery->where('department_id', $departmentId);
        }
        
        // Apply status filter
        if ($statusFilter && $statusFilter !== 'all') {
            $returnOrdersQuery->where('status', $statusFilter);
        }
        
        $returnOrders = $returnOrdersQuery->orderBy('created_at', 'desc')->get();
        
        // ─── Get Refund Payments ──────────────────────────────────
        $refundPaymentsQuery = OrderPayment::whereHas('order', function($query) use ($tenantId, $startDate, $endDate, $locationId, $departmentId) {
                $query->where('tenant_id', $tenantId)
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                
                if ($locationId) {
                    $query->where('location_id', $locationId);
                }
                
                if ($departmentId) {
                    $query->where('department_id', $departmentId);
                }
            })
            ->where('status', 'refunded')
            ->with(['order', 'paymentMethod', 'processor']);
        
        $refundPaymentsRaw = $refundPaymentsQuery->orderBy('processed_at', 'desc')->get();
        
        $refundPayments = $refundPaymentsRaw->map(function($payment) {
            return (object)[
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'order_number' => $payment->order->order_number ?? 'N/A',
                'order_total' => $payment->order->total ?? 0,
                'amount' => $payment->amount,
                'payment_method' => $payment->paymentMethod->name ?? 'Unknown',
                'payment_method_type' => $payment->paymentMethod->type ?? 'other',
                'processed_at' => $payment->processed_at,
                'status' => $payment->status,
                'reference' => $payment->transaction_id ?? 'N/A', // ✅ Fixed: Use transaction_id
                'processed_by' => $payment->processor->name ?? 'System',
            ];
        });
        
        // ─── Return Reasons Analysis ─────────────────────────────
        $returnReasonsCollection = $returnOrders->where('type', 'return')
            ->groupBy(function($order) {
                // ✅ Fixed: Use notes field as return reason
                $notes = $order->notes ?? '';
                if (strpos($notes, ':') !== false) {
                    return trim(substr($notes, 0, strpos($notes, ':')));
                }
                return $notes ?: 'Other';
            })
            ->map(function($orders, $reason) use ($returnOrders) {
                return (object)[
                    'reason' => $reason,
                    'count' => $orders->count(),
                    'total_amount' => $orders->sum('total'),
                    'percentage' => $returnOrders->count() > 0 ? ($orders->count() / $returnOrders->count()) * 100 : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();
        
        // ─── Return Rate Calculation ──────────────────────────────
        $totalOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sales()
            ->count();
        
        $totalReturnOrders = $returnOrders->where('type', 'return')->count();
        $returnRate = $totalOrders > 0 ? ($totalReturnOrders / $totalOrders) * 100 : 0;
        
        // ─── Refund Rate Calculation ─────────────────────────────
        $totalSalesValue = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sales()
            ->sum('total');
        
        $totalRefundValue = $refundPayments->sum('amount');
        $refundRate = $totalSalesValue > 0 ? ($totalRefundValue / $totalSalesValue) * 100 : 0;
        
        // ─── Top Returned Products ────────────────────────────────
        $topReturnedProducts = collect();
        
        foreach ($returnOrders->where('type', 'return') as $returnOrder) {
            foreach ($returnOrder->items as $item) {
                $variant = $item->productVariant;
                if ($variant) {
                    $key = $variant->id;
                    $existing = $topReturnedProducts->get($key);
                    
                    if ($existing) {
                        $existing->return_quantity += $item->quantity;
                        $existing->return_value += $item->total_price;
                        $existing->return_count++;
                    } else {
                        $topReturnedProducts->put($key, (object)[
                            'sku' => $variant->sku ?? 'N/A',
                            'name' => $variant->name ?? 'Unknown',
                            'return_quantity' => $item->quantity,
                            'return_value' => $item->total_price,
                            'return_count' => 1,
                        ]);
                    }
                }
            }
        }
        
        $topReturnedProducts = $topReturnedProducts->sortByDesc('return_quantity')->take(10)->values();
        
        // ─── Metrics ──────────────────────────────────────────────
        $metrics = (object)[
            'total_return_orders' => $totalReturnOrders,
            'total_refund_amount' => $totalRefundValue,
            'average_return_value' => $totalReturnOrders > 0 ? $totalRefundValue / $totalReturnOrders : 0,
            'return_rate' => $returnRate,
            'refund_rate' => $refundRate,
            'total_sales_value' => $totalSalesValue,
            'total_orders' => $totalOrders,
        ];
        
        // ─── Monthly Return Trends ───────────────────────────────
        $monthlyReturnTrends = $returnOrders
            ->groupBy(function($order) {
                return $order->created_at->format('Y-m');
            })
            ->map(function($orders, $month) use ($returnOrders) {
                return (object)[
                    'month' => $month,
                    'return_count' => $orders->count(),
                    'return_value' => $orders->sum('total'),
                    'percentage_of_total' => $returnOrders->count() > 0 ? ($orders->count() / $returnOrders->count()) * 100 : 0,
                ];
            })
            ->sortKeys()
            ->values();
        
        // ─── Returns by Payment Method ──────────────────────────
        $returnsByPaymentMethod = $returnOrders
            ->groupBy(function($order) {
                $firstPayment = $order->refundPayments->first();
                return $firstPayment ? ($firstPayment->paymentMethod->name ?? 'Unknown') : 'Unknown';
            })
            ->map(function($orders, $method) {
                return (object)[
                    'payment_method' => $method,
                    'return_count' => $orders->count(),
                    'return_value' => $orders->sum('total'),
                ];
            })
            ->filter(function($item) {
                return $item->payment_method !== 'Unknown' || $item->return_count > 0;
            })
            ->sortByDesc('return_value')
            ->values();
        
        // ─── Pagination for Return Orders ──────────────────────
        $currentPage = LengthAwarePaginator::resolveCurrentPage('returns_page');
        $perPage = min($perPage, max($returnOrders->count(), 1));
        $paginatedReturns = $returnOrders->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $returnOrdersPaginated = new LengthAwarePaginator(
            $paginatedReturns,
            $returnOrders->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'returns_page']
        );
        
        // ─── Pagination for Refund Payments ────────────────────
        $currentRefundPage = LengthAwarePaginator::resolveCurrentPage('refunds_page');
        $paginatedRefunds = $refundPayments->slice(($currentRefundPage - 1) * $perPage, $perPage)->values();
        
        $refundPaymentsPaginated = new LengthAwarePaginator(
            $paginatedRefunds,
            $refundPayments->count(),
            $perPage,
            $currentRefundPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'refunds_page']
        );
        
        // ─── Get Filter Options ──────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // ─── Return View ──────────────────────────────────────────
        return view('reports.orders.returns-refunds', compact(
            'returnOrders',
            'returnOrdersPaginated',
            'refundPayments',
            'refundPaymentsPaginated',
            'returnReasonsCollection',
            'returnRate',
            'refundRate',
            'topReturnedProducts',
            'metrics',
            'monthlyReturnTrends',
            'returnsByPaymentMethod',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'statusFilter',
            'perPage'
        ));
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
        
        // ─── Date Range ──────────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // ─── Filter Parameters ──────────────────────────────────────
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $employeeId = $request->get('employee_id');
        $perPage = $request->get('per_page', 25);
        
        // ─── Get Orders with Discounts ─────────────────────────────
        $discountedOrdersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->withDiscounts()
            ->with(['customer', 'orderCreater']);
        
        // Apply location filter
        if ($locationId) {
            $discountedOrdersQuery->where('location_id', $locationId);
        }
        
        // Apply department filter
        if ($departmentId) {
            $discountedOrdersQuery->where('department_id', $departmentId);
        }
        
        // Apply employee filter
        if ($employeeId) {
            $discountedOrdersQuery->where('created_by', $employeeId);
        }
        
        $discountedOrders = $discountedOrdersQuery->orderBy('discount_total', 'desc')->get();
        
        // ─── Discount Summary ───────────────────────────────────────
        $discountSummary = [
            'total_discounted_orders' => $discountedOrders->count(),
            'total_discount_amount' => $discountedOrders->sum('discount_total'),
            'average_discount_per_order' => $discountedOrders->avg('discount_total') ?? 0,
            'max_discount' => $discountedOrders->max('discount_total') ?? 0,
            'min_discount' => $discountedOrders->min('discount_total') ?? 0,
            'discount_rate' => $discountedOrders->sum('total') > 0 ? 
                ($discountedOrders->sum('discount_total') / $discountedOrders->sum('total')) * 100 : 0,
        ];
        
        // ─── Discount by Employee ──────────────────────────────────
        $discountByEmployee = $discountedOrders
            ->groupBy('created_by')
            ->map(function($orders, $userId) {
                $firstOrder = $orders->first();
                $employee = $firstOrder->orderCreater;
                
                $totalDiscount = $orders->sum('discount_total');
                $orderCount = $orders->count();
                $totalRevenue = $orders->sum('total');
                
                return (object)[
                    'id' => $userId,
                    'first_name' => $employee->first_name ?? 'Unknown',
                    'last_name' => $employee->last_name ?? '',
                    'email' => $employee->email ?? '',
                    'order_count' => $orderCount,
                    'total_discount_given' => $totalDiscount,
                    'average_discount' => $orders->avg('discount_total') ?? 0,
                    'max_discount_given' => $orders->max('discount_total') ?? 0,
                    'discount_per_order' => $orderCount > 0 ? $totalDiscount / $orderCount : 0,
                    'discount_as_percentage_of_sales' => $totalRevenue > 0 ? ($totalDiscount / $totalRevenue) * 100 : 0,
                ];
            })
            ->filter(function($employee) {
                return $employee->first_name !== 'Unknown';
            })
            ->sortByDesc('total_discount_given')
            ->values();
        
        // ─── Discount Patterns by Day of Week ──────────────────────
        $discountByDay = $discountedOrders
            ->groupBy(function($order) {
                return $order->created_at->format('l');
            })
            ->map(function($orders, $day) {
                return (object)[
                    'day' => $day,
                    'discount_count' => $orders->count(),
                    'total_amount' => $orders->sum('discount_total'),
                    'average_amount' => $orders->avg('discount_total') ?? 0,
                    'order_count' => $orders->count(),
                ];
            })
            ->sortBy(function($item, $key) {
                $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                return array_search($key, $order);
            })
            ->values();
        
        // ─── Discount Patterns by Hour ─────────────────────────────
        $discountByHour = $discountedOrders
            ->groupBy(function($order) {
                return $order->created_at->format('H');
            })
            ->map(function($orders, $hour) {
                return (object)[
                    'hour' => (int)$hour,
                    'hour_formatted' => date('g:00 A', mktime($hour, 0, 0)),
                    'discount_count' => $orders->count(),
                    'total_amount' => $orders->sum('discount_total'),
                    'average_amount' => $orders->avg('discount_total') ?? 0,
                ];
            })
            ->sortKeys()
            ->values();
        
        // ─── Orders with Discount vs Orders Without ──────────────
        $ordersWithDiscountData = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->withDiscounts();
        
        if ($locationId) {
            $ordersWithDiscountData->where('location_id', $locationId);
        }
        if ($departmentId) {
            $ordersWithDiscountData->where('department_id', $departmentId);
        }
        $ordersWithDiscountData = $ordersWithDiscountData->get();
        
        $ordersWithoutDiscountData = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->withoutDiscounts();
        
        if ($locationId) {
            $ordersWithoutDiscountData->where('location_id', $locationId);
        }
        if ($departmentId) {
            $ordersWithoutDiscountData->where('department_id', $departmentId);
        }
        $ordersWithoutDiscountData = $ordersWithoutDiscountData->get();
        
        $ordersWithDiscount = (object)[
            'order_count' => $ordersWithDiscountData->count(),
            'average_order_value' => $ordersWithDiscountData->avg('total') ?? 0,
            'average_discount' => $ordersWithDiscountData->avg('discount_total') ?? 0,
            'total_revenue' => $ordersWithDiscountData->sum('total'),
        ];
        
        $ordersWithoutDiscount = (object)[
            'order_count' => $ordersWithoutDiscountData->count(),
            'average_order_value' => $ordersWithoutDiscountData->avg('total') ?? 0,
            'total_revenue' => $ordersWithoutDiscountData->sum('total'),
        ];
        
        // ─── Discount Range Distribution ──────────────────────────
        $discountRanges = [
            '0-10%' => 0,
            '10-20%' => 0,
            '20-30%' => 0,
            '30-40%' => 0,
            '40-50%' => 0,
            '50%+' => 0,
        ];
        
        foreach ($discountedOrders as $order) {
            // ✅ Fixed: Calculate discount percentage from total and discount_total
            $percentage = $order->total > 0 ? ($order->discount_total / ($order->total + $order->discount_total)) * 100 : 0;
            
            if ($percentage <= 10) {
                $discountRanges['0-10%']++;
            } elseif ($percentage <= 20) {
                $discountRanges['10-20%']++;
            } elseif ($percentage <= 30) {
                $discountRanges['20-30%']++;
            } elseif ($percentage <= 40) {
                $discountRanges['30-40%']++;
            } elseif ($percentage <= 50) {
                $discountRanges['40-50%']++;
            } else {
                $discountRanges['50%+']++;
            }
        }
        
        // ─── Discount Effectiveness ────────────────────────────────
        $discountEffectiveness = (object)[
            'with_discount_avg' => $ordersWithDiscount->average_order_value ?? 0,
            'without_discount_avg' => $ordersWithoutDiscount->average_order_value ?? 0,
            'difference' => ($ordersWithDiscount->average_order_value ?? 0) - ($ordersWithoutDiscount->average_order_value ?? 0),
            'percentage_difference' => $ordersWithoutDiscount->average_order_value > 0 ? 
                ((($ordersWithDiscount->average_order_value ?? 0) - ($ordersWithoutDiscount->average_order_value ?? 0)) / $ordersWithoutDiscount->average_order_value) * 100 : 0,
            'with_discount_count' => $ordersWithDiscount->order_count ?? 0,
            'without_discount_count' => $ordersWithoutDiscount->order_count ?? 0,
        ];
        
        // ─── Pagination ──────────────────────────────────────────────
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min($perPage, max($discountedOrders->count(), 1));
        $paginatedOrders = $discountedOrders->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $discountedOrdersPaginated = new LengthAwarePaginator(
            $paginatedOrders,
            $discountedOrders->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        
        // ─── Get Filter Options ─────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $employeesList = User::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
        
        // ─── Return View ────────────────────────────────────────────
        return view('reports.orders.discount-analysis', compact(
            'discountedOrders',
            'discountedOrdersPaginated',
            'discountSummary',
            'discountByEmployee',
            'discountByDay',
            'discountByHour',
            'ordersWithDiscount',
            'ordersWithoutDiscount',
            'discountRanges',
            'discountEffectiveness',
            'locations',
            'departments',
            'employeesList',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'employeeId',
            'perPage'
        ));
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
        
        // ─── Date Range ──────────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $forecastDays = $request->get('forecast_days', 30);
        
        // ─── Filter Parameters ──────────────────────────────────────
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        
        // ─── Pagination Parameters ──────────────────────────────────
        $historicalPerPage = $request->get('historical_per_page', 15);
        $forecastPerPage = $request->get('forecast_per_page', 15);
        
        // ─── Validate Dates ─────────────────────────────────────────
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // ─── Build Orders Query ─────────────────────────────────────
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed'); // ✅ Fixed: Use 'completed' only
        
        // Apply location filter
        if ($locationId) {
            $ordersQuery->where('location_id', $locationId);
        }
        
        // Apply department filter
        if ($departmentId) {
            $ordersQuery->where('department_id', $departmentId);
        }
        
        $orders = $ordersQuery->get();
        
        // ─── Historical Data Collection ─────────────────────────────
        $historicalDataCollection = $orders
            ->groupBy(function($order) {
                return $order->created_at->format('Y-m-d');
            })
            ->map(function($dailyOrders, $date) {
                return (object)[
                    'date' => $date,
                    'order_count' => $dailyOrders->count(),
                    'daily_sales' => $dailyOrders->sum('total'),
                    'average_order_value' => $dailyOrders->avg('total') ?? 0,
                ];
            })
            ->sortKeysDesc()
            ->values();
        
        // ─── Pagination for Historical Data ─────────────────────────
        $currentPage = Paginator::resolveCurrentPage('historical_page');
        $historicalData = new LengthAwarePaginator(
            $historicalDataCollection->slice(($currentPage - 1) * $historicalPerPage, $historicalPerPage)->values(),
            $historicalDataCollection->count(),
            $historicalPerPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'historical_page']
        );
        
        // ─── Calculate Trends ───────────────────────────────────────
        $trends = $this->calculateSalesTrends($historicalDataCollection);
        
        // ─── Generate Forecast ──────────────────────────────────────
        $forecast = $this->generateForecast($historicalDataCollection, $trends, $forecastDays);
        
        // ─── Pagination for Forecast Data ───────────────────────────
        $forecastCollection = collect($forecast);
        $currentForecastPage = Paginator::resolveCurrentPage('forecast_page');
        $forecastData = new LengthAwarePaginator(
            $forecastCollection->slice(($currentForecastPage - 1) * $forecastPerPage, $forecastPerPage)->values(),
            $forecastCollection->count(),
            $forecastPerPage,
            $currentForecastPage,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'forecast_page']
        );
        
        // ─── Seasonality Analysis ───────────────────────────────────
        $seasonality = $orders
            ->groupBy(function($order) {
                return $order->created_at->dayOfWeek;
            })
            ->map(function($dayOrders, $dayOfWeek) {
                return (object)[
                    'day_of_week' => (int)$dayOfWeek,
                    'order_count' => $dayOrders->count(),
                    'average_sales' => $dayOrders->avg('total') ?? 0,
                    'total_sales' => $dayOrders->sum('total'),
                    'day_name' => $this->getDayNameFromNumber($dayOfWeek),
                ];
            })
            ->sortKeys()
            ->values();
        
        // ─── Growth Rate ─────────────────────────────────────────────
        $growthRate = $this->calculateGrowthRate($historicalDataCollection);
        
        // ─── Historical Average ─────────────────────────────────────
        $historicalAvg = $historicalDataCollection->avg('daily_sales') ?? 0;
        
        // ─── Get Filter Options ─────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // ─── Return View ─────────────────────────────────────────────
        return view('reports.orders.sales-forecast', compact(
            'historicalData',
            'historicalDataCollection',
            'forecast',
            'forecastData',
            'forecastCollection',
            'seasonality',
            'trends',
            'growthRate',
            'historicalAvg',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'forecastDays',
            'historicalPerPage',
            'forecastPerPage',
            'locationId',
            'departmentId'
        ));
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
        
        // ─── Check if Single Shop or Multi-Shop ──────────────────────
        $isSingleShop = tenant_is_single_shop($tenantId);
        
        // ─── Date Range ──────────────────────────────────────────────
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // ─── Filter Parameters ──────────────────────────────────────
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        
        // ─── Pagination Parameters ──────────────────────────────────
        $soldPerPage = $request->get('sold_per_page', 15);
        $unsoldPerPage = $request->get('unsold_per_page', 15);
        $deadStockPerPage = $request->get('dead_stock_per_page', 10);
        
        // ─── Days in Period ──────────────────────────────────────────
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        
        // ─── Get All Active Products ─────────────────────────────────
        $allProducts = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
        
        // ─── Get Sold Products from OrderItems ──────────────────────
        $soldProductsCollection = $this->getSoldProducts($tenantId, $startDate, $endDate, $locationId, $departmentId, $daysInPeriod, $isSingleShop);
        
        // ─── Get Sold Product IDs ───────────────────────────────────
        $soldProductIds = $soldProductsCollection->pluck('id')->toArray();
        
        // ─── Get Unsold Products ─────────────────────────────────────
        $unsoldProductsCollection = $this->getUnsoldProducts($allProducts, $soldProductIds, $tenantId, $isSingleShop, $locationId, $departmentId);
        
        // ─── Calculate Inventory Metrics ─────────────────────────────
        $totalInventoryValue = $this->calculateTotalInventoryValue($allProducts, $tenantId, $isSingleShop, $locationId, $departmentId);
        $soldInventoryValue = $soldProductsCollection->sum('revenue_generated');
        $turnoverRate = $totalInventoryValue > 0 ? ($soldInventoryValue / $totalInventoryValue) * 100 : 0;
        
        // ─── Stock Aging Analysis ────────────────────────────────────
        $stockAging = $this->calculateStockAging($soldProductsCollection, $unsoldProductsCollection);
        
        // ─── Dead Stock ──────────────────────────────────────────────
        $deadStockCollection = $unsoldProductsCollection->filter(function($product) {
            return $product->current_stock > 10;
        })->values();
        
        // ─── Apply Pagination ─────────────────────────────────────────
        $soldProducts = $this->paginateCollection($soldProductsCollection, $soldPerPage, 'sold_page');
        $unsoldProducts = $this->paginateCollection($unsoldProductsCollection, $unsoldPerPage, 'unsold_page');
        $deadStock = $this->paginateCollection($deadStockCollection, $deadStockPerPage, 'dead_stock_page');
        
        $productMovement = $soldProductsCollection;
        
        // ─── Get Filter Options ─────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // ─── Return View ─────────────────────────────────────────────
        return view('reports.orders.inventory-sales', compact(
            'soldProducts',
            'soldProductsCollection',
            'unsoldProducts',
            'unsoldProductsCollection',
            'deadStock',
            'deadStockCollection',
            'productMovement',
            'totalInventoryValue',
            'soldInventoryValue',
            'turnoverRate',
            'stockAging',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'soldPerPage',
            'unsoldPerPage',
            'deadStockPerPage',
            'daysInPeriod',
            'isSingleShop'
        ));
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
            ->whereHas('invoices', function($query) {
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
        $validOrderIds = array_merge($completedOrderIds, $confirmedOrderIds, $posInvoiceOrderIds);
        $validOrderIds = array_unique($validOrderIds);
        
        // ─── Build OrderItems Query ──────────────────────────────────
        $orderItemsQuery = OrderItem::whereHas('order', function($query) use ($tenantId, $validOrderIds, $locationId, $departmentId) {
            $query->where('tenant_id', $tenantId)
                ->whereIn('id', $validOrderIds);
            
            if ($locationId) {
                $query->where('location_id', $locationId);
            }
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }
        })
        ->with(['productVariant', 'order']);
        
        $orderItems = $orderItemsQuery->get();
        
        // ─── Build Sold Products Collection ─────────────────────────
        return $orderItems
            ->groupBy('variant_id')
            ->map(function($items, $variantId) use ($daysInPeriod, $tenantId, $isSingleShop, $locationId, $departmentId) {
                $firstItem = $items->first();
                $variant = $firstItem->productVariant;
                
                if (!$variant) return null;
                
                $dailySalesRate = $items->sum('quantity') / max($daysInPeriod, 1);
                
                if ($dailySalesRate >= 1) {
                    $movementCategory = 'Fast Mover';
                } elseif ($dailySalesRate >= 0.1) {
                    $movementCategory = 'Medium Mover';
                } else {
                    $movementCategory = 'Slow Mover';
                }
                
                // ─── Get Current Stock ────────────────────────────────────
                $currentStock = $this->getVariantStock($variant->id, $tenantId, $isSingleShop, $locationId, $departmentId);
                
                return (object)[
                    'id' => $variant->id,
                    'sku' => $variant->sku ?? 'N/A',
                    'name' => $variant->name ?? 'Unknown',
                    'price' => $variant->selling_price ?? 0,
                    'current_stock' => $currentStock,
                    'quantity_sold' => $items->sum('quantity'),
                    'revenue_generated' => $items->sum('total_price'),
                    'average_selling_price' => $items->avg('unit_price') ?? 0,
                    'times_ordered' => $items->unique('order_id')->count(),
                    'last_sold_date' => $items->max('created_at'),
                    'daily_sales_rate' => $dailySalesRate,
                    'movement_category' => $movementCategory,
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
            ->map(function($product) use ($isSingleShop, $locationId, $departmentId) {
                $currentStock = $this->getVariantStock($product->id, $product->tenant_id, $isSingleShop, $locationId, $departmentId);
                
                return (object)[
                    'id' => $product->id,
                    'sku' => $product->sku ?? 'N/A',
                    'name' => $product->name ?? 'Unknown',
                    'price' => $product->selling_price ?? 0,
                    'current_stock' => $currentStock,
                    'stock_value' => $currentStock * ($product->selling_price ?? 0),
                ];
            })
            ->sortByDesc('current_stock')
            ->values();
    }
    
    /**
     * Get variant stock based on shop type
     */
    private function getVariantStock($variantId, $tenantId, $isSingleShop, $locationId = null, $departmentId = null)
    {
        if ($isSingleShop) {
            // ─── Single Shop: Use SingleShopInventoryLog ──────────────
            $latestLog = SingleShopInventoryLog::where('variant_id', $variantId)
                ->where('tenant_id', $tenantId)
                ->latest('created_at')
                ->first();
            
            return $latestLog ? (int)$latestLog->quantity_after : 0;
            
        } else {
            // ─── Multi-Shop: Use InventoryItems ────────────────────────
            $query = InventoryItems::where('variant_id', $variantId)
                ->where('tenant_id', $tenantId);
            
            if ($locationId) {
                $query->where('location_id', $locationId);
            }
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }
            
            return (int)$query->sum('quantity_on_hand');
        }
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

    

}