<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // ✅ Use bigInteger for money values (stored in cents)
            $table->bigInteger('discount_amount')->nullable()->after('discount_total');
            $table->text('discount_notes')->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'discount_notes']);
        });
    }
};