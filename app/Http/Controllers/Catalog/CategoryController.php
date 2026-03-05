<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\{ Category, ProductCategory };
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('view category')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query
        $query = Category::with('categoryCreater');
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }
        
        $categories = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadCategoryComponent':
                return view('inventory.category.category-componenet', [
                    'all_categories' => $categories,
                ]);
            default:
                return view('inventory.category-index', [
                    'all_categories' => $categories,
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
                
        if (!$user->hasPermissionTo('create category')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'description' => 'nullable|string',
        ]);

        // Check maximum categories limit
        // $currentCategoryCount = Category::where('tenant_id', $tenantId)->count();
        // $maxCategories = tenant_setting($tenantId, 'max_categories', 20);

        // if ($currentCategoryCount >= $maxCategories) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => __('auth.maximum_categories_reached', ['max' => $maxCategories]),
        //     ]);
        // }

        $validated['slug'] = Str::slug($validated['name']);
        $validated['created_by'] = $user->id;
        $validated['tenant_id'] = $tenantId;

        Category::create($validated);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadCategoryComponent',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('category.index'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('edit category')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Check if category belongs to tenant
        if ($category->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('categories')->where(function ($query) use ($tenantId, $category) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $category->id);
                })->ignore($category->id),
            ],
        ]);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadCategoryComponent',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('category.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('delete category')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Check if category belongs to tenant
        if ($category->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        if ($category->is_active === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // Check if category is being used as parent category in ProductCategory
        $usedInProductCategories = ProductCategory::where('parent_category_id', $category->id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($usedInProductCategories) {
            return response()->json([
                'success' => false,
                'message' => __('auth.category_used_in_product_categories'),
            ]);
        }

        $category->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadCategoryComponent',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('category.index'),
        ]);
    }

    public function changeCategoryStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update category')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $category = Category::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
    
        if ($category) {
            $category->is_active = $validated['status']; 
            if ($category->save()) {  // Save the user object
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'reloadCategoryComponent',
                    'message' => __('auth._updated'),
                    'redirect' => route('category.index'),
                ]);
            }
        }
    
        // If user not found or status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth._not_found'),
        ]);
    }
}
