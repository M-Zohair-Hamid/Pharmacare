<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Stress-test seeder — generates a large, realistic volume of data so you can
 * see how the app and SQLite behave under real-world-sized load.
 *
 * Usage (from the project root):
 *   php artisan db:seed --class=Database\\Seeders\\VolumeTestSeeder
 *
 * Tune the numbers below before running, or override via env vars, e.g.:
 *   SEED_MEDICINES=2000 SEED_SALES=20000 php artisan db:seed --class=Database\\Seeders\\VolumeTestSeeder
 */
class VolumeTestSeeder extends Seeder
{
    public function run(): void
    {
        $medicineCount = (int) (env('SEED_MEDICINES', 500));
        $batchesPerMedicine = (int) (env('SEED_BATCHES_PER_MEDICINE', 2));
        $saleCount = (int) (env('SEED_SALES', 5000));
        $maxItemsPerSale = (int) (env('SEED_MAX_ITEMS_PER_SALE', 4));

        $this->command?->info("Seeding {$medicineCount} medicines, ~{$batchesPerMedicine} batches each, {$saleCount} sales...");

        $start = microtime(true);

        $supplierIds = $this->seedSuppliers();
        $medicineIds = $this->seedMedicines($medicineCount);
        $this->seedBatches($medicineIds, $batchesPerMedicine);
        $this->seedSales($medicineIds, $saleCount, $maxItemsPerSale);

        $elapsed = round(microtime(true) - $start, 2);

        $this->command?->info("Done in {$elapsed}s.");
        $this->command?->info('Medicines: ' . Medicine::count());
        $this->command?->info('Medicine batches: ' . MedicineBatch::count());
        $this->command?->info('Sales: ' . Sale::count());
        $this->command?->info('Sale items: ' . SaleItem::count());

        $dbPath = database_path('database.sqlite');
        if (file_exists($dbPath)) {
            $sizeMb = round(filesize($dbPath) / 1024 / 1024, 2);
            $this->command?->info("database.sqlite size: {$sizeMb} MB");
        }
    }

    protected function seedSuppliers(): array
    {
        if (Supplier::count() > 0) {
            return Supplier::pluck('id')->all();
        }

        $rows = [];
        for ($i = 1; $i <= 10; $i++) {
            $rows[] = [
                'name' => "Test Supplier {$i}",
                'contact_person' => "Contact Person {$i}",
                'email' => "supplier{$i}@example.test",
                'phone' => '+1 555-01' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'address' => "{$i} Distribution Way",
                'notes' => null,
                'created_at' => now(),
            ];
        }
        Supplier::insert($rows);

        return Supplier::pluck('id')->all();
    }

    /**
     * Bulk-inserts medicines directly via the query builder (bypassing
     * Eloquent events) since we don't need per-row model hooks here and this
     * is dramatically faster at high volumes.
     */
    protected function seedMedicines(int $count): array
    {
        $categories = ['Antibiotic', 'Analgesic', 'NSAID', 'Antidiabetic', 'Cardiovascular', 'Antihistamine', 'Gastrointestinal', 'Supplement', 'Respiratory', 'General'];
        $types = Medicine::TYPES;
        $manufacturers = ['Pfizer', 'GSK', 'Sandoz', 'Teva', 'Lupin', 'Novartis', 'Bayer', 'Cipla', 'Sanofi', 'Abbott'];

        $chunkSize = 500;
        $ids = [];
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $type = $types[array_rand($types)];
            $isTablet = $type === 'Tablet';

            $rows[] = [
                'name' => "Test Medicine {$i}",
                'generic_name' => "Generic Compound {$i}",
                'category' => $categories[array_rand($categories)],
                'medicine_type' => $type,
                'tablets_per_box' => $isTablet ? random_int(10, 30) : null,
                'box_price' => $isTablet ? round(random_int(500, 5000) / 10, 2) : null,
                'manufacturer' => $manufacturers[array_rand($manufacturers)],
                'unit_price' => round(random_int(50, 3000) / 10, 2),
                'cost_price' => round(random_int(20, 1500) / 10, 2),
                'quantity' => random_int(0, 500),
                'expiry_date' => now()->addDays(random_int(-10, 720)),
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) === $chunkSize || $i === $count) {
                DB::table('medicines')->insert($rows);
                $rows = [];
                if ($this->command && $i % 5000 === 0) {
                    $this->command->info("  ...{$i} medicines inserted");
                }
            }
        }

        return DB::table('medicines')->orderBy('id')->pluck('id')->all();
    }

    protected function seedBatches(array $medicineIds, int $perMedicine): void
    {
        if ($perMedicine <= 0) {
            return;
        }

        $chunkSize = 500;
        $rows = [];
        $count = 0;

        foreach ($medicineIds as $medicineId) {
            for ($b = 0; $b < $perMedicine; $b++) {
                $rows[] = [
                    'medicine_id' => $medicineId,
                    'batch_number' => 'BATCH-' . strtoupper(bin2hex(random_bytes(3))),
                    'quantity' => random_int(10, 200),
                    'received_date' => now()->subDays(random_int(0, 180)),
                    'expiry_date' => now()->addDays(random_int(30, 720)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $count++;

                if (count($rows) === $chunkSize) {
                    DB::table('medicine_batches')->insert($rows);
                    $rows = [];
                }
            }
        }

        if (!empty($rows)) {
            DB::table('medicine_batches')->insert($rows);
        }
    }

    /**
     * Sales go through Sale::create() (not raw inserts) because bill_code
     * generation and default timestamps live in model hooks. This is the
     * realistic path — it's also the slowest part of seeding, since each
     * sale opens its own locking transaction, same as real checkouts do.
     */
    protected function seedSales(array $medicineIds, int $saleCount, int $maxItemsPerSale): void
    {
        $paymentMethods = ['cash', 'card'];
        $customerNames = [null, 'Walk-in', 'Ahmad Raza', 'Fatima Khan', 'Bilal Hussain', 'Sana Malik', null];

        for ($i = 1; $i <= $saleCount; $i++) {
            $itemCount = random_int(1, max(1, $maxItemsPerSale));
            $chosenMedicineIds = (array) array_rand(array_flip($medicineIds), min($itemCount, count($medicineIds)));

            $items = [];
            $total = 0;

            foreach ($chosenMedicineIds as $medicineId) {
                $medicine = Medicine::find($medicineId);
                if (!$medicine) {
                    continue;
                }

                $qty = random_int(1, 5);
                $unitPrice = (float) $medicine->unit_price;
                $subtotal = round($unitPrice * $qty, 2);
                $total += $subtotal;

                $items[] = [
                    'medicine_id' => $medicine->id,
                    'quantity' => $qty,
                    'unit_type' => $medicine->medicine_type,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }

            if (empty($items)) {
                continue;
            }

            $sale = Sale::create([
                'customer_name' => $customerNames[array_rand($customerNames)],
                'total_amount' => round($total, 2),
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
            ]);

            $sale->items()->createMany($items);

            if ($this->command && $i % 1000 === 0) {
                $this->command->info("  ...{$i} sales inserted");
            }
        }
    }
}
