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
        
        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', $isActive);
        }
        
        if ($isTaxable !== null && $isTaxable !== '') {
            $query->where('is_taxable', $isTaxable);
        }
        
        // Get products with pagination
        $products = $query->paginate($perPage)->withQueryString();
        
        // Get summary statistics
        $summary = $this->getProductSummary($tenantId, [
            'category_id' => $categoryId,
            'product_type' => $productType,
            'is_active' => $isActive,
            'is_taxable' => $isTaxable
        ]);
        
        // Get category breakdown
        $categoryBreakdown = $this->getCategoryBreakdown($tenantId);
        
        // Get type breakdown
        $typeBreakdown = $this->getTypeBreakdown($tenantId);
        
        // Get status breakdown
        $statusBreakdown = $this->getStatusBreakdown($tenantId);
        
        // Get tax status breakdown
        $taxBreakdown = $this->getTaxStatusBreakdown($tenantId);
        
        // Get filter options
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return view('reports.products.summary', compact(
            'products',
            'summary',
            'categoryBreakdown',
            'typeBreakdown',
            'statusBreakdown',
            'taxBreakdown',
            'categories',
            'categoryId',
            'productType',
            'isActive',
            'isTaxable',
            'perPage'
        ));
    }

    /**
     * Get product summary statistics using pure Eloquent
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
        if ($filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', $filters['is_active']);
        }
        if ($filters['is_taxable'] !== null && $filters['is_taxable'] !== '') {
            $query->where('is_taxable', $filters['is_taxable']);
        }
        
        $products = $query->get();
        
        // Calculate total variants count
        $totalVariants = ProductVariant::whereIn('product_id', $products->pluck('id'))->count();
        
        // Calculate total stock
        $totalStock = ProductVariant::whereIn('product_id', $products->pluck('id'))
            ->sum('overal_quantity_at_hand');
        
        return [
            'total_products' => $products->count(),
            'total_variants' => $totalVariants,
            'total_stock' => $totalStock,
            'average_price' => $products->avg('price') ?? 0,
            'average_cost' => $products->avg('cost') ?? 0,
            'active_products' => $products->where('is_active', true)->count(),
        ];
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
                'variants' => fn($q) => $q->select('product_id', 'cost_price', 'price', 'overal_quantity_at_hand')
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
        
        // Calculate performance metrics on the paginated collection
        // This preserves the Product model instances and their accessors
        $products->getCollection()->transform(function ($product) {
            $variants = $product->variants;
            
            // Use collection methods for calculations
            $totalStock = $variants->sum('overal_quantity_at_hand');
            $totalCostValue = $variants->sum(function ($variant) {
                return ($variant->cost_price ?? 0) * ($variant->overal_quantity_at_hand ?? 0);
            });
            $totalRevenueValue = $variants->sum(function ($variant) {
                return ($variant->price ?? 0) * ($variant->overal_quantity_at_hand ?? 0);
            });
            $totalMargin = $totalRevenueValue - $totalCostValue;
            $marginPercentage = $totalRevenueValue > 0 ? ($totalMargin / $totalRevenueValue) * 100 : 0;
            
            // Add calculated attributes to the product model
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
     * Inventory Valuation Report with Clean Pagination Pattern
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
        
        // Build query with eager loading
        $query = ProductVariant::with(['product.category', 'unitMeasure'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        // Apply filters
        if ($categoryId && is_numeric($categoryId)) {
            $query->whereHas('product', fn($q) => $q->where('category_id', (int)$categoryId));
        }
        
        if ($stockStatus === 'low') {
            $query->where('overal_quantity_at_hand', '<', 10);
        } elseif ($stockStatus === 'out') {
            $query->where('overal_quantity_at_hand', '=', 0);
        } elseif ($stockStatus === 'overstock') {
            $query->where('overal_quantity_at_hand', '>', 100);
        }
        
        // Get ALL variants for calculations
        $allVariants = $query->get();
        
        // Calculate metrics for ALL variants
        $allVariants->transform(function ($variant) {
            $variant->cost_value = ($variant->cost_price ?? 0) * $variant->overal_quantity_at_hand;
            $variant->revenue_value = ($variant->price ?? 0) * $variant->overal_quantity_at_hand;
            $variant->stock_value = $variant->cost_value;
            $variant->potential_profit = $variant->revenue_value - $variant->cost_value;
            $variant->margin_percentage = $variant->price > 0 
                ? (($variant->price - ($variant->cost_price ?? 0)) / $variant->price) * 100 
                : 0;
            
            // Stock health
            if ($variant->overal_quantity_at_hand == 0) {
                $variant->stock_health = 'critical';
                $variant->stock_status = __('auth.out_of_stock');
                $variant->stock_color = 'danger';
            } elseif ($variant->overal_quantity_at_hand < 10) {
                $variant->stock_health = 'warning';
                $variant->stock_status = __('auth.low_stock');
                $variant->stock_color = 'warning';
            } elseif ($variant->overal_quantity_at_hand > 100) {
                $variant->stock_health = 'warning';
                $variant->stock_status = __('auth.overstock');
                $variant->stock_color = 'info';
            } else {
                $variant->stock_health = 'healthy';
                $variant->stock_status = __('auth.in_stock');
                $variant->stock_color = 'success';
            }
            return $variant;
        });
        
        // Sort ALL variants by stock value
        $sortedVariants = $allVariants->sortByDesc('stock_value')->values();
        
        // ✅ Use the SAME pagination pattern as inventorySales
        $variants = $this->paginateCollection($sortedVariants, $perPage, 'page');
        
        // Calculate totals from ALL variants (for summary cards and charts)
        $totalValuation = [
            'total_cost_value' => $allVariants->sum('cost_value'),
            'total_revenue_value' => $allVariants->sum('revenue_value'),
            'total_potential_profit' => $allVariants->sum('potential_profit'),
            'total_items' => $allVariants->count(),
            'total_quantity' => $allVariants->sum('overal_quantity_at_hand'),
            'low_stock_count' => $allVariants->filter(fn($v) => $v->overal_quantity_at_hand < 10 && $v->overal_quantity_at_hand > 0)->count(),
            'out_of_stock_count' => $allVariants->filter(fn($v) => $v->overal_quantity_at_hand == 0)->count(),
            'overstock_count' => $allVariants->filter(fn($v) => $v->overal_quantity_at_hand > 100)->count(),
            'healthy_count' => $allVariants->filter(fn($v) => $v->stock_health === 'healthy')->count(),
            'warning_count' => $allVariants->filter(fn($v) => $v->stock_health === 'warning')->count(),
            'critical_count' => $allVariants->filter(fn($v) => $v->stock_health === 'critical')->count(),
            'healthy_value' => $allVariants->filter(fn($v) => $v->stock_health === 'healthy')->sum('cost_value'),
            'low_stock_value' => $allVariants->filter(fn($v) => $v->overal_quantity_at_hand < 10 && $v->overal_quantity_at_hand > 0)->sum('cost_value'),
            'overstock_value' => $allVariants->filter(fn($v) => $v->overal_quantity_at_hand > 100)->sum('cost_value'),
        ];
        
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
     * Stock Movement Report with Clean Pagination Pattern
     */
    public function stockMovement(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get filter parameters
        $categoryId = $request->get('category_id');
        $days = $request->get('days', 30);
        $perPage = (int)$request->get('per_page', 15); // ✅ Add per_page parameter
        
        $startDate = Carbon::now()->subDays($days);
        
        // Get variants created or updated recently
        $query = ProductVariant::with(['product.category', 'unitMeasure'])
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
        
        // ✅ Get ALL variants for calculations (unpaginated for metrics)
        $allVariants = $query->orderBy('updated_at', 'desc')->get();
        
        // Calculate movement metrics for ALL variants
        $allVariants->each(function ($variant) {
            $variant->days_since_update = Carbon::parse($variant->updated_at)->diffInDays(Carbon::now());
            $variant->days_since_creation = Carbon::parse($variant->created_at)->diffInDays(Carbon::now());
            
            // Determine movement status
            if ($variant->days_since_update <= 7) {
                $variant->movement_status = 'recent';
                $variant->movement_label = __('auth.recently_updated');
                $variant->movement_color = 'success';
            } elseif ($variant->days_since_update <= 30) {
                $variant->movement_status = 'active';
                $variant->movement_label = __('auth.active');
                $variant->movement_color = 'primary';
            } else {
                $variant->movement_status = 'stale';
                $variant->movement_label = __('auth.stale');
                $variant->movement_color = 'warning';
            }
        });
        
        // ✅ Apply pagination using your helper method
        $variants = $this->paginateCollection($allVariants, $perPage, 'page');
        
        // Get movement summary from ALL variants (not just paginated)
        $movementSummary = [
            'recent_count' => $allVariants->where('days_since_update', '<=', 7)->count(),
            'active_count' => $allVariants->where('days_since_update', '>', 7)
                ->where('days_since_update', '<=', 30)
                ->count(),
            'stale_count' => $allVariants->where('days_since_update', '>', 30)->count(),
            'new_this_month' => $allVariants->where('days_since_creation', '<=', 30)->count(),
            'updated_this_week' => $allVariants->where('days_since_update', '<=', 7)->count(),
            'total_items' => $allVariants->count(),
        ];
        
        // Get categories for filter
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
            'perPage'
        ));
    }
   
    /**
     * Product Margin Report with Clean Pagination Pattern
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
            ->where('is_active', true)
            ->whereNotNull('cost_price')
            ->where('cost_price', '>', 0);
        
        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        
        // Get ALL variants for calculations (unpaginated)
        $allVariants = $query->get();
        
        // ✅ Calculate margin for each variant (FIXED)
        $allVariants->each(function ($variant) {
            // Ensure we're working with float values
            $price = (float)$variant->price;
            $costPrice = (float)$variant->cost_price;
            
            // Calculate margin amount
            $marginAmount = $price - $costPrice;
            
            // Calculate margin percentage
            $marginPercentage = $price > 0 ? ($marginAmount / $price) * 100 : 0;
            
            // Assign to variant with proper casting
            $variant->margin_amount = $marginAmount;
            $variant->margin_percentage = $marginPercentage;
            
            // For debugging - remove after fixing
            \Log::info('Margin Calculation:', [
                'sku' => $variant->sku,
                'price' => $price,
                'cost_price' => $costPrice,
                'margin_amount' => $marginAmount,
                'margin_percentage' => $marginPercentage
            ]);
            
            // Categorize margin with locale support
            if ($marginPercentage >= 50) {
                $variant->margin_category = 'high';
                $variant->margin_label = __('auth.high_margin');
                $variant->margin_color = 'success';
            } elseif ($marginPercentage >= 30) {
                $variant->margin_category = 'medium';
                $variant->margin_label = __('auth.medium_margin');
                $variant->margin_color = 'primary';
            } elseif ($marginPercentage >= 10) {
                $variant->margin_category = 'low';
                $variant->margin_label = __('auth.low_margin');
                $variant->margin_color = 'warning';
            } else {
                $variant->margin_category = 'very_low';
                $variant->margin_label = __('auth.very_low_margin');
                $variant->margin_color = 'danger';
            }
            
            // Calculate total margin value for inventory
            $quantity = (float)($variant->overal_quantity_at_hand ?? 0);
            $variant->total_margin_value = $marginAmount * $quantity;
        });
        
        // ✅ Apply margin range filter to ALL variants
        if ($minMargin > 0 || $maxMargin < 100) {
            $filteredVariants = $allVariants->filter(function ($variant) use ($minMargin, $maxMargin) {
                return $variant->margin_percentage >= $minMargin && 
                    $variant->margin_percentage <= $maxMargin;
            });
        } else {
            $filteredVariants = $allVariants;
        }
        
        // Sort by margin percentage descending
        $sortedVariants = $filteredVariants->sortByDesc('margin_percentage')->values();
        
        // ✅ Apply pagination using your helper method
        $variants = $this->paginateCollection($sortedVariants, $perPage, 'page');
        
        // Get margin summary from FILTERED variants (not paginated)
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
        
        // Get top 10 products for chart (from filtered, unpaginated data)
        $topMarginProducts = $sortedVariants->take(10);
        
        // Get categories for filter
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