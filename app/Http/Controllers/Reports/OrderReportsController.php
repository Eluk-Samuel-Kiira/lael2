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
use App\Models\{ User, OrderItem, OrderPayment };
use Illuminate\Support\Facades\{ DB };
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

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

    // Sales by Customer Report - FIXED VERSION
    public function byCustomer(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
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
        
        // Get all orders with filters using Eloquent
        $ordersQuery = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('customer_id')
            ->with(['customer', 'location', 'department']);
        
        // Apply location filter
        if ($locationId) {
            $ordersQuery->where('location_id', $locationId);
        }
        
        // Apply department filter
        if ($departmentId) {
            $ordersQuery->where('department_id', $departmentId);
        }
        
        $allOrders = $ordersQuery->get();
        
        // Group by customer and calculate statistics using Collections
        $customerSalesRaw = $allOrders->groupBy('customer_id')
            ->map(function($customerOrders, $customerId) {
                $customer = $customerOrders->first()->customer;
                $totalSpent = $customerOrders->sum('total');
                $totalTax = $customerOrders->sum('tax_total');
                $totalDiscount = $customerOrders->sum('discount_total');
                $orderCount = $customerOrders->count();
                $lastOrder = $customerOrders->sortByDesc('created_at')->first();
                
                // Calculate average order value safely
                $averageOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0;
                
                // Get max and min order values
                $maxOrderValue = $customerOrders->max('total') ?? 0;
                $minOrderValue = $customerOrders->min('total') ?? 0;
                
                return (object)[
                    'id' => $customerId,
                    'customer_id' => $customerId,
                    'first_name' => $customer->first_name ?? '',
                    'last_name' => $customer->last_name ?? '',
                    'full_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                    'email' => $customer->email ?? '',
                    'phone' => $customer->phone ?? '',
                    'city' => $customer->city ?? '',
                    'order_count' => $orderCount,
                    'total_spent' => $totalSpent,
                    'total_tax' => $totalTax,
                    'total_discount' => $totalDiscount,
                    'average_order_value' => $averageOrderValue,
                    'max_order_value' => $maxOrderValue,
                    'min_order_value' => $minOrderValue,
                    'last_order_date' => $lastOrder->created_at ?? null,
                    'last_order_amount' => $lastOrder->total ?? 0,
                ];
            })
            ->sortByDesc('total_spent')
            ->values();
        
        // Apply amount filters (using Collection filtering)
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
        
        // Create a new collection with filtered values
        $customerSales = $customerSales->values();
        
        // Calculate customer loyalty segments
        $customerSegments = (object)[
            'new' => $customerSales->filter(function($customer) {
                return $customer->order_count == 1;
            })->count(),
            'returning' => $customerSales->filter(function($customer) {
                return $customer->order_count > 1 && $customer->order_count <= 5;
            })->count(),
            'regular' => $customerSales->filter(function($customer) {
                return $customer->order_count > 5 && $customer->order_count <= 20;
            })->count(),
            'vip' => $customerSales->filter(function($customer) {
                return $customer->order_count > 20;
            })->count(),
        ];
        
        // Top customers for chart
        $topCustomers = $customerSales->take(10);
        
        // Calculate total spent for percentages
        $totalSpentAll = $customerSales->sum('total_spent');
        
        // Summary statistics
        $summary = (object)[
            'total_customers' => $customerSales->count(),
            'total_orders' => $customerSales->sum('order_count'),
            'total_revenue' => $customerSales->sum('total_spent'),
            'average_per_customer' => $customerSales->count() > 0 ? $customerSales->avg('total_spent') : 0,
            'average_orders_per_customer' => $customerSales->count() > 0 ? $customerSales->avg('order_count') : 0,
        ];
        
        // Add percentage to each customer
        $customerSales = $customerSales->map(function($customer) use ($totalSpentAll) {
            $customer->percentage = $totalSpentAll > 0 ? ($customer->total_spent / $totalSpentAll) * 100 : 0;
            return $customer;
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
        
        return view('reports.orders.by-customer', compact(
            'customerSales',
            'customerSegments',
            'topCustomers',
            'summary',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'displayStartDate',
            'displayEndDate',
            'locationId',
            'departmentId',
            'minSpent',
            'maxSpent',
            'minOrders',
            'maxOrders',
            'totalSpentAll'
        ));
    }
    
    // Sales by Product/Variant Report
    public function byProduct(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
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
        $perPage = $request->get('per_page', 15);
        
        // Build product sales query using Eloquent
        $query = OrderItem::with(['order', 'variant'])
            ->whereHas('order', function($q) use ($tenantId, $startDate, $endDate, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId)
                    ->whereIn('status', ['completed', 'processing'])
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
        
        // Group by variant and calculate statistics
        $productSalesRaw = $orderItems->groupBy('variant_id')
            ->map(function($items, $variantId) {
                $firstItem = $items->first();
                $variant = $firstItem->variant;
                $totalQuantity = $items->sum('quantity');
                $totalRevenue = $items->sum('total_price');
                $totalTax = $items->sum('tax_amount');
                $totalDiscount = $items->sum('discount');
                $orderCount = $items->pluck('order_id')->unique()->count();
                $avgSellingPrice = $totalQuantity > 0 ? $totalRevenue / $totalQuantity : 0;
                $lastSold = $items->sortByDesc(function($item) {
                    return $item->order->created_at ?? null;
                })->first();
                
                return (object)[
                    'variant_id' => $variantId,
                    'sku' => $variant->sku ?? 'N/A',
                    'variant_name' => $variant->name ?? 'Unknown',
                    'current_price' => $variant->price ?? 0,
                    'current_stock' => $variant->overal_quantity_at_hand ?? 0,
                    'total_quantity_sold' => $totalQuantity,
                    'total_revenue' => $totalRevenue,
                    'total_tax' => $totalTax,
                    'total_discount' => $totalDiscount,
                    'average_selling_price' => $avgSellingPrice,
                    'order_count' => $orderCount,
                    'last_sold_date' => $lastSold->order->created_at ?? null,
                ];
            });
        
        // Apply filters
        $productSales = $productSalesRaw->filter(function($product) use ($minQuantity, $maxQuantity, $minRevenue, $maxRevenue) {
            if ($minQuantity && $product->total_quantity_sold < $minQuantity) return false;
            if ($maxQuantity && $product->total_quantity_sold > $maxQuantity) return false;
            if ($minRevenue && $product->total_revenue < $minRevenue) return false;
            if ($maxRevenue && $product->total_revenue > $maxRevenue) return false;
            return true;
        })->sortByDesc('total_revenue')->values();
        
        // Calculate days in period
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        
        // Calculate sales velocity
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
        
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        return view('reports.orders.by-product', compact(
            'productSales',
            'topProducts',
            'bottomProducts',
            'locations',
            'departments',
            'startDate',
            'endDate',
            'displayStartDate',
            'displayEndDate',
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
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
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
        
        // Get order payments using Eloquent
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
            ->whereBetween('processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed');
        
        // Apply payment type filter
        if ($paymentType && $paymentType !== 'all') {
            $query->whereHas('paymentMethod', function($q) use ($paymentType) {
                $q->where('type', $paymentType);
            });
        }
        
        $allPayments = $query->get();
        
        // Group by payment method for breakdown
        $paymentMethodAnalysis = $allPayments->groupBy('payment_method_id')
            ->map(function($payments, $methodId) {
                $paymentMethod = $payments->first()->paymentMethod;
                $totalAmount = $payments->sum('amount');
                $transactionCount = $payments->count();
                $avgTransaction = $transactionCount > 0 ? $totalAmount / $transactionCount : 0;
                $lastTransaction = $payments->sortByDesc('processed_at')->first();
                
                return (object)[
                    'id' => $methodId,
                    'method_name' => $paymentMethod->name ?? 'Unknown',
                    'method_type' => $paymentMethod->type ?? 'unknown',
                    'transaction_count' => $transactionCount,
                    'total_amount' => $totalAmount,
                    'average_transaction' => $avgTransaction,
                    'largest_transaction' => $payments->max('amount'),
                    'smallest_transaction' => $payments->min('amount'),
                    'last_transaction_date' => $lastTransaction->processed_at ?? null,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();
        
        // Payment method trends over time
        $paymentTrendsRaw = $allPayments->groupBy(function($payment) {
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
        
        // Failed transactions
        $failedPayments = OrderPayment::with(['order', 'paymentMethod'])
            ->whereHas('order', function($q) use ($tenantId, $locationId, $departmentId) {
                $q->where('tenant_id', $tenantId);
                
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
                
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
            })
            ->whereBetween('processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'failed')
            ->get();
        
        $failedTransactions = $failedPayments->groupBy('payment_method_id')
            ->map(function($failures, $methodId) {
                $paymentMethod = $failures->first()->paymentMethod;
                
                return (object)[
                    'name' => $paymentMethod->name ?? 'Unknown',
                    'failed_count' => $failures->count(),
                    'failed_amount' => $failures->sum('amount'),
                ];
            })
            ->values();
        
        // Get filter options
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // Get unique payment types for filter dropdown
        $paymentTypes = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->distinct()
            ->pluck('type');
        
        // Summary statistics
        $summary = (object)[
            'total_transactions' => $paymentMethodAnalysis->sum('transaction_count'),
            'total_amount' => $paymentMethodAnalysis->sum('total_amount'),
            'avg_transaction' => $paymentMethodAnalysis->avg('average_transaction'),
            'most_used_method' => $paymentMethodAnalysis->first()->method_name ?? 'N/A',
            'most_used_amount' => $paymentMethodAnalysis->first()->total_amount ?? 0,
        ];
        
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        // Data for charts
        $chartData = [
            'labels' => $paymentMethodAnalysis->pluck('method_name'),
            'amounts' => $paymentMethodAnalysis->pluck('total_amount'),
            'counts' => $paymentMethodAnalysis->pluck('transaction_count'),
        ];
        
        return view('reports.orders.by-payment-method', compact(
            'paymentMethodAnalysis',
            'paymentTrends',
            'failedTransactions',
            'locations',
            'departments',
            'paymentTypes',
            'summary',
            'chartData',
            'startDate',
            'endDate',
            'displayStartDate',
            'displayEndDate',
            'locationId',
            'departmentId',
            'paymentType'
        ));
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

            
    // Time-based Sales Report - Pure Eloquent Version
    public function timeAnalysis(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $groupBy = $request->get('group_by', 'daily');
        
        // Validate and format dates
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Get filter parameters
        $locationId = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $orderType = $request->get('order_type');
        
        // Get all orders using Eloquent scopes
        $orders = Order::where('tenant_id', $tenantId)
            ->dateBetween($startDate, $endDate)
            ->completed()
            ->byLocation($locationId)
            ->byDepartment($departmentId)
            ->byOrderType($orderType)
            ->get();
        
        // Group orders based on the group_by parameter using collection methods
        $timeAnalysis = collect();
        
        switch ($groupBy) {
            case 'hourly':
                $grouped = $orders->groupBy(function($order) {
                    return $order->created_hour;
                })->sortKeys();
                
                foreach ($grouped as $hour => $hourOrders) {
                    $timeAnalysis->push((object)[
                        'time_period' => $hour,
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
                    return $order->created_date;
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
        
        // Sort descending by date/period
        if ($groupBy == 'daily') {
            $timeAnalysis = $timeAnalysis->sortByDesc('date')->values();
        } elseif ($groupBy == 'weekly') {
            $timeAnalysis = $timeAnalysis->sortByDesc('year')->sortByDesc('week_number')->values();
        } elseif ($groupBy == 'monthly') {
            $timeAnalysis = $timeAnalysis->sortByDesc('year')->sortByDesc('month_number')->values();
        } else {
            $timeAnalysis = $timeAnalysis->sortByDesc('time_period')->values();
        }
        
        // Calculate growth metrics using collection methods
        $growthMetrics = $this->calculateGrowthMetricsEloquent($timeAnalysis, $groupBy);
        
        // Peak hours/days analysis using Eloquent
        $peakAnalysis = $this->analyzePeakTimesEloquent($orders, $groupBy, $startDate, $endDate);
        
        // Get filter options
        $locations = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
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

    /**
     * Calculate growth metrics using collection methods
     */
    private function calculateGrowthMetricsEloquent($timeAnalysis, $groupBy)
    {
        if ($timeAnalysis->count() < 2) {
            return [
                'trend' => 'stable',
                'daily_growth' => 0,
                'weekly_growth' => 0,
                'current_average' => $timeAnalysis->first() ? $timeAnalysis->first()->average_sale : 0
            ];
        }
        
        // Get first and last periods
        $firstPeriod = $timeAnalysis->last(); // Oldest
        $lastPeriod = $timeAnalysis->first(); // Newest
        
        // Calculate overall growth
        $growth = 0;
        if ($firstPeriod->total_sales > 0) {
            $growth = (($lastPeriod->total_sales - $firstPeriod->total_sales) / $firstPeriod->total_sales) * 100;
        }
        
        // Determine trend
        $trend = 'stable';
        if ($growth > 10) {
            $trend = 'upward';
        } elseif ($growth < -10) {
            $trend = 'downward';
        }
        
        // Calculate daily growth (average across all periods)
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
        
        // Calculate weekly growth (7 periods if daily)
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
     * Analyze peak times using collection methods
     */
    private function analyzePeakTimesEloquent($orders, $groupBy, $startDate, $endDate)
    {
        $peakHours = collect();
        $peakDays = collect();
        
        if ($groupBy == 'hourly') {
            // Peak hours analysis
            $peakHours = $orders->groupBy(function($order) {
                return $order->created_hour;
            })->map(function($hourOrders, $hour) {
                return (object)[
                    'hour' => $hour,
                    'order_count' => $hourOrders->count(),
                    'hourly_total' => $hourOrders->sum('total'),
                    'hourly_average' => $hourOrders->avg('total'),
                ];
            })->sortByDesc('hourly_total')->take(5)->values();
            
            // Peak days analysis (still useful for hourly view)
            $peakDays = $orders->groupBy(function($order) {
                return $order->created_date;
            })->map(function($dayOrders, $date) {
                return (object)[
                    'date' => $date,
                    'order_count' => $dayOrders->count(),
                    'total_sales' => $dayOrders->sum('total'),
                    'average_sale' => $dayOrders->avg('total'),
                ];
            })->sortByDesc('total_sales')->take(5)->values();
            
        } elseif ($groupBy == 'weekly') {
            // Peak weeks analysis
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
            // Peak months analysis
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
            // Daily peak analysis
            $peakDays = $orders->groupBy(function($order) {
                return $order->created_date;
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


    
    // Returns and Refunds Report - Pure Eloquent Version
    public function returnsRefunds(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Validate dates
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Get return orders using Eloquent with eager loading
        $returnOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->returns()
            ->with(['customer', 'createdBy', 'refundPayments', 'items.productVariant'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get refund payments using Eloquent relationship
        $refundPayments = OrderPayment::whereHas('order', function($query) use ($tenantId, $startDate, $endDate) {
                $query->where('tenant_id', $tenantId)
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })
            ->where('status', 'refunded')
            ->with(['order'])
            ->orderBy('processed_at', 'desc')
            ->get()
            ->map(function($payment) {
                return (object)[
                    'id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'order_number' => $payment->order->order_number ?? 'N/A',
                    'order_total' => $payment->order->total ?? 0,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->paymentMethod->name ?? 'Unknown',
                    'processed_at' => $payment->processed_at,
                    'status' => $payment->status,
                    'reference' => $payment->reference,
                ];
            });
        
        // Return reasons analysis using collection methods
        $returnReasonsCollection = $returnOrders->where('type', 'return')
            ->groupBy(function($order) {
                return $order->return_reason;
            })
            ->map(function($orders, $reason) {
                return (object)[
                    'reason' => $reason,
                    'count' => $orders->count(),
                    'total_amount' => $orders->sum('total'),
                    'percentage' => $returnOrders->count() > 0 ? ($orders->count() / $returnOrders->count()) * 100 : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();
        
        // Return rate calculation using Eloquent
        $totalOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sales()
            ->count();
        
        $totalReturnOrders = $returnOrders->where('type', 'return')->count();
        $returnRate = $totalOrders > 0 ? ($totalReturnOrders / $totalOrders) * 100 : 0;
        
        // Calculate refund rate (by value)
        $totalSalesValue = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sales()
            ->sum('total');
        
        $totalRefundValue = $refundPayments->sum('amount');
        $refundRate = $totalSalesValue > 0 ? ($totalRefundValue / $totalSalesValue) * 100 : 0;
        
        // Top products returned using Eloquent with eager loading
        $topReturnedProducts = collect();
        
        foreach ($returnOrders->where('type', 'return') as $returnOrder) {
            foreach ($returnOrder->items as $item) {
                if ($item->productVariant) {
                    $key = $item->productVariant->id;
                    $existing = $topReturnedProducts->get($key);
                    
                    if ($existing) {
                        $existing->return_quantity += $item->quantity;
                        $existing->return_value += $item->total_price;
                        $existing->return_count++;
                    } else {
                        $topReturnedProducts->put($key, (object)[
                            'sku' => $item->productVariant->sku,
                            'name' => $item->productVariant->name,
                            'return_quantity' => $item->quantity,
                            'return_value' => $item->total_price,
                            'return_count' => 1,
                        ]);
                    }
                }
            }
        }
        
        $topReturnedProducts = $topReturnedProducts->sortByDesc('return_quantity')->take(10)->values();
        
        // Additional metrics
        $metrics = (object)[
            'total_return_orders' => $totalReturnOrders,
            'total_refund_amount' => $totalRefundValue,
            'average_return_value' => $totalReturnOrders > 0 ? $totalRefundValue / $totalReturnOrders : 0,
            'return_rate' => $returnRate,
            'refund_rate' => $refundRate,
            'total_sales_value' => $totalSalesValue,
            'total_orders' => $totalOrders,
        ];
        
        // Monthly return trends
        $monthlyReturnTrends = $returnOrders
            ->groupBy(function($order) {
                return $order->created_at->format('Y-m');
            })
            ->map(function($orders, $month) {
                return (object)[
                    'month' => $month,
                    'return_count' => $orders->count(),
                    'return_value' => $orders->sum('total'),
                    'percentage_of_total' => $returnOrders->count() > 0 ? ($orders->count() / $returnOrders->count()) * 100 : 0,
                ];
            })
            ->sortKeys()
            ->values();
        
        // Return by payment method
        $returnsByPaymentMethod = $returnOrders
            ->groupBy(function($order) {
                return $order->payments->first()->paymentMethod->name ?? 'Unknown';
            })
            ->map(function($orders, $method) {
                return (object)[
                    'payment_method' => $method,
                    'return_count' => $orders->count(),
                    'return_value' => $orders->sum('total'),
                ];
            })
            ->sortByDesc('return_value')
            ->values();
        
        return view('reports.orders.returns-refunds', compact(
            'returnOrders',
            'refundPayments',
            'returnReasonsCollection',
            'returnRate',
            'refundRate',
            'topReturnedProducts',
            'metrics',
            'monthlyReturnTrends',
            'returnsByPaymentMethod',
            'startDate',
            'endDate'
        ));
    }


    // Discount Analysis Report - Pure Eloquent Version
    public function discountAnalysis(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Validate dates
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Get orders with discounts using Eloquent
        $discountedOrders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->withDiscounts()
            ->with(['customer', 'orderCreater']) // Changed from 'createdBy' to 'orderCreater'
            ->orderBy('discount_total', 'desc')
            ->get();
        
        // Discount summary using collection methods
        $discountSummary = [
            'total_discounted_orders' => $discountedOrders->count(),
            'total_discount_amount' => $discountedOrders->sum('discount_total'),
            'average_discount_per_order' => $discountedOrders->avg('discount_total') ?? 0,
            'max_discount' => $discountedOrders->max('discount_total') ?? 0,
            'min_discount' => $discountedOrders->min('discount_total') ?? 0,
            'discount_rate' => $discountedOrders->sum('total') > 0 ? 
                ($discountedOrders->sum('discount_total') / $discountedOrders->sum('total')) * 100 : 0,
        ];
        
        // Discount by employee using collection methods - CORRECTED
        $discountByEmployee = $discountedOrders
            ->groupBy('created_by')
            ->map(function($orders, $userId) {
                $firstOrder = $orders->first();
                $user = $firstOrder->orderCreater; // Changed from createdBy to orderCreater
                
                return (object)[
                    'id' => $userId,
                    'first_name' => $user->first_name ?? 'Unknown',
                    'last_name' => $user->last_name ?? '',
                    'email' => $user->email ?? '',
                    'order_count' => $orders->count(),
                    'total_discount_given' => $orders->sum('discount_total'),
                    'average_discount' => $orders->avg('discount_total'),
                    'max_discount_given' => $orders->max('discount_total'),
                ];
            })
            ->filter(function($employee) {
                // Remove employees with no name (optional)
                return $employee->first_name !== 'Unknown';
            })
            ->sortByDesc('total_discount_given')
            ->values();
        
        // Discount patterns by time using collection methods
        $discountPatterns = $discountedOrders
            ->groupBy(function($order) {
                return $order->day_of_week . '-' . $order->created_hour;
            })
            ->map(function($orders, $key) {
                list($dayOfWeek, $hour) = explode('-', $key);
                return (object)[
                    'hour_of_day' => (int)$hour,
                    'day_of_week' => (int)$dayOfWeek,
                    'discount_count' => $orders->count(),
                    'average_discount_amount' => $orders->avg('discount_total'),
                    'total_discount_amount' => $orders->sum('discount_total'),
                ];
            })
            ->sortByDesc('total_discount_amount')
            ->values();
        
        // Orders with discount vs orders without discount
        $ordersWithDiscountData = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->withDiscounts()
            ->get();
        
        $ordersWithoutDiscountData = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->withoutDiscounts()
            ->get();
        
        $ordersWithDiscount = (object)[
            'order_count' => $ordersWithDiscountData->count(),
            'average_order_value' => $ordersWithDiscountData->avg('total') ?? 0,
            'average_discount' => $ordersWithDiscountData->avg('discount_total') ?? 0,
        ];
        
        $ordersWithoutDiscount = (object)[
            'order_count' => $ordersWithoutDiscountData->count(),
            'average_order_value' => $ordersWithoutDiscountData->avg('total') ?? 0,
        ];
        
        // Additional metrics for chart
        $discountByDay = $discountedOrders
            ->groupBy('day_name')
            ->map(function($orders, $day) {
                return (object)[
                    'day' => $day,
                    'discount_count' => $orders->count(),
                    'total_amount' => $orders->sum('discount_total'),
                    'average_amount' => $orders->avg('discount_total'),
                ];
            })
            ->sortBy(function($item, $key) {
                $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                return array_search($key, $order);
            })
            ->values();
        
        // Discount range distribution
        $discountRanges = [
            '0-10%' => 0,
            '10-20%' => 0,
            '20-30%' => 0,
            '30-40%' => 0,
            '40-50%' => 0,
            '50%+' => 0,
        ];
        
        foreach ($discountedOrders as $order) {
            $percentage = $order->discount_percentage;
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
        
        return view('reports.orders.discount-analysis', compact(
            'discountedOrders',
            'discountSummary',
            'discountByEmployee',
            'discountPatterns',
            'ordersWithDiscount',
            'ordersWithoutDiscount',
            'discountByDay',
            'discountRanges',
            'startDate',
            'endDate'
        ));
    }

    // Sales Forecast Report - Simplified with Pagination
    public function salesForecast(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get historical data (last 90 days by default)
        $startDate = $request->get('start_date', Carbon::now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $forecastDays = $request->get('forecast_days', 30);
        
        // Pagination parameters
        $historicalPerPage = $request->get('historical_per_page', 15);
        $forecastPerPage = $request->get('forecast_per_page', 15);
        
        // Validate dates
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Get all completed orders in the date range
        $orders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('status', ['completed', 'processing'])
            ->get();
        
        // Get historical daily sales using collection methods
        $historicalDataCollection = $orders
            ->groupBy('date_only')
            ->map(function($dailyOrders, $date) {
                return (object)[
                    'date' => $date,
                    'order_count' => $dailyOrders->count(),
                    'daily_sales' => $dailyOrders->sum('total'),
                    'average_order_value' => $dailyOrders->avg('total'),
                ];
            })
            ->sortKeysDesc() // Sort by date descending (newest first)
            ->values();
        
        // Apply pagination to historical data using simple pagination
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('historical_page');
        $historicalData = new \Illuminate\Pagination\LengthAwarePaginator(
            $historicalDataCollection->slice(($currentPage - 1) * $historicalPerPage, $historicalPerPage)->values(),
            $historicalDataCollection->count(),
            $historicalPerPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'pageName' => 'historical_page']
        );
        
        // Calculate trends using collection methods (using full collection, not paginated)
        $trends = $this->calculateSalesTrendsEloquent($historicalDataCollection);
        
        // Generate forecast (using full collection)
        $forecast = $this->generateForecastEloquent($historicalDataCollection, $trends, $forecastDays);
        
        // Convert forecast to collection for pagination
        $forecastCollection = collect($forecast);
        
        // Apply pagination to forecast data using simple pagination
        $currentForecastPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('forecast_page');
        $forecastData = new \Illuminate\Pagination\LengthAwarePaginator(
            $forecastCollection->slice(($currentForecastPage - 1) * $forecastPerPage, $forecastPerPage)->values(),
            $forecastCollection->count(),
            $forecastPerPage,
            $currentForecastPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'pageName' => 'forecast_page']
        );
        
        // Seasonality analysis (by day of week) using collection methods
        $seasonality = $orders
            ->groupBy('day_of_week')
            ->map(function($dayOrders, $dayOfWeek) {
                return (object)[
                    'day_of_week' => (int)$dayOfWeek,
                    'order_count' => $dayOrders->count(),
                    'average_sales' => $dayOrders->avg('total'),
                    'total_sales' => $dayOrders->sum('total'),
                    'day_name' => $dayOrders->first()->day_name ?? $this->getDayNameFromNumber($dayOfWeek),
                ];
            })
            ->sortKeys()
            ->values();
        
        // Calculate growth rate
        $growthRate = $this->calculateGrowthRateEloquent($historicalDataCollection);
        
        // Calculate historical average for deviation
        $historicalAvg = $historicalDataCollection->avg('daily_sales');
        
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
            'startDate',
            'endDate',
            'forecastDays',
            'historicalPerPage',
            'forecastPerPage'
        ));
    }


    /**
     * Calculate sales trends using collection methods
     */
    private function calculateSalesTrendsEloquent($historicalData)
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
        
        // Calculate daily growth rates
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
        
        // Calculate weekly growth
        $weeklyGrowth = 0;
        if ($historicalData->count() >= 14) {
            $lastWeek = $historicalData->slice(-7)->sum('daily_sales');
            $previousWeek = $historicalData->slice(-14, 7)->sum('daily_sales');
            
            if ($previousWeek > 0) {
                $weeklyGrowth = (($lastWeek - $previousWeek) / $previousWeek) * 100;
            }
        }
        
        // Calculate monthly growth
        $monthlyGrowth = 0;
        if ($historicalData->count() >= 60) {
            $lastMonth = $historicalData->slice(-30)->sum('daily_sales');
            $previousMonth = $historicalData->slice(-60, 30)->sum('daily_sales');
            
            if ($previousMonth > 0) {
                $monthlyGrowth = (($lastMonth - $previousMonth) / $previousMonth) * 100;
            }
        }
        
        // Determine trend direction
        $trendDirection = 'stable';
        if ($dailyGrowth > 2) {
            $trendDirection = 'upward';
        } elseif ($dailyGrowth < -2) {
            $trendDirection = 'downward';
        }
        
        // Calculate volatility (standard deviation of daily sales)
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
     * Generate forecast using collection methods
     */
    private function generateForecastEloquent($historicalData, $trends, $forecastDays)
    {
        if ($historicalData->count() < 2) {
            return [];
        }
        
        // Calculate baseline average
        $averageDailySales = $historicalData->avg('daily_sales');
        $averageDailyOrders = $historicalData->avg('order_count');
        $averageOrderValue = $historicalData->avg('average_order_value');
        
        // Get seasonality factors by day of week
        $seasonalityFactors = [];
        foreach ($historicalData as $day) {
            $date = Carbon::parse($day->date);
            $dayOfWeek = $date->dayOfWeek;
            $seasonalityFactors[$dayOfWeek][] = $day->daily_sales;
        }
        
        // Calculate average seasonality factor for each day
        $avgSeasonality = [];
        foreach ($seasonalityFactors as $dayOfWeek => $values) {
            $dayAverage = array_sum($values) / count($values);
            $avgSeasonality[$dayOfWeek] = $averageDailySales > 0 
                ? $dayAverage / $averageDailySales 
                : 1.0;
        }
        
        // Generate forecast for future days
        $forecast = [];
        $lastDate = $historicalData->isNotEmpty() 
            ? Carbon::parse($historicalData->last()->date) 
            : Carbon::now();
        
        // Calculate confidence intervals based on volatility
        $volatilityFactor = max(0.05, min(0.3, $trends->volatility / 100));
        $confidenceMultiplier = $volatilityFactor;
        
        for ($i = 1; $i <= $forecastDays; $i++) {
            $forecastDate = $lastDate->copy()->addDays($i);
            $dayOfWeek = $forecastDate->dayOfWeek;
            
            // Apply growth trend
            $growthMultiplier = 1 + ($trends->daily_growth / 100);
            
            // Apply seasonality
            $seasonality = $avgSeasonality[$dayOfWeek] ?? 1.0;
            
            // Calculate forecast
            $forecastSales = $averageDailySales * $growthMultiplier * $seasonality;
            $forecastOrders = $averageDailyOrders * $growthMultiplier * $seasonality;
            $forecastAOV = $forecastOrders > 0 ? $forecastSales / $forecastOrders : $averageOrderValue;
            
            // Confidence interval
            $confidenceLow = $forecastSales * (1 - $confidenceMultiplier);
            $confidenceHigh = $forecastSales * (1 + $confidenceMultiplier);
            
            // Determine confidence level
            $confidence = 'medium';
            if ($volatilityFactor < 0.1) {
                $confidence = 'high';
            } elseif ($volatilityFactor > 0.2) {
                $confidence = 'low';
            }
            
            // Calculate trend value
            $trendValue = $trends->daily_growth;
            
            $forecast[$forecastDate->format('Y-m-d')] = [
                'date' => $forecastDate->format('Y-m-d'),
                'day_name' => $forecastDate->format('l'),
                'forecast_sales' => max(0, $forecastSales),
                'forecast_orders' => max(0, $forecastOrders),
                'average_order_value' => $forecastAOV,
                'confidence_low' => max(0, $confidenceLow),
                'confidence_high' => max(0, $confidenceHigh),
                'confidence' => $confidence,
                'trend' => $trendValue,
                'seasonality_factor' => $seasonality,
            ];
        }
        
        return $forecast;
    }

    /**
     * Calculate growth rate using collection methods
     */
    private function calculateGrowthRateEloquent($historicalData)
    {
        if ($historicalData->count() < 2) {
            return 0;
        }
        
        // Calculate average of first half vs second half
        $halfCount = floor($historicalData->count() / 2);
        $firstHalf = $historicalData->slice(0, $halfCount);
        $secondHalf = $historicalData->slice($halfCount);
        
        $firstHalfAvg = $firstHalf->avg('daily_sales');
        $secondHalfAvg = $secondHalf->avg('daily_sales');
        
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



    // Inventory Sales Report (Sold vs Unsold) - Pure Eloquent with Pagination
    public function inventorySales(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('order reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Pagination parameters
        $soldPerPage = $request->get('sold_per_page', 15);
        $unsoldPerPage = $request->get('unsold_per_page', 15);
        $deadStockPerPage = $request->get('dead_stock_per_page', 10);
        
        // Validate dates
        [$startDate, $endDate] = $this->validateAndFormatDates($startDate, $endDate);
        
        // Calculate days in period (needed for daily sales rate)
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        
        // Get all order items in the date range
        $orderItems = OrderItem::whereHas('order', function($query) use ($tenantId, $startDate, $endDate) {
                $query->where('tenant_id', $tenantId)
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->whereIn('status', ['completed', 'processing']);
            })
            ->with(['productVariant', 'order'])
            ->get();
        
        // Get sold products using collection methods
        $soldProductsCollection = $orderItems
            ->groupBy('variant_id')
            ->map(function($items, $variantId) use ($daysInPeriod) {
                $firstItem = $items->first();
                $product = $firstItem->productVariant;
                
                if (!$product) return null;
                
                $dailySalesRate = $items->sum('quantity') / max($daysInPeriod, 1);
                
                // Determine movement category
                if ($dailySalesRate >= 1) {
                    $movementCategory = 'Fast Mover';
                } elseif ($dailySalesRate >= 0.1) {
                    $movementCategory = 'Medium Mover';
                } else {
                    $movementCategory = 'Slow Mover';
                }
                
                return (object)[
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'current_stock' => $product->overal_quantity_at_hand ?? 0,
                    'quantity_sold' => $items->sum('quantity'),
                    'revenue_generated' => $items->sum('total_price'),
                    'average_selling_price' => $items->avg('unit_price'),
                    'times_ordered' => $items->unique('order_id')->count(),
                    'last_sold_date' => $items->max('created_at'),
                    'daily_sales_rate' => $dailySalesRate,
                    'movement_category' => $movementCategory,
                ];
            })
            ->filter()
            ->sortByDesc('quantity_sold')
            ->values();
        
        // Get all active products
        $allProducts = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
        
        // Get sold product IDs
        $soldProductIds = $soldProductsCollection->pluck('id')->toArray();
        
        // Get unsold products
        $unsoldProductsCollection = $allProducts
            ->filter(function($product) use ($soldProductIds) {
                return !in_array($product->id, $soldProductIds);
            })
            ->map(function($product) {
                return (object)[
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'current_stock' => $product->overal_quantity_at_hand ?? 0,
                    'stock_value' => ($product->overal_quantity_at_hand ?? 0) * $product->price,
                ];
            })
            ->sortByDesc('current_stock')
            ->values();
        
        // Calculate inventory metrics
        $totalInventoryValue = $allProducts->sum(function($product) {
            return ($product->overal_quantity_at_hand ?? 0) * $product->price;
        });
        
        $soldInventoryValue = $soldProductsCollection->sum('revenue_generated');
        
        // Turnover rate
        $turnoverRate = $totalInventoryValue > 0 ? ($soldInventoryValue / $totalInventoryValue) * 100 : 0;
        
        // Stock aging analysis
        $stockAging = $this->calculateStockAgingEloquent($soldProductsCollection, $unsoldProductsCollection);
        
        // Dead stock (no sales and high inventory)
        $deadStockCollection = $unsoldProductsCollection->filter(function($product) {
            return $product->current_stock > 10;
        })->values();
        
        // Apply pagination
        $soldProducts = $this->paginateCollection($soldProductsCollection, $soldPerPage, 'sold_page');
        $unsoldProducts = $this->paginateCollection($unsoldProductsCollection, $unsoldPerPage, 'unsold_page');
        $deadStock = $this->paginateCollection($deadStockCollection, $deadStockPerPage, 'dead_stock_page');
        
        $productMovement = $soldProductsCollection;
        
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
            'startDate',
            'endDate',
            'soldPerPage',
            'unsoldPerPage',
            'deadStockPerPage',
            'daysInPeriod'
        ));
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
                'path' => request()->url(), // ✅ Use current URL to preserve filters
                'pageName' => $pageName,
                'query' => request()->except($pageName, 'sold_page', 'unsold_page') // ✅ Preserve all filters
            ]
        );
    }
    
    /**
     * Calculate stock aging
     */
    private function calculateStockAgingEloquent($soldProducts, $unsoldProducts)
    {
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
        ];
    }

    

    

}