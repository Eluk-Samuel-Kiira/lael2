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
        
        // Get filter parameters
        $categoryId = $request->get('category_id');
        $productType = $request->get('product_type');
        $inventoryStrategy = $request->get('inventory_strategy');
        $isActive = $request->get('is_active');
        $isTaxable = $request->get('is_taxable');
        $perPage = $request->get('per_page', 15);
        
        // Build base query with eager loading
        $query = Product::with(['category', 'variants'])
            ->where('tenant_id', $tenantId);
        
        // Apply filters
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        if ($productType && $productType !== 'all') {
            $query->where('type', $productType);
        }
        
        if ($inventoryStrategy && $inventoryStrategy !== 'all') {
            $query->where('inventory_strategy', $inventoryStrategy);
        }
        
        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', $isActive);
        }
        
        if ($isTaxable !== null && $isTaxable !== '') {
            $query->where('is_taxable', $isTaxable);
        }
        
        // Get products with pagination
        $products = $query->paginate($perPage)->withQueryString();
        
        // Calculate aggregated metrics on the collection
        $products->getCollection()->transform(function ($product) {
            // Calculate prices from variants
            $variants = $product->variants;
            $totalCostValue = $variants->sum(function ($variant) {
                return ($variant->grand_total_cost_price ?? 0) * ($variant->overal_quantity_at_hand ?? 0);
            });
            $totalRevenueValue = $variants->sum(function ($variant) {
                return ($variant->selling_price ?? 0) * ($variant->overal_quantity_at_hand ?? 0);
            });
            
            // Average prices (weighted by stock)
            $totalStock = $variants->sum('overal_quantity_at_hand');
            $avgCostPrice = $totalStock > 0 ? $totalCostValue / $totalStock : 0;
            $avgSellingPrice = $totalStock > 0 ? $totalRevenueValue / $totalStock : 0;
            
            $product->total_stock = $totalStock;
            $product->avg_cost_price = $avgCostPrice;
            $product->avg_selling_price = $avgSellingPrice;
            $product->total_cost_value = $totalCostValue;
            $product->total_revenue_value = $totalRevenueValue;
            $product->variant_count = $variants->count();
            
            return $product;
        });
        
        // Get summary statistics
        $summary = $this->getProductSummary($tenantId, [
            'category_id' => $categoryId,
            'product_type' => $productType,
            'inventory_strategy' => $inventoryStrategy,
            'is_active' => $isActive,
            'is_taxable' => $isTaxable
        ]);
        
        // Get breakdowns
        $categoryBreakdown = $this->getCategoryBreakdown($tenantId);
        $typeBreakdown = $this->getTypeBreakdown($tenantId);
        $strategyBreakdown = $this->getStrategyBreakdown($tenantId);
        $statusBreakdown = $this->getStatusBreakdown($tenantId);
        $taxBreakdown = $this->getTaxStatusBreakdown($tenantId);
        
        // Get filter options
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $strategies = ['quantity', 'batch', 'serial', 'recipe'];
        
        return view('reports.products.summary', compact(
            'products',
            'summary',
            'categoryBreakdown',
            'typeBreakdown',
            'strategyBreakdown',
            'statusBreakdown',
            'taxBreakdown',
            'categories',
            'strategies',
            'categoryId',
            'productType',
            'inventoryStrategy',
            'isActive',
            'isTaxable',
            'perPage'
        ));
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
        
        // Get filter parameters with validation
        $categoryId = $request->get('category_id');
        $productType = $request->get('product_type');
        $perPage = $request->get('per_page', 15);
        
        // Build Eloquent query with efficient eager loading
        $query = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with([
                'category' => fn($q) => $q->select('id', 'name'),
                'variants' => fn($q) => $q->select('product_id', 'grand_total_cost_price', 'selling_price', 'overal_quantity_at_hand')
            ]);
        
        // Apply filters using Eloquent where clauses
        if ($categoryId && is_numeric($categoryId)) {
            $query->where('category_id', (int)$categoryId);
        }
        
        if ($productType && $productType !== 'all') {
            $query->where('type', $productType);
        }
        
        // Paginate first, then calculate metrics on the collection
        $products = $query->paginate($perPage)->withQueryString();
        
        // In the transform function, add a check for zero values
        $products->getCollection()->transform(function ($product) {
            $variants = $product->variants;
            
            // Skip products with no variants
            if ($variants->isEmpty()) {
                $product->total_stock = 0;
                $product->total_cost_value = 0;
                $product->total_revenue_value = 0;
                $product->total_margin = 0;
                $product->margin_percentage = 0;
                return $product;
            }
            
            $totalStock = $variants->sum('overal_quantity_at_hand');
            
            $totalCostValue = $variants->sum(function ($variant) {
                $costPrice = $variant->grand_total_cost_price ?? 0;
                $quantity = $variant->overal_quantity_at_hand ?? 0;
                return $costPrice * $quantity;
            });
            
            $totalRevenueValue = $variants->sum(function ($variant) {
                $sellingPrice = $variant->selling_price ?? 0;
                $quantity = $variant->overal_quantity_at_hand ?? 0;
                return $sellingPrice * $quantity;
            });
            
            $totalMargin = $totalRevenueValue - $totalCostValue;
            $marginPercentage = $totalRevenueValue > 0 ? ($totalMargin / $totalRevenueValue) * 100 : 0;
            
            // Debug log to check values
            // \Log::info('Product Performance Calculation', [
            //     'product_id' => $product->id,
            //     'product_name' => $product->name,
            //     'total_stock' => $totalStock,
            //     'total_cost_value' => $totalCostValue,
            //     'total_revenue_value' => $totalRevenueValue,
            //     'total_margin' => $totalMargin,
            //     'margin_percentage' => $marginPercentage,
            //     'variants_count' => $variants->count()
            // ]);
            
            $product->total_stock = $totalStock;
            $product->total_cost_value = $totalCostValue;
            $product->total_revenue_value = $totalRevenueValue;
            $product->total_margin = $totalMargin;
            $product->margin_percentage = $marginPercentage;
            
            return $product;
        });
                
        // Sort the collection by margin percentage
        $sortedProducts = $products->getCollection()->sortByDesc('margin_percentage')->values();
        $products->setCollection($sortedProducts);
        
        // Get filter options
        $categories = ProductCategory::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();
        
        return view('reports.products.performance', compact(
            'products',
            'categories',
            'categoryId',
            'productType',
            'perPage'
        ));
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
        
        $categoryId = $request->get('category_id');
        $stockStatus = $request->get('stock_status');
        $perPage = (int)$request->get('per_page', 15);
        
        $query = ProductVariant::with(['product.category'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        if ($categoryId && is_numeric($categoryId)) {
            $query->whereHas('product', function($q) use ($categoryId) {
                $q->where('category_id', (int)$categoryId);
            });
        }
        
        if ($stockStatus === 'low') {
            $query->where('overal_quantity_at_hand', '<', 10)
                ->where('overal_quantity_at_hand', '>', 0);
        } elseif ($stockStatus === 'out') {
            $query->where('overal_quantity_at_hand', '=', 0);
        } elseif ($stockStatus === 'overstock') {
            $query->where('overal_quantity_at_hand', '>', 100);
        }
        
        // Get all variants
        $allVariants = $query->get();
        
        // Calculate stock health categories
        $healthyCount = 0;
        $warningCount = 0;
        $criticalCount = 0;
        $healthyValue = 0;
        $lowStockValue = 0;
        $overstockValue = 0;
        $totalCostValue = 0;
        $totalRevenueValue = 0;
        $totalPotentialProfit = 0;
        $totalQuantity = 0;
        
        foreach ($allVariants as $variant) {
            $costPrice = (float)($variant->grand_total_cost_price ?? 0);
            $sellingPrice = (float)($variant->selling_price ?? 0);
            $quantity = (float)($variant->overal_quantity_at_hand ?? 0);
            
            $costValue = $costPrice * $quantity;
            $revenueValue = $sellingPrice * $quantity;
            $potentialProfit = $revenueValue - $costValue;
            
            $totalCostValue += $costValue;
            $totalRevenueValue += $revenueValue;
            $totalPotentialProfit += $potentialProfit;
            $totalQuantity += $quantity;
            
            // Stock health
            if ($quantity == 0) {
                $criticalCount++;
            } elseif ($quantity < 10) {
                $warningCount++;
                $lowStockValue += $costValue;
            } elseif ($quantity > 100) {
                $warningCount++;
                $overstockValue += $costValue;
            } else {
                $healthyCount++;
                $healthyValue += $costValue;
            }
        }
        
        $totalValuation = [
            'total_cost_value' => $totalCostValue,
            'total_revenue_value' => $totalRevenueValue,
            'total_potential_profit' => $totalPotentialProfit,
            'total_items' => $allVariants->count(),
            'total_quantity' => $totalQuantity,
            'low_stock_count' => $warningCount,
            'out_of_stock_count' => $criticalCount,
            'overstock_count' => $warningCount,
            'healthy_count' => $healthyCount,
            'warning_count' => $warningCount,
            'critical_count' => $criticalCount,
            'healthy_value' => $healthyValue,
            'low_stock_value' => $lowStockValue,
            'overstock_value' => $overstockValue,
            'avg_margin' => $totalRevenueValue > 0 ? ($totalPotentialProfit / $totalRevenueValue) * 100 : 0,
        ];
        
        // Paginate
        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $currentPageItems = $allVariants->slice(($page - 1) * $perPage, $perPage)->values();
        
        $variants = new LengthAwarePaginator(
            $currentPageItems,
            $allVariants->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
                'query' => request()->except('page', 'per_page')
            ]
        );
        
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();
        
        return view('reports.products.inventory', compact(
            'variants',
            'totalValuation',
            'categories',
            'categoryId',
            'stockStatus',
            'perPage'
        ));
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
        
        $categoryId = $request->get('category_id');
        $days = (int)$request->get('days', 30);
        $perPage = (int)$request->get('per_page', 15);
        
        $startDate = Carbon::now()->subDays($days);
        
        // Build query
        $query = ProductVariant::with(['product.category'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                    ->orWhere('updated_at', '>=', $startDate);
            });
        
        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        
        // ✅ Get ALL variants for calculations
        $allVariants = $query->orderBy('updated_at', 'desc')->get();
        
        // ✅ Calculate movement metrics
        $today = Carbon::now();
        $recentCount = 0;
        $activeCount = 0;
        $staleCount = 0;
        $newThisMonth = 0;
        $updatedThisWeek = 0;
        $activityByDay = [];
        
        // Initialize activity by day (last 30 days)
        for ($i = 29; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $activityByDay[$date->format('Y-m-d')] = [
                'date' => $date->format('M d'),
                'count' => 0
            ];
        }
        
        foreach ($allVariants as $variant) {
            $createdAt = Carbon::parse($variant->created_at);
            $updatedAt = Carbon::parse($variant->updated_at);
            
            $variant->days_since_update = $updatedAt->diffInDays($today);
            $variant->days_since_creation = $createdAt->diffInDays($today);
            
            // Movement status
            if ($variant->days_since_update <= 7) {
                $variant->movement_status = 'recent';
                $variant->movement_label = __('auth.recently_updated');
                $variant->movement_color = 'success';
                $recentCount++;
                $updatedThisWeek++;
            } elseif ($variant->days_since_update <= 30) {
                $variant->movement_status = 'active';
                $variant->movement_label = __('auth.active');
                $variant->movement_color = 'primary';
                $activeCount++;
            } else {
                $variant->movement_status = 'stale';
                $variant->movement_label = __('auth.stale');
                $variant->movement_color = 'warning';
                $staleCount++;
            }
            
            // New this month
            if ($createdAt->diffInDays($today) <= 30) {
                $newThisMonth++;
            }
            
            // Activity by day (count updates per day)
            $dateKey = $updatedAt->format('Y-m-d');
            if (isset($activityByDay[$dateKey])) {
                $activityByDay[$dateKey]['count']++;
            }
        }
        
        $movementSummary = [
            'recent_count' => $recentCount,
            'active_count' => $activeCount,
            'stale_count' => $staleCount,
            'new_this_month' => $newThisMonth,
            'updated_this_week' => $updatedThisWeek,
            'total_items' => $allVariants->count(),
        ];
        
        // ✅ Apply pagination using helper method
        $variants = $this->paginateCollection($allVariants, $perPage, 'page');
        
        // ✅ Activity data for chart
        $activityLabels = array_column($activityByDay, 'date');
        $activityData = array_column($activityByDay, 'count');
        
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.products.stock-movement', compact(
            'variants',
            'movementSummary',
            'categories',
            'categoryId',
            'allVariants',
            'days',
            'perPage',
            'activityLabels',
            'activityData',
            'recentCount',
            'activeCount',
            'staleCount'
        ));
    }
    
    /**
     * Product Margin Report
     */
    public function margin(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get filter parameters
        $categoryId = $request->get('category_id');
        $minMargin = (float)$request->get('min_margin', 0);
        $maxMargin = (float)$request->get('max_margin', 100);
        $perPage = (int)$request->get('per_page', 15);
        
        // Get product variants with cost and price
        $query = ProductVariant::with(['product.category', 'unitMeasure'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        
        // Get ALL variants for calculations
        $allVariants = $query->get();
        
        // ✅ Debug first variant to see what's in the database
        if ($allVariants->count() > 0) {
            $first = $allVariants->first();
            // \Log::info('Margin Debug - First Variant', [
            //     'id' => $first->id,
            //     'sku' => $first->sku,
            //     'raw_grand_total_cost_price' => $first->getRawOriginal('grand_total_cost_price'),
            //     'raw_selling_price' => $first->getRawOriginal('selling_price'),
            //     'grand_total_cost_price' => $first->grand_total_cost_price,
            //     'selling_price' => $first->selling_price,
            //     'overal_quantity_at_hand' => $first->overal_quantity_at_hand,
            // ]);
        }
        
        // Transform variants with calculated metrics
        $allVariants->transform(function ($variant) {
            // ✅ Use correct field names with fallback to 0
            $sellingPrice = (float)($variant->selling_price ?? 0);
            $costPrice = (float)($variant->grand_total_cost_price ?? 0);
            $quantity = (float)($variant->overal_quantity_at_hand ?? 0);
            
            // Calculate margin
            $marginAmount = $sellingPrice - $costPrice;
            $marginPercentage = $sellingPrice > 0 ? ($marginAmount / $sellingPrice) * 100 : 0;
            
            // Set properties for blade
            $variant->price = $sellingPrice;
            $variant->cost_price = $costPrice;
            $variant->margin_amount = $marginAmount;
            $variant->margin_percentage = $marginPercentage;
            $variant->total_margin_value = $marginAmount * $quantity;
            
            // Determine category
            if ($marginPercentage >= 50) {
                $variant->margin_category = 'high';
                $variant->margin_label = __('auth.high_margin');
                $variant->margin_color = 'success';
                $variant->margin_badge = 'success';
            } elseif ($marginPercentage >= 30) {
                $variant->margin_category = 'medium';
                $variant->margin_label = __('auth.medium_margin');
                $variant->margin_color = 'primary';
                $variant->margin_badge = 'primary';
            } elseif ($marginPercentage >= 10) {
                $variant->margin_category = 'low';
                $variant->margin_label = __('auth.low_margin');
                $variant->margin_color = 'warning';
                $variant->margin_badge = 'warning';
            } else {
                $variant->margin_category = 'very_low';
                $variant->margin_label = __('auth.very_low_margin');
                $variant->margin_color = 'danger';
                $variant->margin_badge = 'danger';
            }
            
            return $variant;
        });
        
        // Apply margin range filter
        $filteredVariants = $allVariants->filter(function ($variant) use ($minMargin, $maxMargin) {
            return $variant->margin_percentage >= $minMargin && 
                $variant->margin_percentage <= $maxMargin;
        });
        
        // Sort by margin percentage descending
        $sortedVariants = $filteredVariants->sortByDesc('margin_percentage')->values();
        
        // Apply pagination
        $variants = $this->paginateCollection($sortedVariants, $perPage, 'page');
        
        // Get margin summary
        $marginSummary = [
            'total_variants' => $sortedVariants->count(),
            'average_margin' => $sortedVariants->avg('margin_percentage') ?? 0,
            'total_margin_value' => $sortedVariants->sum('total_margin_value') ?? 0,
            'high_margin_count' => $sortedVariants->where('margin_percentage', '>=', 50)->count(),
            'medium_margin_count' => $sortedVariants->where('margin_percentage', '>=', 30)
                ->where('margin_percentage', '<', 50)
                ->count(),
            'low_margin_count' => $sortedVariants->where('margin_percentage', '>=', 10)
                ->where('margin_percentage', '<', 30)
                ->count(),
            'very_low_margin_count' => $sortedVariants->where('margin_percentage', '<', 10)->count(),
        ];
        
        // ✅ Log the margin summary
        // \Log::info('Margin Summary', $marginSummary);
        
        // Top 10 products for chart
        $topMarginProducts = $sortedVariants->take(10);
        
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.products.margin', compact(
            'variants',
            'sortedVariants',
            'topMarginProducts',
            'marginSummary',
            'categories',
            'categoryId',
            'minMargin',
            'maxMargin',
            'perPage'
        ));
    }
        
    /**
     * By Category Report with Pagination
     */
    public function byCategory(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $perPage = (int)$request->get('per_page', 15);
        
        // Get category performance
        $categories = ProductCategory::with(['products.variants'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
        
        // Calculate metrics for each category
        $categories->each(function ($category) {
            $category->product_count = $category->products->count();
            $category->variant_count = $category->products->sum(function ($product) {
                return $product->variants->count();
            });
            $category->total_stock = $category->products->sum(function ($product) {
                return $product->variants->sum('overal_quantity_at_hand');
            });
            $category->total_cost_value = $category->products->sum(function ($product) {
                return $product->variants->sum(function ($variant) {
                    return ($variant->cost_price ?? 0) * ($variant->overal_quantity_at_hand ?? 0);
                });
            });
            $category->total_revenue_value = $category->products->sum(function ($product) {
                return $product->variants->sum(function ($variant) {
                    return ($variant->price ?? 0) * ($variant->overal_quantity_at_hand ?? 0);
                });
            });
            $category->total_margin = $category->total_revenue_value - $category->total_cost_value;
            $category->margin_percentage = $category->total_revenue_value > 0 
                ? ($category->total_margin / $category->total_revenue_value) * 100 
                : 0;
        });
        
        // Sort by product count (descending)
        $sortedCategories = $categories->sortByDesc('product_count')->values();
        
        // ✅ Apply pagination using your helper method
        $paginatedCategories = $this->paginateCollection($sortedCategories, $perPage, 'page');
        
        // Get category summary from ALL categories (not paginated)
        $categorySummary = [
            'total_categories' => $categories->count(),
            'total_products' => $categories->sum('product_count'),
            'total_variants' => $categories->sum('variant_count'),
            'total_stock' => $categories->sum('total_stock'),
            'total_value' => $categories->sum('total_revenue_value'),
            'average_margin' => $categories->avg('margin_percentage'),
            'total_cost_value' => $categories->sum('total_cost_value'),
            'total_margin' => $categories->sum('total_margin'),
        ];
        
        // Get top categories for charts (from all data, not paginated)
        $topCategoriesByProducts = $sortedCategories->take(10);
        $topCategoriesByValue = $sortedCategories->sortByDesc('total_revenue_value')->take(10);
        
        return view('reports.products.by-category', compact(
            'paginatedCategories',
            'sortedCategories',
            'categorySummary',
            'topCategoriesByProducts',
            'topCategoriesByValue',
            'perPage'
        ));
    }
        

}