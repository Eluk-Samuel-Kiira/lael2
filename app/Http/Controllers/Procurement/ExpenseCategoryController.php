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
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('view category-expense')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query
        $query = ExpenseCategory::with('tenant');
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
        }
        
        $categories = $query->latest()->get();

        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'reloadExpenseCategoryComponent':
                return view('procurement.expense-category.category-component', [
                    'expense_categories' => $categories,
                ]);
            default:
                return view('procurement.expense-category-index', [
                    'expense_categories' => $categories,
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

    
}
