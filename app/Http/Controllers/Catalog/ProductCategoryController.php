<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\{ ProductCategory, Product, Category };
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('view subcategory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query
        $query = ProductCategory::with('productCategoryCreater', 'parentCategory');
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        $products_categories = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadProductCategoryComponent':
                return view('inventory.product-category.component', [
                    'products_categories' => $products_categories,
                ]);
            default:
                return view('inventory.product-category-index', [
                    'products_categories' => $products_categories,
                ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        // $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('create subcategory')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('inventory.product-category.create', [
            
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    
    public function store(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('create subcategory')) {
            abort(403, __('payments.not_authorized'));
        }

        

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'parent_category_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($tenantId) {
                    if ($value != 0) { // Assuming 0 means no parent
                        $parentCategory = Category::where('id', $value)
                                            ->where('tenant_id', $tenantId)
                                            ->first();
                        if (!$parentCategory) {
                            $fail(__('payments.selected_cat_invalid'));
                        }
                    }
                }
            ],
            'description' => 'nullable|string',
            'is_active' => 'required|in:1,0',
            // 'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        try {

            // Check maximum product categories limit
            // $currentProductCategoryCount = ProductCategory::where('tenant_id', $tenantId)->count();
            // $maxProductCategories = tenant_setting($tenantId, 'max_product_categories', 50); // Default to 50 if not set

            // if ($currentProductCategoryCount >= $maxProductCategories) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => __('auth.maximum_product_categories_reached', ['max' => $maxProductCategories]),
            //     ]);
            // }

            $validated['slug'] = Str::slug($validated['name']);
            $validated['created_by'] = $user->id;
            $validated['tenant_id'] = $tenantId;

            // If file uploaded, store and set image_url
            // if ($request->hasFile('photo')) {
            //     $path = $request->file('photo')->store('categories', 'public');
            //     $validated['image_url'] = $path;
            // }

            // // Remove 'photo' from array before saving if not needed in DB
            // unset($validated['photo']);

            ProductCategory::create($validated);

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadProductCategoryComponent',
                'refresh' => false,
                'message' => __('auth._created'),
                'redirect' => route('product-category.index'),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $productCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductCategory $productCategory)
    {
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('edit subcategory')) {
            abort(403, __('payments.not_authorized'));
        }
        return view('inventory.product-category.edit', compact('productCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('edit subcategory')) {
            abort(403, __('payments.not_authorized'));
        }

        
        // Find product category and ensure it belongs to tenant
        $category = ProductCategory::where('id', $id)
                                ->where('tenant_id', $tenantId)
                                ->first();

        $validated = $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('product_categories')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($category->id),
            ],
            'parent_category_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($tenantId, $id) {
                    if ($value != 0) {
                        $parentCategory = Category::where('id', $value)
                                            ->where('tenant_id', $tenantId)
                                            ->first();
                        if (!$parentCategory) {
                            $fail('The selected parent category is invalid.');
                        }
                    }
                }
            ],
            'description' => 'nullable|string',
            'is_active' => 'required|in:1,0',
            // 'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:4048',
        ]);

        try {


            // Check if category belongs to tenant
            if ($category->tenant_id !== $tenantId) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized_access'),
                ]);
            }

            $validated['slug'] = Str::slug($validated['name']);
            $validated['updated_by'] = $user->id;

            // If file uploaded, store and set image_url
            // if ($request->hasFile('photo')) {
            //     $path = $request->file('photo')->store('categories', 'public');
            //     $validated['image_url'] = $path;
            // } else {
            //     // Keep existing image_url if no new file uploaded
            //     $validated['image_url'] = $category->image_url;
            // }

            // // Remove 'photo' field if exists (not needed in DB)
            // unset($validated['photo']);

            // Update the category record
            $category->update($validated);

            // session()->flash('toast', [
            //     'type' => 'success',
            //     'message' => __('auth._updated'),
            // ]);

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadProductCategoryComponent',
                'refresh' => false,
                'message' => __('auth._updated'),
                'redirect' => route('product-category.index'),
            ]);

        } catch (ValidationException $e) {
            return redirect()->back()
                            ->withErrors($e->errors())
                            ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('delete subcategory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find product category and ensure it belongs to tenant
        $category = ProductCategory::where('id', $id)
                                ->where('tenant_id', $tenantId)
                                ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if ($category->is_active === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // Check if product category is attached to any products
        $attachedToProducts = Product::where('category_id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($attachedToProducts) {
            return response()->json([
                'success' => false,
                'message' => __('auth.product_category_attached_to_products'),
            ]);
        }

        // Check if this category is used as parent in other product categories
        $hasChildCategories = ProductCategory::where('parent_category_id', $id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasChildCategories) {
            return response()->json([
                'success' => false,
                'message' => __('auth.product_category_has_children'),
            ]);
        }

        $category->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadProductCategoryComponent',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('product-category.index'),
        ]);
    }
}
