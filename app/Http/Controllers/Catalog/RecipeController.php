<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    Recipe, RecipeIngredient, Product, ProductVariant,
    Department, Location, InventoryItems
};
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{
    /**
     * Recipes index — grouped by Product → Variant → Ingredients.
     *
     * The page shows one block per product. Inside each block, one
     * card per variant. Inside each variant card, the ingredient
     * table (or an "empty" hint when no recipe is defined).
     */
    public function index(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (! $user->hasPermissionTo('view inventory')) {
            abort(403);
        }

        $isSingleShop = tenant_is_single_shop($tenantId);

        $perPage = in_array((int) $request->input('per_page', 15), [15, 25, 50, 100])
            ? (int) $request->input('per_page', 15)
            : 15;

        // ── Scope: location + department, only meaningful in multi-shop ──
        $locationId   = $isSingleShop ? null : $request->input('location_id');
        $departmentId = $isSingleShop ? null : $request->input('department_id');

        

        // ── Build the product query — one product = one row on the page ──
        $productQuery = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('inventory_strategy', 'recipe')
            ->with([
                'variants' => function ($q) {
                    $q->where('is_active', 1)->orderBy('name');
                },
            ]);

        // Search: product name / sku, or any variant name / sku,
        // or ingredient name / sku
        if ($request->filled('search')) {
            $search = trim($request->search);
            $productQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku',  'like', "%{$search}%")
                  ->orWhereHas('variants', function ($v) use ($search) {
                      $v->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                  })
                  ->orWhereHas('recipe.ingredients.ingredientVariant', function ($i) use ($search) {
                      $i->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('product_id')) {
            $productQuery->where('id', $request->product_id);
        }

        $products = $productQuery->orderBy('name')->get();

        // ── Load every recipe + ingredient for the products on this page ──
        $productIds = $products->pluck('id');

        $recipes = Recipe::query()
            ->whereIn('product_id', $productIds)
            ->with([
                'ingredients.ingredientVariant.product:id,name',
                'ingredients.unit:id,name',
                'variant:id,name,sku',
            ])
            ->get();

        // Group recipes by product_id, then by variant_id (null = product-level)
        $recipesByProduct = $recipes->groupBy('product_id');
        $recipesByVariant = $recipes->whereNotNull('product_variant_id')
            ->keyBy('product_variant_id');
        $productLevelRecipes = $recipes->whereNull('product_variant_id')
            ->keyBy('product_id');

        // ── Preload scoped inventory items for the ingredient variants ──
        $allIngredientVariantIds = $recipes
            ->flatMap(fn($r) => $r->ingredients->pluck('ingredient_variant_id'))
            ->filter()->unique()->values();

        $inventoryByVariant = collect();
        if (! $isSingleShop && ($locationId || $departmentId) && $allIngredientVariantIds->isNotEmpty()) {
            $inventoryByVariant = InventoryItems::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('variant_id', $allIngredientVariantIds)
                ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
                ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                ->get()
                ->keyBy('variant_id');
        }

        // ── Annotate each recipe with computed totals ──
        foreach ($recipes as $recipe) {
            $this->annotateRecipe($recipe, $inventoryByVariant, $isSingleShop);
        }

        // ── Build the display tree: Product → Variants → Recipe ──
        $displayTree = $products->map(function ($product) use ($recipesByVariant, $productLevelRecipes) {
            $productLevelRecipe = $productLevelRecipes->get($product->id);

            $variants = $product->variants->map(function ($variant) use ($recipesByVariant, $productLevelRecipe) {
                $variantRecipe = $recipesByVariant->get($variant->id)
                    ?? $productLevelRecipe;

                return (object) [
                    'variant'         => $variant,
                    'recipe'          => $variantRecipe,
                    'inherits_from_product' => $variantRecipe && $variantRecipe->product_variant_id === null,
                ];
            });

            return (object) [
                'product'            => $product,
                'variants'           => $variants,
                'product_level_recipe' => $productLevelRecipe,
            ];
        });

        // ── Filter dropdown data ──
        $filterProducts = Product::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('inventory_strategy', 'recipe')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $locations   = collect();
        $departments = collect();

        if (! $isSingleShop) {
            $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
            $departments = Department::where('tenant_id', $tenantId)
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->orderBy('name')
                ->get(['id', 'name', 'location_id']);
        }

        $states = [
            ['value' => '',                    'label' => __('pagination.all_recipes')],
            ['value' => 'with_ingredients',    'label' => __('pagination.with_ingredients')],
            ['value' => 'empty',               'label' => __('pagination.empty_recipes')],
            ['value' => 'with_custom_pricing', 'label' => __('pagination.with_custom_pricing')],
        ];

        return view('store.recipes.index', [
            'displayTree'        => $displayTree,
            'recipes'            => $recipes,
            'filterProducts'     => $filterProducts,
            'states'             => $states,
            'locations'          => $locations,
            'departments'        => $departments,
            'inventoryByVariant' => $inventoryByVariant,
            'isSingleShop'       => $isSingleShop,
            'search'             => $request->search,
            'productId'          => $request->product_id,
            'state'              => $request->state,
            'locationId'         => $locationId,
            'departmentId'       => $departmentId,
            'perPage'            => $perPage,
        ]);
    }

    /**
     * Compute totals and badges on a recipe for display.
     * Uses the SAME cascade the POS uses: ingredient → item → variant.
     */
    private function annotateRecipe(Recipe $recipe, $inventoryByVariant, bool $isSingleShop): void
    {
        $totalCost      = 0.0;
        $totalSellValue = 0.0;
        $customCount    = 0;

        foreach ($recipe->ingredients as $ing) {
            [$unitCost, $unitSell, $source] = $this->resolveIngredientPricing(
                $ing,
                $inventoryByVariant->get($ing->ingredient_variant_id),
                $isSingleShop
            );

            $qty = (float) $ing->quantity_required;

            // Attach resolved values on the ingredient for the blade
            $ing->resolved_unit_cost     = $unitCost;
            $ing->resolved_unit_sell     = $unitSell;
            $ing->resolved_line_cost     = $unitCost * $qty;
            $ing->resolved_line_sell     = $unitSell * $qty;
            $ing->resolved_source        = $source;

            $totalCost      += $ing->resolved_line_cost;
            $totalSellValue += $ing->resolved_line_sell;

            if ($source === 'item' || $source === 'ingredient') {
                $customCount++;
            }
        }

        $recipe->computed_total_cost         = $totalCost;
        $recipe->computed_total_selling      = $totalSellValue;
        $recipe->computed_profit_per_unit    = $totalSellValue - $totalCost;
        $recipe->computed_ingredient_count   = $recipe->ingredients->count();
        $recipe->computed_custom_count       = $customCount;
    }

    /**
     * Resolve the price + cost for a single recipe ingredient.
     *
     * Cascade for COST:
     *   1. Ingredient snapshot when has_custom_pricing = 1
     *   2. Inventory item at the scoped location+department when
     *      has_custom_pricing = 1
     *   3. Variant grand_total_cost_price
     *   4. Variant component sum
     *
     * Cascade for SELLING PRICE:
     *   1. Inventory item discount_selling_price / selling_price
     *   2. Variant discount_selling_price / selling_price
     *
     * Returns [unitCost, unitSell, source] where source is one of
     * 'ingredient' | 'item' | 'variant'.
     */
    private function resolveIngredientPricing(
        RecipeIngredient $ing,
        ?InventoryItems $inventory,
        bool $isSingleShop
    ): array {
        $variant = $ing->ingredientVariant;
        if (! $variant) {
            return [0.0, 0.0, 'variant'];
        }

        // ── Cost ─────────────────────────────────────────────
        $unitCost = 0.0;
        $source   = 'variant';

        if (((int) ($ing->has_custom_pricing ?? 0) === 1) && (float) ($ing->unit_cost ?? 0) > 0) {
            $unitCost = (float) $ing->unit_cost;
            $source   = 'ingredient';
        } elseif (! $isSingleShop
                && $inventory
                && ((int) ($inventory->has_custom_pricing ?? 0) === 1)
                && (float) ($inventory->grand_total_cost_price ?? 0) > 0) {
            $unitCost = (float) $inventory->grand_total_cost_price;
            $source   = 'item';
        } else {
            $grand = (float) ($variant->grand_total_cost_price ?? 0);
            if ($grand > 0) {
                $unitCost = $grand;
            } else {
                $unitCost = (float) ($variant->supplier_cost_price ?? 0)
                          + (float) ($variant->total_shipping_cost ?? 0)
                          + (float) ($variant->ura_taxes_applied   ?? 0)
                          + (float) ($variant->additional_expenses ?? 0);
            }
        }

        // ── Selling price ────────────────────────────────────
        $unitSell = 0.0;

        if (! $isSingleShop && $inventory) {
            $unitSell = (float) (
                $inventory->discount_selling_price
                ?? $inventory->selling_price
                ?? 0
            );
        }

        if ($unitSell <= 0) {
            $unitSell = (float) (
                $variant->discount_selling_price
                ?? $variant->selling_price
                ?? 0
            );
        }

        return [$unitCost, $unitSell, $source];
    }
}