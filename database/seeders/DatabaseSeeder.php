<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Heavy test dataset: ~9,000 rows total across all tables.
     * Order matters - suppliers/customers/medicines have no dependencies.
     * Batches and purchases both restock medicines, so they run before
     * sales, which consume stock and must run last.
     */
    public function run(): void
    {
        $this->call([
            SupplierSeeder::class,       // 20
            CustomerSeeder::class,       // 150
            MedicineSeeder::class,       // 80
            MedicineBatchSeeder::class,  // ~100
            PurchaseSeeder::class,       // 300 purchases, ~900 items
            SaleSeeder::class,           // 2,200 sales, ~5,300 items
            SettingSeeder::class,
        ]);
    }
}
