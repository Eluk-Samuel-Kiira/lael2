<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\ProductVariant;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\InventoryAdjustments;
use App\Models\Department;
use App\Models\Location;
use App\Models\SingleShopInventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryReportsController extends Controller
{
    /**
     * Get current tenant ID
     */
    private function getTenantId()
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('inventory reports')) {
            abort(403, __('payments.not_authorized'));
        }
        return $tenantId;
    }
    
    /**
     * Inventory Summary Report
     */
    public function summary(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        // Filter parameters
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $isActive = $request->get('is_active', 'all');
        
        // Query for inventory items
        $query = InventoryItems::with(['variant', 'departmentItem', 'itemLocation'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Apply filters
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($variantId) {
            $query->where('variant_id', $variantId);
        }
        
        if ($isActive !== 'all') {
            $query->whereHas('variant', function($q) use ($isActive) {
                $q->where('is_active', $isActive);
            });
        }
        
        $inventoryItems = $query->paginate(25);
        
        // Summary statistics
        $summary = [
            'total_items' => $query->count(),
            'total_quantity' => $query->sum('quantity_on_hand'),
            'total_value' => $this->calculateInventoryValue($query),
            'average_stock_level' => $query->avg('quantity_on_hand'),
            'items_below_reorder' => $query->whereColumn('quantity_on_hand', '<', 'reorder_point')->count(),
            'out_of_stock' => $query->where('quantity_on_hand', 0)->count(),
        ];
        
        // Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.summary', compact(
            'inventoryItems',
            'summary',
            'variants',
            'departments',
            'locations',
            'startDate',
            'endDate',
            'departmentId',
            'locationId',
            'variantId',
            'isActive'
        ));
    }
    
    /**
     * Inventory Turnover Report
     */
    public function turnover(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $variantId = $request->get('variant_id');
        
        // Get turnover data using inventory logs
        $turnoverQuery = SingleShopInventoryLog::with(['variant.product'])
            ->select([
                'variant_id',
                DB::raw('SUM(ABS(quantity_change)) as total_movement'),
                DB::raw('AVG(quantity_before) as avg_stock_level'),
                DB::raw('COUNT(*) as transaction_count')
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('variant_id');
        
        if ($variantId) {
            $turnoverQuery->where('variant_id', $variantId);
        }
        
        $turnoverData = $turnoverQuery->paginate(25);
        
        // Calculate turnover rates
        $turnoverData->each(function ($item) use ($startDate, $endDate) {
            $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $avgStock = $item->avg_stock_level ?: 1;
            $item->turnover_rate = $item->total_movement / $avgStock;
            $item->days_inventory_held = $days / ($item->turnover_rate ?: 1);
        });
        
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->get();
        
        return view('reports.inventory.turnover', compact(
            'turnoverData',
            'variants',
            'startDate',
            'endDate',
            'variantId'
        ));
    }
    
    /**
     * Stock Aging Report
     */
    public function stockAging(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $category = $request->get('category');
        
        // Get stock aging data from inventory items with batch/expiry info
        $query = InventoryItems::with(['variant', 'departmentItem', 'itemLocation'])
            ->where('tenant_id', $tenantId)
            ->where('quantity_on_hand', '>', 0)
            ->whereNotNull('batch_number')
            ->whereNotNull('expiry_date');
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        $agingItems = $query->orderBy('expiry_date')->paginate(25);
        
        // Categorize by age
        $agingCategories = [
            'expired' => 0,
            '1_week' => 0,
            '1_month' => 0,
            '3_months' => 0,
            '6_months' => 0,
            'over_6_months' => 0,
        ];
        
        $agingItems->each(function ($item) use (&$agingCategories) {
            $daysToExpiry = Carbon::parse($item->expiry_date)->diffInDays(now(), false) * -1;
            
            if ($daysToExpiry < 0) {
                $agingCategories['expired'] += $item->quantity_on_hand;
            } elseif ($daysToExpiry <= 7) {
                $agingCategories['1_week'] += $item->quantity_on_hand;
            } elseif ($daysToExpiry <= 30) {
                $agingCategories['1_month'] += $item->quantity_on_hand;
            } elseif ($daysToExpiry <= 90) {
                $agingCategories['3_months'] += $item->quantity_on_hand;
            } elseif ($daysToExpiry <= 180) {
                $agingCategories['6_months'] += $item->quantity_on_hand;
            } else {
                $agingCategories['over_6_months'] += $item->quantity_on_hand;
            }
        });
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.stock-aging', compact(
            'agingItems',
            'agingCategories',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'category'
        ));
    }
    
    /**
     * Low Stock Alerts Report
     */
    public function lowStockAlerts(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $severity = $request->get('severity', 'all'); // critical, warning, normal
        
        // Query for items below reorder point
        $query = InventoryItems::with(['variant', 'departmentItem', 'itemLocation'])
            ->where('tenant_id', $tenantId)
            ->where('quantity_on_hand', '>', 0)
            ->where(function($q) {
                $q->whereColumn('quantity_on_hand', '<=', 'reorder_point')
                  ->orWhereColumn('quantity_on_hand', '<=', DB::raw('preferred_stock_level * 0.5'));
            });
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        // Apply severity filter
        if ($severity === 'critical') {
            $query->where('quantity_on_hand', '<=', DB::raw('reorder_point * 0.5'));
        } elseif ($severity === 'warning') {
            $query->whereBetween('quantity_on_hand', [
                DB::raw('reorder_point * 0.5 + 1'),
                DB::raw('reorder_point')
            ]);
        }
        
        $lowStockItems = $query->orderBy('quantity_on_hand')->paginate(25);
        
        // Calculate summary
        $summary = [
            'critical' => $query->clone()->where('quantity_on_hand', '<=', DB::raw('reorder_point * 0.5'))->count(),
            'warning' => $query->clone()->whereBetween('quantity_on_hand', [
                DB::raw('reorder_point * 0.5 + 1'),
                DB::raw('reorder_point')
            ])->count(),
            'total_items' => $lowStockItems->total(),
            'total_value_at_risk' => $this->calculateLowStockValue($query),
        ];
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.low-stock-alerts', compact(
            'lowStockItems',
            'summary',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'severity'
        ));
    }
    
    /**
     * Inventory Transactions Report
     */
    public function transactions(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $type = $request->get('type', 'all');
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        
        // Query inventory transactions
        $query = InventoryTransactions::with([
            'InventoryItems.variant',
            'InventoryItems.departmentItem',
            'InventoryItems.itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($type !== 'all') {
            $query->where('type', $type);
        }
        
        if ($departmentId) {
            $query->whereHas('InventoryItems', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        
        if ($locationId) {
            $query->whereHas('InventoryItems', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->paginate(25);
        
        // Summary by type
        $typeSummary = InventoryTransactions::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('type')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.transactions', compact(
            'transactions',
            'typeSummary',
            'departments',
            'locations',
            'startDate',
            'endDate',
            'type',
            'departmentId',
            'locationId'
        ));
    }
    
    /**
     * Inventory Adjustments Report
     */
    public function adjustments(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        
        // Query inventory adjustments
        $query = InventoryAdjustments::with([
            'InventoryItems.variant',
            'InventoryItems.departmentItem',
            'InventoryItems.itemLocation',
            'createdBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($departmentId) {
            $query->whereHas('InventoryItems', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        
        if ($locationId) {
            $query->whereHas('InventoryItems', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }
        
        $adjustments = $query->orderBy('created_at', 'desc')->paginate(25);
        
        // Summary statistics
        $summary = [
            'total_adjustments' => $query->count(),
            'total_quantity_changed' => $query->sum(DB::raw('ABS(quantity_after - quantity_before)')),
            'net_change' => $query->sum(DB::raw('quantity_after - quantity_before')),
            'increase_count' => $query->clone()->whereColumn('quantity_after', '>', 'quantity_before')->count(),
            'decrease_count' => $query->clone()->whereColumn('quantity_after', '<', 'quantity_before')->count(),
        ];
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.adjustments', compact(
            'adjustments',
            'summary',
            'departments',
            'locations',
            'startDate',
            'endDate',
            'departmentId',
            'locationId'
        ));
    }
    
    /**
     * ABC Analysis Report
     */
    public function abcAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        
        // Get inventory value and movement data
        $query = InventoryItems::with(['variant.product', 'departmentItem'])
            ->select([
                'inventory_items.*',
                DB::raw('(inventory_items.quantity_on_hand * product_variants.cost_price) as inventory_value'),
                DB::raw('COALESCE((
                    SELECT SUM(ABS(quantity_change))
                    FROM single_shop_inventory_logs
                    WHERE single_shop_inventory_logs.variant_id = inventory_items.variant_id
                    AND single_shop_inventory_logs.created_at BETWEEN ? AND ?
                ), 0) as total_movement'),
            ])
            ->join('product_variants', 'inventory_items.variant_id', '=', 'product_variants.id')
            ->where('inventory_items.tenant_id', $tenantId)
            ->where('inventory_items.quantity_on_hand', '>', 0)
            ->addBinding([$startDate . ' 00:00:00', $endDate . ' 23:59:59'], 'select');
        
        if ($departmentId) {
            $query->where('inventory_items.department_id', $departmentId);
        }
        
        $items = $query->get();
        
        // Calculate total inventory value
        $totalValue = $items->sum('inventory_value');
        
        // Sort by inventory value and calculate cumulative percentage
        $sortedItems = $items->sortByDesc('inventory_value');
        $cumulativePercentage = 0;
        
        $abcCategories = [
            'A' => ['items' => [], 'percentage' => 0, 'value' => 0],
            'B' => ['items' => [], 'percentage' => 0, 'value' => 0],
            'C' => ['items' => [], 'percentage' => 0, 'value' => 0],
        ];
        
        foreach ($sortedItems as $item) {
            $percentage = ($item->inventory_value / $totalValue) * 100;
            $cumulativePercentage += $percentage;
            
            if ($cumulativePercentage <= 80) {
                $category = 'A';
            } elseif ($cumulativePercentage <= 95) {
                $category = 'B';
            } else {
                $category = 'C';
            }
            
            $item->abc_category = $category;
            $item->value_percentage = $percentage;
            $item->cumulative_percentage = $cumulativePercentage;
            
            $abcCategories[$category]['items'][] = $item;
            $abcCategories[$category]['value'] += $item->inventory_value;
        }
        
        // Calculate percentages for each category
        foreach ($abcCategories as $category => $data) {
            $abcCategories[$category]['percentage'] = count($data['items']) > 0 
                ? ($data['value'] / $totalValue) * 100 
                : 0;
            $abcCategories[$category]['count'] = count($data['items']);
        }
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.abc-analysis', compact(
            'abcCategories',
            'totalValue',
            'departments',
            'startDate',
            'endDate',
            'departmentId'
        ));
    }
    
    /**
     * Movement Analysis Report (Simplified - Single Shop)
     */
    public function movementAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $movementType = $request->get('movement_type', 'all');
        
        // Get movement data from inventory logs
        $query = SingleShopInventoryLog::with(['variant.product'])
            ->select([
                'variant_id',
                DB::raw('SUM(ABS(quantity_change)) as total_movement'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('MIN(created_at) as first_movement'),
                DB::raw('MAX(created_at) as last_movement'),
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('variant_id');
        
        $movementData = $query->paginate(25);
        
        // Calculate movement categories
        $movementStats = [
            'fast_moving' => 0,
            'slow_moving' => 0,
            'non_moving' => 0,
        ];
        
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        
        $movementData->each(function ($item) use ($daysInPeriod, &$movementStats) {
            $avgDailyMovement = $item->total_movement / $daysInPeriod;
            
            if ($avgDailyMovement >= 10) {
                $item->movement_category = 'fast';
                $movementStats['fast_moving']++;
            } elseif ($avgDailyMovement >= 1) {
                $item->movement_category = 'slow';
                $movementStats['slow_moving']++;
            } else {
                $item->movement_category = 'non-moving';
                $movementStats['non_moving']++;
            }
            
            $item->avg_daily_movement = $avgDailyMovement;
            $item->days_since_last_movement = Carbon::parse($item->last_movement)->diffInDays(now());
        });
        
        // Filter by movement type if specified
        if ($movementType !== 'all') {
            $movementData = $movementData->filter(function ($item) use ($movementType) {
                return $item->movement_category === $movementType;
            });
        }
        
        return view('reports.inventory.movement-analysis', compact(
            'movementData',
            'movementStats',
            'startDate',
            'endDate',
            'movementType'
        ));
    }
     
    
    public function getMovementLogs(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        // Use request dates or default to last 90 days
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        $query = SingleShopInventoryLog::where('tenant_id', $tenantId)
            ->where('variant_id', $request->variant_id);
        
        // Apply date filter
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59'
            ]);
        }
        
        $logs = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'logs' => $logs
        ]);
    }

    /**
     * Helper method to calculate inventory value
     */
    private function calculateInventoryValue($query)
    {
        return $query->with(['variant'])
            ->get()
            ->sum(function ($item) {
                return $item->quantity_on_hand * ($item->variant->cost_price ?? 0);
            });
    }
    
    /**
     * Helper method to calculate low stock value at risk
     */
    private function calculateLowStockValue($query)
    {
        return $query->with(['variant'])
            ->get()
            ->sum(function ($item) {
                $shortage = ($item->reorder_point ?: $item->preferred_stock_level * 0.5) - $item->quantity_on_hand;
                return max(0, $shortage) * ($item->variant->cost_price ?? 0);
            });
    }

    /**
     * Inventory Valuation Report
     */
    public function valuation(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $valuationMethod = $request->get('valuation_method', 'fifo'); // fifo, lifo, weighted_average, cost
        
        // Get inventory items with valuation data
        $query = InventoryItems::with(['variant.product', 'departmentItem', 'itemLocation'])
            ->where('tenant_id', $tenantId)
            ->where('quantity_on_hand', '>', 0);
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        $inventoryItems = $query->paginate(25);
        
        // Calculate valuation based on method
        $valuationSummary = [
            'total_items' => $inventoryItems->count(),
            'total_quantity' => $inventoryItems->sum('quantity_on_hand'),
            'total_value' => 0,
            'avg_unit_cost' => 0,
            'avg_unit_price' => 0,
            'potential_profit' => 0,
        ];
        
        $inventoryItems->each(function ($item) use (&$valuationSummary, $valuationMethod) {
            $costPrice = $item->variant->cost_price ?? 0;
            $sellingPrice = $item->variant->price ?? 0;
            $quantity = $item->quantity_on_hand;
            
            // Calculate item value based on valuation method
            switch ($valuationMethod) {
                case 'fifo':
                    // For FIFO, use oldest batch cost if available
                    $itemValue = $quantity * $costPrice;
                    break;
                case 'lifo':
                    // For LIFO, use latest batch cost if available
                    $itemValue = $quantity * $costPrice;
                    break;
                case 'weighted_average':
                    // Weighted average cost
                    $itemValue = $quantity * $costPrice;
                    break;
                case 'cost':
                default:
                    $itemValue = $quantity * $costPrice;
                    break;
            }
            
            $item->valuation_value = $itemValue;
            $item->potential_profit = $quantity * ($sellingPrice - $costPrice);
            
            $valuationSummary['total_value'] += $itemValue;
            $valuationSummary['potential_profit'] += $item->potential_profit;
        });
        
        if ($valuationSummary['total_quantity'] > 0) {
            $valuationSummary['avg_unit_cost'] = $valuationSummary['total_value'] / $valuationSummary['total_quantity'];
        }
        
        // **FIX 1: Calculate value by department - using departmentItem relationship**
        $valueByDepartment = $inventoryItems->groupBy(function ($item) {
            return $item->departmentItem->name ?? 'Unspecified';
        })->map(function ($items) {
            return $items->sum('valuation_value');
        });
        
        // **FIX 2: Calculate value by location - using itemLocation relationship**
        $valueByLocation = $inventoryItems->groupBy(function ($item) {
            return $item->itemLocation->name ?? 'Unspecified';
        })->map(function ($items) {
            return $items->sum('valuation_value');
        });
        
        // **FIX 3: Or group by department_id and location_id first, then get names**
        // This is more efficient
        $valueByDepartment = $inventoryItems->groupBy('department_id')->map(function ($items, $deptId) {
            $department = $items->first()->departmentItem ?? null;
            $departmentName = $department ? $department->name : 'Unspecified';
            return [
                'name' => $departmentName,
                'value' => $items->sum('valuation_value')
            ];
        });
        
        $valueByLocation = $inventoryItems->groupBy('location_id')->map(function ($items, $locId) {
            $location = $items->first()->itemLocation ?? null;
            $locationName = $location ? $location->name : 'Unspecified';
            return [
                'name' => $locationName,
                'value' => $items->sum('valuation_value')
            ];
        });
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.valuation', compact(
            'inventoryItems',
            'valuationSummary',
            'valueByDepartment',
            'valueByLocation',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'valuationMethod'
        ));
    }

    /**
     * Dead Stock Report
     */
    public function deadStock(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $daysThreshold = $request->get('days_threshold', 180); // Items not sold in X days
        $includeExpired = $request->get('include_expired', true);
        
        // Get dead stock items (no movement for specified days)
        $query = InventoryItems::with(['variant.product', 'departmentItem', 'itemLocation'])
            ->where('inventory_items.tenant_id', $tenantId)
            ->where('inventory_items.quantity_on_hand', '>', 0)
            ->select([
                'inventory_items.*',
                DB::raw('COALESCE((
                    SELECT MAX(created_at)
                    FROM single_shop_inventory_logs
                    WHERE single_shop_inventory_logs.variant_id = inventory_items.variant_id
                    AND single_shop_inventory_logs.tenant_id = inventory_items.tenant_id
                ), inventory_items.created_at) as last_movement_date'),
                DB::raw('COALESCE((
                    SELECT SUM(ABS(quantity_change))
                    FROM single_shop_inventory_logs
                    WHERE single_shop_inventory_logs.variant_id = inventory_items.variant_id
                    AND single_shop_inventory_logs.tenant_id = inventory_items.tenant_id
                ), 0) as total_movement'),
            ])
            ->havingRaw('DATEDIFF(NOW(), last_movement_date) >= ?', [$daysThreshold]);
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($includeExpired) {
            $query->orWhere(function ($q) {
                $q->whereNotNull('expiry_date')
                 ->where('expiry_date', '<', now());
            });
        }
        
        $deadStockItems = $query->orderByDesc(DB::raw('DATEDIFF(NOW(), last_movement_date)'))->paginate(25);
        
        // Calculate dead stock summary
        $summary = [
            'total_items' => $deadStockItems->total(),
            'total_quantity' => $deadStockItems->sum('quantity_on_hand'),
            'total_value' => $this->calculateDeadStockValue($deadStockItems),
            'avg_days_idle' => $deadStockItems->avg(function ($item) {
                return \Carbon\Carbon::parse($item->last_movement_date)->diffInDays(now());
            }),
            'expired_items' => $deadStockItems->where('expiry_date', '<', now())->count(),
        ];
        
        // Categorize by idle time
        $idleCategories = [
            '180_365' => 0, // 6-12 months
            '365_730' => 0, // 1-2 years
            'over_730' => 0, // Over 2 years
        ];
        
        $deadStockItems->each(function ($item) use (&$idleCategories) {
            $daysIdle = \Carbon\Carbon::parse($item->last_movement_date)->diffInDays(now());
            
            if ($daysIdle >= 180 && $daysIdle < 365) {
                $idleCategories['180_365'] += $item->quantity_on_hand;
            } elseif ($daysIdle >= 365 && $daysIdle < 730) {
                $idleCategories['365_730'] += $item->quantity_on_hand;
            } elseif ($daysIdle >= 730) {
                $idleCategories['over_730'] += $item->quantity_on_hand;
            }
        });
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.dead-stock', compact(
            'deadStockItems',
            'summary',
            'idleCategories',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'daysThreshold',
            'includeExpired'
        ));
    }

        
    /**
     * Excess Stock Report
     */
    public function excessStock(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $excessThreshold = $request->get('excess_threshold', 1.5); // 150% of preferred stock
        
        // Get excess stock items (stock > threshold * preferred stock)
        $query = InventoryItems::with(['variant.product', 'departmentItem', 'itemLocation'])
            ->where('tenant_id', $tenantId)
            ->where('quantity_on_hand', '>', 0)
            ->where('preferred_stock_level', '>', 0)
            ->whereColumn('quantity_on_hand', '>', DB::raw('preferred_stock_level * ' . $excessThreshold));
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        $excessStockItems = $query->orderByDesc(DB::raw('quantity_on_hand - preferred_stock_level'))->paginate(25);
        
        // Calculate excess stock summary
        $summary = [
            'total_items' => $excessStockItems->total(),
            'total_excess_quantity' => $excessStockItems->sum(function ($item) use ($excessThreshold) {
                $excess = $item->quantity_on_hand - ($item->preferred_stock_level * $excessThreshold);
                return max(0, $excess);
            }),
            'total_excess_value' => $this->calculateExcessStockValue($excessStockItems, $excessThreshold),
            'avg_excess_percentage' => $excessStockItems->avg(function ($item) use ($excessThreshold) {
                if ($item->preferred_stock_level > 0) {
                    return (($item->quantity_on_hand / $item->preferred_stock_level) - 1) * 100;
                }
                return 0;
            }),
        ];
        
        // Categorize by excess percentage
        $excessCategories = [
            '50_100' => 0, // 50-100% excess
            '100_200' => 0, // 100-200% excess
            'over_200' => 0, // Over 200% excess
        ];
        
        $excessStockItems->each(function ($item) use (&$excessCategories) {
            if ($item->preferred_stock_level > 0) {
                $excessPercentage = (($item->quantity_on_hand / $item->preferred_stock_level) - 1) * 100;
                
                if ($excessPercentage >= 50 && $excessPercentage < 100) {
                    $excessCategories['50_100'] += $item->quantity_on_hand;
                } elseif ($excessPercentage >= 100 && $excessPercentage < 200) {
                    $excessCategories['100_200'] += $item->quantity_on_hand;
                } elseif ($excessPercentage >= 200) {
                    $excessCategories['over_200'] += $item->quantity_on_hand;
                }
            }
        });
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.excess-stock', compact(
            'excessStockItems',
            'summary',
            'excessCategories',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'excessThreshold'
        ));
    }
    
    /**
     * Helper method to calculate dead stock value
     */
    private function calculateDeadStockValue($items)
    {
        return $items->sum(function ($item) {
            return $item->quantity_on_hand * ($item->variant->cost_price ?? 0);
        });
    }

    /**
     * Helper method to calculate excess stock value
     */
    private function calculateExcessStockValue($items, $threshold)
    {
        return $items->sum(function ($item) use ($threshold) {
            $costPrice = $item->variant->cost_price ?? 0;
            $excessQuantity = max(0, $item->quantity_on_hand - ($item->preferred_stock_level * $threshold));
            return $excessQuantity * $costPrice;
        });
    }
    
}