<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\{ DB, Auth };
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{

    /**
     * Product Summary Report
     */
    public function summary(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('product_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        return view('reports.products.summary', [
            'initialFilters' => [
                'category_id'        => $request->get('category_id', ''),
                'product_type'       => $request->get('product_type', 'all'),
                'inventory_strategy' => $request->get('inventory_strategy', 'all'),
                'is_active'          => $request->get('is_active', ''),
                'is_taxable'         => $request->get('is_taxable', ''),
                'per_page'           => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                            ? (int) $request->get('per_page', 15) : 15,
                'page'               => max(1, (int) $request->get('page', 1)),
            ],
        ]);
    }

    public function summaryData(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $categoryId        = $request->get('category_id');
        $productType       = $request->get('product_type', 'all');
        $inventoryStrategy = $request->get('inventory_strategy', 'all');
        $isActive          = $request->get('is_active');
        $isTaxable         = $request->get('is_taxable');
        $perPage           = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;
        $page              = max(1, (int) $request->get('page', 1));

        // ── Filtered products query ────────────────────────────────
        $query = Product::with([
                'category:id,name',
                'variants:id,product_id,grand_total_cost_price,selling_price,overal_quantity_at_hand',
            ])
            ->where('tenant_id', $tenantId);

        if ($categoryId && is_numeric($categoryId)) $query->where('category_id', (int) $categoryId);
        if ($productType && $productType !== 'all')   $query->where('type', $productType);
        if ($inventoryStrategy && $inventoryStrategy !== 'all') $query->where('inventory_strategy', $inventoryStrategy);
        if ($isActive !== null && $isActive !== '')   $query->where('is_active', $isActive);
        if ($isTaxable !== null && $isTaxable !== '') $query->where('is_taxable', $isTaxable);

        $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        // ── Compute row-level metrics for the current page ─────────
        $rows = collect($paginator->items())->map(function ($p) {
            $variants = $p->variants;
            $totalStock   = (float) $variants->sum('overal_quantity_at_hand');
            $totalCost    = (float) $variants->sum(fn($v) =>
                ((float) ($v->grand_total_cost_price ?? 0)) * ((float) ($v->overal_quantity_at_hand ?? 0))
            );
            $totalRevenue = (float) $variants->sum(fn($v) =>
                ((float) ($v->selling_price ?? 0)) * ((float) ($v->overal_quantity_at_hand ?? 0))
            );

            return [
                'id'               => $p->id,
                'sku'              => $p->sku,
                'name'             => $p->name,
                'description'      => $p->description,
                'image_url'        => $p->image_url ? asset($p->image_url) : null,
                'category_name'    => $p->category?->name,
                'type'             => $p->type,
                'inventory_strategy' => $p->inventory_strategy ?? 'quantity',
                'is_active'        => (bool) $p->is_active,
                'is_taxable'       => (bool) $p->is_taxable,
                'variant_count'    => $variants->count(),
                'total_stock'      => $totalStock,
                'total_cost_value' => $totalCost,
                'total_revenue_value' => $totalRevenue,
                'created_at'       => optional($p->created_at)->format('Y-m-d'),
            ];
        })->values();

        // ── Summary (over the FILTERED set, not the page) ──────────
        $summaryRaw = $this->computeSummary($tenantId, [
            'category_id' => $categoryId, 'product_type' => $productType,
            'inventory_strategy' => $inventoryStrategy,
            'is_active' => $isActive, 'is_taxable' => $isTaxable,
        ]);

        // ── Breakdowns (whole tenant, unfiltered — as in your current controller) ──
        $categoryBreakdown = $this->getCategoryBreakdown($tenantId);
        $typeBreakdown     = $this->getTypeBreakdown($tenantId);
        $strategyBreakdown = $this->getStrategyBreakdown($tenantId);
        $statusBreakdown   = $this->getStatusBreakdown($tenantId);
        $taxBreakdown      = $this->getTaxStatusBreakdown($tenantId);

        return response()->json([
            'success'    => true,
            'currency'   => currency_symbol(),
            'products'   => $rows,
            'summary'    => $summaryRaw,
            'breakdowns' => [
                'category' => $categoryBreakdown,
                'type'     => $typeBreakdown,
                'strategy' => $strategyBreakdown,
                'status'   => $statusBreakdown,
                'tax'      => $taxBreakdown,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function summaryMeta(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success'    => true,
            'categories' => $categories,
            'strategies' => ['quantity', 'batch', 'serial', 'recipe'],
            'currency'   => currency_symbol(),
        ]);
    }

    /**
     * GET /reports/products/{id}/drawer
     * Full product detail + list of its variants.
     */
    public function productDrawer(Request $request, $id)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $product = Product::with([
                'category:id,name',
                'productCreater:id,name',
                'variants' => fn($q) => $q->where('tenant_id', $tenantId)->orderBy('name'),
            ])
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        // Variant rows — same shape as variantsData() gives
        $variants = $product->variants->map(function (ProductVariant $v) {
            $qty   = (float) ($v->overal_quantity_at_hand ?? 0);
            $cost  = (float) ($v->grand_total_cost_price ?? 0);
            $price = (float) ($v->selling_price ?? 0);
            $margin = $price > 0 ? (($price - $cost) / $price) * 100 : 0;

            return [
                'id'            => $v->id,
                'sku'           => $v->sku,
                'name'          => $v->name,
                'quantity'      => $qty,
                'cost_price'    => $cost,
                'selling_price' => $price,
                'margin_pct'    => $margin,
                'stock_value'   => $qty * $cost,
                'is_active'     => (bool) $v->is_active,
                'is_taxable'    => (bool) $v->is_taxable,
            ];
        })->values();

        // Roll-up totals from variants
        $totalStock = (float) $product->variants->sum('overal_quantity_at_hand');
        $totalCost  = (float) $product->variants->sum(fn($v) => ((float) $v->grand_total_cost_price) * ((float) $v->overal_quantity_at_hand));
        $totalRev   = (float) $product->variants->sum(fn($v) => ((float) $v->selling_price) * ((float) $v->overal_quantity_at_hand));

        return response()->json([
            'success'  => true,
            'currency' => currency_symbol(),
            'product'  => [
                'id'                 => $product->id,
                'sku'                => $product->sku,
                'name'               => $product->name,
                'description'        => $product->description,
                'image_url'          => $product->image_url ? asset($product->image_url) : null,
                'category_name'      => $product->category?->name,
                'type'               => $product->type,
                'inventory_strategy' => $product->inventory_strategy ?? 'quantity',
                'is_active'          => (bool) $product->is_active,
                'is_taxable'         => (bool) $product->is_taxable,
                'created_by'         => $product->productCreater?->name,
                'created_at'         => optional($product->created_at)->format('d M Y, H:i'),

                'variant_count'      => $product->variants->count(),
                'total_stock'        => $totalStock,
                'total_cost_value'   => $totalCost,
                'total_revenue_value'=> $totalRev,

                'variants'           => $variants,
            ],
        ]);
    }

    /**
     * GET /reports/products/variants/data
     * Paginated list of variants, used by both the Inventory and Margin reports.
     * Query string: category_id, stock_status, min_margin, max_margin, per_page, page
     */
    public function variantsData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;
        $page    = max(1, (int) $request->get('page', 1));

        $query = ProductVariant::with([
                'product:id,name,category_id',
                'product.category:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($request->filled('category_id') && is_numeric($request->category_id)) {
            $cid = (int) $request->category_id;
            $query->whereHas('product', fn($q) => $q->where('category_id', $cid));
        }

        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'low'       => $query->whereBetween('overal_quantity_at_hand', [1, 9]),
                'out'       => $query->where('overal_quantity_at_hand', 0),
                'overstock' => $query->where('overal_quantity_at_hand', '>', 100),
                default     => null,
            };
        }

        if ($request->filled('min_margin') || $request->filled('max_margin')) {
            $min = (float) $request->get('min_margin', 0);
            $max = (float) $request->get('max_margin', 100);

            $query->whereRaw(
                '((`selling_price` - `grand_total_cost_price`) / NULLIF(`selling_price`, 0)) * 100 BETWEEN ? AND ?',
                [$min, $max]
            );
        }

        $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        $rows = collect($paginator->items())->map(function (ProductVariant $v) {
            $qty      = (float) ($v->overal_quantity_at_hand ?? 0);
            $cost     = (float) ($v->grand_total_cost_price ?? 0);
            $price    = (float) ($v->selling_price ?? 0);
            $margin   = $price > 0 ? (($price - $cost) / $price) * 100 : 0;
            $profit   = $price - $cost;

            return [
                'id'             => $v->id,
                'sku'            => $v->sku,
                'name'           => $v->name,
                'product_name'   => $v->product?->name,
                'category_name'  => $v->product?->category?->name,
                'quantity'       => $qty,
                'cost_price'     => $cost,
                'selling_price'  => $price,
                'profit_per_unit'=> $profit,
                'margin_pct'     => $margin,
                'stock_value'    => $qty * $cost,
                'revenue_value'  => $qty * $price,
                'is_active'      => (bool) $v->is_active,
                'is_taxable'     => (bool) $v->is_taxable,
            ];
        })->values();

        return response()->json([
            'success'    => true,
            'currency'   => currency_symbol(),
            'variants'   => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /reports/products/variants/{id}/drawer
     * Full attribute detail for a single variant.
     */
    public function variantDrawer(Request $request, $id)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $v = ProductVariant::with([
                'product:id,name,category_id,image_url,type,inventory_strategy',
                'product.category:id,name',
                'unitMeasure:id,name',
                'variantCreater:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$v) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        // Cost breakdown
        $supplierCost   = (float) ($v->supplier_cost_price ?? 0);
        $shippingCost   = (float) ($v->total_shipping_cost ?? 0);
        $uraTax         = (float) ($v->ura_taxes_applied ?? 0);
        $additionalCost = (float) ($v->additional_expenses ?? 0);
        $grandCost      = (float) ($v->grand_total_cost_price ?? 0);

        $sellingPrice   = (float) ($v->selling_price ?? 0);
        $discountPct    = (float) ($v->discount_percentage ?? 0);
        $discountPrice  = (float) ($v->discount_selling_price ?? $sellingPrice);

        $markupPct      = $grandCost > 0 ? (($sellingPrice - $grandCost) / $grandCost) * 100 : 0;
        $marginPct      = $sellingPrice > 0 ? (($sellingPrice - $grandCost) / $sellingPrice) * 100 : 0;
        $profitPerUnit  = $sellingPrice - $grandCost;

        $qty            = (float) ($v->overal_quantity_at_hand ?? 0);
        $lowStock       = (float) ($v->low_stock_level ?? 0);

        return response()->json([
            'success'  => true,
            'currency' => currency_symbol(),
            'variant'  => [
                'id'            => $v->id,
                'sku'           => $v->sku,
                'name'          => $v->name,
                'barcode'       => $v->barcode,
                'image_url'     => $v->image_url ? asset($v->image_url) : null,
                'product_name'  => $v->product?->name,
                'product_type'  => $v->product?->type,
                'inventory_strategy' => $v->product?->inventory_strategy ?? 'quantity',
                'category_name' => $v->product?->category?->name,
                'unit_name'     => $v->unitMeasure?->name,
                'is_active'     => (bool) $v->is_active,
                'is_taxable'    => (bool) $v->is_taxable,

                // Cost
                'supplier_cost_price'   => $supplierCost,
                'total_shipping_cost'   => $shippingCost,
                'ura_taxes_applied'     => $uraTax,
                'additional_expenses'   => $additionalCost,
                'grand_total_cost_price'=> $grandCost,

                // Pricing
                'selling_price'         => $sellingPrice,
                'discount_percentage'   => $discountPct,
                'discount_selling_price'=> $discountPrice,
                'markup_percentage'     => $markupPct,

                // Profit
                'profit_per_unit'       => $profitPerUnit,
                'profit_margin_pct'     => $marginPct,

                // Inventory
                'overal_quantity_at_hand'=> $qty,
                'low_stock_level'       => $lowStock,
                'is_low_stock'          => $lowStock > 0 && $qty > 0 && $qty <= $lowStock,
                'is_out_of_stock'       => $qty <= 0,
                'stock_cost_value'      => $qty * $grandCost,
                'stock_revenue_value'   => $qty * $sellingPrice,

                // Meta
                'weight'      => $v->weight !== null ? (float) $v->weight : null,
                'weight_unit' => $v->weight_unit,
                'created_by'  => $v->variantCreater?->name,
                'created_at'  => optional($v->created_at)->format('d M Y, H:i'),
                'updated_at'  => optional($v->updated_at)->format('d M Y, H:i'),
            ],
        ]);
    }

    /** Extract the current getProductSummary() into a reusable method. */
    private function computeSummary(int $tenantId, array $filters): array
    {
        $q = Product::where('tenant_id', $tenantId);
        if (!empty($filters['category_id']))        $q->where('category_id', $filters['category_id']);
        if (!empty($filters['product_type']) && $filters['product_type'] !== 'all')
            $q->where('type', $filters['product_type']);
        if (!empty($filters['inventory_strategy']) && $filters['inventory_strategy'] !== 'all')
            $q->where('inventory_strategy', $filters['inventory_strategy']);
        if ($filters['is_active'] !== null && $filters['is_active'] !== '')
            $q->where('is_active', $filters['is_active']);
        if ($filters['is_taxable'] !== null && $filters['is_taxable'] !== '')
            $q->where('is_taxable', $filters['is_taxable']);

        $products   = $q->get(['id', 'is_active', 'inventory_strategy']);
        $productIds = $products->pluck('id');

        $variants = ProductVariant::whereIn('product_id', $productIds)
            ->where('tenant_id', $tenantId)
            ->get(['overal_quantity_at_hand', 'grand_total_cost_price', 'selling_price']);

        $totalStock   = (float) $variants->sum('overal_quantity_at_hand');
        $totalCost    = (float) $variants->sum(fn($v) => ((float) $v->grand_total_cost_price) * ((float) $v->overal_quantity_at_hand));
        $totalRevenue = (float) $variants->sum(fn($v) => ((float) $v->selling_price) * ((float) $v->overal_quantity_at_hand));

        return [
            'total_products'      => $products->count(),
            'total_variants'      => $variants->count(),
            'total_stock'         => $totalStock,
            'total_cost_value'    => $totalCost,
            'total_revenue_value' => $totalRevenue,
            'avg_cost_price'      => $totalStock > 0 ? $totalCost / $totalStock : 0,
            'avg_selling_price'   => $totalStock > 0 ? $totalRevenue / $totalStock : 0,
            'avg_margin'          => $totalRevenue > 0 ? (($totalRevenue - $totalCost) / $totalRevenue) * 100 : 0,
            'active_products'     => $products->where('is_active', true)->count(),
            'strategy_counts'     => $products->groupBy('inventory_strategy')->map->count()->toArray(),
        ];
    }


    /**
     * Get product summary statistics
     */
    private function getProductSummary($tenantId, $filters = [])
    {
        $query = Product::where('tenant_id', $tenantId);
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['product_type']) && $filters['product_type'] !== 'all') {
            $query->where('type', $filters['product_type']);
        }
        if (!empty($filters['inventory_strategy']) && $filters['inventory_strategy'] !== 'all') {
            $query->where('inventory_strategy', $filters['inventory_strategy']);
        }
        if ($filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', $filters['is_active']);
        }
        if ($filters['is_taxable'] !== null && $filters['is_taxable'] !== '') {
            $query->where('is_taxable', $filters['is_taxable']);
        }
        
        $products = $query->get();
        $productIds = $products->pluck('id');
        
        // Get variants for these products
        $variants = ProductVariant::whereIn('product_id', $productIds)
            ->where('tenant_id', $tenantId)
            ->get();
        
        // Calculate variant metrics
        $totalVariants = $variants->count();
        $totalStock = $variants->sum('overal_quantity_at_hand');
        $totalCostValue = $variants->sum(function ($variant) {
            return ($variant->grand_total_cost_price ?? 0) * ($variant->overal_quantity_at_hand ?? 0);
        });
        $totalRevenueValue = $variants->sum(function ($variant) {
            return ($variant->selling_price ?? 0) * ($variant->overal_quantity_at_hand ?? 0);
        });
        
        $avgCostPrice = $totalStock > 0 ? $totalCostValue / $totalStock : 0;
        $avgSellingPrice = $totalStock > 0 ? $totalRevenueValue / $totalStock : 0;
        $avgMargin = $totalRevenueValue > 0 
            ? (($totalRevenueValue - $totalCostValue) / $totalRevenueValue) * 100 
            : 0;
        
        // Strategy counts
        $strategyCounts = $products->groupBy('inventory_strategy')
            ->map->count()
            ->toArray();
        
        return [
            'total_products' => $products->count(),
            'total_variants' => $totalVariants,
            'total_stock' => $totalStock,
            'total_cost_value' => $totalCostValue,
            'total_revenue_value' => $totalRevenueValue,
            'avg_cost_price' => $avgCostPrice,
            'avg_selling_price' => $avgSellingPrice,
            'avg_margin' => $avgMargin,
            'active_products' => $products->where('is_active', true)->count(),
            'strategy_counts' => $strategyCounts,
        ];
    }

    /**
     * Get inventory strategy breakdown
     */
    private function getStrategyBreakdown($tenantId)
    {
        $breakdown = Product::select('inventory_strategy', DB::raw('COUNT(*) as count'))
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->groupBy('inventory_strategy')
            ->orderBy('count', 'desc')
            ->get();
        
        $allStrategies = ['quantity', 'batch', 'serial', 'recipe'];
        $labels = [
            'quantity' => 'Quantity Tracking',
            'batch' => 'Batch Tracking',
            'serial' => 'Serial Tracking',
            'recipe' => 'Recipe Product'
        ];
        $colors = [
            'quantity' => 'primary',
            'batch' => 'info',
            'serial' => 'warning',
            'recipe' => 'success'
        ];
        
        $result = collect();
        foreach ($allStrategies as $strategy) {
            $found = $breakdown->firstWhere('inventory_strategy', $strategy);
            $result->push((object)[
                'strategy' => $labels[$strategy] ?? ucfirst($strategy),
                'key' => $strategy,
                'count' => $found ? $found->count : 0,
                'color' => $colors[$strategy] ?? 'secondary'
            ]);
        }
        
        return $result->filter(fn($item) => $item->count > 0)->values();
    }

    /**
     * Get category breakdown using Eloquent
     */
    private function getCategoryBreakdown($tenantId)
    {
        return ProductCategory::select('product_categories.id', 'product_categories.name', 
                DB::raw('COUNT(products.id) as product_count'))
            ->leftJoin('products', 'product_categories.id', '=', 'products.category_id')
            ->where('product_categories.tenant_id', $tenantId)
            ->where('product_categories.is_active', true)
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderBy('product_count', 'desc')
            ->get()
            ->filter(function($category) {
                return $category->product_count > 0;
            })
            ->values()
            ->map(function($category) {
                return (object)[
                    'name' => $category->name,
                    'product_count' => $category->product_count,
                ];
            });
    }

    /**
     * Get type breakdown using Eloquent
     */
    private function getTypeBreakdown($tenantId)
    {
        $breakdown = Product::select('type', DB::raw('COUNT(*) as count'))
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();
        
        $allTypes = ['physical', 'digital', 'service', 'composite'];
        $result = collect();
        
        foreach ($allTypes as $type) {
            $found = $breakdown->firstWhere('type', $type);
            $result->push((object)[
                'type' => ucfirst($type),
                'count' => $found ? $found->count : 0
            ]);
        }
        
        return $result;
    }

    /**
     * Get status breakdown using Eloquent
     */
    private function getStatusBreakdown($tenantId)
    {
        $active = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
        
        $inactive = Product::where('tenant_id', $tenantId)
            ->where('is_active', false)
            ->count();
        
        return collect([
            (object)['status' => 'Active', 'count' => $active, 'color' => 'success'],
            (object)['status' => 'Inactive', 'count' => $inactive, 'color' => 'danger']
        ]);
    }

    /**
     * Get tax status breakdown using Eloquent
     */
    private function getTaxStatusBreakdown($tenantId)
    {
        $taxable = Product::where('tenant_id', $tenantId)
            ->where('is_taxable', true)
            ->count();
        
        $nonTaxable = Product::where('tenant_id', $tenantId)
            ->where('is_taxable', false)
            ->count();
        
        return collect([
            (object)['status' => 'Taxable', 'count' => $taxable, 'color' => 'primary'],
            (object)['status' => 'Non-Taxable', 'count' => $nonTaxable, 'color' => 'secondary']
        ]);
    }


    /**
     * Product Performance Report - Eloquent-focused implementation with accessors
     */
    public function performance(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('product_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        return view('reports.products.performance', [
            'initialFilters' => [
                'category_id'  => $request->get('category_id', ''),
                'product_type' => $request->get('product_type', 'all'),
                'per_page'     => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                    ? (int) $request->get('per_page', 15) : 15,
                'page'         => max(1, (int) $request->get('page', 1)),
            ],
        ]);
    }

    public function performanceData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $categoryId  = $request->get('category_id');
        $productType = $request->get('product_type', 'all');
        $perPage     = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;
        $page        = max(1, (int) $request->get('page', 1));

        $query = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with([
                'category:id,name',
                'variants:id,product_id,grand_total_cost_price,selling_price,overal_quantity_at_hand',
            ]);

        if ($categoryId && is_numeric($categoryId)) $query->where('category_id', (int) $categoryId);
        if ($productType && $productType !== 'all')  $query->where('type', $productType);

        $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        // ── Map rows: compute cost / revenue / margin per product ──
        $rows = collect($paginator->items())->map(function ($p) {
            $variants = $p->variants;
            $totalStock = (float) $variants->sum('overal_quantity_at_hand');
            $totalCost  = (float) $variants->sum(fn($v) =>
                ((float) ($v->grand_total_cost_price ?? 0)) * ((float) ($v->overal_quantity_at_hand ?? 0))
            );
            $totalRev   = (float) $variants->sum(fn($v) =>
                ((float) ($v->selling_price ?? 0)) * ((float) ($v->overal_quantity_at_hand ?? 0))
            );
            $margin     = $totalRev - $totalCost;
            $marginPct  = $totalRev > 0 ? ($margin / $totalRev) * 100 : 0;

            return [
                'id'                 => $p->id,
                'sku'                => $p->sku,
                'name'               => $p->name,
                'image_url'          => $p->image_url ? asset($p->image_url) : null,
                'category_name'      => $p->category?->name,
                'type'               => $p->type,
                'variant_count'      => $variants->count(),
                'total_stock'        => $totalStock,
                'total_cost_value'   => $totalCost,
                'total_revenue_value'=> $totalRev,
                'total_margin'       => $margin,
                'margin_pct'         => $marginPct,
            ];
        })->values();

        // ── Aggregate summary + charts across the WHOLE filtered set ──
        // Re-query without pagination, but with the same filters — like your original.
        $allQuery = (clone $query)->get();
        $allRows = $allQuery->map(function ($p) {
            $variants = $p->variants;
            $totalCost = (float) $variants->sum(fn($v) =>
                ((float) ($v->grand_total_cost_price ?? 0)) * ((float) ($v->overal_quantity_at_hand ?? 0))
            );
            $totalRev  = (float) $variants->sum(fn($v) =>
                ((float) ($v->selling_price ?? 0)) * ((float) ($v->overal_quantity_at_hand ?? 0))
            );
            $margin = $totalRev - $totalCost;
            return [
                'id'   => $p->id,
                'name' => $p->name,
                'stock'=> (float) $variants->sum('overal_quantity_at_hand'),
                'cost' => $totalCost,
                'rev'  => $totalRev,
                'margin' => $margin,
                'margin_pct' => $totalRev > 0 ? ($margin / $totalRev) * 100 : 0,
            ];
        });

        $totalProducts = $allRows->count();
        $totalStock    = (float) $allRows->sum('stock');
        $totalRevenue  = (float) $allRows->sum('rev');
        $totalMargin   = (float) $allRows->sum('margin');
        $avgMargin     = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;

        $summary = [
            'total_products'      => $totalProducts,
            'total_stock'         => $totalStock,
            'total_revenue_value' => $totalRevenue,
            'total_margin'        => $totalMargin,
            'avg_margin'          => $avgMargin,
        ];

        // Margin distribution buckets
        $buckets = [
            'excellent' => $allRows->where('margin_pct', '>=', 50)->count(),
            'good'      => $allRows->where('margin_pct', '>=', 30)->where('margin_pct', '<', 50)->count(),
            'average'   => $allRows->where('margin_pct', '>=', 10)->where('margin_pct', '<', 30)->count(),
            'poor'      => $allRows->where('margin_pct', '<', 10)->count(),
        ];

        // Top 10 by margin %
        $top10 = $allRows->sortByDesc('margin_pct')->take(10)->values()->map(fn($r) => [
            'name'     => \Str::limit($r['name'], 20),
            'margin'   => $r['margin_pct'],
        ]);

        return response()->json([
            'success'    => true,
            'currency'   => currency_symbol(),
            'products'   => $rows,
            'summary'    => $summary,
            'charts'     => [
                'distribution' => $buckets,
                'top10'        => $top10,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function performanceMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success'    => true,
            'categories' => $categories,
            'currency'   => currency_symbol(),
        ]);
    }


    /**
     * Inventory Valuation Report - Simplified
     */
    public function inventory(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('product_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        return view('reports.products.inventory', [
            'initialFilters' => [
                'category_id'  => $request->get('category_id', ''),
                'stock_status' => $request->get('stock_status', ''),
                'per_page'     => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                    ? (int) $request->get('per_page', 15) : 15,
                'page'         => max(1, (int) $request->get('page', 1)),
            ],
        ]);
    }

    public function inventoryData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;
        $page = max(1, (int) $request->get('page', 1));

        $baseQuery = ProductVariant::with([
                'product:id,name,category_id,image_url',
                'product.category:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($request->filled('category_id') && is_numeric($request->category_id)) {
            $cid = (int) $request->category_id;
            $baseQuery->whereHas('product', fn($q) => $q->where('category_id', $cid));
        }

        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'low'       => $baseQuery->whereBetween('overal_quantity_at_hand', [1, 9]),
                'out'       => $baseQuery->where('overal_quantity_at_hand', 0),
                'overstock' => $baseQuery->where('overal_quantity_at_hand', '>', 100),
                default     => null,
            };
        }

        // Pagination
        $paginator = (clone $baseQuery)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        // Rows for the current page
        $rows = collect($paginator->items())->map(function (ProductVariant $v) {
            $qty   = (float) ($v->overal_quantity_at_hand ?? 0);
            $cost  = (float) ($v->grand_total_cost_price ?? 0);
            $price = (float) ($v->selling_price ?? 0);
            $costValue    = $qty * $cost;
            $revenueValue = $qty * $price;
            $profit       = $revenueValue - $costValue;
            $margin       = $price > 0 ? (($price - $cost) / $price) * 100 : 0;

            $health = $qty == 0
                ? 'out'
                : ($qty < 10 ? 'low' : ($qty > 100 ? 'overstock' : 'healthy'));

            return [
                'id'            => $v->id,
                'sku'           => $v->sku,
                'name'          => $v->name,
                'product_name'  => $v->product?->name,
                'image_url'     => $v->product?->image_url ? asset($v->product->image_url) : null,
                'category_name' => $v->product?->category?->name,
                'quantity'      => $qty,
                'cost_price'    => $cost,
                'selling_price' => $price,
                'cost_value'    => $costValue,
                'revenue_value' => $revenueValue,
                'profit'        => $profit,
                'margin_pct'    => $margin,
                'health'        => $health,
            ];
        })->values();

        // ── Full-set aggregates (not paginated) ─────────────────────
        $allVariants = (clone $baseQuery)
            ->get(['id', 'overal_quantity_at_hand', 'grand_total_cost_price', 'selling_price']);

        $totalQty   = (float) $allVariants->sum('overal_quantity_at_hand');
        $totalCost  = (float) $allVariants->sum(fn($v) => ((float) $v->grand_total_cost_price) * ((float) $v->overal_quantity_at_hand));
        $totalRev   = (float) $allVariants->sum(fn($v) => ((float) $v->selling_price) * ((float) $v->overal_quantity_at_hand));
        $totalProf  = $totalRev - $totalCost;
        $avgMargin  = $totalRev > 0 ? ($totalProf / $totalRev) * 100 : 0;

        $healthBuckets = ['healthy' => 0, 'low' => 0, 'out' => 0, 'overstock' => 0];
        $valueBuckets  = ['healthy' => 0, 'low' => 0, 'out' => 0, 'overstock' => 0];
        foreach ($allVariants as $v) {
            $q = (float) ($v->overal_quantity_at_hand ?? 0);
            $c = (float) ($v->grand_total_cost_price ?? 0);
            $bucket = $q == 0 ? 'out' : ($q < 10 ? 'low' : ($q > 100 ? 'overstock' : 'healthy'));
            $healthBuckets[$bucket]++;
            $valueBuckets[$bucket] += $q * $c;
        }

        return response()->json([
            'success'    => true,
            'currency'   => currency_symbol(),
            'variants'   => $rows,
            'summary'    => [
                'total_items'    => $allVariants->count(),
                'total_quantity' => $totalQty,
                'total_cost'     => $totalCost,
                'total_revenue'  => $totalRev,
                'total_profit'   => $totalProf,
                'avg_margin'     => $avgMargin,
            ],
            'charts' => [
                'health_counts' => $healthBuckets,
                'health_values' => $valueBuckets,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function inventoryMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success'    => true,
            'categories' => $categories,
            'currency'   => currency_symbol(),
        ]);
    }

    /**
     * ✅ Reusable pagination method (same as inventorySales)
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
     * Stock Movement Report
     */
    public function stockMovement(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('product_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        return view('reports.products.stock-movement', [
            'initialFilters' => [
                'category_id' => $request->get('category_id', ''),
                'days'        => in_array((int) $request->get('days', 30), [7, 14, 30, 60, 90], true)
                                    ? (int) $request->get('days', 30) : 30,
                'per_page'    => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                    ? (int) $request->get('per_page', 15) : 15,
                'page'        => max(1, (int) $request->get('page', 1)),
            ],
        ]);
    }

    public function stockMovementData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $categoryId = $request->get('category_id');
        $days       = (int) $request->get('days', 30);
        if (!in_array($days, [7, 14, 30, 60, 90], true)) $days = 30;
        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;
        $page = max(1, (int) $request->get('page', 1));

        $startDate = Carbon::now()->subDays($days);
        $today     = Carbon::now();

        $baseQuery = ProductVariant::with([
                'product:id,name,category_id,image_url',
                'product.category:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                ->orWhere('updated_at', '>=', $startDate);
            });

        if ($categoryId && is_numeric($categoryId)) {
            $cid = (int) $categoryId;
            $baseQuery->whereHas('product', fn($q) => $q->where('category_id', $cid));
        }

        // ── Paginated rows ──────────────────────────────────────
        $paginator = (clone $baseQuery)
            ->orderByDesc('updated_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $rows = collect($paginator->items())->map(function (ProductVariant $v) use ($today) {
            $createdAt = Carbon::parse($v->created_at);
            $updatedAt = Carbon::parse($v->updated_at);
            $daysSinceUpdate   = (int) max(0, round($updatedAt->diffInDays($today, false)));
            $daysSinceCreation = (int) max(0, round($createdAt->diffInDays($today, false)));

            if ($daysSinceUpdate <= 7)       $status = ['key' => 'recent', 'label' => __('auth.recently_updated'), 'color' => 'success', 'icon' => 'check-circle'];
            elseif ($daysSinceUpdate <= 30)  $status = ['key' => 'active', 'label' => __('auth.active'),              'color' => 'primary', 'icon' => 'clock'];
            else                             $status = ['key' => 'stale',  'label' => __('auth.stale'),               'color' => 'warning', 'icon' => 'hourglass'];

            return [
                'id'                  => $v->id,
                'sku'                 => $v->sku,
                'name'                => $v->name,
                'product_name'        => $v->product?->name,
                'category_name'       => $v->product?->category?->name,
                'image_url'           => $v->product?->image_url ? asset($v->product->image_url) : null,
                'quantity'            => (float) ($v->overal_quantity_at_hand ?? 0),
                'created_at'          => optional($v->created_at)->format('Y-m-d'),
                'updated_at'          => optional($v->updated_at)->format('Y-m-d H:i'),
                'days_since_update'   => $daysSinceUpdate,
                'days_since_creation' => $daysSinceCreation,
                'movement_status'     => $status['key'],
                'movement_label'      => $status['label'],
                'movement_color'      => $status['color'],
                'activity_icon'       => $status['icon'],
            ];
        })->values();

        // ── Full-set movement summary + timeline ───────────────
        $allVariants = (clone $baseQuery)->get(['id', 'created_at', 'updated_at']);

        $recentCount = 0;
        $activeCount = 0;
        $staleCount  = 0;
        $newThisMonth = 0;
        $updatedThisWeek = 0;

        // Timeline — last N days bucketed (N = $days, capped at 30 for readability)
        $timelineDays = min($days, 30);
        $activityByDay = [];
        for ($i = $timelineDays - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $activityByDay[$date->format('Y-m-d')] = [
                'date'  => $date->format('M d'),
                'count' => 0,
            ];
        }

        foreach ($allVariants as $v) {
            $createdAt = Carbon::parse($v->created_at);
            $updatedAt = Carbon::parse($v->updated_at);
            $dsu = (int) max(0, round($updatedAt->diffInDays($today, false)));

            if ($dsu <= 7)       { $recentCount++; $updatedThisWeek++; }
            elseif ($dsu <= 30)  { $activeCount++; }
            else                 { $staleCount++; }

            if ($createdAt->diffInDays($today) <= 30) $newThisMonth++;

            $dateKey = $updatedAt->format('Y-m-d');
            if (isset($activityByDay[$dateKey])) $activityByDay[$dateKey]['count']++;
        }

        return response()->json([
            'success'    => true,
            'currency'   => currency_symbol(),
            'variants'   => $rows,
            'summary'    => [
                'recent_count'      => $recentCount,
                'active_count'      => $activeCount,
                'stale_count'       => $staleCount,
                'new_this_month'    => $newThisMonth,
                'updated_this_week' => $updatedThisWeek,
                'total_items'       => $allVariants->count(),
            ],
            'charts' => [
                'timeline' => [
                    'labels' => array_column($activityByDay, 'date'),
                    'data'   => array_column($activityByDay, 'count'),
                ],
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function stockMovementMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success'    => true,
            'categories' => $categories,
            'currency'   => currency_symbol(),
        ]);
    }
        

            
    /**
     * By Category Report — view shell (filters seeded from query string).
     */
    public function byCategory(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('product_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        return view('reports.products.by-category', [
            'initialFilters' => [
                'per_page' => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                ? (int) $request->get('per_page', 15) : 15,
                'page'     => max(1, (int) $request->get('page', 1)),
            ],
        ]);
    }

    /**
     * GET /reports/products/by-category/data
     * Paginated category performance + full-set summary + charts.
     */
    public function byCategoryData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;
        $page    = max(1, (int) $request->get('page', 1));

        // Load categories with their products + variants
        $categories = ProductCategory::with([
                'products:id,category_id,name,image_url',
                'products.variants:id,product_id,grand_total_cost_price,selling_price,overal_quantity_at_hand',
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        // Compute metrics per category — using the REAL column names
        $rows = $categories->map(function (ProductCategory $c) {
            $productCount = $c->products->count();

            $variantCount = 0;
            $totalStock   = 0.0;
            $totalCost    = 0.0;
            $totalRevenue = 0.0;

            foreach ($c->products as $product) {
                foreach ($product->variants as $variant) {
                    $qty   = (float) ($variant->overal_quantity_at_hand ?? 0);
                    $cost  = (float) ($variant->grand_total_cost_price ?? 0);
                    $price = (float) ($variant->selling_price ?? 0);

                    $variantCount++;
                    $totalStock   += $qty;
                    $totalCost    += $cost  * $qty;
                    $totalRevenue += $price * $qty;
                }
            }

            $totalMargin = $totalRevenue - $totalCost;
            $marginPct   = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;

            return [
                'id'                => $c->id,
                'name'              => $c->name,
                'description'       => $c->description,
                'image_url'         => $c->image_url ? asset($c->image_url) : null,
                'product_count'     => $productCount,
                'variant_count'     => $variantCount,
                'total_stock'       => $totalStock,
                'total_cost_value'  => $totalCost,
                'total_revenue_value' => $totalRevenue,
                'total_margin'      => $totalMargin,
                'margin_percentage' => $marginPct,
            ];
        })->sortByDesc('product_count')->values();

        // Summary across ALL categories (not just current page)
        $summary = [
            'total_categories' => $rows->count(),
            'total_products'   => $rows->sum('product_count'),
            'total_variants'   => $rows->sum('variant_count'),
            'total_stock'      => $rows->sum('total_stock'),
            'total_cost_value' => $rows->sum('total_cost_value'),
            'total_value'      => $rows->sum('total_revenue_value'),
            'total_margin'     => $rows->sum('total_margin'),
            'average_margin'   => $rows->avg('margin_percentage') ?? 0,
        ];

        // Paginate the sorted collection
        $paginator = $this->paginateCollection($rows, $perPage, 'page');

        // Page footer totals
        $pageTotals = [
            'product_count'       => collect($paginator->items())->sum('product_count'),
            'variant_count'       => collect($paginator->items())->sum('variant_count'),
            'total_stock'         => collect($paginator->items())->sum('total_stock'),
            'total_cost_value'    => collect($paginator->items())->sum('total_cost_value'),
            'total_revenue_value' => collect($paginator->items())->sum('total_revenue_value'),
            'total_margin'        => collect($paginator->items())->sum('total_margin'),
        ];

        // Charts — always use full set
        $topByProducts = $rows->take(10)->map(fn($r) => [
            'name'          => $r['name'],
            'product_count' => $r['product_count'],
            'variant_count' => $r['variant_count'],
            'total_stock'   => $r['total_stock'],
        ])->values();

        $topByValue = $rows->sortByDesc('total_revenue_value')->take(10)->values()
            ->map(fn($r) => [
                'name'              => $r['name'],
                'total_revenue_value' => $r['total_revenue_value'],
                'margin_percentage' => $r['margin_percentage'],
                'product_count'     => $r['product_count'],
            ]);

        return response()->json([
            'success'    => true,
            'currency'   => currency_symbol(),
            'categories' => collect($paginator->items())->values(),
            'summary'    => $summary,
            'page_totals'=> $pageTotals,
            'charts'     => [
                'top_by_products' => $topByProducts,
                'top_by_value'    => $topByValue,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /reports/products/by-category/meta
     */
    public function byCategoryMeta(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        return response()->json([
            'success'  => true,
            'currency' => currency_symbol(),
        ]);
    }

    /**
     * GET /reports/products/category/{id}/drawer
     * Full category detail + list of its products.
     */
    public function categoryDrawer(Request $request, $id)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $category = ProductCategory::with([
                'productCategoryCreater:id,name',
                'parentCategory:id,name',
                'products' => fn($q) => $q->where('tenant_id', $tenantId)->orderBy('name'),
                'products.variants:id,product_id,grand_total_cost_price,selling_price,overal_quantity_at_hand',
            ])
            ->where('tenant_id', $tenantId)
            ->find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        // ── Per-product rollup (same shape as summaryData rows) ──
        $products = $category->products->map(function (Product $p) {
            $variants     = $p->variants;
            $variantCount = $variants->count();
            $totalStock   = (float) $variants->sum('overal_quantity_at_hand');
            $totalCost    = (float) $variants->sum(fn($v) =>
                ((float) ($v->grand_total_cost_price ?? 0)) * ((float) ($v->overal_quantity_at_hand ?? 0))
            );
            $totalRevenue = (float) $variants->sum(fn($v) =>
                ((float) ($v->selling_price ?? 0)) * ((float) ($v->overal_quantity_at_hand ?? 0))
            );
            $margin       = $totalRevenue - $totalCost;
            $marginPct    = $totalRevenue > 0 ? ($margin / $totalRevenue) * 100 : 0;

            return [
                'id'                 => $p->id,
                'sku'                => $p->sku,
                'name'               => $p->name,
                'image_url'          => $p->image_url ? asset($p->image_url) : null,
                'type'               => $p->type,
                'inventory_strategy' => $p->inventory_strategy ?? 'quantity',
                'is_active'          => (bool) $p->is_active,
                'is_taxable'         => (bool) $p->is_taxable,
                'variant_count'      => $variantCount,
                'total_stock'        => $totalStock,
                'total_cost_value'   => $totalCost,
                'total_revenue_value'=> $totalRevenue,
                'total_margin'       => $margin,
                'margin_pct'         => $marginPct,
            ];
        })->values();

        // ── Rollup for the whole category ──
        $totalStock   = (float) $products->sum('total_stock');
        $totalCost    = (float) $products->sum('total_cost_value');
        $totalRevenue = (float) $products->sum('total_revenue_value');
        $totalMargin  = $totalRevenue - $totalCost;
        $marginPct    = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;

        return response()->json([
            'success'  => true,
            'currency' => currency_symbol(),
            'category' => [
                'id'              => $category->id,
                'name'            => $category->name,
                'description'     => $category->description,
                'image_url'       => $category->image_url ? asset($category->image_url) : null,
                'is_active'       => (bool) $category->is_active,
                'parent_name'     => $category->parentCategory?->name,
                'created_by'      => $category->productCategoryCreater?->name,
                'created_at'      => optional($category->created_at)->format('d M Y, H:i'),

                'product_count'      => $products->count(),
                'variant_count'      => (int) $products->sum('variant_count'),
                'total_stock'        => $totalStock,
                'total_cost_value'   => $totalCost,
                'total_revenue_value'=> $totalRevenue,
                'total_margin'       => $totalMargin,
                'margin_percentage'  => $marginPct,

                'products'           => $products,
            ],
        ]);
    }
        
    
    /**
     * Product Margin Report — view shell.
     */
    public function margin(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('product_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }

        return view('reports.products.margin', [
            'initialFilters' => [
                'category_id' => $request->get('category_id', ''),
                'min_margin'  => (float) $request->get('min_margin', 0),
                'max_margin'  => (float) $request->get('max_margin', 100),
                'per_page'    => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                    ? (int) $request->get('per_page', 15) : 15,
                'page'        => max(1, (int) $request->get('page', 1)),
            ],
        ]);
    }

    /**
     * GET /reports/products/margin/data
     * Paginated variants ranked by margin %, plus summary + charts.
     */
    public function marginData(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $categoryId = $request->get('category_id');
        $minMargin  = (float) $request->get('min_margin', 0);
        $maxMargin  = (float) $request->get('max_margin', 100);
        $perPage    = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;
        $page       = max(1, (int) $request->get('page', 1));

        $query = ProductVariant::with([
                'product:id,name,category_id,image_url,type',
                'product.category:id,name',
                'unitMeasure:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($categoryId && is_numeric($categoryId)) {
            $query->whereHas('product', fn($q) => $q->where('category_id', (int) $categoryId));
        }

        $allVariants = $query->get();

        // Map + compute margin metrics using the real accessor columns
        $rows = $allVariants->map(function (ProductVariant $v) {
            $price    = (float) ($v->selling_price ?? 0);
            $cost     = (float) ($v->grand_total_cost_price ?? 0);
            $qty      = (float) ($v->overal_quantity_at_hand ?? 0);
            $marginAmt = $price - $cost;
            $marginPct = $price > 0 ? ($marginAmt / $price) * 100 : 0;

            if ($marginPct >= 50)      $band = ['key' => 'high',      'color' => 'success', 'label' => __('auth.high_margin')];
            elseif ($marginPct >= 30)  $band = ['key' => 'medium',    'color' => 'primary', 'label' => __('auth.medium_margin')];
            elseif ($marginPct >= 10)  $band = ['key' => 'low',       'color' => 'warning', 'label' => __('auth.low_margin')];
            else                       $band = ['key' => 'very_low',  'color' => 'danger',  'label' => __('auth.very_low_margin')];

            return [
                'id'                 => $v->id,
                'sku'                => $v->sku,
                'name'               => $v->name,
                'image_url'          => $v->image_url ? asset($v->image_url) : ($v->product?->image_url ? asset($v->product->image_url) : null),
                'product_name'       => $v->product?->name,
                'category_name'      => $v->product?->category?->name,
                'unit_name'          => $v->unitMeasure?->name,
                'quantity'           => $qty,
                'cost_price'         => $cost,
                'selling_price'      => $price,
                'margin_amount'      => $marginAmt,
                'margin_percentage'  => $marginPct,
                'total_margin_value' => $marginAmt * $qty,
                'margin_category'    => $band['key'],
                'margin_label'       => $band['label'],
                'margin_color'       => $band['color'],
                'is_active'          => (bool) $v->is_active,
                'is_taxable'         => (bool) $v->is_taxable,
            ];
        });

        // Range filter, then sort by margin % desc
        $sorted = $rows
            ->filter(fn($r) => $r['margin_percentage'] >= $minMargin && $r['margin_percentage'] <= $maxMargin)
            ->sortByDesc('margin_percentage')
            ->values();

        // Summary over the FILTERED set
        $summary = [
            'total_variants'        => $sorted->count(),
            'average_margin'        => $sorted->avg('margin_percentage') ?? 0,
            'total_margin_value'    => $sorted->sum('total_margin_value'),
            'high_margin_count'     => $sorted->where('margin_percentage', '>=', 50)->count(),
            'medium_margin_count'   => $sorted->where('margin_percentage', '>=', 30)->where('margin_percentage', '<', 50)->count(),
            'low_margin_count'      => $sorted->where('margin_percentage', '>=', 10)->where('margin_percentage', '<', 30)->count(),
            'very_low_margin_count' => $sorted->where('margin_percentage', '<', 10)->count(),
        ];

        // Page totals for footer
        $paginator = $this->paginateCollection($sorted, $perPage, 'page');
        $pageRows  = collect($paginator->items());
        $pageTotals = [
            'quantity'           => $pageRows->sum('quantity'),
            'total_margin_value' => $pageRows->sum('total_margin_value'),
        ];

        // Charts
        $distribution = [
            'high'     => $summary['high_margin_count'],
            'medium'   => $summary['medium_margin_count'],
            'low'      => $summary['low_margin_count'],
            'very_low' => $summary['very_low_margin_count'],
        ];

        $top10 = $sorted->take(10)->map(fn($r) => [
            'name'   => \Str::limit($r['product_name'] ?: $r['name'], 22),
            'margin' => $r['margin_percentage'],
            'color'  => $r['margin_color'],
        ])->values();

        return response()->json([
            'success'    => true,
            'currency'   => currency_symbol(),
            'variants'   => $pageRows->values(),
            'summary'    => $summary,
            'page_totals'=> $pageTotals,
            'charts'     => [
                'distribution' => $distribution,
                'top10'        => $top10,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /reports/products/margin/meta
     */
    public function marginMeta(Request $request)
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('product_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success'    => true,
            'categories' => $categories,
            'currency'   => currency_symbol(),
        ]);
    }

}