<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ ExpenseCategory, Expense };
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('view category-expense')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('procurement.expense-category-index', [
            'initialFilters' => [
                'search'   => (string) $request->get('search', ''),
                'per_page' => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                    ? (int) $request->get('per_page', 15)
                    : 15,
            ],
        ]);
    }

    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view category-expense')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = ExpenseCategory::where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', (int) $request->input('status'));
        }

        // Summary over the filtered set
        $all = (clone $query)->get(['id', 'is_active']);

        $summary = [
            'total'    => $all->count(),
            'active'   => $all->where('is_active', 1)->count(),
            'inactive' => $all->where('is_active', 0)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(ExpenseCategory $c) => [
            'id'                => $c->id,
            'name'              => $c->name,
            'code'              => $c->code,
            'description'       => $c->description,
            'gl_account_id'     => $c->gl_account_id,
            'is_active'         => (bool) $c->is_active,
            'requires_receipt'  => (bool) $c->requires_receipt,
            'requires_approval' => (bool) $c->requires_approval,
            'budget_monthly'    => (float) $c->budget_monthly,
            'budget_annual'     => (float) $c->budget_annual,
            'created_at'        => optional($c->created_at)->format('d M Y'),
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
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function drawerData(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view category-expense')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $category = ExpenseCategory::where('tenant_id', $tenantId)->find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => __('auth.category_not_found')], 404);
        }

        // How many expenses reference this category
        $expenseCount = Expense::where('category_id', $category->id)
            ->where('tenant_id', $tenantId)
            ->count();

        $can = [
            'can_edit'   => $user->can('edit category-expense'),
            'can_delete' => $user->can('delete category-expense'),
            'can_toggle' => $user->can('update category-expense'),
        ];

        return response()->json([
            'success'  => true,
            'category' => array_merge([
                'id'                => $category->id,
                'name'              => $category->name,
                'code'              => $category->code,
                'description'       => $category->description,
                'gl_account_id'     => $category->gl_account_id,
                'is_active'         => (bool) $category->is_active,
                'requires_receipt'  => (bool) $category->requires_receipt,
                'requires_approval' => (bool) $category->requires_approval,
                'budget_monthly'    => (float) $category->budget_monthly,
                'budget_annual'     => (float) $category->budget_annual,
                'expense_count'     => $expenseCount,
                'created_at'        => optional($category->created_at)->format('d M Y, H:i'),
                'updated_at'        => optional($category->updated_at)->format('d M Y, H:i'),
            ], $can),
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
        if (!$user->hasPermissionTo('create category-expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validation rules
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('expense_categories')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('expense_categories')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'description' => 'nullable|string',
            'gl_account_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'required|boolean',
            'requires_receipt' => 'boolean',
            'requires_approval' => 'boolean',
            'budget_monthly' => 'nullable|numeric|min:0|decimal:0,2',
            'budget_annual' => 'nullable|numeric|min:0|decimal:0,2',
        ]);

        // Prepare data for creation
        $expenseCategoryData = [
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'gl_account_id' => $validated['gl_account_id'] ?? null,
            'is_active' => $validated['is_active'],
            'requires_receipt' => $request->boolean('requires_receipt'),
            'requires_approval' => $request->boolean('requires_approval'),
            'budget_monthly' => $validated['budget_monthly'] ?? null,
            'budget_annual' => $validated['budget_annual'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Create the expense category
        ExpenseCategory::create($expenseCategoryData);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadExpenseCategoryComponent',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('expense-category.index'), // Make sure this route exists
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
    public function update(Request $request, $id)
    {
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('edit category-expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        

        $category = ExpenseCategory::where('id', $id)->where('tenant_id', $tenantId)->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => __('auth.category_not_found'),
            ]);
        }

        // Check if category belongs to tenant
        if ($category->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        // Validation rules
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('expense_categories')->where(function ($query) use ($tenantId, $category) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $category->id);
                })->ignore($category->id),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('expense_categories')->where(function ($query) use ($tenantId, $category) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $category->id);
                })->ignore($category->id),
            ],
            'description' => 'nullable|string',
            'gl_account_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'required|boolean',
            'requires_receipt' => 'boolean',
            'requires_approval' => 'boolean',
            'budget_monthly' => 'nullable|numeric|min:0|decimal:0,2',
            'budget_annual' => 'nullable|numeric|min:0|decimal:0,2',
        ]);

        // Prepare update data
        $updateData = [
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'gl_account_id' => $request->gl_account_id,
            'is_active' => $request->is_active,
            'requires_receipt' => $request->boolean('requires_receipt'),
            'requires_approval' => $request->boolean('requires_approval'),
            'budget_monthly' => $request->budget_monthly,
            'budget_annual' => $request->budget_annual,
            'updated_at' => now(),
        ];

        // Update the category
        $category->update($updateData);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadExpenseCategoryComponent',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('expense-category.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('delete category-expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find the category
        $category = ExpenseCategory::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => __('auth.category_not_found'),
            ]);
        }

        // Check if category belongs to tenant
        if ($category->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        // Check if category is active
        if ($category->is_active === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.deactivate_first'),
            ]);
        }

        // Check if category has any expenses
        $hasExpenses = Expense::where('category_id', $category->id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasExpenses) {
            return response()->json([
                'success' => false,
                'message' => __('auth.category_has_expenses'),
            ]);
        }

        $category->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'reloadExpenseCategoryComponent',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('expense-categories.index'),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update category-expense')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $validated = $request->validate(['status' => 'required|in:1,0']);

        $category = ExpenseCategory::where('tenant_id', $tenantId)->find($id);
        if (!$category) {
            return response()->json(['success' => false, 'message' => __('auth.category_not_found')], 404);
        }

        $category->is_active = (int) $validated['status'];
        $category->save();

        return response()->json([
            'success' => true,
            'message' => __('auth._updated'),
        ]);
    }

}
