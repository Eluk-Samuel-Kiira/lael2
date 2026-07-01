<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tenant;
use App\Models\CustomerGroup;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = \App\Models\Customer::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'group_id' => CustomerGroup::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'birth_date' => $this->faker->date(),
            'tax_number' => $this->faker->optional()->numerify('#########'),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country_code' => $this->faker->countryCode(),
            'notes' => $this->faker->sentence(),
            'accepts_marketing' => $this->faker->boolean(50),
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
            'is_active' => true,
        ];
    }
}
