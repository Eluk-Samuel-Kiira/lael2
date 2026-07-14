<?php
// database/migrations/2024_01_01_000002_create_recipe_ingredients_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->decimal('quantity_required', 10, 4);
            
            // ✅ Use foreign key to unit_of_measures instead of string
            $table->foreignId('unit_id')->constrained('unit_of_measures')->onDelete('restrict');
            
            $table->timestamps();

            // Optional: Add unique constraint to prevent duplicate ingredients
            $table->unique(['recipe_id', 'ingredient_variant_id'], 'unique_recipe_ingredient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};