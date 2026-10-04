<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\InventoryItems;
use App\Models\PurchaseReceiptItem;
use App\Models\SerialNumber;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\ProductCategory;
use App\Models\Location;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\PricingResolverService;
use Illuminate\Support\Collection;

class InventoryStrategyReportController extends Controller
{

    public function __construct(
        protected PricingResolverService $pricingResolver
    ) {}

    /**
     * Resolve cost + selling price for a single variant, optionally scoped
     * to a location + department.
     *
     * Cascade (identical to POS `attachPosPricing()`):
     *   1. InventoryItems row with has_custom_pricing = 1
     *      at (variant, location, department) — use its own selling_price
     *      and grand_total_cost_price
     *   2. InventoryItems row with has_custom_pricing = 1
     *      for this variant anywhere (fallback for scoped lookups)
     *   3. Variant's grand_total_cost_price / selling_price
     *   4. Recompute cost from variant components
     *
     * @return array{cost: float, selling: float, source: string}
     */
    private function resolveVariantPricing(
        ProductVariant $variant,
        ?int $locationId = null,
        ?int $departmentId = null
    ): array {
        $tenantId = $variant->tenant_id;

        // ── Level 1 & 2: InventoryItems override ───────────────────
        $item = null;

        if ($locationId && $departmentId) {
            $item = InventoryItems::where('tenant_id', $tenantId)
                ->where('variant_id', $variant->id)
                ->where('location_id', $locationId)
                ->where('department_id', $departmentId)
                ->where('has_custom_pricing', 1)
                ->first();
        }

        if (! $item && ($locationId || $departmentId)) {
            // Fall back to a custom-priced row for this variant anywhere
            $item = InventoryItems::where('tenant_id', $tenantId)
                ->where('variant_id', $variant->id)
                ->where('has_custom_pricing', 1)
                ->when($locationId,   fn($q) => $q->where('location_id', $locationId))
                ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                ->first();
        }

        if ($item) {
            $cost = (float) ($item->grand_total_cost_price ?? 0);

            if ($cost <= 0) {
                // Item row exists but no cost set — fall through to variant
                $cost = $this->variantFallbackCost($variant);
            }

            $selling = (float) ($item->selling_price ?? 0);
            if ($selling <= 0) {
                $selling = (float) ($variant->selling_price ?? 0);
            }

            return [
                'cost'    => $cost,
                'selling' => $selling,
                'source'  => 'item',
            ];
        }

        // ── Level 3 & 4: variant ────────────────────────────────────
        return [
            'cost'    => $this->variantFallbackCost($variant),
            'selling' => (float) ($variant->selling_price ?? 0),
            'source'  => 'variant',
        ];
    }

    /**
     * Variant cost — grand total, else recomputed from components.
     * Never returns null.
     */
    private function variantFallbackCost(ProductVariant $variant): float
    {
        $grand = (float) ($variant->grand_total_cost_price ?? 0);
        if ($grand > 0) return $grand;

        return (float) ($variant->supplier_cost_price  ?? 0)
            + (float) ($variant->total_shipping_cost  ?? 0)
            + (float) ($variant->ura_taxes_applied    ?? 0)
            + (float) ($variant->additional_expenses  ?? 0);
    }

    /**
     * Pre-load custom-priced inventory rows for a set of variants.
     * Keyed: "{variant_id}|{location_id}|{department_id}"
     *
     * Optional optimisation for the index() loop — pass the returned
     * collection into resolveVariantPricingFromCache() to avoid N+1.
     */
    private function preloadCustomPricing(Collection $variantIds, int $tenantId): Collection
    {
        if ($variantIds->isEmpty()) return collect();

        return InventoryItems::where('tenant_id', $tenantId)
            ->whereIn('variant_id', $variantIds->all())
            ->where('has_custom_pricing', 1)
            ->get([
                'variant_id',
                'location_id',
                'department_id',
                'grand_total_cost_price',
                'selling_price',
            ])
            ->keyBy(fn($r) => "{$r->variant_id}|{$r->location_id}|{$r->department_id}");
    }

    /**
     * Cached variant of resolveVariantPricing() — uses the preloaded map.
     */
    private function resolveVariantPricingFromCache(
        ProductVariant $variant,
        Collection $pricingMap,
        ?int $locationId = null,
        ?int $departmentId = null
    ): array {
        $item = null;

        if ($locationId && $departmentId) {
            $item = $pricingMap->get("{$variant->id}|{$locationId}|{$departmentId}");
        }

        if (! $item) {
            $item = $pricingMap->first(fn($r) => $r->variant_id === $variant->id);
        }

        if ($item) {
            $cost = (float) ($item->grand_total_cost_price ?? 0);
            if ($cost <= 0) $cost = $this->variantFallbackCost($variant);

            $selling = (float) ($item->selling_price ?? 0);
            if ($selling <= 0) $selling = (float) ($variant->selling_price ?? 0);

            return ['cost' => $cost, 'selling' => $selling, 'source' => 'item'];
        }

        return [
            'cost'    => $this->variantFallbackCost($variant),
            'selling' => (float) ($variant->selling_price ?? 0),
            'source'  => 'variant',
        ];
    }

    /**
     * Get current tenant ID and check permissions
     */
    private function getTenantId()
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('product_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        return $user->tenant_id;
    }


    /**
     * ✅ Reusable pagination method
     */
    private function paginateCollection($collection, $perPage = 15, $pageName = 'page')
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $currentPageItems = $collection->slice(($page - 1) * $perPage, $perPage)->values();
        
        return new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->except($pageName, 'per_page')
            ]
        );
    }

    /**
     * Main Inventory Strategy Report
     */
    public function index(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $strategy     = $request->get('strategy', 'all');
        $categoryId   = $request->get('category_id');
        $search       = $request->get('search');
        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $perPage      = (int) $request->get('per_page', 15);

        // Pre-load custom-priced inventory for every variant in scope
        $variantIds = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('variants:id,product_id')
            ->get()
            ->flatMap(fn($p) => $p->variants->pluck('id'))
            ->unique();

        $pricingMap = $this->preloadCustomPricing($variantIds, $tenantId);

        $query = Product::with(['category', 'variants'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($strategy && $strategy !== 'all') {
            $query->where('inventory_strategy', $strategy);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('sku', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $products = $query->get();

        $strategyData = collect();

        foreach ($products as $product) {
            $productStrategy = $product->inventory_strategy ?? 'quantity';

            $stockData = $this->getStockDataByStrategy(
                $product,
                $tenantId,
                $isSingleShop,
                $pricingMap,
                $locationId,
                $departmentId
            );

            $strategyData->push((object) [
                'product'        => $product,
                'product_id'     => $product->id,
                'strategy'       => $productStrategy,
                'strategy_label' => $this->getStrategyLabel($productStrategy),
                'strategy_color' => $this->getStrategyColor($productStrategy),
                'strategy_icon'  => $this->getStrategyIcon($productStrategy),
                'stock_data'     => $stockData,
                'variant_count'  => $product->variants->count(),
                'total_stock'    => $stockData['total_stock']  ?? 0,
                'total_value'    => $stockData['total_value']  ?? 0,
                'status'         => $stockData['status']       ?? 'unknown',
                'status_color'   => $stockData['status_color'] ?? 'secondary',
                'status_label'   => $stockData['status_label'] ?? __('pagination.unknown'),
            ]);
        }

        $paginatedData = $this->paginateCollection($strategyData, $perPage, 'page');

        $summary = [
            'total_products'    => $products->count(),
            'quantity_strategy' => $products->where('inventory_strategy', 'quantity')->count(),
            'batch_strategy'    => $products->where('inventory_strategy', 'batch')->count(),
            'serial_strategy'   => $products->where('inventory_strategy', 'serial')->count(),
            'recipe_strategy'   => $products->where('inventory_strategy', 'recipe')->count(),
            'total_stock'       => $strategyData->sum('total_stock'),
            'total_value'       => $strategyData->sum('total_value'),
            'in_stock'          => $strategyData->where('status', 'in_stock')->count(),
            'low_stock'         => $strategyData->where('status', 'low_stock')->count(),
            'out_of_stock'      => $strategyData->where('status', 'out_of_stock')->count(),
        ];

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $strategies = [
            ['value' => 'all',      'label' => __('pagination.all_strategies')],
            ['value' => 'quantity', 'label' => __('pagination.quantity_tracking')],
            ['value' => 'batch',    'label' => __('pagination.batch_tracking')],
            ['value' => 'serial',   'label' => __('pagination.serial_tracking')],
            ['value' => 'recipe',   'label' => __('pagination.recipe_product')],
        ];

        $locations   = Location::where('tenant_id', $tenantId)->where('is_active', 1)->orderBy('name')->get(['id', 'name']);
        $departments = Department::where('tenant_id', $tenantId)->where('isActive', 1)->orderBy('name')->get(['id', 'name']);

        return view('reports.products.strategy', compact(
            'paginatedData', 'strategyData', 'summary',
            'categories', 'strategies', 'strategy', 'categoryId', 'search',
            'perPage', 'isSingleShop',
            'locations', 'departments', 'locationId', 'departmentId'
        ));
    }

    /**
     * Get detailed view for a specific product
     * ✅ Uses route parameter {productId}
     */
    public function detail(Request $request, $productId)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');

        $product = Product::with(['category', 'variants'])
            ->where('tenant_id', $tenantId)
            ->where('id', $productId)
            ->firstOrFail();

        $strategy = $product->inventory_strategy ?? 'quantity';

        // Preload pricing for this product's variants only
        $pricingMap = $this->preloadCustomPricing(
            $product->variants->pluck('id'),
            $tenantId
        );

        $variantsData = collect();
        $totalStock   = 0;
        $totalValue   = 0;

        foreach ($product->variants as $variant) {
            $pricing = $this->resolveVariantPricingFromCache(
                $variant, $pricingMap, $locationId, $departmentId
            );

            $variantStock = 0;
            $variantValue = 0;
            $details      = [];

            switch ($strategy) {
                case 'batch':
                    $result = $this->getVariantBatchData($variant, $tenantId, $pricing, $locationId, $departmentId);
                    break;
                case 'serial':
                    $result = $this->getVariantSerialData($variant, $tenantId, $pricing, $locationId, $departmentId);
                    break;
                case 'recipe':
                    $result = $this->getRecipeData($product, $tenantId, $isSingleShop, $locationId, $departmentId);
                    break;
                default:
                    $result = $this->getVariantQuantityData($variant, $tenantId, $isSingleShop, $pricing, $locationId, $departmentId);
                    break;
            }

            $variantStock = $result['stock'];
            $variantValue = $result['value'];
            $details      = $result['details'];

            $totalStock += $variantStock;
            $totalValue += $variantValue;

            $variantsData->push([
                'variant'        => $variant,
                'variant_id'     => $variant->id,
                'variant_name'   => $variant->name,
                'variant_sku'    => $variant->sku,
                'stock'          => $variantStock,
                'value'          => $variantValue,
                'cost_price'     => $pricing['cost'],
                'selling_price'  => $pricing['selling'],
                'pricing_source' => $pricing['source'],
                'details'        => $details,
            ]);
        }

        $status = $this->getStockStatus($totalStock, $product);

        return response()->json([
            'product'        => $product,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'product_sku'    => $product->sku,
            'strategy'       => $strategy,
            'strategy_label' => $this->getStrategyLabel($strategy),
            'strategy_color' => $this->getStrategyColor($strategy),
            'strategy_icon'  => $this->getStrategyIcon($strategy),
            'variants'       => $variantsData,
            'total_stock'    => $totalStock,
            'total_value'    => $totalValue,
            'status'         => $status['status'],
            'status_color'   => $status['color'],
            'status_label'   => $status['label'],
            'is_single_shop' => $isSingleShop,
        ]);
    }

    /**
     * Get recipe details for a product
     * ✅ Uses route parameter {productId}
     */
    public function recipeDetail($productId)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $product = Product::where('tenant_id', $tenantId)
            ->where('id', $productId)
            ->firstOrFail();

        $recipe = Recipe::with(['ingredients.ingredientVariant.product'])
            ->where('product_id', $productId)
            ->first();

        if (! $recipe) {
            return response()->json(['error' => 'No recipe found'], 404);
        }

        $ingredients = [];

        foreach ($recipe->ingredients as $ingredient) {
            $variant = $ingredient->ingredientVariant;

            if ($isSingleShop) {
                $availableStock = (float) ($variant->overal_quantity_at_hand ?? 0);
            } else {
                $availableStock = (float) InventoryItems::where('variant_id', $variant->id)
                    ->where('tenant_id', $tenantId)
                    ->sum('quantity_allocated');
            }

            $pricing = $variant
                ? $this->resolveVariantPricing($variant)
                : ['cost' => 0.0, 'selling' => 0.0, 'source' => 'unknown'];

            $quantityRequired = (float) ($ingredient->quantity_required ?? 0);

            $ingredients[] = [
                'variant_name'      => $variant?->name ?? 'Unknown',
                'sku'               => $variant?->sku ?? 'N/A',
                'quantity_required' => $quantityRequired,
                'unit'              => $ingredient->unit?->name ?? 'units',
                'available_stock'   => $availableStock,
                'producible'        => $quantityRequired > 0
                    ? (int) floor($availableStock / $quantityRequired)
                    : 0,
                'cost_price'        => $pricing['cost'],
                'selling_price'     => $pricing['selling'],
                'pricing_source'    => $pricing['source'],
            ];
        }

        return response()->json([
            'product'     => $product,
            'recipe'      => $recipe,
            'ingredients' => $ingredients,
        ]);
    }

    private function getStockDataByStrategy(
        $product,
        $tenantId,
        $isSingleShop,
        Collection $pricingMap,
        ?int $locationId,
        ?int $departmentId
    ) {
        $strategy = $product->inventory_strategy ?? 'quantity';

        return match ($strategy) {
            'batch'  => $this->getBatchStockData($product, $tenantId, $isSingleShop, $pricingMap, $locationId, $departmentId),
            'serial' => $this->getSerialStockData($product, $tenantId, $isSingleShop, $pricingMap, $locationId, $departmentId),
            'recipe' => $this->getRecipeStockData($product, $tenantId, $isSingleShop, $locationId, $departmentId),
            default  => $this->getQuantityStockData($product, $tenantId, $isSingleShop, $pricingMap, $locationId, $departmentId),
        };
    }

    /**
     * Get stock data for quantity strategy
     */
    private function getQuantityStockData(
        $product,
        $tenantId,
        $isSingleShop,
        Collection $pricingMap,
        ?int $locationId,
        ?int $departmentId
    ) {
        $totalStock = 0;
        $totalValue = 0;
        $variantDetails = [];

        foreach ($product->variants as $variant) {
            // ── Stock ─────────────────────────────────────────────
            if ($isSingleShop) {
                $stock = (float) ($variant->overal_quantity_at_hand ?? 0);
            } else {
                $stock = (float) InventoryItems::where('variant_id', $variant->id)
                    ->where('tenant_id', $tenantId)
                    ->when($locationId,   fn($q) => $q->where('location_id', $locationId))
                    ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                    ->sum('quantity_allocated');
            }

            // ── Pricing ───────────────────────────────────────────
            $pricing = $this->resolveVariantPricingFromCache(
                $variant, $pricingMap, $locationId, $departmentId
            );

            $value = $stock * $pricing['cost'];

            $totalStock += $stock;
            $totalValue += $value;

            $variantDetails[] = [
                'variant'        => $variant,
                'stock'          => $stock,
                'value'          => $value,
                'cost_price'     => $pricing['cost'],
                'selling_price'  => $pricing['selling'],
                'pricing_source' => $pricing['source'],
            ];
        }

        $status = $this->getStockStatus($totalStock, $product);

        return [
            'total_stock'   => $totalStock,
            'total_value'   => $totalValue,
            'variants'      => $variantDetails,
            'status'        => $status['status'],
            'status_color'  => $status['color'],
            'status_label'  => $status['label'],
        ];
    }

    /**
     * Get stock data for batch strategy
     */
    private function getBatchStockData(
        $product,
        $tenantId,
        $isSingleShop,
        Collection $pricingMap,
        ?int $locationId,
        ?int $departmentId
    ) {
        $totalStock = 0;
        $totalValue = 0;
        $batchDetails = [];

        foreach ($product->variants as $variant) {
            $pricing = $this->resolveVariantPricingFromCache(
                $variant, $pricingMap, $locationId, $departmentId
            );

            $batchItems = PurchaseReceiptItem::query()
                ->whereHas('purchaseOrderItem', fn($q) => $q->where('product_variant_id', $variant->id))
                ->whereHas('purchaseReceipt.purchaseOrder', fn($q) => $q->where('tenant_id', $tenantId))
                ->whereHas('purchaseReceipt', fn($q) => $q->where('received_at', '<=', now()))
                ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
                ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                ->where(function ($q) {
                    $q->where('quantity_remaining', '>', 0)
                    ->orWhereNull('quantity_remaining');
                })
                ->get();

            $variantStock = 0;
            $variantValue = 0;

            foreach ($batchItems as $batch) {
                $quantity = (float) ($batch->quantity_remaining ?? $batch->quantity_received ?? 0);
                $value    = $quantity * $pricing['cost'];

                $variantStock += $quantity;
                $variantValue += $value;

                $batchDetails[] = [
                    'variant'        => $variant,
                    'batch_number'   => $batch->batch_number,
                    'quantity'       => $quantity,
                    'expiry_date'    => $batch->expiry_date,
                    'value'          => $value,
                    'cost_price'     => $pricing['cost'],
                    'selling_price'  => $pricing['selling'],
                    'pricing_source' => $pricing['source'],
                ];
            }

            $totalStock += $variantStock;
            $totalValue += $variantValue;
        }

        $status = $this->getStockStatus($totalStock, $product);

        return [
            'total_stock'  => $totalStock,
            'total_value'  => $totalValue,
            'batches'      => $batchDetails,
            'status'       => $status['status'],
            'status_color' => $status['color'],
            'status_label' => $status['label'],
        ];
    }

    /**
     * Get stock data for serial strategy
     */
    private function getSerialStockData($product, $tenantId)
    {
        $variants = $product->variants;
        $totalStock = 0;
        $totalValue = 0;
        $serialDetails = [];
        
        foreach ($variants as $variant) {
            $serials = SerialNumber::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->get();
            
            $variantStock = $serials->count();
            $costPrice = $variant->grand_total_cost_price ?? 0;
            $value = $variantStock * $costPrice;
            
            $totalStock += $variantStock;
            $totalValue += $value;
            
            foreach ($serials as $serial) {
                $serialDetails[] = [
                    'variant' => $variant,
                    'serial_number' => $serial->serial_number,
                    'location' => $serial->location,
                    'expiry_date' => $serial->expiry_date,
                    'cost_price' => $costPrice,
                    'value' => $costPrice,
                ];
            }
        }
        
        $status = $this->getStockStatus($totalStock, $product);
        
        return [
            'total_stock' => $totalStock,
            'total_value' => $totalValue,
            'serials' => $serialDetails,
            'status' => $status['status'],
            'status_color' => $status['color'],
            'status_label' => $status['label'],
        ];
    }

    /**
     * Get stock data for recipe strategy
     */
    private function getRecipeStockData(
        $product,
        $tenantId,
        $isSingleShop,
        ?int $locationId,
        ?int $departmentId
    ) {
        $recipe = Recipe::where('product_id', $product->id)->first();
        $ingredientDetails = [];
        $totalValue        = 0;
        $maxProducible     = PHP_INT_MAX;
        $hasIngredients    = false;

        if ($recipe) {
            $ingredients = RecipeIngredient::where('recipe_id', $recipe->id)
                ->with(['ingredientVariant'])
                ->get();

            foreach ($ingredients as $ingredient) {
                $variant = $ingredient->ingredientVariant;
                $quantityRequired = (float) ($ingredient->quantity_required ?? 0);

                if (! $variant) {
                    continue;
                }

                // ── Stock (respects single/multi-shop) ─────────────
                if ($isSingleShop) {
                    $availableStock = (float) ($variant->overal_quantity_at_hand ?? 0);
                } else {
                    $availableStock = (float) InventoryItems::where('variant_id', $variant->id)
                        ->where('tenant_id', $tenantId)
                        ->when($locationId,   fn($q) => $q->where('location_id', $locationId))
                        ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                        ->sum('quantity_allocated');
                }

                // ── Pricing for the ingredient ─────────────────────
                $pricing = $this->resolveVariantPricing(
                    $variant, $locationId, $departmentId
                );

                $producible = $quantityRequired > 0
                    ? (int) floor($availableStock / $quantityRequired)
                    : 0;

                $maxProducible = min($maxProducible, $producible);
                $hasIngredients = true;

                $ingredientDetails[] = [
                    'variant'           => $variant,
                    'variant_name'      => $variant->name,
                    'quantity_required' => $quantityRequired,
                    'available_stock'   => $availableStock,
                    'producible'        => $producible,
                    'cost_price'        => $pricing['cost'],
                    'selling_price'     => $pricing['selling'],
                    'total_cost'        => $availableStock * $pricing['cost'],
                    'pricing_source'    => $pricing['source'],
                ];
            }
        }

        if (! $hasIngredients) {
            $totalStock = 0;
            $status = ['status' => 'out_of_stock', 'color' => 'danger',
                    'label' => __('pagination.out_of_stock')];
        } else {
            $totalStock = $maxProducible === PHP_INT_MAX ? 0 : $maxProducible;

            if ($totalStock <= 0) {
                $status = ['status' => 'out_of_stock', 'color' => 'danger',
                        'label' => __('pagination.out_of_stock')];
            } else {
                $status = ['status' => 'in_stock', 'color' => 'success',
                        'label' => __('pagination.can_produce')];
            }

            // Value = cost to produce $totalStock units
            foreach ($ingredientDetails as $ing) {
                $totalValue += $totalStock * $ing['quantity_required'] * $ing['cost_price'];
            }
        }

        return [
            'total_stock'    => $totalStock,
            'total_value'    => $totalValue,
            'ingredients'    => $ingredientDetails,
            'can_produce'    => $totalStock > 0,
            'max_producible' => $totalStock,
            'status'         => $status['status'],
            'status_color'   => $status['color'],
            'status_label'   => $status['label'],
        ];
    }

    private function getVariantQuantityData($variant, $tenantId, $isSingleShop, array $pricing, $locationId, $departmentId)
    {
        if ($isSingleShop) {
            $stock = (float) ($variant->overal_quantity_at_hand ?? 0);
        } else {
            $stock = (float) InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->when($locationId,   fn($q) => $q->where('location_id', $locationId))
                ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                ->sum('quantity_allocated');
        }

        $value = $stock * $pricing['cost'];

        return [
            'stock' => $stock,
            'value' => $value,
            'details' => [
                'cost_price'     => $pricing['cost'],
                'selling_price'  => $pricing['selling'],
                'pricing_source' => $pricing['source'],
                'value'          => $value,
            ],
        ];
    }

    private function getVariantBatchData($variant, $tenantId, array $pricing, $locationId, $departmentId)
    {
        $batchItems = PurchaseReceiptItem::query()
            ->whereHas('purchaseOrderItem', fn($q) => $q->where('product_variant_id', $variant->id))
            ->whereHas('purchaseReceipt.purchaseOrder', fn($q) => $q->where('tenant_id', $tenantId))
            ->whereHas('purchaseReceipt', fn($q) => $q->where('received_at', '<=', now()))
            ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->where(function ($q) {
                $q->where('quantity_remaining', '>', 0)->orWhereNull('quantity_remaining');
            })
            ->get();

        $totalStock = 0;
        $totalValue = 0;
        $batchDetails = [];

        foreach ($batchItems as $batch) {
            $quantity = (float) ($batch->quantity_remaining ?? $batch->quantity_received ?? 0);
            $value    = $quantity * $pricing['cost'];

            $totalStock += $quantity;
            $totalValue += $value;

            $batchDetails[] = [
                'batch_number'   => $batch->batch_number,
                'quantity'       => $quantity,
                'expiry_date'    => $batch->expiry_date,
                'value'          => $value,
                'cost_price'     => $pricing['cost'],
                'selling_price'  => $pricing['selling'],
            ];
        }

        return ['stock' => $totalStock, 'value' => $totalValue, 'details' => $batchDetails];
    }

    private function getVariantSerialData($variant, $tenantId, array $pricing, $locationId, $departmentId)
    {
        $serials = SerialNumber::where('variant_id', $variant->id)
            ->where('tenant_id', $tenantId)
            ->where('status', SerialNumber::STATUS_AVAILABLE)
            ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->get();

        $stock = $serials->count();
        $value = $stock * $pricing['cost'];

        $serialDetails = $serials->map(fn($serial) => [
            'serial_number' => $serial->serial_number,
            'location'      => $serial->location?->name ?? 'N/A',
            'expiry_date'   => $serial->expiry_date,
            'cost_price'    => $pricing['cost'],
            'value'         => $pricing['cost'],
        ])->all();

        return ['stock' => $stock, 'value' => $value, 'details' => $serialDetails];
    }


    /**
     * Get recipe data
     */
    private function getRecipeData($product, $tenantId, $isSingleShop, $locationId, $departmentId)
    {
        $recipe = Recipe::where('product_id', $product->id)->first();
        $ingredientDetails = [];
        $maxProducible = PHP_INT_MAX;
        $hasIngredients = false;

        if ($recipe) {
            $ingredients = RecipeIngredient::where('recipe_id', $recipe->id)
                ->with(['ingredientVariant'])
                ->get();

            foreach ($ingredients as $ingredient) {
                $variant = $ingredient->ingredientVariant;
                if (! $variant) continue;

                $quantityRequired = (float) ($ingredient->quantity_required ?? 0);

                if ($isSingleShop) {
                    $availableStock = (float) ($variant->overal_quantity_at_hand ?? 0);
                } else {
                    $availableStock = (float) InventoryItems::where('variant_id', $variant->id)
                        ->where('tenant_id', $tenantId)
                        ->when($locationId,   fn($q) => $q->where('location_id', $locationId))
                        ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                        ->sum('quantity_allocated');
                }

                $pricing = $this->resolveVariantPricing($variant, $locationId, $departmentId);

                $producible = $quantityRequired > 0
                    ? (int) floor($availableStock / $quantityRequired)
                    : 0;

                $maxProducible = min($maxProducible, $producible);
                $hasIngredients = true;

                $ingredientDetails[] = [
                    'ingredient_name'    => $variant->name,
                    'ingredient_sku'     => $variant->sku,
                    'quantity_required'  => $quantityRequired,
                    'available_stock'    => $availableStock,
                    'producible'         => $producible,
                    'cost_price'         => $pricing['cost'],
                    'selling_price'      => $pricing['selling'],
                    'total_cost'         => $availableStock * $pricing['cost'],
                    'pricing_source'     => $pricing['source'],
                ];
            }
        }

        $totalStock = $hasIngredients && $maxProducible !== PHP_INT_MAX ? $maxProducible : 0;
        $canProduce = $totalStock > 0;

        return [
            'stock' => $totalStock,
            'value' => $totalStock > 0
                ? collect($ingredientDetails)->sum(fn($i) => $totalStock * $i['quantity_required'] * $i['cost_price'])
                : 0,
            'details' => [
                'ingredients'    => $ingredientDetails,
                'can_produce'    => $canProduce,
                'max_producible' => $maxProducible === PHP_INT_MAX ? 0 : $maxProducible,
            ],
        ];
    }

    /**
     * Get stock status based on quantity
     */
    private function getStockStatus($quantity, $product)
    {
        if ($quantity <= 0) {
            return ['status' => 'out_of_stock', 'color' => 'danger',
                    'label' => __('pagination.out_of_stock')];
        }

        // Sum of thresholds across all variants
        $threshold = $product->variants->sum(fn($v) => $v->low_stock_level ?? 5);
        if ($threshold <= 0) $threshold = 5 * max($product->variants->count(), 1);

        if ($quantity < $threshold) {
            return ['status' => 'low_stock', 'color' => 'warning',
                    'label' => __('pagination.low_stock')];
        }

        return ['status' => 'in_stock', 'color' => 'success',
                'label' => __('pagination.in_stock')];
    }

    /**
     * Get strategy label
     */
    private function getStrategyLabel($strategy)
    {
        $labels = [
            'quantity' => __('pagination.quantity_tracking'),
            'batch' => __('pagination.batch_tracking'),
            'serial' => __('pagination.serial_tracking'),
            'recipe' => __('pagination.recipe_product'),
        ];
        return $labels[$strategy] ?? ucfirst($strategy);
    }

    /**
     * Get strategy color
     */
    private function getStrategyColor($strategy)
    {
        $colors = [
            'quantity' => 'primary',
            'batch' => 'info',
            'serial' => 'warning',
            'recipe' => 'success',
        ];
        return $colors[$strategy] ?? 'secondary';
    }

    /**
     * Get strategy icon
     */
    private function getStrategyIcon($strategy)
    {
        $icons = [
            'quantity' => 'ki-barcode',
            'batch' => 'ki-bucket',
            'serial' => 'ki-qr',
            'recipe' => 'ki-cup',
        ];
        return $icons[$strategy] ?? 'ki-box';
    }
}