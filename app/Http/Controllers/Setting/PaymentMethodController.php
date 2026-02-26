<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ PaymentMethod };
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Auth, Log, Storage };
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('view payment method')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Get payment methods for current tenant
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->with('creator')
            ->latest()
            ->get();
        
        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'paymentMethodIndexTable':
                return view('settings.payment-method.component', [
                    'all_payment_methods' => $paymentMethods,
                ]);
            default:
                return view('settings.payment-method-index', [
                    'all_payment_methods' => $paymentMethods,
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
        if (!$user->hasPermissionTo('create payment method')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payment_methods')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'type' => 'required|in:bank_account,digital_wallet,card,cash,check,mobile_money,other',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_methods')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'description' => 'nullable|string',
            'provider' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'current_balance' => 'required|numeric',
            'transaction_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'transaction_fee_fixed' => 'nullable|numeric|min:0',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_online' => 'boolean',
            'is_verified' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if ($request->is_default) {
            PaymentMethod::where('tenant_id', $tenantId)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $paymentMethod = PaymentMethod::create([
            'name' => $request->name,
            'type' => $request->type,
            'code' => $request->code,
            'description' => $request->description,
            'provider' => $request->provider,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'account_number' => $request->account_number,
            'current_balance' => $request->current_balance,
            'transaction_fee_percentage' => $request->transaction_fee_percentage ?? 0,
            'transaction_fee_fixed' => $request->transaction_fee_fixed ?? 0,
            'min_transaction_amount' => $request->min_transaction_amount ?? 0,
            'max_transaction_amount' => $request->max_transaction_amount,
            'daily_limit' => $request->daily_limit,
            'monthly_limit' => $request->monthly_limit,
            'is_active' => $request->is_active ?? true,
            'is_default' => $request->is_default ?? false,
            'is_online' => $request->is_online ?? true,
            'is_verified' => $request->is_verified ?? false,
            'created_by' => $user->id,
            'tenant_id' => $tenantId,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'paymentMethodIndexTable',
            'refresh' => false,
            'message' => __('auth._created'),
            'redirect' => route('paymentmethod.index'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,  $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('edit payment method')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $paymentMethod = PaymentMethod::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        // Check if payment method belongs to current tenant
        if ($paymentMethod->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized_access'),
            ]);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payment_methods')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($paymentMethod->id),
            ],
            'type' => 'required|in:bank_account,digital_wallet,card,cash,check,mobile_money,other',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_methods')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($paymentMethod->id),
            ],
            'description' => 'nullable|string',
            'provider' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'current_balance' => 'required|numeric',
            'transaction_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'transaction_fee_fixed' => 'nullable|numeric|min:0',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_online' => 'boolean',
            'is_verified' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if ($request->is_default && !$paymentMethod->is_default) {
            PaymentMethod::where('tenant_id', $tenantId)
                ->where('is_default', true)
                ->where('id', '!=', $paymentMethod->id)
                ->update(['is_default' => false]);
        }

        $paymentMethod->update([
            'name' => $request->name,
            'type' => $request->type,
            'code' => $request->code,
            'description' => $request->description,
            'provider' => $request->provider,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'current_balance' => $request->current_balance,
            'transaction_fee_percentage' => $request->transaction_fee_percentage ?? 0,
            'transaction_fee_fixed' => $request->transaction_fee_fixed ?? 0,
            'min_transaction_amount' => $request->min_transaction_amount ?? 0,
            'max_transaction_amount' => $request->max_transaction_amount,
            'daily_limit' => $request->daily_limit,
            'monthly_limit' => $request->monthly_limit,
            'is_active' => $request->is_active ?? true,
            'is_default' => $request->is_default ?? false,
            'is_online' => $request->is_online ?? true,
            'is_verified' => $request->is_verified ?? false,
        ]);

        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'paymentMethodIndexTable',
            'refresh' => false,
            'message' => __('auth._updated'),
            'redirect' => route('paymentmethod.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('delete payment method')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $paymentMethod = PaymentMethod::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        // Check if payment method belongs to current tenant
        if ($paymentMethod->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('payments.unauthorized_access'),
            ]);
        }

        // Check if it's the default payment method
        if ($paymentMethod->is_default) {
            return response()->json([
                'success' => false,
                'message' => __('payments.default_payment_method_protected'),
            ]);
        }

        // Check if it's active
        if ($paymentMethod->is_active) {
            return response()->json([
                'success' => false,
                'message' => __('payments.active_payment_method_protected'),
            ]);
        }

        // Check if it's being used in any payments
        if ($paymentMethod->transactionLogs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('payments.payment_method_in_use'),
            ]);
        }

        $paymentMethod->delete();
        
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'paymentMethodIndexTable',
            'refresh' => false,
            'message' => __('auth._deleted'),
            'redirect' => route('paymentmethod.index'),
        ]);
    }

    /**
     * Change payment method status
     */
    public function changePaymentMethodStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('update payment method')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',
        ]);

        $paymentMethod = PaymentMethod::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        // Check if payment method exists
        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => __('payments._not_found'),
            ]);
        }

        // Check if payment method belongs to current tenant
        if ($paymentMethod->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => __('payments.unauthorized_access'),
            ]);
        }

        // Check if trying to deactivate default payment method
        if ($validated['status'] == 0 && $paymentMethod->is_default) {
            return response()->json([
                'success' => false,
                'message' => __('payments.cannot_deactivate_default_payment_method'),
            ]);
        }

        // Update the payment method status
        $paymentMethod->is_active = $validated['status'];
        
        if ($paymentMethod->save()) {
            $statusMessage = $validated['status'] == 1 
                ? __('payments.payment_method_activated') 
                : __('payments.payment_method_deactivated');
            
            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'paymentMethodIndexTable',
                'message' => $statusMessage,
                'redirect' => route('paymentmethod.index'),
            ]);
        }

        // If status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth.update_failed'),
        ]);
    }

    /**
     * Set default payment method
     */
    // public function setDefaultPaymentMethod(Request $request, $id)
    // {
    //     $user = Auth::user();
    //     $tenantId = $user->tenant_id;

    //     $paymentMethod = PaymentMethod::find($id);

    //     if (!$paymentMethod) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => __('payments._not_found'),
    //         ]);
    //     }

    //     // Check if payment method belongs to current tenant
    //     if ($paymentMethod->tenant_id !== $tenantId) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => __('payments.unauthorized_access'),
    //         ]);
    //     }

    //     // Check if payment method is active
    //     if (!$paymentMethod->is_active) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => __('payments.inactive_payment_method_cannot_be_default'),
    //         ]);
    //     }

    //     // Unset other defaults
    //     PaymentMethod::where('tenant_id', $tenantId)
    //         ->where('is_default', true)
    //         ->update(['is_default' => false]);

    //     // Set new default
    //     $paymentMethod->update(['is_default' => true]);

    //     return response()->json([
    //         'success' => true,
    //         'reload' => true,
    //         'refresh' => false,
    //         'componentId' => 'paymentMethodIndexTable',
    //         'message' => __('payments.default_payment_method_updated'),
    //         'redirect' => route('payment-methods.index'),
    //     ]);
    // }
}
