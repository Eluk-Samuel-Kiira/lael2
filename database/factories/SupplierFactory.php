<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition()
    {
        // Generate dates and ensure they're valid by converting to UTC format
        $createdAt = $this->faker->dateTimeBetween('-1 year', 'now');
        $updatedAt = $this->faker->dateTimeBetween('-1 year', 'now');
        
        // Ensure the dates are valid by converting to Carbon and formatting
        $createdAt = Carbon::instance($createdAt)->format('Y-m-d H:i:s');
        $updatedAt = Carbon::instance($updatedAt)->format('Y-m-d H:i:s');

        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'tax_number' => $this->faker->numerify('TAX#####'),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country_code' => $this->faker->randomElement(['US', 'CA', 'GB', 'AU']),
            'payment_terms' => $this->faker->randomElement([15, 30, 45, 60]),
            'notes' => $this->faker->optional()->paragraph(),
            'is_active' => $this->faker->boolean(90),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'created_by' => User::inRandomOrder()->first()->id,
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}