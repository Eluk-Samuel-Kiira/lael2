<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition()
    {
        $subtotal = $this->faker->randomFloat(2, 100, 10000);
        $taxTotal = $subtotal * 0.1;
        $total = $subtotal + $taxTotal;

        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'supplier_id' => Supplier::inRandomOrder()->first()->id,
            'location_id' => Location::inRandomOrder()->first()->id,
            'po_number' => 'PO-' . $this->faker->unique()->numerify('#####'),
            'status' => $this->faker->randomElement(['draft', 'sent', 'partially_received', 'received', 'cancelled']),
            'expected_delivery_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'notes' => $this->faker->optional()->paragraph(),
            'created_by' => User::inRandomOrder()->first()->id,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
            ];
        });
    }

    public function sent()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'sent',
            ];
        });
    }

    public function received()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'received',
            ];
        });
    }

    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
            ];
        });
    }
}