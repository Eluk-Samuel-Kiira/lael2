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
        Schema::table('departments', function (Blueprint $table) {
            $table->enum('department_type', [
                'retail',        // clothing, hardware, general goods
                'electronics',
                'pharmacy',
                'restaurant',
                'manufacturing', // maize grain -> packaged maize
            ])->default('retail')->after('location_id');

            $table->enum('default_inventory_strategy', [
                'quantity',
                'batch',
                'serial',
                'recipe',
            ])->nullable()->after('department_type');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('inventory_strategy', ['quantity','batch','serial','recipe'])
                ->nullable()
                ->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('department_type');
            $table->dropColumn('default_inventory_strategy');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('inventory_strategy');
        });
    }
};