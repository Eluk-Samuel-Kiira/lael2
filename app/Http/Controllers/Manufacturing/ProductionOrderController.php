<?php
// app/Http/Controllers/Manufacturing/ProductionOrderController.php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ProductionOrder, ProductionOrderInput, ProductionOrderOutput, 
    ProductVariant, PurchaseReceiptItem, SerialNumber, Location, PaymentMethod,
    SingleShopInventoryLog, BatchLog, Currency};
use Illuminate\Support\Facades\{Auth, DB};

class ProductionOrderController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view production_orders')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }
            abort(403);
        }

        $perPage = $request->input('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }

        $query = ProductionOrder::with(['inputs.productVariant', 'outputs.productVariant', 'createdBy', 'location'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('production_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('createdBy', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $productionOrders = $query->latest()->paginate($perPage);
        $productionOrders->appends(['per_page' => $perPage, 'search' => $request->search]);

        $bladeToReload = $request->query('bladeFileToReload');

        if ($request->ajax() && $bladeToReload === 'reloadProductionComponent') {
            return view('manufacturing.production-order.component', [
                'productionOrders' => $productionOrders,
            ])->render();
        }

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get();

        return view('manufacturing.production-index', [
            'productionOrders' => $productionOrders,
            'variants' => $variants,
            'locations' => $locations,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request)
    {
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
            'inputs.*.product_variant_id' => 'required|exists:product_variants,id',
            'inputs.*.planned_quantity' => 'required|numeric|min:0.01',
            'inputs.*.purchase_receipt_item_id' => 'nullable|exists:purchase_receipt_items,id',
            'inputs.*.unit' => 'required|string|max:20',
            'inputs.*.estimated_cost' => 'nullable|numeric|min:0',
            'outputs' => 'required|array|min:1',
            'outputs.*.product_variant_id' => 'required|exists:product_variants,id',
            'outputs.*.planned_quantity' => 'required|numeric|min:0.01',
            'outputs.*.unit' => 'required|string|max:20',
            'outputs.*.production_cost' => 'nullable|numeric|min:0', // This is PER-UNIT cost
            'outputs.*.selling_price' => 'nullable|numeric|min:0',
            'outputs.*.inventory_strategy' => 'required|in:quantity,batch,serial',
        ]);

        DB::beginTransaction();

        try {
            $productionNumber = ProductionOrder::generateProductionNumber($tenantId);

            // ✅ Calculate estimated costs - INPUTS
            $estimatedInputCost = 0;
            foreach ($request->inputs as $input) {
                $variant = ProductVariant::find($input['product_variant_id']);
                // Use the provided estimated_cost or calculate from unit cost * quantity
                $cost = $input['estimated_cost'] ?? ($variant->supplier_cost_price ?? 0) * $input['planned_quantity'];
                $estimatedInputCost += $cost;
            }

            // ✅ Calculate estimated costs - OUTPUTS
            // production_cost is PER-UNIT, so multiply by planned_quantity
            $estimatedOutputCost = 0;
            foreach ($request->outputs as $output) {
                $perUnitCost = $output['production_cost'] ?? 0;
                $quantity = $output['planned_quantity'] ?? 0;
                // ✅ Total cost = per-unit cost * quantity
                $totalOutputCost = $perUnitCost * $quantity;
                $estimatedOutputCost += $totalOutputCost;
            }

            // ✅ Log for debugging
            \Log::info('Production order cost calculation', [
                'estimated_input_cost' => $estimatedInputCost,
                'estimated_output_cost' => $estimatedOutputCost,
                'total_estimated_cost' => $estimatedInputCost + $estimatedOutputCost,
                'outputs' => $request->outputs,
            ]);

            $totalEstimatedCost = $estimatedInputCost + $estimatedOutputCost;

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

            // Create inputs
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

            // Create outputs
            foreach ($request->outputs as $outputData) {
                // ✅ Store the TOTAL production cost (per-unit * quantity)
                $totalProductionCost = ($outputData['production_cost'] ?? 0) * ($outputData['planned_quantity'] ?? 0);
                
                ProductionOrderOutput::create([
                    'production_order_id' => $productionOrder->id,
                    'product_variant_id' => $outputData['product_variant_id'],
                    'planned_quantity' => $outputData['planned_quantity'],
                    'actual_quantity' => 0,
                    'defective_quantity' => 0,
                    'unit' => $outputData['unit'],
                    'production_cost' => $totalProductionCost, // ✅ Store TOTAL cost
                    'selling_price' => $outputData['selling_price'] ?? 0,
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
        $user = Auth::user();
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
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'withdrawal_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'estimated_cost' => 'required|numeric|min:0',
        ]);

        $estimatedCost = (float) $validated['estimated_cost'];
        $withdrawalAmount = (float) $validated['withdrawal_amount'];

        // ✅ STEP 1: VALIDATE - All inputs have sufficient stock in master stock
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
            $paymentMethod = null;
            $transactionLog = null;

            // ✅ STEP 2: RECORD PAYMENT WITHDRAWAL (if cost > 0)
            if ($estimatedCost > 0) {
                if (empty($validated['payment_method_id'])) {
                    throw new \Exception(__('payments.payment_method_required'));
                }

                $paymentMethod = PaymentMethod::findForTenant($validated['payment_method_id'], $tenantId);
                if (!$paymentMethod) {
                    throw new \Exception(__('pagination.payment_method_not_found'));
                }

                $transactionData = [
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_type' => 'WITHDRAWAL',
                    'transaction_category' => 'FEE',
                    'amount' => $withdrawalAmount,
                    'currency_id' => $paymentMethod->currency_id ?? Currency::default()->id,
                    'reference_table' => 'production_orders',
                    'reference_id' => $productionOrder->id,
                    'description' => 'Production Cost Withdrawal - #' . $productionOrder->production_number,
                    'notes' => 'Withdrawal of ' . number_format($withdrawalAmount, 2) . ' for production #' . $productionOrder->production_number . 
                            ($validated['notes'] ? ' - ' . $validated['notes'] : ''),
                    'metadata' => [
                        'production_number' => $productionOrder->production_number,
                        'estimated_cost' => $estimatedCost,
                        'withdrawal_amount' => $withdrawalAmount,
                        'payment_method' => $paymentMethod->name,
                        'transaction_nature' => 'PRODUCTION_COST_WITHDRAWAL',
                        'processed_by_id' => $user->id,
                        'processed_by_name' => $user->name,
                    ],
                ];

                $transactionLog = app('payment-transaction')->recordTransaction($transactionData);

                $paymentMethod->current_balance -= $withdrawalAmount;
                $paymentMethod->save();

                \Log::info('[Production] Cost withdrawal recorded', [
                    'production_order_id' => $productionOrder->id,
                    'production_number' => $productionOrder->production_number,
                    'amount' => $withdrawalAmount,
                    'payment_method' => $paymentMethod->name,
                    'transaction_ref' => $transactionLog->transaction_ref ?? null,
                ]);
            }

            // ✅ STEP 3: CONSUME - All inputs (deplete master stock)
            foreach ($productionOrder->inputs as $input) {
                $this->consumeInput($input, $productionOrder);
            }

            // ✅ STEP 4: UPDATE - Production order status
            $updateData = [
                'status' => ProductionOrder::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'started_by' => $user->id,
            ];

            if ($estimatedCost > 0 && isset($paymentMethod)) {
                $updateData['payment_method_id'] = $paymentMethod->id;
                $updateData['payment_transaction_ref'] = $transactionLog->transaction_ref ?? null;
            }

            $productionOrder->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('passwords.production_started'),
                'reload' => true,
                'componentId' => 'reloadProductionComponent',
                'redirect' => route('production-orders.index'),
                'transaction_ref' => $transactionLog->transaction_ref ?? null,
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

            \Log::info('[Production] Validating stock', [
                'variant' => $variant->name,
                'strategy' => $strategy,
                'needed' => $quantityNeeded,
                'available' => $variant->overal_quantity_at_hand ?? 0,
            ]);

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
     * ✅ CONSUME: Deplete master stock (overal_quantity_at_hand) based on strategy
     */
    private function consumeInput($input, $productionOrder): void
    {
        $variant = $input->productVariant;
        if (!$variant) return;

        $product = $variant->product;
        $strategy = $product?->resolvedInventoryStrategy() ?? 'quantity';
        $quantity = $input->planned_quantity;

        \Log::info('[Production] Consuming input', [
            'variant' => $variant->name,
            'strategy' => $strategy,
            'quantity' => $quantity,
            'current_stock' => $variant->overal_quantity_at_hand ?? 0,
        ]);

        // Update input with actual quantity
        $input->update([
            'actual_quantity' => $quantity,
            'actual_cost' => $input->estimated_cost,
        ]);

        // ✅ BATCH STRATEGY: Reduce batch quantity_remaining
        if ($strategy === 'batch' && $input->purchase_receipt_item_id) {
            $batch = PurchaseReceiptItem::find($input->purchase_receipt_item_id);
            if ($batch) {
                $before = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
                $after = max(0, $before - $quantity);
                
                $batch->quantity_remaining = $after;
                $batch->save();

                BatchLog::create([
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'variant_id' => $variant->id,
                    'variant_name' => $variant->name,
                    'variant_sku' => $variant->sku,
                    'type' => 'consumed',
                    'quantity_change' => -$quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $batch->unit_cost ?? 0,
                    'total_cost' => ($batch->unit_cost ?? 0) * $quantity,
                    'production_order_id' => $productionOrder->id,
                    'production_order_input_id' => $input->id,
                    'tenant_id' => $productionOrder->tenant_id,
                    'location_id' => $productionOrder->location_id,
                    'expiry_date' => $batch->expiry_date,
                    'event_date' => now(),
                    'performed_by' => auth()->id(),
                ]);
            }
        }
        // ✅ QUANTITY STRATEGY: Reduce overal_quantity_at_hand (master stock)
        elseif ($strategy === 'quantity') {
            $before = $variant->overal_quantity_at_hand ?? 0;
            $after = max(0, $before - $quantity);
            
            $variant->overal_quantity_at_hand = $after;
            $variant->save();

            // ✅ Audit trail - SingleShopInventoryLog (for both single and multi shop)
            SingleShopInventoryLog::create([
                'variant_id' => $variant->id,
                'order_id' => $productionOrder->id,
                'tenant_id' => $productionOrder->tenant_id,
                'created_by' => auth()->id(),
                'quantity_before' => $before,
                'quantity_after' => $after,
                'quantity_change' => -$quantity,
                'reason' => 'production_consumption',
                'notes' => "Consumed in production #{$productionOrder->production_number} - {$variant->name}",
                'source' => 'production',
                'metadata' => [
                    'input_id' => $input->id,
                    'production_number' => $productionOrder->production_number,
                    'strategy' => 'quantity',
                ],
            ]);
        }
        // ✅ SERIAL STRATEGY: Mark serials as reserved
        elseif ($strategy === 'serial') {
            $serials = SerialNumber::where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $productionOrder->tenant_id)
                ->limit($quantity)
                ->get();

            foreach ($serials as $serial) {
                $serial->update([
                    'status' => SerialNumber::STATUS_RESERVED,
                    'production_order_id' => $productionOrder->id,
                    'notes' => "Consumed in production #{$productionOrder->production_number}",
                ]);
            }
        }
    }


        
    /**
     * Complete production with outputs (update actual quantities and complete in one step)
     */
    public function completeWithOutputs(Request $request, $id)
    {
        $user = Auth::user();
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

        $validated = $request->validate([
            'outputs' => 'required|array|min:1',
            'outputs.*.output_id' => 'required|exists:production_order_outputs,id',
            'outputs.*.actual_quantity' => 'required|numeric|min:0',
            'outputs.*.defective_quantity' => 'nullable|numeric|min:0',
            'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
            'complete' => 'boolean',
        ]);

        // ✅ Check if at least one output has quantity > 0
        $hasQuantity = false;
        foreach ($validated['outputs'] as $outputData) {
            if ($outputData['actual_quantity'] > 0) {
                $hasQuantity = true;
                break;
            }
        }

        if (!$hasQuantity) {
            return response()->json([
                'success' => false,
                'message' => __('passwords.enter_at_least_one_actual_quantity'),
            ]);
        }

        DB::beginTransaction();

        try {
            $totalActualQuantity = 0;
            $batchNumber = $validated['batch_number'] ?? $productionOrder->production_number . '-' . date('Ymd');
            $expiryDate = $validated['expiry_date'] ?? null;

            // ✅ Update all outputs with actual quantities
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

                $oldActual = $output->actual_quantity;
                $newActual = $outputData['actual_quantity'];
                $quantityDifference = $newActual - $oldActual;
                $defectiveQuantity = $outputData['defective_quantity'] ?? 0;

                // ✅ Update output record with batch info
                $output->update([
                    'actual_quantity' => $newActual,
                    'defective_quantity' => $defectiveQuantity,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                ]);

                // ✅ Only update inventory if there's a positive difference
                if ($quantityDifference > 0) {
                    // ✅ Update master stock (overal_quantity_at_hand)
                    $beforeQty = $variant->overal_quantity_at_hand ?? 0;
                    $afterQty = $beforeQty + $quantityDifference;
                    
                    $variant->overal_quantity_at_hand = $afterQty;
                    $variant->save();

                    // ✅ Audit trail
                    SingleShopInventoryLog::create([
                        'variant_id' => $variant->id,
                        'order_id' => $productionOrder->id,
                        'tenant_id' => $productionOrder->tenant_id,
                        'created_by' => auth()->id(),
                        'quantity_before' => $beforeQty,
                        'quantity_after' => $afterQty,
                        'quantity_change' => $quantityDifference,
                        'reason' => 'production_output',
                        'notes' => "Production output - Order #{$productionOrder->production_number} - {$variant->name}",
                        'source' => 'production',
                        'metadata' => [
                            'production_order_id' => $productionOrder->id,
                            'production_number' => $productionOrder->production_number,
                            'output_id' => $output->id,
                            'quantity_added' => $quantityDifference,
                            'cost' => $output->production_cost,
                            'inventory_strategy' => $output->inventory_strategy,
                            'batch_number' => $batchNumber,
                            'expiry_date' => $expiryDate,
                        ],
                    ]);

                    // ✅ If batch strategy, create batch record
                    if ($output->inventory_strategy === 'batch') {
                        $unitCost = $output->production_cost / max(1, $quantityDifference);
                        
                        $batch = PurchaseReceiptItem::create([
                            'purchase_receipt_id' => null,
                            'purchase_order_item_id' => null,
                            'quantity_received' => $quantityDifference,
                            'quantity_remaining' => $quantityDifference,
                            'unit_cost' => $unitCost,
                            'batch_number' => $batchNumber,
                            'expiry_date' => $expiryDate,
                            'location_id' => $productionOrder->location_id,
                            'tenant_id' => $productionOrder->tenant_id,
                            'notes' => "Produced from production #{$productionOrder->production_number}",
                        ]);

                        BatchLog::create([
                            'batch_id' => $batch->id,
                            'batch_number' => $batch->batch_number,
                            'variant_id' => $variant->id,
                            'variant_name' => $variant->name,
                            'variant_sku' => $variant->sku,
                            'type' => 'produced',
                            'quantity_change' => $quantityDifference,
                            'quantity_before' => 0,
                            'quantity_after' => $quantityDifference,
                            'unit_cost' => $unitCost,
                            'total_cost' => $output->production_cost,
                            'production_order_id' => $productionOrder->id,
                            'production_order_output_id' => $output->id,
                            'tenant_id' => $productionOrder->tenant_id,
                            'location_id' => $productionOrder->location_id,
                            'expiry_date' => $expiryDate,
                            'event_date' => now(),
                            'performed_by' => auth()->id(),
                            'metadata' => [
                                'production_number' => $productionOrder->production_number,
                                'batch_generated' => true,
                            ],
                        ]);
                    }

                    $totalActualQuantity += $quantityDifference;
                }
            }

            // ✅ Update production order notes
            if ($validated['notes']) {
                $productionOrder->notes = ($productionOrder->notes ? $productionOrder->notes . "\n" : '') . $validated['notes'];
                $productionOrder->save();
            }

            // ✅ Complete the production order
            $productionOrder->complete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('passwords.production_completed'),
                'reload' => true,
                'componentId' => 'reloadProductionComponent',
                'redirect' => route('production-orders.index'),
                'data' => [
                    'total_output_quantity' => $productionOrder->outputs->sum('actual_quantity'),
                    'inventory_updated' => $totalActualQuantity > 0,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                ],
            ]);

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
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $variantId = $request->variant_id;
        $locationId = $request->location_id ?? $user->location_id;

        $batches = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->where('purchase_orders.tenant_id', $tenantId)
            ->where('purchase_order_items.product_variant_id', $variantId)
            ->where(function($q) {
                $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                  ->orWhereNull('purchase_receipt_items.quantity_remaining');
            })
            ->when($locationId, function($q) use ($locationId) {
                return $q->where('purchase_receipt_items.location_id', $locationId);
            })
            ->orderBy('purchase_receipt_items.expiry_date', 'asc')
            ->select('purchase_receipt_items.*')
            ->get();

        return response()->json([
            'success' => true,
            'batches' => $batches->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity_remaining' => $batch->quantity_remaining ?? $batch->quantity_received ?? 0,
                    'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
                    'unit_cost' => $batch->unit_cost,
                    'location_id' => $batch->location_id,
                ];
            }),
        ]);
    }

    public function updateOutput(Request $request, $id)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update production_orders')) {
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
                        'metadata' => [
                            'production_order_id' => $productionOrder->id,
                            'production_number' => $productionOrder->production_number,
                            'output_id' => $output->id,
                            'quantity_added' => $quantityDifference,
                            'cost' => $output->production_cost,
                            'inventory_strategy' => $output->inventory_strategy,
                        ],
                    ]);

                    $totalActualQuantity += $quantityDifference;

                    \Log::info('[Production] Output inventory updated', [
                        'production_order_id' => $productionOrder->id,
                        'production_number' => $productionOrder->production_number,
                        'variant_id' => $variant->id,
                        'variant_name' => $variant->name,
                        'quantity_added' => $quantityDifference,
                        'total_actual' => $newActual,
                        'old_actual' => $oldActual,
                        'before_qty' => $beforeQty,
                        'after_qty' => $afterQty,
                    ]);
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
}