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
        $purchaseOrders = PurchaseOrder::whereNotIn('status', ['draft', 'cancelled'])->get();

        $purchaseOrders->each(function ($purchaseOrder) {
            $receiptCount = rand(1, 2);
            
            for ($i = 0; $i < $receiptCount; $i++) {
                $receipt = PurchaseReceipt::factory()->create([
                    'purchase_order_id' => $purchaseOrder->id,
                ]);

                $purchaseOrder->items->each(function ($item) use ($receipt) {
                    PurchaseReceiptItem::factory()->create([
                        'purchase_receipt_id' => $receipt->id,
                        'purchase_order_item_id' => $item->id,
                    ]);
                });
            }
        });
    }
}