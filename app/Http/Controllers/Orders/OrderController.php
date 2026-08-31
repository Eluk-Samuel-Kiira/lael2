<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\{ Auth, DB };

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('view order')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }
        
        // Get per_page from request, default to 15
        $perPage = $request->input('per_page', 15);
        
        // Validate per_page is in allowed values
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }
        
        // Build the query with relationships
        $query = Order::with([
            'orderItems',
            'customer',
            'orderCreater',
            'location',
            'department',
            'orderPayments'
        ]);
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('source', 'like', "%{$search}%")
                ->orWhereHas('customer', function($c) use ($search) {
                    $c->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('orderCreater', fn($cr) => $cr->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Paginate with dynamic per_page
        $orders = $query->latest()->paginate($perPage);
        
        // Preserve per_page and search in pagination links
        $orders->appends(['per_page' => $perPage, 'search' => $request->search]);
        
        $bladeToReload = $request->query('bladeFileToReload');
        
        // For AJAX requests - return just the component HTML
        if ($request->ajax() && $bladeToReload === 'ordersIndexTable') {
            return view('orders.order.component', [
                'orders' => $orders,
            ])->render();
        }
        
        // Regular page load
        return view('orders.order-index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getPausedOrders(Request $request)
    {
        \Log::info('[PauseBuy] hit');

        try {
            $user = Auth::user();
            
            if (!$user) {
                \Log::warning('[PauseBuy] Unauthenticated request');
                return response()->json([
                    'orders' => [],
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $tenantId = $user->tenant_id;
            $locationId = $user->location_id ?? 1;
            
            $selectedDepartmentId = $request->input('department', '');
            
            if (empty($selectedDepartmentId)) {
                return response()->json([
                    'orders' => [],
                    'message' => 'Please select a department first',
                ]);
            }

            $orders = Order::with(['orderItems.variant.product'])
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('source', 'pos')
                ->where('status', 'confirmed')
                ->where('paid_amount', 0)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            // \Log::info('[PauseBuy] found ' . $orders->count());

            $result = $orders->map(function ($order) use ($selectedDepartmentId, $locationId) {
                $items = $order->orderItems->map(function ($item) use ($selectedDepartmentId, $locationId, $order) { // ✅ Pass $order here
                    $variant = $item->variant;
                    $product = $variant?->product;

                    // ✅ Get inventory for this variant in the selected department
                    $inventoryId = null;
                    $departmentId = null;
                    $quantityAvailable = 0;

                    if ($variant) {
                        $inventory = $variant->inventory()
                            ->where('department_id', $selectedDepartmentId)
                            ->where('location_id', $locationId)
                            ->first();
                        
                        if ($inventory) {
                            $inventoryId = $inventory->id;
                            $departmentId = $inventory->department_id;
                            $quantityAvailable = (int) $inventory->quantity_allocated;
                        }
                    }

                    // Decode JSON blobs
                    $taxes = [];
                    if (!empty($item->tax_data)) {
                        $decoded = is_array($item->tax_data)
                            ? $item->tax_data
                            : json_decode($item->tax_data, true);
                        $taxes = is_array($decoded) ? $decoded : [];
                    }

                    $promotions = [];
                    if (!empty($item->promotion_data)) {
                        $decoded = is_array($item->promotion_data)
                            ? $item->promotion_data
                            : json_decode($item->promotion_data, true);
                        $promotions = is_array($decoded) ? $decoded : [];
                    }

                    // ✅ Get inventory strategy from product
                    $strategy = $product?->inventory_strategy ?? 'quantity';

                    // ✅ Get batch and serial data
                    $batchId = $item->batch_id ?? null;
                    $batchNumber = $item->batch_number ?? null;
                    $serialId = $item->serial_id ?? null;
                    $serialNumber = $item->serial_number ?? null;

                    // ✅ If serial_id exists, verify it's still available
                    $serialAvailable = true;
                    if ($serialId) {
                        $serial = \App\Models\SerialNumber::where('id', $serialId)
                            ->where('status', 'available')
                            ->where('tenant_id', $order->tenant_id) // ✅ Now $order is in scope
                            ->exists();
                        $serialAvailable = $serial;
                    }

                    // Image
                    $imageUrl = asset('assets/media/stock/ecommerce/2.png');
                    if ($variant) {
                        $raw = $variant->image_url ?? $product?->image_url ?? null;
                        if ($raw) {
                            try {
                                $imageUrl = productVariantImage($raw);
                            } catch (\Exception $e) {
                                // Keep default
                            }
                        }
                    }

                    return [
                        'id'                 => $item->id,
                        'variant_id'         => $item->variant_id,
                        'name'               => $item->item_name,
                        'item_name'          => $item->item_name,
                        'sku'                => $item->sku ?? '',
                        'unit_price'         => (float) $item->unit_price,
                        'price'              => (float) $item->unit_price,
                        'quantity'           => (int)   $item->quantity,
                        'quantity_available' => $quantityAvailable,
                        'image_url'          => $imageUrl,
                        'taxes'              => $taxes,
                        'promotions'         => $promotions,
                        'tax_amount'         => (float) $item->tax_amount,
                        'discount'           => (float) $item->discount,
                        'total'              => (float) $item->total_price,
                        'inventory_id'       => $inventoryId,
                        'department_id'      => $departmentId,
                        // ✅ NEW: Add strategy, batch, and serial data
                        'strategy'           => $strategy,
                        'batch_id'           => $batchId,
                        'batch_number'       => $batchNumber,
                        'serial_id'          => $serialId,
                        'serial_number'      => $serialNumber,
                        'serial_available'   => $serialAvailable,
                    ];
                })->values()->toArray();

                return [
                    'id'            => $order->id,
                    'order_id'      => $order->id,
                    'order_number'  => $order->order_number,
                    'customer_id'   => $order->customer_id,
                    'customer_name' => $order->customer_name ?? __('pagination.guest'),
                    'subtotal'      => (float) $order->subtotal,
                    'discount'      => (float) $order->discount_total,
                    'tax'           => (float) $order->tax_total,
                    'total'         => (float) $order->total,
                    'source'        => $order->source,
                    'created_at'    => $order->created_at?->toISOString(),
                    'items'         => $items,
                ];
            });

            return response()->json(['orders' => $result]);

        } catch (\Exception $e) {
            \Log::error('[PauseBuy] error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'orders' => [],
                'message' => __('pagination.error_loading_orders'),
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


}