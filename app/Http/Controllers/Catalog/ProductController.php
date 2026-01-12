<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Product, ProductCategory, Promotion, Tax };
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Auth, DB };
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Build the query
        $query = Product::with('category', 'productCreater');
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        $products = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadProductComponent':
                return view('inventory.product.component', [
                    'all_products' => $products,
                ]);
            default:
                return view('inventory.product-index', [
                    'all_products' => $products,
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
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $data = $request->validate([
            'category_id' => [
                'nullable',
                'exists:product_categories,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    if ($value) {
                        $category = ProductCategory::where('id', $value)
                                                ->where('tenant_id', $tenantId)
                                                ->first();
                        if (!$category) {
                            $fail('The selected category is invalid.');
                        }
                    }
                }
            ],
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'description' => 'nullable|string',
            'type' => 'required|in:physical,digital,service,composite',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Check maximum products limit
        // $currentProductCount = Product::where('tenant_id', $tenantId)->count();
        // $maxProducts = tenant_setting($tenantId, 'max_products', 100); // Default to 100 if not set

        // if ($currentProductCount >= $maxProducts) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => __('auth.maximum_products_reached', ['max' => $maxProducts]),
        //     ]);
        // }

        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $user->id;
        $data['tenant_id'] = $tenantId;

        Product::create($data);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadProductComponent',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('products.index'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Build the query
        $query = Product::with('variants')->where('id', $id);
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }
        
        $product_variants = $query->firstOrFail();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadVariantComponent':
                return view('inventory.product-variant.component', compact('product_variants'));
            default:
                return view('inventory.product-variant.index', compact('product_variants'));
        }
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

        // Find product and ensure it belongs to tenant
        $product = Product::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'max:100',
                Rule::unique('products')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($product->id),
            ],
            'sku' => [
                'required',
                'max:50',
                Rule::unique('products')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($product->id),
            ],
            'category_id' => [
                'required',
                'exists:product_categories,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $category = ProductCategory::where('id', $value)
                                            ->where('tenant_id', $tenantId)
                                            ->first();
                    if (!$category) {
                        $fail('The selected category is invalid.');
                    }
                }
            ],
            'description' => 'nullable|string',
            'type' => 'required|in:physical,digital,service,composite',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $user->id;
        // Don't update tenant_id

        $product->update($data);

        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadProductComponent',
            'message' => __('auth._updated'),
            'redirect' => route('products.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Find product and ensure it belongs to tenant
        $product = Product::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if ($product->is_active == 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // Check if product has any variants
        $hasVariants = ProductVariant::where('product_id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasVariants) {
            return response()->json([
                'success' => false,
                'message' => __('auth.product_has_variants'),
            ]);
        }

        // Check if product is referenced in orders
        $hasOrders = DB::table('order_items')
            ->where('product_id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasOrders) {
            return response()->json([
                'success' => false,
                'message' => __('auth.product_has_orders'),
            ]);
        }

        // Check if product is referenced in inventory
        $hasInventory = DB::table('inventory_items')
            ->where('product_id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasInventory) {
            return response()->json([
                'success' => false,
                'message' => __('auth.product_has_inventory'),
            ]);
        }

        $product->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadProductComponent',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('products.index'),
        ]);
    }

    
    public function changeProductStatus(Request $request, $id) 
    {
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $product = Product::find($id);
    
        if ($product) {
            $product->is_active = $validated['status']; 
            if ($product->save()) {  // Save the user object
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'reloadProductComponent',
                    'message' => __('auth._updated'),
                    'redirect' => route('products.index'),
                ]);
            }
        }
    
        // If user not found or status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth._not_found'),
        ]);
    }

    
    public function changeProductTaxStatus(Request $request, $id) 
    {
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $product = Product::find($id);
    
        if ($product) {
            $product->is_taxable = $validated['status']; 
            $product->save();
            if ($validated['status'] == 1) {  
                $message = __('pagination.taxable_now');
            } else {
                $message = __('pagination.not_taxable');
            }
            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadProductComponent',
                'message' => $message,
                'redirect' => route('products.index'),
            ]);
        }
    
        // If user not found or status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth._not_found'),
        ]);
    }

    public function uploadProductImage(Request $request)
    {
        // Validate the request to ensure the file exists
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'product_id' => 'required|integer',
        ]);

        if ($request->hasFile('photo')) {
            $product = Product::find($request['product_id']);

            $path = $request->file('photo')->store('products', 'public');
            $product->update(['image_url' => $path]);


            // Respond with success if it's an AJAX request
            return response()->json([
                'success' => true,
                'message' => __('auth._uploaded '),
            ]);
        }

        // Return an error response if something goes wrong upload_failed
        return response()->json([
            'success' => false,
            'message' => __('auth.upload_failed '),
        ]);
    }

    public function updateProductAssignments(Request $request, Product $product)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Check if product belongs to tenant
        if ($product->tenant_id !== $tenantId) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.unauthorized_access'),
            ]);
            return redirect()->back();
        }

        $validated = $request->validate([
            'departments' => ['nullable', 'array'],
            'departments.*' => [
                'exists:departments,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $department = Department::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    if (!$department) {
                        $fail('The selected department is invalid.');
                    }
                }
            ],
            'locations' => ['nullable', 'array'],
            'locations.*' => [
                'exists:locations,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $location = Location::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$location) {
                        $fail('The selected location is invalid.');
                    }
                }
            ],
            'taxes' => ['nullable', 'array'],
            'taxes.*' => [
                'exists:taxes,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $tax = Tax::where('id', $value)
                            ->where('tenant_id', $tenantId)
                            ->first();
                    if (!$tax) {
                        $fail('The selected tax is invalid.');
                    }
                }
            ],
            'promotions' => ['nullable', 'array'],
            'promotions.*' => [
                'exists:promotions,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $promotion = Promotion::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    if (!$promotion) {
                        $fail('The selected promotion is invalid.');
                    }
                }
            ],
        ]);

        // Start database transaction for data consistency
        DB::beginTransaction();

        try {
            // 1. Sync departments
            $product->departments()->sync($validated['departments'] ?? []);

            // 2. Sync locations
            $product->locations()->sync($validated['locations'] ?? []);

            // 3. Sync promotions
            $product->promotions()->sync($validated['promotions'] ?? []);

            // 4. Handle taxes based on product's taxable status
            if ($product->is_taxable == 1) {
                // Product is taxable - sync taxes
                $product->taxes()->sync($validated['taxes'] ?? []);
            } else {
                // Product is not taxable - remove all taxes
                $product->taxes()->sync([]);
                
                // Show warning message if taxes were attempted to be assigned
                if (!empty($validated['taxes'])) {
                    session()->flash('toast', [
                        'type' => 'warning',
                        'message' => __('pagination.product_not_taxable_taxes_removed'),
                    ]);
                }
            }

            // Commit transaction
            DB::commit();

            session()->flash('toast', [
                'type' => 'success',
                'message' => __('auth._updated'),
            ]);

        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            \Log::error('Product assignments update failed', [
                'product_id' => $product->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.update_failed'),
            ]);
        }

        return redirect()->back();
    }


}
