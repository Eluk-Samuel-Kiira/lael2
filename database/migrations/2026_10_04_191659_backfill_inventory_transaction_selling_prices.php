<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory_transactions')
            ->whereNull('unit_selling_price')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $inventory = DB::table('inventory_items')->find($row->inventory_id);
                    $variant   = $inventory
                        ? DB::table('product_variants')->find($inventory->variant_id)
                        : null;

                    $unitSell = $inventory->discount_selling_price
                        ?? $inventory->selling_price
                        ?? $variant->discount_selling_price
                        ?? $variant->selling_price
                        ?? 0;

                    $qty = (float) $row->quantity;

                    DB::table('inventory_transactions')
                        ->where('id', $row->id)
                        ->update([
                            'unit_selling_price'  => $unitSell,
                            'total_selling_value' => $qty * $unitSell,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Not reversible.
    }
};