<?php
// app/Services/Accounting/JournalEntryService.php

namespace App\Services\Accounting;

use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    public function createSalesEntry($tenantId, $order, $userId): JournalEntry
    {
        return DB::transaction(function () use ($tenantId, $order, $userId) {
            $journal = JournalEntry::create([
                'tenant_id' => $tenantId,
                'entry_date' => now(),
                'description' => "Sales invoice #{$order->invoice_number}",
                'source_module' => 'sales',
                'source_id' => $order->id,
                'created_by' => $userId,
            ]);

            // Debit Accounts Receivable or Cash
            $cashAccount = ChartOfAccount::where('tenant_id', $tenantId)
                ->where('account_code', '1010')
                ->first();

            $journal->lines()->create([
                'account_id' => $cashAccount->id,
                'line_number' => 1,
                'description' => 'Cash received from sale',
                'debit_amount' => $order->total_amount,
            ]);

            // Credit Sales Revenue
            $salesAccount = ChartOfAccount::where('tenant_id', $tenantId)
                ->where('account_code', '4010')
                ->first();

            $journal->lines()->create([
                'account_id' => $salesAccount->id,
                'line_number' => 2,
                'description' => 'Sales revenue',
                'credit_amount' => $order->subtotal,
            ]);

            // Credit Tax Payable if applicable
            if ($order->tax_amount > 0) {
                $taxAccount = ChartOfAccount::where('tenant_id', $tenantId)
                    ->where('account_code', '2020')
                    ->first();

                $journal->lines()->create([
                    'account_id' => $taxAccount->id,
                    'line_number' => 3,
                    'description' => 'Sales tax collected',
                    'credit_amount' => $order->tax_amount,
                ]);
            }

            $journal->recalculateTotals();
            return $journal;
        });
    }

    public function createExpenseEntry($tenantId, $expense, $userId): JournalEntry
    {
        return DB::transaction(function () use ($tenantId, $expense, $userId) {
            $journal = JournalEntry::create([
                'tenant_id' => $tenantId,
                'entry_date' => $expense->date,
                'description' => "Expense: {$expense->description}",
                'source_module' => 'expenses',
                'source_id' => $expense->id,
                'created_by' => $userId,
            ]);

            // Debit Expense Account
            $journal->lines()->create([
                'account_id' => $expense->category->gl_account_id,
                'line_number' => 1,
                'description' => $expense->description,
                'debit_amount' => $expense->total_amount,
            ]);

            // Credit Cash or Accounts Payable
            $cashAccount = ChartOfAccount::where('tenant_id', $tenantId)
                ->where('account_code', '1010')
                ->first();

            $journal->lines()->create([
                'account_id' => $cashAccount->id,
                'line_number' => 2,
                'description' => 'Cash payment for expense',
                'credit_amount' => $expense->total_amount,
            ]);

            $journal->recalculateTotals();
            return $journal;
        });
    }
}