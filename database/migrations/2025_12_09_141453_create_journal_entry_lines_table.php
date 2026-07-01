<?php
// database/migrations/2024_01_01_000004_create_journal_entry_lines_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->integer('line_number');
            $table->text('description')->nullable();
            
            // 👇 Changed to BIGINT for storing in smallest currency unit
            $table->bigInteger('debit_amount')->default(0)->comment('Stored in smallest currency unit');
            $table->bigInteger('credit_amount')->default(0)->comment('Stored in smallest currency unit');
            
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->unique(['journal_id', 'line_number']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};