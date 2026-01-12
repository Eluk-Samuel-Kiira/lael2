<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50, 500);
        $discountTotal = $this->faker->randomFloat(2, 0, 50);
        $taxTotal = $this->faker->randomFloat(2, 5, 30);
        $total = $subtotal - $discountTotal + $taxTotal;
        $paidAmount = $this->faker->randomFloat(2, 0, $total);
        
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'customer_id' => Customer::inRandomOrder()->first()->id,
            'customer_name' => $this->faker->name(),
            'location_id' => Location::inRandomOrder()->first()->id,
            'department_id' => Department::inRandomOrder()->first()->id,
            'order_number' => strtoupper($this->faker->unique()->bothify('ORD###??')),
            'type' => $this->faker->randomElement(['sale', 'return', 'quote', 'layby']),
            'status' => $this->faker->randomElement(['draft', 'confirmed', 'processing', 'completed', 'cancelled', 'refunded']),
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'balance_due' => max(0, $total - $paidAmount),
            'source' => $this->faker->randomElement(['pos', 'online', 'phone', 'mobile']),
            'notes' => $this->faker->sentence(),
            'created_by' => User::inRandomOrder()->first()->id,
        ];
    }
}
