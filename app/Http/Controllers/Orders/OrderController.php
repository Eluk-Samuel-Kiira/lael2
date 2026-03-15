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
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        $orders = Order::with([
            'orderItems',
            'customer',
            'orderCreater',
            'location',
            'department'
        ])->latest()->get();


        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'ordersIndexTable':
                return view('orders.order.component', [
                    'orders' => $orders,
                ]);
            default:
                return view('orders.order-index', [
                    'orders' => $orders,
                ]);
        }
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
            $user       = Auth::user();
            $tenantId   = $user->tenant_id;
            $locationId = $user->location_id ?? 1;

            $orders = Order::with(['orderItems.variant.product'])
                ->where('tenant_id',   $tenantId)
                ->where('location_id', $locationId)
                ->where('source',      'pos')
                ->where('status',      'confirmed')
                ->where('paid_amount', 0)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            \Log::info('[PauseBuy] found ' . $orders->count());

            $result = $orders->map(function ($order) {

                $items = $order->orderItems->map(function ($item) {
                    $variant = $item->variant;
                    $product = $variant?->product;

                    // Safely decode JSON blobs
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

                    // Live stock
                    $qtyAvailable = 9999;
                    if ($variant) {
                        $qtyAvailable = (int) (
                            $variant->quantity_available
                            ?? $variant->overal_quantity_at_hand
                            ?? 9999
                        );
                    }

                    // Image
                    $imageUrl = asset('assets/media/avatars/blank.png');
                    if ($variant) {
                        $raw      = $variant->image_url ?? $product?->image_url ?? null;
                        $imageUrl = $raw ? productVariantImage($raw) : $imageUrl;
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
                        'quantity_available' => $qtyAvailable,
                        'image_url'          => $imageUrl,
                        'taxes'              => $taxes,
                        'promotions'         => $promotions,
                        'tax_amount'         => (float) $item->tax_amount,
                        'discount'           => (float) $item->discount,
                        'total'              => (float) $item->total_price,
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
            \Log::error('[PauseBuy] error: ' . $e->getMessage());
            return response()->json([
                'orders'  => [],
                'message' => __('pagination.error_loading_orders'),
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    }
