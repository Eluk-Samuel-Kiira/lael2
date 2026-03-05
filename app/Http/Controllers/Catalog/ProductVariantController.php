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
                'required',
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
            'variants.*.image' => 'required|image|mimes:jpg,jpeg,png,gif|max:4048',
        ]);

        // Check maximum variants limit for this product
        // $currentVariantCount = ProductVariant::where('product_id', $validated['product_id'])
        //                                     ->where('tenant_id', $tenantId)
        //                                     ->count();
        // $maxVariants = tenant_setting($tenantId, 'max_variants_per_product', 10); // Default to 10 if not set

        // if (($currentVariantCount + count($validated['variants'])) > $maxVariants) {
        //     session()->flash('toast', [
        //         'type' => 'error',
        //         'message' => __('auth.maximum_variants_reached', ['max' => $maxVariants]),
        //     ]);
        //     return redirect()->back();
        // }

        DB::beginTransaction();

        try {
            foreach ($validated['variants'] as $variantData) {
                $variantData['product_id'] = $request->product_id;
                $variantData['created_by'] = $user->id;
                $variantData['tenant_id'] = $tenantId;
                
                // Handle image upload
                if (isset($variantData['image'])) {
                    $path = $variantData['image']->store('variants', 'public');
                    $variantData['image_url'] = $path;
                    unset($variantData['image']); // Remove the image object from array
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
        // Don't update tenant_id

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

        $product = ProductVariant::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if ($product->is_active === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        $product->delete();
        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadVariantComponent',
            'message' => __('auth._deleted'),
            'redirect' => route('products.show', $product['product_id']),
        ]);
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
