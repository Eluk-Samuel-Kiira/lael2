<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            TenantSeeder::class,
            UserSeeder::class, 
            CurrencySeeder::class,
            LocationSeeder::class,
            DepartmentSeeder::class,
            SettingSeeder::class,
            UnitOfMeasureSeeder::class,
            PaymentMethodSeeder::class,
            CategorySeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
            ProductVariantSeeder::class,
            PromotionSeeder::class,
            InventoryItemSeeder::class,
            InventoryTransactionSeeder::class,
            InventoryAdjustmentsSeeder::class,
            TaxSeeder::class,
            VariantTaxSeeder::class,
            CustomerGroupSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
            OrderTaxSeeder::class,
            OrderPaymentSeeder::class,
            SupplierSeeder::class,
            PurchaseOrderSeeder::class,
            PurchaseReceiptSeeder::class,

            // Accounting
            ChartOfAccountSeeder::class,
            AccountingPeriodSeeder::class,
            JournalEntrySeeder::class,
            JournalEntryLineSeeder::class,
            GeneralLedgerSeeder::class,

            // Employement
            EmployeeSeeder::class,
            EmployeePaymentSeeder::class,

            // System tracking
            TenantSettingsSeeder::class,


            ExpenseCategorySeeder::class,
            ExpenseSeeder::class,

            BillingPlanSeeder::class,
            TenantUsageTrackingSeeder::class,

        ]);
    }
}
