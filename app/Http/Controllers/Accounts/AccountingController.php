<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\PaymentTransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB };

class AccountingController extends Controller
{
    // 1. Payment Methods Report
    public function paymentMethods(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get location filter from request
        $locationId = $request->get('location_id');
        $userLocationId = $user->location_id ?? null;
        
        // Build query
        $query = PaymentMethod::where('tenant_id', $tenantId)
            ->with(['currency']);
        
        // Apply location filter if provided
        if ($locationId) {
            // Filter by specific location
            $query->where(function($q) use ($locationId) {
                $q->whereNull('location_id')
                ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
            });
        } elseif ($userLocationId) {
            // Auto-filter by user's location if no filter specified
            $query->where(function($q) use ($userLocationId) {
                $q->whereNull('location_id')
                ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$userLocationId)]);
            });
        }
        // If no location filter and user has no location, return all
        
        $paymentMethods = $query->get()
            ->map(function($method) {
                // Convert balances from cents to base currency
                $method->current_balance_display = from_base_currency($method->current_balance);
                $method->available_balance_display = from_base_currency($method->available_balance);
                $method->pending_balance_display = from_base_currency($method->pending_balance);
                return $method;
            });
        
        $stats = [
            'total_payment_methods' => $paymentMethods->count(),
            'total_balance' => from_base_currency($paymentMethods->sum('current_balance')),
            'average_balance' => from_base_currency($paymentMethods->avg('current_balance')),
            'active_methods' => $paymentMethods->where('is_active', true)->count(),
            'inactive_methods' => $paymentMethods->where('is_active', false)->count(),
        ];
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        return view('basic-accounting.payment-methods', compact('paymentMethods', 'stats', 'locations', 'locationId'));
    }
    
    // 2. Account Balances Report
    public function accountBalances(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get location filter from request
        $locationId = $request->get('location_id');
        $userLocationId = $user->location_id ?? null;
        
        // Build query with location filter
        $query = PaymentMethod::where('tenant_id', $tenantId)
            ->select([
                'id', 'name', 'type', 'current_balance', 'available_balance', 
                'pending_balance', 'currency_id', 'is_active', 'last_transaction_at',
                'account_number', 'location_id'
            ])
            ->with(['currency']);
        
        // Apply location filter if provided
        if ($locationId) {
            $query->where(function($q) use ($locationId) {
                $q->whereNull('location_id')
                ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
            });
        } elseif ($userLocationId) {
            // Auto-filter by user's location if no filter specified
            $query->where(function($q) use ($userLocationId) {
                $q->whereNull('location_id')
                ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$userLocationId)]);
            });
        }
        // If no location filter and user has no location, return all
        
        $accounts = $query->get()
            ->map(function($account) {
                // Convert balances from cents to base currency
                $account->current_balance_display = from_base_currency($account->current_balance);
                $account->available_balance_display = from_base_currency($account->available_balance);
                $account->pending_balance_display = from_base_currency($account->pending_balance);
                return $account;
            });
        
        // Calculate summary
        $summary = [
            'total_current' => from_base_currency($accounts->sum('current_balance')),
            'total_available' => from_base_currency($accounts->sum('available_balance')),
            'total_pending' => from_base_currency($accounts->sum('pending_balance')),
            'accounts_count' => $accounts->count(),
        ];
        
        // Get recent transactions for context - include today's transactions
        $endDate = now()->endOfDay()->format('Y-m-d H:i:s');
        $recentTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [now()->subDays(30)->format('Y-m-d'), $endDate])
            ->with(['paymentMethod'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transaction) {
                // Convert amounts from cents to base currency
                $transaction->amount_display = from_base_currency($transaction->amount);
                $transaction->balance_after_display = from_base_currency($transaction->balance_after);
                return $transaction;
            });
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        return view('basic-accounting.account-balances', compact('accounts', 'summary', 'recentTransactions', 'locations', 'locationId'));
    }
    
    // 3. Transaction Ledger Report
    public function transactionLedger(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $filters = [
            'start_date' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'transaction_type' => $request->get('transaction_type'),
            'payment_method_id' => $request->get('payment_method_id'),
            'status' => $request->get('status', 'COMPLETED'),
            'location_id' => $request->get('location_id'),
            'user_id' => $request->get('user_id'),
        ];
        
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';
        
        $query = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->with(['paymentMethod', 'currency', 'customer', 'user']);
        
        // Apply date filters
        if ($filters['start_date']) {
            $query->where('transaction_date', '>=', $startDateTime);
        }
        
        if ($filters['end_date']) {
            $query->where('transaction_date', '<=', $endDateTime);
        }
        
        if ($filters['transaction_type']) {
            $query->where('transaction_type', $filters['transaction_type']);
        }
        
        if ($filters['payment_method_id']) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }
        
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }
        
        // Apply location filter
        if ($filters['location_id']) {
            $query->whereHas('paymentMethod', function($q) use ($filters) {
                $q->where(function($sub) use ($filters) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$filters['location_id'])]);
                });
            });
        }
        
        // Apply user filter
        if ($filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }
        
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate(15)
            ->through(function($transaction) {
                $transaction->amount_display = from_base_currency($transaction->amount);
                $transaction->fee_display = from_base_currency($transaction->transaction_fee);
                $transaction->net_amount_display = from_base_currency($transaction->net_amount);
                $transaction->balance_before_display = from_base_currency($transaction->balance_before);
                $transaction->balance_after_display = from_base_currency($transaction->balance_after);
                return $transaction;
            });
        
        $totalAmount = $transactions->sum('amount');
        $averageAmount = $transactions->count() > 0 ? $totalAmount / $transactions->count() : 0;
        
        $recentTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->where('status', 'COMPLETED')
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function($transaction) {
                $transaction->amount_display = from_base_currency($transaction->amount);
                $transaction->balance_after_display = from_base_currency($transaction->balance_after);
                return $transaction;
            });
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name']);
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // FIXED: Get users who have processed transactions - Using DB Builder
        $userIds = \DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        $transactionTypes = PaymentTransactionLog::distinct('transaction_type')
            ->pluck('transaction_type');
        
        $categories = PaymentTransactionLog::distinct('transaction_category')
            ->pluck('transaction_category');
        
        $displayStartDate = $filters['start_date'];
        $displayEndDate = $filters['end_date'];
        
        return view('basic-accounting.transaction-ledger', compact(
            'transactions', 'filters', 'paymentMethods', 
            'transactionTypes', 'categories', 'displayStartDate', 'displayEndDate',
            'totalAmount', 'averageAmount', 'recentTransactions',
            'locations', 'users'
        ));
    }


    public function getTransactionDetails($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $transaction = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with(['paymentMethod.currency', 'customer'])
            ->firstOrFail();
        
        // Convert amounts
        $transaction->amount = from_base_currency($transaction->amount);
        $transaction->balance_before = from_base_currency($transaction->balance_before);
        $transaction->balance_after = from_base_currency($transaction->balance_after);
        $transaction->transaction_fee = from_base_currency($transaction->transaction_fee);
        $transaction->net_amount = from_base_currency($transaction->net_amount);
        
        // Handle metadata - ensure it's properly formatted
        if ($transaction->metadata && is_string($transaction->metadata)) {
            try {
                $transaction->metadata = json_decode($transaction->metadata, true);
            } catch (\Exception $e) {
                $transaction->metadata = ['error' => 'Invalid JSON format'];
            }
        }
        
        return response()->json([
            'success' => true,
            'transaction' => $transaction,
            'customer' => $transaction->customer,
            'payment_method' => $transaction->paymentMethod,
            'currency' => $transaction->paymentMethod->currency ?? null,
        ]);
    }
    
    // 4. Income Statement Report
    public function incomeStatement(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $period = $request->get('period', 'month');
        $locationId = $request->get('location_id'); // Location filter
        $userId = $request->get('user_id'); // NEW: User filter
        
        // Set dates based on period
        switch ($period) {
            case 'month':
                $startDate = now()->startOfMonth()->format('Y-m-d');
                $endDate = now()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 'quarter':
                $startDate = now()->startOfQuarter()->format('Y-m-d');
                $endDate = now()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 'year':
                $startDate = now()->startOfYear()->format('Y-m-d');
                $endDate = now()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 'custom':
                $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
                $endDate = $request->get('end_date', now()->format('Y-m-d')) . ' 23:59:59';
                break;
            default:
                $startDate = now()->startOfMonth()->format('Y-m-d');
                $endDate = now()->endOfDay()->format('Y-m-d H:i:s');
        }
        
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        // Base query for transactions
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        
        // Apply location filter
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Apply user filter
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }
        
        // Get revenue (deposits)
        $revenueCents = (clone $baseQuery)
            ->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])
            ->sum('amount');
        
        // Get expenses (withdrawals)
        $expensesCents = (clone $baseQuery)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->sum('amount');
        
        $revenue = from_base_currency($revenueCents);
        $expenses = from_base_currency($expensesCents);
        $netIncome = $revenue - $expenses;
        
        // Get revenue by category
        $revenueByCategory = (clone $baseQuery)
            ->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])
            ->select('transaction_category', DB::raw('SUM(amount) as total_cents'))
            ->groupBy('transaction_category')
            ->get()
            ->map(function($item) {
                $item->total = from_base_currency($item->total_cents);
                return $item;
            });
        
        // Get expenses by category
        $expensesByCategory = (clone $baseQuery)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->select('transaction_category', DB::raw('SUM(amount) as total_cents'))
            ->groupBy('transaction_category')
            ->get()
            ->map(function($item) {
                $item->total = from_base_currency($item->total_cents);
                return $item;
            });
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // Get users who have processed transactions
        $userIds = DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        // Get monthly trends for chart
        $monthlyTrends = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->when($locationId, function($q) use ($locationId) {
                $q->whereHas('paymentMethod', function($sub) use ($locationId) {
                    $sub->where(function($inner) use ($locationId) {
                        $inner->whereNull('location_id')
                            ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                    });
                });
            })
            ->when($userId, function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->select(
                DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as revenue_cents'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as expenses_cents')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                $item->revenue = from_base_currency($item->revenue_cents);
                $item->expenses = from_base_currency($item->expenses_cents);
                return $item;
            });
        
        return view('basic-accounting.income-statement', compact(
            'revenue', 'expenses', 'netIncome', 
            'revenueByCategory', 'expensesByCategory',
            'displayStartDate', 'displayEndDate', 'period',
            'locations', 'locationId', 'monthlyTrends',
            'users', 'userId' // NEW: Pass users and userId
        ));
    }
        
    // 5. Cash Flow Report
    public function cashFlow(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfDay()->format('Y-m-d H:i:s'));
        $locationId = $request->get('location_id'); // NEW: Location filter
        $userId = $request->get('user_id'); // NEW: User filter
        
        // For display (show date only)
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        // Base query for transactions
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        
        // Apply location filter
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Apply user filter
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }
        
        // Get daily cash flow
        $dailyCashFlow = (clone $baseQuery)
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as cash_in_cents'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as cash_out_cents'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date', 'desc')
            ->get()
            ->map(function($item) {
                $item->cash_in = from_base_currency($item->cash_in_cents);
                $item->cash_out = from_base_currency($item->cash_out_cents);
                return $item;
            });
        
        // Get cash flow by payment method
        $cashFlowByMethod = (clone $baseQuery)
            ->with('paymentMethod')
            ->select(
                'payment_method_id',
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as cash_in_cents'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as cash_out_cents'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('payment_method_id')
            ->get()
            ->map(function($item) {
                $item->cash_in = from_base_currency($item->cash_in_cents);
                $item->cash_out = from_base_currency($item->cash_out_cents);
                return $item;
            });
        
        // Summary
        $totalCashIn = from_base_currency($dailyCashFlow->sum('cash_in_cents'));
        $totalCashOut = from_base_currency($dailyCashFlow->sum('cash_out_cents'));
        
        $summary = [
            'total_cash_in' => $totalCashIn,
            'total_cash_out' => $totalCashOut,
            'net_cash_flow' => $totalCashIn - $totalCashOut,
            'total_transactions' => $dailyCashFlow->sum('transaction_count'),
        ];
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // Get users who have processed transactions
        $userIds = DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        return view('basic-accounting.cash-flow', compact(
            'dailyCashFlow', 'cashFlowByMethod', 'summary',
            'startDate', 'endDate', 'displayStartDate', 'displayEndDate',
            'locations', 'locationId', 'users', 'userId' // NEW: Pass locations and users
        ));
    }
            
    // 6. Transaction Analysis Report
    public function transactionAnalysis(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfDay()->format('Y-m-d H:i:s'));
        $locationId = $request->get('location_id'); // NEW: Location filter
        $userId = $request->get('user_id'); // NEW: User filter
        
        // For display (show date only)
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        // Base query for transactions
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        
        // Apply location filter
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Apply user filter
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }
        
        // Transaction volume by type
        $volumeByType = (clone $baseQuery)
            ->select(
                'transaction_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(amount), 0) as total_cents'),
                DB::raw('COALESCE(AVG(amount), 0) as avg_cents')
            )
            ->groupBy('transaction_type')
            ->get()
            ->map(function($item) {
                $item->total_amount = from_base_currency($item->total_cents);
                $item->average_amount = from_base_currency($item->avg_cents);
                return $item;
            });
        
        // Transaction volume by category
        $volumeByCategory = (clone $baseQuery)
            ->select(
                'transaction_category',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(amount), 0) as total_cents'),
                DB::raw('COALESCE(AVG(amount), 0) as avg_cents')
            )
            ->groupBy('transaction_category')
            ->orderBy('total_cents', 'desc')
            ->get()
            ->map(function($item) {
                $item->total_amount = from_base_currency($item->total_cents);
                $item->average_amount = from_base_currency($item->avg_cents);
                return $item;
            });
        
        // Daily transaction trends
        $dailyTrends = (clone $baseQuery)
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('COALESCE(SUM(amount), 0) as daily_total_cents')
            )
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                $item->daily_total = from_base_currency($item->daily_total_cents);
                return $item;
            });
        
        // Top transactions
        $topTransactions = (clone $baseQuery)
            ->with(['paymentMethod'])
            ->orderBy('amount', 'desc')
            ->paginate(15);
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // Get users who have processed transactions
        $userIds = DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        return view('basic-accounting.transaction-analysis', compact(
            'volumeByType', 'volumeByCategory', 'dailyTrends', 'topTransactions',
            'startDate', 'endDate', 'displayStartDate', 'displayEndDate',
            'locations', 'locationId', 'users', 'userId' // NEW: Pass locations and users
        ));
    }
        
    // 7. Expense Analysis Report
    public function expenseAnalysis(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfDay()->format('Y-m-d H:i:s'));
        $locationId = $request->get('location_id'); // NEW: Location filter
        $userId = $request->get('user_id'); // NEW: User filter
        
        // For display (show date only)
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        // Base query for expense transactions
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        
        // Apply location filter
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Apply user filter
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }
        
        // Calculate summary
        $summaryRaw = (clone $baseQuery)
            ->select(
                DB::raw('SUM(amount) as total_cents'),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(amount) as avg_cents'),
                DB::raw('MAX(amount) as max_cents')
            )
            ->first();
        
        $summary = [
            'total_expenses' => from_base_currency($summaryRaw->total_cents ?? 0),
            'expense_count' => $summaryRaw->count ?? 0,
            'average_expense' => from_base_currency($summaryRaw->avg_cents ?? 0),
            'largest_expense' => from_base_currency($summaryRaw->max_cents ?? 0),
        ];
        
        // Expenses by category
        $expensesByCategory = (clone $baseQuery)
            ->select(
                'transaction_category',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_cents'),
                DB::raw('AVG(amount) as avg_cents'),
                DB::raw('MAX(amount) as max_cents')
            )
            ->groupBy('transaction_category')
            ->orderBy('total_cents', 'desc')
            ->get()
            ->map(function($category) {
                $category->total_amount = from_base_currency($category->total_cents);
                $category->average_amount = from_base_currency($category->avg_cents);
                $category->max_amount = from_base_currency($category->max_cents);
                return $category;
            });
        
        // Top expenses with pagination
        $topExpenses = (clone $baseQuery)
            ->with(['paymentMethod', 'user'])
            ->orderBy('amount', 'desc')
            ->paginate(10);
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // Get users who have processed transactions
        $userIds = DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        return view('basic-accounting.expense-analysis', compact(
            'summary', 'expensesByCategory', 'topExpenses',
            'startDate', 'endDate', 'displayStartDate', 'displayEndDate',
            'locations', 'locationId', 'users', 'userId' // NEW: Pass locations and users
        ));
    }
            
    // 8. Payment Method Analysis Report
    public function paymentMethodAnalysis(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id'); // NEW: Location filter
        $userId = $request->get('user_id'); // NEW: User filter
        
        // Convert to proper datetime ranges to include full days
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        
        // Build payment methods query with location filter
        $paymentMethodsQuery = PaymentMethod::where('tenant_id', $tenantId)
            ->with(['currency']);
        
        // Apply location filter
        if ($locationId) {
            $paymentMethodsQuery->where(function($q) use ($locationId) {
                $q->whereNull('location_id')
                    ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
            });
        }
        
        // Get payment methods with transaction stats
        $paymentMethods = $paymentMethodsQuery->get()
            ->map(function ($method) use ($tenantId, $startDateTime, $endDateTime, $userId) {
                // Build transaction query
                $transactionQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
                    ->where('payment_method_id', $method->id)
                    ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
                    ->where('status', 'COMPLETED');
                
                // Apply user filter to transactions
                if ($userId) {
                    $transactionQuery->where('user_id', $userId);
                }
                
                $transactions = $transactionQuery->get();
                
                $method->transaction_stats = [
                    'total_transactions' => $transactions->count(),
                    'total_amount' => $transactions->sum('amount'),
                    'average_transaction' => $transactions->avg('amount'),
                    'last_transaction' => $transactions->sortByDesc('transaction_date')->first(),
                    'deposit_count' => $transactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->count(),
                    'withdrawal_count' => $transactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->count(),
                    'deposit_total' => $transactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount'),
                    'withdrawal_total' => $transactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount'),
                ];
                
                return $method;
            });
        
        // Overall statistics
        $stats = [
            'total_balance' => $paymentMethods->sum('current_balance'),
            'total_transactions' => $paymentMethods->sum('transaction_stats.total_transactions'),
            'total_transaction_amount' => $paymentMethods->sum('transaction_stats.total_amount'),
            'most_active_method' => $paymentMethods->sortByDesc('transaction_stats.total_transactions')->first(),
            'highest_balance_method' => $paymentMethods->sortByDesc('current_balance')->first(),
        ];
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // Get users who have processed transactions
        $userIds = DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        // For display in the form
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        return view('basic-accounting.payment-method-analysis', compact(
            'paymentMethods', 'stats', 'startDate', 'endDate', 'displayStartDate', 'displayEndDate',
            'locations', 'locationId', 'users', 'userId' 
        ));
    }

    // 9. Daily Summary Report
    public function dailySummary(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $date = $request->get('date', now()->format('Y-m-d'));
        $perPage = (int)$request->get('per_page', 15);
        $locationId = $request->get('location_id'); // NEW: Location filter
        $userId = $request->get('user_id'); // NEW: User filter
        
        // Build base query with filters
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', $date)
            ->where('status', 'COMPLETED');
        
        // Apply location filter
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Apply user filter
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }
        
        // Get transactions for the day with pagination
        $transactions = (clone $baseQuery)
            ->with(['paymentMethod', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
        
        // Get ALL transactions for summary calculations (unpaginated)
        $allTransactions = (clone $baseQuery)->get();
        
        // Summary
        $summary = [
            'total_transactions' => $allTransactions->count(),
            'total_amount' => $allTransactions->sum('amount'),
            'deposit_total' => $allTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount'),
            'withdrawal_total' => $allTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount'),
            'net_cash_flow' => $allTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount') 
                            - $allTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount'),
        ];
        
        // Transactions by type
        $byType = $allTransactions->groupBy('transaction_type')
            ->map(function ($group, $type) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                    'average' => $group->avg('amount'),
                ];
            });
        
        // Transactions by category
        $byCategory = $allTransactions->groupBy('transaction_category')
            ->map(function ($group, $category) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            });
        
        // Balance changes
        $balanceChanges = [];
        foreach ($allTransactions as $transaction) {
            if (!isset($balanceChanges[$transaction->payment_method_id])) {
                $balanceChanges[$transaction->payment_method_id] = [
                    'method' => $transaction->paymentMethod,
                    'starting_balance' => $transaction->balance_before,
                    'ending_balance' => $transaction->balance_after,
                    'net_change' => $transaction->balance_after - $transaction->balance_before,
                ];
            }
        }
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // Get users who have processed transactions
        $userIds = DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        return view('basic-accounting.daily-summary', compact(
            'transactions', 'summary', 'byType', 'byCategory', 
            'balanceChanges', 'date', 'perPage',
            'locations', 'locationId', 'users', 'userId' // NEW: Pass locations and users
        ));
    }
    
    // 10. Monthly Report
    public function monthlyReport(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get month and year from request
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $locationId = $request->get('location_id'); // NEW: Location filter
        $userId = $request->get('user_id'); // NEW: User filter
        
        // Clean and validate month
        $month = strval($month);
        $month = preg_replace('/[^0-9]/', '', $month);
        
        if (empty($month) || strlen($month) > 2 || intval($month) > 12 || intval($month) < 1) {
            $month = date('m');
        }
        
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        // Validate year
        $year = intval($year);
        if ($year < 2000 || $year > 2100) {
            $year = date('Y');
        }
        
        // Create dates
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Base query for transactions
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        
        // Apply location filter
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Apply user filter
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }
        
        // Monthly transactions
        $transactions = (clone $baseQuery)->get();
        
        // Summary
        $summary = [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'deposit_total' => $transactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount'),
            'withdrawal_total' => $transactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount'),
            'net_cash_flow' => $transactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount') 
                            - $transactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount'),
        ];
        
        // Daily breakdown
        $dailyBreakdown = (clone $baseQuery)
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as deposits'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as withdrawals'),
                DB::raw('SUM(amount) as daily_total')
            )
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date')
            ->get();
        
        // Category breakdown
        $categoryBreakdown = (clone $baseQuery)
            ->select(
                'transaction_category',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total'),
                DB::raw('AVG(amount) as average')
            )
            ->groupBy('transaction_category')
            ->orderBy('total', 'desc')
            ->get();
        
        // Payment method breakdown
        $methodBreakdown = (clone $baseQuery)
            ->with('paymentMethod')
            ->select(
                'payment_method_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('payment_method_id')
            ->orderBy('total_amount', 'desc')
            ->get();
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // Get users who have processed transactions
        $userIds = DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        // Month name for display
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $monthName = $monthNames[(int)$month] ?? 'Unknown';
        
        return view('basic-accounting.monthly-report', compact(
            'transactions', 'summary', 'dailyBreakdown', 
            'categoryBreakdown', 'methodBreakdown',
            'month', 'year', 'startDate', 'endDate', 'monthName',
            'locations', 'locationId', 'users', 'userId' // NEW: Pass locations and users
        ));
    }

    // 11. Weekly Report with 6-Month Comparison
    public function weeklyReport(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get week and year from request
        $week = $request->input('week', date('W'));
        $year = $request->input('year', date('Y'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Clean and validate week
        $week = intval($week);
        if ($week < 1 || $week > 53) {
            $week = date('W');
        }
        
        // Validate year
        $year = intval($year);
        if ($year < 2000 || $year > 2100) {
            $year = date('Y');
        }
        
        // Get start and end dates for the week
        $startDate = date('Y-m-d', strtotime($year . 'W' . str_pad($week, 2, '0', STR_PAD_LEFT) . '1'));
        $endDate = date('Y-m-d 23:59:59', strtotime($startDate . ' +6 days'));
        
        // Base query for transactions
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        
        // Apply location filter
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Apply user filter
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }
        
        // Get weekly transactions with pagination
        $transactions = (clone $baseQuery)
            ->with(['paymentMethod', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
        
        // Get ALL transactions for summary calculations (unpaginated)
        $allTransactions = (clone $baseQuery)->get();
        
        // Weekly summary
        $summary = [
            'total_transactions' => $allTransactions->count(),
            'total_amount' => $allTransactions->sum('amount'),
            'deposit_total' => $allTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount'),
            'withdrawal_total' => $allTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount'),
            'net_cash_flow' => $allTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount') 
                            - $allTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount'),
        ];
        
        // Daily breakdown for the week
        $dailyBreakdown = (clone $baseQuery)
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as deposits'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as withdrawals'),
                DB::raw('SUM(amount) as daily_total')
            )
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date')
            ->get();
        
        // Get last 6 months comparison data
        $sixMonthsData = [];
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = date('m', strtotime("-$i months"));
            $yearMonth = date('Y', strtotime("-$i months"));
            $monthStart = date('Y-m-01', strtotime("-$i months"));
            $monthEnd = date('Y-m-t 23:59:59', strtotime("-$i months"));
            
            // Query for monthly totals
            $monthQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->where('status', 'COMPLETED');
            
            // Apply location filter for monthly data
            if ($locationId) {
                $monthQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                    $q->where(function($sub) use ($locationId) {
                        $sub->whereNull('location_id')
                            ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                    });
                });
            }
            
            if ($userId) {
                $monthQuery->where('user_id', $userId);
            }
            
            $monthTransactions = $monthQuery->get();
            
            $monthLabel = $monthNames[(int)$month] . ' ' . substr($yearMonth, -2);
            $deposits = $monthTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
            $withdrawals = $monthTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
            $net = $deposits - $withdrawals;
            
            $sixMonthsData[] = [
                'month' => $monthLabel,
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'net' => $net,
                'transactions' => $monthTransactions->count(),
            ];
        }
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // Get users who have processed transactions
        $userIds = DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        // Week range for display
        $weekStartDisplay = date('M d', strtotime($startDate));
        $weekEndDisplay = date('M d, Y', strtotime($endDate));
        
        return view('basic-accounting.weekly-report', compact(
            'transactions', 'summary', 'dailyBreakdown', 'sixMonthsData',
            'week', 'year', 'startDate', 'endDate', 'weekStartDisplay', 'weekEndDisplay',
            'locations', 'locationId', 'users', 'userId', 'perPage'
        ));
    }

    // 12. User Performance Report - Top Profit/Loss Makers
    public function userPerformanceReport(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfDay()->format('Y-m-d H:i:s'));
        $locationId = $request->get('location_id');
        $limit = $request->get('limit', 10);
        $perPage = (int)$request->get('per_page', 15);
        
        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        // Build base query for user transactions
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->whereNotNull('user_id');
        
        // Apply location filter
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Get user performance data
        $userPerformance = (clone $baseQuery)
            ->select(
                'user_id',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as total_deposits'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as total_withdrawals'),
                DB::raw('AVG(amount) as average_transaction'),
                DB::raw('MAX(amount) as largest_transaction'),
                DB::raw('MIN(amount) as smallest_transaction')
            )
            ->groupBy('user_id')
            ->get()
            ->map(function($item) {
                $item->net_amount = $item->total_deposits - $item->total_withdrawals;
                $item->total_amount_display = from_base_currency($item->total_amount);
                $item->total_deposits_display = from_base_currency($item->total_deposits);
                $item->total_withdrawals_display = from_base_currency($item->total_withdrawals);
                $item->net_amount_display = from_base_currency($item->net_amount);
                $item->average_transaction_display = from_base_currency($item->average_transaction);
                $item->largest_transaction_display = from_base_currency($item->largest_transaction);
                $item->smallest_transaction_display = from_base_currency($item->smallest_transaction);
                return $item;
            });
        
        // Get user details
        $userIds = $userPerformance->pluck('user_id')->toArray();
        $users = \App\Models\User::whereIn('id', $userIds)
            ->get(['id', 'name', 'email'])
            ->keyBy('id');
        
        // Attach user data to performance
        $userPerformance = $userPerformance->map(function($item) use ($users) {
            $user = $users[$item->user_id] ?? null;
            $item->user_name = $user->name ?? 'Unknown User';
            $item->user_email = $user->email ?? '';
            return $item;
        });
        
        // Sort by net amount (profit/loss)
        $topProfitMakers = $userPerformance->sortByDesc('net_amount')->values()->take($limit);
        $topLossMakers = $userPerformance->sortBy('net_amount')->values()->take($limit);
        
        // Get recent transactions for top users with pagination
        $topUserIds = $topProfitMakers->pluck('user_id')->merge($topLossMakers->pluck('user_id'))->unique()->toArray();
        
        $recentTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->whereIn('user_id', $topUserIds)
            ->with(['paymentMethod', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage)
            ->through(function($transaction) {
                $transaction->amount_display = from_base_currency($transaction->amount);
                return $transaction;
            });
        
        // Summary statistics
        $summary = [
            'total_users' => $userPerformance->count(),
            'total_transactions' => $userPerformance->sum('total_transactions'),
            'total_volume' => from_base_currency($userPerformance->sum('total_amount')),
            'average_per_user' => $userPerformance->count() > 0 
                ? from_base_currency($userPerformance->sum('total_amount') / $userPerformance->count()) 
                : 0,
            'total_profit' => from_base_currency($userPerformance->sum('net_amount')),
            'best_user' => $topProfitMakers->first(),
            'worst_user' => $topLossMakers->first(),
        ];
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        return view('basic-accounting.user-performance-report', compact(
            'topProfitMakers', 'topLossMakers', 'recentTransactions', 'summary',
            'startDate', 'endDate', 'displayStartDate', 'displayEndDate',
            'locations', 'locationId', 'limit', 'userPerformance', 'perPage'
        ));
    }
        
    // 13. Reconciliation Report
    public function reconciliation(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $date = $request->get('date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        
        // Get all payment methods with location filter
        $paymentMethodsQuery = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['currency']);
        
        // Apply location filter
        if ($locationId) {
            $paymentMethodsQuery->where(function($q) use ($locationId) {
                $q->whereNull('location_id')
                    ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
            });
        }
        
        $paymentMethods = $paymentMethodsQuery->get()
            ->map(function ($method) use ($tenantId, $date, $userId) {
                // Build transaction query for the day
                $transactionQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
                    ->where('payment_method_id', $method->id)
                    ->whereDate('transaction_date', $date)
                    ->where('status', 'COMPLETED');
                
                // Apply user filter to transactions
                if ($userId) {
                    $transactionQuery->where('user_id', $userId);
                }
                
                $transactions = $transactionQuery->get();
                
                // Calculate net change for the day
                $netChange = 0;
                foreach ($transactions as $transaction) {
                    if (in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])) {
                        $netChange += $transaction->amount;
                    } else {
                        $netChange -= $transaction->amount;
                    }
                }
                
                // FIXED: Calculate expected balance correctly
                // Expected Balance = Current Balance - Net Change
                // Because the current balance already includes all transactions
                // If we subtract the net change, we get what the balance should be
                $expectedBalance = $method->current_balance - $netChange;
                
                // FIXED: Calculate starting balance (balance at beginning of day)
                // Starting Balance = Current Balance - Net Change
                $startingBalance = $method->current_balance - $netChange;
                
                $method->reconciliation_data = [
                    'current_balance' => $method->current_balance,
                    'starting_balance' => $startingBalance,
                    'expected_balance' => $expectedBalance,
                    'net_change' => $netChange,
                    'transaction_count' => $transactions->count(),
                    'discrepancy' => abs($method->current_balance - $expectedBalance),
                    'is_reconciled' => abs($method->current_balance - $expectedBalance) < 0.01,
                ];
                
                return $method;
            });
        
        // Summary
        $summary = [
            'total_methods' => $paymentMethods->count(),
            'reconciled_methods' => $paymentMethods->where('reconciliation_data.is_reconciled', true)->count(),
            'unreconciled_methods' => $paymentMethods->where('reconciliation_data.is_reconciled', false)->count(),
            'total_discrepancy' => $paymentMethods->sum('reconciliation_data.discrepancy'),
        ];
        
        // Get unreconciled transactions with location and user filters
        $unreconciledQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', $date)
            ->where('status', 'PENDING')
            ->with(['paymentMethod', 'user']);
        
        // Apply location filter to unreconciled transactions
        if ($locationId) {
            $unreconciledQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Apply user filter to unreconciled transactions
        if ($userId) {
            $unreconciledQuery->where('user_id', $userId);
        }
        
        $unreconciledTransactions = $unreconciledQuery->get();
        
        // Get locations for filter dropdown
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        // Get users who have processed transactions
        $userIds = DB::table('payment_transaction_logs')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
        
        return view('basic-accounting.reconciliation', compact(
            'paymentMethods', 'summary', 'unreconciledTransactions', 'date',
            'locations', 'locationId', 'users', 'userId'
        ));
    }

}
