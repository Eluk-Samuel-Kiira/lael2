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
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('payments.not_authorized')]);
            }
            abort(403);
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $initialFilters = [
            'search'   => (string) $request->get('search', ''),
            'per_page' => $perPage,
        ];

        return view('inventory.product-category-index', [
            'initialFilters' => $initialFilters,
        ]);
    }

    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view subcategory')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $search  = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = ProductCategory::with(['parentCategory:id,name', 'productCategoryCreater:id,name']);

        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('parentCategory', fn($p) => $p->where('name', 'like', "%{$search}%"))
                ->orWhereHas('productCategoryCreater', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $summary = [
            'total'    => (clone $query)->count(),
            'active'   => (clone $query)->where('is_active', 1)->count(),
            'inactive' => (clone $query)->where('is_active', 0)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(ProductCategory $c) => [
            'id'          => $c->id,
            'name'        => $c->name,
            'slug'        => $c->slug,
            'parent_name' => $c->parentCategory?->name ?? '—',
            'parent_id'   => $c->parent_category_id,
            'is_active'   => (bool) $c->is_active,
            'creator'     => $c->productCategoryCreater?->name ?? '—',
            'created_at'  => optional($c->created_at)->format('d M Y, h:i a'),
        ]);

        return response()->json([
            'success'    => true,
            'data'       => $rows,
            'summary'    => $summary,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'filters' => ['search' => $search, 'per_page' => $perPage],
        ]);
    }

    public function show(ProductCategory $productCategory)
    {
        $user = Auth::user();
        if (!$user->hasPermissionTo('view subcategory')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $tenantId = $productCategory->tenant_id;

        // Usage counts
        $productCount = Product::where('category_id', $productCategory->id)->count();
        $childCount   = ProductCategory::where('parent_category_id', $productCategory->id)->count();

        // ── Parent options come from `Category`, NOT ProductCategory ──
        $parentCategories = Category::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'category' => [
                'id'              => $productCategory->id,
                'name'            => $productCategory->name,
                'slug'            => $productCategory->slug,
                'description'     => $productCategory->description,
                'image_url'       => $productCategory->image_url
                    ? productCategoryImage($productCategory->image_url)
                    : null,
                'parent_id'       => (int) ($productCategory->parent_category_id ?? 0),
                'parent_name'     => $productCategory->parentCategory?->name ?? '—',
                'is_active'       => (bool) $productCategory->is_active,
                'creator' => [
                    'name'  => $productCategory->productCategoryCreater?->name ?? '—',
                    'email' => $productCategory->productCategoryCreater?->email,
                ],
                'created_at'      => optional($productCategory->created_at)->format('d M Y, h:i a'),
                'updated_at'      => optional($productCategory->updated_at)->format('d M Y, h:i a'),
                'product_count'   => $productCount,
                'child_count'     => $childCount,
                'can_edit'        => $user->can('edit subcategory'),
                'can_delete'      => $user->can('delete subcategory') && !$productCategory->is_active,
                'can_toggle'      => $user->can('edit subcategory'),
                'parent_options'  => $parentCategories,   // Category rows, not ProductCategory
            ],
        ]);
    }

    public function changeStatus(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit subcategory')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $validated = $request->validate(['status' => 'required|in:1,0']);

        $category = ProductCategory::where('id', $id)->where('tenant_id', $tenantId)->first();

        if ($category) {
            $category->is_active = $validated['status'];
            if ($category->save()) {
                return response()->json([
                    'success' => true,
                    'message' => __('auth._updated'),
                ]);
            }
        }
        return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
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
