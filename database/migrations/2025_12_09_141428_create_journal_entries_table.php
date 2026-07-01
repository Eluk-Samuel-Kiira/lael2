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
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date');
            $table->foreignId('period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('source_module')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            
            // 👇 Changed to BIGINT for storing in smallest currency unit
            $table->bigInteger('total_debit')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('total_credit')->default(0)->comment('Stored in smallest currency unit');
            
            $table->enum('status', ['draft', 'posted', 'voided'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'entry_date']);
            $table->index(['tenant_id', 'period_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('journal_entries');
    }
};