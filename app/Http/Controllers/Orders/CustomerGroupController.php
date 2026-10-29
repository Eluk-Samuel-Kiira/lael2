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
        $user = Auth::user();

        if (!$user->hasPermissionTo('view customer-group')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('orders.customer-group-index', [
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

        if (!$user->hasPermissionTo('view customer-group')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = CustomerGroup::with('customerGroupCreater:id,name')
            ->withCount('customers')
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_default')) {
            $query->where('is_default', (int) $request->input('is_default'));
        }

        $all = (clone $query)->withCount('customers')->get(['id', 'is_default']);

        $summary = [
            'total'         => $all->count(),
            'default'       => $all->where('is_default', 1)->count(),
            'with_customers'=> $all->where('customers_count', '>', 0)->count(),
            'total_customers' => $all->sum('customers_count'),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(CustomerGroup $g) => [
            'id'                  => $g->id,
            'name'                => $g->name,
            'discount_percentage' => (float) $g->discount_percentage,
            'is_default'          => (bool) $g->is_default,
            'customers_count'     => (int) ($g->customers_count ?? 0),
            'creator_name'        => $g->customerGroupCreater?->name,
            'created_at'          => optional($g->created_at)->format('d M Y'),
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

        if (!$user->hasPermissionTo('view customer-group')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $group = CustomerGroup::with(['customerGroupCreater:id,name,email'])
            ->withCount('customers')
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$group) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        // Sample of customers (first 10)
        $customers = Customer::where('group_id', $group->id)
            ->where('tenant_id', $tenantId)
            ->orderBy('first_name')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        $can = [
            'can_edit'   => $user->can('edit customer-group'),
            'can_delete' => $user->can('delete customer-group') && !$group->is_default && $group->customers_count === 0,
        ];

        return response()->json([
            'success' => true,
            'group'   => array_merge([
                'id'                  => $group->id,
                'name'                => $group->name,
                'discount_percentage' => (float) $group->discount_percentage,
                'is_default'          => (bool) $group->is_default,
                'customers_count'     => (int) $group->customers_count,
                'creator_name'        => $group->customerGroupCreater?->name,
                'creator_email'       => $group->customerGroupCreater?->email,
                'created_at'          => optional($group->created_at)->format('d M Y, H:i'),
                'updated_at'          => optional($group->updated_at)->format('d M Y, H:i'),
                'sample_customers'    => $customers->map(fn($c) => [
                    'id'        => $c->id,
                    'name'      => trim($c->first_name . ' ' . $c->last_name),
                    'email'     => $c->email,
                    'phone'     => $c->phone,
                ])->values(),
            ], $can),
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