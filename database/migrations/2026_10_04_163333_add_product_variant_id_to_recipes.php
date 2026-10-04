<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recipes')) {
            return;
        }

        if (! Schema::hasColumn('recipes', 'product_variant_id')) {
            Schema::table('recipes', function (Blueprint $table) {
                $table->unsignedBigInteger('product_variant_id')
                      ->nullable()
                      ->after('product_id');
                $table->index('product_variant_id', 'idx_recipes_variant');
            });
        }

        // Backfill: for every product with exactly one active variant,
        // attach that variant to its recipe. Products with multiple
        // variants keep product-level recipes (variant_id stays null)
        // so they still resolve, but the UI will show them under the
        // product rather than under a specific variant.
        DB::table('recipes')
            ->whereNull('product_variant_id')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $variantIds = DB::table('product_variants')
                        ->where('product_id', $row->product_id)
                        ->where('is_active', 1)
                        ->pluck('id');

                    if ($variantIds->count() === 1) {
                        DB::table('recipes')
                            ->where('id', $row->id)
                            ->update(['product_variant_id' => $variantIds->first()]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('recipes')) return;

        Schema::table('recipes', function (Blueprint $table) {
            if (Schema::hasColumn('recipes', 'product_variant_id')) {
                $table->dropIndex('idx_recipes_variant');
                $table->dropColumn('product_variant_id');
            }
        });
    }
};