<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DependentController extends Controller
{
    /**
     * Get child options based on parent for ANY model
     * Usage: /api/dependent/options?parent_id=1&child_model=department&parent_field=location_id
     */
    public function getOptions(Request $request)
    {
        $parentId = $request->parent_id;
        $childModel = $request->child_model;
        $parentField = $request->parent_field;
        $tenantId = auth()->user()->tenant_id;
        
        // Map model names to their classes
        $models = [
            'department' => \App\Models\Department::class,
            'subcategory' => \App\Models\Subcategory::class,
            'city' => \App\Models\City::class,
            'product' => \App\Models\Product::class,
            'user' => \App\Models\User::class,
            'location' => \App\Models\Location::class,
        ];
        
        if (!isset($models[$childModel]) || !$parentId) {
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
        
        $model = $models[$childModel];
        
        $query = $model::where('tenant_id', $tenantId)
            ->where('is_active', 1);
        
        // Apply parent filter
        if ($parentField) {
            $query->where($parentField, $parentId);
        }
        
        $data = $query->orderBy('name')->get(['id', 'name']);
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * Get departments by location (specific helper)
     */
    public function getDepartmentsByLocation(Request $request)
    {
        $locationId = $request->parent_id;
        $tenantId = auth()->user()->tenant_id;
        
        $departments = \App\Models\Department::where('tenant_id', $tenantId)
            ->where('location_id', $locationId)
            // ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }
}