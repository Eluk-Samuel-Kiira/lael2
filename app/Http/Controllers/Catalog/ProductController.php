<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Department, Location, Product, ProductCategory, Promotion, Tax };
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
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('view product')) {
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
        $query = Product::with(['category', 'productCreater']);
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$search}%"))
                ->orWhereHas('productCreater', fn($cr) => $cr->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Apply category filter if provided
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Apply type filter if provided
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Paginate with dynamic per_page
        $products = $query->latest()->paginate($perPage);
        
        // Preserve filters in pagination links
        $products->appends([
            'per_page' => $perPage, 
            'search' => $request->search,
            'category_id' => $request->category_id,
            'type' => $request->type
        ]);
        
        $bladeToReload = $request->query('bladeFileToReload');
        
        // For AJAX requests - return just the component HTML
        if ($request->ajax() && $bladeToReload === 'reloadProductComponent') {
            return view('inventory.product.component', [
                'all_products' => $products,
            ])->render();
        }
        
        // Get categories for filter
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get(['id', 'name']);
        
        // Get product types for filter
        $productTypes = ['simple' => 'Simple', 'variable' => 'Variable', 'digital' => 'Digital', 'service' => 'Service'];
        
        // Regular page load
        return view('inventory.product-index', [
            'all_products' => $products,
            'categories' => $categories,
            'productTypes' => $productTypes,
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
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('create product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $data = $request->validate([
            'category_id' => [
                'required',
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
                
        if (!$user->hasPermissionTo('view variant')) {
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
        
        // Get the parent product first
        $product = Product::where('id', $id);
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $product->where('tenant_id', $tenantId);
        }
        
        $product_variants = $product->firstOrFail();
        
        // Build query for variants with relationships
        $query = ProductVariant::with(['variantCreater', 'unitMeasure'])
            ->where('product_id', $id);
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%")
                ->orWhereHas('variantCreater', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Paginate with dynamic per_page
        $variants = $query->latest()->paginate($perPage);
        
        // Preserve per_page and search in pagination links
        $variants->appends(['per_page' => $perPage, 'search' => $request->search]);
        
        // Attach variants to product object
        $product_variants->setRelation('variants', $variants);
        
        $bladeToReload = $request->query('bladeFileToReload');
        
        // For AJAX requests - return just the component HTML
        if ($request->ajax() && $bladeToReload === 'reloadVariantComponent') {
            return view('inventory.product-variant.component', compact('product_variants'));
        }
        
        return view('inventory.product-variant.index', compact('product_variants'));
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
                
        if (!$user->hasPermissionTo('edit product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

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
                
        if (!$user->hasPermissionTo('delete product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

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
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $product = Product::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
    
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
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update product tax-promotion')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $product = Product::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
    
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
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('upload product photo')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
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
                
        if (!$user->hasPermissionTo('update product')) {
            abort(403, __('payments.not_authorized'));
        }

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

        // Auto-calculate locations based on selected departments
        $selectedDepartments = $validated['departments'] ?? [];
        $autoLocations = [];
        
        if (!empty($selectedDepartments)) {
            // Get all locations for the selected departments
            $autoLocations = Department::whereIn('id', $selectedDepartments)
                ->where('tenant_id', $tenantId)
                ->distinct()
                ->pluck('location_id')
                ->filter()
                ->toArray();
        }
        
        // Merge manually selected locations with auto-detected ones
        $manualLocations = $validated['locations'] ?? [];
        $allLocations = array_unique(array_merge($manualLocations, $autoLocations));

        // Start database transaction for data consistency
        DB::beginTransaction();

        try {
            // 1. Sync departments (use selected departments only)
            $product->departments()->sync($selectedDepartments);

            // 2. Sync locations (merged manual + auto-detected)
            $product->locations()->sync($allLocations);

            // 3. Sync promotions
            $product->promotions()->sync($validated['promotions'] ?? []);

            // 4. Handle taxes based on product's taxable status
            if ($product->is_taxable == 1) {
                $product->taxes()->sync($validated['taxes'] ?? []);
            } else {
                $product->taxes()->sync([]);
                
                if (!empty($validated['taxes'])) {
                    session()->flash('toast', [
                        'type' => 'warning',
                        'message' => __('pagination.product_not_taxable_taxes_removed'),
                    ]);
                }
            }

            DB::commit();

            session()->flash('toast', [
                'type' => 'success',
                'message' => __('auth._updated'),
            ]);

        } catch (\Exception $e) {
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
