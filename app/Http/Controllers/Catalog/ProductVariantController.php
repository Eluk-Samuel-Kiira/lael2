<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\{ Product, UnitOfMeasure, Tax, Promotion };
use Illuminate\Support\Facades\{ Auth, DB, Log };
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
            
            // ✅ NEW COST FIELDS
            'variants.*.supplier_cost_price' => 'nullable|numeric|min:0',
            'variants.*.total_shipping_cost' => 'nullable|numeric|min:0',
            'variants.*.ura_taxes_applied' => 'nullable|numeric|min:0',
            'variants.*.additional_expenses' => 'nullable|numeric|min:0',
            'variants.*.grand_total_cost_price' => 'nullable|numeric|min:0',
            
            // ✅ PRICING FIELDS
            'variants.*.selling_price' => 'required|numeric|min:0',
            'variants.*.discount_selling_price' => 'nullable|numeric|min:0',
            
            // ✅ DISCOUNT & MARKUP
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

                // ── Calculate Grand Total Cost Price if not provided ────────
                if (empty($variantData['grand_total_cost_price'])) {
                    $variantData['grand_total_cost_price'] = 
                        ($variantData['supplier_cost_price'] ?? 0) + 
                        ($variantData['total_shipping_cost'] ?? 0) + 
                        ($variantData['ura_taxes_applied'] ?? 0) + 
                        ($variantData['additional_expenses'] ?? 0);
                }

                // ── Set default values for new fields if not provided ────────
                $variantData['discount_selling_price'] = $variantData['discount_selling_price'] ?? $variantData['selling_price'];
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

            Log::error('Product variant creation failed', [
                'product_id' => $request->product_id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.variant_creation_failed') . ': ' . $e->getMessage(),
            ]);
        }

        return redirect()->route('products.show', $request['product_id']);
    }

    /**
     * Generate a unique 7-character alphanumeric SKU, scoped per tenant.
     */
    private function generateUniqueSku(int $tenantId): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $prefix   = 'VR';

        do {
            $random = '';
            for ($i = 0; $i < 5; $i++) {
                $random .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $sku = $prefix . $random;

            $exists = ProductVariant::where('tenant_id', $tenantId)
                ->where('sku', $sku)
                ->exists();
        } while ($exists);

        return $sku;
    }

    /**
     * Generate a unique EAN-13 barcode, scoped per tenant.
     */
    private function generateUniqueBarcode(int $tenantId): string
    {
        do {
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
     * Standard EAN-13 check digit algorithm.
     */
    private function calculateEan13CheckDigit(string $twelveDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit  = (int) $twelveDigits[$i];
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum   += $digit * $weight;
        }

        return (10 - ($sum % 10)) % 10;
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
        // Log::info($request->all());
        // Log::info($id);
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('edit variant')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $productVariant = ProductVariant::where('id', $id)
                                    ->where('tenant_id', $tenantId)
                                    ->first();

        if (!$productVariant) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        try {
            $data = $request->validate([
                'name' => [
                    'required',
                    'max:100',
                    Rule::unique('product_variants')->where(function ($query) use ($tenantId, $id) {
                        return $query->where('tenant_id', $tenantId)
                                ->where('id', '!=', $id);
                    }),
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
                    }),
                ],
                'barcode' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('product_variants')->where(function ($query) use ($tenantId, $id) {
                        return $query->where('tenant_id', $tenantId)
                                ->where('id', '!=', $id);
                    }),
                ],
                
                // ✅ NEW COST FIELDS
                'supplier_cost_price' => 'nullable|numeric|min:0',
                'total_shipping_cost' => 'nullable|numeric|min:0',
                'ura_taxes_applied' => 'nullable|numeric|min:0',
                'additional_expenses' => 'nullable|numeric|min:0',
                'grand_total_cost_price' => 'nullable|numeric|min:0',
                
                // ✅ PRICING FIELDS
                'selling_price' => 'required|numeric|min:0',
                'discount_selling_price' => 'nullable|numeric|min:0',
                
                // ✅ DISCOUNT & MARKUP
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

            // ✅ Calculate Grand Total Cost Price if not provided
            if (empty($data['grand_total_cost_price']) || $data['grand_total_cost_price'] == 0) {
                $data['grand_total_cost_price'] = 
                    ($data['supplier_cost_price'] ?? 0) + 
                    ($data['total_shipping_cost'] ?? 0) + 
                    ($data['ura_taxes_applied'] ?? 0) + 
                    ($data['additional_expenses'] ?? 0);
            }

            // ✅ Set default values for new fields if not provided
            $data['discount_selling_price'] = $data['discount_selling_price'] ?? $data['selling_price'];
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

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Variant update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
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

        // ✅ Begin transaction to delete variant and related records
        DB::beginTransaction();
        
        try {
            // ✅ Delete inventory records for this variant (multi-shop)
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

            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadVariantComponent',
                'message' => __('auth._deleted'),
                'redirect' => route('products.show', $variant->product_id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Variant deletion failed', [
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

    /**
     * Change variant status (active/inactive).
     */
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

        $validated = $request->validate([
            'status' => 'required|in:1,0',
        ]);
        
        $variant = ProductVariant::where('id', $id)
                                ->where('tenant_id', $tenantId)
                                ->first();

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $variant->is_active = $validated['status']; 
        $variant->save();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadVariantComponent',
            'message' => __('auth._updated'),
            'redirect' => route('products.show', $variant->product_id),
        ]);
    }

    /**
     * Upload variant image.
     */
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

        $variant = ProductVariant::where('id', $request->variant_id)
                                ->where('tenant_id', $tenantId)
                                ->first();

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('variants', 'public');
            $variant->update(['image_url' => $path]);

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

    /**
     * Change variant tax status.
     */
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

        $validated = $request->validate([
            'status' => 'required|in:1,0',
        ]);
        
        $variant = ProductVariant::where('id', $id)
                                ->where('tenant_id', $tenantId)
                                ->first();

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $variant->is_taxable = $validated['status']; 
        $variant->save();
        
        if ($validated['status'] == 1) {  
            $message = __('pagination.variant_taxable_now');
        } else {
            $message = __('pagination.variant_not_taxable');
            $variant->variantTaxes()->sync([]);
        }
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadVariantComponent',
            'message' => $message,
            'redirect' => route('products.show', $variant->product_id),
        ]);
    }

    /**
     * Update variant assignments (taxes and promotions).
     */
    public function updateVariantAssignments(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update variant tax-promotion')) {
            abort(403, __('payments.not_authorized'));
        }

        try {
            $variant = ProductVariant::where('id', $id)
                                    ->where('tenant_id', $tenantId)
                                    ->first();

            if (!$variant) {
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

            DB::beginTransaction();

            // Sync promotions
            $variant->Variantpromotions()->sync($validated['promotions'] ?? []);

            // Handle taxes based on taxable status
            if ($variant->is_taxable == 1) {
                $pivotData = [];
                if (!empty($validated['taxes'])) {
                    foreach ($validated['taxes'] as $taxId) {
                        $pivotData[$taxId] = [
                            'created_by' => $user->id,
                            'tenant_id' => $tenantId,
                        ];
                    }
                }
                $variant->variantTaxes()->sync($pivotData);
            } else {
                $variant->variantTaxes()->sync([]);
                
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
            Log::error('Validation failed for variant assignments', [
                'variant_id' => $id,
                'tenant_id' => $tenantId,
                'errors' => $e->errors()
            ]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update variant assignments', [
                'variant_id' => $id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.update_failed') . ': ' . $e->getMessage(),
            ]);
            return redirect()->back()->withInput();
        }

        return redirect()->back();
    }
}