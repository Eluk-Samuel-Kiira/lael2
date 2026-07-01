<?php

namespace Database\Factories;

use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseReceiptItemFactory extends Factory
{
    protected $model = PurchaseReceiptItem::class;

    public function definition()
    {
        return [
            'purchase_receipt_id' => PurchaseReceipt::inRandomOrder()->first()->id, // Changed from receipt_id
            'purchase_order_item_id' => PurchaseOrderItem::inRandomOrder()->first()->id, // Changed from po_item_id
            'quantity_received' => $this->faker->numberBetween(1, 50),
            'batch_number' => $this->faker->optional()->bothify('BATCH-#####'),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('+1 year', '+3 years'),
        ];
    }
}