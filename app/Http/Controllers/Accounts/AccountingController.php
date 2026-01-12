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
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->with(['currency'])
            ->get();
        
        $stats = [
            'total_payment_methods' => $paymentMethods->count(),
            'total_balance' => $paymentMethods->sum('current_balance'),
            'average_balance' => $paymentMethods->avg('current_balance'),
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
        
        // Get all payment methods with balances
        $accounts = PaymentMethod::where('tenant_id', $tenantId)
            ->select([
                'id', 'name', 'type', 'current_balance', 'available_balance', 
                'pending_balance', 'currency_id', 'is_active', 'last_transaction_at'
            ])
            ->with(['currency'])
            ->get();
            
        // Calculate summary
        $summary = [
            'total_current' => $accounts->sum('current_balance'),
            'total_available' => $accounts->sum('available_balance'),
            'total_pending' => $accounts->sum('pending_balance'),
            'accounts_count' => $accounts->count(),
        ];
        
        // Get recent transactions for context
        $recentTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->with(['paymentMethod'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();
        
        return view('basic-accounting.account-balances', compact('accounts', 'summary', 'recentTransactions'));
    }
    
    // 3. Transaction Ledger Report
    public function transactionLedger(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $filters = [
            'start_date' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'transaction_type' => $request->get('transaction_type'),
            'payment_method_id' => $request->get('payment_method_id'),
            'status' => $request->get('status', 'COMPLETED'),
        ];
        
        $query = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->with(['paymentMethod', 'currency', 'customer']); // Added customer here
                
        // Apply filters
        if ($filters['start_date']) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }
        
        if ($filters['end_date']) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
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
        
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate(50);
            
        // Get filter options
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
            
        $transactionTypes = PaymentTransactionLog::distinct('transaction_type')
            ->pluck('transaction_type');
            
        $categories = PaymentTransactionLog::distinct('transaction_category')
            ->pluck('transaction_category');
        
        return view('basic-accounting.transaction-ledger', compact(
            'transactions', 'filters', 'paymentMethods', 
            'transactionTypes', 'categories'
        ));
    }

    // Add this method to AccountingController
    public function getTransactionDetails($id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $transaction = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with(['paymentMethod.currency', 'customer'])
            ->firstOrFail();
        
        // Handle metadata - ensure it's properly formatted
        if ($transaction->metadata && is_string($transaction->metadata)) {
            try {
                $transaction->metadata = json_decode($transaction->metadata, true);
            } catch (\Exception $e) {
                $transaction->metadata = ['error' => 'Invalid JSON format'];
            }
        }
        
        return response()->json([
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
        
        $period = $request->get('period', 'month');
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        // Get revenue (deposits)
        $revenue = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->sum('amount');
            
        // Get expenses (withdrawals)
        $expenses = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->sum('amount');
            
        // Get net income
        $netIncome = $revenue - $expenses;
        
        // Get revenue by category
        $revenueByCategory = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select('transaction_category', DB::raw('SUM(amount) as total'))
            ->groupBy('transaction_category')
            ->get();
            
        // Get expenses by category
        $expensesByCategory = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select('transaction_category', DB::raw('SUM(amount) as total'))
            ->groupBy('transaction_category')
            ->get();
        
        return view('basic-accounting.income-statement', compact(
            'revenue', 'expenses', 'netIncome', 
            'revenueByCategory', 'expensesByCategory',
            'startDate', 'endDate', 'period'
        ));
    }
    
    // 5. Cash Flow Report
    public function cashFlow(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        // Get daily cash flow
        $dailyCashFlow = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as cash_in'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as cash_out'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date', 'desc')
            ->get();
            
        // Get cash flow by payment method
        $cashFlowByMethod = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->with('paymentMethod')
            ->select(
                'payment_method_id',
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as cash_in'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as cash_out'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('payment_method_id')
            ->get();
            
        // Summary
        $summary = [
            'total_cash_in' => $dailyCashFlow->sum('cash_in'),
            'total_cash_out' => $dailyCashFlow->sum('cash_out'),
            'net_cash_flow' => $dailyCashFlow->sum('cash_in') - $dailyCashFlow->sum('cash_out'),
            'total_transactions' => $dailyCashFlow->sum('transaction_count'),
        ];
        
        return view('basic-accounting.cash-flow', compact(
            'dailyCashFlow', 'cashFlowByMethod', 'summary',
            'startDate', 'endDate'
        ));
    }
    
    // 6. Transaction Analysis Report
    public function transactionAnalysis(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            // Swap dates if start is after end
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        // Transaction volume by type
        $volumeByType = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select(
                'transaction_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(amount), 0) as total_amount'),
                DB::raw('COALESCE(AVG(amount), 0) as average_amount')
            )
            ->groupBy('transaction_type')
            ->get();
            
        // Transaction volume by category
        $volumeByCategory = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select(
                'transaction_category',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(amount), 0) as total_amount'),
                DB::raw('COALESCE(AVG(amount), 0) as average_amount')
            )
            ->groupBy('transaction_category')
            ->orderBy('total_amount', 'desc')
            ->get();
            
        // Daily transaction trends
        $dailyTrends = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('COALESCE(SUM(amount), 0) as daily_total')
            )
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date')
            ->get();
            
        // Top transactions
        $topTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->with(['paymentMethod'])
            ->orderBy('amount', 'desc')
            ->limit(20)
            ->get();
        
        return view('basic-accounting.transaction-analysis', compact(
            'volumeByType', 'volumeByCategory', 'dailyTrends', 'topTransactions',
            'startDate', 'endDate'
        ));
    }
        
    // 7. Expense Analysis Report
    public function expenseAnalysis(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        // All expenses (withdrawals)
        $expenses = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->with(['paymentMethod'])
            ->orderBy('transaction_date', 'desc')
            ->get();
            
        // Expense summary
        $summary = [
            'total_expenses' => $expenses->sum('amount'),
            'expense_count' => $expenses->count(),
            'average_expense' => $expenses->avg('amount'),
            'largest_expense' => $expenses->max('amount'),
        ];
        
        // Expenses by category
        $expensesByCategory = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select(
                'transaction_category',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as average_amount'),
                DB::raw('MAX(amount) as max_amount')
            )
            ->groupBy('transaction_category')
            ->orderBy('total_amount', 'desc')
            ->get();
            
        // Top expense sources
        $topExpenses = $expenses->sortByDesc('amount')->take(10);
        
        return view('basic-accounting.expense-analysis', compact(
            'expenses', 'summary', 'expensesByCategory', 'topExpenses',
            'startDate', 'endDate'
        ));
    }
    
    // 8. Payment Method Analysis Report
    public function paymentMethodAnalysis(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        // Get payment methods with transaction stats
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->with(['currency'])
            ->get()
            ->map(function ($method) use ($tenantId, $startDate, $endDate) {
                $transactions = PaymentTransactionLog::where('tenant_id', $tenantId)
                    ->where('payment_method_id', $method->id)
                    ->whereBetween('transaction_date', [$startDate, $endDate])
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
        
        return view('basic-accounting.payment-method-analysis', compact(
            'paymentMethods', 'stats', 'startDate', 'endDate'
        ));
    }
    
    // 9. Daily Summary Report
    public function dailySummary(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $date = $request->get('date', now()->format('Y-m-d'));
        
        // Get transactions for the day
        $transactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', $date)
            ->where('status', 'COMPLETED')
            ->with(['paymentMethod'])
            ->orderBy('transaction_date', 'desc')
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
        
        // Transactions by type
        $byType = $transactions->groupBy('transaction_type')
            ->map(function ($group, $type) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                    'average' => $group->avg('amount'),
                ];
            });
            
        // Transactions by category
        $byCategory = $transactions->groupBy('transaction_category')
            ->map(function ($group, $category) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            });
            
        // Balance changes
        $balanceChanges = [];
        foreach ($transactions as $transaction) {
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
            'balanceChanges', 'date'
        ));
    }
    
    // 10. Monthly Report
    public function monthlyReport(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
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
