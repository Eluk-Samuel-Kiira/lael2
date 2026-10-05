<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_expense_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. Create the templates table ──────────────────────
        Schema::create('expense_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // What this template represents
            $table->string('name', 150);
            $table->string('code', 40)->nullable();
            $table->text('description')->nullable();

            // Fields that would otherwise be retyped
            $table->unsignedBigInteger('category_id');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();

            // Amount hints
            $table->bigInteger('default_amount')->nullable()
                  ->comment('Suggested amount, in smallest currency unit');
            $table->bigInteger('last_amount')->nullable()
                  ->comment('Last amount actually used, in smallest currency unit');

            // Frequency hints
            $table->enum('frequency', [
                'once', 'daily', 'weekly', 'biweekly',
                'monthly', 'quarterly', 'annually', 'random',
            ])->default('random');

            $table->unsignedSmallInteger('reminder_interval_days')->nullable()
                  ->comment('For random frequency: expected days between occurrences');

            // Approval defaults
            $table->boolean('requires_receipt')->default(true);
            $table->boolean('requires_approval')->default(false);

            // Tax defaults
            $table->json('default_tax_ids')->nullable();

            // Usage analytics
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('restrict');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'frequency']);
            $table->index(['tenant_id', 'last_used_at']);

            // Uniqueness
            $table->unique(['tenant_id', 'name']);
        });

        // ─── 2. Add template_id to expenses ─────────────────────
        // Runs second because the FK target (expense_templates)
        // must already exist.
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('template_id')
                  ->nullable()
                  ->after('tenant_id')
                  ->constrained('expense_templates')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // ─── Reverse order ──────────────────────────────────────
        // 1. Remove the FK + column from expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id');
        });

        // 2. Then drop the templates table
        Schema::dropIfExists('expense_templates');
    }
};