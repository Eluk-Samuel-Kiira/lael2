<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Department;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\{ DB };
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class OrderReportsController extends Controller
{
    // Order Summary Report - IMPROVED VERSION
    public function summary(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
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
        
        // Build base query with filters
        $query = $this->buildOrderQuery($tenantId, $startDate, $endDate, [
            'location_id' => $locationId,
            'department_id' => $departmentId,
            'order_type' => $orderType,
            'order_status' => $orderStatus
        ]);
        
        // Get summary statistics
        $summary = $this->getOrderSummary($query);
        
        // Get daily breakdown for chart
        $dailyBreakdown = $this->getDailyBreakdown($tenantId, $startDate, $endDate, [
            'location_id' => $locationId,
            'department_id' => $departmentId,
            'order_type' => $orderType,
            'order_status' => $orderStatus
        ]);
        
        // Get orders by hour
        $hourlyBreakdown = $this->getHourlyBreakdown($tenantId, $startDate, $endDate, [
            'location_id' => $locationId,
            'department_id' => $departmentId,
            'order_type' => $orderType,
            'order_status' => $orderStatus
        ]);
        
        // Get orders by type with percentages
        $typeBreakdown = $this->getTypeBreakdown($tenantId, $startDate, $endDate, [
            'location_id' => $locationId,
            'department_id' => $departmentId,
            'order_type' => $orderType,
            'order_status' => $orderStatus
        ]);
        
        // Get orders by status with percentages
        $statusBreakdown = $this->getStatusBreakdown($tenantId, $startDate, $endDate, [
            'location_id' => $locationId,
            'department_id' => $departmentId,
            'order_type' => $orderType,
            'order_status' => $orderStatus
        ]);
        
        // Add colors to breakdowns
        $typeBreakdown = $typeBreakdown->map(function($type) {
            $type->color = getOrderTypeColor($type->type);
            return $type;
        });
        
        $statusBreakdown = $statusBreakdown->map(function($status) {
            $status->color = getOrderStatusColor($status->status);
            return $status;
        });
        
        // Format daily breakdown with day names
        $dailyBreakdown = $dailyBreakdown->map(function($day) {
            $date = Carbon::parse($day->date);
            $day->day_name = $date->format('l');
            $day->is_weekend = in_array($day->day_name, ['Saturday', 'Sunday']);
            $day->formatted_date = $date->format('M d, Y');
            return $day;
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
            'locationId',
            'departmentId',
            'orderType',
            'orderStatus'
        ));
    }
    
    // Helper Methods for Query Building
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

    private function buildOrderQuery($tenantId, $startDate, $endDate, $filters = [])
    {
        $query = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
        // Apply location filter
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        // Apply department filter
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        
        // Apply order type filter
        if (!empty($filters['order_type']) && $filters['order_type'] !== 'all') {
            $query->where('type', $filters['order_type']);
        }
        
        // Apply order status filter
        if (!empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('status', $filters['order_status']);
        }
        
        return $query;
    }

    private function getOrderSummary($query)
    {
        $cloneQuery = clone $query;
        
        return [
            'total_orders' => $query->count(),
            'total_sales' => $cloneQuery->sum('total') ?? 0,
            'total_tax' => $cloneQuery->sum('tax_total') ?? 0,
            'total_discount' => $cloneQuery->sum('discount_total') ?? 0,
            'average_order_value' => $cloneQuery->avg('total') ?? 0,
            'max_order_value' => $cloneQuery->max('total') ?? 0,
            'min_order_value' => $cloneQuery->min('total') ?? 0,
            'total_paid' => $cloneQuery->sum('paid_amount') ?? 0,
            'total_balance' => $cloneQuery->sum('balance_due') ?? 0,
        ];
    }

    private function getDailyBreakdown($tenantId, $startDate, $endDate, $filters = [])
    {
        $query = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Apply filters
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        
        if (!empty($filters['order_type']) && $filters['order_type'] !== 'all') {
            $query->where('type', $filters['order_type']);
        }
        
        if (!empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('status', $filters['order_status']);
        }
        
        return $query->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as daily_total'),
                DB::raw('AVG(total) as daily_average'),
                DB::raw('SUM(tax_total) as daily_tax'),
                DB::raw('SUM(discount_total) as daily_discount')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    private function getHourlyBreakdown($tenantId, $startDate, $endDate, $filters = [])
    {
        $query = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('status', ['completed', 'processing', 'confirmed']); // Only count actual sales
        
        // Apply filters
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        
        if (!empty($filters['order_type']) && $filters['order_type'] !== 'all') {
            $query->where('type', $filters['order_type']);
        }
        
        if (!empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('status', $filters['order_status']);
        }
        
        return $query->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as hourly_total'),
                DB::raw('AVG(total) as hourly_average')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get();
    }

    private function getTypeBreakdown($tenantId, $startDate, $endDate, $filters = [])
    {
        $query = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Apply filters
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        
        if (!empty($filters['order_type']) && $filters['order_type'] !== 'all') {
            $query->where('type', $filters['order_type']);
        }
        
        if (!empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('status', $filters['order_status']);
        }
        
        $breakdown = $query->select(
                'type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('AVG(total) as average_amount'),
                DB::raw('SUM(tax_total) as total_tax'),
                DB::raw('SUM(discount_total) as total_discount')
            )
            ->groupBy('type')
            ->orderBy('total_amount', 'desc')
            ->get();
        
        // Calculate percentages
        $totalCount = $breakdown->sum('count');
        $totalAmount = $breakdown->sum('total_amount');
        
        return $breakdown->map(function($type) use ($totalCount, $totalAmount) {
            $type->percentage_count = $totalCount > 0 ? ($type->count / $totalCount) * 100 : 0;
            $type->percentage_amount = $totalAmount > 0 ? ($type->total_amount / $totalAmount) * 100 : 0;
            return $type;
        });
    }
    
    private function getStatusBreakdown($tenantId, $startDate, $endDate, $filters = [])
    {
        $query = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Apply filters
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        
        if (!empty($filters['order_type']) && $filters['order_type'] !== 'all') {
            $query->where('type', $filters['order_type']);
        }
        
        if (!empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('status', $filters['order_status']);
        }
        
        $breakdown = $query->select(
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('AVG(total) as average_amount'),
                DB::raw('SUM(tax_total) as total_tax'),
                DB::raw('SUM(discount_total) as total_discount')
            )
            ->groupBy('status')
            ->orderBy('total_amount', 'desc')
            ->get();
        
        // Calculate percentages
        $totalCount = $breakdown->sum('count');
        $totalAmount = $breakdown->sum('total_amount');
        
        return $breakdown->map(function($status) use ($totalCount, $totalAmount) {
            $status->percentage_count = $totalCount > 0 ? ($status->count / $totalCount) * 100 : 0;
            $status->percentage_amount = $totalAmount > 0 ? ($status->total_amount / $totalAmount) * 100 : 0;
            return $status;
        });
    }


    // Sales by Customer Report
    public function byCustomer(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Get and validate dates
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Additional filters
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $minSpent = $request->get('min_spent');
        $maxSpent = $request->get('max_spent');
        $minOrders = $request->get('min_orders');
        $maxOrders = $request->get('max_orders');
        
        // Build query
        $query = Order::where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('customer_id')
            ->join('customers', 'orders.customer_id', '=', 'customers.id');
        
        // Apply filters
        if ($locationId) {
            $query->where('orders.location_id', $locationId);
        }
        
        if ($departmentId) {
            $query->where('orders.department_id', $departmentId);
        }
        
        // Get customer sales breakdown
        $customerSales = $query->select(
                'customers.id',
                'customers.first_name',
                'customers.last_name',
                'customers.email',
                'customers.phone',
                'customers.city',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total) as total_spent'),
                DB::raw('SUM(orders.tax_total) as total_tax'),
                DB::raw('SUM(orders.discount_total) as total_discount'),
                DB::raw('AVG(orders.total) as average_order_value'),
                DB::raw('MAX(orders.total) as max_order_value'),
                DB::raw('MIN(orders.total) as min_order_value'),
                DB::raw('MAX(orders.created_at) as last_order_date')
            )
            ->groupBy('customers.id', 'customers.first_name', 'customers.last_name', 'customers.email', 'customers.phone', 'customers.city')
            ->havingRaw('COUNT(orders.id) > 0')
            ->orderBy('total_spent', 'desc')
            ->get();
        
        // Apply amount filters
        if ($minSpent && is_numeric($minSpent)) {
            $customerSales = $customerSales->where('total_spent', '>=', $minSpent);
        }
        
        if ($maxSpent && is_numeric($maxSpent)) {
            $customerSales = $customerSales->where('total_spent', '<=', $maxSpent);
        }
        
        if ($minOrders && is_numeric($minOrders)) {
            $customerSales = $customerSales->where('order_count', '>=', $minOrders);
        }
        
        if ($maxOrders && is_numeric($maxOrders)) {
            $customerSales = $customerSales->where('order_count', '<=', $maxOrders);
        }
        
        // Calculate customer loyalty segments
        $customerSegments = [
            'new' => $customerSales->where('order_count', 1)->count(),
            'returning' => $customerSales->where('order_count', '>', 1)->where('order_count', '<=', 5)->count(),
            'regular' => $customerSales->where('order_count', '>', 5)->where('order_count', '<=', 20)->count(),
            'vip' => $customerSales->where('order_count', '>', 20)->count(),
        ];
        
        // Top customers
        $topCustomers = $customerSales->take(10);
        
        // Get filter options
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.orders.by-customer', compact(
            'customerSales',
            'customerSegments',
            'topCustomers',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'minSpent',
            'maxSpent',
            'minOrders',
            'maxOrders'
        ));
    }
    
    // Sales by Product/Variant Report
    public function byProduct(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Get and validate dates
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Additional filters
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $minQuantity = $request->get('min_quantity');
        $maxQuantity = $request->get('max_quantity');
        $minRevenue = $request->get('min_revenue');
        $maxRevenue = $request->get('max_revenue');
        
        // Build product sales query
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('orders.status', ['completed', 'processing']);
        
        // Apply filters
        if ($locationId) {
            $query->where('orders.location_id', $locationId);
        }
        
        if ($departmentId) {
            $query->where('orders.department_id', $departmentId);
        }
        
        // Get product sales
        $productSales = $query->select(
                'product_variants.id as variant_id',
                'product_variants.sku',
                'product_variants.name as variant_name',
                'product_variants.price as current_price',
                'product_variants.overal_quantity_at_hand as current_stock',
                DB::raw('SUM(order_items.quantity) as total_quantity_sold'),
                DB::raw('SUM(order_items.total_price) as total_revenue'),
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as total_sales_value'),
                DB::raw('SUM(order_items.discount) as total_discount'),
                DB::raw('SUM(order_items.tax_amount) as total_tax'),
                DB::raw('AVG(order_items.unit_price) as average_selling_price'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('MAX(orders.created_at) as last_sold_date')
            )
            ->groupBy(
                'product_variants.id',
                'product_variants.sku',
                'product_variants.name',
                'product_variants.price',
                'product_variants.overal_quantity_at_hand'
            )
            ->orderBy('total_revenue', 'desc')
            ->get();
        
        // Apply quantity filters
        if ($minQuantity && is_numeric($minQuantity)) {
            $productSales = $productSales->where('total_quantity_sold', '>=', $minQuantity);
        }
        
        if ($maxQuantity && is_numeric($maxQuantity)) {
            $productSales = $productSales->where('total_quantity_sold', '<=', $maxQuantity);
        }
        
        // Apply revenue filters
        if ($minRevenue && is_numeric($minRevenue)) {
            $productSales = $productSales->where('total_revenue', '>=', $minRevenue);
        }
        
        if ($maxRevenue && is_numeric($maxRevenue)) {
            $productSales = $productSales->where('total_revenue', '<=', $maxRevenue);
        }
        
        // Calculate sales velocity
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $productSales = $productSales->map(function($product) use ($daysInPeriod) {
            $product->daily_sales_rate = $product->total_quantity_sold / max($daysInPeriod, 1);
            $product->daily_revenue_rate = $product->total_revenue / max($daysInPeriod, 1);
            
            // Categorize by sales velocity
            if ($product->daily_sales_rate >= 5) {
                $product->velocity_category = 'Fast Mover';
                $product->velocity_color = 'success';
            } elseif ($product->daily_sales_rate >= 1) {
                $product->velocity_category = 'Medium Mover';
                $product->velocity_color = 'warning';
            } else {
                $product->velocity_category = 'Slow Mover';
                $product->velocity_color = 'danger';
            }
            
            return $product;
        });
        
        // Get top and bottom performers
        $topProducts = $productSales->take(10);
        $bottomProducts = $productSales->sortBy('total_revenue')->take(10);
        
        // Get filter options
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.orders.by-product', compact(
            'productSales',
            'topProducts',
            'bottomProducts',
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
            'daysInPeriod'
        ));
    }
    
    // Payment Method Analysis Report
    public function byPaymentMethod(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Validate dates
        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
        
        // Get filter parameters
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $paymentType = $request->get('payment_type');
        
        // Build base query
        $paymentMethodQuery = DB::table('order_payments')
            ->join('orders', 'order_payments.order_id', '=', 'orders.id')
            ->leftJoin('payment_methods', 'order_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('order_payments.processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('order_payments.status', 'completed');
        
        // Apply location filter
        if ($locationId) {
            $paymentMethodQuery->where('orders.location_id', $locationId);
        }
        
        // Apply department filter
        if ($departmentId) {
            $paymentMethodQuery->where('orders.department_id', $departmentId);
        }
        
        // Apply payment type filter
        if ($paymentType && $paymentType !== 'all') {
            $paymentMethodQuery->where('payment_methods.type', $paymentType);
        }
        
        // Get payment method breakdown from order_payments
        $paymentMethodAnalysis = $paymentMethodQuery->select(
                'payment_methods.id',
                'payment_methods.name as method_name',
                'payment_methods.type as method_type',
                DB::raw('COUNT(order_payments.id) as transaction_count'),
                DB::raw('SUM(order_payments.amount) as total_amount'),
                DB::raw('AVG(order_payments.amount) as average_transaction'),
                DB::raw('MAX(order_payments.amount) as largest_transaction'),
                DB::raw('MIN(order_payments.amount) as smallest_transaction'),
                DB::raw('MAX(order_payments.processed_at) as last_transaction_date')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name', 'payment_methods.type')
            ->orderBy('total_amount', 'desc')
            ->get();
        
        // Payment method trends over time (with filters)
        $trendsQuery = DB::table('order_payments')
            ->join('orders', 'order_payments.order_id', '=', 'orders.id')
            ->leftJoin('payment_methods', 'order_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('order_payments.processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('order_payments.status', 'completed');
        
        // Apply filters to trends query
        if ($locationId) {
            $trendsQuery->where('orders.location_id', $locationId);
        }
        
        if ($departmentId) {
            $trendsQuery->where('orders.department_id', $departmentId);
        }
        
        if ($paymentType && $paymentType !== 'all') {
            $trendsQuery->where('payment_methods.type', $paymentType);
        }
        
        $paymentTrends = $trendsQuery->select(
                DB::raw('DATE(order_payments.processed_at) as date'),
                'payment_methods.type',
                DB::raw('COUNT(order_payments.id) as daily_count'),
                DB::raw('SUM(order_payments.amount) as daily_total')
            )
            ->groupBy(DB::raw('DATE(order_payments.processed_at)'), 'payment_methods.type')
            ->orderBy('date')
            ->get()
            ->groupBy('type');
        
        // Failed transactions (with filters)
        $failedQuery = DB::table('order_payments')
            ->join('orders', 'order_payments.order_id', '=', 'orders.id')
            ->leftJoin('payment_methods', 'order_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('order_payments.processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('order_payments.status', 'failed');
        
        // Apply filters to failed transactions query
        if ($locationId) {
            $failedQuery->where('orders.location_id', $locationId);
        }
        
        if ($departmentId) {
            $failedQuery->where('orders.department_id', $departmentId);
        }
        
        if ($paymentType && $paymentType !== 'all') {
            $failedQuery->where('payment_methods.type', $paymentType);
        }
        
        $failedTransactions = $failedQuery->select(
                'payment_methods.name',
                DB::raw('COUNT(order_payments.id) as failed_count'),
                DB::raw('SUM(order_payments.amount) as failed_amount')
            )
            ->groupBy('payment_methods.name')
            ->get();
        
        // Get filter options
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get();
        
        // Get unique payment types for filter dropdown
        $paymentTypes = DB::table('payment_methods')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->get()
            ->pluck('type');
        
        return view('reports.orders.by-payment-method', compact(
            'paymentMethodAnalysis',
            'paymentTrends',
            'failedTransactions',
            'locations',
            'departments',
            'paymentTypes',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'paymentType'
        ));
    }
        
    // Employee Performance Report
    public function byEmployee(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
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
        
        // Build query
        $query = Order::where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->join('users', 'orders.created_by', '=', 'users.id');
        
        // Apply filters
        if ($locationId) {
            $query->where('orders.location_id', $locationId);
        }
        
        if ($departmentId) {
            $query->where('orders.department_id', $departmentId);
        }
        
        if ($employeeId) {
            $query->where('orders.created_by', $employeeId);
        }
        
        // Get employee performance
        $employeePerformance = $query->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total) as total_sales'),
                DB::raw('SUM(orders.tax_total) as total_tax_collected'),
                DB::raw('SUM(orders.discount_total) as total_discount_given'),
                DB::raw('AVG(orders.total) as average_order_value'),
                DB::raw('MAX(orders.total) as largest_sale'),
                DB::raw('MIN(orders.total) as smallest_sale'),
                DB::raw('COUNT(DISTINCT customer_id) as unique_customers'),
                DB::raw('MAX(orders.created_at) as last_sale_date')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->orderBy('total_sales', 'desc')
            ->get();
        
        // Apply sales filters
        if ($minSales && is_numeric($minSales)) {
            $employeePerformance = $employeePerformance->where('total_sales', '>=', $minSales);
        }
        
        if ($maxSales && is_numeric($maxSales)) {
            $employeePerformance = $employeePerformance->where('total_sales', '<=', $maxSales);
        }
        
        // Calculate efficiency metrics
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $employeePerformance = $employeePerformance->map(function($employee) use ($daysInPeriod) {
            $employee->orders_per_day = $employee->order_count / max($daysInPeriod, 1);
            $employee->sales_per_day = $employee->total_sales / max($daysInPeriod, 1);
            
            // Performance rating
            if ($employee->sales_per_day >= 1000) {
                $employee->performance_rating = 'Excellent';
                $employee->rating_color = 'success';
            } elseif ($employee->sales_per_day >= 500) {
                $employee->performance_rating = 'Good';
                $employee->rating_color = 'primary';
            } elseif ($employee->sales_per_day >= 200) {
                $employee->performance_rating = 'Average';
                $employee->rating_color = 'warning';
            } else {
                $employee->performance_rating = 'Needs Improvement';
                $employee->rating_color = 'danger';
            }
            
            return $employee;
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
        
        $employees = User::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        return view('reports.orders.by-employee', compact(
            'employeePerformance',
            'locations',
            'departments',
            'employees',
            'startDate',
            'endDate',
            'locationId',
            'departmentId',
            'employeeId',
            'minSales',
            'maxSales',
            'daysInPeriod'
        ));
    }
        
    // Time-based Sales Report
    public function timeAnalysis(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $groupBy = $request->get('group_by', 'daily');
        
        // Validate and format dates
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Get filter parameters
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType = $request->get('order_type');
        
        // Build query
        $query = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('status', ['completed', 'processing']);
        
        // Apply filters
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($orderType && $orderType !== 'all') {
            $query->where('type', $orderType);
        }
        
        // Get time-based analysis based on group_by parameter
        switch ($groupBy) {
            case 'hourly':
                $timeAnalysis = $query->select(
                    DB::raw('HOUR(created_at) as time_period'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('AVG(total) as average_sale'),
                    DB::raw('SUM(tax_total) as total_tax'),
                    DB::raw('SUM(discount_total) as total_discount')
                )
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->orderBy('time_period')
                ->get();
                break;
                
            case 'weekly':
                $timeAnalysis = $query->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('WEEK(created_at) as week_number'),
                    DB::raw('MIN(DATE(created_at)) as week_start'),
                    DB::raw('MAX(DATE(created_at)) as week_end'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('AVG(total) as average_sale'),
                    DB::raw('SUM(tax_total) as total_tax'),
                    DB::raw('SUM(discount_total) as total_discount')
                )
                ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('WEEK(created_at)'))
                ->orderBy('year', 'desc')
                ->orderBy('week_number', 'desc')
                ->get();
                break;
                
            case 'monthly':
                $timeAnalysis = $query->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month_number'),
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_period'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('AVG(total) as average_sale'),
                    DB::raw('SUM(tax_total) as total_tax'),
                    DB::raw('SUM(discount_total) as total_discount')
                )
                ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'), DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->orderBy('year', 'desc')
                ->orderBy('month_number', 'desc')
                ->get();
                break;
                
            default: // daily
                $timeAnalysis = $query->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('AVG(total) as average_sale'),
                    DB::raw('SUM(tax_total) as total_tax'),
                    DB::raw('SUM(discount_total) as total_discount')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'desc')
                ->get();
        }
        
        // Calculate growth metrics
        $growthMetrics = $this->calculateGrowthMetrics($timeAnalysis, $groupBy);
        
        // Peak hours/days analysis
        $peakAnalysis = $this->analyzePeakTimes($tenantId, $startDate, $endDate, [
            'location_id' => $locationId,
            'department_id' => $departmentId,
            'order_type' => $orderType
        ]);
        
        // Get filter options
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.orders.time-analysis', compact(
            'timeAnalysis',
            'growthMetrics',
            'peakAnalysis',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'groupBy',
            'locationId',
            'departmentId',
            'orderType'
        ));
    }
    
    // Returns and Refunds Report
    public function returnsRefunds(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Validate dates
        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
        
        // Get return orders (type = 'return' or status = 'refunded')
        $returnOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where(function($query) {
                $query->where('type', 'return')
                      ->orWhere('status', 'refunded');
            })
            ->with(['customer', 'orderCreater'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get refund payments
        $refundPayments = DB::table('order_payments')
            ->join('orders', 'order_payments.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('order_payments.processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('order_payments.status', 'refunded')
            ->select(
                'order_payments.*',
                'orders.order_number',
                'orders.total as order_total'
            )
            ->orderBy('order_payments.processed_at', 'desc')
            ->get();
        
        // Return reasons analysis (if you have a field for return reason)
        $returnReasons = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('type', 'return')
            ->select(
                DB::raw('SUBSTRING_INDEX(notes, ":", 1) as reason'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total_amount')
            )
            ->groupBy(DB::raw('SUBSTRING_INDEX(notes, ":", 1)'))
            ->orderBy('count', 'desc')
            ->get();
        
        // Return rate calculation
        $totalOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('type', 'sale')
            ->count();
        
        $returnRate = $totalOrders > 0 ? ($returnOrders->count() / $totalOrders) * 100 : 0;
        
        // Top products returned
        $topReturnedProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('orders.type', 'return')
            ->select(
                'product_variants.sku',
                'product_variants.name',
                DB::raw('SUM(order_items.quantity) as return_quantity'),
                DB::raw('SUM(order_items.total_price) as return_value'),
                DB::raw('COUNT(DISTINCT orders.id) as return_count')
            )
            ->groupBy('product_variants.id', 'product_variants.sku', 'product_variants.name')
            ->orderBy('return_quantity', 'desc')
            ->get();
        
        return view('reports.orders.returns-refunds', compact(
            'returnOrders',
            'refundPayments',
            'returnReasons',
            'returnRate',
            'topReturnedProducts',
            'startDate',
            'endDate'
        ));
    }
    
    public function discountAnalysis(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Validate dates
        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
        
        // Get orders with discounts
        $discountedOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('discount_total', '>', 0)
            ->with(['customer', 'orderCreater'])
            ->orderBy('discount_total', 'desc')
            ->get();
        
        // Discount summary
        $discountSummary = [
            'total_discounted_orders' => $discountedOrders->count(),
            'total_discount_amount' => $discountedOrders->sum('discount_total'),
            'average_discount_per_order' => $discountedOrders->avg('discount_total') ?? 0,
            'max_discount' => $discountedOrders->max('discount_total') ?? 0,
            'min_discount' => $discountedOrders->min('discount_total') ?? 0,
            'discount_rate' => $discountedOrders->sum('total') > 0 ? 
                ($discountedOrders->sum('discount_total') / $discountedOrders->sum('total')) * 100 : 0,
        ];
        
        // Discount by employee - FIXED: added table prefix to avoid ambiguity
        $discountByEmployee = Order::where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('orders.discount_total', '>', 0)
            ->join('users', 'orders.created_by', '=', 'users.id')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.discount_total) as total_discount_given'),
                DB::raw('AVG(orders.discount_total) as average_discount'),
                DB::raw('MAX(orders.discount_total) as max_discount_given')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderBy('total_discount_given', 'desc')
            ->get();
        
        // Discount patterns
        $discountPatterns = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('discount_total', '>', 0)
            ->select(
                DB::raw('HOUR(created_at) as hour_of_day'),
                DB::raw('DAYOFWEEK(created_at) as day_of_week'),
                DB::raw('COUNT(*) as discount_count'),
                DB::raw('AVG(discount_total) as average_discount_amount'),
                DB::raw('SUM(discount_total) as total_discount_amount')
            )
            ->groupBy(DB::raw('HOUR(created_at)'), DB::raw('DAYOFWEEK(created_at)'))
            ->orderBy('total_discount_amount', 'desc')
            ->get();
        
        // Discount effectiveness (orders with vs without discount)
        $ordersWithDiscount = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('discount_total', '>', 0)
            ->select(
                DB::raw('COUNT(*) as order_count'),
                DB::raw('AVG(total) as average_order_value'),
                DB::raw('AVG(discount_total) as average_discount')
            )
            ->first();
            
        $ordersWithoutDiscount = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('discount_total', 0)
            ->select(
                DB::raw('COUNT(*) as order_count'),
                DB::raw('AVG(total) as average_order_value')
            )
            ->first();
        
        return view('reports.orders.discount-analysis', compact(
            'discountedOrders',
            'discountSummary',
            'discountByEmployee',
            'discountPatterns',
            'ordersWithDiscount',
            'ordersWithoutDiscount',
            'startDate',
            'endDate'
        ));
    }
    
    // Sales Forecast Report
    public function salesForecast(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Get historical data (last 90 days by default)
        $startDate = $request->get('start_date', Carbon::now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $forecastDays = $request->get('forecast_days', 30);
        
        // Validate dates
        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Exception $e) {
            $startDate = Carbon::now()->subDays(90)->format('Y-m-d');
            $endDate = Carbon::now()->format('Y-m-d');
        }
        
        // Get historical daily sales
        $historicalData = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('status', ['completed', 'processing'])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as daily_sales'),
                DB::raw('AVG(total) as average_order_value')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
        
        // Calculate trends
        $trends = $this->calculateSalesTrends($historicalData);
        
        // Generate forecast
        $forecast = $this->generateForecast($trends, $forecastDays);
        
        // Seasonality analysis (by day of week)
        $seasonality = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('status', ['completed', 'processing'])
            ->select(
                DB::raw('DAYOFWEEK(created_at) as day_of_week'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('AVG(total) as average_sales'),
                DB::raw('SUM(total) as total_sales')
            )
            ->groupBy(DB::raw('DAYOFWEEK(created_at)'))
            ->orderBy('day_of_week')
            ->get();
        
        // Growth rate
        $growthRate = $this->calculateGrowthRate($historicalData);
        
        return view('reports.orders.sales-forecast', compact(
            'historicalData',
            'trends',
            'forecast',
            'seasonality',
            'growthRate',
            'startDate',
            'endDate',
            'forecastDays'
        ));
    }
    
    // Inventory Sales Report (Sold vs Unsold)
    public function inventorySales(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Validate dates
        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
            
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
        
        // Get sold products
        $soldProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('orders.status', ['completed', 'processing'])
            ->select(
                'product_variants.id',
                'product_variants.sku',
                'product_variants.name',
                'product_variants.price',
                'product_variants.overal_quantity_at_hand as current_stock',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.total_price) as revenue_generated'),
                DB::raw('AVG(order_items.unit_price) as average_selling_price'),
                DB::raw('COUNT(DISTINCT orders.id) as times_ordered'),
                DB::raw('MAX(orders.created_at) as last_sold_date')
            )
            ->groupBy(
                'product_variants.id',
                'product_variants.sku',
                'product_variants.name',
                'product_variants.price',
                'product_variants.overal_quantity_at_hand'
            )
            ->orderBy('quantity_sold', 'desc')
            ->get();
        
        // Get unsold products (products with no sales in period)
        $allProducts = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select(
                'id',
                'sku',
                'name',
                'price',
                'overal_quantity_at_hand as current_stock'
            )
            ->get();
            
        $soldProductIds = $soldProducts->pluck('id')->toArray();
        
        $unsoldProducts = $allProducts->filter(function($product) use ($soldProductIds) {
            return !in_array($product->id, $soldProductIds);
        })->values();
        
        // Calculate inventory metrics
        $totalInventoryValue = $allProducts->sum(function($product) {
            return $product->price * $product->current_stock;
        });
        
        $soldInventoryValue = $soldProducts->sum('revenue_generated');
        
        // Turnover rate
        $turnoverRate = $totalInventoryValue > 0 ? ($soldInventoryValue / $totalInventoryValue) * 100 : 0;
        
        // Stock aging analysis
        $stockAging = $this->calculateStockAging($soldProducts, $unsoldProducts);
        
        // Fast vs slow movers
        $productMovement = $soldProducts->map(function($product) use ($startDate, $endDate) {
            $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $product->daily_sales_rate = $product->quantity_sold / max($daysInPeriod, 1);
            
            if ($product->daily_sales_rate >= 1) {
                $product->movement_category = 'Fast Mover';
            } elseif ($product->daily_sales_rate >= 0.1) {
                $product->movement_category = 'Medium Mover';
            } else {
                $product->movement_category = 'Slow Mover';
            }
            
            return $product;
        });
        
        // Dead stock (no sales and high inventory)
        $deadStock = $unsoldProducts->filter(function($product) {
            return $product->current_stock > 10; // More than 10 units with no sales
        })->values();
        
        return view('reports.orders.inventory-sales', compact(
            'soldProducts',
            'unsoldProducts',
            'totalInventoryValue',
            'soldInventoryValue',
            'turnoverRate',
            'stockAging',
            'productMovement',
            'deadStock',
            'startDate',
            'endDate'
        ));
    }
    
    // Helper Methods
    
    private function calculateGrowthMetrics($timeAnalysis, $groupBy)
    {
        if ($timeAnalysis->count() < 2) {
            return [];
        }
        
        $sorted = $timeAnalysis->sortBy(function($item) use ($groupBy) {
            return $item->date ?? $item->month_period ?? $item->week_number ?? $item->time_period;
        })->values();
        
        $first = $sorted->first();
        $last = $sorted->last();
        
        return [
            'sales_growth' => $last->total_sales > 0 ? 
                (($last->total_sales - $first->total_sales) / $first->total_sales) * 100 : 0,
            'order_growth' => $last->order_count > 0 ? 
                (($last->order_count - $first->order_count) / $first->order_count) * 100 : 0,
            'average_order_growth' => $last->average_sale > 0 ? 
                (($last->average_sale - $first->average_sale) / $first->average_sale) * 100 : 0,
        ];
    }
    
    private function analyzePeakTimes($tenantId, $startDate, $endDate)
    {
        // Peak hours
        $peakHours = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('AVG(total) as average_order')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('order_count', 'desc')
            ->take(5)
            ->get();
        
        // Peak days
        $peakDays = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select(
                DB::raw('DAYOFWEEK(created_at) as day_of_week'),
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_sales')
            )
            ->groupBy(DB::raw('DATE(created_at)'), DB::raw('DAYOFWEEK(created_at)'))
            ->orderBy('total_sales', 'desc')
            ->take(10)
            ->get();
        
        return [
            'peak_hours' => $peakHours,
            'peak_days' => $peakDays,
        ];
    }
    
    private function calculateSalesTrends($historicalData)
    {
        if ($historicalData->count() < 2) {
            return [
                'daily_growth' => 0, 
                'weekly_growth' => 0, 
                'trend' => 'stable',
                'current_average' => $historicalData->count() > 0 ? $historicalData->avg('daily_sales') : 0 // Add this
            ];
        }
        
        // Calculate simple moving average
        $period = min(7, $historicalData->count());
        $recentSales = $historicalData->take(-$period);
        $previousSales = $historicalData->slice(0, $period);
        
        $recentAverage = $recentSales->avg('daily_sales') ?? 0;
        $previousAverage = $previousSales->avg('daily_sales') ?? 0;
        
        $growth = $previousAverage > 0 ? (($recentAverage - $previousAverage) / $previousAverage) * 100 : 0;
        
        $trend = 'stable';
        if ($growth > 10) $trend = 'upward';
        if ($growth < -10) $trend = 'downward';
        
        return [
            'daily_growth' => $growth,
            'weekly_growth' => $growth * 7,
            'trend' => $trend,
            'current_average' => $recentAverage,
        ];
    }

    private function generateForecast($trends, $days)
    {
        $forecast = [];
        $baseAmount = $trends['current_average'] ?? 0; // Add null coalescing operator
        $dailyGrowth = ($trends['daily_growth'] ?? 0) / 100;
        
        $date = Carbon::now();
        for ($i = 1; $i <= $days; $i++) {
            $forecastDate = $date->copy()->addDays($i);
            $forecastAmount = $baseAmount > 0 ? $baseAmount * (1 + ($dailyGrowth * $i)) : 0;
            
            // Adjust for day of week seasonality
            $dayOfWeek = $forecastDate->dayOfWeek;
            $dayFactor = 1.0;
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                $dayFactor = 1.2; // Weekend boost
            } elseif ($dayOfWeek == 1) {
                $dayFactor = 0.8; // Monday slump
            }
            
            $forecastAmount = round($forecastAmount * $dayFactor, 2);
            
            // Ensure forecast orders is valid (avoid division by zero)
            $averageOrderValue = $trends['current_average'] > 0 ? $trends['current_average'] : 1;
            $forecastOrders = round(($forecastAmount / $averageOrderValue) * $dayFactor, 0);
            
            $forecast[$forecastDate->format('Y-m-d')] = [
                'date' => $forecastDate->format('Y-m-d'),
                'day_of_week' => $forecastDate->dayName,
                'forecast_sales' => $forecastAmount,
                'forecast_orders' => $forecastOrders,
                'average_order_value' => $baseAmount > 0 ? $baseAmount : 0,
                'confidence' => $i <= 7 ? 'high' : ($i <= 14 ? 'medium' : 'low'),
                'confidence_low' => $forecastAmount * 0.8,
                'confidence_high' => $forecastAmount * 1.2,
                'trend' => $dailyGrowth * 100, // Convert back to percentage
                'seasonality_factor' => $dayFactor
            ];
        }
        
        return $forecast;
    }
    
    private function calculateGrowthRate($historicalData)
    {
        if ($historicalData->count() < 2) {
            return 0;
        }
        
        $first = $historicalData->first();
        $last = $historicalData->last();
        
        $daysBetween = Carbon::parse($first->date)->diffInDays(Carbon::parse($last->date));
        
        if ($daysBetween == 0 || $first->daily_sales == 0) {
            return 0;
        }
        
        // Compound Annual Growth Rate formula
        $cagr = pow(($last->daily_sales / $first->daily_sales), (365 / $daysBetween)) - 1;
        
        return $cagr * 100; // Return as percentage
    }
    
    private function calculateStockAging($soldProducts, $unsoldProducts)
    {
        $agingCategories = [
            '0-30 days' => ['sold' => 0, 'unsold' => 0],
            '31-60 days' => ['sold' => 0, 'unsold' => 0],
            '61-90 days' => ['sold' => 0, 'unsold' => 0],
            '91+ days' => ['sold' => 0, 'unsold' => 0],
        ];
        
        foreach ($soldProducts as $product) {
            if ($product->last_sold_date) {
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
        
        return $agingCategories;
    }
}