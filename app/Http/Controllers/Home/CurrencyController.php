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
        $tenantId = $user->tenant_id;
        
        $query = Currency::where('tenant_id', $tenantId);
        
        // If user is super_admin, also show all other currencies
        if ($user->hasRole('super_admin')) {
            $query->orWhere(function($query) use ($tenantId) {
                $query->where('tenant_id', '!=', $tenantId)
                    ->whereNotNull('tenant_id');
            });
        }
        
        $currencies = $query->latest()->get();
        
        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'currencyIndexTable':
                return view('settings.currency.currency-component', [
                    'all_currencies' => $currencies,
                ]);
            default:
                return view('settings.currency-index', [
                    'all_currencies' => $currencies,
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
