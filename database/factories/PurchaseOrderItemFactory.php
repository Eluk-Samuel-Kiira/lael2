<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ProductVariant;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition()
    {
        // Get a purchase order to determine tenant
        $purchaseOrder = PurchaseOrder::inRandomOrder()->first();
        
        if (!$purchaseOrder) {
            $purchaseOrder = PurchaseOrder::factory()->create();
        }
        
        // Get a random existing product variant for the same tenant
        $productVariant = ProductVariant::where('tenant_id', $purchaseOrder->tenant_id)
            ->inRandomOrder()
            ->first();
        
        // If no product variant exists, create one
        if (!$productVariant) {
            $productVariant = ProductVariant::factory()->create([
                'tenant_id' => $purchaseOrder->tenant_id,
            ]);
        }
        
        // Get payment methods for this tenant
        $paymentMethods = PaymentMethod::where('tenant_id', $purchaseOrder->tenant_id)
            ->where('is_active', true)
            ->get();
        
        // If no payment methods, create one
        if ($paymentMethods->isEmpty()) {
            $paymentMethods = collect([PaymentMethod::factory()->create([
                'tenant_id' => $purchaseOrder->tenant_id,
                'created_by' => \App\Models\User::where('tenant_id', $purchaseOrder->tenant_id)->first()->id ?? 1,
            ])]);
        }
        
        $quantity = $this->faker->numberBetween(1, 50);
        $unitCost = $this->faker->randomFloat(2, 1, 100); 
        $taxAmount = ($quantity * $unitCost) * 0.1;
        $totalCost = ($quantity * $unitCost) + $taxAmount;
        
        $paymentMethod = $paymentMethods->random();
        $paymentStatuses = ['pending', 'partial', 'paid', 'overdue'];
        $paymentStatus = $paymentStatuses[array_rand($paymentStatuses)];

        return [
            'purchase_order_id' => $purchaseOrder->id,
            'product_variant_id' => $productVariant->id,
            'product_name' => $productVariant->name,
            'sku' => $productVariant->sku,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'tax_amount' => $taxAmount,
            'total_cost' => $totalCost,
            'payment_method_id' => $paymentMethod->id, // Added
            'payment_status' => $paymentStatus, // Added
            'payment_date' => $paymentStatus === 'paid' ? $this->faker->dateTimeBetween('-30 days', 'now') : null, // Added
            'received_quantity' => $this->faker->numberBetween(0, $quantity),
        ];
    }
}