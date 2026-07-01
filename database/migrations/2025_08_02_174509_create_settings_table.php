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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('app_name')->default('LAEL');
            $table->string('favicon')->nullable();
            $table->string('logo')->nullable();
            $table->string('app_email')->nullable();
            $table->string('app_contact')->nullable();
            $table->string('meta_keyword')->default('Best POS System')->nullable();
            $table->longText('meta_descrip')->nullable();
            
            // Mail Settings
            $table->enum('mail_status', ['enabled', 'disabled'])->default('enabled')->nullable();
            $table->string('mail_mailer')->default('smtp')->nullable();
            $table->string('mail_host')->default('smtp.gmail.com')->nullable();
            $table->string('mail_port')->default('465')->nullable();
            $table->string('mail_username')->default('yorentos23@gmail.com')->nullable();
            $table->string('mail_password')->default('shxdoavekodckrnh')->nullable();
            $table->string('mail_encryption')->default('tls')->nullable();
            $table->string('mail_address')->default('yorentos23@gmail.com')->nullable();
            $table->string('mail_name')->default('no_reply')->nullable();
            
            // UI Settings
            $table->string('menu_nav_color')->default('#3498db')->nullable();
            $table->string('font_family')->default('Cursive')->nullable();
            $table->decimal('font_size')->default('1.3')->nullable();
            
            // Localization
            $table->string('locale')->default('en')->nullable();
            $table->string('currency')->default('USD')->nullable();
            
            // License Keys
            $table->string('public_key')->nullable()->unique()->comment('Public license key for identification');
            $table->string('private_key')->nullable()->comment('Hashed/encrypted private license key');
            $table->string('license_type')->default('trial')->comment('trial, basic, premium, enterprise');
            $table->timestamp('license_expires_at')->nullable()->comment('License expiration date');
            $table->boolean('license_active')->default(true)->comment('Whether license is active');
            
            // Resource Limits
            $table->integer('max_users')->default(10)->comment('Maximum number of users');
            $table->integer('max_products')->default(100)->comment('Maximum number of products');
            $table->integer('max_departments')->default(10)->comment('Maximum number of departments');
            $table->integer('max_categories')->default(20)->comment('Maximum number of categories');
            $table->integer('max_suppliers')->default(50)->comment('Maximum number of suppliers');
            
            // Feature Flags
            $table->boolean('enable_inventory')->default(true);
            $table->boolean('enable_multi_location')->default(false);
            $table->boolean('enable_reports')->default(true);
            $table->boolean('enable_api')->default(false);
            $table->boolean('enable_backup')->default(false);
            
            // Storage
            $table->integer('storage_limit_mb')->default(1024)->comment('Storage limit in MB');
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('public_key');
            $table->index('license_type');
            $table->index('license_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};