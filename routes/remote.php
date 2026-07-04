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
});







// Public Invoice Routes (no authentication required)
Route::prefix('public/invoices')->name('public.invoice.')->group(function () {
    Route::get('/{token}', [App\Http\Controllers\Public\InvoiceController::class, 'show'])->name('show');
    Route::get('/{token}/pay', [App\Http\Controllers\Public\InvoiceController::class, 'pay'])->name('pay');
    Route::post('/{token}/payment', [App\Http\Controllers\Public\InvoiceController::class, 'processPayment'])->name('process-payment');
});




