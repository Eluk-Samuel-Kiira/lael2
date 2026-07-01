<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InventoryItems;
use App\Models\ProductVariant;
use App\Models\Department;
use App\Models\Location;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting InventoryItemSeeder...');
        
        // Get all existing combinations in one query
        $existingCombos = InventoryItems::select('variant_id', 'department_id', 'location_id')
            ->get()
            ->map(fn($item) => "{$item->variant_id}-{$item->department_id}-{$item->location_id}")
            ->toArray();
        
        $this->command->info('Found ' . count($existingCombos) . ' existing inventory items');
        
        // Get counts for debugging
        $variantCount = ProductVariant::where('is_active', true)->count();
        $departmentCount = Department::where('isActive', 1)->count();
        $locationCount = Location::where('is_active', 1)->count();
        
        $this->command->info("Available: {$variantCount} variants, {$departmentCount} departments, {$locationCount} locations");
        
        // Calculate maximum possible unique combinations
        $maxPossibleCombinations = $variantCount * $departmentCount * $locationCount;
        $availableSlots = $maxPossibleCombinations - count($existingCombos);
        
        $this->command->info("Maximum possible combinations: {$maxPossibleCombinations}");
        $this->command->info("Available slots: {$availableSlots}");
        
        if ($availableSlots <= 0) {
            $this->command->error('No available unique combinations left!');
            return;
        }
        
        // Determine how many to create (max 7 or available slots)
        $toCreate = min(7, $availableSlots);
        $this->command->info("Attempting to create {$toCreate} inventory items...");
        
        // Create records
        $created = 0;
        $attempts = 0;
        $maxAttempts = 100; // Safety limit
        
        while ($created < $toCreate && $attempts < $maxAttempts) {
            $attempts++;
            
            // Get random IDs directly - much faster than fetching models
            $variantId = ProductVariant::where('is_active', true)->inRandomOrder()->value('id');
            $departmentId = Department::where('isActive', 1)->inRandomOrder()->value('id');
            $locationId = Location::where('is_active', 1)->inRandomOrder()->value('id');
            
            $comboKey = "{$variantId}-{$departmentId}-{$locationId}";
            
            // Check if this combination is already used
            if (!in_array($comboKey, $existingCombos)) {
                try {
                    // Create the inventory item using the factory
                    InventoryItems::factory()->create([
                        'variant_id' => $variantId,
                        'department_id' => $departmentId,
                        'location_id' => $locationId,
                    ]);
                    
                    // Add to existing combos to prevent duplicates in this batch
                    $existingCombos[] = $comboKey;
                    $created++;
                    
                    $this->command->info("  ✓ Created item {$created}/{$toCreate} (Combination: {$comboKey})");
                    
                } catch (\Exception $e) {
                    $this->command->warn("  ✗ Failed to create item, retrying...");
                    continue;
                }
            }
        }
        
        if ($created < $toCreate) {
            $this->command->warn("Only created {$created} out of {$toCreate} inventory items.");
        } else {
            $this->command->info("Successfully created {$created} inventory items!");
        }
    }
}