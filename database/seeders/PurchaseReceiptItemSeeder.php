<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use Illuminate\Database\Seeder;

class PurchaseReceiptSeeder extends Seeder
{
    public function run()
    {
        // Get purchase orders that are not draft or cancelled
        $purchaseOrders = PurchaseOrder::whereNotIn('status', ['draft', 'cancelled'])->get();

        $purchaseOrders->each(function ($purchaseOrder) {
            // Create 1-2 receipts per purchase order
            $receiptCount = rand(1, 2);
            
            for ($i = 0; $i < $receiptCount; $i++) {
                $receipt = PurchaseReceipt::factory()->create([
                    'po_id' => $purchaseOrder->po_id,
                ]);

                // Create receipt items for each purchase order item
                $purchaseOrder->items->each(function ($item) use ($receipt) {
                    PurchaseReceiptItem::factory()->create([
                        'receipt_id' => $receipt->receipt_id,
                        'po_item_id' => $item->item_id,
                    ]);
                });
            }
        });
    }
}