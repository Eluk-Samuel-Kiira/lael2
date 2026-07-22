<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB, Log };
use App\Models\{ Product, SingleShopInventoryLog, InventoryItems, ProductVariant, Order, InventoryTransactions, Invoice };
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; 
use App\Models\{ OrderItem, OrderPayment, InventoryLog, Customer, Inventory, OrderTax, InventoryAdjustments,
                    PaymentMethod, Currency };


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

        $isSingleShop = tenant_is_single_shop($tenantId);
        $now = now();
        $departmentId = $request->input('department', '');

        // ✅ Get user departments (for multi-shop)
        $user_departments = $user->departments()->get();

        // ✅ Build the base query with filters
        $productsQuery = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->whereHas('variants', function($q) {
                $q->where('is_active', 1);
            });

        // ✅ Apply department filter for multi-shop
        if (!$isSingleShop && !empty($departmentId)) {
            $productsQuery->whereHas('departments', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        // ✅ IMPORTANT: Limit to only 20 products for initial load
        $products = $productsQuery->limit(20)->get();

        // ✅ Eager load relationships based on what we have
        if ($isSingleShop) {
            $products->load([
                'taxes' => fn($q) => $q->where('is_active', 1),
                'promotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
                'variants' => function($query) {
                    $query->where('is_active', 1)->orderBy('name');
                },
                'variants.variantTaxes' => fn($q) => $q->where('is_active', 1),
                'variants.variantPromotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
            ]);

            foreach ($products as $product) {
                foreach ($product->variants as $variant) {
                    $variant->quantity_available = $variant->overal_quantity_at_hand ?? 0;
                    $variant->quantity_source = 'overall';
                    $variant->inventory_by_dept = [];
                }
            }
        } else {
            $userDepartmentIds = $user->departments()->pluck('departments.id');
            $userLocationId = $user->location_id;

            $products->load([
                'departments',
                'taxes' => fn($q) => $q->where('is_active', 1),
                'promotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
                'variants' => function($query) use ($userDepartmentIds, $userLocationId, $now) {
                    $query->where('is_active', 1)
                        ->whereHas('inventory', function($q) use ($userDepartmentIds, $userLocationId) {
                            $q->whereIn('department_id', $userDepartmentIds)
                            ->where('location_id', $userLocationId);
                        })
                        ->with(['inventory' => function($q) use ($userDepartmentIds, $userLocationId) {
                            $q->whereIn('department_id', $userDepartmentIds)
                            ->where('location_id', $userLocationId);
                        }])
                        ->with(['variantTaxes' => fn($q) => $q->where('is_active', 1)])
                        ->with(['variantPromotions' => fn($q) => $q
                            ->where('is_active', 1)
                            ->where('start_date', '<=', $now)
                            ->where('end_date', '>=', $now)
                        ]);
                },
                'variants.variantTaxes' => fn($q) => $q->where('is_active', 1),
                'variants.variantPromotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
            ]);

            // Attach inventory_by_dept to each variant
            foreach ($products as $product) {
                foreach ($product->variants as $variant) {
                    $inventoryByDept = [];
                    $totalQty = 0;
                    foreach ($variant->inventory as $inv) {
                        $inventoryByDept[$inv->department_id] = [
                            'inventory_id' => $inv->id,
                            'quantity' => $inv->quantity_allocated,
                            'location_id' => $inv->location_id,
                            'department_id' => $inv->department_id,
                        ];
                        $totalQty += $inv->quantity_allocated;
                    }
                    $variant->inventory_by_dept = $inventoryByDept;
                    $variant->quantity_available = $totalQty;
                    $variant->quantity_source = 'inventory_allocated';
                }
            }
        }

        // ✅ Compute taxes and promotions
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                /** ---------------- TAXES ---------------- */
                if ((int)$variant->is_taxable === 0) {
                    $variant->applicable_taxes = collect();
                } else {
                    $applicableTaxes = collect();
                    
                    if ($variant->variantTaxes->isNotEmpty()) {
                        $applicableTaxes = $variant->variantTaxes->keyBy('id');
                    } else if ((int)$product->is_taxable === 1 && $product->taxes->isNotEmpty()) {
                        $applicableTaxes = $product->taxes->keyBy('id');
                    }

                    $variant->applicable_taxes = $applicableTaxes->map(function ($t) {
                        $rate = (float) $t->rate;
                        return [
                            'id'   => (int)$t->id,
                            'name' => $t->name,
                            'rate' => $rate,
                            'type' => $t->type,
                        ];
                    })->values();
                }

                /** ---------------- PROMOTIONS ---------------- */
                $applicablePromos = collect();

                if ($variant->variantPromotions->isNotEmpty()) {
                    $applicablePromos = $variant->variantPromotions->keyBy('id');
                } else if ($product->promotions->isNotEmpty()) {
                    $applicablePromos = $product->promotions->keyBy('id');
                }

                $variant->applicable_promotions = $applicablePromos->map(function ($p) {
                    $value = (float) $p->discount_value;
                    return [
                        'id'          => (int)$p->id,
                        'name'        => $p->name,
                        'type'        => $p->discount_type,
                        'value'       => $value,
                        'start_date'  => $p->start_date,
                        'end_date'    => $p->end_date,
                    ];
                })->values();

                $variant->price = $variant->price;
                $variant->grant_total_cost_price = $variant->grant_total_cost_price;
            }
        }

        return view('orders.pos-index', compact('products', 'user_departments', 'isSingleShop'));
    }

    /**
     * Search products and variants for POS
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view order')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $isSingleShop = tenant_is_single_shop($tenantId);
        $now = now();
        $searchTerm = $request->input('search', '');
        $departmentId = $request->input('department', '');

        // If no search term, return empty
        if (empty($searchTerm)) {
            return response()->json([
                'success' => true,
                'products' => [],
                'has_more' => false,
            ]);
        }

        // Build the query
        $productsQuery = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1);

        // Search by product name, SKU, or variant name/SKU
        $productsQuery->where(function($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('sku', 'LIKE', "%{$searchTerm}%")
            ->orWhereHas('variants', function($vq) use ($searchTerm) {
                $vq->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('sku', 'LIKE', "%{$searchTerm}%");
            });
        });

        // Apply department filter for multi-shop
        if (!$isSingleShop && !empty($departmentId)) {
            $productsQuery->whereHas('departments', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        // Limit results for performance
        $products = $productsQuery->limit(20)->get();

        // If no products found via product search, try direct variant search
        if ($products->isEmpty()) {
            $variantIds = ProductVariant::where('is_active', 1)
                ->where('tenant_id', $tenantId)
                ->where(function($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('sku', 'LIKE', "%{$searchTerm}%");
                })
                ->pluck('product_id')
                ->unique()
                ->toArray();

            if (!empty($variantIds)) {
                $products = Product::whereIn('id', $variantIds)
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', 1)
                    ->limit(20)
                    ->get();
            }
        }

        // Load relationships
        if ($isSingleShop) {
            $products->load([
                'taxes' => fn($q) => $q->where('is_active', 1),
                'promotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
                'variants' => function($query) {
                    $query->where('is_active', 1)->orderBy('name');
                },
                'variants.variantTaxes' => fn($q) => $q->where('is_active', 1),
                'variants.variantPromotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
            ]);

            foreach ($products as $product) {
                foreach ($product->variants as $variant) {
                    $variant->quantity_available = $variant->overal_quantity_at_hand ?? 0;
                    $variant->quantity_source = 'overall';
                    $variant->inventory_by_dept = [];
                }
            }
        } else {
            $userDepartmentIds = $user->departments()->pluck('departments.id');
            $userLocationId = $user->location_id;

            $products->load([
                'departments',
                'taxes' => fn($q) => $q->where('is_active', 1),
                'promotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
                'variants' => function($query) use ($userDepartmentIds, $userLocationId, $now) {
                    $query->where('is_active', 1)
                        ->whereHas('inventory', function($q) use ($userDepartmentIds, $userLocationId) {
                            $q->whereIn('department_id', $userDepartmentIds)
                            ->where('location_id', $userLocationId);
                        })
                        ->with(['inventory' => function($q) use ($userDepartmentIds, $userLocationId) {
                            $q->whereIn('department_id', $userDepartmentIds)
                            ->where('location_id', $userLocationId);
                        }])
                        ->with(['variantTaxes' => fn($q) => $q->where('is_active', 1)])
                        ->with(['variantPromotions' => fn($q) => $q
                            ->where('is_active', 1)
                            ->where('start_date', '<=', $now)
                            ->where('end_date', '>=', $now)
                        ]);
                },
                'variants.variantTaxes' => fn($q) => $q->where('is_active', 1),
                'variants.variantPromotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
            ]);

            // Attach inventory_by_dept to each variant
            foreach ($products as $product) {
                foreach ($product->variants as $variant) {
                    $inventoryByDept = [];
                    $totalQty = 0;
                    foreach ($variant->inventory as $inv) {
                        $inventoryByDept[$inv->department_id] = [
                            'inventory_id' => $inv->id,
                            'quantity' => $inv->quantity_allocated,
                            'location_id' => $inv->location_id,
                            'department_id' => $inv->department_id,
                        ];
                        $totalQty += $inv->quantity_allocated;
                    }
                    $variant->inventory_by_dept = $inventoryByDept;
                    $variant->quantity_available = $totalQty;
                    $variant->quantity_source = 'inventory_allocated';
                }
            }
        }

        // Compute taxes and promotions
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                // Taxes
                if ((int)$variant->is_taxable === 0) {
                    $variant->applicable_taxes = collect();
                } else {
                    $applicableTaxes = collect();
                    
                    if ($variant->variantTaxes->isNotEmpty()) {
                        $applicableTaxes = $variant->variantTaxes->keyBy('id');
                    } else if ((int)$product->is_taxable === 1 && $product->taxes->isNotEmpty()) {
                        $applicableTaxes = $product->taxes->keyBy('id');
                    }

                    $variant->applicable_taxes = $applicableTaxes->map(function ($t) {
                        $rate = (float) $t->rate;
                        return [
                            'id'   => (int)$t->id,
                            'name' => $t->name,
                            'rate' => $rate,
                            'type' => $t->type,
                        ];
                    })->values();
                }

                // Promotions
                $applicablePromos = collect();

                if ($variant->variantPromotions->isNotEmpty()) {
                    $applicablePromos = $variant->variantPromotions->keyBy('id');
                } else if ($product->promotions->isNotEmpty()) {
                    $applicablePromos = $product->promotions->keyBy('id');
                }

                $variant->applicable_promotions = $applicablePromos->map(function ($p) {
                    $value = (float) $p->discount_value;
                    return [
                        'id'          => (int)$p->id,
                        'name'        => $p->name,
                        'type'        => $p->discount_type,
                        'value'       => $value,
                        'start_date'  => $p->start_date,
                        'end_date'    => $p->end_date,
                    ];
                })->values();

                $variant->price = $variant->price;
                $variant->grant_total_cost_price = $variant->grant_total_cost_price;
            }
        }

        // ✅ Return JSON response
        return response()->json([
            'success' => true,
            'products' => $products,
            'has_more' => $products->count() >= 20,
            'is_single_shop' => $isSingleShop,
        ]);
    }


    public function processPayment(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create order')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        try {
            $cartData = json_decode($request->cart_data, true);
            $isSingleShop = tenant_is_single_shop($tenantId);

            // ── RESUME PATH: existing paused order ───────────────────────
            $resumedOrderId = $request->input('resumed_order_id');

            if ($resumedOrderId) {
                $order = Order::findOrFail($resumedOrderId);

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

                // \Log::info('[PauseBuy] Resuming existing order', [
                //     'order_id' => $order->id,
                //     'order_number' => $order->order_number,
                // ]);

                return response()->json([
                    'success' => true,
                    'message' => __('pagination.order_resumed'),
                    'order_number' => $order->order_number,
                    'customerName' => $order->customer_name,
                    'order_id' => $order->id,
                    'is_single_shop' => $isSingleShop,
                    'resumed' => true,
                ]);
            }

            // ── FRESH PATH: new order ─────────────────────────────────────
            $customerId = null;
            $customerName = null;

            if (isset($cartData['customer'])) {
                if ($cartData['customer']['type'] === 'existing') {
                    $customerId = $cartData['customer']['id'];
                    $customer = Customer::find($customerId);
                    $customerName = $customer
                        ? trim($customer->first_name . ' ' . $customer->last_name)
                        : null;
                } elseif ($cartData['customer']['type'] === 'new') {
                    $customerName = $cartData['customer']['name'];
                }
            }

            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $order = Order::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'location_id' => $user->location_id ?? 1,
                'department_id' => $user->department_id ?? 1,
                'order_number' => $orderNumber,
                'type' => 'sale',
                'status' => 'confirmed',
                'subtotal' => $cartData['subtotal'],
                'discount_total' => $cartData['discount'],
                'tax_total' => $cartData['tax'],
                'total' => $cartData['total'],
                'paid_amount' => 0,
                'balance_due' => 0,
                'subtotal_before_bargain' => null,
                'bargain_discount_applied' => 0,
                'source' => 'pos',
                'created_by' => $user->id,
            ]);

            foreach ($cartData['items'] as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                if (!$variant) continue;

                if ($isSingleShop) {
                    $inventoryData = [
                        'initial_stock' => $variant->overal_quantity_at_hand,
                        'current_stock' => $variant->overal_quantity_at_hand - $item['quantity'],
                        'shop_type' => 'single_shop',
                    ];
                } else {
                    // ✅ Use inventory_id and department_id from cart
                    $inventoryId = $item['inventory_id'] ?? null;
                    $departmentId = $item['department_id'] ?? $user->department_id ?? 1;
                    $locationId = $user->location_id ?? 1;

                    if ($inventoryId) {
                        $inventory = InventoryItems::find($inventoryId);
                        if ($inventory) {
                            $inventoryData = [
                                'initial_stock' => $inventory->quantity_allocated,
                                'current_stock' => $inventory->quantity_allocated - $item['quantity'],
                                'inventory_id'  => $inventory->id,
                                'location_id'   => $inventory->location_id,
                                'department_id' => $inventory->department_id,
                                'shop_type'     => 'multi_shop',
                            ];
                        } else {
                            // Fallback: query inventory
                            $inventory = $variant->inventory()
                                ->where('location_id', $locationId)
                                ->where('department_id', $departmentId)
                                ->first();
                            $inventoryData = [
                                'initial_stock' => $inventory ? $inventory->quantity_allocated : 0,
                                'current_stock' => $inventory ? $inventory->quantity_allocated - $item['quantity'] : 0,
                                'inventory_id'  => $inventory?->id,
                                'location_id'   => $locationId,
                                'department_id' => $departmentId,
                                'shop_type'     => 'multi_shop',
                            ];
                        }
                    } else {
                        // Fallback: query inventory
                        $inventory = $variant->inventory()
                            ->where('location_id', $locationId)
                            ->where('department_id', $departmentId)
                            ->first();
                        $inventoryData = [
                            'initial_stock' => $inventory ? $inventory->quantity_allocated : 0,
                            'current_stock' => $inventory ? $inventory->quantity_allocated - $item['quantity'] : 0,
                            'inventory_id'  => $inventory?->id,
                            'location_id'   => $locationId,
                            'department_id' => $departmentId,
                            'shop_type'     => 'multi_shop',
                        ];
                    }
                }

                $order->orderItems()->create([
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'item_name' => $item['name'],
                    'sku' => $variant->sku,
                    'unit_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'tax_amount' => $item['tax_total'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'total_price' => $item['total'],
                    'inventory_data' => json_encode($inventoryData),
                    'tax_data' => json_encode($item['taxes'] ?? []),
                    'promotion_data' => json_encode($item['promotions'] ?? []),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => __('pagination.order_placed'),
                'order_number' => $orderNumber,
                'customerName' => $customerName,
                'order_id' => $order->id,
                'is_single_shop' => $isSingleShop,
                'resumed' => false,
            ]);

        } catch (\Exception $e) {
            \Log::error('Order processing failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('pagination.order_failed'),
            ], 500);
        }
    }

    public function generateInvoice(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create order')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        try {
            $cartData = json_decode($request->cart_data, true);
            $isSingleShop = tenant_is_single_shop($tenantId);

            if (empty($cartData['items'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('pagination.cart_empty'),
                ]);
            }

            $customerId = null;
            $customerName = null;
            $customerEmail = null;
            $customerPhone = null;
            $customerAddress = null;

            if (isset($cartData['customer']['type']) && $cartData['customer']['type'] === 'existing') {
                $customerId = $cartData['customer']['id'];
                $customer = Customer::find($customerId);
                if ($customer) {
                    $customerName = trim($customer->first_name . ' ' . $customer->last_name);
                    $customerEmail = $customer->email;
                    $customerPhone = $customer->phone;
                    $customerAddress = $customer->address ?? null;
                }
            } elseif (isset($cartData['customer']['type']) && $cartData['customer']['type'] === 'new') {
                $customerName = $cartData['customer']['name'];
            }

            if (!$customerName) {
                return response()->json([
                    'success' => false,
                    'message' => __('pagination.customer_required_for_invoice'),
                ]);
            }

            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $order = Order::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'location_id' => $user->location_id ?? 1,
                'department_id' => $user->department_id ?? 1,
                'order_number' => $orderNumber,
                'type' => 'sale',
                'status' => 'confirmed',
                'subtotal' => $cartData['subtotal'],
                'discount_total' => $cartData['discount'],
                'tax_total' => $cartData['tax'],
                'total' => $cartData['total'],
                'paid_amount' => 0,
                'balance_due' => $cartData['total'],
                'subtotal_before_bargain' => null,
                'bargain_discount_applied' => 0,
                'source' => 'invoice',
                'created_by' => $user->id,
            ]);

            foreach ($cartData['items'] as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                if (!$variant) continue;

                // ✅ For invoices, we also need to track inventory
                if ($isSingleShop) {
                    $inventoryData = [
                        'initial_stock' => $variant->overal_quantity_at_hand,
                        'current_stock' => $variant->overal_quantity_at_hand - $item['quantity'],
                        'shop_type' => 'single_shop',
                    ];
                } else {
                    // ✅ Use inventory_id and department_id from cart for invoices too
                    $inventoryId = $item['inventory_id'] ?? null;
                    $departmentId = $item['department_id'] ?? $user->department_id ?? 1;
                    $locationId = $user->location_id ?? 1;

                    if ($inventoryId) {
                        $inventory = InventoryItems::find($inventoryId);
                        if ($inventory) {
                            $inventoryData = [
                                'initial_stock' => $inventory->quantity_allocated,
                                'current_stock' => $inventory->quantity_allocated - $item['quantity'],
                                'inventory_id'  => $inventory->id,
                                'location_id'   => $inventory->location_id,
                                'department_id' => $inventory->department_id,
                                'shop_type'     => 'multi_shop',
                            ];
                        } else {
                            $inventory = $variant->inventory()
                                ->where('location_id', $locationId)
                                ->where('department_id', $departmentId)
                                ->first();
                            $inventoryData = [
                                'initial_stock' => $inventory ? $inventory->quantity_allocated : 0,
                                'current_stock' => $inventory ? $inventory->quantity_allocated - $item['quantity'] : 0,
                                'inventory_id'  => $inventory?->id,
                                'location_id'   => $locationId,
                                'department_id' => $departmentId,
                                'shop_type'     => 'multi_shop',
                            ];
                        }
                    } else {
                        $inventory = $variant->inventory()
                            ->where('location_id', $locationId)
                            ->where('department_id', $departmentId)
                            ->first();
                        $inventoryData = [
                            'initial_stock' => $inventory ? $inventory->quantity_allocated : 0,
                            'current_stock' => $inventory ? $inventory->quantity_allocated - $item['quantity'] : 0,
                            'inventory_id'  => $inventory?->id,
                            'location_id'   => $locationId,
                            'department_id' => $departmentId,
                            'shop_type'     => 'multi_shop',
                        ];
                    }
                }

                $order->orderItems()->create([
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'item_name' => $item['name'],
                    'sku' => $variant->sku,
                    'unit_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'tax_amount' => $item['tax_total'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'total_price' => $item['total'],
                    'inventory_data' => json_encode($inventoryData),
                    'tax_data' => json_encode($item['taxes'] ?? []),
                    'promotion_data' => json_encode($item['promotions'] ?? []),
                ]);
            }

            $invoiceNumber = Invoice::generateInvoiceNumber($tenantId);

            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'invoice_number' => $invoiceNumber,
                'public_token' => Invoice::generatePublicToken(),
                'billing_name' => $customerName,
                'billing_email' => $customerEmail,
                'billing_phone' => $customerPhone,
                'billing_address' => $customerAddress,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'status' => 'draft',
                'currency' => 'UGX',
                'subtotal' => $cartData['subtotal'],
                'discount_total' => $cartData['discount'],
                'tax_total' => $cartData['tax'],
                'total' => $cartData['total'],
                'amount_paid' => 0,
                'balance_due' => $cartData['total'],
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('pagination.invoice_generated'),
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'customerName' => $customerName,
            ]);

        } catch (\Exception $e) {
            \Log::error('Invoice generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('pagination.invoice_generation_failed'),
            ], 500);
        }
    }


    public function processSplitPayment(Request $request)
    {
        // \Log::info('processSplitPayment called', $request->all());
        
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('complete order')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $order = Order::lockForUpdate()->findOrFail($request->order_id);
            $payments = $request->payments ?? [];
            $isSingleShop = tenant_is_single_shop($tenantId);
            $bargainDiscount = (float) $request->input('bargain_discount', 0);

            // \Log::info('[POS] Current order state', [
            //     'order_id' => $order->id,
            //     'subtotal' => $order->subtotal,
            //     'tax_total' => $order->tax_total,
            //     'total' => $order->total,
            //     'discount_total' => $order->discount_total,
            //     'subtotal_before_bargain' => $order->subtotal_before_bargain,
            //     'bargain_discount_applied' => $order->bargain_discount_applied,
            //     'bargain_discount_requested' => $bargainDiscount,
            // ]);

            if (empty($payments)) {
                return response()->json([
                    'success' => false,
                    'message' => __('pagination.no_payments_added'),
                ]);
            }

            // ── Was the cart modified after resuming? ──────────────────────
            $cartWasUpdated = (bool) $request->input('cart_updated', false);
            $updatedCart = $request->input('updated_cart');

            if ($cartWasUpdated && $updatedCart) {
                $order->orderItems()->delete();

                $newSubtotal = 0;
                $newDiscount = 0;
                $newTax = 0;

                foreach ($updatedCart['items'] as $item) {
                    $variant = ProductVariant::find($item['variant_id']);
                    if (!$variant) continue;

                    if ($isSingleShop) {
                        $inventoryData = [
                            'initial_stock' => $variant->overal_quantity_at_hand,
                            'current_stock' => $variant->overal_quantity_at_hand - $item['quantity'],
                            'shop_type' => 'single_shop',
                        ];
                    } else {
                        // ✅ Use inventory_id and department_id from cart
                        $inventoryId = $item['inventory_id'] ?? null;
                        $departmentId = $item['department_id'] ?? $user->department_id ?? 1;
                        $locationId = $user->location_id ?? 1;

                        if ($inventoryId) {
                            $inventory = InventoryItems::find($inventoryId);
                            if ($inventory) {
                                $inventoryData = [
                                    'initial_stock' => $inventory->quantity_allocated,
                                    'current_stock' => $inventory->quantity_allocated - $item['quantity'],
                                    'inventory_id'  => $inventory->id,
                                    'location_id'   => $inventory->location_id,
                                    'department_id' => $inventory->department_id,
                                    'shop_type'     => 'multi_shop',
                                ];
                            } else {
                                // Fallback: query inventory
                                $inventory = $variant->inventory()
                                    ->where('location_id', $locationId)
                                    ->where('department_id', $departmentId)
                                    ->first();
                                $inventoryData = [
                                    'initial_stock' => $inventory ? $inventory->quantity_allocated : 0,
                                    'current_stock' => $inventory ? $inventory->quantity_allocated - $item['quantity'] : 0,
                                    'inventory_id'  => $inventory?->id,
                                    'location_id'   => $locationId,
                                    'department_id' => $departmentId,
                                    'shop_type'     => 'multi_shop',
                                ];
                            }
                        } else {
                            // Fallback: query inventory
                            $inventory = $variant->inventory()
                                ->where('location_id', $locationId)
                                ->where('department_id', $departmentId)
                                ->first();
                            $inventoryData = [
                                'initial_stock' => $inventory ? $inventory->quantity_allocated : 0,
                                'current_stock' => $inventory ? $inventory->quantity_allocated - $item['quantity'] : 0,
                                'inventory_id'  => $inventory?->id,
                                'location_id'   => $locationId,
                                'department_id' => $departmentId,
                                'shop_type'     => 'multi_shop',
                            ];
                        }
                    }

                    $order->orderItems()->create([
                        'product_id' => $variant->product_id,
                        'variant_id' => $variant->id,
                        'item_name' => $item['name'],
                        'sku' => $variant->sku,
                        'unit_price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'tax_amount' => $item['tax_total'] ?? 0,
                        'discount' => $item['discount'] ?? 0,
                        'total_price' => $item['total'],
                        'inventory_data' => json_encode($inventoryData),
                        'tax_data' => json_encode($item['taxes'] ?? []),
                        'promotion_data' => json_encode($item['promotions'] ?? []),
                    ]);

                    $newSubtotal += $item['subtotal'] ?? ($item['price'] * $item['quantity']);
                    $newDiscount += $item['discount'] ?? 0;
                    $newTax += $item['tax_total'] ?? 0;
                }

                $newTotal = $newSubtotal - $newDiscount + $newTax;

                // When cart changes, reset the bargain anchor
                $order->update([
                    'subtotal' => $newSubtotal,
                    'discount_total' => $newDiscount,
                    'tax_total' => $newTax,
                    'total' => $newTotal,
                    'subtotal_before_bargain' => 0,
                    'bargain_discount_applied' => 0,
                ]);

                $order->refresh();

                \Log::info('[POS] Order totals updated after cart change', [
                    'order_id' => $order->id,
                    'new_total' => $newTotal,
                ]);
            }

            // ── Apply negotiated/bargain discount (if any) ─────────────────
            if ($bargainDiscount > 0) {
                // ✅ SIMPLE FIX: Check if the anchor is 0 (meaning not set yet)
                // Since to_base_currency(null) returns 0, we check for 0
                $anchor = $order->subtotal_before_bargain;
                
                if ($anchor == 0 || $anchor === null) {
                    // ✅ First time applying a bargain discount
                    // The base is the current subtotal + tax (before any bargain discount)
                    $baseTotal = $order->subtotal + $order->tax_total;
                    
                    // ✅ Store the anchor
                    $order->subtotal_before_bargain = $baseTotal;
                    $order->save();
                    $order->refresh();
                    
                    // \Log::info('[POS] Bargain anchor created', [
                    //     'order_id' => $order->id,
                    //     'base_total' => $baseTotal,
                    // ]);
                    
                    $anchor = $order->subtotal_before_bargain;
                }

                // ✅ Validate against the base total
                if ($bargainDiscount > $anchor) {
                    return response()->json([
                        'success' => false,
                        'message' => __('pagination.discount_exceeds_total'),
                        'debug' => [
                            'bargain_discount' => $bargainDiscount,
                            'base_total' => $anchor,
                            'order_subtotal' => $order->subtotal,
                            'order_tax' => $order->tax_total,
                            'current_total' => $order->total,
                        ]
                    ]);
                }

                // ✅ Remove any existing bargain discount from discount_total
                // This prevents compounding
                $otherDiscount = $order->discount_total - $order->bargain_discount_applied;

                // ✅ Apply the new bargain discount
                $order->bargain_discount_applied = $bargainDiscount;
                $order->discount_total = $otherDiscount + $bargainDiscount;
                
                // ✅ Calculate total from the ANCHOR, not current values
                $order->total = $anchor - $order->discount_total;
                $order->save();
                $order->refresh();

                // \Log::info('[POS] Bargain discount applied', [
                //     'order_id' => $order->id,
                //     'bargain_discount' => $bargainDiscount,
                //     'anchor_total' => $anchor,
                //     'other_discount' => $otherDiscount,
                //     'new_total' => $order->total,
                //     'discount_total' => $order->discount_total,
                // ]);
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
                    $payment['payment_method_id'],
                    $tenantId
                );

                if (!$paymentMethod) {
                    return response()->json([
                        'success' => false,
                        'message' => __('pagination.payment_method_not_found'),
                    ]);
                }

                $validation = $paymentMethod->validateTransaction($payment['amount']);
                if (!$validation['success']) {
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
                        'amount_tendered' => $payment['tendered'] ?? $payment['amount'],
                        'change_due' => $payment['change'] ?? 0,
                        'transaction_id' => $payment['transaction_reference'] ?? null,
                    ]
                );

                OrderPayment::create([
                    'order_id' => $order->id,
                    'amount' => $payment['amount'],
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_id' => $payment['transaction_reference'] ?? (string) Str::uuid(),
                    'status' => 'completed',
                    'notes' => __('pagination.payment_completed'),
                    'processed_at' => now(),
                    'processed_by' => $user->id,
                ]);

                $paymentMethod->current_balance += $payment['amount'];
                $paymentMethod->save();

                $processedPayments[] = [
                    'type' => $payment['type'],
                    'method_name' => $paymentMethod->name,
                    'account_number' => $paymentMethod->account_number ?? null,
                    'amount' => (float) $payment['amount'],
                    'tendered' => (float) ($payment['tendered'] ?? $payment['amount']),
                    'change' => (float) ($payment['change'] ?? 0),
                    'transaction_reference' => $payment['transaction_reference'] ?? null,
                ];
            }

            // ── Record bargain discount (for reporting only) ───────────────
            if ($order->bargain_discount_applied > 0) {
                $primaryMethod = PaymentMethod::findForTenant($payments[0]['payment_method_id'], $tenantId);
                if ($primaryMethod) {
                    $this->recordBargainDiscount($order, $primaryMethod);
                }
            }

            // ── Record promotion/discount (for reporting only) ─────────────
            $nonBargainDiscount = $order->discount_total - $order->bargain_discount_applied;

            if ($nonBargainDiscount > 0) {
                $order->load('orderItems');
                $primaryMethod = PaymentMethod::findForTenant($payments[0]['payment_method_id'], $tenantId);
                if ($primaryMethod) {
                    $this->recordOrderPromotionLoss($order, $primaryMethod, $nonBargainDiscount);
                }
            }

            // ── Tax record ─────────────────────────────────────────────────
            $taxAmount = $order->tax_total ?: 0;
            if ($taxAmount > 0) {
                $now = now();

                OrderTax::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'tax_name' => 'VAT',
                        'tax_rate' => $order->subtotal > 0
                            ? round(($taxAmount / $order->subtotal) * 100, 2)
                            : 0,
                        'tax_amount' => $taxAmount,
                        'is_compound' => 1,
                        'created_by' => $user->id,
                        'tenant_id' => $tenantId,
                        'status' => 'pending',
                        'tax_year' => $now->year,
                        'tax_month' => $now->month,
                        'tax_quarter' => (int) ceil($now->month / 3),
                        'due_date' => $now->copy()->addMonthNoOverflow()->startOfMonth()->addDays(14),
                    ]
                );
            }

            // ── Inventory ──────────────────────────────────────────────────
            $order->load('orderItems');
            foreach ($order->orderItems as $item) {
                $variant = ProductVariant::find($item->variant_id);
                if (!$variant) continue;
                if ($isSingleShop) {
                    $this->handleSingleShopInventory($variant, $item, $order);
                } else {
                    $this->handleMultiShopInventory($variant, $item, $order);
                }
            }

            // ── Complete the order ─────────────────────────────────────────
            $order->update([
                'paid_amount' => $totalPaid,
                'balance_due' => 0,
                'status' => 'completed',
                'payment_method_id' => null,
            ]);

            // ── Build receipt ──────────────────────────────────────────────
            $customerName = $order->customer_name;
            $customer = null;
            if (!$customerName && $order->customer_id) {
                $customer = Customer::find($order->customer_id);
                $customerName = $customer
                    ? trim($customer->first_name . ' ' . $customer->last_name)
                    : null;
            }

            $order->load('orderItems');

            return response()->json([
                'success' => true,
                'message' => __('pagination.payment_completed'),
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'ref' => $order->order_number,
                    'customer_name' => $customerName ?? __('pagination.walk_in_customer'),
                    'customer' => [
                        'name' => $customerName ?? __('pagination.walk_in_customer'),
                        'phone' => $customer?->phone ?? null,
                        'email' => $customer?->email ?? null,
                    ],
                    'date' => $order->created_at->format('Y-m-d'),
                    'time' => $order->created_at->format('H:i:s'),
                    'subtotal' => (float) $order->subtotal,
                    'discount' => (float) $order->discount_total,
                    'tax' => (float) $order->tax_total,
                    'total' => (float) $order->total,
                    'total_paid' => (float) $totalPaid,
                    'total_tendered' => (float) array_sum(array_column($processedPayments, 'tendered')),
                    'total_change' => (float) array_sum(array_column($processedPayments, 'change')),
                    'items' => $order->orderItems->map(fn($item) => [
                        'name' => $item->item_name,
                        'quantity' => (int) $item->quantity,
                        'price' => (float) $item->unit_price,
                        'total' => (float) $item->total_price,
                        'note' => $item->notes ?? null,
                    ])->toArray(),
                    'payments' => $processedPayments,
                    'order_type' => $order->type ?? 'sale',
                    'cashier' => $user->name,
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
                'trace' => $e->getTraceAsString(),
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
     * Record promotion/discount for reporting purposes only.
     * ✅ Uses ADJUSTMENT (already exists in ENUM)
     */
    private function recordOrderPromotionLoss($order, $paymentMethod, $discountAmount = 0): void
    {
        if ($discountAmount <= 0) {
            return;
        }

        try {
            $itemPromotions = [];

            foreach ($order->orderItems as $item) {
                $promotions = is_string($item->promotion_data)
                    ? json_decode($item->promotion_data, true)
                    : ($item->promotion_data ?? []);

                if (!empty($promotions)) {
                    $itemPromotions[] = [
                        'item_name' => $item->item_name,
                        'variant_id' => $item->variant_id,
                        'sku' => $item->sku,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'item_discount' => $item->discount ?? 0,
                        'promotions' => $promotions,
                    ];
                }
            }

            $customerName = $order->customer_name;
            if (!$customerName && $order->customer_id) {
                $customer = \App\Models\Customer::find($order->customer_id);
                $customerName = $customer
                    ? trim($customer->first_name . ' ' . $customer->last_name)
                    : null;
            }

            // ✅ Set balance_before and balance_after to SAME value
            $currentBalance = $paymentMethod->current_balance;

            $transactionData = [
                'tenant_id' => $order->tenant_id,
                'user_id' => auth()->id(),
                'payment_method_id' => $paymentMethod->id,
                'transaction_type' => 'ADJUSTMENT',
                'transaction_category' => 'ADJUSTMENT',
                'amount' => $discountAmount,
                'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                'reference_table' => 'orders',
                'reference_id' => $order->id,
                'description' => 'Promotion/Discount - Order #' . $order->order_number,
                'notes' => 'Revenue reduction from applied promotions and item discounts',
                // ✅ CRITICAL: Same balance before and after = NO EFFECT
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance, // No change!
                'metadata' => [
                    'order_number' => $order->order_number,
                    'customer_id' => $order->customer_id,
                    'customer_name' => $customerName ?? __('pagination.walk_in_customer'),
                    'subtotal_before' => $order->subtotal,
                    'discount_type' => 'PROMOTION',
                    'discount_amount' => $discountAmount,
                    'final_total' => $order->total,
                    'transaction_nature' => 'PROMOTION_DISCOUNT_RECORD',
                    'processed_by_id' => auth()->id(),
                    'processed_by_name' => auth()->user()->name,
                    'items_with_promotions' => $itemPromotions,
                    'is_discount' => true,
                    'discount_category' => 'promotion',
                    'balance_effect' => 'none',  // ✅ Explicitly mark no balance effect
                ],
            ];

            app('payment-transaction')->recordTransaction($transactionData);

            \Log::info('[POS] Promotion discount recorded (no balance effect)', [
                'order_id' => $order->id,
                'discount_amount' => $discountAmount,
                'payment_method_balance' => $currentBalance,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to record promotion discount: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            throw $e;
        }
    }

    
    /**
     * Record bargain discount for reporting purposes only.
     * ✅ Uses ADJUSTMENT (already exists in ENUM)
     */
    private function recordBargainDiscount($order, $paymentMethod): void
    {
        if ($order->bargain_discount_applied <= 0) {
            return;
        }

        try {
            $customerName = $order->customer_name;
            if (!$customerName && $order->customer_id) {
                $customer = \App\Models\Customer::find($order->customer_id);
                $customerName = $customer
                    ? trim($customer->first_name . ' ' . $customer->last_name)
                    : null;
            }

            // ✅ Set balance_before and balance_after to SAME value
            // This ensures NO balance effect
            $currentBalance = $paymentMethod->current_balance;

            $transactionData = [
                'tenant_id' => $order->tenant_id,
                'user_id' => auth()->id(),
                'payment_method_id' => $paymentMethod->id,
                'transaction_type' => 'ADJUSTMENT',  
                'transaction_category' => 'ADJUSTMENT',
                'amount' => $order->bargain_discount_applied,
                'currency_id' => $paymentMethod->currency_id ?? \App\Models\Currency::default()->id,
                'reference_table' => 'orders',
                'reference_id' => $order->id,
                'description' => 'Bargain Discount - Order #' . $order->order_number,
                'notes' => 'Negotiated discount given to customer',
                // ✅ CRITICAL: Same balance before and after = NO EFFECT
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance, // No change!
                'metadata' => [
                    'order_number' => $order->order_number,
                    'customer_id' => $order->customer_id,
                    'customer_name' => $customerName ?? __('pagination.walk_in_customer'),
                    'subtotal_before_discount' => $order->subtotal_before_bargain,
                    'discount_type' => 'BARGAIN',
                    'discount_amount' => $order->bargain_discount_applied,
                    'final_total' => $order->total,
                    'transaction_nature' => 'BARGAIN_DISCOUNT_RECORD',
                    'processed_by_id' => auth()->id(),
                    'processed_by_name' => auth()->user()->name,
                    'is_discount' => true,
                    'discount_category' => 'bargain',
                    'balance_effect' => 'none',  // ✅ Explicitly mark no balance effect
                ],
            ];

            app('payment-transaction')->recordTransaction($transactionData);

            \Log::info('[POS] Bargain discount recorded (no balance effect)', [
                'order_id' => $order->id,
                'discount_amount' => $order->bargain_discount_applied,
                'payment_method_balance' => $currentBalance,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to record bargain discount: ' . $e->getMessage(), [
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
        if (!$user) {
            $user = Auth::user();
        }

        // ✅ Get inventory_id from inventory_data JSON
        $inventoryData = json_decode($item->inventory_data, true);
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? 1;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? 1;

        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        } else {
            // Fallback: query inventory
            $inventory = $variant->inventory()
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            \Log::warning("No inventory found for variant {$variant->id} in multi-shop mode", [
                'variant_id' => $variant->id,
                'location_id' => $locationId,
                'department_id' => $departmentId,
                'order_id' => $order->id
            ]);
            return;
        }

        $beforeQty = $inventory->quantity_allocated;
        $afterQty = $beforeQty - $item->quantity;

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
            'quantity' => -$item->quantity,
            'reference_id' => $order->id,
            'reference_type' => 'order',
            'type' => 'sale',
            'notes' => 'Sold ' . $item->quantity . ' units of ' . $variant->sku,
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $order->tenant_id,
        ]);

        \Log::info('Multi-shop inventory updated', [
            'variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'location_id' => $locationId,
            'department_id' => $departmentId,
            'quantity_sold' => $item->quantity,
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

