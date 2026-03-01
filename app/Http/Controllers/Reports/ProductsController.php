<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        
        // Build base query
        $query = Product::with(['category', 'variants'])
            ->where('tenant_id', $tenantId);
        
        // Apply filters
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        if ($productType && $productType !== 'all') {
            $query->where('type', $productType);
        }
        
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }
        
        if ($isTaxable !== null) {
            $query->where('is_taxable', $isTaxable);
        }
        
        // Get products with pagination
        $products = $query->paginate(20)->withQueryString();
        
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
            ->get();
        
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
            'isTaxable'
        ));
    }

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
                return $category->product_count > 0; // Only show categories with products
            })
            ->values(); // Reset keys
    }

    private function getTypeBreakdown($tenantId)
    {
        $breakdown = Product::select('type', DB::raw('COUNT(*) as count'))
            ->where('tenant_id', $tenantId)
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();
        
        // Ensure all product types are represented even with 0 count
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
     * Product Performance Report
     */
    public function performance(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get filter parameters
        $categoryId = $request->get('category_id');
        $productType = $request->get('product_type');
        
        // Get products with variants
        $query = Product::with(['variants', 'category'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        if ($productType && $productType !== 'all') {
            $query->where('type', $productType);
        }
        
        $products = $query->paginate(20)->withQueryString();
        
        // Calculate performance metrics for each product
        $products->each(function ($product) {
            $product->total_stock = $product->variants->sum('overal_quantity_at_hand');
            $product->total_cost_value = $product->variants->sum(function ($variant) {
                return $variant->cost_price * $variant->overal_quantity_at_hand;
            });
            $product->total_revenue_value = $product->variants->sum(function ($variant) {
                return $variant->price * $variant->overal_quantity_at_hand;
            });
            $product->total_margin = $product->total_revenue_value - $product->total_cost_value;
            $product->margin_percentage = $product->total_revenue_value > 0 
                ? ($product->total_margin / $product->total_revenue_value) * 100 
                : 0;
        });
        
        // Sort by margin percentage (descending)
        $sortedProducts = $products->sortByDesc('margin_percentage')->values();
        
        // Get categories for filter
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.products.performance', compact(
            'products',
            'sortedProducts',
            'categories',
            'categoryId',
            'productType'
        ));
    }
    
   
   /**
     * Inventory Valuation Report (Simplified - No Pagination)
     */
    public function inventory(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        // Get filter parameters
        $categoryId = $request->get('category_id');
        $stockStatus = $request->get('stock_status');
        
        // Get ALL product variants with stock (no pagination)
        $query = ProductVariant::with(['product.category', 'unitMeasure'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        
        // Apply stock status filters
        if ($stockStatus === 'low') {
            $query->where('overal_quantity_at_hand', '<', 10);
        } elseif ($stockStatus === 'out') {
            $query->where('overal_quantity_at_hand', '=', 0);
        } elseif ($stockStatus === 'overstock') {
            $query->where('overal_quantity_at_hand', '>', 100);
        }
        
        // Get ALL variants (no pagination)
        $variants = $query->get();
        
        // Calculate metrics for each variant
        $variants->transform(function ($variant) {
            $variant->cost_value = ($variant->cost_price ?? 0) * $variant->overal_quantity_at_hand;
            $variant->revenue_value = $variant->price * $variant->overal_quantity_at_hand;
            $variant->stock_value = $variant->cost_value;
            $variant->potential_profit = $variant->revenue_value - $variant->cost_value;
            $variant->margin_percentage = $variant->price > 0 
                ? (($variant->price - ($variant->cost_price ?? 0)) / $variant->price) * 100 
                : 0;
            
            // Determine stock health
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
        
        // Sort by stock value (highest first)
        $sortedVariants = $variants->sortByDesc('stock_value')->values();
        
        // Get total valuation
        $totalValuation = [
            'total_cost_value' => $variants->sum('cost_value'),
            'total_revenue_value' => $variants->sum('revenue_value'),
            'total_potential_profit' => $variants->sum('potential_profit'),
            'total_items' => $variants->count(),
            'total_quantity' => $variants->sum('overal_quantity_at_hand'),
            'low_stock_count' => $variants->where('overal_quantity_at_hand', '<', 10)
                ->where('overal_quantity_at_hand', '>', 0)
                ->count(),
            'out_of_stock_count' => $variants->where('overal_quantity_at_hand', 0)->count(),
            'overstock_count' => $variants->where('overal_quantity_at_hand', '>', 100)->count(),
            'healthy_count' => $variants->where('stock_health', 'healthy')->count(),
        ];
        
        // Get categories for filter
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.products.inventory', compact(
            'variants',
            'sortedVariants',
            'totalValuation',
            'categories',
            'categoryId',
            'stockStatus'
        ));
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
        
        // Get filter parameters
        $categoryId = $request->get('category_id');
        $days = $request->get('days', 30);
        
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
        
        $variants = $query->orderBy('updated_at', 'desc')
            ->paginate(20)
            ->withQueryString();
        
        // Calculate movement metrics
        $variants->each(function ($variant) {
            $variant->days_since_update = Carbon::parse($variant->updated_at)->diffInDays(Carbon::now());
            $variant->days_since_creation = Carbon::parse($variant->created_at)->diffInDays(Carbon::now());
            
            // Determine movement status
            if ($variant->days_since_update <= 7) {
                $variant->movement_status = 'recent';
                $variant->movement_label = __('auth.recently_updated');
            } elseif ($variant->days_since_update <= 30) {
                $variant->movement_status = 'active';
                $variant->movement_label = __('auth.active');
            } else {
                $variant->movement_status = 'stale';
                $variant->movement_label = __('auth.stale');
            }
        });
        
        // Get movement summary
        $movementSummary = [
            'recent_count' => $variants->where('days_since_update', '<=', 7)->count(),
            'active_count' => $variants->where('days_since_update', '>', 7)
                ->where('days_since_update', '<=', 30)
                ->count(),
            'stale_count' => $variants->where('days_since_update', '>', 30)->count(),
            'new_this_month' => $variants->where('days_since_creation', '<=', 30)->count(),
            'updated_this_week' => $variants->where('days_since_update', '<=', 7)->count(),
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
            'days'
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
        $minMargin = $request->get('min_margin', 0);
        $maxMargin = $request->get('max_margin', 100);
        
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
        
        $variants = $query->paginate(20)->withQueryString();
        
        // Calculate margin for each variant
        $variants->each(function ($variant) {
            $variant->margin_amount = $variant->price - $variant->cost_price;
            $variant->margin_percentage = $variant->price > 0 
                ? ($variant->margin_amount / $variant->price) * 100 
                : 0;
            
            // Categorize margin with locale support
            if ($variant->margin_percentage >= 50) {
                $variant->margin_category = 'high';
                $variant->margin_label = __('auth.high_margin');
            } elseif ($variant->margin_percentage >= 30) {
                $variant->margin_category = 'medium';
                $variant->margin_label = __('auth.medium_margin');
            } elseif ($variant->margin_percentage >= 10) {
                $variant->margin_category = 'low';
                $variant->margin_label = __('auth.low_margin');
            } else {
                $variant->margin_category = 'very_low';
                $variant->margin_label = __('auth.very_low_margin');
            }
            
        });
        
        // Apply margin range filter
        if ($minMargin > 0 || $maxMargin < 100) {
            $variants = $variants->filter(function ($variant) use ($minMargin, $maxMargin) {
                return $variant->margin_percentage >= $minMargin && 
                       $variant->margin_percentage <= $maxMargin;
            })->values();
        }
        
        // Get margin summary
        $marginSummary = [
            'total_variants' => $variants->count(),
            'average_margin' => $variants->avg('margin_percentage'),
            'total_margin_value' => $variants->sum(function ($variant) {
                return $variant->margin_amount * $variant->overal_quantity_at_hand;
            }),
            'high_margin_count' => $variants->where('margin_percentage', '>=', 50)->count(),
            'medium_margin_count' => $variants->where('margin_percentage', '>=', 30)
                ->where('margin_percentage', '<', 50)
                ->count(),
            'low_margin_count' => $variants->where('margin_percentage', '>=', 10)
                ->where('margin_percentage', '<', 30)
                ->count(),
            'very_low_margin_count' => $variants->where('margin_percentage', '<', 10)->count(),
        ];
        
        // Get categories for filter
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.products.margin', compact(
            'variants',
            'marginSummary',
            'categories',
            'categoryId',
            'minMargin',
            'maxMargin'
        ));
    }
    
    /**
     * By Category Report
     */
    public function byCategory(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
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
                    return $variant->cost_price * $variant->overal_quantity_at_hand;
                });
            });
            // CORRECTED: Access variants through the product relationship
            $category->total_revenue_value = $category->products->sum(function ($product) {
                return $product->variants->sum(function ($variant) {
                    return $variant->price * $variant->overal_quantity_at_hand;
                });
            });
            $category->total_margin = $category->total_revenue_value - $category->total_cost_value;
            $category->margin_percentage = $category->total_revenue_value > 0 
                ? ($category->total_margin / $category->total_revenue_value) * 100 
                : 0;
        });
        
        // Sort by product count (descending)
        $sortedCategories = $categories->sortByDesc('product_count')->values();
        
        // Get category summary
        $categorySummary = [
            'total_categories' => $categories->count(),
            'total_products' => $categories->sum('product_count'),
            'total_variants' => $categories->sum('variant_count'),
            'total_stock' => $categories->sum('total_stock'),
            'total_value' => $categories->sum('total_revenue_value'),
            'average_margin' => $categories->avg('margin_percentage'),
        ];
        
        return view('reports.products.by-category', compact(
            'categories',
            'sortedCategories',
            'categorySummary'
        ));
    }
    
    /**
     * Helper Methods
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
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['is_taxable'])) {
            $query->where('is_taxable', $filters['is_taxable']);
        }
        
        $totalProducts = $query->count();
        $activeProducts = $query->where('is_active', true)->count();
        $taxableProducts = $query->where('is_taxable', true)->count();
        
        // Get variant counts
        $variantQuery = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        if (!empty($filters['category_id'])) {
            $variantQuery->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        
        $totalVariants = $variantQuery->count();
        $totalStock = $variantQuery->sum('overal_quantity_at_hand');
        
        // Get average price and cost
        $avgPrice = $variantQuery->avg('price');
        $avgCost = $variantQuery->avg('cost_price');
        
        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'inactive_products' => $totalProducts - $activeProducts,
            'taxable_products' => $taxableProducts,
            'non_taxable_products' => $totalProducts - $taxableProducts,
            'total_variants' => $totalVariants,
            'total_stock' => $totalStock,
            'average_price' => $avgPrice ?: 0,
            'average_cost' => $avgCost ?: 0,
            'physical_count' => $query->where('type', 'physical')->count(),
            'digital_count' => $query->where('type', 'digital')->count(),
            'service_count' => $query->where('type', 'service')->count(),
            'composite_count' => $query->where('type', 'composite')->count(),
        ];
    }
    

    
    
    private function getStatusBreakdown($tenantId)
    {
        return Product::select(
                DB::raw("CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END as status"),
                DB::raw('COUNT(*) as count')
            )
            ->where('tenant_id', $tenantId)
            ->groupBy('is_active')
            ->get();
    }
    
    private function getTaxStatusBreakdown($tenantId)
    {
        return Product::select(
                DB::raw("CASE WHEN is_taxable = 1 THEN 'Taxable' ELSE 'Non-Taxable' END as tax_status"),
                DB::raw('COUNT(*) as count')
            )
            ->where('tenant_id', $tenantId)
            ->groupBy('is_taxable')
            ->get();
    }
}