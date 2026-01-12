<?php
// database/migrations/2024_01_01_000003_create_journal_entries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('entry_number'); // Remove ->unique() from here
            $table->date('entry_date');
            $table->foreignId('period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->text('description');
            $table->string('reference_number')->nullable();
            $table->enum('source_module', ['sales', 'purchases', 'payroll', 'inventory', 'expenses', 'manual', 'adjustment'])->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->boolean('is_balanced')->storedAs('total_debit = total_credit');
            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Add composite unique constraint (tenant_id + entry_number)
            $table->unique(['tenant_id', 'entry_number'], 'journal_entries_tenant_entry_unique');
            
            $table->index(['tenant_id', 'entry_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['source_module', 'source_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('journal_entries');
    }
};