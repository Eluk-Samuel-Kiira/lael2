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

class InventoryStrategyReportController extends Controller
{
    /**
     * Get current tenant ID and check permissions
     */
    private function getTenantId()
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('product reports')) {
            abort(403, __('payments.not_authorized'));
        }
        return $user->tenant_id;
    }

    /**
     * Check if tenant is single shop
     */
    private function isTenantSingleShop($tenantId)
    {
        $locationCount = Location::where('tenant_id', $tenantId)->count();
        return $locationCount <= 1;
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
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        $strategy = $request->get('strategy', 'all');
        $categoryId = $request->get('category_id');
        $search = $request->get('search');
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get Products with their strategies ──────────────────────────
        $query = Product::with(['category', 'variants'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        // Filter by strategy
        if ($strategy && $strategy !== 'all') {
            $query->where('inventory_strategy', $strategy);
        }
        
        // Filter by category
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        // Search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        $products = $query->get();
        
        // ─── Build Strategy Data ──────────────────────────────────────────
        $strategyData = collect();
        
        foreach ($products as $product) {
            $productStrategy = $product->inventory_strategy ?? 'quantity';
            
            // Get stock data based on strategy
            $stockData = $this->getStockDataByStrategy($product, $tenantId, $isSingleShop);
            
            $strategyData->push((object)[
                'product' => $product,
                'product_id' => $product->id,
                'strategy' => $productStrategy,
                'strategy_label' => $this->getStrategyLabel($productStrategy),
                'strategy_color' => $this->getStrategyColor($productStrategy),
                'strategy_icon' => $this->getStrategyIcon($productStrategy),
                'stock_data' => $stockData,
                'variant_count' => $product->variants->count(),
                'total_stock' => $stockData['total_stock'] ?? 0,
                'total_value' => $stockData['total_value'] ?? 0,
                'status' => $stockData['status'] ?? 'unknown',
                'status_color' => $stockData['status_color'] ?? 'secondary',
                'status_label' => $stockData['status_label'] ?? __('pagination.unknown'),
            ]);
        }
        
        // ─── Apply pagination ─────────────────────────────────────────────
        $paginatedData = $this->paginateCollection($strategyData, $perPage, 'page');
        
        // ─── Summary Statistics ───────────────────────────────────────────
        $summary = [
            'total_products' => $products->count(),
            'quantity_strategy' => $products->where('inventory_strategy', 'quantity')->count(),
            'batch_strategy' => $products->where('inventory_strategy', 'batch')->count(),
            'serial_strategy' => $products->where('inventory_strategy', 'serial')->count(),
            'recipe_strategy' => $products->where('inventory_strategy', 'recipe')->count(),
            'total_stock' => $strategyData->sum('total_stock'),
            'total_value' => $strategyData->sum('total_value'),
            'in_stock' => $strategyData->where('status', 'in_stock')->count(),
            'low_stock' => $strategyData->where('status', 'low_stock')->count(),
            'out_of_stock' => $strategyData->where('status', 'out_of_stock')->count(),
        ];
        
        // ─── Get filter options ───────────────────────────────────────────
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $strategies = [
            ['value' => 'all', 'label' => __('pagination.all_strategies')],
            ['value' => 'quantity', 'label' => __('pagination.quantity_tracking')],
            ['value' => 'batch', 'label' => __('pagination.batch_tracking')],
            ['value' => 'serial', 'label' => __('pagination.serial_tracking')],
            ['value' => 'recipe', 'label' => __('pagination.recipe_product')],
        ];
        
        return view('reports.products.strategy', compact(
            'paginatedData',
            'strategyData',
            'summary',
            'categories',
            'strategies',
            'strategy',
            'categoryId',
            'search',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Get detailed view for a specific product
     * ✅ Uses route parameter {productId}
     */
    public function detail($productId)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = $this->isTenantSingleShop($tenantId);
        
        $product = Product::with(['category', 'variants'])
            ->where('tenant_id', $tenantId)
            ->where('id', $productId)
            ->firstOrFail();
        
        $strategy = $product->inventory_strategy ?? 'quantity';
        
        // ─── Get variants with their data based on strategy ──────────────
        $variantsData = collect();
        $totalStock = 0;
        $totalValue = 0;
        
        foreach ($product->variants as $variant) {
            $variantStock = 0;
            $variantValue = 0;
            $details = [];
            
            switch ($strategy) {
                case 'batch':
                    $result = $this->getVariantBatchData($variant, $tenantId);
                    $variantStock = $result['stock'];
                    $variantValue = $result['value'];
                    $details = $result['details'];
                    break;
                    
                case 'serial':
                    $result = $this->getVariantSerialData($variant, $tenantId);
                    $variantStock = $result['stock'];
                    $variantValue = $result['value'];
                    $details = $result['details'];
                    break;
                    
                case 'recipe':
                    // Recipe products don't have physical variants in the same way
                    $result = $this->getRecipeData($product, $tenantId);
                    $variantStock = $result['stock'];
                    $variantValue = $result['value'];
                    $details = $result['details'];
                    break;
                    
                default: // quantity
                    $result = $this->getVariantQuantityData($variant, $tenantId, $isSingleShop);
                    $variantStock = $result['stock'];
                    $variantValue = $result['value'];
                    $details = $result['details'];
                    break;
            }
            
            $totalStock += $variantStock;
            $totalValue += $variantValue;
            
            $variantsData->push([
                'variant' => $variant,
                'variant_id' => $variant->id,
                'variant_name' => $variant->name,
                'variant_sku' => $variant->sku,
                'stock' => $variantStock,
                'value' => $variantValue,
                'cost_price' => $variant->grand_total_cost_price ?? 0,
                'selling_price' => $variant->selling_price ?? 0,
                'details' => $details,
            ]);
        }
        
        // ─── Get overall stock status ─────────────────────────────────────
        $status = $this->getStockStatus($totalStock, $product);
        
        return response()->json([
            'product' => $product,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'strategy' => $strategy,
            'strategy_label' => $this->getStrategyLabel($strategy),
            'strategy_color' => $this->getStrategyColor($strategy),
            'strategy_icon' => $this->getStrategyIcon($strategy),
            'variants' => $variantsData,
            'total_stock' => $totalStock,
            'total_value' => $totalValue,
            'status' => $status['status'],
            'status_color' => $status['color'],
            'status_label' => $status['label'],
            'is_single_shop' => $isSingleShop,
        ]);
    }

    /**
     * Get recipe details for a product
     * ✅ Uses route parameter {productId}
     */
    public function recipeDetail($productId)
    {
        $tenantId = $this->getTenantId();
        
        $product = Product::where('tenant_id', $tenantId)
            ->where('id', $productId)
            ->firstOrFail();
        
        $recipe = Recipe::with(['ingredients.ingredientVariant.product'])
            ->where('product_id', $productId)
            ->first();
        
        if (!$recipe) {
            return response()->json(['error' => 'No recipe found'], 404);
        }
        
        $ingredients = [];
        foreach ($recipe->ingredients as $ingredient) {
            $variant = $ingredient->ingredientVariant;
            $availableStock = $variant ? $variant->overal_quantity_at_hand ?? 0 : 0;
            $quantityRequired = $ingredient->quantity_required ?? 0;
            $producible = $quantityRequired > 0 ? floor($availableStock / $quantityRequired) : 0;
            
            $ingredients[] = [
                'variant_name' => $variant ? $variant->name : 'Unknown',
                'sku' => $variant ? $variant->sku : 'N/A',
                'quantity_required' => $quantityRequired,
                'unit' => $ingredient->unit ? $ingredient->unit->name : 'units',
                'available_stock' => $availableStock,
                'producible' => $producible,
                'cost_price' => $variant ? $variant->grand_total_cost_price ?? 0 : 0,
            ];
        }
        
        return response()->json([
            'product' => $product,
            'recipe' => $recipe,
            'ingredients' => $ingredients,
        ]);
    }

    /**
     * Get stock data based on inventory strategy
     */
    private function getStockDataByStrategy($product, $tenantId, $isSingleShop)
    {
        $strategy = $product->inventory_strategy ?? 'quantity';
        
        switch ($strategy) {
            case 'batch':
                return $this->getBatchStockData($product, $tenantId);
            case 'serial':
                return $this->getSerialStockData($product, $tenantId);
            case 'recipe':
                return $this->getRecipeStockData($product, $tenantId);
            default:
                return $this->getQuantityStockData($product, $tenantId, $isSingleShop);
        }
    }

    /**
     * Get stock data for quantity strategy
     */
    private function getQuantityStockData($product, $tenantId, $isSingleShop)
    {
        $variants = $product->variants;
        $totalStock = 0;
        $totalValue = 0;
        $variantDetails = [];
        
        foreach ($variants as $variant) {
            if ($isSingleShop) {
                $stock = $variant->overal_quantity_at_hand ?? 0;
            } else {
                $stock = InventoryItems::where('variant_id', $variant->id)
                    ->where('tenant_id', $tenantId)
                    ->sum('quantity_allocated');
            }
            
            $costPrice = $variant->grand_total_cost_price ?? 0;
            $value = $stock * $costPrice;
            
            $totalStock += $stock;
            $totalValue += $value;
            
            $variantDetails[] = [
                'variant' => $variant,
                'stock' => $stock,
                'value' => $value,
                'cost_price' => $costPrice,
                'selling_price' => $variant->selling_price ?? 0,
            ];
        }
        
        $status = $this->getStockStatus($totalStock, $product);
        
        return [
            'total_stock' => $totalStock,
            'total_value' => $totalValue,
            'variants' => $variantDetails,
            'status' => $status['status'],
            'status_color' => $status['color'],
            'status_label' => $status['label'],
        ];
    }

    /**
     * Get stock data for batch strategy
     */
    private function getBatchStockData($product, $tenantId)
    {
        $variants = $product->variants;
        $totalStock = 0;
        $totalValue = 0;
        $batchDetails = [];
        
        foreach ($variants as $variant) {
            $batchItems = PurchaseReceiptItem::whereHas('purchaseReceipt.purchaseOrder', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->whereHas('purchaseReceipt', function($q) {
                $q->where('received_at', '<=', now());
            })
            ->where('quantity_remaining', '>', 0)
            ->get()
            ->filter(function($item) use ($variant) {
                return $item->purchaseOrderItem && 
                       $item->purchaseOrderItem->product_variant_id == $variant->id;
            });
            
            $variantStock = 0;
            $variantValue = 0;
            
            foreach ($batchItems as $batch) {
                $quantity = $batch->quantity_remaining ?? 0;
                $costPrice = $variant->grand_total_cost_price ?? 0;
                $value = $quantity * $costPrice;
                
                $variantStock += $quantity;
                $variantValue += $value;
                
                $batchDetails[] = [
                    'variant' => $variant,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $quantity,
                    'expiry_date' => $batch->expiry_date,
                    'value' => $value,
                    'cost_price' => $costPrice,
                ];
            }
            
            $totalStock += $variantStock;
            $totalValue += $variantValue;
        }
        
        $status = $this->getStockStatus($totalStock, $product);
        
        return [
            'total_stock' => $totalStock,
            'total_value' => $totalValue,
            'batches' => $batchDetails,
            'status' => $status['status'],
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
    private function getRecipeStockData($product, $tenantId)
    {
        $recipe = Recipe::where('product_id', $product->id)->first();
        $totalStock = 0;
        $totalValue = 0;
        $ingredientDetails = [];
        $canProduce = true;
        $maxProducible = PHP_INT_MAX;
        
        if ($recipe) {
            $ingredients = RecipeIngredient::where('recipe_id', $recipe->id)
                ->with(['ingredientVariant'])
                ->get();
            
            foreach ($ingredients as $ingredient) {
                $variant = $ingredient->ingredientVariant;
                $quantityRequired = $ingredient->quantity_required ?? 0;
                
                $availableStock = $variant ? $variant->overal_quantity_at_hand ?? 0 : 0;
                $costPrice = $variant ? $variant->grand_total_cost_price ?? 0 : 0;
                
                $producible = $quantityRequired > 0 ? floor($availableStock / $quantityRequired) : 0;
                $maxProducible = min($maxProducible, $producible);
                
                $ingredientDetails[] = [
                    'variant' => $variant,
                    'variant_name' => $variant ? $variant->name : 'Unknown',
                    'quantity_required' => $quantityRequired,
                    'available_stock' => $availableStock,
                    'producible' => $producible,
                    'cost_price' => $costPrice,
                    'total_cost' => $availableStock * $costPrice,
                ];
                
                $totalValue += $availableStock * $costPrice;
            }
            
            $canProduce = $maxProducible > 0;
            $totalStock = $maxProducible;
        }
        
        if ($totalStock == 0) {
            $status = ['status' => 'out_of_stock', 'color' => 'danger', 'label' => __('pagination.out_of_stock')];
        } elseif (!$canProduce) {
            $status = ['status' => 'cannot_produce', 'color' => 'warning', 'label' => __('pagination.cannot_produce')];
        } else {
            $status = ['status' => 'in_stock', 'color' => 'success', 'label' => __('pagination.can_produce')];
        }
        
        return [
            'total_stock' => $totalStock,
            'total_value' => $totalValue,
            'ingredients' => $ingredientDetails,
            'can_produce' => $canProduce,
            'max_producible' => $maxProducible,
            'status' => $status['status'],
            'status_color' => $status['color'],
            'status_label' => $status['label'],
        ];
    }

    /**
     * Get variant data for quantity strategy
     */
    private function getVariantQuantityData($variant, $tenantId, $isSingleShop)
    {
        if ($isSingleShop) {
            $stock = $variant->overal_quantity_at_hand ?? 0;
        } else {
            $stock = InventoryItems::where('variant_id', $variant->id)
                ->where('tenant_id', $tenantId)
                ->sum('quantity_allocated');
        }
        
        $costPrice = $variant->grand_total_cost_price ?? 0;
        $value = $stock * $costPrice;
        
        return [
            'stock' => $stock,
            'value' => $value,
            'details' => [
                'quantity_on_hand' => $stock,
                'cost_price' => $costPrice,
                'selling_price' => $variant->selling_price ?? 0,
                'value' => $value,
            ]
        ];
    }

    /**
     * Get variant data for batch strategy
     */
    private function getVariantBatchData($variant, $tenantId)
    {
        $totalStock = 0;
        $totalValue = 0;
        $batchDetails = [];
        
        $batchItems = PurchaseReceiptItem::whereHas('purchaseReceipt.purchaseOrder', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })
        ->whereHas('purchaseReceipt', function($q) {
            $q->where('received_at', '<=', now());
        })
        ->where('quantity_remaining', '>', 0)
        ->get()
        ->filter(function($item) use ($variant) {
            return $item->purchaseOrderItem && 
                   $item->purchaseOrderItem->product_variant_id == $variant->id;
        });
        
        foreach ($batchItems as $batch) {
            $quantity = $batch->quantity_remaining ?? 0;
            $costPrice = $variant->grand_total_cost_price ?? 0;
            $value = $quantity * $costPrice;
            
            $totalStock += $quantity;
            $totalValue += $value;
            
            $batchDetails[] = [
                'batch_number' => $batch->batch_number,
                'quantity' => $quantity,
                'expiry_date' => $batch->expiry_date,
                'value' => $value,
                'cost_price' => $costPrice,
            ];
        }
        
        return [
            'stock' => $totalStock,
            'value' => $totalValue,
            'details' => $batchDetails,
        ];
    }

    /**
     * Get variant data for serial strategy
     */
    private function getVariantSerialData($variant, $tenantId)
    {
        $serials = SerialNumber::where('variant_id', $variant->id)
            ->where('tenant_id', $tenantId)
            ->where('status', SerialNumber::STATUS_AVAILABLE)
            ->get();
        
        $stock = $serials->count();
        $costPrice = $variant->grand_total_cost_price ?? 0;
        $value = $stock * $costPrice;
        
        $serialDetails = [];
        foreach ($serials as $serial) {
            $serialDetails[] = [
                'serial_number' => $serial->serial_number,
                'location' => $serial->location ? $serial->location->name : 'N/A',
                'expiry_date' => $serial->expiry_date,
                'value' => $costPrice,
            ];
        }
        
        return [
            'stock' => $stock,
            'value' => $value,
            'details' => $serialDetails,
        ];
    }

    /**
     * Get recipe data
     */
    private function getRecipeData($product, $tenantId)
    {
        $recipe = Recipe::where('product_id', $product->id)->first();
        $ingredientDetails = [];
        $totalValue = 0;
        $maxProducible = PHP_INT_MAX;
        $canProduce = true;
        
        if ($recipe) {
            $ingredients = RecipeIngredient::where('recipe_id', $recipe->id)
                ->with(['ingredientVariant'])
                ->get();
            
            foreach ($ingredients as $ingredient) {
                $variant = $ingredient->ingredientVariant;
                $quantityRequired = $ingredient->quantity_required ?? 0;
                
                $availableStock = $variant ? $variant->overal_quantity_at_hand ?? 0 : 0;
                $costPrice = $variant ? $variant->grand_total_cost_price ?? 0 : 0;
                
                $producible = $quantityRequired > 0 ? floor($availableStock / $quantityRequired) : 0;
                $maxProducible = min($maxProducible, $producible);
                
                $ingredientDetails[] = [
                    'ingredient_name' => $variant ? $variant->name : 'Unknown',
                    'ingredient_sku' => $variant ? $variant->sku : 'N/A',
                    'quantity_required' => $quantityRequired,
                    'available_stock' => $availableStock,
                    'producible' => $producible,
                    'cost_price' => $costPrice,
                    'total_cost' => $availableStock * $costPrice,
                ];
                
                $totalValue += $availableStock * $costPrice;
            }
            
            $canProduce = $maxProducible > 0;
        }
        
        return [
            'stock' => $canProduce ? $maxProducible : 0,
            'value' => $totalValue,
            'details' => [
                'ingredients' => $ingredientDetails,
                'can_produce' => $canProduce,
                'max_producible' => $maxProducible,
            ],
        ];
    }

    /**
     * Get stock status based on quantity
     */
    private function getStockStatus($quantity, $product)
    {
        if ($quantity <= 0) {
            return [
                'status' => 'out_of_stock', 
                'color' => 'danger', 
                'label' => __('pagination.out_of_stock')
            ];
        }
        
        $lowStockThreshold = 5;
        if ($product->variants->isNotEmpty()) {
            $lowStockThreshold = $product->variants->first()->low_stock_level ?? 5;
        }
        
        if ($quantity <= $lowStockThreshold) {
            return [
                'status' => 'low_stock', 
                'color' => 'warning', 
                'label' => __('pagination.low_stock')
            ];
        }
        
        return [
            'status' => 'in_stock', 
            'color' => 'success', 
            'label' => __('pagination.in_stock')
        ];
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