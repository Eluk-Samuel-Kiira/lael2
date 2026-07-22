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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            
            // ── Identification ────────────────────────────────────────────────
            $table->string('sku', 50)->nullable();
            $table->string('name', 100);
            $table->string('barcode', 50)->nullable();
            
            // ── Cost & Pricing ────────────────────────────────────────────────
            // Supplier cost (what you pay the supplier)
            $table->bigInteger('supplier_cost_price')->default(0)
                ->comment('Supplier cost price in smallest currency unit (e.g., cents)');
            
            // Total shipping cost incurred
            $table->bigInteger('total_shipping_cost')->default(0)
                ->comment('Total shipping cost in smallest currency unit (e.g., cents)');
            
            // URA taxes applied (import duties, VAT, etc.)
            $table->bigInteger('ura_taxes_applied')->default(0)
                ->comment('URA taxes applied in smallest currency unit (e.g., cents)');
            
            // Additional expenses (handling, insurance, etc.)
            $table->bigInteger('additional_expenses')->default(0)
                ->comment('Additional expenses in smallest currency unit (e.g., cents)');
            
            // Grand total cost price (supplier_cost + shipping + taxes + expenses)
            $table->bigInteger('grand_total_cost_price')->default(0)
                ->comment('Grand total cost price in smallest currency unit (e.g., cents)');
            
            // Selling price to customer
            $table->bigInteger('selling_price')->default(0)
                ->comment('Selling price in smallest currency unit (e.g., cents)');
            
            // Discounted selling price (after promotions)
            $table->bigInteger('discount_selling_price')->default(0)
                ->comment('Discounted selling price in smallest currency unit (e.g., cents)');
            
            // Discount percentage applied
            $table->decimal('discount_percentage', 8, 2)->default(0)
                ->comment('Discount percentage applied to get discounted selling price');
            
            // Markup percentage from cost to selling price
            $table->decimal('markup_percentage', 8, 2)->default(0)
                ->comment('Markup percentage from cost to selling price');
            
            // ── Inventory ──────────────────────────────────────────────────────
            $table->integer('overal_quantity_at_hand')->default(0);
            
            // ── Physical Attributes ───────────────────────────────────────────
            $table->decimal('weight', 15, 2)->nullable();
            $table->foreignId('weight_unit')->constrained('unit_of_measures')->cascadeOnDelete();
            
            // ── Media & Status ────────────────────────────────────────────────
            $table->string('image_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_taxable')->default(true);
            
            // ── Relationships ──────────────────────────────────────────────────
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};