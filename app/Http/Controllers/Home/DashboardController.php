<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
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
        
        // ✅ Inventory alerts
        $lowStockItems = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('overal_quantity_at_hand', '<', 10)
            ->where('overal_quantity_at_hand', '>', 0)
            ->orderBy('overal_quantity_at_hand')
            ->limit(5)
            ->get();
        
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
                    ->whereIn('status', ['completed', 'processing']);
            })
            ->with('variant')
            ->get();
        
        $totalProfit = 0;
        foreach ($todayOrderItems as $item) {
            if ($item->variant) {
                $profit = ($item->unit_price - $item->variant->cost_price) * $item->quantity;
                $totalProfit += $profit;
            }
        }
        
        return $totalProfit; // Accessor on cost_price and unit_price handle conversion
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
        
        // Adjust dates based on filter type
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
        
        // Parse dates for queries
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        // ✅ Financial Summary - Using models
        $filteredOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereIn('status', ['completed', 'processing'])
            ->get();
        
        $financialSummary = (object)[
            'total_sales' => $filteredOrders->sum('total'), // Accessor
            'total_tax' => $filteredOrders->sum('tax_total'), // Accessor
            'total_discounts' => $filteredOrders->sum('discount_total'), // Accessor
            'order_count' => $filteredOrders->count(),
            'average_order' => $filteredOrders->count() > 0 ? $filteredOrders->avg('total') : 0,
        ];
        
        // ✅ Calculate profit - Using models
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
        
        // ✅ Payment method breakdown
        $paymentBreakdown = DB::table('order_payments')
            ->join('orders', 'order_payments.order_id', '=', 'orders.id')
            ->join('payment_methods', 'order_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startDateTime, $endDateTime])
            ->where('order_payments.status', 'completed')
            ->select(
                'payment_methods.name',
                'payment_methods.type',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(order_payments.amount) as total_amount')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name', 'payment_methods.type')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->map(function($item) {
                // PaymentMethod model accessor doesn't apply here since we're using DB
                // But we can manually convert
                $item->total_amount = $item->total_amount / 100;
                return $item;
            });
        
        // ✅ Hourly breakdown - Using models
        $hourlyBreakdown = collect();
        for ($hour = 0; $hour < 24; $hour++) {
            $hourOrders = $filteredOrders->filter(function($order) use ($hour) {
                return $order->created_at->hour == $hour;
            });
            
            $hourlyBreakdown->push((object)[
                'hour' => $hour,
                'order_count' => $hourOrders->count(),
                'hourly_total' => $hourOrders->sum('total'), // Accessor
            ]);
        }
        
        // ✅ Top transactions
        $topTransactions = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereIn('status', ['completed', 'processing'])
            ->with(['customer', 'orderCreater'])
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get(); // Accessor on total handles conversion
        
        // ✅ Expense summary (refunds, discounts)
        $refundOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('type', 'return')
            ->get();
        
        $expenseSummary = [
            'refunds' => $refundOrders->sum('total'), // Accessor
            'discounts' => $filteredOrders->sum('discount_total'), // Accessor
            'tax_collected' => $filteredOrders->sum('tax_total'), // Accessor
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