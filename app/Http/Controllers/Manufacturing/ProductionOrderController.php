<?php
// app/Http/Controllers/Manufacturing/ProductionOrderController.php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ProductionOrder, ProductionOrderInput, ProductionOrderOutput,
    ProductVariant, PurchaseReceiptItem, SerialNumber, Location, PaymentMethod,
    SingleShopInventoryLog, BatchLog, Currency,
    InventoryItems, InventoryAdjustments, InventoryTransactions, Department};
use Illuminate\Support\Facades\{Auth, DB};

class ProductionOrderController extends Controller
{

    public function index(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view production_orders')) {
            abort(403, __('payments.not_authorized'));
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $isSingleShop = tenant_is_single_shop($tenantId);

        // ── Dropdown data for the create modal ─────────────────────
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get();

        $locationsQuery = Location::where('tenant_id', $tenantId);
        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }
        $locations = $locationsQuery->get();

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location_id']);

        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get();

        $uoms = \App\Models\UnitOfMeasure::where(function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
        })
        ->where('isActive', 1)
        ->get();

        // ── Initial filter state (URL preserved on refresh) ────────
        $initialFilters = [
            'search'      => (string) $request->get('search', ''),
            'location_id' => (string) $request->get('location_id', ''),
            'status'      => (string) $request->get('status', 'all'),
            'per_page'    => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                                ? (int) $request->get('per_page', 15)
                                : 15,
        ];

        return view('manufacturing.production-index', compact(
            'variants',
            'locations',
            'departments',
            'paymentMethods',
            'uoms',
            'isSingleShop',
            'initialFilters',
        ));
    }

    /**
     * JSON endpoint for the production orders list.
     *
     * Mirrors the filters and scoping rules in index(), but returns
     * a JS-shaped payload for the vanilla-JS table renderer.
     *
     * GET /production-orders/data
     */
    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view production_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $isAdmin         = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ── Filters ────────────────────────────────────────────────
        $search     = trim((string) $request->get('search', ''));
        $locationId = $request->get('location_id');
        $status     = $request->get('status');
        $perPage    = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        // ── Base query ─────────────────────────────────────────────
        $query = ProductionOrder::with([
                'location:id,name',
                'createdBy:id,name',
            ])
            ->withCount(['inputs', 'outputs'])
            ->where('tenant_id', $tenantId);

        if (!$isAdmin) {
            if (empty($userLocationIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('location_id', $userLocationIds);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('production_number', 'like', "%{$search}%")
                ->orWhere('status',          'like', "%{$search}%")
                ->orWhereHas('createdBy', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($locationId) {
            $requestedLocationId = (int) $locationId;
            if ($isAdmin || in_array($requestedLocationId, $userLocationIds, true)) {
                $query->where('location_id', $requestedLocationId);
            }
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // ── Summary from the FILTERED set (before pagination) ─────
        $allFiltered = (clone $query)->get([
            'id', 'status', 'total_cost', 'total_input_quantity', 'total_output_quantity',
        ]);

        $summary = [
            'total'          => $allFiltered->count(),
            'draft'          => $allFiltered->where('status', ProductionOrder::STATUS_DRAFT)->count(),
            'in_progress'    => $allFiltered->where('status', ProductionOrder::STATUS_IN_PROGRESS)->count(),
            'completed'      => $allFiltered->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
            'cancelled'      => $allFiltered->where('status', ProductionOrder::STATUS_CANCELLED)->count(),
            'total_cost'     => (float) $allFiltered->sum('total_cost'),
            'total_input'    => (float) $allFiltered->sum('total_input_quantity'),
            'total_output'   => (float) $allFiltered->sum('total_output_quantity'),
        ];

        // ── Paginate ───────────────────────────────────────────────
        $paginator = $query->latest()->paginate($perPage);

        // ── Shape rows for JS ──────────────────────────────────────
        $rows = collect($paginator->items())->map(function (ProductionOrder $order) {
            return [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'location_id'       => $order->location_id,
                'location_name'     => $order->location?->name ?? __('pagination._none'),
                'status'            => $order->status,
                'status_label'      => $order->status_label ?? $order->status,
                'status_badge'      => $order->status_badge ?? 'secondary',

                'inputs_count'      => $order->inputs_count ?? 0,
                'outputs_count'     => $order->outputs_count ?? 0,
                'total_input_quantity'  => (float) ($order->total_input_quantity ?? 0),
                'total_output_quantity' => (float) ($order->total_output_quantity ?? 0),

                'total_cost'        => (float) ($order->total_cost ?? 0),
                'total_cost_formatted' => currency_symbol() . number_format((float) ($order->total_cost ?? 0), 2),

                'scheduled_date'    => optional($order->scheduled_date)->format('Y-m-d'),
                'scheduled_date_human' => optional($order->scheduled_date)->format('M d, Y') ?? __('pagination._none'),

                'created_by_name'   => $order->createdBy?->name ?? __('pagination._none'),
                'created_at'        => optional($order->created_at)->format('d M Y H:i'),
            ];
        });

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
                'has_more'     => $paginator->hasMorePages(),
            ],
            'filters' => [
                'search'      => $search,
                'location_id' => $locationId,
                'status'      => $status,
            ],
        ]);
    }



    /**
     * Sentence fragment: "Fred (fred@shop.com)".
     */
    private function actorLabel($user = null): string
    {
        $user = $user ?? Auth::user();
        return $user ? "{$user->name} ({$user->email})" : 'System';
    }

    /**
     * Sentence fragment: "at Main Store (Sales)" — from a resolved context.
     */
    private function productionContextLabel(ProductionOrder $po): string
    {
        $loc = $po->location?->name ?? "Location #{$po->location_id}";
        return " at {$loc}";
    }

    /**
     * Short label for a variant: "Widget Pro (SKU WG-001)".
     */
    private function variantLabel(?ProductVariant $variant): string
    {
        if (!$variant) return 'Unknown Item';
        return $variant->name . ' (SKU ' . ($variant->sku ?? 'N/A') . ')';
    }

    /**
     * Short label for a batch: "LOT-A" or "#123".
     */
    private function batchLabel($batch): string
    {
        if (!$batch) return 'unknown batch';
        return $batch->batch_number ? "batch {$batch->batch_number}" : "batch #{$batch->id}";
    }

    public function store(Request $request)
    {
        // \Log::info($request->all());
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create production_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'scheduled_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
            
            'inputs' => 'required|array|min:1',
            'inputs.*.product_variant_id' => [
                'required',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) use ($request) {
                    preg_match('/inputs\.(\d+)\./', $attribute, $m);
                    $index = $m[1] ?? null;
                    if ($index === null) return;

                    $variant = ProductVariant::with('product')->find($value);
                    if (!$variant) return;

                    $strategy = $variant->product?->resolvedInventoryStrategy();
                    if ($strategy === 'batch') {
                        $batchId = $request->input("inputs.{$index}.purchase_receipt_item_id");
                        if (empty($batchId)) {
                            $fail("A specific batch must be selected for batch-tracked material '{$variant->name}'.");
                        }
                    }
                },
            ],
            'inputs.*.planned_quantity' => 'required|numeric|min:0.01',
            'inputs.*.purchase_receipt_item_id' => 'nullable|exists:purchase_receipt_items,id',
            'inputs.*.unit' => 'required|string|max:20',
            'inputs.*.estimated_cost' => 'nullable|numeric|min:0',
            
            'outputs' => 'required|array|min:1',
            'outputs.*.product_variant_id' => 'required|exists:product_variants,id',
            'outputs.*.planned_quantity' => 'required|numeric|min:0.01',
            'outputs.*.unit' => 'required|string|max:20',
            'outputs.*.production_cost' => 'nullable|numeric|min:0',
            'outputs.*.inventory_strategy' => 'required|in:quantity,batch,serial',
        ]);

        // 🔒 Prevent duplicate batch usage
        $batchIds = collect($request->inputs)->pluck('purchase_receipt_item_id')->filter()->values();
        $duplicates = $batchIds->duplicates()->unique()->values();

        if ($duplicates->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.batch_already_used') ?? 'The same batch was selected more than once.',
                'errors'  => ['inputs' => ['Batch IDs used more than once: ' . $duplicates->implode(', ')]],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $productionNumber = ProductionOrder::generateProductionNumber($tenantId);

            // Calculate totals
            $estimatedInputCost = 0;
            foreach ($request->inputs as $input) {
                $variant = ProductVariant::find($input['product_variant_id']);
                $cost = $input['estimated_cost'] ?? (($variant->supplier_cost_price ?? 0) * $input['planned_quantity']);
                $estimatedInputCost += $cost;
            }

            $estimatedOutputCost = 0;
            foreach ($request->outputs as $output) {
                $perUnitCost = (float) ($output['production_cost'] ?? 0);
                $quantity = (float) ($output['planned_quantity'] ?? 0);
                $estimatedOutputCost += ($perUnitCost * $quantity);
            }

            $totalEstimatedCost = $estimatedInputCost + $estimatedOutputCost;

            // Create Production Order
            $productionOrder = ProductionOrder::create([
                'tenant_id' => $tenantId,
                'location_id' => $validated['location_id'],
                'production_number' => $productionNumber,
                'status' => ProductionOrder::STATUS_DRAFT,
                'scheduled_date' => $validated['scheduled_date'] ?? null,
                'estimated_cost' => $totalEstimatedCost,
                'created_by' => $user->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create Inputs
            foreach ($request->inputs as $inputData) {
                $variant = ProductVariant::find($inputData['product_variant_id']);
                $unitCost = $inputData['estimated_cost'] ?? ($variant->supplier_cost_price ?? 0);
                $estimatedCost = $unitCost * $inputData['planned_quantity'];

                ProductionOrderInput::create([
                    'production_order_id' => $productionOrder->id,
                    'product_variant_id' => $inputData['product_variant_id'],
                    'purchase_receipt_item_id' => $inputData['purchase_receipt_item_id'] ?? null,
                    'planned_quantity' => $inputData['planned_quantity'],
                    'actual_quantity' => 0,
                    'waste_quantity' => 0,
                    'unit' => $inputData['unit'],
                    'estimated_cost' => $estimatedCost,
                    'actual_cost' => 0,
                ]);
            }

            // Create Outputs
            foreach ($request->outputs as $outputData) {
                // Save the PER-UNIT cost exactly as sent
                $perUnitCost = (float) ($outputData['production_cost'] ?? 0);
                
                ProductionOrderOutput::create([
                    'production_order_id' => $productionOrder->id,
                    'product_variant_id' => $outputData['product_variant_id'],
                    'planned_quantity' => $outputData['planned_quantity'],
                    'actual_quantity' => 0,
                    'defective_quantity' => 0,
                    'unit' => $outputData['unit'],
                    'production_cost' => $perUnitCost, 
                    'selling_price' => 0,
                    'inventory_strategy' => $outputData['inventory_strategy'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadProductionComponent',
                'message' => __('passwords.production_order_created'),
                'redirect' => route('production-orders.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Production order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating production order: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Start production with payment withdrawal
     */
    public function startWithPayment(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('start production_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $productionOrder = ProductionOrder::where('tenant_id', $tenantId)
            ->with(['inputs.productVariant.product', 'outputs.productVariant.product'])
            ->findOrFail($id);

        if ($productionOrder->status !== ProductionOrder::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_start_draft_orders'),
            ]);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);

        // Validate inputs have stock
        try {
            $this->validateInputStock($productionOrder);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        DB::beginTransaction();

        try {
            // Consume each input from its batch / overall stock
            foreach ($productionOrder->inputs as $input) {
                $this->consumeInput($input, $productionOrder);
            }

            // Pure status transition — no money moves here
            $productionOrder->update([
                'status'     => ProductionOrder::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'started_by' => $user->id,
                'notes'      => $validated['notes']
                    ? ($productionOrder->notes
                        ? $productionOrder->notes . "\n" . $validated['notes']
                        : $validated['notes'])
                    : $productionOrder->notes,
            ]);

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => __('passwords.production_started'),
                'reload'      => true,
                'componentId' => 'reloadProductionComponent',
                'redirect'    => route('production-orders.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Production start failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'production_order_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ✅ VALIDATE: All inputs have sufficient stock in master stock (overal_quantity_at_hand)
     */
    private function validateInputStock($productionOrder): void
    {
        foreach ($productionOrder->inputs as $input) {
            $variant = $input->productVariant;
            if (!$variant) continue;

            $product = $variant->product;
            $strategy = $product?->resolvedInventoryStrategy() ?? 'quantity';
            $quantityNeeded = $input->planned_quantity;

            // \Log::info('[Production] Validating stock', [
            //     'variant' => $variant->name,
            //     'strategy' => $strategy,
            //     'needed' => $quantityNeeded,
            //     'available' => $variant->overal_quantity_at_hand ?? 0,
            // ]);

            // ✅ BATCH STRATEGY: Check batch quantity_remaining
            if ($strategy === 'batch') {
                if ($input->purchase_receipt_item_id) {
                    $batch = PurchaseReceiptItem::find($input->purchase_receipt_item_id);
                    if (!$batch) {
                        throw new \Exception("Batch not found for {$variant->name}");
                    }
                    
                    $available = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                    if ($available < $quantityNeeded) {
                        throw new \Exception("Insufficient batch stock for {$variant->name}. Available: {$available}, Required: {$quantityNeeded}");
                    }
                } else {
                    $available = $variant->batches()
                        ->where(function($q) {
                            $q->where('quantity_remaining', '>', 0)
                              ->orWhereNull('quantity_remaining');
                        })
                        ->sum('quantity_remaining');
                    
                    if ($available < $quantityNeeded) {
                        throw new \Exception("Insufficient batch stock for {$variant->name}. Available: {$available}, Required: {$quantityNeeded}");
                    }
                }
            }
            // ✅ QUANTITY STRATEGY: Check overal_quantity_at_hand (master stock)
            elseif ($strategy === 'quantity') {
                $available = $variant->overal_quantity_at_hand ?? 0;
                if ($available < $quantityNeeded) {
                    throw new \Exception("Insufficient stock for {$variant->name}. Available: {$available}, Required: {$quantityNeeded}");
                }
            }
            // ✅ SERIAL STRATEGY: Check available serials
            elseif ($strategy === 'serial') {
                $available = SerialNumber::where('variant_id', $variant->id)
                    ->where('status', SerialNumber::STATUS_AVAILABLE)
                    ->where('tenant_id', $productionOrder->tenant_id)
                    ->count();
                
                if ($available < $quantityNeeded) {
                    throw new \Exception("Insufficient serial numbers for {$variant->name}. Available: {$available}, Required: {$quantityNeeded}");
                }
            }
        }
    }

    /**
     * Consume a production input's quantity from its batch (or overall stock).
     *
     * STRICT MODE — no FIFO fallback:
     *   - Batch-strategy inputs MUST have a purchase_receipt_item_id.
     *   - That batch is the ONLY batch touched.
     *   - If the batch has less stock than required, throw.
     *   - If no batch is attached, throw.
     *
     * The user is expected to add one input row per batch they want to consume.
     */
    private function consumeInput($input, $productionOrder): void
    {
        $variant = $input->productVariant;
        if (!$variant) return;

        $product  = $variant->product;
        $strategy = $product?->resolvedInventoryStrategy() ?? 'quantity';
        $quantity = (float) $input->planned_quantity;

        // Persist actual quantity/cost on the input row
        $input->update([
            'actual_quantity' => $quantity,
            'actual_cost'     => $input->estimated_cost,
        ]);

        // ── 1. Batch strategy — STRICT ────────────────────────────────
        if ($strategy === 'batch') {
            if (empty($input->purchase_receipt_item_id)) {
                throw new \Exception(sprintf(
                    'Production order %s: input for "%s" uses the batch strategy but no batch was selected. '
                    . 'Every batch-tracked input must point to exactly one batch. '
                    . 'FIFO across batches is disabled.',
                    $productionOrder->production_number,
                    $variant->name
                ));
            }

            $this->depleteSpecificBatch(
                batchId:         (int) $input->purchase_receipt_item_id,
                variant:         $variant,
                quantity:        $quantity,
                productionOrder: $productionOrder,
                inputId:         $input->id
            );

            return;
        }

        // ── 2. Serial strategy — reserve specific serials ────────────
        if ($strategy === 'serial') {
            $this->reserveSpecificSerials($variant, $quantity, $productionOrder, $input);
            return;
        }

        // ── 3. Quantity strategy — decrement overall stock ───────────
        $this->decrementOverallQuantity($variant, $quantity, $input, $productionOrder, 'production_consumption');
    }


    /**
     * Consume from one specific batch — STRICT.
     *
     * Validations (all must pass or the whole transaction aborts):
     *   1. Batch exists.
     *   2. Batch belongs to the same tenant.
     *   3. Batch's variant matches the input's variant.
     *   4. Batch's quantity_remaining is >= quantity requested.
     *   5. Batch's quantity_remaining is not null (batch has been properly received).
     *
     * On success:
     *   - Decrements the batch's quantity_remaining.
     *   - Writes a BatchLog with the full narrative.
     *   - Decrements the variant's overall_quantity_at_hand.
     *   - Writes a SingleShopInventoryLog for the tenant-wide tally.
     */
    private function depleteSpecificBatch(
        int $batchId,
        ProductVariant $variant,
        float $quantity,
        ProductionOrder $productionOrder,
        ?int $inputId
    ): void {
        $batch = PurchaseReceiptItem::query()
            ->where('id', $batchId)
            ->where('tenant_id', $productionOrder->tenant_id)
            ->first();

        if (!$batch) {
            throw new \Exception(sprintf(
                'Production order %s: batch id %d not found for tenant %d.',
                $productionOrder->production_number,
                $batchId,
                $productionOrder->tenant_id
            ));
        }

        // ── Verify the batch belongs to this variant ─────────────────
        $batchVariantId = $batch->purchaseOrderItem?->product_variant_id
                    ?? $batch->purchaseOrderItem?->productVariant?->id;

        if ($batchVariantId && (int) $batchVariantId !== (int) $variant->id) {
            throw new \Exception(sprintf(
                'Production order %s: batch %s belongs to variant #%d, but input expects variant #%d ("%s").',
                $productionOrder->production_number,
                $batch->batch_number,
                $batchVariantId,
                $variant->id,
                $variant->name
            ));
        }

        // ── Verify the batch has a real remaining quantity ───────────
        if ($batch->quantity_remaining === null) {
            throw new \Exception(sprintf(
                'Production order %s: batch %s has no quantity_remaining value. '
                . 'The batch may not have been properly received.',
                $productionOrder->production_number,
                $batch->batch_number
            ));
        }

        $before = (float) $batch->quantity_remaining;

        // ── Over-consumption: already caught ─────────────────────────
        if ($before < $quantity) {
            throw new \Exception(sprintf(
                'Production order %s: batch %s has %s units remaining, but %s units are needed '
                . 'for input "%s". FIFO across batches is disabled — add a second input row if you '
                . 'intend to consume from multiple batches.',
                $productionOrder->production_number,
                $batch->batch_number,
                number_format($before, 2),
                number_format($quantity, 2),
                $variant->name
            ));
        }

        // ── NEW: reject a partial draw that leaves an implausibly small remainder.
        //    This catches the "typed 1 against a 35,349 batch" mistake.
        //    The threshold is expressed as a fraction of the batch; tune per product.
        $minFraction    = (float) ($variant->product?->min_batch_consumption_fraction ?? 0);
        $remainder      = $before - $quantity;
        $remainderRatio = $before > 0 ? ($remainder / $before) : 1.0;
        $consumedRatio  = $before > 0 ? ($quantity / $before) : 1.0;

        if ($minFraction > 0
            && $before > 0
            && $consumedRatio < $minFraction
            && $remainder > 0.0001              // not a full depletion
        ) {
            throw new \Exception(sprintf(
                'Production order %s: batch %s holds %s units; you entered %s, which is only %s%% of the batch. '
                . 'The product "%s" requires at least %s%% of a batch to be consumed per input row. '
                . 'If this is intentional, set the product\'s minimum batch consumption to 0 or split the batch first.',
                $productionOrder->production_number,
                $batch->batch_number,
                number_format($before, 2),
                number_format($quantity, 2),
                number_format($consumedRatio * 100, 1),
                $variant->name,
                number_format($minFraction * 100, 1)
            ));
        }

        $after    = $before - $quantity;
        $unitCost = (float) ($batch->unit_cost ?? 0);

        // ── Decrement the batch ──────────────────────────────────────
        $batch->quantity_remaining = $after;
        $batch->save();

        // ── Resolve the PO / supplier chain for the log ──────────────
        $origin = $this->resolveBatchOrigin($batch);

        // ── Build the narrative ──────────────────────────────────────
        $actor        = $this->actorLabel();
        $productionN° = $productionOrder->production_number;
        $context      = $this->productionContextLabel($productionOrder);

        $narrative = sprintf(
            '%s consumed %s × "%s" from batch %s for production %s%s. '
            . 'Batch stock: %s → %s. '
            . 'This was the only batch touched for this input row — no FIFO fallback.',
            $actor,
            number_format($quantity, 2),
            $variant->name,
            $batch->batch_number,
            $productionN°,
            $context,
            number_format($before, 2),
            number_format($after, 2)
        );

        BatchLog::create(array_merge([
            'batch_id'                   => $batch->id,
            'batch_number'               => $batch->batch_number,
            'variant_id'                 => $variant->id,
            'variant_name'               => $variant->name,
            'variant_sku'                => $variant->sku,
            'type'                       => BatchLog::TYPE_DEPLETED,
            'quantity_change'            => -$quantity,
            'quantity_before'            => $before,
            'quantity_after'             => $after,
            'unit_cost'                  => $unitCost,
            'total_cost'                 => $unitCost * $quantity,
            'production_order_id'        => $productionOrder->id,
            'production_order_input_id'  => $inputId,
            'tenant_id'                  => $productionOrder->tenant_id,
            'location_id'                => $batch->location_id ?? $productionOrder->location_id,
            'department_id'              => $batch->department_id,
            'expiry_date'                => $batch->expiry_date,
            'event_date'                 => now(),
            'performed_by'               => auth()->id(),
            'metadata' => [
                'source'            => 'production_consumption_strict',
                'production_number' => $productionN°,
                'reason'            => 'consumed',
                'narrative'         => $narrative,
                'quantity_before'   => $before,
                'quantity_after'    => $after,
                'batch_number'      => $batch->batch_number,
            ],
        ], $origin));

        // ── Decrement the tenant-wide overall tally ──────────────────
        $this->decrementOverallQuantity(
            $variant,
            $quantity,
            $inputId ? (object) ['id' => $inputId, 'purchase_receipt_item_id' => $batch->id] : null,
            $productionOrder,
            'production_consumption_batch_strict'
        );
    }

    /**
     * Given a PurchaseReceiptItem, walk up the chain to find its
     * purchase order, supplier, and receipt context.
     *
     * Returns an array ready to merge into a BatchLog::create() payload.
     */
    private function resolveBatchOrigin(PurchaseReceiptItem $batch): array
    {
        // Join through to the PO and supplier
        $row = DB::table('purchase_receipt_items as pri')
            ->leftJoin('purchase_receipts as pr', 'pr.id', '=', 'pri.purchase_receipt_id')
            ->leftJoin('purchase_orders as po', 'po.id', '=', 'pr.purchase_order_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->where('pri.id', $batch->id)
            ->select([
                'po.id as purchase_order_id',
                'po.po_number as purchase_order_number',
                'pr.id as purchase_receipt_id',
                's.id as supplier_id',
                's.name as supplier_name',
            ])
            ->first();

        return [
            'purchase_order_id'     => $row->purchase_order_id    ?? null,
            'purchase_order_number' => $row->purchase_order_number ?? null,
            'purchase_receipt_id'   => $row->purchase_receipt_id   ?? null,
            'supplier_id'           => $row->supplier_id           ?? null,
            'supplier_name'         => $row->supplier_name         ?? null,
        ];
    }

    /**
     * FIFO across all available batches for a variant.
     * Logs one BatchLog row per batch touched.
     */
    private function depleteBatchesFifo(
        ProductVariant $variant,
        float $quantity,
        ProductionOrder $productionOrder,
        $input
    ): void {
        $batches = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->where('purchase_orders.tenant_id', $productionOrder->tenant_id)
            ->where('purchase_order_items.product_variant_id', $variant->id)
            ->where(function ($q) {
                $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                ->orWhereNull('purchase_receipt_items.quantity_remaining');
            })
            ->orderBy('purchase_receipt_items.expiry_date', 'asc')
            ->orderBy('purchase_receipt_items.id', 'asc')
            ->select('purchase_receipt_items.*')
            ->get();

        if ($batches->isEmpty()) {
            throw new \Exception("No available batches for {$variant->name}");
        }

        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $before  = (float) ($batch->quantity_remaining ?? $batch->quantity_received ?? 0);
            $deduct  = min($before, $remaining);
            $after   = $before - $deduct;
            $remaining -= $deduct;

            $batch->quantity_remaining = $after;
            $batch->save();

            $unitCost = (float) ($batch->unit_cost ?? 0);
            $origin   = $this->resolveBatchOrigin($batch);

            $actor        = $this->actorLabel();
            $productionN° = $productionOrder->production_number;
            $context      = $this->productionContextLabel($productionOrder);

            $narrative = sprintf(
                '%s consumed %s × "%s" from %s (FIFO) for production %s%s. '
                . 'Batch stock: %s → %s. Consumed as raw material input%s.',
                $actor,
                number_format($deduct, 2),
                $variant->name,
                $this->batchLabel($batch),
                $productionN°,
                $context,
                number_format($before, 2),
                number_format($after, 2),
                $deduct < $before ? ' (partial depletion)' : ''
            );

            BatchLog::create(array_merge([
                'batch_id'                   => $batch->id,
                'batch_number'               => $batch->batch_number,
                'variant_id'                 => $variant->id,
                'variant_name'               => $variant->name,
                'variant_sku'                => $variant->sku,
                'type'                       => BatchLog::TYPE_DEPLETED,
                'quantity_change'            => -$deduct,
                'quantity_before'            => $before,
                'quantity_after'             => $after,
                'unit_cost'                  => $unitCost,
                'total_cost'                 => $unitCost * $deduct,
                'production_order_id'        => $productionOrder->id,
                'production_order_input_id'  => $input?->id,
                'tenant_id'                  => $productionOrder->tenant_id,
                'location_id'                => $batch->location_id ?? $productionOrder->location_id,
                'department_id'              => $batch->department_id,
                'expiry_date'                => $batch->expiry_date,
                'event_date'                 => now(),
                'performed_by'               => auth()->id(),
                'metadata' => [
                    'source'            => 'production_consumption_fifo',
                    'production_number' => $productionN°,
                    'partial'           => $deduct < $before,
                    'narrative'         => $narrative,
                ],
            ], $origin));
        }

        if ($remaining > 0) {
            throw new \Exception(
                "Insufficient batch stock for {$variant->name}. " .
                "Short by {$remaining} after FIFO across all batches."
            );
        }
    }

    /**
     * Reduce overal_quantity_at_hand and log to SingleShopInventoryLog.
     * Keeps the tenant-wide tally in sync with batch movements.
     */
    // private function decrementOverallQuantity(
    //     ProductVariant $variant,
    //     float $quantity,
    //     $input,
    //     ProductionOrder $productionOrder,
    //     string $reason
    // ): void {
    //     $before = (float) ($variant->overal_quantity_at_hand ?? 0);
    //     $after  = max(0, $before - $quantity);

    //     $variant->overal_quantity_at_hand = $after;
    //     $variant->save();

    //     SingleShopInventoryLog::create([
    //         'variant_id'      => $variant->id,
    //         'order_id'        => $productionOrder->id,
    //         'tenant_id'       => $productionOrder->tenant_id,
    //         'created_by'      => auth()->id(),
    //         'quantity_before' => $before,
    //         'quantity_after'  => $after,
    //         'quantity_change' => -$quantity,
    //         'reason'          => $reason,
    //         'notes'           => "Consumed in production #{$productionOrder->production_number} - {$variant->name}",
    //         'source'          => 'production',
    //         'metadata' => [
    //             'input_id'              => $input->id,
    //             'production_number'     => $productionOrder->production_number,
    //             'purchase_receipt_item' => $input->purchase_receipt_item_id,
    //         ],
    //     ]);
    // }
        
    /**
     * Complete production with outputs (update actual quantities and complete in one step)
     */
    public function completeWithOutputs(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('complete production_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $productionOrder = ProductionOrder::where('tenant_id', $tenantId)
            ->with(['inputs', 'outputs.productVariant'])
            ->findOrFail($id);

        if ($productionOrder->status !== ProductionOrder::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_complete_in_progress_orders'),
            ]);
        }

        // ── Validate ──────────────────────────────────────────────────
        $validated = $request->validate([
            'outputs'                       => 'required|array|min:1',
            'outputs.*.product_variant_id'  => 'required|exists:product_variants,id',
            'outputs.*.actual_quantity'     => 'required|numeric|min:0',
            'outputs.*.defective_quantity'  => 'nullable|numeric|min:0',
            'outputs.*.inventory_strategy'  => 'required|in:quantity,batch,serial',
            'outputs.*.unit'                => 'nullable',
            'batch_number'                  => 'nullable|string|max:100',
            'expiry_date'                   => 'nullable|date|after_or_equal:today',
            'notes'                         => 'nullable|string|max:500',
            'complete'                      => 'boolean',
            'output_location_id'            => 'nullable|integer|exists:locations,id',
            'output_department_id'          => 'nullable|integer|exists:departments,id',

            'payment_method_id'             => 'nullable|exists:payment_methods,id',
            'withdrawal_amount'             => 'nullable|numeric|min:0',
        ]);

        // ── At least one output must have actual_quantity > 0 ─────────
        $hasQuantity = false;
        foreach ($validated['outputs'] as $row) {
            if (($row['actual_quantity'] ?? 0) > 0) { $hasQuantity = true; break; }
        }
        if (!$hasQuantity) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.enter_at_least_one_actual_quantity'),
            ]);
        }

        // ── Multi-shop: require location + department ─────────────────
        $isSingleShop = tenant_is_single_shop($tenantId);

        $allocLocationId   = null;
        $allocDepartmentId = null;

        if (!$isSingleShop) {
            $allocLocationId   = (int) $request->input('output_location_id');
            $allocDepartmentId = (int) $request->input('output_department_id');

            if (!$allocLocationId || !$allocDepartmentId) {
                return response()->json([
                    'success' => false,
                    'message' => __('passwords.output_allocation_required'),
                ], 422);
            }

            $dept = Department::find($allocDepartmentId);
            if (!$dept || (int) $dept->location_id !== $allocLocationId) {
                return response()->json([
                    'success' => false,
                    'message' => __('passwords.department_location_mismatch'),
                ], 422);
            }
        }

        // ── Trust the Frontend's Total Cost ──────────────────────────
        // The frontend calculates the sum of (actual_quantity * production_cost)
        // and ignores items with 0 cost (e.g., maize bran). We use exactly
        // what the user confirmed in the UI.
        $totalProductionCost = (float) ($validated['withdrawal_amount'] ?? 0);
        $paymentMethodId     = $validated['payment_method_id'] ?? null;

        // ── Validate payment block if cost > 0 ───────────────────────
        if ($totalProductionCost > 0) {
            if (!$paymentMethodId) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.payment_method_required'),
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            // ── 1. Block deletion of any output that already has production ──
            $keptIds = collect($validated['outputs'])
                ->keys()
                ->filter(fn($k) => !is_string($k) || !str_starts_with((string)$k, 'new_'))
                ->map(fn($k) => (int) $k)
                ->values()
                ->all();

            $blockedDeletes = ProductionOrderOutput::where('production_order_id', $productionOrder->id)
                ->when(!empty($keptIds), fn($q) => $q->whereNotIn('id', $keptIds))
                ->when(empty($keptIds),   fn($q) => $q)
                ->where('actual_quantity', '>', 0)
                ->get();

            if ($blockedDeletes->isNotEmpty()) {
                throw new \Exception(__('passwords.cannot_delete_produced_output'));
            }

            // ── 2. Process each output row ────────────────────────────
            $batchNumber       = $validated['batch_number']
                                ?? $productionOrder->production_number . '-' . date('Ymd');
            $expiryDate        = $validated['expiry_date'] ?? null;
            $totalActualQty    = 0.0;

            foreach ($validated['outputs'] as $key => $row) {
                $isNew = is_string($key) && str_starts_with($key, 'new_');

                if ($isNew) {
                    $variantForNew = ProductVariant::find($row['product_variant_id']);
                    $output = ProductionOrderOutput::create([
                        'production_order_id' => $productionOrder->id,
                        'product_variant_id'  => $row['product_variant_id'],
                        'planned_quantity'    => $row['actual_quantity'],
                        'actual_quantity'     => 0,
                        'defective_quantity'  => 0,
                        'unit'                => $row['unit'] ?? $variantForNew?->weight_unit ?? 'unit',
                        'production_cost'     => 0,
                        'selling_price'       => 0,
                        'inventory_strategy'  => $row['inventory_strategy'],
                    ]);
                    $oldActual = 0.0;
                } else {
                    $output = ProductionOrderOutput::where('production_order_id', $productionOrder->id)
                        ->where('id', (int) $key)->first();
                    if (!$output) throw new \Exception("Output not found: {$key}");
                    $oldActual = (float) $output->actual_quantity;
                }

                $variant = $output->productVariant;
                if (!$variant) throw new \Exception("Product variant not found for output {$output->id}");

                $newActual          = (float) $row['actual_quantity'];
                $quantityDifference = $newActual - $oldActual;
                $defectiveQuantity  = (float) ($row['defective_quantity'] ?? 0);

                $unit = $row['unit'] ?? $variant->weight_unit ?? $output->unit;
                if (!$unit) {
                    throw new \Exception(
                        "Cannot resolve a unit of measure for '{$variant->name}' (output #{$output->id})."
                    );
                }

                // Persist production_cost (the per-unit cost the user confirmed)
                $perUnitCost = (float) ($row['production_cost'] ?? 0);

                $output->update([
                    'actual_quantity'    => $newActual,
                    'defective_quantity' => $defectiveQuantity,
                    'batch_number'       => $batchNumber,
                    'expiry_date'        => $expiryDate,
                    'inventory_strategy' => $row['inventory_strategy'],
                    'unit'               => $unit,
                    'production_cost'    => $perUnitCost,
                ]);

                // Sync the cost back to the variant record (only if > 0)
                if ($perUnitCost > 0) {
                    $variant->grand_total_cost_price = $perUnitCost;
                    $variant->supplier_cost_price    = $perUnitCost;

                    if ((float) $variant->selling_price > 0) {
                        $variant->markup_percentage =
                            (($variant->selling_price - $perUnitCost) / max($perUnitCost, 0.0001)) * 100;
                    }

                    $variant->save();

                    try {
                        $recorder  = app('inventory-pricing-history');
                        $oldValues = $recorder->snapshot($variant);
                        $recorder->record(
                            $variant,
                            $oldValues,
                            \App\Models\InventoryPricingHistory::CHANGE_UPDATED,
                            sprintf(
                                'Cost updated from production %s (output #%s). New cost: %s.',
                                $productionOrder->production_number,
                                $output->id,
                                number_format($perUnitCost, 2)
                            )
                        );
                    } catch (\Throwable $e) {
                        \Log::warning('[Production] Pricing history record failed: ' . $e->getMessage());
                    }
                }

                if ($quantityDifference <= 0) continue;

                $this->allocateProductionOutput(
                    $variant,
                    $output,
                    $productionOrder,
                    $quantityDifference,
                    $batchNumber,
                    $expiryDate,
                    $isSingleShop,
                    $allocLocationId,
                    $allocDepartmentId
                );

                $totalActualQty += $quantityDifference;
            }

            // ── 3. Delete outputs removed from the modal ──
            $stillKeptIds = array_map('intval', array_filter(
                array_keys($validated['outputs']),
                fn($k) => !is_string($k) || !str_starts_with((string)$k, 'new_')
            ));

            $orphanQuery = ProductionOrderOutput::where('production_order_id', $productionOrder->id);
            if (!empty($stillKeptIds)) {
                $orphanQuery->whereNotIn('id', $stillKeptIds);
            }
            $orphanQuery->where('actual_quantity', 0)->delete();

            // ── 4. Record the payment withdrawal (Trusting frontend total) ──
            if ($totalProductionCost > 0 && $paymentMethodId) {
                $paymentMethod = PaymentMethod::findForTenant($paymentMethodId, $tenantId);
                if (!$paymentMethod) throw new \Exception(__('pagination.payment_method_not_found'));

                $actor         = $this->actorLabel($user);
                $currency      = $paymentMethod->currency->code ?? 'UGX';
                $amountStr     = number_format($totalProductionCost, 2) . ' ' . $currency;
                $methodName    = $paymentMethod->name ?? "Method #{$paymentMethod->id}";

                $narrative = "{$actor} paid {$amountStr} from {$methodName} to complete production "
                    . "{$productionOrder->production_number}"
                    . $this->productionContextLabel($productionOrder) . ". "
                    . "This withdrawal settles the cost of goods produced.";

                app('payment-transaction')->recordTransaction([
                    'user_id'              => $user->id,
                    'tenant_id'            => $tenantId,
                    'payment_method_id'    => $paymentMethod->id,
                    'transaction_type'     => 'WITHDRAWAL',
                    'transaction_category' => 'EXPENSE',
                    'amount'               => $totalProductionCost,
                    'currency_id'          => $paymentMethod->currency_id ?? Currency::default()->id,
                    'reference_table'      => 'production_orders',
                    'reference_id'         => $productionOrder->id,
                    'description'          => $narrative,
                    'notes'                => "Production completion payment — {$methodName}",
                    'metadata' => [
                        'production_number'  => $productionOrder->production_number,
                        'actual_cost'        => $totalProductionCost,
                        'payment_method'     => $methodName,
                        'transaction_nature' => 'PRODUCTION_COMPLETION_PAYMENT',
                        'narrative'          => $narrative,
                    ],
                ]);

                $paymentMethod->current_balance -= $totalProductionCost;
                $paymentMethod->save();

                // Track payment on the order
                $productionOrder->payment_method_id = $paymentMethod->id;
                $productionOrder->save();
            }

            // ── 5. Update order notes + mark complete ─────────────────
            if (!empty($validated['notes'])) {
                $productionOrder->notes = ($productionOrder->notes
                    ? $productionOrder->notes . "\n"
                    : '') . $validated['notes'];
            }

            $productionOrder->complete();  // existing helper

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => __('passwords.production_completed'),
                'reload'      => true,
                'componentId' => 'reloadProductionComponent',
                'redirect'    => route('production-orders.index'),
                'data' => [
                    'total_output_quantity' => $productionOrder->outputs->sum('actual_quantity'),
                    'total_production_cost' => $totalProductionCost,
                    'withdrawal_amount'     => $totalProductionCost,
                    'batch_number'          => $batchNumber,
                    'expiry_date'           => $expiryDate,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Production completion failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'production_order_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Allocate a newly produced quantity of a variant.
     *
     * - Always bumps the tenant-wide tally (variant.overal_quantity_at_hand).
     * - Single-shop: logs to SingleShopInventoryLog.
     * - Multi-shop: creates/finds an InventoryItems row for (variant, location, department),
     *   increments quantity_allocated, logs to InventoryAdjustments + InventoryTransactions.
     * - For batch strategy: creates a PurchaseReceiptItem batch tied to the right location.
     */
    private function allocateProductionOutput(
        ProductVariant $variant,
        ProductionOrderOutput $output,
        ProductionOrder $productionOrder,
        float $quantity,
        string $batchNumber,
        ?string $expiryDate,
        bool $isSingleShop,
        ?int $allocLocationId,
        ?int $allocDepartmentId
    ): void {
        $beforeOverall = (float) ($variant->overal_quantity_at_hand ?? 0);
        $afterOverall  = $beforeOverall + $quantity;

        $actor   = $this->actorLabel();
        $context = $this->productionContextLabel($productionOrder);

        $variant->overal_quantity_at_hand = $afterOverall;
        $variant->save();

        // ── Single-shop ───────────────────────────────────────────────
        if ($isSingleShop) {

            $narrative = sprintf(
                '%s produced %s × "%s" in production %s%s. '
                . 'Overall stock: %s → %s. Output allocated to the single-shop pool.',
                $actor,
                number_format($quantity, 2),
                $variant->name,
                $productionOrder->production_number,
                $context,
                number_format($beforeOverall, 2),
                number_format($afterOverall, 2)
            );
            SingleShopInventoryLog::create([
                'variant_id'      => $variant->id,
                'order_id'        => $productionOrder->id,
                'tenant_id'       => $productionOrder->tenant_id,
                'created_by'      => auth()->id(),
                'quantity_before' => $beforeOverall,
                'quantity_after'  => $afterOverall,
                'quantity_change' => $quantity,
                'reason'          => 'production_output',
                'notes'           => $narrative,
                'source'          => 'production',
                'metadata'        => [
                    'mode'              => 'single_shop',
                    'production_number' => $productionOrder->production_number,
                    'output_id'         => $output->id,
                    'batch_number'      => $batchNumber,
                    'expiry_date'       => $expiryDate,
                    'narrative' => $narrative,
                ],
            ]);
        }
        // ── Multi-shop ────────────────────────────────────────────────
        else {
            $item = InventoryItems::firstOrCreate(
                [
                    'variant_id'    => $variant->id,
                    'location_id'   => $allocLocationId,
                    'department_id' => $allocDepartmentId,
                    'tenant_id'     => $productionOrder->tenant_id,
                ],
                [
                    'quantity_allocated' => 0,
                    'quantity_on_hand'   => 0,
                    'created_by'         => auth()->id(),
                ]
            );

            $beforeAlloc = (int) $item->quantity_allocated;
            $item->quantity_allocated = $beforeAlloc + (int) $quantity;
            $item->save();

            $locName  = $item->itemLocation?->name ?? "Location #{$item->location_id}";
            $deptName = $item->departmentItem?->name ?? "Department #{$item->department_id}";

            $narrative = sprintf(
                '%s produced %s × "%s" in production %s%s and allocated it to %s (%s). '
                . 'Branch allocation: %s → %s units. Overall stock: %s → %s.',
                $actor,
                number_format($quantity, 2),
                $variant->name,
                $productionOrder->production_number,
                $context,
                $locName,
                $deptName,
                number_format($beforeAlloc),
                number_format($item->quantity_allocated),
                number_format($beforeOverall, 2),
                number_format($afterOverall, 2)
            );

            InventoryAdjustments::create([
                'inventory_id'    => $item->id,
                'quantity_before' => $beforeAlloc,
                'quantity_after'  => $item->quantity_allocated,
                'reason'          => 'production_output',
                'notes'           => $narrative,
                'created_by'      => auth()->id(),
                'tenant_id'       => $productionOrder->tenant_id,
            ]);

            InventoryTransactions::create([
                'inventory_id'   => $item->id,
                'quantity'       => (int) $quantity,
                'reference_id'   => $productionOrder->id,
                'reference_type' => 'production_order',
                'type'           => 'transfer_in',
                'notes'          => $narrative,
                'created_by'     => auth()->id(),
                'tenant_id'      => $productionOrder->tenant_id,
            ]);
        }

        // ── Batch strategy: create the batch record ───────────────────
        if ($output->inventory_strategy === 'batch') {
            $unitCost = $output->production_cost > 0
                ? $output->production_cost / max(1, $output->actual_quantity)
                : 0;

            $batch = PurchaseReceiptItem::create([
                'purchase_receipt_id'    => null,
                'purchase_order_item_id' => null,
                'quantity_received'      => $quantity,
                'quantity_remaining'     => $quantity,
                'unit_cost'              => $unitCost,
                'batch_number'           => $batchNumber,
                'expiry_date'            => $expiryDate,
                'location_id'            => $isSingleShop ? $productionOrder->location_id : $allocLocationId,
                'department_id'          => $isSingleShop ? null                        : $allocDepartmentId,
                'tenant_id'              => $productionOrder->tenant_id,
                'notes'                  => "Produced from production #{$productionOrder->production_number}",
            ]);

            // ✅ Resolve which purchase orders fed this production run so we can
            //    carry the provenance forward onto the produced batch.
            $sourcePOs = $this->resolveSourcePurchaseOrders($productionOrder);

            $narrative = sprintf(
                '%s created %s with %s × "%s" produced in production %s%s. '
                . 'Batch starts at %s units. Cost per unit: %s.',
                $actor,
                $this->batchLabel($batch),
                number_format($quantity, 2),
                $variant->name,
                $productionOrder->production_number,
                $context,
                number_format($quantity, 2),
                number_format($unitCost, 2)
            );

            BatchLog::create([
                'batch_id'                    => $batch->id,
                'batch_number'                => $batch->batch_number,
                'variant_id'                  => $variant->id,
                'variant_name'                => $variant->name,
                'variant_sku'                 => $variant->sku,
                'type'                        => BatchLog::TYPE_PRODUCED,
                'quantity_change'             => $quantity,
                'quantity_before'             => 0,
                'quantity_after'              => $quantity,
                'unit_cost'                   => $unitCost,
                'total_cost'                  => $unitCost * $quantity,
                'production_order_id'         => $productionOrder->id,
                'production_order_output_id'  => $output->id,
                'tenant_id'                   => $productionOrder->tenant_id,
                'location_id'                 => $isSingleShop ? $productionOrder->location_id : $allocLocationId,
                'department_id'               => $isSingleShop ? null                        : $allocDepartmentId,
                'expiry_date'                 => $expiryDate,
                'event_date'                  => now(),
                'performed_by'                => auth()->id(),
                'metadata' => [
                    'production_number'       => $productionOrder->production_number,
                    'batch_generated'         => true,
                    'narrative'              => $narrative,
                    'source_purchase_orders'  => $sourcePOs,
                    'source_po_ids'           => collect($sourcePOs)->pluck('po_id')->filter()->unique()->values()->all(),
                ],
            ]);
        }
    }

    /**
     * Which purchase orders contributed to this production order?
     *
     * Reads the batch logs of inputs that have already been consumed by this
     * production order (type = depleted, purchase_order_id not null) and
     * returns a deduplicated list with PO numbers and suppliers.
     *
     * Returns [] when the inputs weren't batched or came from stock that
     * never had a PO.
     */
    private function resolveSourcePurchaseOrders(ProductionOrder $productionOrder): array
    {
        return BatchLog::query()
            ->where('production_order_id', $productionOrder->id)
            ->where('type', BatchLog::TYPE_DEPLETED)
            ->whereNotNull('purchase_order_id')
            ->get()
            ->map(fn($log) => [
                'po_id'       => $log->purchase_order_id,
                'po_number'   => $log->purchase_order_number,
                'supplier_id' => $log->supplier_id,
                'supplier'    => $log->supplier_name,
                'quantity'    => abs((float) $log->quantity_change),
            ])
            ->groupBy('po_id')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'po_id'       => $first['po_id'],
                    'po_number'   => $first['po_number'],
                    'supplier_id' => $first['supplier_id'],
                    'supplier'    => $first['supplier'],
                    'quantity'    => $rows->sum('quantity'),
                ];
            })
            ->values()
            ->all();
    }
        

    public function cancel(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('cancel production_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $productionOrder = ProductionOrder::where('tenant_id', $tenantId)->findOrFail($id);

        if ($productionOrder->status === ProductionOrder::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.cannot_cancel_completed_order'),
            ]);
        }

        try {
            $productionOrder->cancel();
            
            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadProductionComponent',
                'message' => __('passwords.production_cancelled'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updateOutputQuantity(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $validated = $request->validate([
            'output_id' => 'required|exists:production_order_outputs,id',
            'actual_quantity' => 'required|numeric|min:0',
            'defective_quantity' => 'nullable|numeric|min:0',
        ]);

        $output = ProductionOrderOutput::where('production_order_id', $id)
            ->whereHas('productionOrder', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->findOrFail($validated['output_id']);

        $productionOrder = $output->productionOrder;

        if ($productionOrder->status !== ProductionOrder::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_update_in_progress'),
            ]);
        }

        $output->update([
            'actual_quantity' => $validated['actual_quantity'],
            'defective_quantity' => $validated['defective_quantity'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('passwords.output_quantity_updated'),
            'output' => $output,
        ]);
    }


    public function getAvailableBatches(Request $request)
    {
        $request->validate([
            'variant_id'  => 'required|integer|exists:product_variants,id',
            'location_id' => 'nullable|integer|exists:locations,id',
        ]);

        $user       = Auth::user();
        $tenantId   = $user->tenant_id;
        $variantId  = $request->variant_id;
        $locationId = $request->location_id ?? $user->location_id;

        $batches = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->where('purchase_orders.tenant_id', $tenantId)
            ->where('purchase_order_items.product_variant_id', $variantId)
            ->where(function ($q) {
                $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                ->orWhereNull('purchase_receipt_items.quantity_remaining');
            })
            ->when($locationId, function ($q) use ($locationId) {
                return $q->where('purchase_receipt_items.location_id', $locationId);
            })
            ->orderByRaw('purchase_receipt_items.expiry_date IS NULL, purchase_receipt_items.expiry_date ASC')
            ->select('purchase_receipt_items.*')
            ->get();

        return response()->json([
            'success' => true,
            'batches' => $batches->map(function ($batch) {
                // Prefer quantity_remaining; fall back to quantity_received; finally 0
                $remaining = $batch->quantity_remaining
                    ?? $batch->quantity_received
                    ?? 0;

                return [
                    'id'                 => $batch->id,
                    'batch_number'       => $batch->batch_number,
                    'quantity_remaining' => (float) $remaining,
                    'quantity_received'  => (float) ($batch->quantity_received ?? 0),
                    'expiry_date'        => $batch->expiry_date?->format('Y-m-d'),
                    'location_id'        => $batch->location_id,
                    // NOTE: unit_cost intentionally omitted per your request
                ];
            }),
        ]);
    }


    public function updateOutput(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit production_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $productionOrder = ProductionOrder::where('tenant_id', $tenantId)
            ->with(['inputs', 'outputs.productVariant'])
            ->findOrFail($id);

        if ($productionOrder->status !== ProductionOrder::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_update_in_progress'),
            ]);
        }

        $validated = $request->validate([
            'outputs' => 'required|array|min:1',
            'outputs.*.output_id' => 'required|exists:production_order_outputs,id',
            'outputs.*.actual_quantity' => 'required|numeric|min:0',
            'outputs.*.defective_quantity' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $totalActualQuantity = 0;

            foreach ($validated['outputs'] as $outputData) {
                $output = ProductionOrderOutput::where('production_order_id', $id)
                    ->where('id', $outputData['output_id'])
                    ->first();

                if (!$output) {
                    throw new \Exception("Output not found");
                }

                $variant = $output->productVariant;
                if (!$variant) {
                    throw new \Exception("Product variant not found for output");
                }

                // ✅ Get the difference between new actual and old actual
                $oldActual = $output->actual_quantity;
                $newActual = $outputData['actual_quantity'];
                $quantityDifference = $newActual - $oldActual;
                $defectiveQuantity = $outputData['defective_quantity'] ?? 0;

                // ✅ Only update inventory if there's a positive difference
                if ($quantityDifference > 0) {
                    // ✅ Update master stock (overal_quantity_at_hand)
                    $beforeQty = $variant->overal_quantity_at_hand ?? 0;
                    $afterQty = $beforeQty + $quantityDifference;
                    
                    $variant->overal_quantity_at_hand = $afterQty;
                    $variant->save();

                    $narrative = sprintf(
                        '%s increased output of "%s" by %s for production %s%s. '
                        . 'Overall stock: %s → %s. Output row: #%s.',
                        $this->actorLabel(),
                        $variant->name,
                        number_format($quantityDifference, 2),
                        $productionOrder->production_number,
                        $this->productionContextLabel($productionOrder),
                        number_format($beforeQty, 2),
                        number_format($afterQty, 2),
                        $output->id
                    );


                    // ✅ Audit trail - SingleShopInventoryLog
                    SingleShopInventoryLog::create([
                        'variant_id' => $variant->id,
                        'order_id' => $productionOrder->id,
                        'tenant_id' => $productionOrder->tenant_id,
                        'created_by' => auth()->id(),
                        'quantity_before' => $beforeQty,
                        'quantity_after' => $afterQty,
                        'quantity_change' => $quantityDifference,
                        'reason' => 'production_output_update',
                        'notes' => "Production output updated - Order #{$productionOrder->production_number} - {$variant->name}",
                        'source' => 'production',
                        'notes'     => $narrative,
                        'metadata' => [
                            'production_order_id' => $productionOrder->id,
                            'production_number' => $productionOrder->production_number,
                            'output_id' => $output->id,
                            'quantity_added' => $quantityDifference,
                            'cost' => $output->production_cost,
                            'inventory_strategy' => $output->inventory_strategy,
                            'narrative' => $narrative,
                        ],
                    ]);

                    $totalActualQuantity += $quantityDifference;

                    // \Log::info('[Production] Output inventory updated', [
                    //     'production_order_id' => $productionOrder->id,
                    //     'production_number' => $productionOrder->production_number,
                    //     'variant_id' => $variant->id,
                    //     'variant_name' => $variant->name,
                    //     'quantity_added' => $quantityDifference,
                    //     'total_actual' => $newActual,
                    //     'old_actual' => $oldActual,
                    //     'before_qty' => $beforeQty,
                    //     'after_qty' => $afterQty,
                    // ]);
                }

                // ✅ Update output record
                $output->update([
                    'actual_quantity' => $newActual,
                    'defective_quantity' => $defectiveQuantity,
                ]);
            }

            // ✅ Update production order totals
            $totalOutputQuantity = $productionOrder->outputs->sum('actual_quantity');
            $productionOrder->update([
                'total_output_quantity' => $totalOutputQuantity,
                'total_output_cost' => $productionOrder->outputs->sum('production_cost'),
                'total_cost' => ($productionOrder->inputs->sum('actual_cost') ?? 0) + $productionOrder->outputs->sum('production_cost'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('passwords.output_updated_successfully'),
                'data' => [
                    'total_output_quantity' => $totalOutputQuantity,
                    'inventory_updated' => $totalActualQuantity > 0,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update output: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'production_order_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    

    public function start(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('start production_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $productionOrder = ProductionOrder::where('tenant_id', $tenantId)->findOrFail($id);

        if ($productionOrder->status !== ProductionOrder::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_start_draft_orders'),
            ]);
        }

        try {
            $productionOrder->start();
            
            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'reloadProductionComponent',
                'message' => __('passwords.production_started'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }


        
    /**
     * Show the edit modal data for a production order
     */
    public function edit(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit production_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $productionOrder = ProductionOrder::where('tenant_id', $tenantId)
            ->with([
                'inputs.productVariant.product',
                'inputs.purchaseReceiptItem',
                'outputs.productVariant.product',
                'location',
            ])
            ->findOrFail($id);

        // Can only edit draft or in_progress orders
        if (!in_array($productionOrder->status, [
            ProductionOrder::STATUS_DRAFT,
            ProductionOrder::STATUS_IN_PROGRESS,
        ])) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_edit_draft_or_in_progress'),
            ]);
        }

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->with('product')
            ->get();

        $uoms = \App\Models\UnitOfMeasure::where('tenant_id', $tenantId)
            ->orWhereNull('tenant_id')
            ->get();

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $productionOrder->id,
                'production_number' => $productionOrder->production_number,
                'status' => $productionOrder->status,
                'location_id' => $productionOrder->location_id,
                'location_name' => $productionOrder->location->name ?? null,
                'scheduled_date' => $productionOrder->scheduled_date?->format('Y-m-d\TH:i'),
                'notes' => $productionOrder->notes,
                'estimated_cost' => $productionOrder->estimated_cost,
                'inputs' => $productionOrder->inputs->map(function ($input) {
                    return [
                        'id' => $input->id,
                        'product_variant_id' => $input->product_variant_id,
                        'product_variant_name' => $input->productVariant->name ?? null,
                        'product_variant_sku' => $input->productVariant->sku ?? null,
                        'planned_quantity' => $input->planned_quantity,
                        'actual_quantity' => $input->actual_quantity,
                        'unit' => $input->unit,
                        'estimated_cost' => $input->estimated_cost,
                        'actual_cost' => $input->actual_cost,
                        'purchase_receipt_item_id' => $input->purchase_receipt_item_id,
                        'batch_number' => $input->purchaseReceiptItem->batch_number ?? null,
                        'batch_quantity_remaining' => $input->purchaseReceiptItem->quantity_remaining ?? null,
                        'is_consumed' => $input->actual_quantity > 0,
                    ];
                }),
                'outputs' => $productionOrder->outputs->map(function ($output) {
                    return [
                        'id' => $output->id,
                        'product_variant_id' => $output->product_variant_id,
                        'product_variant_name' => $output->productVariant->name ?? null,
                        'product_variant_sku' => $output->productVariant->sku ?? null,
                        'planned_quantity' => $output->planned_quantity,
                        'weight' => $output->productVariant->weight ?? 1, 
                        'actual_quantity' => $output->actual_quantity,
                        'defective_quantity' => $output->defective_quantity,
                        'unit' => $output->unit,
                        'production_cost' => $output->production_cost,
                        'selling_price' => $output->selling_price,
                        'inventory_strategy' => $output->inventory_strategy,
                        'batch_number' => $output->batch_number,
                        'expiry_date' => $output->expiry_date?->format('Y-m-d'),
                    ];
                }),
            ],
            'variants' => $variants->map(fn($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'sku' => $v->sku,
                'weight_unit' => $v->weight_unit,
                'supplier_cost_price' => $v->supplier_cost_price,
                'selling_price' => $v->selling_price,
                'inventory_strategy' => $v->product?->resolvedInventoryStrategy() ?? 'quantity',
            ]),
            'uoms' => $uoms->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'symbol' => $u->symbol,
            ]),
        ]);
    }

    /**
     * Update the production order - handles both inputs and outputs
     * with full inventory reconciliation
     */
    public function update(Request $request, $id)
    {
        // \Log::info('[ProductionUpdate] Incoming payload', [
        //     'production_order_id' => $id,
        //     'inputs_count'        => count($request->input('inputs', [])),
        //     'outputs_count'       => count($request->input('outputs', [])),
        //     'inputs'              => $request->input('inputs', []),
        //     'outputs'             => $request->input('outputs', []),
        //     'raw_output_keys'     => array_keys($request->input('outputs', [])),
        // ]);

        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit production_orders')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $productionOrder = ProductionOrder::where('tenant_id', $tenantId)
            ->with(['inputs.purchaseReceiptItem', 'outputs'])
            ->findOrFail($id);

        if (!in_array($productionOrder->status, [
            ProductionOrder::STATUS_COMPLETED,
            ProductionOrder::STATUS_DRAFT,
            ProductionOrder::STATUS_IN_PROGRESS,
        ])) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.can_only_edit_draft_or_in_progress'),
            ]);
        }

        // ── Pre-process: auto-fill unit from variant if missing ─────────────
        $inputsPayload = $request->input('inputs', []);
        foreach ($inputsPayload as $key => &$row) {
            if (empty($row['unit']) && !empty($row['product_variant_id'])) {
                $v = ProductVariant::find($row['product_variant_id']);
                if ($v && $v->weight_unit) {
                    $row['unit'] = (string) $v->weight_unit;
                }
            }
        }
        unset($row);
        $request->merge(['inputs' => $inputsPayload]);

        $outputsPayload = $request->input('outputs', []);
        foreach ($outputsPayload as $key => &$row) {
            if (empty($row['unit']) && !empty($row['product_variant_id'])) {
                $v = ProductVariant::find($row['product_variant_id']);
                if ($v && $v->weight_unit) {
                    $row['unit'] = (string) $v->weight_unit;
                }
            }
        }
        unset($row);
        $request->merge(['outputs' => $outputsPayload]);

        $validated = $request->validate([
            'scheduled_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            
            // Inputs validation
            'inputs' => 'required|array|min:1',
            'inputs.*.id' => 'nullable|integer|exists:production_order_inputs,id',
            'inputs.*.product_variant_id' => [
                'required',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) use ($request) {
                    $variant = ProductVariant::with('product')->find($value);
                    if (!$variant) return;
                    $strategy = $variant->product?->resolvedInventoryStrategy();
                    if ($strategy === 'batch') {
                        $index = explode('.', $attribute)[1] ?? null;
                        $batchId = $request->input("inputs.{$index}.purchase_receipt_item_id");
                        if (empty($batchId)) {
                            $fail("A batch must be selected for batch-tracked material '{$variant->name}'.");
                        }
                    }
                },
            ],
            'inputs.*.planned_quantity' => 'required|numeric|min:0.01',
            'inputs.*.purchase_receipt_item_id' => 'nullable|exists:purchase_receipt_items,id',
            'inputs.*.unit' => 'required|string|max:20',
            'inputs.*.estimated_cost' => 'nullable|numeric|min:0',
            
            // Outputs validation
            'outputs' => 'required|array|min:1',
            'outputs.*.id' => 'nullable|integer|exists:production_order_outputs,id',
            'outputs.*.product_variant_id' => 'required|exists:product_variants,id',
            'outputs.*.planned_quantity' => 'required|numeric|min:0.01',
            'outputs.*.actual_quantity' => 'nullable|numeric|min:0',
            'outputs.*.defective_quantity' => 'nullable|numeric|min:0',
            'outputs.*.unit' => 'required|string|max:20',
            'outputs.*.production_cost' => 'nullable|numeric|min:0',
            'outputs.*.selling_price' => 'nullable|numeric|min:0',
            'outputs.*.inventory_strategy' => 'required|in:quantity,batch,serial',
            
            // Multi-shop allocation
            'output_location_id' => 'nullable|integer|exists:locations,id',
            'output_department_id' => 'nullable|integer|exists:departments,id',
        ]);

        // Validate duplicate batches
        $batchIds = collect($validated['inputs'])
            ->pluck('purchase_receipt_item_id')
            ->filter()
            ->values();

        $duplicates = $batchIds->duplicates()->unique()->values();
        if ($duplicates->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'The same batch was selected more than once.',
                'errors' => ['inputs' => ['Duplicate batch IDs: ' . $duplicates->implode(', ')]],
            ], 422);
        }

        DB::beginTransaction();

        try {
            // ─────────────────────────────────────────────────────────────
            // STEP 1: Handle INPUT changes (batch reconciliation)
            // ─────────────────────────────────────────────────────────────
            $this->reconcileInputs($productionOrder, $validated['inputs']);

            // ─────────────────────────────────────────────────────────────
            // STEP 2: Handle OUTPUT changes (add/remove/update)
            // ─────────────────────────────────────────────────────────────
            $this->reconcileOutputs($productionOrder, $validated['outputs'], $validated);

            // ─────────────────────────────────────────────────────────────
            // STEP 3: Recalculate totals
            // ─────────────────────────────────────────────────────────────
            $productionOrder->refresh();
            
            $estimatedInputCost = $productionOrder->inputs->sum('estimated_cost');
            $estimatedOutputCost = $productionOrder->outputs->sum('production_cost');
            $totalEstimatedCost = $estimatedInputCost + $estimatedOutputCost;

            $totalInputQuantity = $productionOrder->inputs->sum('actual_quantity');
            $totalOutputQuantity = $productionOrder->outputs->sum('actual_quantity');
            $totalInputCost = $productionOrder->inputs->sum('actual_cost');
            $totalOutputCost = $productionOrder->outputs->sum('production_cost');
            $totalCost = $totalInputCost + $totalOutputCost;

            $productionOrder->update([
                'scheduled_date' => $validated['scheduled_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'estimated_cost' => $totalEstimatedCost,
                'total_input_quantity' => $totalInputQuantity,
                'total_output_quantity' => $totalOutputQuantity,
                'total_input_cost' => $totalInputCost,
                'total_output_cost' => $totalOutputCost,
                'total_cost' => $totalCost,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('passwords.production_order_updated'),
                'reload' => true,
                'componentId' => 'reloadProductionComponent',
                'redirect' => route('production-orders.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Production order update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'production_order_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reconcile input changes - handles batch swaps, quantity changes, additions, removals
     */
    private function reconcileInputs(ProductionOrder $productionOrder, array $newInputs): void
    {
        $existingInputs = $productionOrder->inputs->keyBy('id');
        $processedIds = [];

        foreach ($newInputs as $inputData) {
            $inputId = $inputData['id'] ?? null;
            $newBatchId = $inputData['purchase_receipt_item_id'] ?? null;
            $newQuantity = (float) $inputData['planned_quantity'];
            $variant = ProductVariant::find($inputData['product_variant_id']);
            $newEstimatedCost = $inputData['estimated_cost'] ?? ($variant?->supplier_cost_price ?? 0) * $newQuantity;

            if ($inputId && isset($existingInputs[$inputId])) {
                // ─── UPDATE EXISTING INPUT ─────────────────────────────
                $input = $existingInputs[$inputId];
                $oldBatchId = $input->purchase_receipt_item_id;
                $oldQuantity = (float) $input->actual_quantity; // Already consumed quantity
                $wasConsumed = $oldQuantity > 0;

                // Check if batch changed
                $batchChanged = $oldBatchId != $newBatchId;

                if ($wasConsumed && $batchChanged) {
                    // Restore old batch and consume from new batch
                    $this->handleBatchSwap($input, $oldBatchId, $newBatchId, $oldQuantity, $newQuantity);
                } elseif ($wasConsumed && !$batchChanged && $oldQuantity != $newQuantity) {
                    // Same batch, quantity changed - adjust batch
                    $this->handleQuantityAdjustment($input, $newQuantity, $oldQuantity);
                }

                // Update the input record
                $input->update([
                    'product_variant_id' => $inputData['product_variant_id'],
                    'purchase_receipt_item_id' => $newBatchId,
                    'planned_quantity' => $newQuantity,
                    'unit' => $inputData['unit'],
                    'estimated_cost' => $newEstimatedCost,
                ]);

                $processedIds[] = $inputId;

            } else {
                // ─── CREATE NEW INPUT ──────────────────────────────────
                $newInput = ProductionOrderInput::create([
                    'production_order_id' => $productionOrder->id,
                    'product_variant_id' => $inputData['product_variant_id'],
                    'purchase_receipt_item_id' => $newBatchId,
                    'planned_quantity' => $newQuantity,
                    'actual_quantity' => 0,
                    'waste_quantity' => 0,
                    'unit' => $inputData['unit'],
                    'estimated_cost' => $newEstimatedCost,
                    'actual_cost' => 0,
                ]);

                // If order is in progress, consume immediately
                if ($productionOrder->status === ProductionOrder::STATUS_IN_PROGRESS) {
                    $this->consumeInput($newInput, $productionOrder);
                }

                $processedIds[] = $newInput->id;
            }
        }

        // ─── DELETE REMOVED INPUTS ─────────────────────────────────────
        $keptInputIds = collect($newInputs)
            ->pluck('id')
            ->filter(fn($id) => $id !== null && $id !== '' && $id !== 'null')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $toDeleteInputs = $existingInputs->keys()->diff($keptInputIds);

        foreach ($toDeleteInputs as $deleteId) {
            $input = $existingInputs[$deleteId] ?? null;
            if (!$input) continue;

            // Restore consumed stock before deletion
            if ((float) $input->actual_quantity > 0) {
                $this->restoreInputStock($input, $productionOrder);
            }

            $input->delete();
        }
        
    }

    /**
     * Handle batch swap for an already-consumed input
     */
    private function handleBatchSwap(
        ProductionOrderInput $input,
        ?int $oldBatchId,
        ?int $newBatchId,
        float $oldQuantity,
        float $newQuantity
    ): void {
        $variant = $input->productVariant;
        if (!$variant) return;

        $productionOrder = $input->productionOrder;
        $tenantId = $productionOrder->tenant_id;

        // ─── RESTORE OLD BATCH ─────────────────────────────────────────
        if ($oldBatchId) {
            $oldBatch = PurchaseReceiptItem::where('id', $oldBatchId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($oldBatch) {
                $beforeRestore = (float) ($oldBatch->quantity_remaining ?? 0);
                $afterRestore = $beforeRestore + $oldQuantity;
                
                $oldBatch->quantity_remaining = $afterRestore;
                $oldBatch->save();

                $narrative = sprintf(
                    '%s restored %s × "%s" to %s during batch swap on production %s%s. '
                    . 'Batch stock: %s → %s. The original batch was swapped out.',
                    $actor,
                    number_format($oldQuantity, 2),
                    $variant->name,
                    $this->batchLabel($oldBatch),
                    $productionN°,
                    $context,
                    number_format($beforeRestore, 2),
                    number_format($afterRestore, 2)
                );

                // Log the restoration
                BatchLog::create([
                    'batch_id' => $oldBatch->id,
                    'batch_number' => $oldBatch->batch_number,
                    'variant_id' => $variant->id,
                    'variant_name' => $variant->name,
                    'variant_sku' => $variant->sku,
                    'type' => BatchLog::TYPE_ADJUSTED,
                    'quantity_change' => $oldQuantity,
                    'quantity_before' => $beforeRestore,
                    'quantity_after' => $afterRestore,
                    'unit_cost' => $oldBatch->unit_cost ?? 0,
                    'total_cost' => ($oldBatch->unit_cost ?? 0) * $oldQuantity,
                    'production_order_id' => $productionOrder->id,
                    'production_order_input_id' => $input->id,
                    'tenant_id' => $tenantId,
                    'location_id' => $oldBatch->location_id ?? $productionOrder->location_id,
                    'event_date' => now(),
                    'performed_by' => auth()->id(),
                    'metadata' => [
                        'source' => 'production_input_batch_swap_restore',
                        'production_number' => $productionOrder->production_number,
                        'reason' => 'Batch swapped to different batch',
                        'new_batch_id' => $newBatchId,
                        'narrative' => $narrative,
                    ],
                ]);
            }
        }

        // Restore overall quantity for old batch consumption
        $this->incrementOverallQuantity(
            $variant,
            $oldQuantity,
            $input,
            $productionOrder,
            'production_input_batch_swap_restore'
        );

        // ─── CONSUME NEW BATCH ─────────────────────────────────────────
        if ($newBatchId) {
            $newBatch = PurchaseReceiptItem::where('id', $newBatchId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$newBatch) {
                throw new \Exception("New batch not found: {$newBatchId}");
            }

            $available = (float) ($newBatch->quantity_remaining ?? $newBatch->quantity_received ?? 0);
            if ($available < $newQuantity) {
                throw new \Exception(
                    "Insufficient stock in new batch for {$variant->name}. " .
                    "Available: {$available}, Required: {$newQuantity}"
                );
            }

            $beforeConsume = $available;
            $afterConsume = $beforeConsume - $newQuantity;

            $newBatch->quantity_remaining = $afterConsume;
            $newBatch->save();

            // Log the consumption
            $origin = $this->resolveBatchOrigin($newBatch);
            $narrative = sprintf(
                '%s consumed %s × "%s" from %s during batch swap on production %s%s. '
                . 'Batch stock: %s → %s. Replacing the previous batch.',
                $actor,
                number_format($newQuantity, 2),
                $variant->name,
                $this->batchLabel($newBatch),
                $productionN°,
                $context,
                number_format($beforeConsume, 2),
                number_format($afterConsume, 2)
            );

            BatchLog::create(array_merge([
                'batch_id' => $newBatch->id,
                'batch_number' => $newBatch->batch_number,
                'variant_id' => $variant->id,
                'variant_name' => $variant->name,
                'variant_sku' => $variant->sku,
                'type' => BatchLog::TYPE_DEPLETED,
                'quantity_change' => -$newQuantity,
                'quantity_before' => $beforeConsume,
                'quantity_after' => $afterConsume,
                'unit_cost' => $newBatch->unit_cost ?? 0,
                'total_cost' => ($newBatch->unit_cost ?? 0) * $newQuantity,
                'production_order_id' => $productionOrder->id,
                'production_order_input_id' => $input->id,
                'tenant_id' => $tenantId,
                'location_id' => $newBatch->location_id ?? $productionOrder->location_id,
                'department_id' => $newBatch->department_id,
                'expiry_date' => $newBatch->expiry_date,
                'event_date' => now(),
                'performed_by' => auth()->id(),
                'metadata' => [
                    'source' => 'production_input_batch_swap_consume',
                    'production_number' => $productionOrder->production_number,
                    'reason' => 'Batch swapped from different batch',
                    'old_batch_id' => $oldBatchId,
                    'narrative' => $narrative,
                ],
            ], $origin));

            // Update overall quantity
            $this->decrementOverallQuantity(
                $variant,
                $newQuantity,
                $input,
                $productionOrder,
                'production_input_batch_swap_consume'
            );
        }

        // Update the input's actual quantity to match new consumption
        $input->update([
            'actual_quantity' => $newQuantity,
            'actual_cost' => $input->estimated_cost,
        ]);
    }

    /**
     * Handle quantity adjustment for an already-consumed input (same batch)
     */
    private function handleQuantityAdjustment(
        ProductionOrderInput $input,
        float $newQuantity,
        float $oldQuantity
    ): void {
        $variant = $input->productVariant;
        if (!$variant) return;

        $productionOrder = $input->productionOrder;
        $difference = $newQuantity - $oldQuantity;

        if (abs($difference) < 0.0001) return; // No change

        if ($difference > 0) {
            // Need to consume MORE from the batch
            $this->consumeAdditionalQuantity($input, $variant, $difference);
        } else {
            // Need to restore SOME to the batch
            $this->restorePartialQuantity($input, $variant, abs($difference));
        }

        // Update actual quantity on input
        $input->update([
            'actual_quantity' => $newQuantity,
            'actual_cost' => $input->estimated_cost,
        ]);
    }

    /**
     * Consume additional quantity from a batch
     */
    private function consumeAdditionalQuantity(
        ProductionOrderInput $input,
        ProductVariant $variant,
        float $additionalQuantity
    ): void {
        $productionOrder = $input->productionOrder;
        $tenantId = $productionOrder->tenant_id;
        $batchId = $input->purchase_receipt_item_id;

        if ($batchId) {
            $batch = PurchaseReceiptItem::where('id', $batchId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$batch) {
                throw new \Exception("Batch not found for additional consumption");
            }

            $available = (float) ($batch->quantity_remaining ?? 0);
            if ($available < $additionalQuantity) {
                throw new \Exception(
                    "Insufficient batch stock for additional consumption. " .
                    "Available: {$available}, Required: {$additionalQuantity}"
                );
            }

            $before = $available;
            $after = $before - $additionalQuantity;

            $batch->quantity_remaining = $after;
            $batch->save();
            $narrative = sprintf(
                '%s consumed an additional %s × "%s" from %s for production %s%s '
                . '(input quantity increased). Batch stock: %s → %s.',
                $this->actorLabel(),
                number_format($additionalQuantity, 2),
                $variant->name,
                $this->batchLabel($batch),
                $productionOrder->production_number,
                $this->productionContextLabel($productionOrder),
                number_format($before, 2),
                number_format($after, 2)
            );

            BatchLog::create([
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'variant_id' => $variant->id,
                'variant_name' => $variant->name,
                'variant_sku' => $variant->sku,
                'type' => BatchLog::TYPE_DEPLETED,
                'quantity_change' => -$additionalQuantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => $batch->unit_cost ?? 0,
                'total_cost' => ($batch->unit_cost ?? 0) * $additionalQuantity,
                'production_order_id' => $productionOrder->id,
                'production_order_input_id' => $input->id,
                'tenant_id' => $tenantId,
                'location_id' => $batch->location_id ?? $productionOrder->location_id,
                'event_date' => now(),
                'performed_by' => auth()->id(),
                'metadata' => [
                    'source' => 'production_input_quantity_increase',
                    'production_number' => $productionOrder->production_number,
                    'narrative' => $narrative,
                ],
            ]);

            $this->decrementOverallQuantity(
                $variant,
                $additionalQuantity,
                $input,
                $productionOrder,
                'production_input_quantity_increase'
            );
        } else {
            // No specific batch - just decrement overall
            $this->decrementOverallQuantity(
                $variant,
                $additionalQuantity,
                $input,
                $productionOrder,
                'production_input_quantity_increase'
            );
        }
    }

    /**
     * Restore partial quantity to a batch
     */
    private function restorePartialQuantity(
        ProductionOrderInput $input,
        ProductVariant $variant,
        float $restoreQuantity
    ): void {
        $productionOrder = $input->productionOrder;
        $tenantId = $productionOrder->tenant_id;
        $batchId = $input->purchase_receipt_item_id;

        if ($batchId) {
            $batch = PurchaseReceiptItem::where('id', $batchId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($batch) {
                $before = (float) ($batch->quantity_remaining ?? 0);
                $after  = $before + $restoreQuantity;

                $batch->quantity_remaining = $after;
                $batch->save();   

                $narrative = sprintf(
                    '%s restored %s × "%s" to %s on production %s%s '
                    . '(input quantity reduced). Batch stock: %s → %s.',
                    $this->actorLabel(),
                    number_format($restoreQuantity, 2),
                    $variant->name,
                    $this->batchLabel($batch),
                    $productionOrder->production_number,
                    $this->productionContextLabel($productionOrder),
                    number_format($before, 2),
                    number_format($after, 2)
                );

                BatchLog::create([
                    'batch_id'                   => $batch->id,
                    'batch_number'               => $batch->batch_number,
                    'variant_id'                 => $variant->id,
                    'variant_name'               => $variant->name,
                    'variant_sku'                => $variant->sku,
                    'type'                       => BatchLog::TYPE_ADJUSTED,
                    'quantity_change'            => $restoreQuantity,
                    'quantity_before'            => $before,
                    'quantity_after'             => $after,
                    'unit_cost'                  => $batch->unit_cost ?? 0,
                    'total_cost'                 => ($batch->unit_cost ?? 0) * $restoreQuantity,
                    'production_order_id'        => $productionOrder->id,
                    'production_order_input_id'  => $input->id,
                    'tenant_id'                  => $tenantId,
                    'location_id'                => $batch->location_id ?? $productionOrder->location_id,
                    'department_id'              => $batch->department_id,
                    'expiry_date'                => $batch->expiry_date,
                    'event_date'                 => now(),
                    'performed_by'               => auth()->id(),
                    'metadata' => [
                        'source'            => 'production_input_quantity_decrease',
                        'production_number' => $productionOrder->production_number,
                        'narrative' => $narrative,
                    ],
                ]);
            }
        }

        $this->incrementOverallQuantity(
            $variant,
            $restoreQuantity,
            $input,
            $productionOrder,
            'production_input_quantity_decrease'
        );
    }

    /**
     * Restore input stock completely (for deletion)
     */
    private function restoreInputStock(ProductionOrderInput $input, ProductionOrder $productionOrder): void
    {
        $variant = $input->productVariant;
        if (!$variant) return;

        $quantity = (float) $input->actual_quantity;
        if ($quantity <= 0) return;

        $tenantId = $productionOrder->tenant_id;
        $batchId = $input->purchase_receipt_item_id;

        if ($batchId) {
            $batch = PurchaseReceiptItem::where('id', $batchId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($batch) {
                $before = (float) ($batch->quantity_remaining ?? 0);
                $after = $before + $quantity;

                $batch->quantity_remaining = $after;
                $batch->save();

                $narrative = sprintf(
                    '%s restored %s × "%s" to %s after removing the input line from production %s%s. '
                    . 'Batch stock: %s → %s.',
                    $this->actorLabel(),
                    number_format($quantity, 2),
                    $variant->name,
                    $this->batchLabel($batch),
                    $productionOrder->production_number,
                    $this->productionContextLabel($productionOrder),
                    number_format($before, 2),
                    number_format($after, 2)
                );

                BatchLog::create([
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'variant_id' => $variant->id,
                    'variant_name' => $variant->name,
                    'variant_sku' => $variant->sku,
                    'type' => BatchLog::TYPE_ADJUSTED,
                    'quantity_change' => $quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $batch->unit_cost ?? 0,
                    'total_cost' => ($batch->unit_cost ?? 0) * $quantity,
                    'production_order_id' => $productionOrder->id,
                    'production_order_input_id' => $input->id,
                    'tenant_id' => $tenantId,
                    'location_id' => $batch->location_id ?? $productionOrder->location_id,
                    'event_date' => now(),
                    'performed_by' => auth()->id(),
                    'metadata' => [
                        'source' => 'production_input_deletion_restore',
                        'production_number' => $productionOrder->production_number,
                        'narrative' => $narrative,
                    ],
                ]);
            }
        }

        $this->incrementOverallQuantity(
            $variant,
            $quantity,
            $input,
            $productionOrder,
            'production_input_deletion_restore'
        );
    }

    
    /**
     * Reconcile output changes - handles additions, removals, and quantity updates
     */
    private function reconcileOutputs(
        ProductionOrder $productionOrder,
        array $newOutputs,
        array $validated
    ): void {
        $existingOutputs = $productionOrder->outputs->keyBy('id');

        \Log::info('[ReconcileOutputs] Starting', [
            'po_id'             => $productionOrder->id,
            'existing_ids'      => $existingOutputs->keys()->all(),
            'incoming_count'    => count($newOutputs),
            'incoming_ids'      => collect($newOutputs)->pluck('id')->all(),
        ]);

        // ✅ Collect kept IDs (existing rows user kept in the modal)
        $keptIds = collect($newOutputs)
            ->pluck('id')
            ->filter(fn($id) => $id !== null && $id !== '' && $id !== 'null')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $isSingleShop      = tenant_is_single_shop($productionOrder->tenant_id);
        $allocLocationId   = $validated['output_location_id']   ?? null;
        $allocDepartmentId = $validated['output_department_id'] ?? null;

        // ── Track which NEW rows we create so we don't delete them ──
        $createdIds = [];

        foreach ($newOutputs as $outputData) {
            $outputId        = $outputData['id'] ?? null;
            $newActualQty    = (float) ($outputData['actual_quantity']    ?? 0);
            $newDefectiveQty = (float) ($outputData['defective_quantity'] ?? 0);
            $variant         = ProductVariant::find($outputData['product_variant_id']);

            if ($outputId && isset($existingOutputs[$outputId])) {
                // ─── UPDATE EXISTING ────────────────────────────────────
                $output             = $existingOutputs[$outputId];
                $oldActualQty       = (float) $output->actual_quantity;
                $quantityDifference = $newActualQty - $oldActualQty;

                $output->update([
                    'product_variant_id' => $outputData['product_variant_id'],
                    'planned_quantity'   => $outputData['planned_quantity'],
                    'actual_quantity'    => $newActualQty,
                    'defective_quantity' => $newDefectiveQty,
                    'unit'               => $outputData['unit'],
                    'production_cost'    => ($outputData['production_cost'] ?? 0) * ($outputData['planned_quantity'] ?? 0),
                    'selling_price'      => $outputData['selling_price'] ?? 0,
                    'inventory_strategy' => $outputData['inventory_strategy'],
                ]);

                if (abs($quantityDifference) > 0.0001) {
                    $this->adjustOutputInventory(
                        $output, $variant, $productionOrder, $quantityDifference,
                        $isSingleShop, $allocLocationId, $allocDepartmentId
                    );
                }

            } else {
                // ─── CREATE NEW ─────────────────────────────────────────
                $newOutput = ProductionOrderOutput::create([
                    'production_order_id' => $productionOrder->id,
                    'product_variant_id'  => $outputData['product_variant_id'],
                    'planned_quantity'    => $outputData['planned_quantity'],
                    'actual_quantity'     => $newActualQty,
                    'defective_quantity'  => $newDefectiveQty,
                    'unit'                => $outputData['unit'],
                    'production_cost'     => ($outputData['production_cost'] ?? 0) * ($outputData['planned_quantity'] ?? 0),
                    'selling_price'       => $outputData['selling_price'] ?? 0,
                    'inventory_strategy'  => $outputData['inventory_strategy'],
                ]);

                $createdIds[] = $newOutput->id;

                \Log::info('[ReconcileOutputs] Created new output', [
                    'new_output_id' => $newOutput->id,
                    'variant_id'    => $outputData['product_variant_id'],
                    'actual_qty'    => $newActualQty,
                ]);

                if (
                    $productionOrder->status === ProductionOrder::STATUS_IN_PROGRESS
                    && $newActualQty > 0
                ) {
                    $this->allocateProductionOutput(
                        $variant, $newOutput, $productionOrder, $newActualQty,
                        $productionOrder->production_number . '-' . date('Ymd'),
                        null, $isSingleShop, $allocLocationId, $allocDepartmentId
                    );
                }
            }
        }

        // ─── DELETE REMOVED OUTPUTS ─────────────────────────────────────
        // Only delete EXISTING rows that were present before but NOT kept now.
        // New rows we just created are excluded by design — they weren't in
        // $existingOutputs to begin with.
        $toDelete = $existingOutputs->keys()->diff($keptIds);

        \Log::info('[ReconcileOutputs] Deleting orphans', [
            'kept_ids'    => $keptIds,
            'created_ids' => $createdIds,
            'to_delete'   => $toDelete->values()->all(),
        ]);

        foreach ($toDelete as $deleteId) {
            $output = $existingOutputs[$deleteId] ?? null;
            if (!$output) continue;

            if (
                (float) $output->actual_quantity > 0
                && $productionOrder->status === ProductionOrder::STATUS_IN_PROGRESS
            ) {
                $this->reverseOutputInventory(
                    $output, $output->productVariant, $productionOrder,
                    (float) $output->actual_quantity,
                    $isSingleShop, $allocLocationId, $allocDepartmentId
                );
            }

            $output->delete();
        }
    }

    /**
     * Adjust output inventory when quantity changes
     */
    private function adjustOutputInventory(
        ProductionOrderOutput $output,
        ProductVariant $variant,
        ProductionOrder $productionOrder,
        float $quantityDifference,
        bool $isSingleShop,
        ?int $allocLocationId,
        ?int $allocDepartmentId
    ): void {
        if ($productionOrder->status !== ProductionOrder::STATUS_IN_PROGRESS) {
            return; // Only adjust inventory for in-progress orders
        }

        if ($quantityDifference > 0) {
            // Adding more output - allocate additional inventory
            $this->allocateProductionOutput(
                $variant,
                $output,
                $productionOrder,
                $quantityDifference,
                $output->batch_number ?? $productionOrder->production_number . '-' . date('Ymd'),
                $output->expiry_date?->format('Y-m-d'),
                $isSingleShop,
                $allocLocationId,
                $allocDepartmentId
            );
        } else {
            // Reducing output - reverse some inventory
            $this->reverseOutputInventory(
                $output,
                $variant,
                $productionOrder,
                abs($quantityDifference),
                $isSingleShop,
                $allocLocationId,
                $allocDepartmentId
            );
        }
    }

    /**
     * Reverse output inventory when quantity is reduced or output is deleted
     */
    private function reverseOutputInventory(
        ProductionOrderOutput $output,
        ProductVariant $variant,
        ProductionOrder $productionOrder,
        float $quantity,
        bool $isSingleShop,
        ?int $allocLocationId,
        ?int $allocDepartmentId
    ): void {
        if (!$variant || $quantity <= 0) return;

        $tenantId = $productionOrder->tenant_id;

        // ─── REDUCE OVERALL QUANTITY ───────────────────────────────
        $beforeOverall = (float) ($variant->overal_quantity_at_hand ?? 0);
        $afterOverall  = max(0, $beforeOverall - $quantity);

        $variant->overal_quantity_at_hand = $afterOverall;
        $variant->save();

        // ─── SINGLE SHOP LOG ───────────────────────────────────────
        if ($isSingleShop) {

            $narrative = sprintf(
                '%s reversed %s × "%s" from production %s%s '
                . '(output reduced or deleted). Overall stock: %s → %s.',
                $actor,
                number_format($quantity, 2),
                $variant->name,
                $productionOrder->production_number,
                $context,
                number_format($beforeOverall, 2),
                number_format($afterOverall, 2)
            );
            SingleShopInventoryLog::create([
                'variant_id'      => $variant->id,
                'order_id'        => $productionOrder->id,
                'tenant_id'       => $tenantId,
                'created_by'      => auth()->id(),
                'quantity_before' => $beforeOverall,
                'quantity_after'  => $afterOverall,
                'quantity_change' => -$quantity,
                'reason'          => 'production_output_reversal',
                'notes'           => $narrative,
                'source'          => 'production',
                'metadata'        => [
                    'production_number' => $productionOrder->production_number,
                    'output_id'         => $output->id,
                    'reversal_reason'   => 'edit_update',
                    'narrative'           => $narrative,
                ],
            ]);
        } else {
            // ─── MULTI-SHOP: REDUCE ALLOCATED QUANTITY ─────────────
            // ✅ Fall back to the output's own location/department if not passed
            $targetLocationId   = $allocLocationId   ?: $output->location_id   ?? $productionOrder->location_id;
            $targetDepartmentId = $allocDepartmentId ?: $output->department_id ?? null;

            if ($targetLocationId && $targetDepartmentId) {
                $item = InventoryItems::where([
                    'variant_id'    => $variant->id,
                    'location_id'   => $targetLocationId,
                    'department_id' => $targetDepartmentId,
                    'tenant_id'     => $tenantId,
                ])->first();

                if ($item) {
                    $beforeAlloc = (int) $item->quantity_allocated;
                    $afterAlloc  = max(0, $beforeAlloc - (int) $quantity);

                    $item->quantity_allocated = $afterAlloc;
                    $item->save();

                    $locName  = $item->itemLocation?->name ?? "Location #{$item->location_id}";
                    $deptName = $item->departmentItem?->name ?? "Department #{$item->department_id}";

                    $narrative = sprintf(
                        '%s reversed %s × "%s" produced by production %s%s from %s (%s). '
                        . 'Branch allocation: %s → %s units. Overall stock: %s → %s.',
                        $actor,
                        number_format($quantity, 2),
                        $variant->name,
                        $productionOrder->production_number,
                        $context,
                        $locName,
                        $deptName,
                        number_format($beforeAlloc),
                        number_format($afterAlloc),
                        number_format($beforeOverall, 2),
                        number_format($afterOverall, 2)
                    );

                    InventoryAdjustments::create([
                        'inventory_id'    => $item->id,
                        'quantity_before' => $beforeAlloc,
                        'quantity_after'  => $afterAlloc,
                        'reason'          => 'production_output_reversal',
                        'notes'           => $narrative,
                        'created_by'      => auth()->id(),
                        'tenant_id'       => $tenantId,
                    ]);

                    InventoryTransactions::create([
                        'inventory_id'   => $item->id,
                        'quantity'       => -(int) $quantity,
                        'reference_id'   => $productionOrder->id,
                        'reference_type' => 'production_order_reversal',
                        'type'           => 'transfer_out',
                        'notes'          => $narrative,
                        'created_by'     => auth()->id(),
                        'tenant_id'      => $tenantId,
                    ]);
                }
            }
        }

        // ─── HANDLE BATCH STRATEGY ─────────────────────────────────
        if ($output->inventory_strategy === 'batch' && $output->batch_number) {
            $batch = PurchaseReceiptItem::where('batch_number', $output->batch_number)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($batch) {
                $beforeBatch = (float) ($batch->quantity_remaining ?? 0);
                $afterBatch  = max(0, $beforeBatch - $quantity);

                $batch->quantity_remaining = $afterBatch;
                $batch->save();

                $narrative = sprintf(
                    '%s reduced %s to %s on production %s%s '
                    . '(produced output reversed). Batch stock: %s → %s.',
                    $actor,
                    $this->batchLabel($batch),
                    number_format($quantity, 2),
                    $productionOrder->production_number,
                    $context,
                    number_format($beforeBatch, 2),
                    number_format($afterBatch, 2)
                );

                BatchLog::create([
                    'batch_id'                   => $batch->id,
                    'batch_number'               => $batch->batch_number,
                    'variant_id'                 => $variant->id,
                    'variant_name'               => $variant->name,
                    'variant_sku'                => $variant->sku,
                    'type'                       => BatchLog::TYPE_ADJUSTED,
                    'quantity_change'            => -$quantity,
                    'quantity_before'            => $beforeBatch,
                    'quantity_after'             => $afterBatch,
                    'unit_cost'                  => $batch->unit_cost ?? 0,
                    'total_cost'                 => ($batch->unit_cost ?? 0) * $quantity,
                    'production_order_id'        => $productionOrder->id,
                    'production_order_output_id' => $output->id,
                    'tenant_id'                  => $tenantId,
                    'location_id'                => $batch->location_id ?? $productionOrder->location_id,
                    'department_id'              => $batch->department_id,  // ✅ added
                    'expiry_date'                => $batch->expiry_date,    // ✅ added
                    'event_date'                 => now(),
                    'performed_by'               => auth()->id(),
                    'metadata'                   => [
                        'source'            => 'production_output_reversal',
                        'production_number' => $productionOrder->production_number,
                        'narrative'           => $narrative,
                    ],
                ]);
            }
        }
    }

    

    /**
     * Reduce variant.overal_quantity_at_hand and log to SingleShopInventoryLog.
     * Keeps the tenant-wide tally in sync with batch movements.
     */
    private function decrementOverallQuantity(
        ProductVariant $variant,
        float $quantity,
        $input,
        ProductionOrder $productionOrder,
        string $reason
    ): void {
        if ($quantity <= 0) return;

        $before = (float) ($variant->overal_quantity_at_hand ?? 0);
        $after  = max(0, $before - $quantity);

        $variant->overal_quantity_at_hand = $after;
        $variant->save();

        $actor        = $this->actorLabel();
        $productionN° = $productionOrder->production_number;
        $context      = $this->productionContextLabel($productionOrder);

        // Human phrase for the reason
        $reasonText = match (true) {
            str_contains($reason, 'batch_fifo')      => 'consumed as raw material (FIFO)',
            str_contains($reason, 'batch_swap')      => 'consumed as raw material (batch swap)',
            str_contains($reason, 'quantity_increase') => 'consumed as additional raw material',
            str_contains($reason, 'quantity_decrease') => 'returned excess raw material',
            str_contains($reason, 'deletion_restore')  => 'restored after input removal',
            default                                  => 'consumed as raw material',
        };

        $narrative = sprintf(
            '%s %s %s × "%s" for production %s%s. '
            . 'Overall stock: %s → %s.',
            $actor,
            $reasonText,
            number_format($quantity, 2),
            $variant->name,
            $productionN°,
            $context,
            number_format($before, 2),
            number_format($after, 2)
        );

        SingleShopInventoryLog::create([
            'variant_id'      => $variant->id,
            'order_id'        => $productionOrder->id,
            'tenant_id'       => $productionOrder->tenant_id,
            'created_by'      => auth()->id(),
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'quantity_change' => -$quantity,
            'reason'          => $reason,
            'notes'           => $narrative,   // ★
            'source'          => 'production',
            'metadata'        => [
                'input_id'              => $input->id ?? null,
                'production_number'     => $productionN°,
                'purchase_receipt_item' => $input->purchase_receipt_item_id ?? null,
                'narrative'             => $narrative,
            ],
        ]);
    }

    /**
     * Increase variant.overal_quantity_at_hand and log to SingleShopInventoryLog.
     * Used when restoring stock during edit (batch swap, quantity decrease, row deletion).
     */
    private function incrementOverallQuantity(
        ProductVariant $variant,
        float $quantity,
        $input,
        ProductionOrder $productionOrder,
        string $reason
    ): void {
        if ($quantity <= 0) return;

        $before = (float) ($variant->overal_quantity_at_hand ?? 0);
        $after  = $before + $quantity;

        $variant->overal_quantity_at_hand = $after;
        $variant->save();

        $reasonText = match (true) {
            str_contains($reason, 'batch_swap')       => 'restored to stock during batch swap',
            str_contains($reason, 'quantity_decrease') => 'restored to stock after reducing input quantity',
            str_contains($reason, 'deletion_restore')  => 'restored to stock after input removal',
            default                                    => 'restored to stock',
        };

        $narrative = sprintf(
            '%s %s: %s × "%s" for production %s%s. '
            . 'Overall stock: %s → %s.',
            $actor,
            $reasonText,
            number_format($quantity, 2),
            $variant->name,
            $productionN°,
            $context,
            number_format($before, 2),
            number_format($after, 2)
        );

        SingleShopInventoryLog::create([
            'variant_id'      => $variant->id,
            'order_id'        => $productionOrder->id,
            'tenant_id'       => $productionOrder->tenant_id,
            'created_by'      => auth()->id(),
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'quantity_change' => $quantity,
            'reason'          => $reason,
            'notes'           => $narrative,
            'source'          => 'production',
            'metadata'        => [
                'input_id'              => $input->id ?? null,
                'production_number'     => $productionOrder->production_number,
                'purchase_receipt_item' => $input->purchase_receipt_item_id ?? null,
            ],
        ]);
    }
    
}