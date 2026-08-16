<?php
// database/migrations/2026_01_15_000004_add_production_fields_to_serial_numbers.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serial_numbers', function (Blueprint $table) {
            $table->unsignedBigInteger('production_order_id')->nullable()->after('order_id');
            $table->unsignedBigInteger('production_order_output_id')->nullable()->after('production_order_id');
            
            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('set null');
            $table->foreign('production_order_output_id')->references('id')->on('production_order_outputs')->onDelete('set null');
            $table->index('production_order_id');
            $table->index('production_order_output_id');
        });
    }

    public function down(): void
    {
        Schema::table('serial_numbers', function (Blueprint $table) {
            $table->dropForeign(['production_order_id']);
            $table->dropForeign(['production_order_output_id']);
            $table->dropColumn(['production_order_id', 'production_order_output_id']);
        });
    }
};