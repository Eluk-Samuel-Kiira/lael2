<?php
// database/migrations/[timestamp]_create_leaves_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Leave Details
            $table->enum('leave_type', [
                'annual',
                'sick',
                'maternity',
                'paternity',
                'bereavement',
                'study',
                'unpaid',
                'other'
            ])->default('annual');
            
            $table->string('custom_type')->nullable(); // For 'other' type
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days')->virtualAs('DATEDIFF(end_date, start_date) + 1');
            
            // Status
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
                'ongoing',
                'completed'
            ])->default('pending');
            
            // Financial
            $table->boolean('is_paid')->default(true);
            $table->decimal('deduction_amount', 12, 2)->nullable(); // For unpaid leave
            $table->boolean('is_deducted_from_salary')->default(false);
            
            // Workflow
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            
            // Documents
            $table->json('attachments')->nullable(); // For medical certificates, etc.
            
            // Contact during leave
            $table->string('alternate_contact')->nullable();
            $table->string('emergency_contact')->nullable();
            
            // Handover notes
            $table->text('handover_notes')->nullable();
            $table->json('handover_to')->nullable(); // Employee IDs handling work
            
            // Timestamps
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'employee_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'start_date', 'end_date']);
            $table->index(['tenant_id', 'leave_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('leaves');
    }
};