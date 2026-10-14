<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PurchaseReceiptItem;
use App\Models\{ Location, BatchLog };
use App\Models\Department;

class BatchController extends Controller
{
    /**
     * Display batches for products that use batch strategy ONLY
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view inventory')) {
            abort(403, __('payments.not_authorized'));
        }

        // ── Visibility rule ─────────────────────────────────────────
        $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        $search       = $request->get('search');
        $locationId   = $request->get('location_id');
        $departmentId = $request->get('department_id');
        $perPage      = $request->get('per_page', 15);

        $query = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->leftJoin('product_variants', 'purchase_order_items.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('products', 'product_variants.product_id', '=', 'products.id')
            ->leftJoin('locations', 'purchase_receipt_items.location_id', '=', 'locations.id')
            ->leftJoin('departments', 'purchase_receipt_items.department_id', '=', 'departments.id')
            ->where('purchase_orders.tenant_id', $tenantId)
            ->where('products.inventory_strategy', 'batch')
            ->where(function ($q) {
                $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                ->orWhereNull('purchase_receipt_items.quantity_remaining');
            })
            ->select(
                'purchase_receipt_items.*',
                'product_variants.name as variant_name',
                'product_variants.sku',
                'products.name as product_name',
                'products.inventory_strategy',
                'locations.name as location_name',
                'departments.name as department_name'
            );

        // ── Non-admins: only batches at their assigned locations ────
        if (!$isAdmin) {
            if (empty($userLocationIds)) {
                // User has no locations assigned → return nothing
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('purchase_receipt_items.location_id', $userLocationIds);
            }
        }

        // ── Search ──────────────────────────────────────────────────
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('purchase_receipt_items.batch_number', 'like', "%{$search}%")
                ->orWhere('product_variants.name', 'like', "%{$search}%")
                ->orWhere('product_variants.sku', 'like', "%{$search}%")
                ->orWhere('products.name', 'like', "%{$search}%");
            });
        }

        // ── Location filter (only meaningful for admins) ────────────
        if ($locationId) {
            // For non-admins, ensure the requested location is within their allowed set
            if ($isAdmin || in_array((int) $locationId, $userLocationIds, true)) {
                $query->where('purchase_receipt_items.location_id', $locationId);
            }
        }

        // ── Department filter ───────────────────────────────────────
        if ($departmentId) {
            $query->where('purchase_receipt_items.department_id', $departmentId);
        }

        $batches = $query->orderBy('purchase_receipt_items.created_at', 'desc')->paginate($perPage);

        // ── Locations list for the filter dropdown ──────────────────
        $locationsQuery = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name');

        if (!$isAdmin) {
            $locationsQuery->whereIn('id', $userLocationIds);
        }

        $locations = $locationsQuery->get();

        $departments = Department::where('tenant_id', $tenantId)
            ->where('isActive', true)
            ->orderBy('name')
            ->get();

        $summary = [
            'total_batches'  => $query->count(),
            'total_quantity' => $query->sum('purchase_receipt_items.quantity_remaining') ?? 0,
        ];

        return view('inventory.batch.index', compact(
            'batches',
            'locations',
            'departments',
            'summary',
            'search',
            'locationId',
            'departmentId',
            'perPage'
        ));
    }

    /**
     * Assign batches to location and department
     */
    public function assign(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $request->validate([
            'batch_ids' => 'required|array',
            'batch_ids.*' => 'exists:purchase_receipt_items,id',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (!$request->location_id && !$request->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least a location or department.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $assigned = 0;
            $skipped = 0;
            $assignmentDetails = [];

            foreach ($request->batch_ids as $batchId) {
                $batch = PurchaseReceiptItem::query()
                    ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                    ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                    ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
                    ->join('product_variants', 'purchase_order_items.product_variant_id', '=', 'product_variants.id')
                    ->join('products', 'product_variants.product_id', '=', 'products.id')
                    ->where('purchase_orders.tenant_id', $tenantId)
                    ->where('products.inventory_strategy', 'batch')
                    ->where('purchase_receipt_items.id', $batchId)
                    ->where(function($q) {
                        $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                        ->orWhereNull('purchase_receipt_items.quantity_remaining');
                    })
                    ->select('purchase_receipt_items.*')
                    ->first();

                if (!$batch) {
                    $skipped++;
                    continue;
                }

                // Store old values for logging
                $oldLocationId = $batch->location_id;
                $oldDepartmentId = $batch->department_id;

                $batch->location_id = $request->location_id;
                $batch->department_id = $request->department_id;
                $batch->save();

                $assigned++;

                // ✅ LOG ASSIGNMENT TO BATCHLOG TABLE
                $this->logBatchAssignment(
                    $batch,
                    $oldLocationId,
                    $oldDepartmentId,
                    $request->location_id,
                    $request->department_id,
                    $user,
                    $tenantId
                );

                $assignmentDetails[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'old_location_id' => $oldLocationId,
                    'old_department_id' => $oldDepartmentId,
                    'new_location_id' => $batch->location_id,
                    'new_department_id' => $batch->department_id,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$assigned} batch(es) assigned successfully." . ($skipped > 0 ? " {$skipped} skipped." : ""),
                'reload' => true,
                'data' => [
                    'assigned' => $assigned,
                    'skipped' => $skipped,
                    'details' => $assignmentDetails,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Batch assignment failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign batches: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function split(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $validated = $request->validate([
            'batch_id'                => 'required|exists:purchase_receipt_items,id',
            'splits'                  => 'required|array|min:2',
            'splits.*.quantity'       => 'required|numeric|gt:0',
            'splits.*.location_id'    => 'required|exists:locations,id',
            'splits.*.department_id'  => 'required|exists:departments,id',
            'splits.*.suffix'         => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();

        try {
            // Load the parent batch, scoped to tenant + batch-strategy products only
            $parent = PurchaseReceiptItem::query()
                ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
                ->join('product_variants', 'purchase_order_items.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->where('purchase_orders.tenant_id', $tenantId)
                ->where('products.inventory_strategy', 'batch')
                ->where('purchase_receipt_items.id', $validated['batch_id'])
                ->select('purchase_receipt_items.*')
                ->firstOrFail();

            $parentQty = (float) ($parent->quantity_remaining ?? $parent->quantity_received ?? 0);
            $splitQty  = collect($validated['splits'])->sum(fn($s) => (float) $s['quantity']);

            if ($splitQty > $parentQty) {
                return response()->json([
                    'success' => false,
                    'message' => __('passwords.split_exceeds_available_quantity'),
                    'debug'   => ['parent_qty' => $parentQty, 'split_qty' => $splitQty],
                ], 422);
            }

            // Validate each split's department belongs to its location
            foreach ($validated['splits'] as $i => $split) {
                $dept = Department::find($split['department_id']);
                if (!$dept || (int) $dept->location_id !== (int) $split['location_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => __('passwords.department_location_mismatch'),
                        'debug'   => [
                            'row' => $i,
                            'department_id' => $split['department_id'],
                            'department_location_id' => $dept?->location_id,
                            'chosen_location_id' => $split['location_id'],
                        ],
                    ], 422);
                }
            }

            $createdIds = [];
            $usedBatchNumbers = [];

            foreach ($validated['splits'] as $i => $split) {
                $location = Location::find($split['location_id']);
                $baseSuffix = $split['suffix'] ?? strtoupper(preg_replace('/[^A-Z0-9]/i', '', $location->name ?? 'L' . $split['location_id']));
                $attempt = 0;
                $childBatchNumber = $this->buildSplitBatchNumber($parent->batch_number, $baseSuffix);

                while (
                    in_array($childBatchNumber, $usedBatchNumbers) ||
                    PurchaseReceiptItem::where('batch_number', $childBatchNumber)->exists()
                ) {
                    $attempt++;
                    $childBatchNumber = $this->buildSplitBatchNumber($parent->batch_number, $baseSuffix, $attempt);
                    if ($attempt > 20) throw new \Exception('Unable to generate a unique split batch number.');
                }
                $usedBatchNumbers[] = $childBatchNumber;

                $child = PurchaseReceiptItem::create([
                    'purchase_receipt_id'    => $parent->purchase_receipt_id,
                    'purchase_order_item_id' => $parent->purchase_order_item_id,
                    'quantity_received'      => $split['quantity'],
                    'quantity_remaining'     => $split['quantity'],
                    'unit_cost'              => $parent->unit_cost,
                    'batch_number'           => $childBatchNumber,
                    'expiry_date'            => $parent->expiry_date,
                    'location_id'            => $split['location_id'],
                    'department_id'          => $split['department_id'],
                    'tenant_id'              => $tenantId,
                    // 'parent_batch_id'     => $parent->id,   // only if you add this column
                ]);

                $createdIds[] = $child->id;

                // Log each split as a transfer
                $this->logBatchSplit($parent, $child, $split, $user, $tenantId, parentQty);
            }

            // Handle the parent: reduce or delete
            $leftover = $parentQty - $splitQty;

            if ($leftover <= 0) {
                $parent->quantity_remaining = 0;
                $parent->save();
                // OR: $parent->delete(); — pick one policy. See notes below.
            } else {
                $parent->quantity_remaining = $leftover;
                $parent->save();
            }

            DB::commit();

            return response()->json([
                'success'   => true,
                'message'   => __('passwords.batch_split_success', ['count' => count($createdIds)]),
                'reload'    => true,
                'data' => [
                    'parent_id'      => $parent->id,
                    'parent_leftover'=> $leftover,
                    'children'       => $createdIds,
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
            \Log::error('Batch split failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('passwords.split_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    private function buildSplitBatchNumber(string $parentBatch, string $suffix, int $attempt = 0): string
    {
        $suffix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $suffix));
        $suffix = substr($suffix, 0, 40);   // keep it bounded

        $candidate = $parentBatch . '-' . $suffix;

        if ($attempt > 0) {
            $candidate .= '-' . ($attempt + 1);
        }

        return $candidate;
    }

    /**
     * Unassign batches (remove location/department)
     */
    public function unassign(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('edit inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $request->validate([
            'batch_ids' => 'required|array',
            'batch_ids.*' => 'exists:purchase_receipt_items,id',
        ]);

        DB::beginTransaction();

        try {
            $unassigned = 0;
            $skipped = 0;
            $unassignmentDetails = [];

            foreach ($request->batch_ids as $batchId) {
                $batch = PurchaseReceiptItem::query()
                    ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                    ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                    ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
                    ->join('product_variants', 'purchase_order_items.product_variant_id', '=', 'product_variants.id')
                    ->join('products', 'product_variants.product_id', '=', 'products.id')
                    ->where('purchase_orders.tenant_id', $tenantId)
                    ->where('products.inventory_strategy', 'batch')
                    ->where('purchase_receipt_items.id', $batchId)
                    ->select('purchase_receipt_items.*')
                    ->first();

                if (!$batch) {
                    $skipped++;
                    continue;
                }

                // Store old values for logging
                $oldLocationId = $batch->location_id;
                $oldDepartmentId = $batch->department_id;

                $batch->location_id = null;
                $batch->department_id = null;
                $batch->save();

                $unassigned++;

                // ✅ LOG UNASSIGNMENT TO BATCHLOG TABLE
                $this->logBatchUnassignment(
                    $batch,
                    $oldLocationId,
                    $oldDepartmentId,
                    $user,
                    $tenantId
                );

                $unassignmentDetails[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'old_location_id' => $oldLocationId,
                    'old_department_id' => $oldDepartmentId,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$unassigned} batch(es) unassigned successfully." . ($skipped > 0 ? " {$skipped} skipped." : ""),
                'reload' => true,
                'data' => [
                    'unassigned' => $unassigned,
                    'skipped' => $skipped,
                    'details' => $unassignmentDetails,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Batch unassignment failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unassign batches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log batch assignment to BatchLog table
     */
    private function logBatchAssignment(
        $batch,
        $oldLocationId,
        $oldDepartmentId,
        $newLocationId,
        $newDepartmentId,
        $user,
        $tenantId
    ) {
        try {
            $variant       = $batch->purchaseOrderItem->productVariant ?? null;
            $purchaseOrder = $batch->purchaseReceipt->purchaseOrder ?? null;

            $actor       = $this->actorLabel($user);
            $variantName = $variant->name ?? 'Unknown Item';
            $variantSku  = $variant->sku  ?? 'N/A';
            $qty         = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;

            $fromLabel   = $this->contextLabel($oldLocationId, $oldDepartmentId);
            $toLabel     = $this->contextLabel($newLocationId, $newDepartmentId);

            // ── The narrative sentence ───────────────────────────────
            $narrative = sprintf(
                '%s assigned batch "%s" (%s, SKU %s, %s unit(s)) from %s to %s. '
                . 'Batch stock is unchanged — this is a location/department move only. '
                . 'Supplier: %s. PO: %s.',
                $actor,
                $batch->batch_number,
                $variantName,
                $variantSku,
                number_format((float) $qty, 2),
                $fromLabel,
                $toLabel,
                optional(optional($purchaseOrder)->supplier)->name ?? 'N/A',
                $purchaseOrder->po_number ?? 'N/A'
            );

            BatchLog::create([
                'batch_id'              => $batch->id,
                'batch_number'          => $batch->batch_number,
                'variant_id'            => $variant?->id,
                'variant_name'          => $variantName,
                'variant_sku'           => $variantSku,
                'type'                  => BatchLog::TYPE_TRANSFERRED,
                'quantity_change'       => 0,
                'quantity_before'       => $qty,
                'quantity_after'        => $qty,
                'unit_cost'             => $batch->unit_cost ?? 0,
                'total_cost'            => 0,
                'order_id'              => null,
                'order_number'          => null,
                'purchase_order_id'     => $purchaseOrder?->id,
                'purchase_order_number' => $purchaseOrder?->po_number,
                'purchase_receipt_id'   => $batch->purchase_receipt_id,
                'supplier_id'           => $purchaseOrder?->supplier_id,
                'supplier_name'         => optional(optional($purchaseOrder)->supplier)->name,
                'tenant_id'             => $tenantId,
                'location_id'           => $newLocationId,
                'department_id'         => $newDepartmentId,
                'expiry_date'           => $batch->expiry_date,
                'event_date'            => now(),
                'performed_by'          => $user->id,
                'metadata' => [
                    // ★ The full story — what any auditor reads first
                    'narrative'        => $narrative,

                    // Structured fields for filtering/reporting
                    'action'           => 'assigned',
                    'from_location_id' => $oldLocationId,
                    'from_location'    => $this->locationLabel($oldLocationId),
                    'from_department_id' => $oldDepartmentId,
                    'from_department'  => $this->departmentLabel($oldDepartmentId),
                    'to_location_id'   => $newLocationId,
                    'to_location'      => $this->locationLabel($newLocationId),
                    'to_department_id' => $newDepartmentId,
                    'to_department'    => $this->departmentLabel($newDepartmentId),
                    'quantity_at_move' => (float) $qty,
                    'actor_name'       => $user->name,
                    'actor_email'      => $user->email,
                    'actor_id'         => $user->id,
                    'occurred_at'      => now()->toDateTimeString(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to log batch assignment: ' . $e->getMessage(), [
                'batch_id' => $batch->id,
            ]);
        }
    }


    /**
     * Log batch unassignment to BatchLog table
     */
    private function logBatchUnassignment($batch, $oldLocationId, $oldDepartmentId, $user, $tenantId)
    {
        try {
            $variant       = $batch->purchaseOrderItem->productVariant ?? null;
            $purchaseOrder = $batch->purchaseReceipt->purchaseOrder ?? null;

            $actor       = $this->actorLabel($user);
            $variantName = $variant->name ?? 'Unknown Item';
            $variantSku  = $variant->sku  ?? 'N/A';
            $qty         = $batch->quantity_remaining ?? $batch->quantity_received ?? 0;

            $fromLabel = $this->contextLabel($oldLocationId, $oldDepartmentId);

            $narrative = sprintf(
                '%s unassigned batch "%s" (%s, SKU %s, %s unit(s)) from %s. '
                . 'The batch is now unassigned — it is no longer tied to any location or department. '
                . 'Batch stock is unchanged. Supplier: %s. PO: %s.',
                $actor,
                $batch->batch_number,
                $variantName,
                $variantSku,
                number_format((float) $qty, 2),
                $fromLabel,
                optional(optional($purchaseOrder)->supplier)->name ?? 'N/A',
                $purchaseOrder->po_number ?? 'N/A'
            );

            BatchLog::create([
                'batch_id'              => $batch->id,
                'batch_number'          => $batch->batch_number,
                'variant_id'            => $variant?->id,
                'variant_name'          => $variantName,
                'variant_sku'           => $variantSku,
                'type'                  => BatchLog::TYPE_TRANSFERRED,
                'quantity_change'       => 0,
                'quantity_before'       => $qty,
                'quantity_after'        => $qty,
                'unit_cost'             => $batch->unit_cost ?? 0,
                'total_cost'            => 0,
                'order_id'              => null,
                'order_number'          => null,
                'purchase_order_id'     => $purchaseOrder?->id,
                'purchase_order_number' => $purchaseOrder?->po_number,
                'purchase_receipt_id'   => $batch->purchase_receipt_id,
                'supplier_id'           => $purchaseOrder?->supplier_id,
                'supplier_name'         => optional(optional($purchaseOrder)->supplier)->name,
                'tenant_id'             => $tenantId,
                'location_id'           => null,
                'department_id'         => null,
                'expiry_date'           => $batch->expiry_date,
                'event_date'            => now(),
                'performed_by'          => $user->id,
                'metadata' => [
                    'narrative'            => $narrative,
                    'action'               => 'unassigned',
                    'from_location_id'     => $oldLocationId,
                    'from_location'        => $this->locationLabel($oldLocationId),
                    'from_department_id'   => $oldDepartmentId,
                    'from_department'      => $this->departmentLabel($oldDepartmentId),
                    'to_location_id'       => null,
                    'to_department_id'     => null,
                    'quantity_at_move'     => (float) $qty,
                    'actor_name'           => $user->name,
                    'actor_email'          => $user->email,
                    'actor_id'             => $user->id,
                    'occurred_at'          => now()->toDateTimeString(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to log batch unassignment: ' . $e->getMessage(), [
                'batch_id' => $batch->id,
            ]);
        }
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
     * Sentence fragment: "at Main Store (Sales)".
     * Prefers names; falls back to IDs so the string is never broken.
     */
    private function locationLabel(?int $locationId): string
    {
        if (!$locationId) return 'Unassigned';
        $name = Location::find($locationId)?->name;
        return $name ?? "Location #{$locationId}";
    }

    private function departmentLabel(?int $departmentId): string
    {
        if (!$departmentId) return 'Unassigned';
        $name = Department::find($departmentId)?->name;
        return $name ?? "Department #{$departmentId}";
    }

    private function contextLabel(?int $locationId, ?int $departmentId): string
    {
        return 'at ' . $this->locationLabel($locationId)
            . ' (' . $this->departmentLabel($departmentId) . ')';
    }

    /**
     * Log a batch split event to BatchLog.
     * Called once per child batch created during a split.
     */
    private function logBatchSplit($parent, $child, array $split, $user, int $tenantId, ?float $parentQtyAtSplit = null): void
    {
        try {
            $variant       = optional($parent->purchaseOrderItem)->productVariant;
            $purchaseOrder = optional($parent->purchaseReceipt)->purchaseOrder;

            $actor       = $this->actorLabel($user);
            $variantName = $variant?->name ?? 'Unknown Item';
            $variantSku  = $variant?->sku  ?? 'N/A';

            $childQty     = (float) ($child->quantity_remaining ?? 0);
            $parentQtyAtSplit = $parentQtyAtSplit
                ?? (float) ($parent->quantity_remaining ?? $parent->quantity_received ?? 0);
            $parentLeftover   = max(0, $parentQtyAtSplit - $childQty);

            $childContext = $this->contextLabel($child->location_id, $child->department_id);

            $narrative = sprintf(
                '%s split batch "%s" (%s, SKU %s). Created child batch "%s" with %s unit(s) %s. '
                . 'Parent batch "%s" now holds %s unit(s) (%s unit(s) at time of split). '
                . 'Supplier: %s. PO: %s.',
                $actor,
                $parent->batch_number,
                $variantName,
                $variantSku,
                $child->batch_number,
                number_format($childQty, 2),
                $childContext,
                $parent->batch_number,
                number_format($parentLeftover, 2),
                number_format($parentQtyAtSplit, 2),
                optional(optional($purchaseOrder)->supplier)->name ?? 'N/A',
                $purchaseOrder->po_number ?? 'N/A'
            );

            BatchLog::create([
                'batch_id'              => $child->id,
                'batch_number'          => $child->batch_number,
                'variant_id'            => $variant?->id,
                'variant_name'          => $variantName,
                'variant_sku'           => $variantSku,

                'type'                  => BatchLog::TYPE_TRANSFERRED,
                'quantity_change'       => 0,
                'quantity_before'       => 0,
                'quantity_after'        => $childQty,
                'unit_cost'             => $child->unit_cost ?? 0,
                'total_cost'            => ($child->unit_cost ?? 0) * $childQty,

                'purchase_order_id'     => $purchaseOrder?->id,
                'purchase_order_number' => $purchaseOrder?->po_number,
                'purchase_receipt_id'   => $child->purchase_receipt_id,
                'supplier_id'           => $purchaseOrder?->supplier_id,
                'supplier_name'         => optional(optional($purchaseOrder)->supplier)->name,

                'tenant_id'             => $tenantId,
                'location_id'           => $child->location_id,
                'department_id'         => $child->department_id,
                'expiry_date'           => $child->expiry_date,

                'event_date'            => now(),
                'performed_by'          => $user->id,

                'metadata' => [
                    'narrative'          => $narrative,
                    'action'             => 'split',
                    'parent_batch_id'    => $parent->id,
                    'parent_batch_number'=> $parent->batch_number,
                    'parent_quantity_at_split' => $parentQtyAtSplit,
                    'parent_leftover'    => $parentLeftover,
                    'child_quantity'     => $childQty,
                    'child_location_id'  => $child->location_id,
                    'child_location'     => $this->locationLabel($child->location_id),
                    'child_department_id' => $child->department_id,
                    'child_department'   => $this->departmentLabel($child->department_id),
                    'actor_name'         => $user->name,
                    'actor_email'        => $user->email,
                    'actor_id'           => $user->id,
                    'occurred_at'        => now()->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log batch split: ' . $e->getMessage(), [
                'child_id'  => $child->id  ?? null,
                'parent_id' => $parent->id ?? null,
            ]);
        }
    }

    /**
     * Get batch details for selected IDs
     */
    public function details(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view inventory')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        $request->validate([
            'batch_ids' => 'required|array',
            'batch_ids.*' => 'exists:purchase_receipt_items,id',
        ]);

        $batches = PurchaseReceiptItem::query()
            ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
            ->join('product_variants', 'purchase_order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('purchase_orders.tenant_id', $tenantId)
            ->where('products.inventory_strategy', 'batch')
            ->whereIn('purchase_receipt_items.id', $request->batch_ids)
            ->select(
                'purchase_receipt_items.*',
                'product_variants.name as variant_name',
                'products.name as product_name'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $batches->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity_remaining' => $batch->quantity_remaining ?? $batch->quantity_received,
                    'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : null,
                    'product_name' => $batch->variant_name ?? $batch->product_name ?? 'N/A',
                    'location_id' => $batch->location_id,
                    'department_id' => $batch->department_id,
                ];
            })
        ]);
    }
}