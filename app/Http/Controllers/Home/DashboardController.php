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
        
        // Today's stats
        $todayStats = [
            'sales' => Order::where('tenant_id', $tenantId)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['completed', 'processing'])
                ->sum('total') / 100, // Convert from cents
            
            'orders' => Order::where('tenant_id', $tenantId)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['completed', 'processing'])
                ->count(),
            
            'customers' => Order::where('tenant_id', $tenantId)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['completed', 'processing'])
                ->distinct('customer_id')
                ->count('customer_id'),
            
            'profit' => $this->calculateTodayProfit($tenantId, $today),
        ];
        
        // Weekly sales trend (last 7 days)
        $weeklySales = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()])
            ->whereIn('status', ['completed', 'processing'])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total_sales'),
                DB::raw('COUNT(*) as order_count')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                $item->total_sales = $item->total_sales / 100;
                $item->day_name = Carbon::parse($item->date)->format('D');
                return $item;
            });
        
        // Best selling products
        $bestSellers = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [Carbon::now()->subDays(30), Carbon::now()])
            ->whereIn('orders.status', ['completed', 'processing'])
            ->select(
                'product_variants.id',
                'product_variants.name',
                'product_variants.sku',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total_price) as total_revenue')
            )
            ->groupBy('product_variants.id', 'product_variants.name', 'product_variants.sku')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                $item->total_revenue = $item->total_revenue / 100;
                return $item;
            });
        
        // Top categories
        $topCategories = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [Carbon::now()->subDays(30), Carbon::now()])
            ->whereIn('orders.status', ['completed', 'processing'])
            ->select(
                'product_categories.id',
                'product_categories.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total_price) as total_revenue')
            )
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                $item->total_revenue = $item->total_revenue / 100;
                return $item;
            });
        
        // Recent orders
        $recentOrders = Order::where('tenant_id', $tenantId)
            ->with(['customer', 'orderCreater'])
            ->whereIn('status', ['completed', 'processing', 'confirmed'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($order) {
                $order->total = $order->total / 100;
                return $order;
            });
        
        // Inventory alerts
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
        
        // User sessions (active users for this tenant)
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
        
        // Key metrics comparison (today vs yesterday)
        $yesterdayStats = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', Carbon::yesterday())
            ->whereIn('status', ['completed', 'processing'])
            ->select(
                DB::raw('SUM(total) as total_sales'),
                DB::raw('COUNT(*) as order_count')
            )
            ->first();
        
        $yesterdaySales = ($yesterdayStats->total_sales ?? 0) / 100;
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
        $todayOrders = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereDate('orders.created_at', $today)
            ->whereIn('orders.status', ['completed', 'processing'])
            ->select(
                DB::raw('SUM((order_items.unit_price - product_variants.cost_price) * order_items.quantity) as total_profit')
            )
            ->first();
        
        return ($todayOrders->total_profit ?? 0) / 100;
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
        $filterType = $request->get('filter_type', 'today'); // today, yesterday, this_week, this_month, custom
        
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
        
        // Financial Summary
        $financialSummary = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereIn('status', ['completed', 'processing'])
            ->select(
                DB::raw('SUM(total) as total_sales'),
                DB::raw('SUM(tax_total) as total_tax'),
                DB::raw('SUM(discount_total) as total_discounts'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('AVG(total) as average_order')
            )
            ->first();
        
        // Calculate profit
        $profitData = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startDateTime, $endDateTime])
            ->whereIn('orders.status', ['completed', 'processing'])
            ->select(
                DB::raw('SUM((order_items.unit_price - product_variants.cost_price) * order_items.quantity) as gross_profit'),
                DB::raw('SUM(order_items.total_price) as revenue')
            )
            ->first();
        
        // Payment method breakdown
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
                $item->total_amount = $item->total_amount / 100;
                return $item;
            });
        
        // Hourly breakdown for the selected day(s)
        $hourlyBreakdown = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereIn('status', ['completed', 'processing'])
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as hourly_total')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get()
            ->map(function($item) {
                $item->hourly_total = $item->hourly_total / 100;
                return $item;
            });
        
        // Top transactions
        $topTransactions = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereIn('status', ['completed', 'processing'])
            ->with(['customer', 'orderCreater'])
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(function($order) {
                $order->total = $order->total / 100;
                return $order;
            });
        
        // Expense summary (refunds, discounts)
        $expenseSummary = [
            'refunds' => Order::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->where('type', 'return')
                ->sum('total') / 100,
            'discounts' => Order::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->whereIn('status', ['completed', 'processing'])
                ->sum('discount_total') / 100,
            'tax_collected' => Order::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->whereIn('status', ['completed', 'processing'])
                ->sum('tax_total') / 100,
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
