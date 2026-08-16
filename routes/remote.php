<?php

use App\Http\Controllers\Api\SyncController;

// ── Public sync API (authenticated via X-Sync-Token header) ──────────────────
// These are called by the local machine's SyncToRemote command.
// No Laravel auth middleware — token validation happens inside the controller.
Route::prefix('sync')->group(function () {
    Route::get( '/status', [SyncController::class, 'status']);
    Route::post('/push',   [SyncController::class, 'push']);
    Route::post('/pull',   [SyncController::class, 'pull']);
});

// ── Frontend routes (require Laravel session auth) ────────────────────────────
// These are called by the browser JS on the LOCAL machine only.
Route::middleware(['auth'])->prefix('sync')->group(function () {

    // Polled every 15s by the badge — reads local sync_status table
    Route::get('/frontend-status', [SyncController::class, 'getFrontendStatus']);

    // Manual sync button — fires artisan pos:sync in background
    // Blocked on remote/cPanel by isLocalMachine() check inside controller
    Route::post('/trigger', [SyncController::class, 'trigger']);
});



use App\Http\Controllers\Api\DependentController;

Route::get('/api/dependent/options', [DependentController::class, 'getOptions'])->name('api.dependent.options');
Route::get('/get-departments', [DependentController::class, 'getDepartmentsByLocation'])->name('get.departments');



use App\Http\Controllers\Orders\InvoiceController;
// Invoice Routes
Route::prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::get('/create', [InvoiceController::class, 'create'])->name('create');
    Route::get('/{id}', [InvoiceController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [InvoiceController::class, 'edit'])->name('edit');
    Route::put('/{id}', [InvoiceController::class, 'update'])->name('update');
    Route::delete('/{id}', [InvoiceController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/send', [InvoiceController::class, 'send'])->name('send');
    Route::post('/{id}/status', [InvoiceController::class, 'updateStatus'])->name('update-status');
    Route::post('/{id}/void', [InvoiceController::class, 'void'])->name('void');
    Route::get('/{id}/pdf', [InvoiceController::class, 'generatePdf'])->name('pdf');
    Route::post('/{id}/record-payment', [InvoiceController::class, 'recordPayment'])->name('record-payment');
    
    Route::post('/{id}/apply-discount', [InvoiceController::class, 'applyDiscount'])->name('apply-discount');
    Route::post('/{id}/remove-discount', [InvoiceController::class, 'removeDiscount'])->name('remove-discount');
});







// Public Invoice Routes (no authentication required)
Route::prefix('public/invoices')->name('public.invoice.')->group(function () {
    Route::get('/{token}', [App\Http\Controllers\Public\InvoiceController::class, 'show'])->name('show');
    Route::get('/{token}/pay', [App\Http\Controllers\Public\InvoiceController::class, 'pay'])->name('pay');
    Route::post('/{token}/payment', [App\Http\Controllers\Public\InvoiceController::class, 'processPayment'])->name('process-payment');
});


use App\Http\Controllers\Manufacturing\ProductionOrderController;

Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::prefix('production-orders')->name('production-orders.')->group(function () {
        Route::get('/', [ProductionOrderController::class, 'index'])->name('index');
        Route::post('/', [ProductionOrderController::class, 'store'])->name('store');
        Route::post('/{id}/start-with-payment', [ProductionOrderController::class, 'startWithPayment'])->name('start-with-payment');
        Route::post('/{id}/start', [ProductionOrderController::class, 'start'])->name('start');
        // Route::post('/{id}/complete', [ProductionOrderController::class, 'complete'])->name('complete');
        Route::post('/{id}/cancel', [ProductionOrderController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/update-output', [ProductionOrderController::class, 'updateOutput'])->name('update-output');
        Route::get('/available-batches', [ProductionOrderController::class, 'getAvailableBatches'])->name('available-batches');
        Route::post('{id}/complete-with-outputs', [ProductionOrderController::class, 'completeWithOutputs'])
            ->name('complete-with-outputs');
    });
});




// Route::get('/debug/batches', function() {
//     $user = Auth::user();
//     $tenantId = $user->tenant_id;
//     $locationId = $user->location_id;
    
//     // Get all batches for this tenant
//     $batches = DB::table('purchase_receipt_items as pri')
//         ->join('purchase_receipts as pr', 'pri.purchase_receipt_id', '=', 'pr.id')
//         ->join('purchase_orders as po', 'pr.purchase_order_id', '=', 'po.id')
//         ->join('purchase_order_items as poi', 'pri.purchase_order_item_id', '=', 'poi.id')
//         ->leftJoin('product_variants as pv', 'poi.product_variant_id', '=', 'pv.id')
//         ->leftJoin('products as p', 'pv.product_id', '=', 'p.id')
//         ->where('po.tenant_id', $tenantId)
//         ->select(
//             'pri.id',
//             'pri.batch_number',
//             'pri.quantity_received',
//             'pri.quantity_remaining',
//             'pri.location_id',
//             'pri.department_id',
//             'pv.id as variant_id',
//             'pv.name as variant_name',
//             'p.name as product_name',
//             'p.inventory_strategy'
//         )
//         ->get();
    
//     return response()->json([
//         'total' => $batches->count(),
//         'batches' => $batches,
//         'location_id' => $locationId,
//         'assigned_to_current_location' => $batches->filter(function($b) use ($locationId) {
//             return $b->location_id == $locationId;
//         })->values()
//     ]);
// })->middleware(['auth']);

