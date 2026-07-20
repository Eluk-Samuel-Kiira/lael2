<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->bigInteger('total_paid')->default(0)->after('total')
                ->comment('Total amount paid for this purchase order in smallest currency unit');
            $table->string('payment_status')->default('pending')->after('total_paid')
                ->comment('pending, partial, paid, overdue');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['total_paid', 'payment_status']);
        });
    }
};