<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccount;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedChartOfAccounts extends Command
{
    protected $signature = 'accounting:seed-coa
                            {--tenant= : Seed only this tenant ID}
                            {--force : Re-seed even if the tenant already has accounts}
                            {--dry-run : Show what would be done without saving}';

    protected $description = 'Seed the default chart of accounts for tenants without one.';

    /**
     * Values MUST match the enum in the chart_of_accounts migration.
     * Enum: asset_current, asset_fixed, asset_non_current,
     *       liability_current, liability_long_term,
     *       equity, equity_retained_earnings,
     *       revenue, revenue_other,
     *       expense, expense_cost_of_goods, expense_operating
     */
    protected array $accounts = [
        // Assets
        ['1000', 'Cash',                     'asset_current',           'D'],
        ['1010', 'Bank Account',             'asset_current',           'D'],
        ['1020', 'Mobile Money',             'asset_current',           'D'],
        ['1030', 'Card Clearing',            'asset_current',           'D'],
        ['1100', 'Accounts Receivable',      'asset_current',           'D'],
        ['1200', 'Inventory',                'asset_current',           'D'],
        ['1300', 'Prepaid Expenses',         'asset_current',           'D'],
        ['1400', 'Equipment',                'asset_fixed',             'D'],
        ['1410', 'Accumulated Depreciation', 'asset_fixed',             'C'],
        ['1500', 'Buildings',                'asset_non_current',       'D'],

        // Liabilities
        ['2000', 'Accounts Payable',         'liability_current',       'C'],
        ['2100', 'VAT Payable',              'liability_current',       'C'],
        ['2110', 'Withholding Tax Payable',  'liability_current',       'C'],
        ['2200', 'Salaries Payable',         'liability_current',       'C'],
        ['2300', 'Short-Term Loans',         'liability_current',       'C'],
        ['2400', 'Long-Term Loans',          'liability_long_term',     'C'],

        // Equity
        ['3000', "Owner's Capital",          'equity',                  'C'],
        ['3100', 'Retained Earnings',        'equity_retained_earnings','C'],
        ['3200', "Owner's Drawings",         'equity',                  'D'],

        // Revenue
        ['4000', 'Sales Revenue',            'revenue',                 'C'],
        ['4100', 'Sales Discounts',          'revenue',                 'D'],
        ['4200', 'Sales Returns',            'revenue',                 'D'],
        ['4900', 'Other Income',             'revenue_other',           'C'],

        // COGS
        ['5000', 'Cost of Goods Sold',       'expense_cost_of_goods',   'D'],
        ['5100', 'Inventory Shrinkage',      'expense_cost_of_goods',   'D'],
        ['5200', 'Production Cost',          'expense_cost_of_goods',   'D'],

        // Operating expenses
        ['6000', 'Rent Expense',             'expense_operating',       'D'],
        ['6100', 'Salaries Expense',         'expense_operating',       'D'],
        ['6200', 'Utilities Expense',        'expense_operating',       'D'],
        ['6300', 'Repairs & Maintenance',    'expense_operating',       'D'],
        ['6400', 'Fuel & Transport',         'expense_operating',       'D'],
        ['6500', 'Office Supplies',          'expense_operating',       'D'],
        ['6600', 'Professional Fees',        'expense_operating',       'D'],
        ['6700', 'Marketing & Advertising',  'expense_operating',       'D'],
        ['6800', 'Bank Charges',             'expense_operating',       'D'],
        ['6900', 'Miscellaneous Expense',    'expense_operating',       'D'],
    ];

    public function handle(): int
    {
        $singleTenant = $this->option('tenant');
        $force        = (bool) $this->option('force');
        $dryRun       = (bool) $this->option('dry-run');

        $query = Tenant::query();
        if ($singleTenant) {
            $query->where('id', (int) $singleTenant);
        }

        $tenants = $query->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenants found.');
            return self::FAILURE;
        }

        $this->info(sprintf(
            '%s %d tenant(s)…',
            $dryRun ? '[DRY RUN] Would process' : 'Processing',
            $tenants->count()
        ));

        $seeded  = 0;
        $skipped = 0;
        $failed  = 0;
        $created = 0;

        foreach ($tenants as $tenant) {
            $existing = ChartOfAccount::where('tenant_id', $tenant->id)->count();

            if ($existing > 0 && !$force) {
                $this->line(sprintf(
                    '  <fg=yellow>↷</> Tenant #%d (%s) — already has %d account(s), skipped.',
                    $tenant->id,
                    $tenant->name ?? 'unnamed',
                    $existing
                ));
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '  <fg=cyan>→</> Tenant #%d (%s) — would seed %d accounts.',
                    $tenant->id,
                    $tenant->name ?? 'unnamed',
                    count($this->accounts)
                ));
                $created += count($this->accounts);
                continue;
            }

            try {
                $inserted = DB::transaction(function () use ($tenant, $force) {
                    if ($force) {
                        \App\Models\GeneralLedger::where('tenant_id', $tenant->id)->delete();
                        \App\Models\JournalEntryLine::where('tenant_id', $tenant->id)->delete();
                        \App\Models\JournalEntry::where('tenant_id', $tenant->id)->delete();
                        ChartOfAccount::where('tenant_id', $tenant->id)->forceDelete();
                    }

                    $now  = now();
                    $rows = [];

                    foreach ($this->accounts as [$code, $name, $type, $normal]) {
                        $rows[] = [
                            'tenant_id'         => $tenant->id,
                            'account_code'      => $code,
                            'account_name'      => $name,
                            'account_type'      => $type,
                            'normal_balance'    => $normal,
                            'is_active'         => true,
                            'is_system_account' => true,
                            'description'       => null,
                            'parent_account_id' => null,
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ];
                    }

                    ChartOfAccount::insert($rows);
                    return count($rows);
                });

                $created += $inserted;
                $seeded++;

                $this->line(sprintf(
                    '  <fg=green>✓</> Tenant #%d (%s) — seeded %d accounts.',
                    $tenant->id,
                    $tenant->name ?? 'unnamed',
                    $inserted
                ));

            } catch (\Throwable $e) {
                $failed++;
                $this->line(sprintf(
                    '  <fg=red>✗</> Tenant #%d (%s) — FAILED: %s',
                    $tenant->id,
                    $tenant->name ?? 'unnamed',
                    $e->getMessage()
                ));
                \Log::error('[accounting:seed-coa] Failed', [
                    'tenant_id' => $tenant->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('──────────────────────────────');
        $this->info($dryRun ? 'DRY RUN COMPLETE' : 'DONE');
        $this->table(
            ['Result', 'Count'],
            [
                ['Tenants seeded',   $seeded],
                ['Tenants skipped',  $skipped],
                ['Tenants failed',   $failed],
                ['Accounts created', $created],
            ]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}