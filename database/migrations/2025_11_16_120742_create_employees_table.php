<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_employees_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('job_title')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->enum('salary_type', ['hourly', 'weekly', 'monthly', 'annual'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};