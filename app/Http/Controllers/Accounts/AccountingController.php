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

        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $locationId = $request->get('location_id');
        $userLocationId = $user->location_id ?? null;

        return view('basic-accounting.payment-methods', compact('locations', 'locationId', 'userLocationId'));
    }

    public function paymentMethodsData(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $locationId = $request->get('location_id');
        $userLocationId = $user->location_id ?? null;
        $search = trim((string) $request->get('search', ''));
        
        $query = PaymentMethod::where('tenant_id', $tenantId)->with(['currency']);
        
        // Apply Location Filter (server-side)
        if ($locationId) {
            $query->where(function($q) use ($locationId) {
                $q->whereNull('location_id')
                ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
            });
        } 
        // elseif ($userLocationId) {
        //     $query->where(function($q) use ($userLocationId) {
        //         $q->whereNull('location_id')
        //         ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$userLocationId)]);
        //     });
        // }

        // Apply Search Filter
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        $methods = $query->get();

        $rows = $methods->map(function($method) {
            $typeColors = [
                'cash' => 'warning', 'bank_account' => 'info', 'card' => 'primary',
                'digital_wallet' => 'success', 'mobile_money' => 'danger',
                'check' => 'secondary', 'other' => 'dark'
            ];

            return [
                'id' => $method->id,
                'name' => $method->name,
                'type' => ucfirst(str_replace('_', ' ', $method->type)),
                'type_color' => $typeColors[$method->type] ?? 'secondary',
                'account_number' => $method->account_number ? '****' . substr($method->account_number, -4) : 'N/A',
                'current_balance' => (float) $method->current_balance,
                'available_balance' => (float) $method->available_balance,
                'pending_balance' => (float) $method->pending_balance,
                'is_active' => (bool) $method->is_active,
                'last_transaction_at' => $method->last_transaction_at ? $method->last_transaction_at->format('M d, Y') : null,
                'transactions_url' => route('accounting.transaction-ledger', ['payment_method_id' => $method->id]),
            ];
        });

        $stats = [
            'total_payment_methods' => $methods->count(),
            'total_balance' => from_base_currency($methods->sum('current_balance')),
            'average_balance' => from_base_currency($methods->avg('current_balance')),
            'active_methods' => $methods->where('is_active', true)->count(),
            'inactive_methods' => $methods->where('is_active', false)->count(),
            'total_available' => from_base_currency($methods->sum('available_balance')),
            'total_pending' => from_base_currency($methods->sum('pending_balance')),
        ];

        return response()->json([
            'success' => true,
            'data' => $rows,
            'stats' => $stats,
        ]);
    }
        
    public function accountBalances(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }

        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $locationId = $request->get('location_id');
        
        return view('basic-accounting.account-balances', compact('locations', 'locationId'));
    }

    public function accountBalancesData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $locationId = $request->get('location_id');
        $userLocationId = $user->location_id ?? null;
        
        // 1. Fetch Accounts
        $query = PaymentMethod::where('tenant_id', $tenantId)
            ->with(['currency']);
        
        if ($locationId) {
            $query->where(function($q) use ($locationId) {
                $q->whereNull('location_id')
                ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
            });
        } 
        // elseif ($userLocationId) {
        //     $query->where(function($q) use ($userLocationId) {
        //         $q->whereNull('location_id')
        //         ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$userLocationId)]);
        //     });
        // }
        
        $accounts = $query->get();
        
        // Map accounts to JSON
        $accountsData = $accounts->map(function($account) {
            return [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'type_label' => ucfirst(str_replace('_', ' ', $account->type)),
                'account_number' => $account->account_number ? '****' . substr($account->account_number, -4) : 'N/A',
                'current_balance' => (float) $account->current_balance,
                'available_balance' => (float) $account->available_balance,
                'pending_balance' => (float) $account->pending_balance,
                'is_active' => (bool) $account->is_active,
                'last_transaction_at' => $account->last_transaction_at ? $account->last_transaction_at->format('M d, Y') : null,
                'transactions_url' => route('accounting.transaction-ledger', ['payment_method_id' => $account->id]),
            ];
        });

        // 2. Calculate Summary
        $summary = [
            'total_current' => (float) $accounts->sum('current_balance'),
            'total_available' => (float) $accounts->sum('available_balance'),
            'total_pending' => (float) $accounts->sum('pending_balance'),
            'accounts_count' => $accounts->count(),
            'active_count' => $accounts->where('is_active', true)->count(),
            'inactive_count' => $accounts->where('is_active', false)->count(),
        ];

        // 3. Balance Distribution (Top 5)
        $topAccounts = $accounts->sortByDesc('current_balance')->take(5)->values();
        $distribution = $topAccounts->map(function($acc) use ($summary) {
            $percent = $summary['total_current'] > 0 ? ($acc->current_balance / $summary['total_current']) * 100 : 0;
            return [
                'name' => \Illuminate\Support\Str::limit($acc->name, 20),
                'balance' => (float) $acc->current_balance,
                'percent' => round($percent, 1),
            ];
        });

        // 4. Recent Transactions
        $endDate = now()->endOfDay()->format('Y-m-d H:i:s');
        $recentTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [now()->subDays(30)->format('Y-m-d'), $endDate])
            ->when($locationId, function($q) use ($locationId) {
                $q->whereHas('paymentMethod', function($sub) use ($locationId) {
                    $sub->where(function($inner) use ($locationId) {
                        $inner->whereNull('location_id')
                            ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                    });
                });
            })
            ->with(['paymentMethod'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->transaction_date->format('M d, Y H:i'),
                    'account_name' => $transaction->paymentMethod->name ?? 'N/A',
                    'type' => $transaction->transaction_type,
                    'type_label' => ucfirst(str_replace('_', ' ', $transaction->transaction_type)),
                    'category' => $transaction->transaction_category,
                    'amount' => (float) $transaction->amount,
                    'balance_after' => (float) $transaction->balance_after,
                    'status' => $transaction->status,
                    'status_color' => match($transaction->status) {
                        'COMPLETED' => 'success',
                        'PENDING' => 'warning',
                        'FAILED' => 'danger',
                        'CANCELLED' => 'secondary',
                        'REVERSED' => 'info',
                        default => 'secondary'
                    },
                    'type_color' => match($transaction->transaction_type) {
                        'DEPOSIT', 'TRANSFER_IN', 'REFUND' => 'success',
                        'WITHDRAWAL', 'TRANSFER_OUT' => 'danger',
                        'FEE' => 'secondary',
                        'ADJUSTMENT' => 'dark',
                        default => 'secondary'
                    }
                ];
            });

        return response()->json([
            'success' => true,
            'accounts' => $accountsData,
            'summary' => $summary,
            'distribution' => $distribution,
            'recent_transactions' => $recentTransactions,
        ]);
    }
        
    // 3. Transaction Ledger Report
    public function transactionLedger(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        // Default filters for initial page load
        $filters = [
            'start_date' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'transaction_type' => $request->get('transaction_type'),
            'payment_method_id' => $request->get('payment_method_id'),
            'status' => $request->get('status', 'COMPLETED'),
            'location_id' => $request->get('location_id'),
            'user_id' => $request->get('user_id'),
        ];
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)->where('is_active', true)->get(['id', 'name']);
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $transactionTypes = PaymentTransactionLog::distinct('transaction_type')->pluck('transaction_type');
        
        // Users who have processed transactions
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        return view('basic-accounting.transaction-ledger', compact(
            'filters', 'paymentMethods', 'transactionTypes', 'locations', 'users'
        ));
    }

    public function transactionLedgerData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $filters = [
            'start_date' => $request->get('start_date', now()->subDays(30)->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'transaction_type' => $request->get('transaction_type'),
            'payment_method_id' => $request->get('payment_method_id'),
            'status' => $request->get('status', 'COMPLETED'),
            'location_id' => $request->get('location_id'),
            'user_id' => $request->get('user_id'),
            'search' => $request->get('search'),
        ];
        
        $startDateTime = $filters['start_date'] . ' 00:00:00';
        $endDateTime = $filters['end_date'] . ' 23:59:59';
        
        $query = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->with(['paymentMethod', 'currency', 'customer', 'user']);
        
        // Apply filters
        if ($filters['start_date']) $query->where('transaction_date', '>=', $startDateTime);
        if ($filters['end_date']) $query->where('transaction_date', '<=', $endDateTime);
        if ($filters['transaction_type']) $query->where('transaction_type', $filters['transaction_type']);
        if ($filters['payment_method_id']) $query->where('payment_method_id', $filters['payment_method_id']);
        if ($filters['status']) $query->where('status', $filters['status']);
        if ($filters['user_id']) $query->where('user_id', $filters['user_id']);
        
        if ($filters['location_id']) {
            $query->whereHas('paymentMethod', function($q) use ($filters) {
                $q->where(function($sub) use ($filters) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$filters['location_id'])]);
                });
            });
        }

        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('transaction_ref', 'like', "%{$search}%")
                ->orWhere('receipt_number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('paymentMethod', fn($pm) => $pm->where('name', 'like', "%{$search}%"));
            });
        }
        
        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(15);
        
        // ✅ FIX: Extract items() before mapping so it serializes as a clean JSON array
        $rows = collect($transactions->items())->map(function($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_ref' => $transaction->transaction_ref,
                'receipt_number' => $transaction->receipt_number,
                'date' => $transaction->transaction_date->format('M d, Y'),
                'time' => $transaction->transaction_date->format('H:i:s'),
                'payment_method_name' => $transaction->paymentMethod->name ?? 'N/A',
                'user_name' => $transaction->user->name ?? null,
                'type' => $transaction->transaction_type,
                'category' => $transaction->transaction_category,
                'description' => \Str::limit($transaction->description, 40),
                'notes' => \Str::limit($transaction->notes, 30),
                'amount' => from_base_currency($transaction->amount),
                'balance_after' => from_base_currency($transaction->balance_after),
                'status' => $transaction->status,
            ];
        });

        // Summary calculations
        $inflowTypes = ['DEPOSIT', 'TRANSFER_IN', 'REFUND'];
        $outflowTypes = ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'];
        $collection = $transactions->getCollection();

        $inflowTotal = (float) $collection->whereIn('transaction_type', $inflowTypes)->sum('amount');
        $outflowTotal = (float) $collection->whereIn('transaction_type', $outflowTypes)->sum('amount');
        $netChange = $inflowTotal - $outflowTotal;
        $totalMoved = $inflowTotal + $outflowTotal;
        
        $summary = [
            'total_count' => $transactions->total(),
            'inflow_total' => $inflowTotal,
            'outflow_total' => $outflowTotal,
            'net_change' => $netChange,
            'average_amount' => $transactions->count() > 0 ? $totalMoved / $transactions->count() : 0,
            'inflow_count' => $collection->whereIn('transaction_type', $inflowTypes)->count(),
            'outflow_count' => $collection->whereIn('transaction_type', $outflowTypes)->count(),
            'current_total_balance' => (float) PaymentMethod::where('tenant_id', $tenantId)->where('is_active', true)->sum('current_balance'),
            'start_date_display' => \Carbon\Carbon::parse($filters['start_date'])->format('M d'),
            'end_date_display' => \Carbon\Carbon::parse($filters['end_date'])->format('M d, Y'),
        ];

        return response()->json([
            'success' => true,
            'data' => $rows,
            'summary' => $summary,
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ]
        ]);
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
            ->with(['paymentMethod.currency', 'customer', 'user'])
            ->firstOrFail();
        
        // Convert amounts
        $transaction->amount = from_base_currency($transaction->amount);
        $transaction->balance_before = from_base_currency($transaction->balance_before);
        $transaction->balance_after = from_base_currency($transaction->balance_after);
        $transaction->transaction_fee = from_base_currency($transaction->transaction_fee);
        $transaction->net_amount = from_base_currency($transaction->net_amount);
        
        // Handle metadata
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
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        $period = $request->get('period', 'month');
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');

        return view('basic-accounting.income-statement', compact('locations', 'users', 'period', 'locationId', 'userId'));
    }

    public function incomeStatementData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $period = $request->get('period', 'month');
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        
        switch ($period) {
            case 'quarter': $startDate = now()->startOfQuarter()->format('Y-m-d'); break;
            case 'year': $startDate = now()->startOfYear()->format('Y-m-d'); break;
            case 'custom': $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d')); break;
            default: $startDate = now()->startOfMonth()->format('Y-m-d');
        }
        
        $endDate = $period === 'custom' ? ($request->get('end_date', now()->format('Y-m-d')) . ' 23:59:59') : now()->endOfDay()->format('Y-m-d H:i:s');
        $displayStartDate = date('Y-m-d', strtotime($startDate));
        $displayEndDate = date('Y-m-d', strtotime($endDate));
        
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        if ($userId) $baseQuery->where('user_id', $userId);
        
        $revenueCents = (clone $baseQuery)->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
        $expensesCents = (clone $baseQuery)->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
        
        $revenue = from_base_currency($revenueCents);
        $expenses = from_base_currency($expensesCents);
        $netIncome = $revenue - $expenses;
        $profitMargin = $revenue > 0 ? ($netIncome / $revenue) * 100 : 0;
        $expenseRatio = $revenue > 0 ? ($expenses / $revenue) * 100 : 0;
        
        $revenueByCategory = (clone $baseQuery)->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])
            ->select('transaction_category', DB::raw('SUM(amount) as total_cents'))->groupBy('transaction_category')->get()
            ->map(function($item) use ($revenue) {
                return [
                    'category' => $item->transaction_category,
                    'total' => from_base_currency($item->total_cents),
                    'percentage' => $revenue > 0 ? (from_base_currency($item->total_cents) / $revenue) * 100 : 0,
                ];
            });
        
        $expensesByCategory = (clone $baseQuery)->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->select('transaction_category', DB::raw('SUM(amount) as total_cents'))->groupBy('transaction_category')->get()
            ->map(function($item) use ($expenses) {
                return [
                    'category' => $item->transaction_category,
                    'total' => from_base_currency($item->total_cents),
                    'percentage' => $expenses > 0 ? (from_base_currency($item->total_cents) / $expenses) * 100 : 0,
                ];
            });
            
        $monthlyTrends = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])->where('status', 'COMPLETED')
            ->when($locationId, function($q) use ($locationId) {
                $q->whereHas('paymentMethod', fn($sub) => $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]));
            })
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->select(
                DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as revenue_cents'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as expenses_cents')
            )->groupBy('month')->orderBy('month')->get()
            ->map(function($item) {
                $rev = from_base_currency($item->revenue_cents);
                $exp = from_base_currency($item->expenses_cents);
                $net = $rev - $exp;
                return [
                    'month' => \Carbon\Carbon::parse($item->month)->format('M Y'),
                    'revenue' => $rev,
                    'expenses' => $exp,
                    'net_income' => $net,
                    'margin' => $rev > 0 ? ($net / $rev) * 100 : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_income' => $netIncome,
                'profit_margin' => $profitMargin,
                'expense_ratio' => $expenseRatio,
                'start_date' => \Carbon\Carbon::parse($displayStartDate)->format('M d'),
                'end_date' => \Carbon\Carbon::parse($displayEndDate)->format('M d, Y'),
            ],
            'revenue_breakdown' => $revenueByCategory,
            'expenses_breakdown' => $expensesByCategory,
            'monthly_trends' => $monthlyTrends,
        ]);
    }
        
    // 5. Cash Flow Report
    public function cashFlow(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');

        return view('basic-accounting.cash-flow', compact('locations', 'users', 'startDate', 'endDate', 'locationId', 'userId'));
    }

    public function cashFlowData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        
        // Ensure end date includes the full day
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDateTime, $endDateTime] = [$endDate . ' 00:00:00', $startDate . ' 23:59:59'];
        }
        
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
            ->where('status', 'COMPLETED');
        
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        if ($userId) $baseQuery->where('user_id', $userId);
        
        // Daily Cash Flow
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
                return [
                    'date' => \Carbon\Carbon::parse($item->date)->format('M d, Y'),
                    'day_name' => \Carbon\Carbon::parse($item->date)->format('l'),
                    'cash_in' => from_base_currency($item->cash_in_cents),
                    'cash_out' => from_base_currency($item->cash_out_cents),
                    'transaction_count' => $item->transaction_count,
                ];
            });
        
        // Cash Flow By Method
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
                return [
                    'name' => $item->paymentMethod->name ?? 'N/A',
                    'type' => $item->paymentMethod->type ?? '',
                    'cash_in' => from_base_currency($item->cash_in_cents),
                    'cash_out' => from_base_currency($item->cash_out_cents),
                    'transaction_count' => $item->transaction_count,
                ];
            });
            
        $totalCashIn = $dailyCashFlow->sum('cash_in');
        $totalCashOut = $dailyCashFlow->sum('cash_out');
        $netCashFlow = $totalCashIn - $totalCashOut;
        
        return response()->json([
            'success' => true,
            'summary' => [
                'total_cash_in' => $totalCashIn,
                'total_cash_out' => $totalCashOut,
                'net_cash_flow' => $netCashFlow,
                'total_transactions' => $dailyCashFlow->sum('transaction_count'),
                'start_date' => \Carbon\Carbon::parse($startDate)->format('M d'),
                'end_date' => \Carbon\Carbon::parse($endDate)->format('M d, Y'),
                'days_count' => max(1, \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1),
            ],
            'daily_flow' => $dailyCashFlow,
            'by_method' => $cashFlowByMethod,
        ]);
    }
            
    // 6. Transaction Analysis Report
    public function transactionAnalysis(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');

        return view('basic-accounting.transaction-analysis', compact('locations', 'users', 'startDate', 'endDate', 'locationId', 'userId'));
    }

    public function transactionAnalysisData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
            ->where('status', 'COMPLETED');
        
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        if ($userId) $baseQuery->where('user_id', $userId);
        
        // 1. By Type
        $volumeByType = (clone $baseQuery)
            ->select('transaction_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_cents'), DB::raw('AVG(amount) as avg_cents'))
            ->groupBy('transaction_type')->get()
            ->map(function($item) {
                return [
                    'type' => $item->transaction_type,
                    'count' => $item->count,
                    'total' => from_base_currency($item->total_cents),
                    'avg' => from_base_currency($item->avg_cents),
                ];
            });

        // 2. By Category
        $volumeByCategory = (clone $baseQuery)
            ->select('transaction_category', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_cents'), DB::raw('AVG(amount) as avg_cents'))
            ->groupBy('transaction_category')->orderBy('total_cents', 'desc')->get()
            ->map(function($item) {
                return [
                    'category' => $item->transaction_category,
                    'count' => $item->count,
                    'total' => from_base_currency($item->total_cents),
                    'avg' => from_base_currency($item->avg_cents),
                ];
            });

        // 3. Daily Trends
        $dailyTrends = (clone $baseQuery)
            ->select(DB::raw('DATE(transaction_date) as date'), DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(amount) as daily_total_cents'))
            ->groupBy(DB::raw('DATE(transaction_date)'))->orderBy('date')->get()
            ->map(function($item) {
                return [
                    'date' => \Carbon\Carbon::parse($item->date)->format('M d, Y'),
                    'day_name' => \Carbon\Carbon::parse($item->date)->format('l'),
                    'count' => $item->transaction_count,
                    'total' => from_base_currency($item->daily_total_cents),
                ];
            });

        // 4. Top Transactions (Paginated)
        $topTransactions = (clone $baseQuery)
            ->with(['paymentMethod', 'user'])
            ->orderBy('amount', 'desc')
            ->paginate(15);

        $txRows = collect($topTransactions->items())->map(function($tx) {
            return [
                'id' => $tx->id,
                'date' => $tx->transaction_date->format('M d, Y'),
                'time' => $tx->transaction_date->format('H:i:s'),
                'description' => \Str::limit($tx->description, 50),
                'notes' => \Str::limit($tx->notes, 30),
                'payment_method' => $tx->paymentMethod->name ?? 'N/A',
                'user' => $tx->user->name ?? null,
                'type' => $tx->transaction_type,
                'category' => $tx->transaction_category,
                'amount' => from_base_currency($tx->amount),
                'balance_after' => from_base_currency($tx->balance_after),
            ];
        });

        // 5. Summary Stats
        $totalCount = $volumeByType->sum('count');
        $totalVolume = $volumeByType->sum('total');
        
        $summary = [
            'total_count' => $totalCount,
            'total_volume' => $totalVolume,
            'avg_transaction' => $totalCount > 0 ? $totalVolume / $totalCount : 0,
            'avg_daily' => $dailyTrends->count() > 0 ? $totalVolume / $dailyTrends->count() : 0,
            'days_count' => $dailyTrends->count(),
            'start_date' => \Carbon\Carbon::parse($startDate)->format('M d'),
            'end_date' => \Carbon\Carbon::parse($endDate)->format('M d, Y'),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'by_type' => $volumeByType,
            'by_category' => $volumeByCategory,
            'daily_trends' => $dailyTrends,
            'top_transactions' => $txRows,
            'pagination' => [
                'current_page' => $topTransactions->currentPage(),
                'last_page' => $topTransactions->lastPage(),
                'from' => $topTransactions->firstItem(),
                'to' => $topTransactions->lastItem(),
                'total' => $topTransactions->total(),
            ]
        ]);
    }
        
    // 7. Expense Analysis Report
    public function expenseAnalysis(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');

        return view('basic-accounting.expense-analysis', compact('locations', 'users', 'startDate', 'endDate', 'locationId', 'userId'));
    }

    public function expenseAnalysisData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])
            ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
            ->where('status', 'COMPLETED');
        
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        if ($userId) $baseQuery->where('user_id', $userId);
        
        // Summary
        $summaryRaw = (clone $baseQuery)->select(
            DB::raw('SUM(amount) as total_cents'),
            DB::raw('COUNT(*) as count'),
            DB::raw('AVG(amount) as avg_cents'),
            DB::raw('MAX(amount) as max_cents')
        )->first();
        
        $totalCents = (int) ($summaryRaw->total_cents ?? 0);
        $totalAmount = from_base_currency($totalCents);
        $count = (int) ($summaryRaw->count ?? 0);
        
        $days = max(1, \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1);
        
        $summary = [
            'total_expenses' => $totalAmount,
            'expense_count' => $count,
            'average_expense' => from_base_currency($summaryRaw->avg_cents ?? 0),
            'largest_expense' => from_base_currency($summaryRaw->max_cents ?? 0),
            'days' => $days,
            'start_date' => \Carbon\Carbon::parse($startDate)->format('M d'),
            'end_date' => \Carbon\Carbon::parse($endDate)->format('M d, Y'),
        ];
        
        // By Category
        $byCategory = (clone $baseQuery)
            ->select('transaction_category', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_cents'), DB::raw('AVG(amount) as avg_cents'), DB::raw('MAX(amount) as max_cents'))
            ->groupBy('transaction_category')
            ->orderBy('total_cents', 'desc')
            ->get()
            ->map(function($item) use ($totalCents) {
                $total = from_base_currency($item->total_cents);
                $pct = $totalCents > 0 ? ($item->total_cents / $totalCents) * 100 : 0;
                $avg = from_base_currency($item->avg_cents);
                
                // "Trend" heuristic matching the old blade logic
                $trendColor = $avg > 1000 ? 'danger' : ($avg > 500 ? 'warning' : 'success');
                $trendLabel = $avg > 1000 ? 'High' : ($avg > 500 ? 'Medium' : 'Low');
                
                return [
                    'category' => $item->transaction_category,
                    'count' => $item->count,
                    'total' => $total,
                    'avg' => $avg,
                    'max' => from_base_currency($item->max_cents),
                    'percentage' => $pct,
                    'trend_color' => $trendColor,
                    'trend_label' => $trendLabel,
                ];
            });
            
        // Top Expenses (Paginated)
        $topExpenses = (clone $baseQuery)->with(['paymentMethod', 'user'])->orderBy('amount', 'desc')->paginate(10);
        
        $topRows = collect($topExpenses->items())->map(function($tx) {
            return [
                'id' => $tx->id,
                'date' => $tx->transaction_date->format('M d, Y'),
                'time' => $tx->transaction_date->format('H:i:s'),
                'description' => $tx->description, // Full text
                'notes' => $tx->notes,             // Full text
                'payment_method' => $tx->paymentMethod->name ?? 'N/A',
                'payment_method_type' => $tx->paymentMethod->type ?? '',
                'category' => $tx->transaction_category,
                'amount' => from_base_currency($tx->amount),
                'balance_after' => from_base_currency($tx->balance_after),
                'user' => $tx->user ? $tx->user->name : 'System',
                'status' => $tx->status,
            ];
        });

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'by_category' => $byCategory,
            'top_expenses' => $topRows,
            'pagination' => [
                'current_page' => $topExpenses->currentPage(),
                'last_page' => $topExpenses->lastPage(),
                'from' => $topExpenses->firstItem(),
                'to' => $topExpenses->lastItem(),
                'total' => $topExpenses->total(),
            ]
        ]);
    }
            
    // 8. Payment Method Analysis Report
    public function paymentMethodAnalysis(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');

        return view('basic-accounting.payment-method-analysis', compact('locations', 'users', 'startDate', 'endDate', 'locationId', 'userId'));
    }

    public function paymentMethodAnalysisData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        
        $paymentMethodsQuery = PaymentMethod::where('tenant_id', $tenantId)->with(['currency']);
        
        if ($locationId) {
            $paymentMethodsQuery->where(function($q) use ($locationId) {
                $q->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
            });
        }
        
        $methods = $paymentMethodsQuery->get()->map(function ($method) use ($tenantId, $startDateTime, $endDateTime, $userId) {
            $transactionQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
                ->where('payment_method_id', $method->id)
                ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
                ->where('status', 'COMPLETED');
            
            if ($userId) $transactionQuery->where('user_id', $userId);
            
            $transactions = $transactionQuery->get();
            
            $totalAmount = (float) $transactions->sum('amount');
            $depositTotal = (float) $transactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
            $withdrawalTotal = (float) $transactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
            $totalTx = $transactions->count();
            
            return [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'type' => $method->type,
                'type_label' => $method->getTypeLabel(),
                'current_balance' => (float) $method->current_balance,
                'is_active' => (bool) $method->is_active,
                'total_transactions' => $totalTx,
                'total_amount' => $totalAmount,
                'deposit_total' => $depositTotal,
                'withdrawal_total' => $withdrawalTotal,
                'deposit_count' => $transactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->count(),
                'withdrawal_count' => $transactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->count(),
                'net_flow' => $depositTotal - $withdrawalTotal,
                'avg_transaction' => $totalTx > 0 ? $totalAmount / $totalTx : 0,
            ];
        });
        
        $stats = [
            'total_balance' => (float) $methods->sum('current_balance'),
            'total_transactions' => $methods->sum('total_transactions'),
            'total_transaction_amount' => $methods->sum('total_amount'),
        ];
        
        $mostActive = $methods->sortByDesc('total_transactions')->first();
        $highestBalance = $methods->sortByDesc('current_balance')->first();
        
        $stats['most_active_method'] = $mostActive ? ['name' => $mostActive['name'], 'total_transactions' => $mostActive['total_transactions']] : null;
        $stats['highest_balance_method'] = $highestBalance ? ['name' => $highestBalance['name'], 'balance' => $highestBalance['current_balance']] : null;

        return response()->json([
            'success' => true,
            'methods' => $methods->values(),
            'stats' => $stats,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    // 9. Daily Summary Report
    public function dailySummary(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        $date = $request->get('date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        $perPage = (int)$request->get('per_page', 15);

        return view('basic-accounting.daily-summary', compact('locations', 'users', 'date', 'locationId', 'userId', 'perPage'));
    }

    public function dailySummaryData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $date = $request->get('date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        $perPage = (int)$request->get('per_page', 15);
        
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', $date)
            ->where('status', 'COMPLETED');
        
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        if ($userId) $baseQuery->where('user_id', $userId);
        
        $allTransactions = (clone $baseQuery)->get();
        
        $depositTotal = (float) $allTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
        $withdrawalTotal = (float) $allTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
        
        $summary = [
            'total_transactions' => $allTransactions->count(),
            'total_amount' => (float) $allTransactions->sum('amount'),
            'deposit_total' => $depositTotal,
            'withdrawal_total' => $withdrawalTotal,
            'net_cash_flow' => $depositTotal - $withdrawalTotal,
            'date' => \Carbon\Carbon::parse($date)->format('M d, Y'),
            'day_of_week' => \Carbon\Carbon::parse($date)->format('l'),
        ];
        
        // By Type
        $byType = $allTransactions->groupBy('transaction_type')->map(function($group, $type) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'total' => (float) $group->sum('amount'),
                'average' => (float) $group->avg('amount'),
            ];
        })->values();
        
        // By Category
        $byCategory = $allTransactions->groupBy('transaction_category')->map(function($group, $cat) {
            return [
                'category' => $cat,
                'count' => $group->count(),
                'total' => (float) $group->sum('amount'),
            ];
        })->values();
        
        // Balance Changes
        $balanceChanges = [];
        $groupedByMethod = $allTransactions->groupBy('payment_method_id');
        foreach ($groupedByMethod as $methodId => $transactions) {
            $method = $transactions->first()->paymentMethod;
            $starting = $transactions->first()->balance_before;
            $ending = $transactions->last()->balance_after;
            $balanceChanges[] = [
                'method_name' => $method->name ?? 'N/A',
                'method_type' => $method->type ?? '',
                'starting_balance' => (float) $starting,
                'ending_balance' => (float) $ending,
                'net_change' => (float) ($ending - $starting),
            ];
        }
        
        // Daily Transactions (Paginated)
        $transactions = (clone $baseQuery)->with(['paymentMethod', 'user'])->orderBy('transaction_date', 'desc')->paginate($perPage);
        $txRows = collect($transactions->items())->map(function($tx) {
            return [
                'id' => $tx->id,
                'time' => $tx->transaction_date->format('H:i:s'),
                'description' => $tx->description,
                'notes' => $tx->notes,
                'payment_method' => $tx->paymentMethod->name ?? 'N/A',
                'payment_method_type' => $tx->paymentMethod->type ?? '',
                'type' => $tx->transaction_type,
                'category' => $tx->transaction_category,
                'amount' => (float) $tx->amount,
                'balance_after' => (float) $tx->balance_after,
                'user' => $tx->user ? $tx->user->name : 'System',
                'status' => $tx->status,
            ];
        });

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'by_type' => $byType,
            'by_category' => $byCategory,
            'balance_changes' => $balanceChanges,
            'transactions' => $txRows,
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
                'total' => $transactions->total(),
            ]
        ]);
    }
    
    // 10. Monthly Report
    public function monthlyReport(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');

        return view('basic-accounting.monthly-report', compact('locations', 'users', 'month', 'year', 'locationId', 'userId'));
    }

    public function monthlyReportData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        
        $month = str_pad(preg_replace('/[^0-9]/', '', $month), 2, '0', STR_PAD_LEFT);
        $year = intval($year);
        
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        if ($userId) $baseQuery->where('user_id', $userId);
        
        $transactions = (clone $baseQuery)->get();
        $depositTotal = (float) $transactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
        $withdrawalTotal = (float) $transactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
        
        $summary = [
            'total_transactions' => $transactions->count(),
            'total_amount' => (float) $transactions->sum('amount'),
            'deposit_total' => $depositTotal,
            'withdrawal_total' => $withdrawalTotal,
            'net_cash_flow' => $depositTotal - $withdrawalTotal,
            'month_name' => date('F', mktime(0, 0, 0, (int)$month, 10)),
            'year' => $year,
            'days_in_month' => (int) date('t', strtotime($startDate)),
        ];
        
        // Daily Breakdown
        $dailyBreakdown = (clone $baseQuery)
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as deposits'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as withdrawals'),
                DB::raw('SUM(amount) as total')
            )->groupBy(DB::raw('DATE(transaction_date)'))->orderBy('date')->get()
            ->map(function($item) {
                return [
                    'date' => \Carbon\Carbon::parse($item->date)->format('M d, Y'),
                    'day_name' => \Carbon\Carbon::parse($item->date)->format('l'),
                    'count' => $item->count,
                    'deposits' => from_base_currency($item->deposits),
                    'withdrawals' => from_base_currency($item->withdrawals),
                    'total' => from_base_currency($item->total),
                    'avg' => $item->count > 0 ? from_base_currency($item->total / $item->count) : 0,
                ];
            });

        // Category Breakdown
        $categoryBreakdown = (clone $baseQuery)
            ->select('transaction_category', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'), DB::raw('AVG(amount) as avg'))
            ->groupBy('transaction_category')->orderBy('total', 'desc')->get()
            ->map(function($item) {
                return [
                    'category' => $item->transaction_category,
                    'count' => $item->count,
                    'total' => from_base_currency($item->total),
                    'avg' => from_base_currency($item->avg),
                ];
            });

        // Method Breakdown
        $methodBreakdown = (clone $baseQuery)
            ->with('paymentMethod')
            ->select('payment_method_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method_id')->orderBy('total', 'desc')->get()
            ->map(function($item) {
                return [
                    'name' => $item->paymentMethod->name ?? 'N/A',
                    'type' => $item->paymentMethod->type ?? '',
                    'count' => $item->count,
                    'total' => from_base_currency($item->total),
                    'avg' => $item->count > 0 ? from_base_currency($item->total / $item->count) : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'daily_breakdown' => $dailyBreakdown,
            'category_breakdown' => $categoryBreakdown,
            'method_breakdown' => $methodBreakdown,
        ]);
    }

    // 11. Weekly Report with 6-Month Comparison
    public function weeklyReport(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        $week = $request->input('week', date('W'));
        $year = $request->input('year', date('Y'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');

        return view('basic-accounting.weekly-report', compact('locations', 'users', 'week', 'year', 'locationId', 'userId'));
    }

    public function weeklyReportData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $week = (int) $request->input('week', date('W'));
        $year = (int) $request->input('year', date('Y'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        $perPage = (int)$request->get('per_page', 15);
        
        if ($week < 1 || $week > 53) $week = (int) date('W');
        if ($year < 2000 || $year > 2100) $year = (int) date('Y');
        
        $startDate = date('Y-m-d', strtotime($year . 'W' . str_pad($week, 2, '0', STR_PAD_LEFT) . '1'));
        $endDate = date('Y-m-d 23:59:59', strtotime($startDate . ' +6 days'));
        
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        if ($userId) $baseQuery->where('user_id', $userId);
        
        $allTransactions = (clone $baseQuery)->get();
        $depositTotal = (float) $allTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
        $withdrawalTotal = (float) $allTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
        
        $summary = [
            'total_transactions' => $allTransactions->count(),
            'total_amount' => (float) $allTransactions->sum('amount'),
            'deposit_total' => $depositTotal,
            'withdrawal_total' => $withdrawalTotal,
            'net_cash_flow' => $depositTotal - $withdrawalTotal,
            'week_start' => date('M d', strtotime($startDate)),
            'week_end' => date('M d, Y', strtotime($endDate)),
            'week_number' => $week,
            'year' => $year,
        ];
        
        // Daily Breakdown
        $dailyBreakdown = (clone $baseQuery)
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as deposits'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as withdrawals'),
                DB::raw('SUM(amount) as total')
            )->groupBy(DB::raw('DATE(transaction_date)'))->orderBy('date')->get()
            ->map(function($item) {
                return [
                    'date' => \Carbon\Carbon::parse($item->date)->format('M d, Y'),
                    'day_name' => \Carbon\Carbon::parse($item->date)->format('l'),
                    'count' => $item->count,
                    'deposits' => from_base_currency($item->deposits),
                    'withdrawals' => from_base_currency($item->withdrawals),
                    'total' => from_base_currency($item->total),
                ];
            });
        
        // Six Months Trend
        $monthNames = [1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Aug', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dec'];
        $sixMonthsData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $mDate = date('Y-m', strtotime("-$i months"));
            $mStart = $mDate . '-01 00:00:00';
            $mEnd = date('Y-m-t 23:59:59', strtotime("-$i months"));
            
            $mQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
                ->whereBetween('transaction_date', [$mStart, $mEnd])->where('status', 'COMPLETED');
            
            if ($locationId) {
                $mQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                    $q->where(function($sub) use ($locationId) {
                        $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                    });
                });
            }
            if ($userId) $mQuery->where('user_id', $userId);
            
            $mTx = $mQuery->get();
            $mDep = (float) $mTx->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
            $mWd = (float) $mTx->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
            
            $sixMonthsData[] = [
                'month' => $monthNames[(int)date('m', strtotime("-$i months"))] . ' ' . substr(date('Y', strtotime("-$i months")), -2),
                'deposits' => $mDep,
                'withdrawals' => $mWd,
                'net' => $mDep - $mWd,
                'transactions' => $mTx->count(),
            ];
        }
        
        // Paginated Transactions
        $transactions = (clone $baseQuery)->with(['paymentMethod', 'user'])->orderBy('transaction_date', 'desc')->paginate($perPage);
        $txRows = collect($transactions->items())->map(function($tx) {
            return [
                'id' => $tx->id,
                'date' => $tx->transaction_date->format('M d, Y'),
                'time' => $tx->transaction_date->format('H:i:s'),
                'description' => $tx->description,
                'notes' => $tx->notes,
                'payment_method' => $tx->paymentMethod->name ?? 'N/A',
                'payment_method_type' => $tx->paymentMethod->type ?? '',
                'type' => $tx->transaction_type,
                'category' => $tx->transaction_category,
                'amount' => from_base_currency($tx->amount),
                'user' => $tx->user ? $tx->user->name : 'System',
                'status' => $tx->status,
            ];
        });

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'daily_breakdown' => $dailyBreakdown,
            'six_months_data' => $sixMonthsData,
            'transactions' => $txRows,
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    // 12. User Performance Report - Top Profit/Loss Makers
    public function userPerformanceReport(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $limit = $request->get('limit', 10);
        $perPage = (int)$request->get('per_page', 15);

        return view('basic-accounting.user-performance-report', compact('locations', 'startDate', 'endDate', 'locationId', 'limit', 'perPage'));
    }

    public function userPerformanceReportData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        
        $locationId = $request->get('location_id');
        $limit = (int) $request->get('limit', 10);
        $perPage = (int) $request->get('per_page', 15);
        
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDateTime, $endDateTime] = [$endDate . ' 00:00:00', $startDate . ' 23:59:59'];
        }
        
        $baseQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
            ->where('status', 'COMPLETED')
            ->whereNotNull('user_id');
        
        if ($locationId) {
            $baseQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        
        // Group by user
        $userPerformance = (clone $baseQuery)
            ->select(
                'user_id',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as total_deposits'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as total_withdrawals'),
                DB::raw('AVG(amount) as average_transaction'),
                DB::raw('MAX(amount) as largest_transaction')
            )
            ->groupBy('user_id')
            ->get()
            ->map(function($item) use ($tenantId) {
                $userInfo = \App\Models\User::find($item->user_id);
                
                $netAmount = $item->total_deposits - $item->total_withdrawals;
                $totalAmount = $item->total_amount;
                $perfPct = $totalAmount > 0 ? (abs($netAmount) / $totalAmount) * 100 : 0;
                
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $userInfo->name ?? 'Unknown',
                    'user_email' => $userInfo->email ?? '',
                    'total_transactions' => $item->total_transactions,
                    'total_amount' => from_base_currency($totalAmount),
                    'total_deposits' => from_base_currency($item->total_deposits),
                    'total_withdrawals' => from_base_currency($item->total_withdrawals),
                    'net_amount' => from_base_currency($netAmount),
                    'average_transaction' => from_base_currency($item->average_transaction),
                    'largest_transaction' => from_base_currency($item->largest_transaction),
                    'performance_percent' => round($perfPct, 1),
                ];
            });

        // Sort for rankings
        $topProfitMakers = $userPerformance->sortByDesc('net_amount')->values()->take($limit);
        $topLossMakers = $userPerformance->sortBy('net_amount')->values()->take($limit);
        
        // Calculate summary
        $summary = [
            'total_users' => $userPerformance->count(),
            'total_transactions' => $userPerformance->sum('total_transactions'),
            'total_volume' => from_base_currency($userPerformance->sum('total_amount')),
            'average_per_user' => $userPerformance->count() > 0 ? from_base_currency($userPerformance->sum('total_amount') / $userPerformance->count()) : 0,
            'total_profit' => from_base_currency($userPerformance->sum('net_amount')),
            'best_user' => $topProfitMakers->first(),
            'worst_user' => $topLossMakers->first(),
        ];
        
        // Recent transactions for top users
        $topUserIds = $topProfitMakers->pluck('user_id')->merge($topLossMakers->pluck('user_id'))->unique()->toArray();
        
        $recentTransactions = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
            ->where('status', 'COMPLETED')
            ->whereIn('user_id', $topUserIds)
            ->with(['paymentMethod', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
            
        $txRows = collect($recentTransactions->items())->map(function($tx) {
            return [
                'id' => $tx->id,
                'date' => $tx->transaction_date->format('M d, Y'),
                'time' => $tx->transaction_date->format('H:i:s'),
                'user' => $tx->user->name ?? 'System',
                'description' => $tx->description,
                'notes' => $tx->notes,
                'payment_method' => $tx->paymentMethod->name ?? 'N/A',
                'payment_method_type' => $tx->paymentMethod->type ?? '',
                'type' => $tx->transaction_type,
                'category' => $tx->transaction_category,
                'amount' => from_base_currency($tx->amount),
                'status' => $tx->status,
            ];
        });

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'top_profit' => $topProfitMakers,
            'top_loss' => $topLossMakers,
            'transactions' => $txRows,
            'pagination' => [
                'current_page' => $recentTransactions->currentPage(),
                'last_page' => $recentTransactions->lastPage(),
                'from' => $recentTransactions->firstItem(),
                'to' => $recentTransactions->lastItem(),
                'total' => $recentTransactions->total(),
            ]
        ]);
    }
        
    // 13. Reconciliation Report
    public function reconciliation(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('financial_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        
        $locations = \App\Models\Location::where('tenant_id', $tenantId)->get();
        $userIds = \DB::table('payment_transaction_logs')->where('tenant_id', $tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = \App\Models\User::where('tenant_id', $tenantId)->whereIn('id', $userIds)->get(['id', 'name']);
        
        $date = $request->get('date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');

        return view('basic-accounting.reconciliation', compact('locations', 'users', 'date', 'locationId', 'userId'));
    }

    public function reconciliationData(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('financial reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        
        $date = $request->get('date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $userId = $request->get('user_id');
        
        $paymentMethodsQuery = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['currency']);
        
        if ($locationId) {
            $paymentMethodsQuery->where(function($q) use ($locationId) {
                $q->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
            });
        }
        
        $methods = $paymentMethodsQuery->get()->map(function ($method) use ($tenantId, $date, $userId) {
            $transactionQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
                ->where('payment_method_id', $method->id)
                ->whereDate('transaction_date', $date)
                ->where('status', 'COMPLETED');
            
            if ($userId) $transactionQuery->where('user_id', $userId);
            
            $transactions = $transactionQuery->get();
            
            $netChange = 0;
            foreach ($transactions as $t) {
                $netChange += in_array($t->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? $t->amount : -$t->amount;
            }
            
            $expectedBalance = $method->current_balance - $netChange;
            $startingBalance = $method->current_balance - $netChange;
            $discrepancy = abs($method->current_balance - $expectedBalance);
            
            return [
                'id' => $method->id,
                'name' => $method->name,
                'type' => $method->type,
                'type_label' => $method->getTypeLabel(),
                'starting_balance' => from_base_currency($startingBalance),
                'net_change' => from_base_currency($netChange),
                'expected_balance' => from_base_currency($expectedBalance),
                'current_balance' => from_base_currency($method->current_balance),
                'discrepancy' => from_base_currency($discrepancy),
                'is_reconciled' => $discrepancy < 0.01,
                'transaction_count' => $transactions->count(),
            ];
        });
        
        $summary = [
            'total_methods' => $methods->count(),
            'reconciled_methods' => $methods->where('is_reconciled', true)->count(),
            'unreconciled_methods' => $methods->where('is_reconciled', false)->count(),
            'total_discrepancy' => from_base_currency($methods->sum('discrepancy')),
            'total_transactions' => $methods->sum('transaction_count'),
            'date' => \Carbon\Carbon::parse($date)->format('M d, Y'),
        ];
        
        // Unreconciled transactions
        $unreconciledQuery = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', $date)
            ->where('status', 'PENDING')
            ->with(['paymentMethod', 'user']);
        
        if ($locationId) {
            $unreconciledQuery->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }
        if ($userId) $unreconciledQuery->where('user_id', $userId);
        
        $unreconciled = $unreconciledQuery->get()->map(function($t) {
            return [
                'id' => $t->id,
                'date' => $t->transaction_date->format('M d, Y'),
                'time' => $t->transaction_date->format('H:i:s'),
                'description' => $t->description,
                'notes' => $t->notes,
                'payment_method' => $t->paymentMethod->name ?? 'N/A',
                'payment_method_type' => $t->paymentMethod->type ?? '',
                'type' => $t->transaction_type,
                'category' => $t->transaction_category,
                'amount' => from_base_currency($t->amount),
                'user' => $t->user ? $t->user->name : 'System',
            ];
        });
        
        return response()->json([
            'success' => true,
            'methods' => $methods->values(),
            'summary' => $summary,
            'unreconciled' => $unreconciled->values(),
        ]);
    }

}
