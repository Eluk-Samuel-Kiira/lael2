<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_advances', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_advances', 'repayment_transaction_ref')) {
                $table->string('repayment_transaction_ref', 64)->nullable();
                $table->index('repayment_transaction_ref');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_advances', function (Blueprint $table) {
            if (Schema::hasColumn('employee_advances', 'repayment_transaction_ref')) {
                $table->dropIndex(['repayment_transaction_ref']);
                $table->dropColumn('repayment_transaction_ref');
            }
        });
    }
};