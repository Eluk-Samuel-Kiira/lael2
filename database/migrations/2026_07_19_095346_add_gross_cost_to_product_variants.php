<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // ✅ Supplier cost price (what you pay the supplier)
            $table->bigInteger('net_cost_price')->nullable()
                ->after('cost_price')
                ->comment('Supplier cost price in smallest currency unit (e.g., cents)');
            
            // ✅ Net selling price (selling price after discounts/promotions)
            $table->bigInteger('net_selling_price')->nullable()
                ->after('price')
                ->comment('Net selling price after discounts in smallest currency unit (e.g., cents)');
            
            // ✅ Discount percentage or amount for reference
            $table->decimal('discount_percentage', 8, 2)->default(0)
                ->after('net_selling_price')
                ->comment('Discount percentage applied to get net selling price');
            
            // ✅ Markup percentage for reference
            $table->decimal('markup_percentage', 5, 2)->default(0)
                ->after('discount_percentage')
                ->comment('Markup percentage from cost to selling price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'net_cost_price',
                'net_selling_price',
                'discount_percentage',
                'markup_percentage'
            ]);
        });
    }
};