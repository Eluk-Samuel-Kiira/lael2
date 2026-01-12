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
        $tenantId = current_tenant_id();
        
        // Build the query - ALWAYS include currency with ID 1 (USD base currency)
        $query = Currency::where(function($query) use ($tenantId, $user) {
            $query->where('id', 1) // Always include base currency (USD)
                ->orWhere('tenant_id', $tenantId); // Include tenant's currencies
        });
        
        // If user is super_admin, also show all other currencies
        if ($user->hasRole('super_admin')) {
            $query->orWhere(function($query) {
                $query->where('id', '!=', 1) // Exclude base currency (already included)
                    ->whereNotNull('tenant_id'); // Include all tenant currencies
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

        // Check for USD or $ restrictions
        if (strtoupper($request->code) === 'USD') {
            return response()->json([
                'success' => false,
                'message' => __('auth.usd_currency_reserved'),
            ]);
        }

        if ($request->symbol === '$') {
            return response()->json([
                'success' => false,
                'message' => __('auth.dollar_symbol_reserved'),
            ]);
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
            'symbol' => 'required',
            'exchange_rate' => 'required',
        ]);

        $currency = Currency::create([
            'code' => $request->code,
            'name' => $request->name,
            'symbol' => $request->symbol,
            'exchange_rate' => $request->exchange_rate,
            'created_by' => $user->id,
            'tenant_id' => $tenantId, // Make sure to set tenant_id
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'currencyIndexTable',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('currency.index'),
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

        // Check for USD or $ restrictions
        if (strtoupper($request->code) === 'USD') {
            return response()->json([
                'success' => false,
                'message' => __('auth.usd_currency_reserved'),
            ]);
        }

        if ($request->symbol === '$') {
            return response()->json([
                'success' => false,
                'message' => __('auth.dollar_symbol_reserved'),
            ]);
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
            'symbol' => 'required',
            'exchange_rate' => 'required',
        ]);

        // Protect the global currency (ID 1) and USD code
        if ($currency->id === 1 || $currency->code === 'USD') {
            return response()->json([
                'success' => false,
                'message' => __('auth.default_currency_cannot_be_modified'),
            ]);
        }

        // Also check if the currency belongs to the current tenant
        if ($currency->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        $currency->update([
            'code' => $request->code,
            'name' => $request->name,
            'symbol' => $request->symbol,
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

        // Detailed protection checks with specific messages
        if ($currency->id === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.global_currency_protected'),
            ]);
        }

        if ($currency->isActive === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.active_currency_protected'),
            ]);
        }

        if ($currency->name === 'USD' || $currency->code === 'USD') {
            return response()->json([
                'success' => false,
                'message' => __('auth.usd_currency_protected'),
            ]);
        }

        if ($currency->is_default === true) {
            return response()->json([
                'success' => false,
                'message' => __('auth.default_currency_protected'),
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
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
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

        // Protect USD currency and global currency (ID 1)
        if ($currency->code === 'USD' || $currency->id === 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.default_currency_cannot_be_modified'),
            ]);
        }

        // Update the currency status
        $currency->isActive = $validated['status'];
        
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

        // If status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth.update_failed'),
        ]);
    }
}
