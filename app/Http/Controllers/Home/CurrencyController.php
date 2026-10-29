<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('view currency')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('settings.currency-index', [
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

        if (!$user->hasPermissionTo('view currency')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = Currency::with('currencyCreater:id,name')
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('symbol', 'like', "%{$search}%")
                ->orWhereHas('currencyCreater', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', (int) $request->input('status'));
        }

        $all = (clone $query)->get(['id', 'is_active', 'is_base_currency']);

        $summary = [
            'total'    => $all->count(),
            'active'   => $all->where('is_active', 1)->count(),
            'inactive' => $all->where('is_active', 0)->count(),
            'base'     => $all->where('is_base_currency', 1)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(Currency $c) => [
            'id'                => $c->id,
            'code'              => $c->code,
            'name'              => $c->name,
            'symbol'            => $c->symbol,
            'symbol_position'   => $c->symbol_position,
            'decimal_places'    => (int) $c->decimal_places,
            'exchange_rate'     => (float) $c->exchange_rate,
            'is_base_currency'  => (bool) $c->is_base_currency,
            'is_active'         => (bool) $c->is_active,
            'creator_name'      => $c->currencyCreater?->name,
            'created_at'        => optional($c->created_at)->format('d M Y, H:i'),
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

        if (!$user->hasPermissionTo('view currency')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $currency = Currency::with('currencyCreater:id,name,email')
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$currency) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        // Base currency check — must not be edited/deleted/deactivated
        $isBase = (bool) $currency->is_base_currency;

        $can = [
            'can_edit'   => $user->can('edit currency')   && !$isBase,
            'can_delete' => $user->can('delete currency') && !$isBase && !$currency->is_active,
            'can_toggle' => $user->can('update currency') && !$isBase,
        ];

        return response()->json([
            'success'  => true,
            'currency' => array_merge([
                'id'               => $currency->id,
                'code'             => $currency->code,
                'name'             => $currency->name,
                'symbol'           => $currency->symbol,
                'symbol_position'  => $currency->symbol_position,
                'decimal_places'   => (int) $currency->decimal_places,
                'exchange_rate'    => (float) $currency->exchange_rate,
                'is_base_currency' => $isBase,
                'is_active'        => (bool) $currency->is_active,
                'creator_name'     => $currency->currencyCreater?->name,
                'creator_email'    => $currency->currencyCreater?->email,
                'created_at'       => optional($currency->created_at)->format('d M Y, H:i'),
                'updated_at'       => optional($currency->updated_at)->format('d M Y, H:i'),
                'tenant_currency'  => tenant_currency(),
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
                
        if (!$user->hasPermissionTo('create currency')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Get the tenant's base currency
        $baseCurrency = Currency::where('tenant_id', $tenantId)
            ->where('is_base_currency', true)
            ->first();

        // If base currency exists, check against it
        if ($baseCurrency) {
            // Check if trying to create currency with same code as base currency
            if (strtoupper($request->code) === $baseCurrency->code) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.base_currency_code_reserved', ['code' => $baseCurrency->code]),
                ]);
            }

            // Check if trying to use base currency's symbol
            if ($request->symbol === $baseCurrency->symbol) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.base_currency_symbol_reserved', ['symbol' => $baseCurrency->symbol]),
                ]);
            }
        }

        $request->validate([
            'code' => [
                'required',
                'alpha',
                'size:3',
                Rule::unique('currencies')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'name' => [
                'required',
                'string',
                Rule::unique('currencies')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'symbol' => 'required|string|max:10',
            'symbol_position' => 'sometimes|in:before,after',
            'decimal_places' => 'sometimes|integer|min:0|max:4',
            'exchange_rate' => 'required|numeric|min:0.00000001',
            'is_base_currency' => 'sometimes|boolean',
        ]);

        // Check if trying to set as base currency when one already exists
        if ($request->is_base_currency) {
            $existingBase = Currency::where('tenant_id', $tenantId)
                ->where('is_base_currency', true)
                ->exists();
                
            if ($existingBase) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.base_currency_already_exists'),
                ]);
            }
        }

        $currency = Currency::create([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'symbol' => $request->symbol,
            'symbol_position' => $request->symbol_position ?? 'before',
            'decimal_places' => $request->decimal_places ?? 2,
            'exchange_rate' => $request->exchange_rate,
            'created_by' => $user->id,
            'tenant_id' => $tenantId,
            'is_active' => $request->is_active ?? true,
            'is_base_currency' => $request->is_base_currency ?? false,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'currencyIndexTable',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('currency.index'),
            'currency' => $currency
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Currency $currency)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Currency $currency)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Currency $currency)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('edit currency')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Get the tenant's base currency
        $baseCurrency = Currency::where('tenant_id', $tenantId)
            ->where('is_base_currency', true)
            ->first();

        // If this currency is the base currency, protect it
        if ($currency->is_base_currency) {
            return response()->json([
                'success' => false,
                'message' => __('auth.base_currency_cannot_be_modified'),
            ]);
        }

        // If base currency exists, check against it
        if ($baseCurrency) {
            // Check if trying to update currency with same code as base currency
            if (strtoupper($request->code) === $baseCurrency->code) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.base_currency_code_reserved', ['code' => $baseCurrency->code]),
                ]);
            }

            // Check if trying to use base currency's symbol
            if ($request->symbol === $baseCurrency->symbol) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.base_currency_symbol_reserved', ['symbol' => $baseCurrency->symbol]),
                ]);
            }
        }

        $request->validate([
            'code' => [
                'required',
                'alpha',
                'size:3',
                Rule::unique('currencies')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($currency->id),
            ],
            'name' => [
                'required',
                'string',
                Rule::unique('currencies')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($currency->id),
            ],
            'symbol' => 'required|string|max:10',
            'symbol_position' => 'sometimes|in:before,after',
            'decimal_places' => 'sometimes|integer|min:0|max:4',
            'exchange_rate' => 'required|numeric|min:0.00000001',
        ]);

        // Check if the currency belongs to the current tenant
        if ($currency->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        $currency->update([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'symbol' => $request->symbol,
            'symbol_position' => $request->symbol_position ?? $currency->symbol_position,
            'decimal_places' => $request->decimal_places ?? $currency->decimal_places,
            'exchange_rate' => $request->exchange_rate,
            'created_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'currencyIndexTable',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('currency.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Currency $currency)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('delete currency')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Check if currency belongs to current tenant
        if ($currency->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        // Get the tenant's base currency
        $baseCurrency = Currency::where('tenant_id', $tenantId)
            ->where('is_base_currency', true)
            ->first();

        // Protect the base currency
        if ($baseCurrency && $currency->id === $baseCurrency->id) {
            return response()->json([
                'success' => false,
                'message' => __('auth.base_currency_cannot_be_deleted'),
            ]);
        }

        // Check if currency is attached to any location
        if ($currency->locations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('auth.currency_in_use'),
            ]);
        }

        // Cannot delete active currency
        if ($currency->is_active) {
            return response()->json([
                'success' => false,
                'message' => __('auth.active_currency_protected'),
            ]);
        }

        $currency->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'currencyIndexTable',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('currency.index'),
        ]);
    }

    public function changeCurrencyStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

                
        if (!$user->hasPermissionTo('update currency')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',
        ]);

        $currency = Currency::find($id);

        // Check if currency exists
        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if currency belongs to current tenant
        if ($currency->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        // Get the tenant's base currency
        $baseCurrency = Currency::where('tenant_id', $tenantId)
            ->where('is_base_currency', true)
            ->first();

        // Cannot deactivate the base currency
        if ($baseCurrency && $currency->id === $baseCurrency->id && $validated['status'] == 0) {
            return response()->json([
                'success' => false,
                'message' => __('auth.base_currency_cannot_be_deactivated'),
            ]);
        }

        // Update the currency status
        $currency->is_active = $validated['status'];
        
        if ($currency->save()) {
            $statusMessage = $validated['status'] == 1 
                ? __('auth.currency_activated') 
                : __('auth.currency_deactivated');
            
            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'currencyIndexTable',
                'message' => $statusMessage,
                'redirect' => route('currency.index'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('auth.update_failed'),
        ]);
    }
}
