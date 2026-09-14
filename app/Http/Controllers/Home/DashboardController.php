<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\{ ProductVariant, InventoryItems, OrderPayment };
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\{ DB, Artisan };
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        Artisan::call('optimize:clear');
        
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        // ✅ Today's stats - Using models with accessors
        $todayOrders = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['completed', 'processing'])
            ->get();
        
        $todayStats = [
            'sales' => $todayOrders->sum('total'), // Accessor converts from cents
            'orders' => $todayOrders->count(),
            'customers' => $todayOrders->pluck('customer_id')->unique()->count(),
            'profit' => $this->calculateTodayProfit($tenantId, $today),
        ];
        
        // ✅ Weekly sales trend - Using models
        $weeklySales = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayName = $date->format('D');
            $dateStr = $date->format('Y-m-d');
            
            $dayOrders = Order::where('tenant_id', $tenantId)
                ->whereDate('created_at', $dateStr)
                ->whereIn('status', ['completed', 'processing'])
                ->get();
            
            $weeklySales->push((object)[
                'date'        => $dateStr,
                'total_sales' => $dayOrders->sum('total'), // Accessor converts
                'order_count' => $dayOrders->count(),
                'day_name'    => $dayName,
            ]);
        }
        
        // ✅ Best selling products - Using models
        $bestSellers = OrderItem::whereHas('order', function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()])
                    ->whereIn('status', ['completed', 'processing']);
            })
            ->with('variant')
            ->get()
            ->groupBy('variant_id')
            ->map(function($items) {
                $variant = $items->first()->variant;
                return (object)[
                    'id' => $variant->id ?? null,
                    'name' => $variant->name ?? 'Unknown',
                    'sku' => $variant->sku ?? '',
                    'total_quantity' => $items->sum('quantity'),
                    'total_revenue' => $items->sum(function($item) {
                        return $item->unit_price * $item->quantity; // Accessor on unit_price
                    }),
                ];
            })
            ->sortByDesc('total_quantity')
            ->take(5)
            ->values();
        
        // ✅ Top categories - Using models
        $topCategories = OrderItem::whereHas('order', function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()])
                    ->whereIn('status', ['completed', 'processing']);
            })
            ->with(['variant.product.category'])
            ->get()
            ->filter(function($item) {
                return $item->variant && $item->variant->product && $item->variant->product->category;
            })
            ->groupBy('variant.product.category_id')
            ->map(function($items) {
                $category = $items->first()->variant->product->category;
                return (object)[
                    'id' => $category->id,
                    'name' => $category->name,
                    'total_quantity' => $items->sum('quantity'),
                    'total_revenue' => $items->sum(function($item) {
                        return $item->unit_price * $item->quantity;
                    }),
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(5)
            ->values();
        
        // ✅ Recent orders
        $recentOrders = Order::where('tenant_id', $tenantId)
            ->with(['customer', 'orderCreater'])
            ->whereIn('status', ['completed', 'processing', 'confirmed'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(); // Accessor on 'total' handles conversion
        
        // ✅ Inventory alerts - Handle both single-shop and multi-shop
        $isSingleShop = tenant_is_single_shop($tenantId);

        $lowStockItems = collect();
        $outOfStockItems = 0;

        if ($isSingleShop) {
            // ============================================
            // SINGLE SHOP: Use ProductVariant.overal_quantity_at_hand
            // ============================================
            $lowStockItems = ProductVariant::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereColumn('overal_quantity_at_hand', '<=', 'low_stock_level')
                ->where('overal_quantity_at_hand', '>', 0)
                ->orderBy('overal_quantity_at_hand')
                ->limit(5)
                ->get()
                ->map(function ($variant) {
                    return (object) [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'quantity' => $variant->overal_quantity_at_hand,
                        'threshold' => $variant->low_stock_level,
                        'location_name' => null,
                        'department_name' => null,
                    ];
                });

            $outOfStockItems = ProductVariant::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('overal_quantity_at_hand', '<=', 0)
                ->count();

        } else {
            // ============================================
            // MULTI-SHOP: Use InventoryItems.quantity_allocated vs preferred_stock_level
            // Group by location + department + variant (unique combinations)
            // ============================================
            
            // Low stock items
            $lowStockItems = InventoryItems::where('tenant_id', $tenantId)
                ->whereColumn('quantity_allocated', '<=', 'preferred_stock_level')
                ->where('quantity_allocated', '>', 0)
                ->whereHas('variant', function ($q) {
                    $q->where('is_active', true);
                })
                ->with(['variant', 'itemLocation', 'departmentItem'])
                ->get()
                // ✅ Ensure unique variant per location + department
                ->groupBy(function ($item) {
                    return $item->variant_id . '_' . $item->location_id . '_' . $item->department_id;
                })
                ->map(function ($group) {
                    // Take the first item of each unique combination
                    $item = $group->first();
                    return (object) [
                        'id' => $item->variant_id,
                        'name' => $item->variant->name ?? 'Unknown',
                        'sku' => $item->variant->sku ?? '',
                        'quantity' => $item->quantity_allocated,
                        'threshold' => $item->preferred_stock_level,
                        'location_name' => $item->itemLocation->name ?? 'N/A',
                        'department_name' => $item->departmentItem->name ?? 'N/A',
                    ];
                })
                ->sortBy('quantity')
                ->take(5)
                ->values();

            // Out of stock items - also unique per location + department
            $outOfStockItems = InventoryItems::where('tenant_id', $tenantId)
                ->where('quantity_allocated', '<=', 0)
                ->whereHas('variant', function ($q) {
                    $q->where('is_active', true);
                })
                ->get()
                ->groupBy(function ($item) {
                    return $item->variant_id . '_' . $item->location_id . '_' . $item->department_id;
                })
                ->count();
        }

        // Also get the total count of low stock items (for badge display)
        $lowStockCount = $lowStockItems->count();
        
        $outOfStockItems = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('overal_quantity_at_hand', '<=', 0)
            ->count();
        
        // ✅ User sessions (active users for this tenant)
        $activeUsers = DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->where('users.tenant_id', $tenantId)
            ->where('users.status', 'active')
            ->where('sessions.last_activity', '>=', Carbon::now()->subMinutes(15)->timestamp)
            ->whereNotNull('sessions.user_id')
            ->distinct('sessions.user_id')
            ->count('sessions.user_id');

        // Get detailed active users list for the table
        $activeUsersList = DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->leftJoin('locations', 'users.location_id', '=', 'locations.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->where('users.tenant_id', $tenantId)
            ->where('users.status', 'active')
            ->where('sessions.last_activity', '>=', Carbon::now()->subMinutes(15)->timestamp)
            ->whereNotNull('sessions.user_id')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.profile_image',
                'users.job_title',
                'locations.name as location_name',
                'departments.name as department_name',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity'
            )
            ->orderBy('sessions.last_activity', 'desc')
            ->get()
            ->map(function($session) {
                $session->last_seen = Carbon::createFromTimestamp($session->last_activity)->diffForHumans();
                $session->full_name = $session->first_name . ' ' . $session->last_name;
                
                // Parse user agent for device info (simplified)
                $ua = $session->user_agent;
                if (strpos($ua, 'Mobile') !== false) {
                    $session->device = 'Mobile';
                    $session->device_icon = 'fa-mobile-alt';
                } elseif (strpos($ua, 'Tablet') !== false) {
                    $session->device = 'Tablet';
                    $session->device_icon = 'fa-tablet-alt';
                } else {
                    $session->device = 'Desktop';
                    $session->device_icon = 'fa-desktop';
                }
                
                // Browser detection (simplified)
                if (strpos($ua, 'Chrome') !== false) {
                    $session->browser = 'Chrome';
                } elseif (strpos($ua, 'Firefox') !== false) {
                    $session->browser = 'Firefox';
                } elseif (strpos($ua, 'Safari') !== false) {
                    $session->browser = 'Safari';
                } elseif (strpos($ua, 'Edge') !== false) {
                    $session->browser = 'Edge';
                } else {
                    $session->browser = 'Other';
                }
                
                return $session;
            });
        
        // ✅ Key metrics comparison (today vs yesterday)
        $yesterdayOrders = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', Carbon::yesterday())
            ->whereIn('status', ['completed', 'processing'])
            ->get();
        
        $yesterdaySales = $yesterdayOrders->sum('total'); // Accessor converts
        $salesChange = $yesterdaySales > 0 
            ? (($todayStats['sales'] - $yesterdaySales) / $yesterdaySales) * 100 
            : 100;
        
        return view('dashboard.dashboard', compact(
            'todayStats',
            'weeklySales',
            'bestSellers',
            'topCategories',
            'recentOrders',
            'lowStockItems',
            'outOfStockItems',
            'activeUsers',
            'activeUsersList',
            'salesChange',
            'yesterdaySales'
        ));
    }
    
    private function calculateTodayProfit($tenantId, $today)
    {
        $todayOrderItems = OrderItem::whereHas('order', function($query) use ($tenantId, $today) {
                $query->where('tenant_id', $tenantId)
                    ->whereDate('created_at', $today)
                    ->whereIn('status', ['completed']);
            })
            ->with('variant')
            ->get();
        
        $totalProfit = 0;
        foreach ($todayOrderItems as $item) {
            if ($item->variant) {
                $profit = ($item->selling_price - $item->variant->grand_total_cost_price) * $item->quantity;
                $totalProfit += $profit;
            }
        }
        
        return $totalProfit;
    }
    
    public function overview(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('view financial dashboard')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get date filters
        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));
        $filterType = $request->get('filter_type', 'today');
        
        switch($filterType) {
            case 'yesterday':
                $startDate = Carbon::yesterday()->format('Y-m-d');
                $endDate = Carbon::yesterday()->format('Y-m-d');
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
                $endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
        }
        
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        // ✅ Financial Summary
        $filteredOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereIn('status', ['completed', 'processing'])
            ->get();
        
        $financialSummary = (object)[
            'total_sales' => $filteredOrders->sum('total'),
            'total_tax' => $filteredOrders->sum('tax_total'),
            'total_discounts' => $filteredOrders->sum('discount_total'),
            'order_count' => $filteredOrders->count(),
            'average_order' => $filteredOrders->count() > 0 ? $filteredOrders->avg('total') : 0,
        ];
        
        // ✅ Profit
        $profitItems = OrderItem::whereHas('order', function($query) use ($tenantId, $startDateTime, $endDateTime) {
                $query->where('tenant_id', $tenantId)
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->whereIn('status', ['completed', 'processing']);
            })
            ->with('variant')
            ->get();
        
        $grossProfit = 0;
        $revenue = 0;
        foreach ($profitItems as $item) {
            $revenue += $item->unit_price * $item->quantity;
            if ($item->variant) {
                $grossProfit += ($item->unit_price - $item->variant->cost_price) * $item->quantity;
            }
        }
        
        $profitData = (object)[
            'gross_profit' => $grossProfit,
            'revenue' => $revenue,
        ];
        
        // ✅ Payment method breakdown — Eloquent only
        $paymentBreakdown = OrderPayment::whereHas('order', function ($q) use ($tenantId, $startDateTime, $endDateTime) {
                $q->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDateTime, $endDateTime]);
            })
            ->where('status', 'completed')
            ->with('paymentMethod')
            ->get()
            ->groupBy('payment_method_id')
            ->map(function ($group) {
                $method = $group->first()->paymentMethod;
                return (object) [
                    'name'              => $method->name ?? 'Unknown',
                    'type'              => $method->type ?? 'unknown',
                    'transaction_count' => $group->count(),
                    'total_amount'      => $group->sum('amount'), // accessor
                ];
            })
            ->sortByDesc('total_amount')
            ->values();
        
        // ✅ Hourly breakdown
        $hourlyBreakdown = collect();
        for ($hour = 0; $hour < 24; $hour++) {
            $hourOrders = $filteredOrders->filter(fn($order) => $order->created_at->hour == $hour);
            $hourlyBreakdown->push((object)[
                'hour' => $hour,
                'order_count' => $hourOrders->count(),
                'hourly_total' => $hourOrders->sum('total'),
            ]);
        }
        
        // ✅ Top transactions
        $topTransactions = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereIn('status', ['completed', 'processing'])
            ->with(['customer', 'orderCreater'])
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();
        
        // ✅ Expense summary
        $refundOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('type', 'return')
            ->get();
        
        $expenseSummary = [
            'refunds' => $refundOrders->sum('total'),
            'discounts' => $filteredOrders->sum('discount_total'),
            'tax_collected' => $filteredOrders->sum('tax_total'),
        ];
        
        return view('dashboard.overview', compact(
            'financialSummary',
            'profitData',
            'paymentBreakdown',
            'hourlyBreakdown',
            'topTransactions',
            'expenseSummary',
            'startDate',
            'endDate',
            'filterType'
        ));
    }

}