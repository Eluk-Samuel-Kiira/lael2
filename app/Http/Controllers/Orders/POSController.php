<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB, Log };
use App\Models\{ Product, SingleShopInventoryLog, InventoryItems, ProductVariant, Order, InventoryTransactions };
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; 
use App\Models\{ OrderItem, OrderPayment, InventoryLog, Customer, Inventory, OrderTax, InventoryAdjustments,
                    PaymentMethod };


class POSController extends Controller
{
    public function index(Request $request)
    {
        Artisan::call('optimize:clear');
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('view order')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Check if this is a single shop tenant
        $isSingleShop = tenant_is_single_shop($tenantId);
        // dd($isSingleShop);

        $now = now();

        if ($isSingleShop) {
            // Single shop: Get all product variants regardless of department, inventory, location
            $products = Product::with([
                    'taxes' => fn($q) => $q->where('is_active', 1),
                    'promotions' => fn($q) => $q
                        ->where('is_active', 1)
                        ->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now),
                    'variants' => function($query) {
                        $query->where('is_active', 1)
                            ->orderBy('name');
                    },
                    'variants.variantTaxes' => fn($q) => $q->where('is_active', 1),
                    'variants.variantPromotions' => fn($q) => $q
                        ->where('is_active', 1)
                        ->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now),
                ])
                ->where('tenant_id', $tenantId) // Added tenant filter
                ->where('is_active', 1)
                ->whereHas('variants') // Only products that have variants
                ->latest()
                ->get();

        } else {
            // Multi-shop: Filter variants by inventory in user's departments (same location)
            $user_departments = $user->departments()->pluck('departments.id');
            $user_location_id = $user->location_id;

            $products = Product::with([
                'departments',
                'taxes' => fn($q) => $q->where('is_active', 1),
                'promotions' => fn($q) => $q
                    ->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
                'variants' => function($query) use ($user_departments, $user_location_id) {
                    $query->where('is_active', 1)
                        ->whereHas('inventory')
                        ->with(['inventory' => function($q) use ($user_departments, $user_location_id) {
                            $q->whereIn('department_id', $user_departments)
                            ->where('location_id', $user_location_id);
                        }]);
                },
                'variants.variantTaxes' => fn($q) => $q->where('is_active', 1),
                'variants.variantPromotions' => fn($q) => $q
                    ->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->whereHas('variants', function($q) use ($user_departments, $user_location_id) {
                $q->where('is_active', 1)
                ->whereHas('inventory', function($query) use ($user_departments, $user_location_id) {
                    $query->whereIn('department_id', $user_departments)
                            ->where('location_id', $user_location_id);
                });
            })
            ->latest()
            ->get();

            // DEBUG: Check what inventory is actually being loaded
            // \Log::info("=== INVENTORY DEBUG ===");
            // foreach ($products as $product) {
            //     foreach ($product->variants as $variant) {
            //         \Log::info("Variant {$variant->id} inventory count: " . $variant->inventory->count());
            //         foreach ($variant->inventory as $inv) {
            //             \Log::info("  - Inventory ID: {$inv->id}, Dept: {$inv->department_id}, Loc: {$inv->location_id}");
            //         }
            //     }
            // }

            // Also check if there are any inventory records that should match but aren't being loaded
            $expectedInventory = \DB::table('inventory_items')
                ->whereIn('variant_id', [228, 229])
                ->whereIn('department_id', $user_departments)
                ->where('location_id', $user_location_id)
                ->get();

            \Log::info("Expected inventory records from DB: " . $expectedInventory->count());
            foreach ($expectedInventory as $inv) {
                \Log::info("  - Variant: {$inv->variant_id}, Dept: {$inv->department_id}, Loc: {$inv->location_id}");
            }
        }
        


        
        // Compute applicable taxes and promotions per variant
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                /** ---------------- Taxes ---------------- */
                $applicableTaxes = collect();
                if ((int)$variant->is_taxable === 0) {
                    $applicableTaxes = collect();
                } else {
                    if ((int)$product->is_taxable === 1 && $product->taxes->isNotEmpty()) {
                        $applicableTaxes = $product->taxes->keyBy('id');
                    }
                    if ($variant->variantTaxes->isNotEmpty()) {
                        foreach ($variant->variantTaxes as $vtax) {
                            $applicableTaxes[$vtax->id] = $vtax; // override if same id
                        }
                    }
                }

                $variant->applicable_taxes = $applicableTaxes->map(function ($t) {
                    $rate = (float) $t->rate;

                    return [
                        'id'   => (int)$t->id,
                        'name' => $t->name,
                        'rate'  => $t->type === 'fixed'
                                ? $rate // convert & format if fixed amount
                                : $rate,
                        'type' => $t->type,
                    ];
                })->values();

                /** ---------------- Promotions ---------------- */
                $applicablePromos = collect();

                // Start with product-level promos
                if ($product->promotions->isNotEmpty()) {
                    $applicablePromos = $product->promotions->keyBy('id');
                }

                // Merge variant promos (override if same id)
                if ($variant->variantPromotions->isNotEmpty()) {
                    foreach ($variant->variantPromotions as $vpromo) {
                        $applicablePromos[$vpromo->id] = $vpromo;
                    }
                }

                $variant->applicable_promotions = $applicablePromos->map(function ($p) {
                    $value = (float) $p->discount_value;
                    return [
                        'id'          => (int)$p->id,
                        'name'        => $p->name,
                        'type'        => $p->discount_type,   // 'percentage' | 'fixed' | 'buy_x_get_y'
                        'value'       => $p->discount_type === 'fixed'
                                        ? $value   // show in system currency convert to needed currency
                                        : $value, 
                        'start_date'  => $p->start_date,
                        'end_date'    => $p->end_date,
                    ];
                })->values();

                /** ---------------- Quantity Handling ---------------- */
                if ($isSingleShop) {
                    // Single shop: Use overal_quantity_at_hand directly
                    $variant->quantity_available = $variant->overal_quantity_at_hand ?? 0;
                    $variant->quantity_source = 'overall';
                } else {
                    // Multi-shop: Calculate from inventory using quantity_allocated
                    $inventory = $variant->inventory; // Now a Collection due to hasMany relationship

                    if ($inventory->isNotEmpty()) {
                        // Sum up quantity_allocated from all inventory records in the collection
                        $variant->quantity_available = $inventory->sum('quantity_allocated') ?? 0;
                    } else {
                        $variant->quantity_available = 0;
                    }
                    $variant->quantity_source = 'inventory_allocated';
                }

                /** ---------------- Currency Casting ---------------- */
                // Always raw in USD
                $variant->price      = $variant->price;
                $variant->cost_price = $variant->cost_price; // They are auto converted in the model by accessors
            }
        }

        // Only include user_departments for multi-shop scenario
        $user_departments = $user->departments()->get();

        // \Log::info($products->toArray());

        return view('orders.pos-index', compact('user_departments', 'products'));
    }


    public function processPayment(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (! $user->hasPermissionTo('create order')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        try {
            $cartData     = json_decode($request->cart_data, true);
            $isSingleShop = tenant_is_single_shop($tenantId);

            // ── RESUME PATH: existing paused order ───────────────────────
            // JS sets resumed_order_id when the cashier restores a paused cart.
            // In this case we skip creating a new order entirely — the existing
            // confirmed order is already in the DB, we just return its details
            // so the payment modal can complete it.
            $resumedOrderId = $request->input('resumed_order_id');

            if ($resumedOrderId) {
                $order = Order::findOrFail($resumedOrderId);

                // Safety: only allow resuming orders that belong to this tenant
                // and are still in a resumable state
                if ($order->tenant_id !== $tenantId) {
                    return response()->json([
                        'success' => false,
                        'message' => __('payments.not_authorized'),
                    ]);
                }

                if ($order->status !== 'confirmed' || $order->paid_amount > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => __('pagination.order_not_resumable'),
                    ]);
                }

                \Log::info('[PauseBuy] Resuming existing order', [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                ]);

                return response()->json([
                    'success'      => true,
                    'message'      => __('pagination.order_resumed'),
                    'order_number' => $order->order_number,
                    'customerName' => $order->customer_name,
                    'order_id'     => $order->id,
                    'is_single_shop' => $isSingleShop,
                    'resumed'      => true,   // flag for JS if needed
                ]);
            }

            // ── FRESH PATH: new order ─────────────────────────────────────
            $customerId   = null;
            $customerName = null;

            if (isset($cartData['customer'])) {
                if ($cartData['customer']['type'] === 'existing') {
                    $customerId   = $cartData['customer']['id'];
                    $customer     = Customer::find($customerId);
                    $customerName = $customer
                        ? trim($customer->first_name . ' ' . $customer->last_name)
                        : null;
                } elseif ($cartData['customer']['type'] === 'new') {
                    $customerName = $cartData['customer']['name'];
                }
            }

            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $order = Order::create([
                'tenant_id'      => $tenantId,
                'customer_id'    => $customerId,
                'customer_name'  => $customerName,
                'location_id'    => $user->location_id   ?? 1,
                'department_id'  => $user->department_id ?? 1,
                'order_number'   => $orderNumber,
                'type'           => 'sale',
                'status'         => 'confirmed',
                'subtotal'       => $cartData['subtotal'],
                'discount_total' => $cartData['discount'],
                'tax_total'      => $cartData['tax'],
                'total'          => $cartData['total'],
                'paid_amount'    => 0,
                'balance_due'    => 0,
                'source'         => 'pos',
                'created_by'     => $user->id,
            ]);

            foreach ($cartData['items'] as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                if (! $variant) continue;

                $inventoryData = [];
                if ($isSingleShop) {
                    $inventoryData = [
                        'initial_stock' => $variant->overal_quantity_at_hand,
                        'current_stock' => $variant->overal_quantity_at_hand - $item['quantity'],
                        'shop_type'     => 'single_shop',
                    ];
                } else {
                    $inventory = $variant->inventory()
                        ->where('location_id',  $user->location_id  ?? 1)
                        ->where('department_id', $user->department_id ?? 1)
                        ->first();

                    $inventoryData = [
                        'initial_stock' => $inventory ? $inventory->quantity_allocated : 0,
                        'current_stock' => $inventory ? $inventory->quantity_allocated - $item['quantity'] : 0,
                        'inventory_id'  => $inventory ? $inventory->id : null,
                        'location_id'   => $user->location_id  ?? 1,
                        'department_id' => $user->department_id ?? 1,
                        'shop_type'     => 'multi_shop',
                    ];
                }

                $order->orderItems()->create([
                    'product_id'      => $variant->product_id,
                    'variant_id'      => $variant->id,
                    'item_name'       => $item['name'],
                    'sku'             => $variant->sku,
                    'unit_price'      => $item['price'],
                    'quantity'        => $item['quantity'],
                    'tax_amount'      => $item['tax_total'],
                    'discount'        => $item['discount'],
                    'total_price'     => $item['total'],
                    'inventory_data'  => json_encode($inventoryData),
                    'tax_data'        => json_encode($item['taxes']      ?? []),
                    'promotion_data'  => json_encode($item['promotions'] ?? []),
                ]);
            }

            return response()->json([
                'success'        => true,
                'message'        => __('pagination.order_placed'),
                'order_number'   => $orderNumber,
                'customerName'   => $customerName,
                'order_id'       => $order->id,
                'is_single_shop' => $isSingleShop,
                'resumed'        => false,
            ]);

        } catch (\Exception $e) {
            \Log::error('Order processing failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('pagination.order_failed'),
            ], 500);
        }
    }

    public function processSplitPayment(Request $request)
    {
        try {
            $user     = Auth::user();
            $tenantId = $user->tenant_id;

            if (! $user->hasPermissionTo('complete order')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $order        = Order::findOrFail($request->order_id);
            $payments     = $request->payments ?? [];
            $isSingleShop = tenant_is_single_shop($tenantId);

            if (empty($payments)) {
                return response()->json([
                    'success' => false,
                    'message' => __('pagination.no_payments_added'),
                ]);
            }

            // ── Was the cart modified after resuming? ──────────────────────
            // JS sends updated_cart when the cashier changed items on a
            // resumed order. We sync the order + items before payment.
            $cartWasUpdated = (bool) $request->input('cart_updated', false);
            $updatedCart    = $request->input('updated_cart');   // full cart payload

            if ($cartWasUpdated && $updatedCart) {
                // Delete old items and replace with the updated cart
                $order->orderItems()->delete();

                $newSubtotal = 0;
                $newDiscount = 0;
                $newTax      = 0;

                foreach ($updatedCart['items'] as $item) {
                    $variant = ProductVariant::find($item['variant_id']);
                    if (! $variant) continue;

                    $inventoryData = [];
                    if ($isSingleShop) {
                        $inventoryData = [
                            'initial_stock' => $variant->overal_quantity_at_hand,
                            'current_stock' => $variant->overal_quantity_at_hand - $item['quantity'],
                            'shop_type'     => 'single_shop',
                        ];
                    } else {
                        $inventory = $variant->inventory()
                            ->where('location_id',   $user->location_id   ?? 1)
                            ->where('department_id', $user->department_id ?? 1)
                            ->first();
                        $inventoryData = [
                            'initial_stock' => $inventory ? $inventory->quantity_allocated : 0,
                            'current_stock' => $inventory ? $inventory->quantity_allocated - $item['quantity'] : 0,
                            'inventory_id'  => $inventory?->id,
                            'location_id'   => $user->location_id   ?? 1,
                            'department_id' => $user->department_id ?? 1,
                            'shop_type'     => 'multi_shop',
                        ];
                    }

                    $order->orderItems()->create([
                        'product_id'     => $variant->product_id,
                        'variant_id'     => $variant->id,
                        'item_name'      => $item['name'],
                        'sku'            => $variant->sku,
                        'unit_price'     => $item['price'],
                        'quantity'       => $item['quantity'],
                        'tax_amount'     => $item['tax_total']  ?? 0,
                        'discount'       => $item['discount']   ?? 0,
                        'total_price'    => $item['total'],
                        'inventory_data' => json_encode($inventoryData),
                        'tax_data'       => json_encode($item['taxes']      ?? []),
                        'promotion_data' => json_encode($item['promotions'] ?? []),
                    ]);

                    $newSubtotal += $item['subtotal'] ?? ($item['price'] * $item['quantity']);
                    $newDiscount += $item['discount'] ?? 0;
                    $newTax      += $item['tax_total'] ?? 0;
                }

                $newTotal = $newSubtotal - $newDiscount + $newTax;

                // Sync order header with updated totals
                $order->update([
                    'subtotal'       => $newSubtotal,
                    'discount_total' => $newDiscount,
                    'tax_total'      => $newTax,
                    'total'          => $newTotal,
                ]);

                // Refresh so $order->total is now the updated value
                $order->refresh();

                \Log::info('[POS] Order totals updated after cart change', [
                    'order_id' => $order->id,
                    'new_total' => $newTotal,
                ]);
            }

            // ── Validate payment total against (possibly updated) order ────
            $totalPaid = array_sum(array_column($payments, 'amount'));

            if (abs($totalPaid - $order->total) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => __('pagination.payment_total_mismatch'),
                    'expected' => $order->total,
                    'received' => $totalPaid,
                ]);
            }

            // ── Process each payment split ─────────────────────────────────
            $processedPayments = [];

            foreach ($payments as $payment) {
                $paymentMethod = PaymentMethod::findForTenant(
                    $payment['payment_method_id'], $tenantId
                );

                if (! $paymentMethod) {
                    return response()->json([
                        'success' => false,
                        'message' => __('pagination.payment_method_not_found'),
                    ]);
                }

                $validation = $paymentMethod->validateTransaction($payment['amount']);
                if (! $validation['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => __('pagination.payment_validation_failed') . ': ' . $validation['message'],
                    ]);
                }

                $this->recordOrderPaymentTransaction(
                    $order,
                    $paymentMethod,
                    $payment['amount'],
                    [
                        'amount_tendered' => $payment['tendered']              ?? $payment['amount'],
                        'change_due'      => $payment['change']                ?? 0,
                        'transaction_id'  => $payment['transaction_reference'] ?? null,
                    ]
                );

                OrderPayment::create([
                    'order_id'          => $order->id,
                    'amount'            => $payment['amount'],
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_id'    => $payment['transaction_reference'] ?? (string) Str::uuid(),
                    'status'            => 'completed',
                    'notes'             => __('pagination.payment_completed'),
                    'processed_at'      => now(),
                    'processed_by'      => $user->id,
                ]);

                $paymentMethod->current_balance += $payment['amount'];
                $paymentMethod->save();

                $processedPayments[] = [
                    'type'                  => $payment['type'],
                    'method_name'           => $paymentMethod->name,
                    'account_number'        => $paymentMethod->account_number ?? null,
                    'amount'                => (float) $payment['amount'],
                    'tendered'              => (float) ($payment['tendered'] ?? $payment['amount']),
                    'change'                => (float) ($payment['change']   ?? 0),
                    'transaction_reference' => $payment['transaction_reference'] ?? null,
                ];
            }

            // ── Record promotion/discount loss (after all splits processed) ─
            if ($order->discount_total > 0) {
                $order->load('orderItems'); // ensure items are available for breakdown
                $primaryMethod = PaymentMethod::findForTenant(
                    $payments[0]['payment_method_id'], $tenantId
                );
                if ($primaryMethod) {
                    $this->recordOrderPromotionLoss($order, $primaryMethod);
                }
            }

            // ── Tax record ─────────────────────────────────────────────────
            $taxAmount = $order->tax_total ?: 0;
            if ($taxAmount > 0) {
                $now = now();

                OrderTax::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'tax_name'    => 'VAT',
                        'tax_rate'    => $order->subtotal > 0
                            ? round(($taxAmount / $order->subtotal) * 100, 2)
                            : 0,
                        'tax_amount'  => $taxAmount,
                        'is_compound' => 1,
                        'created_by'  => $user->id,
                        // ── Remittance tracking ────────────────────
                        'tenant_id'   => $tenantId,
                        'status'      => 'pending',
                        'tax_year'    => $now->year,
                        'tax_month'   => $now->month,
                        'tax_quarter' => (int) ceil($now->month / 3),
                        // URA deadline: 15th of the following month
                        'due_date'    => $now->copy()->addMonthNoOverflow()->startOfMonth()->addDays(14),
                    ]
                );
            }

            // ── Inventory ──────────────────────────────────────────────────
            $order->load('orderItems'); // reload fresh items
            foreach ($order->orderItems as $item) {
                $variant = ProductVariant::find($item->variant_id);
                if (! $variant) continue;
                if ($isSingleShop) {
                    $this->handleSingleShopInventory($variant, $item, $order);
                } else {
                    $this->handleMultiShopInventory($variant, $item, $order);
                }
            }

            // ── Complete the order ─────────────────────────────────────────
            $order->update([
                'paid_amount'       => $totalPaid,
                'balance_due'       => 0,
                'status'            => 'completed',
                'payment_method_id' => null,
            ]);

            // ── Build receipt ──────────────────────────────────────────────
            $customerName = $order->customer_name;
            $customer     = null;
            if (! $customerName && $order->customer_id) {
                $customer     = Customer::find($order->customer_id);
                $customerName = $customer
                    ? trim($customer->first_name . ' ' . $customer->last_name)
                    : null;
            }

            $order->load('orderItems');

            return response()->json([
                'success' => true,
                'message' => __('pagination.payment_completed'),
                'order'   => [
                    'id'             => $order->id,
                    'order_number'   => $order->order_number,
                    'ref'            => $order->order_number,
                    'customer_name'  => $customerName ?? __('pagination.walk_in_customer'),
                    'customer'       => [
                        'name'  => $customerName ?? __('pagination.walk_in_customer'),
                        'phone' => $customer?->phone ?? null,
                        'email' => $customer?->email ?? null,
                    ],
                    'date'           => $order->created_at->format('Y-m-d'),
                    'time'           => $order->created_at->format('H:i:s'),
                    'subtotal'       => (float) $order->subtotal,
                    'discount'       => (float) $order->discount_total,
                    'tax'            => (float) $order->tax_total,
                    'total'          => (float) $order->total,
                    'total_paid'     => (float) $totalPaid,
                    'total_tendered' => (float) array_sum(array_column($processedPayments, 'tendered')),
                    'total_change'   => (float) array_sum(array_column($processedPayments, 'change')),
                    'items'          => $order->orderItems->map(fn ($item) => [
                        'name'     => $item->item_name,
                        'quantity' => (int)   $item->quantity,
                        'price'    => (float) $item->unit_price,
                        'total'    => (float) $item->total_price,
                        'note'     => $item->notes ?? null,
                    ])->toArray(),
                    'payments'       => $processedPayments,
                    'order_type'     => $order->type ?? 'sale',
                    'cashier'        => $user->name,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.order_not_found'),
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Split payment failed: ' . $e->getMessage(), [
                'order_id' => $request->order_id ?? null,
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('pagination.payment_error'),
            ], 500);
        }
    }

    /**
     * Record order payment transaction for POS sales
     * When selling items, money comes IN to your payment method
     */
    private function recordOrderPaymentTransaction($order, $paymentMethod, $amount, $paymentDetails = []): void
    {
        try {
            $transactionData = [
                'tenant_id' => $order->tenant_id,
                'user_id' => auth()->id(),
                'payment_method_id' => $paymentMethod->id,
                'transaction_type' => 'DEPOSIT', 
                'transaction_category' => 'ORDER',
                'amount' => $amount,
                'currency_id' => $paymentMethod->currency_id ?? Currency::default()->id,
                'reference_table' => 'orders',
                'reference_id' => $order->id,
                'description' => 'POS Sale - Order #' . $order->order_number,
                'notes' => 'Payment received for order',
                'metadata' => [
                    'order_number' => $order->order_number,
                    'customer_id' => $order->customer_id,
                    'customer_name' => $order->customer?->name,
                    'items_count' => $order->items()->count(),
                    'payment_type' => $paymentMethod->type,
                    'payment_details' => $paymentDetails,
                    'transaction_nature' => 'SALE_RECEIPT', // Indicates this is money received from sales
                ],
            ];

            // For cash payments, add cash-specific details
            if ($paymentMethod->type === 'cash') {
                $transactionData['metadata']['cash_details'] = [
                    'amount_tendered' => $paymentDetails['amount_tendered'] ?? 0,
                    'change_due' => $paymentDetails['change_due'] ?? 0,
                    'cash_handler' => auth()->user()->name,
                    'cash_received' => $amount, // Actual cash received
                ];
            }

            // For card payments
            if ($paymentMethod->type === 'card') {
                $transactionData['metadata']['card_details'] = [
                    'card_last_four' => $paymentMethod->card_last_four,
                    'card_type' => $paymentMethod->card_type,
                    'transaction_reference' => $paymentDetails['transaction_id'] ?? null,
                ];
            }

            // For bank transfers
            if ($paymentMethod->type === 'bank_account') {
                $transactionData['metadata']['bank_details'] = [
                    'account_name' => $paymentMethod->account_name,
                    'account_number' => $paymentMethod->account_number,
                    'reference' => $paymentDetails['reference'] ?? null,
                ];
            }

            // For mobile money
            if ($paymentMethod->type === 'mobile_money') {
                $transactionData['metadata']['mobile_money_details'] = [
                    'account_number' => $paymentMethod->account_number,
                    'provider' => $paymentMethod->provider,
                    'transaction_id' => $paymentDetails['transaction_id'] ?? null,
                ];
            }

            // Use the PaymentTransactionService to record the transaction
            $transactionLog = app('payment-transaction')->recordTransaction($transactionData);

            // Also update the order with payment method info
            $order->update([
                'payment_method_id' => $paymentMethod->id,
                'payment_method_type' => $paymentMethod->type,
                'payment_transaction_ref' => $transactionLog->transaction_ref ?? null,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to record payment transaction: ' . $e->getMessage());
            throw $e; // Re-throw to handle in main method
        }
    }

    /**
     * Record promotion/discount as a financial loss (revenue reduction).
     * Aggregates per-item promotion_data for a full breakdown in metadata.
     */
    private function recordOrderPromotionLoss($order, $paymentMethod): void
    {
        $totalDiscount = $order->discount_total ?? 0;

        if ($totalDiscount <= 0) {
            return;
        }

        try {
            // ── Build per-item promotion breakdown from stored promotion_data ──
            $itemPromotions = [];

            foreach ($order->orderItems as $item) {
                $promotions = is_string($item->promotion_data)
                    ? json_decode($item->promotion_data, true)
                    : ($item->promotion_data ?? []);

                if (! empty($promotions)) {
                    $itemPromotions[] = [
                        'item_name'    => $item->item_name,
                        'variant_id'   => $item->variant_id,
                        'sku'          => $item->sku,
                        'quantity'     => $item->quantity,
                        'unit_price'   => $item->unit_price,
                        'item_discount'=> $item->discount ?? 0,
                        'promotions'   => $promotions,
                    ];
                }
            }

            // ── Resolve customer name ──────────────────────────────────────────
            $customerName = $order->customer_name;
            if (! $customerName && $order->customer_id) {
                $customer     = \App\Models\Customer::find($order->customer_id);
                $customerName = $customer
                    ? trim($customer->first_name . ' ' . $customer->last_name)
                    : null;
            }

            $transactionData = [
                'tenant_id'            => $order->tenant_id,
                'user_id'              => auth()->id(),
                'payment_method_id'    => $paymentMethod->id,
                'transaction_type'     => 'WITHDRAWAL',
                'transaction_category' => 'ADJUSTMENT',
                'amount'               => $totalDiscount,
                'currency_id'          => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                'reference_table'      => 'orders',
                'reference_id'         => $order->id,
                'description'          => 'Promotion/Discount Loss - Order #' . $order->order_number,
                'notes'                => 'Revenue reduction from applied discounts and promotions',
                'metadata'             => [
                    'order_number'       => $order->order_number,
                    'customer_id'        => $order->customer_id,
                    'customer_name'      => $customerName ?? __('pagination.walk_in_customer'),
                    'subtotal_before'    => $order->subtotal,
                    'total_discount'     => $totalDiscount,
                    'final_total'        => $order->total,
                    'transaction_nature' => 'PROMOTION_LOSS',
                    'processed_by_id'    => auth()->id(),
                    'processed_by_name'  => auth()->user()->name,
                    'items_with_promotions' => $itemPromotions, // full per-item breakdown
                ],
            ];

            app('payment-transaction')->recordTransaction($transactionData);

            \Log::info('[POS] Promotion loss recorded', [
                'order_id'        => $order->id,
                'discount_total'  => $totalDiscount,
                'promoted_items'  => count($itemPromotions),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to record promotion loss: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            throw $e;
        }
    }

    /**
     * Helper method to record order payment using PaymentMethod model
     */
    // private function recordOrderPaymentSimple($order, $paymentMethod, $amount): void
    // {
    //     // Alternative simpler method using PaymentMethod model
    //     $transactionData = [
    //         'user_id' => auth()->id(),
    //         'transaction_type' => 'WITHDRAWAL',
    //         'transaction_category' => 'ORDER',
    //         'amount' => $amount,
    //         'currency_id' => $paymentMethod->currency_id ?? Currency::default()->id,
    //         'reference_id' => $order->id,
    //         'description' => 'Order payment #' . $order->order_number,
    //         'notes' => 'Payment processed',
    //     ];

    //     $paymentMethod->recordTransaction($transactionData);
    // }

    /**
     * Handle inventory updates for single shop
     */
    private function handleSingleShopInventory($variant, $item, $order)
    {
        $beforeQty = $variant->overal_quantity_at_hand;
        $afterQty = $beforeQty - $item['quantity'];

        // Update overall quantity
        $variant->update([
            'overal_quantity_at_hand' => $afterQty
        ]);

        // Record single shop transaction using the new model
        SingleShopInventoryLog::create([
            'variant_id' => $variant->id,
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'created_by' => auth()->id(),
            'quantity_before' => $beforeQty,
            'quantity_after' => $afterQty,
            'quantity_change' => -$item['quantity'],
            'reason' => 'pos_sale',
            'notes' => 'POS sale - Order #' . $order->order_number,
            'source' => 'pos',
            'metadata' => [
                'item_name' => $item['name'],
                'unit_price' => $item['price'],
                'customer_name' => $order->customer_name,
                'location_id' => $order->location_id,
                'department_id' => $order->department_id,
            ],
        ]);
    }

    /**
     * Handle inventory updates for multi shop
     */
    private function handleMultiShopInventory($variant, $item, $order, $user = null)
    {
        // \Log::info('Yes it reached');
        // Get the user if not passed
        if (!$user) {
            $user = Auth::user();
        }

        // Get the specific inventory item for this location/department
        // IMPORTANT: inventory() is a relationship, not a property
        $inventory = $variant->inventory()
            ->where('location_id', $user->location_id ?? 1)
            ->where('department_id', $user->department_id ?? 1)
            ->first();

        if (!$inventory) {
            \Log::warning("No inventory found for variant {$variant->id} in multi-shop mode", [
                'variant_id' => $variant->id,
                'location_id' => $user->location_id ?? 1,
                'department_id' => $user->department_id ?? 1,
                'order_id' => $order->id
            ]);
            return;
        }

        $beforeQty = $inventory->quantity_allocated;
        $afterQty = $beforeQty - $item['quantity'];

        // Update inventory
        $inventory->update([
            'quantity_allocated' => $afterQty
        ]);

        // Record adjustment (audit trail)
        InventoryAdjustments::create([
            'quantity_before' => $beforeQty,
            'quantity_after' => $afterQty,
            'reason' => 'order_sale',
            'notes' => 'Stock reduced due to order #' . $order->order_number,
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $order->tenant_id,
        ]);

        // Record transaction (movement)
        InventoryTransactions::create([
            'quantity' => -$item['quantity'],
            'reference_id' => $order->id,
            'reference_type' => 'order',
            'type' => 'sale',
            'notes' => 'Sold ' . $item['quantity'] . ' units of ' . $variant->sku,
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $order->tenant_id,
        ]);

        \Log::info('Multi-shop inventory updated', [
            'variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'location_id' => $user->location_id ?? 1,
            'department_id' => $user->department_id ?? 1,
            'quantity_sold' => $item['quantity'],
            'before' => $beforeQty,
            'after' => $afterQty
        ]);
    }

    
    public function cancel(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                  
        if (!$user->hasPermissionTo('cancel order')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $validated = $request->validate([
            'status' => 'required', 
        ]);
        
        $order = Order::where('id', $id)
                    ->where('tenant_id', $tenantId)
                    ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        // Check if status is already cancelled
        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.already_cancelled'),
            ], 400);
        }
        
        // Validate that the requested status is cancelled
        if ($validated['status'] !== 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => __('passwords.invalid_status_transition'),
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Update purchase order status
            $order->status = $validated['status'];
            $order->created_by = auth()->id();
            
            if ($order->save()) {  
                DB::commit();

                // Return JSON success - don't redirect here
                return response()->json([
                    'success' => true,
                    'message' => __('passwords.cancel_success'),
                    'redirect' => route('orders.index') // Optional: send redirect URL if needed
                ]);
            }

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('passwords.status_update_failed'),
            ], 500);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('passwords.error_occurred') . $e->getMessage(),
            ], 500);
        }
    }



    

}

