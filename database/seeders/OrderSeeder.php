<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 orders, each with 1-5 order items
        Order::factory()
            ->count(10)
            ->has(OrderItem::factory()->count(3), 'orderItems')
            ->create();
            
        // Alternatively, create order items separately
        OrderItem::factory()
            ->count(10)
            ->create();
    }
}
