<?php
// app/Http/Controllers/Orders/CustomerController.php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Customer, CustomerGroup };
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Rules\ValidPhoneNumber;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('view customer')) {
            abort(403, __('payments.not_authorized'));
        }

        $groups = CustomerGroup::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('orders.customer-index', [
            'customerGroups' => $groups,
            'initialFilters' => [
                'search'   => (string) $request->get('search', ''),
                'group_id' => (string) $request->get('group_id', ''),
                'status'   => (string) $request->get('status', ''),
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

        if (!$user->hasPermissionTo('view customer')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = Customer::with(['group:id,name,discount_percentage', 'customerCreater:id,name'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('tax_number', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhereHas('group', fn($g) => $g->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', (int) $request->input('group_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', (int) $request->input('status'));
        }

        $all = (clone $query)->get(['id', 'is_active']);

        $summary = [
            'total'       => $all->count(),
            'active'      => $all->where('is_active', 1)->count(),
            'inactive'    => $all->where('is_active', 0)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(Customer $c) => [
            'id'         => $c->id,
            'first_name' => $c->first_name,
            'last_name'  => $c->last_name,
            'full_name'  => trim($c->first_name . ' ' . $c->last_name),
            'email'      => $c->email,
            'phone'      => $c->phone,
            'city'       => $c->city,
            'country_code' => $c->country_code,
            'tax_number' => $c->tax_number,
            'group_name' => $c->group?->name,
            'group_discount' => (float) ($c->group?->discount_percentage ?? 0),
            'is_active'  => (bool) $c->is_active,
            'creator_name' => $c->customerCreater?->name,
            'created_at' => optional($c->created_at)->format('d M Y'),
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

        if (!$user->hasPermissionTo('view customer')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $customer = Customer::with([
                'group:id,name,discount_percentage',
                'customerCreater:id,name,email',
            ])
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$customer) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        // Order counts (if relationship exists)
        $orderCount = 0;
        $totalSpent = 0.0;
        if (method_exists($customer, 'orders')) {
            $orderCount = $customer->orders()->count();
            $totalSpent = (float) ($customer->orders()->sum('total_amount') ?? 0);
        }

        $can = [
            'can_edit'   => $user->can('edit customer'),
            'can_delete' => $user->can('delete customer'),
            'can_toggle' => $user->can('edit customer'),
        ];

        return response()->json([
            'success'  => true,
            'customer' => array_merge([
                'id'         => $customer->id,
                'first_name' => $customer->first_name,
                'last_name'  => $customer->last_name,
                'full_name'  => trim($customer->first_name . ' ' . $customer->last_name),
                'email'      => $customer->email,
                'phone'      => $customer->phone,
                'birth_date' => optional($customer->birth_date)->format('Y-m-d'),
                'birth_date_human' => optional($customer->birth_date)->format('d M Y'),
                'tax_number' => $customer->tax_number,
                'address'    => $customer->address,
                'city'       => $customer->city,
                'state'      => $customer->state,
                'postal_code' => $customer->postal_code,
                'country_code' => $customer->country_code,

                'group_id'         => $customer->group_id,
                'group_name'       => $customer->group?->name,
                'group_discount'   => (float) ($customer->group?->discount_percentage ?? 0),

                'accepts_marketing' => (bool) $customer->accepts_marketing,
                'is_active'         => (bool) $customer->is_active,
                'notes'             => $customer->notes,

                'order_count'  => $orderCount,
                'total_spent'  => $totalSpent,

                'creator_name'  => $customer->customerCreater?->name,
                'creator_email' => $customer->customerCreater?->email,
                'created_at'    => optional($customer->created_at)->format('d M Y, H:i'),
                'updated_at'    => optional($customer->updated_at)->format('d M Y, H:i'),
            ], $can),
        ]);
    }

    /**
     * Store a new customer.
     */
    public function store(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create customer')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $validated = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'email'        => [
                'nullable', 'email', 'max:255',
                Rule::unique('customers')->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'phone'        => ['required', 'string', 'max:50', new ValidPhoneNumber],
            'group_id'     => 'nullable|exists:customer_groups,id',
            'birth_date'   => 'nullable|date',
            'tax_number'   => 'nullable|string|max:50',
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:100',
            'state'        => 'nullable|string|max:100',
            'postal_code'  => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2',
            'notes'        => 'nullable|string',
            'accepts_marketing' => 'boolean',
            'is_active'    => 'required|boolean',
        ]);

        // Guard: group must belong to this tenant
        if (!empty($validated['group_id'])) {
            $groupBelongs = CustomerGroup::where('id', $validated['group_id'])
                ->where('tenant_id', $tenantId)
                ->exists();

            if (!$groupBelongs) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized_access'),
                ], 422);
            }
        }

        Customer::create([
            'tenant_id'         => $tenantId,
            'group_id'          => $validated['group_id'] ?? null,
            'first_name'        => $validated['first_name'],
            'last_name'         => $validated['last_name'],
            'email'             => $validated['email'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'birth_date'        => $validated['birth_date'] ?? null,
            'tax_number'        => $validated['tax_number'] ?? null,
            'address'           => $validated['address'] ?? null,
            'city'              => $validated['city'] ?? null,
            'state'             => $validated['state'] ?? null,
            'postal_code'       => $validated['postal_code'] ?? null,
            'country_code'      => $validated['country_code'] ?? null,
            'notes'             => $validated['notes'] ?? null,
            'accepts_marketing' => $request->boolean('accepts_marketing'),
            'is_active'         => $validated['is_active'],
            'created_by'        => $user->id,
        ]);

        return response()->json([
            'success'     => true,
            'reload'      => true,
            'componentId' => 'reloadCustomerComponent',
            'refresh'     => false,
            'message'     => __('auth._created'),
            'redirect'    => route('customer.index'),
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit customer')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $customer = Customer::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        $validated = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'email'        => [
                'nullable', 'email', 'max:255',
                Rule::unique('customers')
                    ->where(fn($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($customer->id),
            ],
            'phone'        => ['required', 'string', 'max:50', new ValidPhoneNumber],
            'group_id'     => 'nullable|exists:customer_groups,id',
            'birth_date'   => 'nullable|date',
            'tax_number'   => 'nullable|string|max:50',
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:100',
            'state'        => 'nullable|string|max:100',
            'postal_code'  => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2',
            'notes'        => 'nullable|string',
            'accepts_marketing' => 'boolean',
            'is_active'    => 'required|boolean',
        ]);

        if (!empty($validated['group_id'])) {
            $groupBelongs = CustomerGroup::where('id', $validated['group_id'])
                ->where('tenant_id', $tenantId)
                ->exists();

            if (!$groupBelongs) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized_access'),
                ], 422);
            }
        }

        $customer->update([
            'group_id'          => $validated['group_id'] ?? null,
            'first_name'        => $validated['first_name'],
            'last_name'         => $validated['last_name'],
            'email'             => $validated['email'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'birth_date'        => $validated['birth_date'] ?? null,
            'tax_number'        => $validated['tax_number'] ?? null,
            'address'           => $validated['address'] ?? null,
            'city'              => $validated['city'] ?? null,
            'state'             => $validated['state'] ?? null,
            'postal_code'       => $validated['postal_code'] ?? null,
            'country_code'      => $validated['country_code'] ?? null,
            'notes'             => $validated['notes'] ?? null,
            'accepts_marketing' => $request->boolean('accepts_marketing'),
            'is_active'         => $validated['is_active'],
        ]);

        return response()->json([
            'success'     => true,
            'reload'      => true,
            'componentId' => 'reloadCustomerComponent',
            'refresh'     => false,
            'message'     => __('auth._updated'),
            'redirect'    => route('customer.index'),
        ]);
    }

    /**
     * Remove the specified customer.
     */
    public function destroy($id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('delete customer')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $customer = Customer::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        // Block deletion if the customer has orders
        if (method_exists($customer, 'orders') && $customer->orders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.customer_has_orders'),
            ]);
        }

        $customer->delete();

        return response()->json([
            'success'     => true,
            'reload'      => true,
            'componentId' => 'reloadCustomerComponent',
            'refresh'     => false,
            'message'     => __('auth._deleted'),
            'redirect'    => route('customer.index'),
        ]);
    }

    /**
     * Toggle customer status (used by the inline select in the table).
     */
    public function updateStatus(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit customer')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $customer = Customer::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        $customer->update(['is_active' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => __('auth._updated'),
        ]);
    }
}