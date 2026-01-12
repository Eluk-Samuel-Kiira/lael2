<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseReceiptFactory extends Factory
{
    protected $model = PurchaseReceipt::class;

    public function definition()
    {
        return [
            'purchase_order_id' => PurchaseOrder::inRandomOrder()->first()->id, // Changed from po_id
            'received_by' => User::inRandomOrder()->first()->id,
            'received_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}