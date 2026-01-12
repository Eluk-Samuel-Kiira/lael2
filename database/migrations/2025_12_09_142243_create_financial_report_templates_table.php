<?php
// database/migrations/2024_01_01_000007_create_financial_report_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('financial_report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('report_type', ['balance_sheet', 'profit_loss', 'cash_flow', 'trial_balance']);
            $table->string('template_name', 100);
            $table->boolean('is_default')->default(false);
            $table->json('configuration')->default('{}');
            $table->timestamps();

            // Shorter but still descriptive
            $table->unique(
                ['tenant_id', 'report_type', 'template_name'],
                'unq_fin_rpt_templates'
            );
        });

        Schema::create('report_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('financial_report_templates')->cascadeOnDelete();
            $table->string('section_name', 100);
            $table->integer('section_order');
            $table->enum('section_type', ['header', 'group', 'account', 'total', 'subtotal'])->nullable();
            $table->foreignId('parent_section_id')->nullable()->constrained('report_sections')->cascadeOnDelete();
            $table->json('account_codes')->nullable(); // Array of account codes
            $table->text('calculation_formula')->nullable();
            $table->string('display_format', 50)->nullable();
            $table->timestamps();

            $table->unique(
                ['template_id', 'section_name'],
                'unq_report_sections'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('report_sections');
        Schema::dropIfExists('financial_report_templates');
    }
};