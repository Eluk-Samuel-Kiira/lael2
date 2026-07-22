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
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_name_unique');
            $table->dropUnique('categories_slug_unique');
            $table->unique(['tenant_id', 'name'], 'categories_tenant_id_name_unique');
            $table->unique(['tenant_id', 'slug'], 'categories_tenant_id_slug_unique');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropUnique('product_categories_slug_unique');
            $table->unique(['tenant_id', 'slug'], 'product_categories_tenant_id_slug_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->dropUnique('products_slug_unique');
            $table->unique(['tenant_id', 'sku'], 'products_tenant_id_sku_unique');
            $table->unique(['tenant_id', 'slug'], 'products_tenant_id_slug_unique');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_tenant_id_sku_unique');
            $table->dropUnique('products_tenant_id_slug_unique');
            $table->unique('sku', 'products_sku_unique');
            $table->unique('slug', 'products_slug_unique');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropUnique('product_categories_tenant_id_slug_unique');
            $table->unique('slug', 'product_categories_slug_unique');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_tenant_id_name_unique');
            $table->dropUnique('categories_tenant_id_slug_unique');
            $table->unique('name', 'categories_name_unique');
            $table->unique('slug', 'categories_slug_unique');
        });
    }
};