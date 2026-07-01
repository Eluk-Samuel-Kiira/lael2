<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\OrderPayment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderPayment>
 */
class OrderPaymentFactory extends Factory
{
    protected $model = OrderPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get or create order
        $order = Order::inRandomOrder()->first();
        
        if (!$order) {
            $order = Order::factory()->create();
        }
        
        // Get payment method for tenant
        $paymentMethod = $this->getPaymentMethodForTenant($order->tenant_id);
        
        // Get a user for processed_by from the same tenant
        $processedByUser = $this->getUserForTenant($order->tenant_id);
        
        $paymentType = $paymentMethod->type;
        $isCardPayment = in_array($paymentType, ['card', 'credit_card', 'debit_card']);
        $status = $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']);
        
        return [
            'order_id' => $order->id,
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'payment_method_id' => $paymentMethod->id,
            'transaction_id' => $this->faker->uuid(),
            'status' => $status,
            'card_last_four' => $isCardPayment ? $this->faker->numerify('####') : null,
            'card_brand' => $isCardPayment ? $this->faker->randomElement(['Visa', 'Mastercard', 'Amex']) : null,
            'notes' => $this->faker->sentence(),
            'processed_at' => $status === 'completed' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'processed_by' => $processedByUser->id, // Always provide a user ID
        ];
    }

    /**
     * Get or create payment method for tenant.
     */
    private function getPaymentMethodForTenant($tenantId): PaymentMethod
    {
        $paymentMethod = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();
        
        if (!$paymentMethod) {
            $paymentMethod = PaymentMethod::factory()->create([
                'tenant_id' => $tenantId,
                'created_by' => User::where('tenant_id', $tenantId)->first()->id ?? 1,
            ]);
        }
        
        return $paymentMethod;
    }

    /**
     * Get a user for processed_by from the tenant.
     */
    private function getUserForTenant($tenantId): User
    {
        $user = User::where('tenant_id', $tenantId)
            ->inRandomOrder()
            ->first();
        
        if (!$user) {
            // Create a user if none exists
            $user = User::factory()->create([
                'tenant_id' => $tenantId,
                'email' => "admin@tenant{$tenantId}.com",
                'password' => bcrypt('password'),
            ]);
            
            // Optionally assign a role
            $user->assignRole('admin');
        }
        
        return $user;
    }

    /**
     * Set payment as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Set payment as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    /**
     * Set payment as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Set payment as refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'processed_at' => now(),
        ]);
    }

    /**
     * Use a specific payment method.
     */
    public function withPaymentMethod($paymentMethodId): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_id' => $paymentMethodId,
        ]);
    }

    /**
     * Use a specific user as processor.
     */
    public function processedBy($userId): static
    {
        return $this->state(fn (array $attributes) => [
            'processed_by' => $userId,
        ]);
    }
}