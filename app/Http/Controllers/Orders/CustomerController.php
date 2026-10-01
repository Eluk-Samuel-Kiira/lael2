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
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view customer')) {
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

        $query = Customer::with(['group', 'customerCreater'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = $request->search;
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
            $query->where('group_id', $request->group_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', (int) $request->status);
        }

        $customers = $query->latest()->paginate($perPage);

        $customers->appends([
            'per_page' => $perPage,
            'search'   => $request->search,
            'group_id' => $request->group_id,
            'status'   => $request->status,
        ]);

        $bladeToReload = $request->query('bladeFileToReload');

        if ($request->ajax() && $bladeToReload === 'reloadCustomerComponent') {
            return view('orders.customer.customer-component', [
                'customers' => $customers,
            ])->render();
        }

        return view('orders.customer-index', [
            'customers' => $customers,
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