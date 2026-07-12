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
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->with(['currency'])
            ->get()
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
        
        return view('basic-accounting.payment-methods', compact('paymentMethods', 'stats'));
    }
    
    // 2. Account Balances Report
    public function accountBalances(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get all payment methods with balances
        $accounts = PaymentMethod::where('tenant_id', $tenantId)
            ->select([
                'id', 'name', 'type', 'current_balance', 'available_balance', 
                'pending_balance', 'currency_id', 'is_active', 'last_transaction_at'
            ])
            ->with(['currency'])
            ->get()
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
        
        return view('basic-accounting.account-balances', compact('accounts', 'summary', 'recentTransactions'));
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
        ];
        
        // Convert to proper datetime ranges for full day inclusion
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';
        
        $query = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->with(['paymentMethod', 'currency', 'customer']);
        
        // Apply filters
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
        
        // Get paginated transactions
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate(15) // Changed to 15 per page for better usability
            ->through(function($transaction) {
                // Convert amounts from cents to base currency
                $transaction->amount_display = from_base_currency($transaction->amount);
                $transaction->fee_display = from_base_currency($transaction->transaction_fee);
                $transaction->net_amount_display = from_base_currency($transaction->net_amount);
                $transaction->balance_before_display = from_base_currency($transaction->balance_before);
                $transaction->balance_after_display = from_base_currency($transaction->balance_after);
                return $transaction;
            });
        
        // Calculate summary stats using the paginated data
        $totalAmount = $transactions->sum('amount_display');
        $averageAmount = $transactions->count() > 0 ? $totalAmount / $transactions->count() : 0;
        
        // Get recent transactions (last 5 for quick view)
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
        
        // Get filter options
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name']);
        
        $transactionTypes = PaymentTransactionLog::distinct('transaction_type')
            ->pluck('transaction_type');
        
        $categories = PaymentTransactionLog::distinct('transaction_category')
            ->pluck('transaction_category');
        
        // For display in the form
        $displayStartDate = $filters['start_date'];
        $displayEndDate = $filters['end_date'];
        
        return view('basic-accounting.transaction-ledger', compact(
            'transactions', 'filters', 'paymentMethods', 
            'transactionTypes', 'categories', 'displayStartDate', 'displayEndDate',
            'totalAmount', 'averageAmount', 'recentTransactions'
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
        
        // Set dates based on period - ensure we include current date
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
        
        // For display purposes (show date only without time)
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        // Get revenue (deposits) - using RAW DB values (cents)
        $revenueCents = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->sum('amount');
        
        // Get expenses (withdrawals) - using RAW DB values (cents)
        $expensesCents = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->sum('amount');
        
        // Convert to base currency using helper
        $revenue = from_base_currency($revenueCents);
        $expenses = from_base_currency($expensesCents);
        $netIncome = $revenue - $expenses;
        
        // Get revenue by category - using RAW DB values (cents)
        $revenueByCategory = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select('transaction_category', DB::raw('SUM(amount) as total_cents'))
            ->groupBy('transaction_category')
            ->get()
            ->map(function($item) {
                $item->total = from_base_currency($item->total_cents);
                return $item;
            });
        
        // Get expenses by category - using RAW DB values (cents)
        $expensesByCategory = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select('transaction_category', DB::raw('SUM(amount) as total_cents'))
            ->groupBy('transaction_category')
            ->get()
            ->map(function($item) {
                $item->total = from_base_currency($item->total_cents);
                return $item;
            });
        
        return view('basic-accounting.income-statement', compact(
            'revenue', 'expenses', 'netIncome', 
            'revenueByCategory', 'expensesByCategory',
            'displayStartDate', 'displayEndDate', 'period'
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
        
        // For display (show date only)
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        // Get daily cash flow - using RAW DB values (cents)
        $dailyCashFlow = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
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
                // Convert cents to base currency using helper
                $item->cash_in = from_base_currency($item->cash_in_cents);
                $item->cash_out = from_base_currency($item->cash_out_cents);
                return $item;
            });
        
        // Get cash flow by payment method - using RAW DB values (cents)
        $cashFlowByMethod = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
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
                // Convert cents to base currency using helper
                $item->cash_in = from_base_currency($item->cash_in_cents);
                $item->cash_out = from_base_currency($item->cash_out_cents);
                return $item;
            });
        
        // Summary - using helpers
        $totalCashIn = from_base_currency($dailyCashFlow->sum('cash_in_cents'));
        $totalCashOut = from_base_currency($dailyCashFlow->sum('cash_out_cents'));
        
        $summary = [
            'total_cash_in' => $totalCashIn,
            'total_cash_out' => $totalCashOut,
            'net_cash_flow' => $totalCashIn - $totalCashOut,
            'total_transactions' => $dailyCashFlow->sum('transaction_count'),
        ];
        
        return view('basic-accounting.cash-flow', compact(
            'dailyCashFlow', 'cashFlowByMethod', 'summary',
            'startDate', 'endDate', 'displayStartDate', 'displayEndDate'
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
        
        // For display (show date only)
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        // Transaction volume by type - using RAW DB values (cents)
        $volumeByType = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select(
                'transaction_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(amount), 0) as total_cents'),
                DB::raw('COALESCE(AVG(amount), 0) as avg_cents')
            )
            ->groupBy('transaction_type')
            ->get()
            ->map(function($item) {
                // Convert cents to base currency using helper
                $item->total_amount = from_base_currency($item->total_cents);
                $item->average_amount = from_base_currency($item->avg_cents);
                return $item;
            });
        
        // Transaction volume by category - using RAW DB values (cents)
        $volumeByCategory = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
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
                // Convert cents to base currency using helper
                $item->total_amount = from_base_currency($item->total_cents);
                $item->average_amount = from_base_currency($item->avg_cents);
                return $item;
            });
        
        // Daily transaction trends - using RAW DB values (cents)
        $dailyTrends = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('COALESCE(SUM(amount), 0) as daily_total_cents')
            )
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                // Convert cents to base currency using helper
                $item->daily_total = from_base_currency($item->daily_total_cents);
                return $item;
            });
        
        // Top transactions - using pagination (amount is already converted by accessor)
        $topTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->with(['paymentMethod'])
            ->orderBy('amount', 'desc')
            ->paginate(15);
        
        return view('basic-accounting.transaction-analysis', compact(
            'volumeByType', 'volumeByCategory', 'dailyTrends', 'topTransactions',
            'startDate', 'endDate', 'displayStartDate', 'displayEndDate'
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
        
        // For display (show date only)
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        // Calculate summary using RAW DB values (cents) then convert
        $summaryRaw = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select(
                DB::raw('SUM(amount) as total_cents'),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(amount) as avg_cents'),
                DB::raw('MAX(amount) as max_cents')
            )
            ->first();
        
        // Convert cents to base currency using helper
        $summary = [
            'total_expenses' => from_base_currency($summaryRaw->total_cents ?? 0),
            'expense_count' => $summaryRaw->count ?? 0,
            'average_expense' => from_base_currency($summaryRaw->avg_cents ?? 0),
            'largest_expense' => from_base_currency($summaryRaw->max_cents ?? 0),
        ];
        
        // Expenses by category using RAW DB values (cents)
        $expensesByCategory = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
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
                // Convert cents to base currency using helper
                $category->total_amount = from_base_currency($category->total_cents);
                $category->average_amount = from_base_currency($category->avg_cents);
                $category->max_amount = from_base_currency($category->max_cents);
                return $category;
            });
        
        // Top expenses - use pagination
        $topExpenses = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->with(['paymentMethod'])
            ->orderBy('amount', 'desc')
            ->paginate(10);
        
        return view('basic-accounting.expense-analysis', compact(
            'summary', 'expensesByCategory', 'topExpenses',
            'startDate', 'endDate', 'displayStartDate', 'displayEndDate'
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
        
        // Convert to proper datetime ranges to include full days
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        
        // Get payment methods with transaction stats
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->with(['currency'])
            ->get()
            ->map(function ($method) use ($tenantId, $startDateTime, $endDateTime) {
                $transactions = PaymentTransactionLog::where('tenant_id', $tenantId)
                    ->where('payment_method_id', $method->id)
                    ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
                    ->where('status', 'COMPLETED')
                    ->get();
                    
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
        
        // For display in the form
        $displayStartDate = $startDate;
        $displayEndDate = $endDate;
        
        return view('basic-accounting.payment-method-analysis', compact(
            'paymentMethods', 'stats', 'startDate', 'endDate', 'displayStartDate', 'displayEndDate'
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
        
        // Get transactions for the day with pagination
        $transactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', $date)
            ->where('status', 'COMPLETED')
            ->with(['paymentMethod'])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
        
        // Get ALL transactions for summary calculations (unpaginated)
        $allTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', $date)
            ->where('status', 'COMPLETED')
            ->get();
            
        // Summary
        $summary = [
            'total_transactions' => $allTransactions->count(),
            'total_amount' => $allTransactions->sum('amount'),
            'deposit_total' => $allTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount'),
            'withdrawal_total' => $allTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount'),
            'net_cash_flow' => $allTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount') 
                            - $allTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount'),
        ];
        
        // Transactions by type (from all transactions)
        $byType = $allTransactions->groupBy('transaction_type')
            ->map(function ($group, $type) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                    'average' => $group->avg('amount'),
                ];
            });
            
        // Transactions by category (from all transactions)
        $byCategory = $allTransactions->groupBy('transaction_category')
            ->map(function ($group, $category) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            });
            
        // Balance changes (from all transactions)
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
        
        return view('basic-accounting.daily-summary', compact(
            'transactions', 'summary', 'byType', 'byCategory', 
            'balanceChanges', 'date', 'perPage'
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
        
        // Get month and year from request with proper defaults
        $month = $request->input('month', date('m')); // Use input() instead of get()
        $year = $request->input('year', date('Y'));
        
        // DEBUG: Uncomment to see what you're receiving
        // \Log::info('Monthly Report Params:', [
        //     'month_received' => $month,
        //     'year_received' => $year,
        //     'month_type' => gettype($month),
        //     'year_type' => gettype($year)
        // ]);
        
        // Clean and validate month
        $month = strval($month); // Ensure it's a string
        $month = preg_replace('/[^0-9]/', '', $month); // Remove non-numeric characters
        
        // If month is empty or looks like a year (4 digits), use current month
        if (empty($month) || strlen($month) > 2 || intval($month) > 12 || intval($month) < 1) {
            $month = date('m');
        }
        
        // Ensure month is 2 digits
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        // Validate year
        $year = intval($year);
        if ($year < 2000 || $year > 2100) {
            $year = date('Y');
        }
        
        // Create dates
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Monthly transactions
        $transactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->get();
            
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
        $dailyBreakdown = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
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
        $categoryBreakdown = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
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
        $methodBreakdown = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->with('paymentMethod')
            ->select(
                'payment_method_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('payment_method_id')
            ->orderBy('total_amount', 'desc')
            ->get();
        
        return view('basic-accounting.monthly-report', compact(
            'transactions', 'summary', 'dailyBreakdown', 
            'categoryBreakdown', 'methodBreakdown',
            'month', 'year', 'startDate', 'endDate'
        ));
    }
    
    // 11. Reconciliation Report
    public function reconciliation(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $date = $request->get('date', now()->format('Y-m-d'));
        
        // Get all payment methods
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['currency'])
            ->get()
            ->map(function ($method) use ($tenantId, $date) {
                // Get expected balance based on transactions
                $transactions = PaymentTransactionLog::where('tenant_id', $tenantId)
                    ->where('payment_method_id', $method->id)
                    ->whereDate('transaction_date', $date)
                    ->where('status', 'COMPLETED')
                    ->get();
                    
                $calculatedBalance = $method->current_balance;
                $netChange = 0;
                
                foreach ($transactions as $transaction) {
                    if (in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])) {
                        $netChange += $transaction->amount;
                    } else {
                        $netChange -= $transaction->amount;
                    }
                }
                
                $expectedBalance = $method->current_balance - $netChange;
                
                $method->reconciliation_data = [
                    'current_balance' => $method->current_balance,
                    'expected_balance' => $expectedBalance,
                    'net_change' => $netChange,
                    'transaction_count' => $transactions->count(),
                    'discrepancy' => abs($method->current_balance - $expectedBalance),
                    'is_reconciled' => abs($method->current_balance - $expectedBalance) < 0.01, // Tolerance for rounding
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
        
        // Get unreconciled transactions
        $unreconciledTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', $date)
            ->where('status', 'PENDING')
            ->with(['paymentMethod'])
            ->get();
            
        return view('basic-accounting.reconciliation', compact(
            'paymentMethods', 'summary', 'unreconciledTransactions', 'date'
        ));
    }
}
