<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Currency;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();
        
        if ($tenants->isEmpty()) {
            $tenants = Tenant::factory()->count(3)->create();
        }
        
        foreach ($tenants as $tenant) {
            // Get currencies for this tenant
            $currencies = Currency::where('tenant_id', $tenant->id)->get();
            
            // If no currencies exist, create a base currency
            if ($currencies->isEmpty()) {
                $adminUser = User::where('role_id', 1)->first();
                if (!$adminUser) {
                    $adminUser = User::factory()->create(['role_id' => 1]);
                }
                
                $baseCurrency = Currency::factory()
                    ->baseCurrency()
                    ->withCode('USD')
                    ->forTenant($tenant->id)
                    ->create([
                        'created_by' => $adminUser->id,
                    ]);
                
                $currencies = collect([$baseCurrency]);
            }
            
            // Get users for this tenant
            $users = User::where('tenant_id', $tenant->id)->get();
            
            if ($users->isEmpty()) {
                // Create some users for this tenant
                $users = User::factory()->count(5)->create([
                    'tenant_id' => $tenant->id,
                ]);
            }

            // Create primary location first
            $primaryLocation = Location::factory()
                ->primary()
                ->forTenant($tenant->id)
                ->create([
                    'name' => $tenant->name . ' - Main Store',
                    'address' => fake()->address(), // Using fake() helper
                    'created_by' => $users->random()->id,
                    'manager_id' => $users->random()->id,
                    'currency_id' => $currencies->where('is_base_currency', true)->first()?->id ?? $currencies->first()->id,
                ]);

            // Create additional locations (2-5 per tenant)
            $locationCount = rand(2, 5);
            
            for ($i = 0; $i < $locationCount; $i++) {
                Location::factory()
                    ->forTenant($tenant->id)
                    ->create([
                        'created_by' => $users->random()->id,
                        'manager_id' => $users->random()->id,
                        'currency_id' => $currencies->random()->id,
                        'is_primary' => false,
                    ]);
            }
        }

        // Update some users to have locations
        $this->assignUsersToLocations();
    }

    /**
     * Assign users to locations
     */
    private function assignUsersToLocations(): void
    {
        $locations = Location::all();
        
        foreach ($locations as $location) {
            // Get users for this tenant that don't have a location yet
            $users = User::where('tenant_id', $location->tenant_id)
                ->whereNull('location_id')
                ->inRandomOrder()
                ->take(rand(3, 8))
                ->get();
            
            foreach ($users as $user) {
                $user->update(['location_id' => $location->id]);
            }
        }
    }
}