<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB, Log };
use App\Models\{ Product, SingleShopInventoryLog, InventoryItems, ProductVariant, Order, InventoryTransactions, 
    Invoice, BatchLog, SerialNumber };
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; 
use App\Models\{ OrderItem, OrderPayment, InventoryLog, Customer, Inventory, OrderTax, InventoryAdjustments,
                    PaymentMethod, Currency, PurchaseReceiptItem };


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
        
        $selectedDepartmentId = $request->input('department', '');
        $products = collect();
        
        if ($isSingleShop) {
            // ─── SINGLE SHOP ────────────────────────────────────────────────
            $products = Product::with([
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
                'variants.batches' => function($q) {
                    $q->where('quantity_remaining', '>', 0)
                        ->orWhereNull('quantity_remaining')
                        ->orderBy('expiry_date', 'asc');
                },
                // ✅ Load serial numbers - use the correct relationship path
                'variants.serialNumbers' => function($q) {
                    $q->where(function($sub) {
                        $sub->where('status', SerialNumber::STATUS_AVAILABLE)
                            ->orWhere('status', SerialNumber::STATUS_RESERVED);
                    });
                },
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->whereHas('variants')
            ->limit(10)
            ->latest()
            ->get();

            foreach ($products as $product) {
                foreach ($product->variants as $variant) {
                    // Default quantity for non-batch
                    $variant->quantity_available = $variant->overal_quantity_at_hand ?? 0;
                    $variant->quantity_source = 'overall';
                    $variant->inventory_by_dept = [];
                    
                    // ✅ For batch products
                    if ($product->inventory_strategy === 'batch') {
                        $batches = $variant->batches;
                        $totalAvailable = $batches->sum(function($batch) {
                            return $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                        });
                        $variant->available_batches = $batches->map(function($batch) {
                            return [
                                'id' => $batch->id,
                                'batch_number' => $batch->batch_number,
                                'quantity_remaining' => $batch->quantity_remaining ?? $batch->quantity_received,
                                'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : null,
                                'location_id' => $batch->location_id,
                                'department_id' => $batch->department_id,
                            ];
                        });
                        $variant->quantity_available = $totalAvailable;
                    } 
                    // ✅ For serial products
                    elseif ($product->inventory_strategy === 'serial') {
                        // ✅ Get serial numbers from the loaded relationship
                        $serialNumbers = $variant->serialNumbers;
                        
                        $variant->available_serials = $serialNumbers->map(function($serial) {
                            return [
                                'id' => $serial->id,
                                'serial_number' => $serial->serial_number,
                                'status' => $serial->status,
                                'location_id' => $serial->location_id,
                                'location_name' => $serial->location ? $serial->location->name : 'N/A',
                                'department_id' => $serial->department_id,
                                'department_name' => $serial->department ? $serial->department->name : 'N/A',
                            ];
                        });
                        $variant->quantity_available = $variant->available_serials->count();
                    } else {
                        $variant->available_batches = [];
                        $variant->available_serials = [];
                    }
                }
            }
            
        } else {
            // ─── MULTI-SHOP ──────────────────────────────────────────────────
            if (empty($selectedDepartmentId)) {
                $products = collect();
                $user_departments = $user->departments()->get();
                return view('orders.pos-index', compact('products', 'user_departments', 'isSingleShop', 'selectedDepartmentId'));
            }
            
            $userLocationId = $user->location_id;
            
            $products = Product::with([
                'departments',
                'taxes' => fn($q) => $q->where('is_active', 1),
                'promotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
                'variants' => function($query) use ($selectedDepartmentId, $userLocationId) {
                    $query->where('is_active', 1)
                        ->whereHas('inventory', function($q) use ($selectedDepartmentId, $userLocationId) {
                            $q->where('department_id', $selectedDepartmentId)
                            ->where('location_id', $userLocationId);
                        })
                        ->with(['inventory' => function($q) use ($selectedDepartmentId, $userLocationId) {
                            $q->where('department_id', $selectedDepartmentId)
                            ->where('location_id', $userLocationId);
                        }])
                        ->with(['batches' => function($q) use ($selectedDepartmentId, $userLocationId) {
                            $q->where(function($sub) {
                                $sub->where('quantity_remaining', '>', 0)
                                    ->orWhereNull('quantity_remaining');
                            })
                            ->where('location_id', $userLocationId)
                            ->where('department_id', $selectedDepartmentId)
                            ->orderBy('expiry_date', 'asc');
                        }])
                        // ✅ Load serial numbers for this location/department
                        ->with(['serialNumbers' => function($q) use ($selectedDepartmentId, $userLocationId) {
                            $q->where(function($sub) {
                                $sub->where('status', SerialNumber::STATUS_AVAILABLE)
                                    ->orWhere('status', SerialNumber::STATUS_RESERVED);
                            })
                            ->where('location_id', $userLocationId)
                            ->where('department_id', $selectedDepartmentId);
                        }]);
                },
                'variants.variantTaxes' => fn($q) => $q->where('is_active', 1),
                'variants.variantPromotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->whereHas('departments', function($q) use ($selectedDepartmentId) {
                $q->where('department_id', $selectedDepartmentId);
            })
            ->whereHas('variants', function($q) use ($selectedDepartmentId, $userLocationId) {
                $q->where('is_active', 1)
                    ->whereHas('inventory', function($query) use ($selectedDepartmentId, $userLocationId) {
                        $query->where('department_id', $selectedDepartmentId)
                            ->where('location_id', $userLocationId);
                    });
            })
            ->limit(10)
            ->latest()
            ->get();

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
                    $variant->quantity_source = 'inventory_allocated';
                    
                    // ✅ For batch products
                    if ($product->inventory_strategy === 'batch') {
                        $batches = $variant->batches;
                        $totalAvailable = $batches->sum(function($batch) {
                            return $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                        });
                        $variant->available_batches = $batches->map(function($batch) {
                            return [
                                'id' => $batch->id,
                                'batch_number' => $batch->batch_number,
                                'quantity_remaining' => $batch->quantity_remaining ?? $batch->quantity_received,
                                'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : null,
                                'location_id' => $batch->location_id,
                                'department_id' => $batch->department_id,
                            ];
                        });
                        $variant->quantity_available = $totalAvailable;
                        $inventoryByDept[$selectedDepartmentId]['quantity'] = $totalAvailable;
                        $variant->inventory_by_dept = $inventoryByDept;
                    }
                    // ✅ For serial products
                    elseif ($product->inventory_strategy === 'serial') {
                        $serialNumbers = $variant->serialNumbers;
                        
                        $variant->available_serials = $serialNumbers->map(function($serial) {
                            return [
                                'id' => $serial->id,
                                'serial_number' => $serial->serial_number,
                                'status' => $serial->status,
                                'location_id' => $serial->location_id,
                                'location_name' => $serial->location ? $serial->location->name : 'N/A',
                                'department_id' => $serial->department_id,
                                'department_name' => $serial->department ? $serial->department->name : 'N/A',
                            ];
                        });
                        $variant->quantity_available = $variant->available_serials->count();
                        $inventoryByDept[$selectedDepartmentId]['quantity'] = $variant->quantity_available;
                        $variant->inventory_by_dept = $inventoryByDept;
                    } else {
                        $variant->available_batches = [];
                        $variant->available_serials = [];
                        $variant->quantity_available = $inventoryByDept[$selectedDepartmentId]['quantity'] ?? 0;
                    }
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

                $variant->price = $variant->selling_price;
                $variant->grant_total_cost_price = $variant->grand_total_cost_price;
            }
        }

        $user_departments = $user->departments()->get();

        return view('orders.pos-index', compact('products', 'user_departments', 'isSingleShop', 'selectedDepartmentId'));
    }


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

        if (empty($searchTerm)) {
            return response()->json([
                'success' => true,
                'products' => [],
                'has_more' => false,
            ]);
        }

        if ($isSingleShop) {
            // ─── SINGLE SHOP SEARCH ──────────────────────────────────────────
            $products = Product::with([
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
                'variants.batches' => function($q) {
                    $q->where('quantity_remaining', '>', 0)
                        ->orWhereNull('quantity_remaining')
                        ->orderBy('expiry_date', 'asc');
                },
                // ✅ Load serial numbers for single shop search
                'variants.serialNumbers' => function($q) {
                    $q->where(function($sub) {
                        $sub->where('status', SerialNumber::STATUS_AVAILABLE)
                            ->orWhere('status', SerialNumber::STATUS_RESERVED);
                    });
                },
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('sku', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('variants', function($vq) use ($searchTerm) {
                    $vq->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('sku', 'LIKE', "%{$searchTerm}%");
                });
            })
            ->limit(10)
            ->get();

            // Prepare variant data with batches and serials
            foreach ($products as $product) {
                foreach ($product->variants as $variant) {
                    $variant->quantity_available = $variant->overal_quantity_at_hand ?? 0;
                    $variant->quantity_source = 'overall';
                    $variant->inventory_by_dept = [];
                    
                    if ($product->inventory_strategy === 'batch') {
                        $batches = $variant->batches;
                        $totalAvailable = $batches->sum(function($batch) {
                            return $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                        });
                        $variant->available_batches = $batches->map(function($batch) {
                            return [
                                'id' => $batch->id,
                                'batch_number' => $batch->batch_number,
                                'quantity_remaining' => $batch->quantity_remaining ?? $batch->quantity_received,
                                'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : null,
                                'location_id' => $batch->location_id,
                                'department_id' => $batch->department_id,
                            ];
                        });
                        $variant->quantity_available = $totalAvailable;
                    } elseif ($product->inventory_strategy === 'serial') {
                        $serialNumbers = $variant->serialNumbers;
                        
                        $variant->available_serials = $serialNumbers->map(function($serial) {
                            return [
                                'id' => $serial->id,
                                'serial_number' => $serial->serial_number,
                                'status' => $serial->status,
                                'location_id' => $serial->location_id,
                                'location_name' => $serial->location ? $serial->location->name : 'N/A',
                                'department_id' => $serial->department_id,
                                'department_name' => $serial->department ? $serial->department->name : 'N/A',
                            ];
                        });
                        $variant->quantity_available = $variant->available_serials->count();
                    } else {
                        $variant->available_batches = [];
                        $variant->available_serials = [];
                    }
                }
            }
            
        } else {
            // ─── MULTI-SHOP SEARCH ────────────────────────────────────────────
            if (empty($departmentId)) {
                return response()->json([
                    'success' => true,
                    'products' => [],
                    'has_more' => false,
                    'message' => 'Please select a department first'
                ]);
            }

            $userLocationId = $user->location_id;

            $products = Product::with([
                'departments',
                'taxes' => fn($q) => $q->where('is_active', 1),
                'promotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
                'variants' => function($query) use ($departmentId, $userLocationId) {
                    $query->where('is_active', 1)
                        ->whereHas('inventory', function($q) use ($departmentId, $userLocationId) {
                            $q->where('department_id', $departmentId)
                            ->where('location_id', $userLocationId);
                        })
                        ->with(['inventory' => function($q) use ($departmentId, $userLocationId) {
                            $q->where('department_id', $departmentId)
                            ->where('location_id', $userLocationId);
                        }])
                        ->with(['batches' => function($q) use ($departmentId, $userLocationId) {
                            $q->where(function($sub) {
                                $sub->where('quantity_remaining', '>', 0)
                                    ->orWhereNull('quantity_remaining');
                            })
                            ->where('location_id', $userLocationId)
                            ->where('department_id', $departmentId)
                            ->orderBy('expiry_date', 'asc');
                        }])
                        // ✅ Load serial numbers for multi-shop search
                        ->with(['serialNumbers' => function($q) use ($departmentId, $userLocationId) {
                            $q->where(function($sub) {
                                $sub->where('status', SerialNumber::STATUS_AVAILABLE)
                                    ->orWhere('status', SerialNumber::STATUS_RESERVED);
                            })
                            ->where('location_id', $userLocationId)
                            ->where('department_id', $departmentId);
                        }]);
                },
                'variants.variantTaxes' => fn($q) => $q->where('is_active', 1),
                'variants.variantPromotions' => fn($q) => $q->where('is_active', 1)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->whereHas('departments', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->whereHas('variants', function($q) use ($departmentId, $userLocationId) {
                $q->where('is_active', 1)
                    ->whereHas('inventory', function($query) use ($departmentId, $userLocationId) {
                        $query->where('department_id', $departmentId)
                            ->where('location_id', $userLocationId);
                    });
            })
            ->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('sku', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('variants', function($vq) use ($searchTerm) {
                    $vq->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('sku', 'LIKE', "%{$searchTerm}%");
                });
            })
            ->limit(10)
            ->get();

            // Calculate quantities per department and prepare batch/serial data
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
                    
                    if ($product->inventory_strategy === 'batch') {
                        $batches = $variant->batches;
                        $totalAvailable = $batches->sum(function($batch) {
                            return $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                        });
                        $variant->available_batches = $batches->map(function($batch) {
                            return [
                                'id' => $batch->id,
                                'batch_number' => $batch->batch_number,
                                'quantity_remaining' => $batch->quantity_remaining ?? $batch->quantity_received,
                                'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : null,
                                'location_id' => $batch->location_id,
                                'department_id' => $batch->department_id,
                            ];
                        });
                        $variant->quantity_available = $totalAvailable;
                        $inventoryByDept[$departmentId]['quantity'] = $totalAvailable;
                        $variant->inventory_by_dept = $inventoryByDept;
                    } elseif ($product->inventory_strategy === 'serial') {
                        $serialNumbers = $variant->serialNumbers;
                        
                        $variant->available_serials = $serialNumbers->map(function($serial) {
                            return [
                                'id' => $serial->id,
                                'serial_number' => $serial->serial_number,
                                'status' => $serial->status,
                                'location_id' => $serial->location_id,
                                'location_name' => $serial->location ? $serial->location->name : 'N/A',
                                'department_id' => $serial->department_id,
                                'department_name' => $serial->department ? $serial->department->name : 'N/A',
                            ];
                        });
                        $variant->quantity_available = $variant->available_serials->count();
                        $inventoryByDept[$departmentId]['quantity'] = $variant->quantity_available;
                        $variant->inventory_by_dept = $inventoryByDept;
                    } else {
                        $variant->available_batches = [];
                        $variant->available_serials = [];
                        $variant->quantity_available = $inventoryByDept[$departmentId]['quantity'] ?? 0;
                    }
                }
            }
        }

        // Compute taxes and promotions for each variant
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

                $variant->price = $variant->selling_price;
                $variant->grant_total_cost_price = $variant->grand_total_cost_price;
            }
        }

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
                    'batch_id' => $item['batch_id'] ?? null,      
                    'batch_number' => $item['batch_number'] ?? null,
                    'serial_id' => $item['serial_id'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null, 
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
                    'batch_id' => $item['batch_id'] ?? null,       
                    'batch_number' => $item['batch_number'] ?? null,
                    'serial_id' => $item['serial_id'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null,
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
                        'batch_id' => $item['batch_id'] ?? null,       
                        'batch_number' => $item['batch_number'] ?? null,
                        'serial_id' => $item['serial_id'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
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

                // \Log::info('[POS] Order totals updated after cart change', [
                //     'order_id' => $order->id,
                //     'new_total' => $newTotal,
                // ]);
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

            // First pass: Validate all items (check stock before depleting)
            foreach ($order->orderItems as $item) {
                $variant = ProductVariant::find($item->variant_id);
                if (!$variant) continue;
                
                $product = $variant->product;
                if (!$product) continue;
                
                $strategy = $product->resolvedInventoryStrategy();
                
                // For recipes, validate ingredients first
                if ($strategy === 'recipe') {
                    $this->validateRecipeIngredients($product, $item->quantity);
                } else {
                    // For non-recipe, check stock
                    $this->checkIngredientStock($variant, $item->quantity, $order->tenant_id);
                }
            }

            // Second pass: Actually deplete inventory
            foreach ($order->orderItems as $item) {
                $variant = ProductVariant::find($item->variant_id);
                if (!$variant) continue;
                
                $product = $variant->product;
                if (!$product) continue;
                
                $strategy = $product->resolvedInventoryStrategy();
                
                if ($strategy === 'recipe') {
                    $this->depleteRecipeIngredients($variant, $item, $order);  // ← $item is OrderItem model
                } else {
                    $this->depleteInventoryByStrategy($variant, $item, $order);
                }
            }

            // ── First pass: Validate all items (check stock before depleting) ──
            // \Log::info('Validating order items', [
            //     'order_id' => $order->id,
            //     'items_count' => $order->orderItems->count()
            // ]);

            foreach ($order->orderItems as $item) {
                $variant = ProductVariant::find($item->variant_id);
                if (!$variant) {
                    \Log::warning('Variant not found', ['variant_id' => $item->variant_id]);
                    continue;
                }
                
                $product = $variant->product;
                if (!$product) {
                    \Log::warning('Product not found for variant', ['variant_id' => $variant->id]);
                    continue;
                }
                
                $strategy = $product->resolvedInventoryStrategy();
                
                // \Log::info('Validating item', [
                //     'item_name' => $item->item_name,
                //     'variant_id' => $variant->id,
                //     'strategy' => $strategy,
                //     'quantity' => $item->quantity,
                //     'batch_id' => $item->batch_id ?? null,  // ✅ Check if batch_id exists
                //     'inventory_data' => $item->inventory_data
                // ]);
                
                // For recipes, validate ingredients first
                if ($strategy === 'recipe') {
                    $this->validateRecipeIngredients($product, $item->quantity);
                } else {
                    // For non-recipe, check stock
                    $this->checkIngredientStock($variant, $item->quantity, $order->tenant_id);
                }
            }

            //Log it here

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

            // \Log::info('[POS] Promotion discount recorded (no balance effect)', [
            //     'order_id' => $order->id,
            //     'discount_amount' => $discountAmount,
            //     'payment_method_balance' => $currentBalance,
            // ]);
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

            // \Log::info('[POS] Bargain discount recorded (no balance effect)', [
            //     'order_id' => $order->id,
            //     'discount_amount' => $order->bargain_discount_applied,
            //     'payment_method_balance' => $currentBalance,
            // ]);
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
     * Deplete recipe ingredients
     * Each ingredient is depleted using its own strategy AND shop mode
     */
    private function depleteRecipeIngredients($variant, $item, $order)
    {
        $product = $variant->product;
        
        if (!$product->hasRecipe()) {
            throw new \Exception("No recipe found for {$product->name}");
        }

        $recipe = $product->recipe;
        $ingredients = $recipe->ingredients;
        $quantityMultiplier = $item->quantity;

        if ($ingredients->isEmpty()) {
            throw new \Exception("Recipe for {$product->name} has no ingredients");
        }

        foreach ($ingredients as $ingredient) {
            $ingredientVariant = $ingredient->ingredientVariant;
            $requiredQuantity = $ingredient->quantity_required * $quantityMultiplier;

            if (!$ingredientVariant) {
                throw new \Exception("Ingredient variant not found for recipe '{$product->name}'");
            }

            $ingredientProduct = $ingredientVariant->product;
            if (!$ingredientProduct) {
                throw new \Exception("Product not found for ingredient '{$ingredientVariant->name}'");
            }

            $strategy = $ingredientProduct->resolvedInventoryStrategy();
            
            if ($strategy === 'recipe') {
                throw new \Exception("Nested recipes are not allowed. '{$ingredientVariant->name}' is also a recipe product.");
            }

            // ✅ Check stock BEFORE depleting
            $this->checkIngredientStock($ingredientVariant, $requiredQuantity, $order->tenant_id);

            // ✅ Deplete using the correct shop mode
            $ingredientItem = (object) [
                'variant_id' => $ingredientVariant->id,
                'quantity' => $requiredQuantity,
                'name' => $ingredientVariant->name,
                'price' => 0,
                'tax_total' => 0,
                'discount' => 0,
                'total' => 0,
            ];

            $this->depleteInventoryByStrategy($ingredientVariant, $ingredientItem, $order);
        }

        // Update recipe product quantity (virtual, for reference only)
        $variant->overal_quantity_at_hand = max(0, ($variant->overal_quantity_at_hand ?? 0) - $item->quantity);
        $variant->save();

        // \Log::info('[Recipe] Depleted', [
        //     'product' => $product->name,
        //     'quantity' => $item->quantity,
        //     'order_id' => $order->id
        // ]);
    }

    /**
     * Deplete inventory based on product's strategy
     * THIS IS THE ONLY ENTRY POINT - everything else calls this
     */
    private function depleteInventoryByStrategy($variant, $item, $order)
    {
        $product = $variant->product;
        if (!$product) {
            throw new \Exception("Product not found for variant '{$variant->name}'");
        }
        
        $strategy = $product->resolvedInventoryStrategy();
        $isSingleShop = tenant_is_single_shop($order->tenant_id);
        
        // ✅ Prevent nested recipes
        if ($strategy === 'recipe') {
            throw new \Exception("Nested recipes are not allowed. '{$variant->name}' is a recipe product.");
        }
        
        // \Log::info('[Inventory Depletion]', [
        //     'variant' => $variant->name,
        //     'strategy' => $strategy,
        //     'shop_mode' => $isSingleShop ? 'single' : 'multi',
        //     'quantity' => $item->quantity,
        //     'order_id' => $order->id
        // ]);

        // Route to correct depletion method based on shop mode AND strategy
        if ($isSingleShop) {
            // SINGLE SHOP - use overall_quantity_at_hand
            match ($strategy) {
                'quantity' => $this->depleteSingleQuantity($variant, $item, $order),
                'batch'    => $this->depleteSingleBatch($variant, $item, $order),
                'serial'   => $this->depleteSingleSerial($variant, $item, $order),
                default    => throw new \Exception("Unknown strategy '{$strategy}' for single shop"),
            };
        } else {
            // MULTI SHOP - use inventory_items table
            match ($strategy) {
                'quantity' => $this->depleteMultiQuantity($variant, $item, $order),
                'batch'    => $this->depleteMultiBatch($variant, $item, $order),
                'serial'   => $this->depleteMultiSerial($variant, $item, $order),
                default    => throw new \Exception("Unknown strategy '{$strategy}' for multi shop"),
            };
        }
    }

    // ============================================================
    // SINGLE SHOP DEPLETION METHODS
    // ============================================================

    /**
     * Single Shop - Quantity strategy
     * Depletes from: overal_quantity_at_hand
     */
    private function depleteSingleQuantity($variant, $item, $order)
    {
        $before = $variant->overal_quantity_at_hand;
        $after = $before - $item->quantity;

        if ($after < 0) {
            throw new \Exception("Insufficient stock for {$variant->name}. Available: {$before}, Required: {$item->quantity}");
        }

        $variant->update(['overal_quantity_at_hand' => $after]);

        // Log
        SingleShopInventoryLog::create([
            'variant_id' => $variant->id,
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'created_by' => auth()->id(),
            'quantity_before' => $before,
            'quantity_after' => $after,
            'quantity_change' => -$item->quantity,
            'reason' => 'pos_sale',
            'notes' => "POS sale - Order #{$order->order_number}",
            'source' => 'pos',
            'metadata' => ['strategy' => 'quantity']
        ]);

        // \Log::info('[Single Shop] Quantity depleted', [
        //     'variant' => $variant->name,
        //     'before' => $before,
        //     'after' => $after
        // ]);
    }

    /**
     * Single Shop - Batch strategy
     * Depletes from the specific batch ID
     */
    private function depleteSingleBatch($variant, $item, $order)
    {
        $tenantId = $order->tenant_id;
        $quantityNeeded = $item->quantity;
        
        // ✅ Get batch_id from the order item
        $batchId = $item->batch_id ?? null;
        
        // \Log::info('Depleting Single Batch', [
        //     'variant_id' => $variant->id,
        //     'variant_name' => $variant->name,
        //     'batch_id' => $batchId,
        //     'batch_number' => $item->batch_number ?? null,
        //     'quantity_needed' => $quantityNeeded,
        //     'order_id' => $order->id
        // ]);
        
        if ($batchId) {
            // ✅ Deduct from specific batch
            $batch = PurchaseReceiptItem::query()
                ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                ->where('purchase_orders.tenant_id', $tenantId)
                ->where('purchase_receipt_items.id', $batchId)
                ->where(function($q) {
                    $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                    ->orWhereNull('purchase_receipt_items.quantity_remaining');
                })
                ->select('purchase_receipt_items.*')
                ->first();
                
            if (!$batch) {
                throw new \Exception("Batch not found or has no remaining quantity");
            }
            
            $effectiveQuantity = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
            if ($effectiveQuantity < $quantityNeeded) {
                throw new \Exception("Insufficient quantity in batch {$batch->batch_number}. Available: {$effectiveQuantity}, Required: {$quantityNeeded}");
            }
            
            // ✅ Update quantity_remaining
            if ($batch->quantity_remaining !== null) {
                $batch->quantity_remaining -= $quantityNeeded;
            } else {
                $batch->quantity_remaining = ($batch->quantity_received ?? 0) - $quantityNeeded;
            }
            $batch->save();
            
            // Log batch depletion
            $this->logBatchDepletion($batch, $variant, $item, $order, $quantityNeeded);
            
            // Update overall quantity for reporting only
            $variant->overal_quantity_at_hand = max(0, ($variant->overal_quantity_at_hand ?? 0) - $quantityNeeded);
            $variant->save();
            
            return;
        }
        
        // ✅ If no batch_id, fallback to FIFO
        // ... FIFO logic ...
    }



    // ============================================================
    // MULTI SHOP DEPLETION METHODS
    // ============================================================

    /**
     * Multi Shop - Quantity strategy
     * Depletes from: inventory_items table (location_id + department_id)
     */
    private function depleteMultiQuantity($variant, $item, $order)
    {
        $user = Auth::user();
        $tenantId = $order->tenant_id;

        // ✅ Get inventory data - handles both OrderItem and stdClass
        $inventoryData = [];
        if (is_object($item) && property_exists($item, 'inventory_data')) {
            $inventoryData = json_decode($item->inventory_data, true);
        } else if (is_array($item) && isset($item['inventory_data'])) {
            $inventoryData = json_decode($item['inventory_data'], true);
        }
        
        // If no inventory_data, try to use what's available
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? null;
        
        // Get quantity from item
        $quantity = is_object($item) ? ($item->quantity ?? 0) : ($item['quantity'] ?? 0);

        // Find the inventory record
        $inventory = null;
        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        }

        if (!$inventory && $locationId && $departmentId) {
            $inventory = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            throw new \Exception("No inventory found for {$variant->name} at location {$locationId} / department {$departmentId}");
        }

        $before = $inventory->quantity_allocated;
        $after = $before - $quantity;

        if ($after < 0) {
            throw new \Exception("Insufficient stock for {$variant->name}. Available: {$before}, Required: {$quantity}");
        }

        // Update inventory
        $inventory->update(['quantity_allocated' => $after]);

        // Log transaction
        InventoryTransactions::create([
            'quantity' => -$quantity,
            'reference_id' => $order->id,
            'reference_type' => 'order',
            'type' => 'sale',
            'notes' => "POS sale - Order #{$order->order_number} - {$variant->name}",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $tenantId,
        ]);

        // Log adjustment
        InventoryAdjustments::create([
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reason' => 'order_sale',
            'notes' => "POS sale - Order #{$order->order_number} - {$variant->name}",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $tenantId,
        ]);

        // \Log::info('[Multi Shop] Quantity depleted', [
        //     'variant' => $variant->name,
        //     'inventory_id' => $inventory->id,
        //     'location_id' => $inventory->location_id,
        //     'department_id' => $inventory->department_id,
        //     'before' => $before,
        //     'after' => $after
        // ]);
    }

    /**
     * Multi Shop - Batch strategy
     * Depletes from: inventory_items AND batch items (filtered by location/department)
     */
    private function depleteMultiBatch($variant, $item, $order)
    {
        $user = Auth::user();
        $tenantId = $order->tenant_id;
        
        // ✅ Get quantity from item
        $quantityNeeded = $item->quantity;  // ✅ This is the actual quantity, not 0
        
        // ✅ Get batch_id from the order item
        $batchId = $item->batch_id ?? null;
        
        // \Log::info('depleteMultiBatch called', [
        //     'variant_id' => $variant->id,
        //     'variant_name' => $variant->name,
        //     'quantity_needed' => $quantityNeeded,
        //     'batch_id' => $batchId,
        //     'batch_number' => $item->batch_number ?? null,
        //     'order_id' => $order->id
        // ]);
        
        // ✅ Get inventory data
        $inventoryData = [];
        if (is_object($item) && property_exists($item, 'inventory_data')) {
            $inventoryData = json_decode($item->inventory_data, true);
        } else if (is_array($item) && isset($item['inventory_data'])) {
            $inventoryData = json_decode($item['inventory_data'], true);
        }
        
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? null;
        
        // Get item name
        $itemName = is_object($item) ? ($item->item_name ?? $item->name ?? $variant->name) : ($item['name'] ?? $variant->name);

        // Get inventory record
        $inventory = null;
        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        }

        if (!$inventory && $locationId && $departmentId) {
            $inventory = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            throw new \Exception("No inventory found for {$variant->name} at this location/department");
        }

        // ✅ Get batches for this variant
        $batches = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->where('purchase_orders.tenant_id', $tenantId)
            ->where('purchase_order_items.product_variant_id', $variant->id)
            ->where(function($q) {
                $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                ->orWhereNull('purchase_receipt_items.quantity_remaining');
            })
            ->when($locationId, function($q) use ($locationId) {
                return $q->where('purchase_receipt_items.location_id', $locationId);
            })
            ->when($departmentId, function($q) use ($departmentId) {
                return $q->where('purchase_receipt_items.department_id', $departmentId);
            })
            ->orderBy('purchase_receipt_items.expiry_date', 'asc')
            ->select('purchase_receipt_items.*')
            ->get();

        if ($batches->isEmpty()) {
            throw new \Exception("No available batches for {$variant->name} at this location/department");
        }

        $totalAvailable = $batches->sum(function($batch) {
            return $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
        });
        
        if ($totalAvailable < $quantityNeeded) {
            throw new \Exception("Insufficient batch stock for {$variant->name}. Available: {$totalAvailable}, Required: {$quantityNeeded}");
        }

        // ✅ Deduct from batches - if batch_id is provided, use that specific batch
        if ($batchId) {
            // Find the specific batch
            $targetBatch = $batches->firstWhere('id', $batchId);
            if (!$targetBatch) {
                throw new \Exception("Specified batch not found or not available");
            }
            
            $effectiveQuantity = $targetBatch->quantity_remaining ?? $targetBatch->quantity_received ?? 0;
            if ($effectiveQuantity < $quantityNeeded) {
                throw new \Exception("Insufficient quantity in batch {$targetBatch->batch_number}. Available: {$effectiveQuantity}, Required: {$quantityNeeded}");
            }
            
            // ✅ Update the specific batch
            if ($targetBatch->quantity_remaining !== null) {
                $targetBatch->quantity_remaining -= $quantityNeeded;
            } else {
                $targetBatch->quantity_remaining = ($targetBatch->quantity_received ?? 0) - $quantityNeeded;
            }
            $targetBatch->save();
            
            // Log batch depletion
            BatchLog::create([
                'batch_id' => $targetBatch->id,
                'batch_number' => $targetBatch->batch_number,
                'variant_id' => $variant->id,
                'variant_name' => $variant->name,
                'type' => BatchLog::TYPE_DEPLETED,
                'quantity_change' => -$quantityNeeded,
                'quantity_before' => $effectiveQuantity,
                'quantity_after' => $targetBatch->quantity_remaining,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'tenant_id' => $tenantId,
                'location_id' => $locationId,
                'department_id' => $departmentId,
                'expiry_date' => $targetBatch->expiry_date,
                'event_date' => now(),
                'performed_by' => auth()->id(),
                'metadata' => [
                    'inventory_id' => $inventory->id,
                    'customer_name' => $order->customer_name,
                    'item_name' => $itemName,
                    'deducted_from_batch' => $targetBatch->batch_number
                ]
            ]);
            
            // \Log::info('Batch depleted from specific batch', [
            //     'batch_id' => $targetBatch->id,
            //     'batch_number' => $targetBatch->batch_number,
            //     'quantity_deducted' => $quantityNeeded,
            //     'remaining' => $targetBatch->quantity_remaining
            // ]);
            
        } else {
            // ✅ No batch_id specified - use FIFO
            foreach ($batches as $batch) {
                if ($quantityNeeded <= 0) break;

                $effectiveQuantity = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                $deduct = min($effectiveQuantity, $quantityNeeded);
                
                if ($batch->quantity_remaining !== null) {
                    $batch->quantity_remaining -= $deduct;
                } else {
                    $batch->quantity_remaining = ($batch->quantity_received ?? 0) - $deduct;
                }
                $batch->save();
                $quantityNeeded -= $deduct;

                BatchLog::create([
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'variant_id' => $variant->id,
                    'variant_name' => $variant->name,
                    'type' => BatchLog::TYPE_DEPLETED,
                    'quantity_change' => -$deduct,
                    'quantity_before' => $effectiveQuantity,
                    'quantity_after' => $batch->quantity_remaining,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'tenant_id' => $tenantId,
                    'location_id' => $locationId,
                    'department_id' => $departmentId,
                    'expiry_date' => $batch->expiry_date,
                    'event_date' => now(),
                    'performed_by' => auth()->id(),
                    'metadata' => [
                        'inventory_id' => $inventory->id,
                        'customer_name' => $order->customer_name,
                        'item_name' => $itemName,
                    ]
                ]);
            }
        }

        // Update inventory allocation
        $inventory->quantity_allocated = max(0, $inventory->quantity_allocated - $quantityNeeded);
        $inventory->save();

        // Log inventory transaction
        InventoryTransactions::create([
            'quantity' => -$quantityNeeded,
            'reference_id' => $order->id,
            'reference_type' => 'order',
            'type' => 'sale',
            'notes' => "POS sale - Order #{$order->order_number} - {$variant->name} (BATCH)",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $tenantId,
        ]);

        // \Log::info('[Multi Shop] Batch depleted', [
        //     'variant' => $variant->name,
        //     'inventory_id' => $inventory->id,
        //     'location_id' => $locationId,
        //     'department_id' => $departmentId,
        //     'quantity' => $quantityNeeded,
        //     'batch_id_used' => $batchId
        // ]);
    }

    /**
     * Multi Shop - Serial strategy
     * Depletes from: inventory_items AND serial numbers (filtered by location/department)
     */
    private function depleteMultiSerial($variant, $item, $order)
    {
        $user = Auth::user();
        $tenantId = $order->tenant_id;

        // Get inventory data
        $inventoryData = json_decode($item->inventory_data, true);
        $inventoryId = $inventoryData['inventory_id'] ?? null;
        $locationId = $inventoryData['location_id'] ?? $user->location_id ?? null;
        $departmentId = $inventoryData['department_id'] ?? $user->department_id ?? null;

        // Get inventory record
        $inventory = null;
        if ($inventoryId) {
            $inventory = InventoryItems::find($inventoryId);
        }

        if (!$inventory && $locationId && $departmentId) {
            $inventory = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
        }

        if (!$inventory) {
            throw new \Exception("No inventory found for {$variant->name} at this location/department");
        }

        // ✅ Get serial_id from order item
        $serialId = $item->serial_id ?? null;
        
        if ($serialId) {
            // ✅ Deplete SPECIFIC serial
            $serial = SerialNumber::where('id', $serialId)
                ->where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->first();
                
            if (!$serial) {
                throw new \Exception("Serial number not found or already sold at this location");
            }
            
            $serial->update([
                'status' => SerialNumber::STATUS_SOLD,
                'order_id' => $order->id,
                'sold_at' => now(),
                'sold_by' => auth()->id(),
            ]);
            
            $quantity = 1;
            
        } else {
            // Fallback: FIFO
            $serials = SerialNumber::where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $tenantId)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->limit($item->quantity)
                ->get();

            if ($serials->count() < $item->quantity) {
                throw new \Exception("Insufficient serial numbers for {$variant->name}. Available: {$serials->count()}, Required: {$item->quantity}");
            }

            foreach ($serials as $serial) {
                $serial->update([
                    'status' => SerialNumber::STATUS_SOLD,
                    'order_id' => $order->id,
                    'sold_at' => now(),
                    'sold_by' => auth()->id(),
                ]);
            }
            
            $quantity = $item->quantity;
        }

        // Update inventory allocation
        $inventory->quantity_allocated = max(0, $inventory->quantity_allocated - $quantity);
        $inventory->save();

        // Log inventory transaction
        InventoryTransactions::create([
            'quantity' => -$quantity,
            'reference_id' => $order->id,
            'reference_type' => 'order',
            'type' => 'sale',
            'notes' => "POS sale - Order #{$order->order_number} - {$variant->name} (SERIAL)",
            'inventory_id' => $inventory->id,
            'created_by' => auth()->id(),
            'tenant_id' => $tenantId,
        ]);
    }


    /**
     * Validate that all recipe ingredients have sufficient stock
     */
    private function validateRecipeIngredients($product, $quantity)
    {
        if (!$product->hasRecipe()) {
            throw new \Exception("No recipe found for {$product->name}");
        }

        $recipe = $product->recipe;
        $ingredients = $recipe->ingredients;
        $quantityMultiplier = $quantity;

        if ($ingredients->isEmpty()) {
            throw new \Exception("Recipe for {$product->name} has no ingredients");
        }

        $isSingleShop = tenant_is_single_shop($product->tenant_id);

        foreach ($ingredients as $ingredient) {
            $ingredientVariant = $ingredient->ingredientVariant;
            
            if (!$ingredientVariant) {
                throw new \Exception("Missing ingredient variant in recipe '{$product->name}'");
            }

            $ingredientProduct = $ingredientVariant->product;
            if (!$ingredientProduct) {
                throw new \Exception("Product not found for ingredient '{$ingredientVariant->name}'");
            }

            $strategy = $ingredientProduct->resolvedInventoryStrategy();
            
            // ✅ Prevent nested recipes
            if ($strategy === 'recipe') {
                throw new \Exception("Nested recipes not allowed: '{$ingredientVariant->name}' is a recipe product");
            }

            $requiredQuantity = $ingredient->quantity_required * $quantityMultiplier;

            // ✅ Check stock based on shop mode and strategy
            if ($isSingleShop) {
                if ($strategy === 'quantity') {
                    // ✅ Check overal_quantity_at_hand
                    if ($ingredientVariant->overal_quantity_at_hand < $requiredQuantity) {
                        throw new \Exception("Insufficient stock for ingredient '{$ingredientVariant->name}'. Available: {$ingredientVariant->overal_quantity_at_hand}, Required: {$requiredQuantity}");
                    }
                } elseif ($strategy === 'batch') {
                    // ✅ Check batch quantity_remaining
                    $available = PurchaseReceiptItem::query()
                        ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                        ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                        ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
                        ->where('purchase_orders.tenant_id', $product->tenant_id)
                        ->where('purchase_order_items.product_variant_id', $ingredientVariant->id)
                        ->where('purchase_receipt_items.quantity_remaining', '>', 0)
                        ->sum('purchase_receipt_items.quantity_remaining');
                        
                    if ($available < $requiredQuantity) {
                        throw new \Exception("Insufficient batch stock for ingredient '{$ingredientVariant->name}'. Available: {$available}, Required: {$requiredQuantity}");
                    }
                } elseif ($strategy === 'serial') {
                    $available = SerialNumber::where('variant_id', $ingredientVariant->id)
                        ->where('status', 'available')
                        ->where('tenant_id', $product->tenant_id)
                        ->count();
                        
                    if ($available < $requiredQuantity) {
                        throw new \Exception("Insufficient serial numbers for ingredient '{$ingredientVariant->name}'. Available: {$available}, Required: {$requiredQuantity}");
                    }
                }
            } else {
                // MULTI SHOP: Check inventory_items table
                $inventory = InventoryItems::where('variant_id', $ingredientVariant->id)
                    ->where('tenant_id', $product->tenant_id)
                    ->first();

                if (!$inventory) {
                    throw new \Exception("No inventory allocation found for ingredient '{$ingredientVariant->name}'");
                }

                if ($inventory->quantity_allocated < $requiredQuantity) {
                    throw new \Exception("Insufficient stock for ingredient '{$ingredientVariant->name}'. Available: {$inventory->quantity_allocated}, Required: {$requiredQuantity}");
                }
            }
        }

        return true;
    }

    /**
     * Check if an ingredient/variant has enough stock (for non-recipe items)
     */
    private function checkIngredientStock($variant, $requiredQuantity, $tenantId)
    {
        $product = $variant->product;
        if (!$product) {
            throw new \Exception("Product not found for variant '{$variant->name}'");
        }

        $strategy = $product->resolvedInventoryStrategy();
        $isSingleShop = tenant_is_single_shop($tenantId);

        // \Log::info('checkIngredientStock called', [
        //     'variant_id' => $variant->id,
        //     'variant_name' => $variant->name,
        //     'strategy' => $strategy,
        //     'required_quantity' => $requiredQuantity,
        //     'is_single_shop' => $isSingleShop
        // ]);

        if ($strategy === 'recipe') {
            throw new \Exception("Nested recipes are not allowed. '{$variant->name}' is a recipe product.");
        }

        // ✅ FOR BATCH PRODUCTS - Check batches regardless of single or multi shop
        if ($strategy === 'batch') {
            // Get ALL batches with their quantities for this variant
            $batches = PurchaseReceiptItem::query()
                ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
                ->where('purchase_orders.tenant_id', $tenantId)
                ->where('purchase_order_items.product_variant_id', $variant->id)
                ->where(function($q) {
                    $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                    ->orWhereNull('purchase_receipt_items.quantity_remaining');
                })
                ->select('purchase_receipt_items.*')
                ->get();

            $totalAvailable = $batches->sum(function($batch) {
                return $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
            });

            // \Log::info('Batch strategy stock check', [
            //     'variant' => $variant->name,
            //     'total_available' => $totalAvailable,
            //     'required' => $requiredQuantity,
            //     'batches_count' => $batches->count(),
            //     'batches' => $batches->map(function($batch) {
            //         return [
            //             'id' => $batch->id,
            //             'batch_number' => $batch->batch_number,
            //             'quantity_remaining' => $batch->quantity_remaining,
            //             'quantity_received' => $batch->quantity_received,
            //             'effective' => $batch->quantity_remaining ?? $batch->quantity_received,
            //             'location_id' => $batch->location_id,
            //             'department_id' => $batch->department_id,
            //         ];
            //     })->toArray()
            // ]);

            if ($totalAvailable < $requiredQuantity) {
                throw new \Exception("Insufficient batch stock for {$variant->name}. Available: {$totalAvailable}, Required: {$requiredQuantity}");
            }
            
            return true;
        }

        // ✅ FOR QUANTITY PRODUCTS
        if ($isSingleShop) {
            // SINGLE SHOP - Check overal_quantity_at_hand
            $available = $variant->overal_quantity_at_hand ?? 0;
            // \Log::info('Single shop quantity stock check', [
            //     'variant' => $variant->name,
            //     'overal_quantity_at_hand' => $available,
            //     'required' => $requiredQuantity
            // ]);
            if ($available < $requiredQuantity) {
                throw new \Exception("Insufficient stock for {$variant->name}. Available: {$available}, Required: {$requiredQuantity}");
            }
        } else {
            // MULTI SHOP - Check inventory_items
            $inventory = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$inventory) {
                throw new \Exception("No inventory allocation found for {$variant->name}");
            }

            // \Log::info('Multi-shop quantity stock check', [
            //     'variant' => $variant->name,
            //     'inventory_id' => $inventory->id,
            //     'available' => $inventory->quantity_allocated,
            //     'required' => $requiredQuantity
            // ]);

            if ($inventory->quantity_allocated < $requiredQuantity) {
                throw new \Exception("Insufficient stock for {$variant->name}. Available: {$inventory->quantity_allocated}, Required: {$requiredQuantity}");
            }
        }

        return true;
    }

    /**
     * Log a batch depletion event
     */
    private function logBatchDepletion($batch, $variant, $item, $order, $quantityDeducted)
    {
        $beforeQty = ($batch->quantity_remaining ?? $batch->quantity_received ?? 0) + $quantityDeducted;
        $afterQty = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
        
        BatchLog::create([
            'batch_id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'variant_sku' => $variant->sku,
            'type' => BatchLog::TYPE_DEPLETED,
            'quantity_change' => -$quantityDeducted,
            'quantity_before' => $beforeQty,
            'quantity_after' => $afterQty,
            'unit_cost' => $batch->unit_cost ?? 0,
            'total_cost' => ($batch->unit_cost ?? 0) * $quantityDeducted,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'tenant_id' => $order->tenant_id,
            'location_id' => $batch->location_id ?? $order->location_id,
            'department_id' => $batch->department_id ?? $order->department_id,
            'expiry_date' => $batch->expiry_date,
            'event_date' => now(),
            'performed_by' => auth()->id(),
            'metadata' => [
                'item_name' => $item->name ?? $variant->name,
                'customer_name' => $order->customer_name,
                'unit_price' => $item->price ?? 0,
            ],
        ]);
    }

    private function depleteSingleSerial($variant, $item, $order)
    {
        // ✅ Get serial_id from the order item
        $serialId = $item->serial_id ?? null;
        $serialNumber = $item->serial_number ?? null;
        
        if ($serialId) {
            // ✅ Deplete SPECIFIC serial
            $serial = SerialNumber::where('id', $serialId)
                ->where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $order->tenant_id)
                ->first();
                
            if (!$serial) {
                throw new \Exception("Serial number not found or already sold");
            }
            
            // ✅ Mark as SOLD
            $serial->update([
                'status' => SerialNumber::STATUS_SOLD,
                'order_id' => $order->id,
                'sold_at' => now(),
                'sold_by' => auth()->id(),
            ]);
            
            // Log the sale
            \Log::info('Serial sold', [
                'serial_id' => $serial->id,
                'serial_number' => $serial->serial_number,
                'order_id' => $order->id,
                'variant_id' => $variant->id,
            ]);
            
        } else {
            // Fallback: FIFO - get first available serial
            $serial = SerialNumber::where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $order->tenant_id)
                ->first();
                
            if (!$serial) {
                throw new \Exception("No available serial numbers for {$variant->name}");
            }
            
            $serial->update([
                'status' => SerialNumber::STATUS_SOLD,
                'order_id' => $order->id,
                'sold_at' => now(),
                'sold_by' => auth()->id(),
            ]);
        }

        // Update overall quantity
        $variant->overal_quantity_at_hand = max(0, ($variant->overal_quantity_at_hand ?? 0) - 1);
        $variant->save();
    }

}

