<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Department, Location, Product, ProductCategory, Promotion, Tax };
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Auth, DB, Log };
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view product')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $productTypes = [
            'physical'  => 'Physical',
            'digital'   => 'Digital',
            'service'   => 'Service',
            'composite' => 'Composite',
        ];

        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        $initialFilters = [
            'search'      => (string) $request->get('search', ''),
            'category_id' => (string) $request->get('category_id', ''),
            'type'        => (string) $request->get('type', ''),
            'per_page'    => $perPage,
        ];

        return view('inventory.product-index', [
            'categories'     => $categories,
            'productTypes'   => $productTypes,
            'locations'      => $locations,
            'departments'    => $departments,
            'initialFilters' => $initialFilters,
        ]);
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view product')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $search     = trim((string) $request->get('search', ''));
        $categoryId = $request->get('category_id');
        $type       = $request->get('type');
        $perPage    = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = Product::with(['category:id,name', 'productCreater:id,name'])
            ->withCount('variants')
            ->where('tenant_id', $tenantId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('inventory_strategy', 'like', "%{$search}%")
                ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$search}%"))
                ->orWhereHas('productCreater', fn($cr) => $cr->where('name', 'like', "%{$search}%"));
            });
        }
        if ($categoryId) $query->where('category_id', $categoryId);
        if ($type)       $query->where('type', $type);

        $summary = [
            'total'     => (clone $query)->count(),
            'active'    => (clone $query)->where('is_active', 1)->count(),
            'inactive'  => (clone $query)->where('is_active', 0)->count(),
            'taxable'   => (clone $query)->where('is_taxable', 1)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(Product $p) => [
            'id'                 => $p->id,
            'sku'                => $p->sku,
            'name'               => $p->name,
            'image_url'          => $p->image_url ? productImage($p->image_url) : null,
            'category_name'      => $p->category?->name ?? '—',
            'inventory_strategy' => $p->inventory_strategy,
            'type'               => $p->type,
            'is_taxable'         => (bool) $p->is_taxable,
            'is_active'          => (bool) $p->is_active,
            'creator'            => $p->productCreater?->name ?? '—',
            'created_at'         => optional($p->created_at)->format('d M Y, h:i a'),
            'variants_count'     => $p->variants_count ?? 0,
        ]);

        return response()->json([
            'success'    => true,
            'data'       => $rows,
            'summary'    => $summary,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'filters' => ['search' => $search, 'category_id' => $categoryId, 'type' => $type],
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $data = $request->validate([
            'category_id' => [
                'required',
                'exists:product_categories,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    if ($value) {
                        $category = ProductCategory::where('id', $value)
                            ->where('tenant_id', $tenantId)
                            ->first();
                        if (!$category) {
                            $fail('The selected category is invalid.');
                        }
                    }
                }
            ],
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'description' => 'nullable|string',
            'type' => 'required|in:physical,digital,service,composite',
            'inventory_strategy' => 'required|in:quantity,batch,serial,recipe',
            'is_taxable' => 'boolean',
            'is_active'  => 'boolean',
        ]);

        // ── Auto-generate SKU when blank ───────────────────────
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateUniqueProductSku($tenantId);
        }

        $data['slug']       = Str::slug($data['name']);
        $data['created_by'] = $user->id;
        $data['tenant_id']  = $tenantId;

        $product = Product::create($data);

        if ($product->inventory_strategy === 'recipe') {
            $this->createRecipeIfNotExists($product->id);
        }

        return response()->json([
            'success'     => true,
            'reload'      => true,
            'componentId' => 'reloadProductComponent',
            'refresh'     => false,
            'message'     => __('auth._created'),
            'redirect'    => route('products.index'),
        ]);
    }

    /**
     * Generate a unique SKU scoped per tenant.
     * Format: PRD-XXXXXX  (6 random alphanumerics).
     */
    private function generateUniqueProductSku(int $tenantId): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';   // no I/O/0/1 to avoid visual confusion
        $prefix   = 'PRD-';

        do {
            $random = '';
            for ($i = 0; $i < 6; $i++) {
                $random .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $sku = $prefix . $random;

            $exists = Product::where('tenant_id', $tenantId)
                ->where('sku', $sku)
                ->exists();
        } while ($exists);

        return $sku;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view variant')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ], 403);
            }
            abort(403);
        }

        $product = Product::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $uoms = \App\Models\UnitOfMeasure::where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->where('isActive', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('inventory.product-variant.index', [
            'product'        => $product,
            'uoms'           => $uoms,
            'initialFilters' => [
                'search'   => (string) $request->get('search', ''),
                'per_page' => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                ? (int) $request->get('per_page', 15)
                                : 15,
            ],
        ]);
    }

    /**
     * JSON payload for the drawer (product + variants).
     * GET /products/{id}/drawer-data
     */
    public function showData(Request $request, string $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $product = Product::with([
            'category:id,name',
            'productCreater:id,name,email',
            'taxes:id,name,code',
            'promotions:id,name',
            'locations:id,name',
            'departments:id,name,location_id',
        ])
        ->withCount('variants')
        ->where('tenant_id', $tenantId)
        ->where('id', $id)
        ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        }

        // ── Variants ────────────────────────────────────────────────
        $variants = ProductVariant::with(['unitMeasure:id,name'])
            ->where('product_id', $product->id)
            ->where('tenant_id', $tenantId)
            ->latest()
            ->get()
            ->map(function (ProductVariant $v) {
                return [
                    'id'                     => $v->id,
                    'name'                   => $v->name,
                    'sku'                    => $v->sku,
                    'barcode'                => $v->barcode,
                    'image_url'              => productVariantImage($v->image_url),
                    'supplier_cost_price'    => (float) $v->supplier_cost_price,
                    'grand_total_cost_price' => (float) $v->grand_total_cost_price,
                    'selling_price'          => (float) $v->selling_price,
                    'discount_selling_price' => (float) $v->discount_selling_price,
                    'profit_per_unit'        => (float) $v->profit_per_unit,
                    'profit_margin'          => (float) $v->profit_margin,
                    'overal_quantity_at_hand'=> (float) $v->overal_quantity_at_hand,
                    'weight'                 => (float) $v->weight,
                    'weight_unit'            => $v->weight_unit,
                    'is_active'              => (bool) $v->is_active,
                ];
            });

        // ── Permissions ─────────────────────────────────────────────
        $can = [
            'can_toggle'        => $user->can('update product'),
            'can_edit'          => $user->can('edit product'),
            'can_delete'        => $user->can('delete product'),
            'can_allocate'      => $user->can('update product'),
            'can_view_variants' => $user->can('view variant'),
        ];

        // ── All tenant options (for the allocate panel) ─────────────
        // We return ALL selectable rows so the user can check/uncheck
        // freely. The "selected" arrays below map to what's already
        // assigned to this product.
        $allTaxes = \App\Models\Tax::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'code' => $t->code])
            ->values();

        $allPromotions = \App\Models\Promotion::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name])
            ->values();

        $allLocations = \App\Models\Location::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($l) => ['id' => $l->id, 'name' => $l->name])
            ->values();

        $allDepartments = \App\Models\Department::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'location_id'])
            ->map(fn($d) => [
                'id'          => $d->id,
                'name'        => $d->name,
                'location_id' => $d->location_id,
            ])
            ->values();

        // ── Response ────────────────────────────────────────────────
        return response()->json([
            'success' => true,
            'product' => array_merge([
                'id'                 => $product->id,
                'name'               => $product->name,
                'sku'                => $product->sku,
                'is_single_shop'     => tenant_is_single_shop($tenantId),
                'category_id'        => $product->category_id,
                'category_name'      => $product->category?->name,
                'type'               => $product->type,
                'inventory_strategy' => $product->inventory_strategy,
                'description'        => $product->description,
                'is_active'          => (bool) $product->is_active,
                'is_taxable'         => (bool) $product->is_taxable,
                'variants_count'     => $product->variants_count ?? 0,
                'creator'            => $product->productCreater ? [
                    'name'  => $product->productCreater->name,
                    'email' => $product->productCreater->email,
                ] : null,
                'created_at'         => optional($product->created_at)->format('d M Y, h:i a'),
                'variants_url'       => route('products.show', $product->id),
            ], $can),
            'variants' => $variants,
            'allocate_options' => [
                'is_single_shop'        => tenant_is_single_shop($tenantId),
                'is_taxable'            => (bool) $product->is_taxable,
                'taxes'                 => $allTaxes,
                'promotions'            => $allPromotions,
                'locations'             => $allLocations,
                'departments'           => $allDepartments,
                'selected_taxes'        => $product->taxes->pluck('id')->values(),
                'selected_promotions'   => $product->promotions->pluck('id')->values(),
                'selected_locations'    => $product->locations->pluck('id')->values(),
                'selected_departments'  => $product->departments->pluck('id')->values(),
                'assign_url'            => route('assign.product', $product->id),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('edit product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find product and ensure it belongs to tenant
        $product = Product::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'max:100',
                Rule::unique('products')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($product->id),
            ],
            'sku' => [
                'required',
                'max:50',
                Rule::unique('products')->where(function ($query) use ($tenantId, $id) {
                    return $query->where('tenant_id', $tenantId)
                            ->where('id', '!=', $id);
                })->ignore($product->id),
            ],
            'category_id' => [
                'required',
                'exists:product_categories,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $category = ProductCategory::where('id', $value)
                                            ->where('tenant_id', $tenantId)
                                            ->first();
                    if (!$category) {
                        $fail('The selected category is invalid.');
                    }
                }
            ],
            'description' => 'nullable|string',
            'type' => 'required|in:physical,digital,service,composite',
            'inventory_strategy' => 'required|in:quantity,batch,serial,recipe',
        ]);

        // Store old strategy before update
        $oldStrategy = $product->inventory_strategy;

        $data['slug'] = Str::slug($data['name']);
        // Don't update created_by or tenant_id

        $product->update($data);

        // ── Handle Recipe creation/deletion based on strategy change ──
        if ($product->inventory_strategy === 'recipe' && $oldStrategy !== 'recipe') {
            // Recipe was just added - create recipe record if it doesn't exist
            $this->createRecipeIfNotExists($product->id);
        } elseif ($product->inventory_strategy !== 'recipe' && $oldStrategy === 'recipe') {
            // Recipe was removed - delete the recipe record
            $this->deleteRecipeIfExists($product->id);
        }

        return response()->json([
            'success' => true,
            'reload' => true,
            'refresh' => false,
            'componentId' => 'reloadProductComponent',
            'message' => __('auth._updated'),
            'redirect' => route('products.index'),
        ]);
    }

    /**
     * Create a recipe record for a product if it doesn't already exist
     */
    private function createRecipeIfNotExists(int $productId): void
    {
        try {
            $exists = DB::table('recipes')->where('product_id', $productId)->exists();
            
            if (!$exists) {
                DB::table('recipes')->insert([
                    'product_id' => $productId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Log::info('Recipe created for product', ['product_id' => $productId]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create recipe for product', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete a recipe record for a product if it exists
     */
    private function deleteRecipeIfExists(int $productId): void
    {
        try {
            $deleted = DB::table('recipes')->where('product_id', $productId)->delete();
            
            if ($deleted > 0) {
                // Log::info('Recipe deleted for product', ['product_id' => $productId]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete recipe for product', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('delete product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Find product and ensure it belongs to tenant
        $product = Product::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        if ($product->is_active == 1) {
            return response()->json([
                'success' => false,
                'message' => __('auth.still_active'),
            ]);
        }

        // ✅ Get variant count
        $variantCount = ProductVariant::where('product_id', $id)
            ->where('tenant_id', $tenantId)
            ->count();



        // ✅ Begin transaction to delete product and its variants
        DB::beginTransaction();
        
        try {
            // ✅ Get all variant IDs to delete inventory records too
            $variantIds = ProductVariant::where('product_id', $id)
                ->where('tenant_id', $tenantId)
                ->pluck('id')
                ->toArray();

            // ✅ Delete inventory records for all variants
            if (!empty($variantIds)) {
                DB::table('inventory_items')
                    ->whereIn('variant_id', $variantIds)
                    ->where('tenant_id', $tenantId)
                    ->delete();

                // ✅ Delete variant taxes and promotions if they exist
                DB::table('variant_taxes')
                    ->whereIn('variant_id', $variantIds)
                    ->delete();

                DB::table('promotion_products')
                    ->whereIn('variant_id', $variantIds)
                    ->delete();
            }

            // ✅ Delete all variants
            ProductVariant::where('product_id', $id)
                ->where('tenant_id', $tenantId)
                ->delete();

            // ✅ Delete product
            $product->delete();

            DB::commit();
            
            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadProductComponent',
                'refresh' => false,
                'message' => __('auth._deleted') . ' (' . $variantCount . ' variants deleted)',
                'redirect' => route('products.index'),
                'variants_deleted' => $variantCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Product deletion failed', [
                'product_id' => $id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.delete_failed') . ': ' . $e->getMessage(),
            ]);
        }
    }

    
    public function changeProductStatus(Request $request, $id) 
    {
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $product = Product::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
    
        if ($product) {
            $product->is_active = $validated['status']; 
            if ($product->save()) {  // Save the user object
                return response()->json([
                    'success' => true,
                    'reload' => true,
                    'refresh' => false,
                    'componentId' => 'reloadProductComponent',
                    'message' => __('auth._updated'),
                    'redirect' => route('products.index'),
                ]);
            }
        }
    
        // If user not found or status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth._not_found'),
        ]);
    }

    
    public function changeProductTaxStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update product tax-promotion')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 'active' or 'inactive' are allowed
        ]);
        
        $product = Product::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();
    
        if ($product) {
            $product->is_taxable = $validated['status']; 
            $product->save();
            if ($validated['status'] == 1) {  
                $message = __('pagination.taxable_now');
            } else {
                $message = __('pagination.not_taxable');
            }
            return response()->json([
                'success' => true,
                'reload' => true,
                'refresh' => false,
                'componentId' => 'reloadProductComponent',
                'message' => $message,
                'redirect' => route('products.index'),
            ]);
        }
    
        // If user not found or status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth._not_found'),
        ]);
    }

    public function uploadProductImage(Request $request)
    {
        
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('upload product photo')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        // Validate the request to ensure the file exists
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'product_id' => 'required|integer',
        ]);

        if ($request->hasFile('photo')) {
            $product = Product::find($request['product_id']);

            $path = $request->file('photo')->store('products', 'public');
            $product->update(['image_url' => $path]);


            // Respond with success if it's an AJAX request
            return response()->json([
                'success' => true,
                'message' => __('auth._uploaded '),
            ]);
        }

        // Return an error response if something goes wrong upload_failed
        return response()->json([
            'success' => false,
            'message' => __('auth.upload_failed '),
        ]);
    }

    public function updateProductAssignments(Request $request, Product $product)
    {
        // \Log::info($request);
        // \Log::info($product);
        $user = Auth::user();
        $tenantId = $user->tenant_id;
                
        if (!$user->hasPermissionTo('update product')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
            }
            abort(403, __('payments.not_authorized'));
        }

        // Check if product belongs to tenant
        if ($product->tenant_id !== $tenantId) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.unauthorized_access'),
            ]);
            return redirect()->back();
        }

        $validated = $request->validate([
            'departments' => ['nullable', 'array'],
            'departments.*' => [
                'exists:departments,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $department = Department::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    if (!$department) {
                        $fail('The selected department is invalid.');
                    }
                }
            ],
            'locations' => ['nullable', 'array'],
            'locations.*' => [
                'exists:locations,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $location = Location::where('id', $value)
                                    ->where('tenant_id', $tenantId)
                                    ->first();
                    if (!$location) {
                        $fail('The selected location is invalid.');
                    }
                }
            ],
            'taxes' => ['nullable', 'array'],
            'taxes.*' => [
                'exists:taxes,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $tax = Tax::where('id', $value)
                            ->where('tenant_id', $tenantId)
                            ->first();
                    if (!$tax) {
                        $fail('The selected tax is invalid.');
                    }
                }
            ],
            'promotions' => ['nullable', 'array'],
            'promotions.*' => [
                'exists:promotions,id',
                function ($attribute, $value, $fail) use ($tenantId) {
                    $promotion = Promotion::where('id', $value)
                                        ->where('tenant_id', $tenantId)
                                        ->first();
                    if (!$promotion) {
                        $fail('The selected promotion is invalid.');
                    }
                }
            ],
        ]);

        // Auto-calculate locations based on selected departments
        $selectedDepartments = $validated['departments'] ?? [];
        $autoLocations = [];
        
        if (!empty($selectedDepartments)) {
            // Get all locations for the selected departments
            $autoLocations = Department::whereIn('id', $selectedDepartments)
                ->where('tenant_id', $tenantId)
                ->distinct()
                ->pluck('location_id')
                ->filter()
                ->toArray();
        }
        
        // Merge manually selected locations with auto-detected ones
        $manualLocations = $validated['locations'] ?? [];
        $allLocations = array_unique(array_merge($manualLocations, $autoLocations));

        // Start database transaction for data consistency
        DB::beginTransaction();

        try {
            // 1. Sync departments (use selected departments only)
            $product->departments()->sync($selectedDepartments);

            // 2. Sync locations (merged manual + auto-detected)
            $product->locations()->sync($allLocations);

            // 3. Sync promotions
            $product->promotions()->sync($validated['promotions'] ?? []);

            // 4. Handle taxes based on product's taxable status
            if ($product->is_taxable == 1) {
                $product->taxes()->sync($validated['taxes'] ?? []);
            } else {
                $product->taxes()->sync([]);
                
                if (!empty($validated['taxes'])) {
                    session()->flash('toast', [
                        'type' => 'warning',
                        'message' => __('pagination.product_not_taxable_taxes_removed'),
                    ]);
                }
            }

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('auth._updated'),
                ]);
            }

            session()->flash('toast', [
                'type' => 'success',
                'message' => __('auth._updated'),
            ]);
            return redirect()->back();

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Product assignments update failed', [
                'product_id' => $product->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.update_failed'),
                ], 500);
            }


            session()->flash('toast', [
                'type' => 'error',
                'message' => __('auth.update_failed'),
            ]);
        }
    }

    public function bulkAllocate(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('update product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        // ✅ Fix: Accept both string and array for product_ids
        $validated = $request->validate([
            'product_ids' => ['required'],
            'departments' => ['nullable', 'array'],
            'departments.*' => ['exists:departments,id'],
        ]);

        // ✅ Parse product IDs - handles both string and array
        $productIds = [];
        if (is_string($validated['product_ids'])) {
            $productIds = array_filter(explode(',', $validated['product_ids']));
        } elseif (is_array($validated['product_ids'])) {
            $productIds = $validated['product_ids'];
        }
        
        if (empty($productIds)) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.no_products_selected'),
            ], 422);
        }

        // Get products that belong to this tenant
        $products = Product::whereIn('id', $productIds)
            ->where('tenant_id', $tenantId)
            ->get();

        if ($products->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('pagination.no_valid_products'),
            ], 422);
        }

        $departmentIds = $validated['departments'] ?? [];

        DB::beginTransaction();

        try {
            foreach ($products as $product) {
                // ✅ Sync departments
                $product->departments()->sync($departmentIds);

                // ✅ Auto-calculate locations based on selected departments
                $autoLocations = [];
                if (!empty($departmentIds)) {
                    $autoLocations = Department::whereIn('id', $departmentIds)
                        ->where('tenant_id', $tenantId)
                        ->distinct()
                        ->pluck('location_id')
                        ->filter()
                        ->toArray();
                }
                
                // ✅ Sync locations
                $product->locations()->sync($autoLocations);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('pagination.products_allocated_success', ['count' => $products->count()]),
                'componentId' => 'reloadProductComponent',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // \Log::error('Bulk allocation failed', [
            //     'product_ids' => $productIds,
            //     'department_ids' => $departmentIds,
            //     'error' => $e->getMessage()
            // ]);

            return response()->json([
                'success' => false,
                'message' => __('pagination.bulk_allocation_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

}
