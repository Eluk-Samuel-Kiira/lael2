<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MySQL doesn't support altering an enum column via the schema builder,
     * so we do it with a raw statement. If you're on Postgres, source is
     * likely a native enum type and this needs ALTER TYPE ... ADD VALUE instead.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE orders
            MODIFY COLUMN source ENUM('pos', 'online', 'phone', 'mobile', 'invoice') NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * Only safe to run down if no rows currently have source = 'invoice'.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE orders
            MODIFY COLUMN source ENUM('pos', 'online', 'phone', 'mobile') NULL
        ");
    }
};