<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ PaymentMethod, Location };
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
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }
        
        // Get per_page from request, default to 15
        $perPage = $request->input('per_page', 15);
        
        // Validate per_page is in allowed values
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }
        
        // Build query with relationships
        $query = PaymentMethod::with('creator')
            ->where('tenant_id', $tenantId);
        
        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('provider', 'like', "%{$search}%")
                ->orWhere('account_number', 'like', "%{$search}%")
                ->orWhereHas('creator', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Filter by location if provided
        if ($request->filled('location_id')) {
            $locationId = $request->location_id;
            $query->where(function($q) use ($locationId) {
                $q->whereRaw('JSON_CONTAINS(location_id, ?)', [json_encode($locationId)])
                  ->orWhereNull('location_id');
            });
        }
        
        // Paginate with dynamic per_page
        $paymentMethods = $query->latest()->paginate($perPage);
        
        // Preserve per_page and search in pagination links
        $paymentMethods->appends(['per_page' => $perPage, 'search' => $request->search]);
        
        // Get locations for the filter dropdown
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        $bladeToReload = $request->query('bladeFileToReload');
        
        // For AJAX requests - return just the component HTML
        if ($request->ajax() && $bladeToReload === 'paymentMethodIndexTable') {
            return view('settings.payment-method.component', [
                'all_payment_methods' => $paymentMethods,
                'locations' => $locations,
            ])->render();
        }
        
        // Regular page load
        return view('settings.payment-method-index', [
            'all_payment_methods' => $paymentMethods,
            'locations' => $locations,
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
        
        if (!$user->hasPermissionTo('create payment method')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Get the actual max_payment_methods limit from tenant settings
        $maxPaymentMethods = tenant_limit('payment_methods', 1, $tenantId);

        // Count current payment methods
        $currentPaymentMethodCount = PaymentMethod::where('tenant_id', $tenantId)->count();

        // Check if limit is reached
        if ($currentPaymentMethodCount >= $maxPaymentMethods) {
            return response()->json([
                'success' => false,
                'message' => __('payments.maximum_payment_methods_reached', ['max' => $maxPaymentMethods]),
                'current' => $currentPaymentMethodCount,
                'limit' => $maxPaymentMethods
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
            // NEW: Location validation
            'location_id' => 'nullable|array',
            'location_id.*' => 'exists:locations,id',
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
            'location_id' => $request->location_id, // Store as array
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
    public function update(Request $request, $id)
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
            // NEW: Location validation
            'location_id' => 'nullable|array',
            'location_id.*' => 'exists:locations,id',
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
            'location_id' => $request->location_id, // Update as array
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
     * Get locations for a payment method
     */
    public function getLocations($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $paymentMethod = PaymentMethod::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => __('payments._not_found'),
            ]);
        }

        return response()->json([
            'success' => true,
            'locations' => $paymentMethod->location_id ?? [],
        ]);
    }

    /**
     * Show the transfer form (or return JSON for modal)
     */
    public function transferForm(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('transfer between payment methods')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        // Only active + non-deleted payment methods for this tenant
        $methods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance', 'available_balance', 'currency_id', 'allow_negative_balance']);

        // Get locations if you filter by them
        $locations = Location::where('tenant_id', $tenantId)->get();

        // Currency info for display
        $currencies = \App\Models\Currency::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get(['id', 'code', 'symbol']);

        return response()->json([
            'success'          => true,
            'payment_methods'  => $methods,
            'locations'        => $locations,
            'currencies'       => $currencies,
            'tenant_currency'  => currency_code(),
            'currency_symbol'  => currency_symbol(),
        ]);
    }

    /**
     * Preview a transfer (no DB writes) — used for JS confirmation.
     * ✅ Uses current_balance as the single source of truth.
     */
    public function transferPreview(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('transfer between payment methods')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $validated = $request->validate([
            'from_payment_method_id' => 'required|integer|exists:payment_methods,id',
            'to_payment_method_id'   => 'required|integer|different:from_payment_method_id|exists:payment_methods,id',
            'amount'                 => 'required|numeric|min:0.01',
            'description'            => 'nullable|string|max:500',
        ]);

        // Fetch both methods scoped to tenant
        $from = PaymentMethod::where('tenant_id', $tenantId)
            ->where('id', $validated['from_payment_method_id'])
            ->first();

        $to = PaymentMethod::where('tenant_id', $tenantId)
            ->where('id', $validated['to_payment_method_id'])
            ->first();

        if (!$from || !$to) {
            return response()->json(['success' => false, 'message' => __('payments._not_found')], 404);
        }

        $amount = (float) $validated['amount'];

        // ✅ Single source of truth: current_balance
        $fromBalance   = (float) $from->current_balance;
        $toBalance     = (float) $to->current_balance;
        $allowNegative = (bool)  $from->allow_negative_balance;

        $errors = [];

        if ($amount <= 0) {
            $errors[] = __('payments.amount_must_be_greater_than_zero');
        }

        if (!$allowNegative && $amount > $fromBalance) {
            $errors[] = __('payments.insufficient_balance');
        }

        if ($from->currency_id && $to->currency_id && $from->currency_id !== $to->currency_id) {
            $errors[] = __('payments.currencies_do_not_match');
        }

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $errors),
                'errors'  => $errors,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'preview' => [
                'from' => [
                    'id'             => $from->id,
                    'name'           => $from->name,
                    'type_label'     => $from->getTypeLabel(),
                    'balance_before' => $fromBalance,
                    'balance_after'  => $fromBalance - $amount,
                ],
                'to' => [
                    'id'             => $to->id,
                    'name'           => $to->name,
                    'type_label'     => $to->getTypeLabel(),
                    'balance_before' => $toBalance,
                    'balance_after'  => $toBalance + $amount,
                ],
                'amount'          => $amount,
                'currency'        => currency_code(),
                'currency_symbol' => currency_symbol(),
                'description'     => $validated['description'] ?? null,
                'reference'       => 'TRF-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'timestamp'       => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Execute a transfer — writes 2 ledger rows + updates both balances.
     *
     * Outgoing row: TRANSFER_OUT (debit on source)
     * Incoming row: TRANSFER_IN  (credit on destination)
     *
     * ✅ Uses current_balance as the single source of truth.
     */
    public function transfer(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('transfer between payment methods')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $validated = $request->validate([
            'from_payment_method_id' => 'required|integer|exists:payment_methods,id',
            'to_payment_method_id'   => 'required|integer|different:from_payment_method_id|exists:payment_methods,id',
            'amount'                 => 'required|numeric|min:0.01',
            'description'            => 'nullable|string|max:500',
        ]);

        try {
            $from = PaymentMethod::where('tenant_id', $tenantId)
                ->where('id', $validated['from_payment_method_id'])
                ->firstOrFail();

            $to = PaymentMethod::where('tenant_id', $tenantId)
                ->where('id', $validated['to_payment_method_id'])
                ->firstOrFail();

            $amount = (float) $validated['amount'];

            // ✅ Server-side balance validation against current_balance
            $fromBalance   = (float) $from->current_balance;
            $allowNegative = (bool)  $from->allow_negative_balance;

            if (!$allowNegative && $amount > $fromBalance) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.insufficient_balance'),
                ], 422);
            }

            if ($from->currency_id && $to->currency_id && $from->currency_id !== $to->currency_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.currencies_do_not_match'),
                ], 422);
            }

            $reference  = 'TRF-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            $currencyId = $from->currency_id
                ?? $to->currency_id
                ?? \App\Models\Currency::default()->id;

            $description = $validated['description']
                ?? "Fund transfer from {$from->name} to {$to->name}";

            $service = app('payment-transaction');

            // ── 1. OUTGOING LEG (debit source) ────────────────────────────
            $outgoingLog = $service->recordTransaction([
                'tenant_id'            => $tenantId,
                'user_id'              => $user->id,
                'payment_method_id'    => $from->id,
                'transaction_type'     => 'TRANSFER_OUT',
                'transaction_category' => 'OTHER',
                'amount'               => $amount,
                'currency_id'          => $currencyId,
                'reference_table'      => 'payment_methods',
                'reference_id'         => $to->id,
                'description'          => $description,
                'notes'                => 'Fund transfer (outgoing leg)',
                'counterparty_id'      => $to->id,
                'counterparty_name'    => $to->name,
                'counterparty_account' => $to->account_number ?? null,
                'metadata' => [
                    'transfer_reference' => $reference,
                    'direction'          => 'OUT',
                    'from_method'        => ['id' => $from->id, 'name' => $from->name, 'type' => $from->type],
                    'to_method'          => ['id' => $to->id,   'name' => $to->name,   'type' => $to->type],
                    'initiated_by'       => $user->name,
                ],
            ]);

            // ── 2. INCOMING LEG (credit destination) ──────────────────────
            $incomingLog = $service->recordTransaction([
                'tenant_id'            => $tenantId,
                'user_id'              => $user->id,
                'payment_method_id'    => $to->id,
                'transaction_type'     => 'TRANSFER_IN',
                'transaction_category' => 'OTHER',
                'amount'               => $amount,
                'currency_id'          => $currencyId,
                'reference_table'      => 'payment_methods',
                'reference_id'         => $from->id,
                'description'          => $description,
                'notes'                => 'Fund transfer (incoming leg)',
                'counterparty_id'      => $from->id,
                'counterparty_name'    => $from->name,
                'counterparty_account' => $from->account_number ?? null,
                'metadata' => [
                    'transfer_reference' => $reference,
                    'direction'          => 'IN',
                    'from_method'        => ['id' => $from->id, 'name' => $from->name, 'type' => $from->type],
                    'to_method'          => ['id' => $to->id,   'name' => $to->name,   'type' => $to->type],
                    'initiated_by'       => $user->name,
                ],
            ]);

            return response()->json([
                'success'   => true,
                'message'   => __('payments.transfer_completed'),
                'reference' => $reference,
                'transfer'  => [
                    'amount'   => $amount,
                    'currency' => currency_code(),
                    'from' => [
                        'id'             => $from->id,
                        'name'           => $from->name,
                        'balance_before' => $fromBalance,
                        'balance_after'  => $fromBalance - $amount,
                    ],
                    'to' => [
                        'id'             => $to->id,
                        'name'           => $to->name,
                        'balance_before' => (float) $to->current_balance,
                        'balance_after'  => (float) $to->current_balance + $amount,
                    ],
                    'outgoing_log_id' => $outgoingLog->id,
                    'incoming_log_id' => $incomingLog->id,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => __('payments._not_found'),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Transfer failed: ' . $e->getMessage(), [
                'trace'   => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



}