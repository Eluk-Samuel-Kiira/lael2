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

        if (!$user->hasPermissionTo('view category')) {
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

        return view('inventory.category-index', [
            'initialFilters' => $initialFilters,
        ]);
    }

    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view category')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $search  = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = Category::with('categoryCreater:id,name');

        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('categoryCreater', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $summary = [
            'total'    => (clone $query)->count(),
            'active'   => (clone $query)->where('is_active', 1)->count(),
            'inactive' => (clone $query)->where('is_active', 0)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(Category $c) => [
            'id'          => $c->id,
            'name'        => $c->name,
            'slug'        => $c->slug,
            'description' => $c->description,
            'is_active'   => (bool) $c->is_active,
            'creator'     => $c->categoryCreater?->name ?? '—',
            'created_at'  => optional($c->created_at)->format('d M Y, h:i a'),
            'created_iso' => optional($c->created_at)->format('Y-m-d H:i'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $rows,
            'summary' => $summary,
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
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view category')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        if ($category->tenant_id !== $tenantId && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        $category->load('categoryCreater:id,name,email');

        // Usage counts
        $usedAsParent = ProductCategory::where('parent_category_id', $category->id)
            ->where('tenant_id', $category->tenant_id)
            ->count();

        $productCount = \App\Models\Product::whereHas('category', fn($q) => $q->where('category_id', $category->id))
            ->count();
            // ^ adjust to your real relation name if different

        return response()->json([
            'success' => true,
            'category' => [
                'id'           => $category->id,
                'name'         => $category->name,
                'slug'         => $category->slug,
                'description'  => $category->description,
                'is_active'    => (bool) $category->is_active,
                'creator'      => [
                    'name'  => $category->categoryCreater?->name ?? '—',
                    'email' => $category->categoryCreater?->email,
                ],
                'created_at'   => optional($category->created_at)->format('d M Y, h:i a'),
                'updated_at'   => optional($category->updated_at)->format('d M Y, h:i a'),
                'used_as_parent' => $usedAsParent,
                'product_count'  => $productCount,
                'can_edit'       => $user->can('edit category'),
                'can_delete'     => $user->can('delete category') && !$category->is_active,
                'can_toggle'     => $user->can('update category'),
            ],
        ]);
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
