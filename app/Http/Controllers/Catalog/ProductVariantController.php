<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\{ Product, UnitOfMeasure, Tax, Promotion };
use Illuminate\Support\Facades\{ Auth, DB };
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
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
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create variant')) {
            abort(403, __('payments.not_authorized'));
        }

        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $product = Product::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$product) {
                        $fail('The selected product is invalid.');
                    }
                }
            ],
            'variants' => 'required|array|min:1',
            'variants.*.name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'variants.*.sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('product_variants')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'variants.*.barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_variants')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.cost_price' => 'required|numeric|min:0',
            
            // ✅ NEW FIELDS - Optional
            'variants.*.net_cost_price' => 'nullable|numeric|min:0',
            'variants.*.net_selling_price' => 'nullable|numeric|min:0',
            'variants.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'variants.*.markup_percentage' => 'nullable|numeric|min:0',
            
            'variants.*.overal_quantity_at_hand' => 'nullable|integer',
            'variants.*.weight' => 'required|numeric|min:0',
            'variants.*.weight_unit' => [
                'required',
                'exists:unit_of_measures,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $uom = UnitOfMeasure::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$uom) {
                        $fail('The selected weight unit is invalid.');
                    }
                }
            ],
            'variants.*.image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:4048',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['variants'] as $variantData) {
                $variantData['product_id'] = $request->product_id;
                $variantData['created_by'] = $user->id;
                $variantData['tenant_id']  = $tenantId;

                // ── Auto-generate SKU if left blank ─────────────────────────
                if (empty($variantData['sku'])) {
                    $variantData['sku'] = $this->generateUniqueSku($tenantId);
                }

                // ── Auto-generate barcode if left blank ─────────────────────
                if (empty($variantData['barcode'])) {
                    $variantData['barcode'] = $this->generateUniqueBarcode($tenantId);
                }

                // ── Set default values for new fields if not provided ────────
                $variantData['net_cost_price'] = $variantData['net_cost_price'] ?? $variantData['cost_price'];
                $variantData['net_selling_price'] = $variantData['net_selling_price'] ?? $variantData['price'];
                $variantData['discount_percentage'] = $variantData['discount_percentage'] ?? 0;
                $variantData['markup_percentage'] = $variantData['markup_percentage'] ?? 0;

                // Handle image upload
                if (isset($variantData['image'])) {
                    $path = $variantData['image']->store('variants', 'public');
                    $variantData['image_url'] = $path;
                    unset($variantData['image']);
                }

                ProductVariant::create($variantData);
            }

            DB::commit();

            session()->flash('toast', [
                'type' => 'success',
                'message' => __('auth._created'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Product variant creation failed', [
                'product_id' => $request->product_id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.variant_creation_failed'),
            ]);
        }

        return redirect()->route('products.show', $request['product_id']);
    }


    /**
     * Generate a unique 7-character alphanumeric SKU, scoped per tenant.
     *
     * Format: 2 letters (tenant-agnostic prefix) + 5 uppercase alphanumeric
     * characters, e.g. "VR7K2QD". Excludes ambiguous characters (0/O, 1/I/L)
     * to keep SKUs easy to read off a shelf label or receipt.
     */
    private function generateUniqueSku(int $tenantId): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // no 0,O,1,I,L
        $prefix   = 'VR'; // short, constant — feel free to swap for a category code

        do {
            $random = '';
            for ($i = 0; $i < 5; $i++) {
                $random .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $sku = $prefix . $random; // 7 characters total

            $exists = ProductVariant::where('tenant_id', $tenantId)
                ->where('sku', $sku)
                ->exists();
        } while ($exists);

        return $sku;
    }

    /**
     * Generate a unique, standard-format EAN-13 barcode, scoped per tenant.
     *
     * EAN-13 is the common retail barcode standard (what you see on shelf
     * products worldwide) — 12 random digits plus a computed check digit,
     * making it scannable by any standard barcode reader/POS scanner.
     */
    private function generateUniqueBarcode(int $tenantId): string
    {
        do {
            // 12 random digits (first digit avoided as 0 to keep it looking
            // like a normal retail prefix, purely cosmetic — not a real GS1
            // registered prefix since these are internally generated codes)
            $digits = (string) random_int(1, 9);
            for ($i = 0; $i < 11; $i++) {
                $digits .= (string) random_int(0, 9);
            }

            $barcode = $digits . $this->calculateEan13CheckDigit($digits);

            $exists = ProductVariant::where('tenant_id', $tenantId)
                ->where('barcode', $barcode)
                ->exists();
        } while ($exists);

        return $barcode;
    }

    /**
     * Standard EAN-13 check digit algorithm (odd positions x1, even x3,
     * mod 10). Given the first 12 digits, returns the 13th check digit.
     */
    private function calculateEan13CheckDigit(string $twelveDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit  = (int) $twelveDigits[$i];
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum   += $digit * $weight;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit;
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('view variant')) {
            abort(403, __('payments.not_authorized'));
        }

        // Find product and ensure it belongs to tenant
        $product = Product::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->firstOrFail();

        return view('inventory.product-variant.create', compact('product'));
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
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('edit variant')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find product variant and ensure it belongs to tenant
        $productVariant = ProductVariant::where('id', $id)
                                    ->where('tenant_id', $tenantId)
                                    ->first();

        if (!$productVariant) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'max:100',
                Rule::unique('product_variants')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($productVariant->id),
            ],
            'product_id' => [
                'required',
                'exists:products,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $product = Product::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$product) {
                        $fail('The selected product is invalid.');
                    }
                }
            ],
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('product_variants')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($productVariant->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_variants')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($productVariant->id),
            ],
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            
            // ✅ NEW VALIDATION RULES
            'net_cost_price' => 'nullable|numeric|min:0',
            'net_selling_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'markup_percentage' => 'nullable|numeric|min:0',
            
            'overal_quantity_at_hand' => 'required|integer|min:0',
            'weight' => 'required|numeric|min:0',
            'weight_unit' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $uom = UnitOfMeasure::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$uom) {
                        $fail(__('payments.uom_invalid'));
                    }
                }
            ],
        ]);

        $data['created_by'] = $user->id;

        // ✅ Set default values for new fields if not provided
        $data['net_cost_price'] = $data['net_cost_price'] ?? $data['cost_price'];
        $data['net_selling_price'] = $data['net_selling_price'] ?? $data['price'];
        $data['discount_percentage'] = $data['discount_percentage'] ?? 0;
        $data['markup_percentage'] = $data['markup_percentage'] ?? 0;

        $productVariant->update($data);

        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadVariantComponent',
            'message' => __('auth._updated'),
            'redirect' => route('products.show', $data['product_id']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('delete variant')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $variant = ProductVariant::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if ($variant->is_active == 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // ✅ Check if variant is referenced in order_items
        $hasOrders = DB::table('order_items')
            ->where('variant_id', $id)
            ->exists();

        if ($hasOrders) {
            return response()->json([
                'success' => false,
                'message' => __('auth.variant_has_orders'),
                'has_orders' => true,
            ]);
        }

        // ✅ Check if variant is used in recipes
        $hasRecipes = DB::table('recipe_ingredients')
            ->where('ingredient_variant_id', $id)
            ->exists();

        if ($hasRecipes) {
            return response()->json([
                'success' => false,
                'message' => __('auth.variant_used_in_recipes'),
                'has_recipes' => true,
            ]);
        }

        // ✅ Get inventory data before deletion (for logging)
        $inventoryItems = DB::table('inventory_items')
            ->where('variant_id', $id)
            ->get();

        $hasInventory = false;
        $totalInventoryQty = 0;

        // ✅ Check if variant has inventory (single shop)
        if ($variant->overal_quantity_at_hand > 0) {
            $hasInventory = true;
            $totalInventoryQty += $variant->overal_quantity_at_hand;
        }

        // ✅ Check if variant has inventory in multi-shop (inventory_items table)
        if (!$hasInventory) {
            $inventoryCount = DB::table('inventory_items')
                ->where('variant_id', $id)
                ->where('tenant_id', $tenantId)
                ->where('quantity_allocated', '>', 0)
                ->exists();
            
            if ($inventoryCount) {
                $hasInventory = true;
                $totalInventoryQty = DB::table('inventory_items')
                    ->where('variant_id', $id)
                    ->where('tenant_id', $tenantId)
                    ->sum('quantity_allocated');
            }
        }

        // ✅ Begin transaction to delete variant and related records
        DB::beginTransaction();
        
        try {
            // ✅ Delete inventory records for this variant (multi-shop) - ALWAYS DELETE
            DB::table('inventory_items')
                ->where('variant_id', $id)
                ->where('tenant_id', $tenantId)
                ->delete();

            // ✅ Delete inventory adjustments
            DB::table('inventory_adjustments')
                ->whereIn('inventory_id', function($query) use ($id, $tenantId) {
                    $query->select('id')
                        ->from('inventory_items')
                        ->where('variant_id', $id)
                        ->where('tenant_id', $tenantId);
                })
                ->delete();

            // ✅ Delete inventory transactions
            DB::table('inventory_transactions')
                ->whereIn('inventory_id', function($query) use ($id, $tenantId) {
                    $query->select('id')
                        ->from('inventory_items')
                        ->where('variant_id', $id)
                        ->where('tenant_id', $tenantId);
                })
                ->delete();

            // ✅ Delete variant taxes
            DB::table('variant_taxes')
                ->where('variant_id', $id)
                ->delete();

            // ✅ Delete variant promotions
            DB::table('promotion_products')
                ->where('variant_id', $id)
                ->delete();

            // ✅ Delete single shop inventory logs
            DB::table('single_shop_inventory_logs')
                ->where('variant_id', $id)
                ->where('tenant_id', $tenantId)
                ->delete();

            // ✅ Delete the variant
            $variant->delete();

            DB::commit();

            // ✅ Prepare success message
            $message = __('auth._deleted');
            if ($hasInventory) {
                $message = __('auth.variant_deleted_with_inventory') . ' (' . $totalInventoryQty . ' ' . __('pagination._units') . ')';
            }

            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadVariantComponent',
                'message' => $message,
                'redirect' => route('products.show', $variant->product_id),
                // 'inventory_deleted' => $hasInventory,
                // 'inventory_quantity' => $totalInventoryQty,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Variant deletion failed', [
                'variant_id' => $id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.delete_failed') . ': ' . $e->getMessage(),
            ]);
        }
    }

    public function changeVariantStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update variant')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',
        ]);
        
        // Find product variant and ensure it belongs to tenant
        $product = ProductVariant::where('id', $id)
                                ->where('tenant_id', $tenantId)
                                ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $product->is_active = $validated['status']; 
        if ($product->save()) {
            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadVariantComponent',
                'message' => __('auth._updated'),
                'redirect' => route('products.show', $product->product_id),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('auth.update_failed'),
        ]);
    }

    public function uploadVariantImage(Request $request)
    {
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('upload variant image')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the request to ensure the file exists
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'variant_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $variant = ProductVariant::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    if (!$variant) {
                        $fail('The selected variant is invalid.');
                    }
                }
            ],
        ]);

        // Find product variant and ensure it belongs to tenant
        $product = ProductVariant::where('id', $request->variant_id)
                                ->where('tenant_id', $tenantId)
                                ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('variants', 'public');
            $product->update(['image_url' => $path]);

            return response()->json([
                'success' => true,
                'message' => __('auth._uploaded'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('auth.upload_failed'),
        ]);
    }

    public function changeProductVariantTaxStatus(Request $request, $id) 
    {
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update variant tax-promotion')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',
        ]);
        
        // Find product variant and ensure it belongs to tenant
        $product = ProductVariant::where('id', $id)
                                ->where('tenant_id', $tenantId)
                                ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $product->is_taxable = $validated['status']; 
        $product->save();
        
        if ($validated['status'] == 1) {  
            $message = __('pagination.variant_taxable_now');
        } else {
            $message = __('pagination.variant_not_taxable');
            
            // Remove all taxes when variant becomes non-taxable
            $product->variantTaxes()->sync([]);
        }
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadVariantComponent',
            'message' => $message,
            'redirect' => route('products.show', $product->product_id),
        ]);
    }

    public function updateVariantAssignments(Request $request, $id)
    {
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update variant tax-promotion')) {
            abort(403, __('payments.not_authorized'));
        }

        try {
            // Find product variant and ensure it belongs to tenant
            $product = ProductVariant::where('id', $id)
                                    ->where('tenant_id', $tenantId)
                                    ->first();

            if (!$product) {
                session()->flash('toast', [
                    'type' => 'error',
                    'message' => __('auth._not_found'),
                ]);
                return redirect()->back();
            }

            $validated = $request->validate([
                'taxes' => ['nullable', 'array'],
                'taxes.*' => [
                    'exists:taxes,id',
                    function ($attribute, $value, $fail) use ($tenantId) {
                        $tax = Tax::where('id', $value)
                                ->where('tenant_id', $tenantId)
                                ->where('is_active', 1)
                                ->first();
                        if (!$tax) {
                            $fail('The selected tax is invalid or inactive.');
                        }
                    }
                ],
                'promotions' => ['nullable', 'array'],
                'promotions.*' => [
                    'exists:promotions,id',
                    function ($attribute, $value, $fail) use ($tenantId) {
                        $promotion = Promotion::where('id', $value)
                                            ->where('tenant_id', $tenantId)
                                            ->where('is_active', 1)
                                            ->first();
                        if (!$promotion) {
                            $fail('The selected promotion is invalid or inactive.');
                        }
                    }
                ],
            ]);

            // Start database transaction
            DB::beginTransaction();

            // Sync promotions
            $product->Variantpromotions()->sync($validated['promotions'] ?? []);

            // Handle taxes based on taxable status
            if ($product->is_taxable == 1) {
                $pivotData = [];
                if (!empty($validated['taxes'])) {
                    foreach ($validated['taxes'] as $taxId) {
                        $pivotData[$taxId] = [
                            'created_by' => $user->id,
                            'tenant_id' => $tenantId,
                        ];
                    }
                }
                $product->variantTaxes()->sync($pivotData);
            } else {
                // Remove all taxes if variant is not taxable
                $product->variantTaxes()->sync([]);
                
                session()->flash('toast', [
                    'type' => 'warning',
                    'message' => __('pagination.variant_not_taxable_now'),
                ]);
            }

            DB::commit();

            session()->flash('toast', [
                'type' => 'success',
                'message' => __('auth._updated'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Validation failed for variant assignments', [
                'variant_id' => $id,
                'tenant_id' => $tenantId,
                'errors' => $e->errors()
            ]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update variant assignments', [
                'variant_id' => $id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.update_failed'),
            ]);
            return redirect()->back()->withInput();
        }

        return redirect()->back();
    }


}
