<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    Recipe, RecipeIngredient, Product, ProductVariant,
    Department, Location, InventoryItems, SerialNumber,
};
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    //  PAGE (shell only — all data comes via /recipes/data)
    // ═══════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (! $user->hasPermissionTo('view inventory')) abort(403);

        $isSingleShop = tenant_is_single_shop($tenantId);

        $filterProducts = Product::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('inventory_strategy', 'recipe')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $states = [
            ['value' => '',                    'label' => __('pagination.all_recipes')],
            ['value' => 'with_ingredients',    'label' => __('pagination.with_ingredients')],
            ['value' => 'empty',               'label' => __('pagination.empty_recipes')],
            ['value' => 'with_custom_pricing', 'label' => __('pagination.with_custom_pricing')],
        ];

        $locations   = collect();
        $departments = collect();

        if (! $isSingleShop) {
            $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
            $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'location_id']);
        }

        return view('store.recipes.index', compact(
            'isSingleShop', 'filterProducts', 'states', 'locations', 'departments'
        ));
    }

    // ═══════════════════════════════════════════════════════════
    //  JSON TREE (fetched by the page)
    // ═══════════════════════════════════════════════════════════
    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (! $user->hasPermissionTo('view inventory')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $isSingleShop = tenant_is_single_shop($tenantId);
        $locationId   = $isSingleShop ? null : $request->input('location_id');
        $departmentId = $isSingleShop ? null : $request->input('department_id');

        // ── Products ─────────────────────────────────────────
        $productQuery = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('inventory_strategy', 'recipe')
            ->with(['variants' => fn($q) => $q->where('is_active', 1)->orderBy('name')]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $productQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku',  'like', "%{$search}%")
                  ->orWhereHas('variants', fn($v) =>
                        $v->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%"))
                  ->orWhereHas('recipe.ingredients.ingredientVariant', fn($i) =>
                        $i->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('product_id')) {
            $productQuery->where('id', $request->product_id);
        }

        $products   = $productQuery->orderBy('name')->get();
        $productIds = $products->pluck('id');

        // ── Recipes + ingredients ────────────────────────────
        $recipes = Recipe::query()
            ->whereIn('product_id', $productIds)
            ->with([
                'ingredients.ingredientVariant.product:id,name',
                'ingredients.unit:id,name',
            ])
            ->get();

        $recipesByVariant    = $recipes->whereNotNull('product_variant_id')->keyBy('product_variant_id');
        $productLevelRecipes = $recipes->whereNull('product_variant_id')->keyBy('product_id');

        // ── Inventory overrides ──────────────────────────────
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

        foreach ($recipes as $recipe) {
            $this->annotateRecipe($recipe, $inventoryByVariant, $isSingleShop);
        }

        // ── Build tree ───────────────────────────────────────
        $tree = $products->map(function ($product) use ($recipesByVariant, $productLevelRecipes) {
            $productLevelRecipe = $productLevelRecipes->get($product->id);

            $variants = $product->variants->map(function ($variant) use ($recipesByVariant, $productLevelRecipe) {
                $variantRecipe = $recipesByVariant->get($variant->id) ?? $productLevelRecipe;
                $inherits      = $variantRecipe && $variantRecipe->product_variant_id === null;

                return [
                    'variant' => [
                        'id'            => $variant->id,
                        'name'          => $variant->name,
                        'sku'           => $variant->sku,
                        'selling_price' => (float) ($variant->selling_price ?? 0),
                    ],
                    'inherits_from_product' => (bool) $inherits,
                    'recipe'                => $variantRecipe ? $this->serializeRecipe($variantRecipe, $inherits) : null,
                ];
            })->values();

            return [
                'product' => [
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'variants_count' => $product->variants->count(),
                ],
                'variants' => $variants,
            ];
        })->values();

        // ── Stats ────────────────────────────────────────────
        $totalIngredients = 0;
        $withRecipe       = 0;
        $emptyRecipe      = 0;
        $totalCost        = 0.0;
        $totalSellValue   = 0.0;

        foreach ($tree as $node) {
            foreach ($node['variants'] as $vn) {
                $recipe = $vn['recipe'];
                if (!$recipe) { $emptyRecipe++; continue; }
                $count = count($recipe['ingredients']);
                if ($count > 0) $withRecipe++; else $emptyRecipe++;
                $totalIngredients += $count;
                $totalCost        += $recipe['computed_total_cost'];
                $totalSellValue   += $recipe['computed_total_selling'];
            }
        }

        $stats = [
            'products'          => $tree->count(),
            'variants'          => $tree->sum(fn($n) => count($n['variants'])),
            'with_recipe'       => $withRecipe,
            'empty_recipe'      => $emptyRecipe,
            'total_ingredients' => $totalIngredients,
            'total_cost'        => (float) $totalCost,
            'total_sell_value'  => (float) $totalSellValue,
            'profit'            => (float) ($totalSellValue - $totalCost),
        ];

        return response()->json([
            'success' => true,
            'tree'    => $tree,
            'stats'   => $stats,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  DRAWER PAYLOAD (variant + recipe + serials)
    // ═══════════════════════════════════════════════════════════
    public function variantDrawerData(Request $request, $variantId)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (! $user->hasPermissionTo('view inventory')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $isSingleShop = tenant_is_single_shop($tenantId);
        $locationId   = $isSingleShop ? null : $request->input('location_id');
        $departmentId = $isSingleShop ? null : $request->input('department_id');

        $variant = ProductVariant::with([
                'product:id,name,sku,inventory_strategy',
                'unitMeasure:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('id', $variantId)
            ->first();

        if (! $variant) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        // ── Recipe (variant-specific → product-level → null) ──
        $recipe = Recipe::query()
            ->where('product_id', $variant->product_id)
            ->where(function ($q) use ($variant) {
                $q->where('product_variant_id', $variant->id)
                  ->orWhereNull('product_variant_id');
            })
            ->with([
                'ingredients.ingredientVariant.product:id,name',
                'ingredients.unit:id,name',
            ])
            ->orderByRaw('product_variant_id IS NULL ASC')
            ->first();

        $inherits = $recipe && $recipe->product_variant_id === null;

        // ── Inventory overrides for ingredient pricing ──
        $ingredientVariantIds = $recipe
            ? $recipe->ingredients->pluck('ingredient_variant_id')->filter()->unique()->values()
            : collect();

        $inventoryByVariant = collect();
        if (! $isSingleShop && ($locationId || $departmentId) && $ingredientVariantIds->isNotEmpty()) {
            $inventoryByVariant = InventoryItems::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('variant_id', $ingredientVariantIds)
                ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
                ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                ->get()
                ->keyBy('variant_id');
        }

        if ($recipe) {
            $this->annotateRecipe($recipe, $inventoryByVariant, $isSingleShop);
        }

        $recipePayload = $recipe ? $this->serializeRecipe($recipe, $inherits) : null;

        // ── Serials (if serial-tracked) ────────────────────
        $serials = [];
        $serialSummary = null;

        if (($variant->product?->inventory_strategy ?? null) === 'serial') {
            $serialsCollection = SerialNumber::with(['location:id,name', 'department:id,name', 'order:id,order_number'])
                ->where('tenant_id', $tenantId)
                ->where('variant_id', $variant->id)
                ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
                ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                ->orderByDesc('created_at')
                ->get();

            $serials = $serialsCollection->map(fn(SerialNumber $s) => [
                'id'              => $s->id,
                'serial_number'   => $s->serial_number,
                'status'          => $s->status,
                'status_label'    => $s->status_label,
                'status_color'    => $s->status_color,
                'location_name'   => $s->location?->name,
                'department_name' => $s->department?->name,
                'order_id'        => $s->order_id,
                'order_number'    => $s->order?->order_number,
                'sold_unit_price' => $s->sold_unit_price !== null ? (float) $s->sold_unit_price : null,
            ])->values()->all();

            $serialSummary = [
                'total'     => $serialsCollection->count(),
                'available' => $serialsCollection->where('status', SerialNumber::STATUS_AVAILABLE)->count(),
                'sold'      => $serialsCollection->where('status', SerialNumber::STATUS_SOLD)->count(),
                'reserved'  => $serialsCollection->where('status', SerialNumber::STATUS_RESERVED)->count(),
                'returned'  => $serialsCollection->where('status', SerialNumber::STATUS_RETURNED)->count(),
                'lost'      => $serialsCollection->where('status', SerialNumber::STATUS_LOST)->count(),
                'damaged'   => $serialsCollection->where('status', SerialNumber::STATUS_DAMAGED)->count(),
            ];
        }

        $can = [
            'can_edit' => $user->can('edit variant'),
        ];

        return response()->json([
            'success' => true,
            'variant' => array_merge([
                'id'            => $variant->id,
                'product_id'    => $variant->product_id,
                'product_name'  => $variant->product?->name,
                'product_sku'   => $variant->product?->sku,
                'name'          => $variant->name,
                'sku'           => $variant->sku,
                'selling_price' => (float) ($variant->selling_price ?? 0),
                'weight_unit'   => $variant->unitMeasure?->name,
            ], $can),
            'recipe'                => $recipePayload,
            'inherits_from_product' => (bool) $inherits,
            'serials'               => $serials,
            'serial_summary'        => $serialSummary,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  PRIVATE HELPERS (unchanged shape from prior versions)
    // ═══════════════════════════════════════════════════════════
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

            $ing->resolved_unit_cost  = $unitCost;
            $ing->resolved_unit_sell  = $unitSell;
            $ing->resolved_line_cost  = $unitCost * $qty;
            $ing->resolved_line_sell  = $unitSell * $qty;
            $ing->resolved_source     = $source;

            $totalCost      += $ing->resolved_line_cost;
            $totalSellValue += $ing->resolved_line_sell;

            if (in_array($source, ['item', 'ingredient'], true)) $customCount++;
        }

        $recipe->computed_total_cost       = $totalCost;
        $recipe->computed_total_selling    = $totalSellValue;
        $recipe->computed_profit_per_unit  = $totalSellValue - $totalCost;
        $recipe->computed_ingredient_count = $recipe->ingredients->count();
        $recipe->computed_custom_count     = $customCount;
    }

    private function resolveIngredientPricing(
        RecipeIngredient $ing,
        ?InventoryItems $inventory,
        bool $isSingleShop
    ): array {
        $variant = $ing->ingredientVariant;
        if (! $variant) return [0.0, 0.0, 'variant'];

        // Cost
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
            $unitCost = $grand > 0
                ? $grand
                : ((float) ($variant->supplier_cost_price ?? 0)
                 + (float) ($variant->total_shipping_cost ?? 0)
                 + (float) ($variant->ura_taxes_applied   ?? 0)
                 + (float) ($variant->additional_expenses ?? 0));
        }

        // Selling
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

    private function serializeRecipe(Recipe $recipe, bool $inherits): array
    {
        $ingredients = $recipe->ingredients->map(fn($ing) => [
            'id'                    => $ing->id,
            'ingredient_variant_id' => $ing->ingredient_variant_id,
            'name'                  => $ing->ingredientVariant?->name,
            'sku'                   => $ing->ingredientVariant?->sku,
            'product_name'          => $ing->ingredientVariant?->product?->name,
            'quantity_required'     => (float) $ing->quantity_required,
            'unit_name'             => $ing->unit?->name,
            'unit_cost'             => (float) ($ing->resolved_unit_cost ?? 0),
            'unit_sell'             => (float) ($ing->resolved_unit_sell ?? 0),
            'line_cost'             => (float) ($ing->resolved_line_cost ?? 0),
            'line_sell'             => (float) ($ing->resolved_line_sell ?? 0),
            'source'                => $ing->resolved_source ?? 'variant',
        ])->values();

        return [
            'id'                       => $recipe->id,
            'inherits_from_product'    => $inherits,
            'computed_total_cost'      => (float) ($recipe->computed_total_cost      ?? 0),
            'computed_total_selling'   => (float) ($recipe->computed_total_selling   ?? 0),
            'computed_profit_per_unit' => (float) ($recipe->computed_profit_per_unit ?? 0),
            'computed_custom_count'    => (int)   ($recipe->computed_custom_count    ?? 0),
            'ingredients'              => $ingredients,
        ];
    }
}