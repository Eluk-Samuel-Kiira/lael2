<?php
// app/Http/Controllers/Orders/CustomerGroupController.php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Customer, CustomerGroup };
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerGroupController extends Controller
{
    /**
     * Display a listing of customer groups.
     */
    public function index(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view customer-group')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }

        $perPage = $request->input('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }

        $query = CustomerGroup::with('customerGroupCreater')
            ->withCount('customers')
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $customerGroups = $query->latest()->paginate($perPage);

        $customerGroups->appends([
            'per_page' => $perPage,
            'search'   => $request->search,
        ]);

        $bladeToReload = $request->query('bladeFileToReload');

        if ($request->ajax() && $bladeToReload === 'reloadCustomerGroupComponent') {
            return view('orders.customer-group.customer-group-component', [
                'customerGroups' => $customerGroups,
            ])->render();
        }

        return view('orders.customer-group-index', [
            'customerGroups' => $customerGroups,
        ]);
    }

    /**
     * Store a new customer group.
     */
    public function store(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create customer-group')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('customer_groups')
                    ->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'discount_percentage' => 'required|numeric|min:0|max:100|decimal:0,2',
            'is_default'          => 'boolean',
        ]);

        // If this group is marked default, unset the previous default
        // so only one default exists per tenant.
        if ($request->boolean('is_default')) {
            CustomerGroup::where('tenant_id', $tenantId)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        CustomerGroup::create([
            'tenant_id'           => $tenantId,
            'name'                => $validated['name'],
            'discount_percentage' => $validated['discount_percentage'],
            'is_default'          => $request->boolean('is_default'),
            'created_by'          => $user->id,
        ]);

        return response()->json([
            'success'     => true,
            'reload'      => true,
            'componentId' => 'reloadCustomerGroupComponent',
            'refresh'     => false,
            'message'     => __('auth._created'),
            'redirect'    => route('customer-group.index'),
        ]);
    }

    /**
     * Update the specified customer group.
     */
    public function update(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit customer-group')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $group = CustomerGroup::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('customer_groups')
                    ->where(fn($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($group->id),
            ],
            'discount_percentage' => 'required|numeric|min:0|max:100|decimal:0,2',
            'is_default'          => 'boolean',
        ]);

        // If this group is being set as default, unset the previous default.
        if ($request->boolean('is_default')) {
            CustomerGroup::where('tenant_id', $tenantId)
                ->where('is_default', true)
                ->where('id', '!=', $group->id)
                ->update(['is_default' => false]);
        }

        $group->update([
            'name'                => $validated['name'],
            'discount_percentage' => $validated['discount_percentage'],
            'is_default'          => $request->boolean('is_default'),
        ]);

        return response()->json([
            'success'     => true,
            'reload'      => true,
            'componentId' => 'reloadCustomerGroupComponent',
            'refresh'     => false,
            'message'     => __('auth._updated'),
            'redirect'    => route('customer-group.index'),
        ]);
    }

    /**
     * Remove the specified customer group.
     */
    public function destroy($id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('delete customer-group')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $group = CustomerGroup::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        // Block deletion when customers still reference the group
        $hasCustomers = Customer::where('group_id', $group->id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasCustomers) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.group_has_customers'),
            ]);
        }

        // Optional guard: don't allow deleting the default group
        if ($group->is_default) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.cannot_delete_default_group'),
            ]);
        }

        $group->delete();

        return response()->json([
            'success'     => true,
            'reload'      => true,
            'componentId' => 'reloadCustomerGroupComponent',
            'refresh'     => false,
            'message'     => __('auth._deleted'),
            'redirect'    => route('customer-group.index'),
        ]);
    }
}