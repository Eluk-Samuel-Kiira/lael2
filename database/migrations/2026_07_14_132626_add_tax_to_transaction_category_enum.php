<?php
// database/migrations/2026_07_14_000003_add_tax_to_transaction_category_enum.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payment_transaction_logs MODIFY transaction_category ENUM(
            'EXPENSE', 'PURCHASE_ORDER', 'PAYMENT', 'ORDER',
            'SALARY', 'INVOICE', 'REFUND', 'FEE', 'ADJUSTMENT', 'ALLOWANCE', 'BONUS',
            'OVERTIME', 'ADVANCE', 'OTHER', 'ADVANCE_REPAYMENT', 'TAX'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payment_transaction_logs MODIFY transaction_category ENUM(
            'EXPENSE', 'PURCHASE_ORDER', 'PAYMENT', 'ORDER',
            'SALARY', 'INVOICE', 'REFUND', 'FEE', 'ADJUSTMENT', 'ALLOWANCE', 'BONUS',
            'OVERTIME', 'ADVANCE', 'OTHER', 'ADVANCE_REPAYMENT'
        ) NOT NULL");
    }
};