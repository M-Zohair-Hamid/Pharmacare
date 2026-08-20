<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PharmacareSeeder extends Seeder
{
    public function run(): void
    {
        // Comment out or remove the early return
        // if (Medicine::count() > 0) {
        //     return;
        // }

        $medicineCount = (int) (env('SEED_MEDICINES', 20000));
        $saleCount = (int) (env('SEED_SALES', 20000));

        $this->command?->info("Seeding {$medicineCount} medicines and {$saleCount} sales...");

        $start = microtime(true);

        // Seed suppliers (keep your existing ones or create more)
        $this->seedSuppliers();

        // Seed medicines in bulk
        $medicineIds = $this->seedMedicines($medicineCount);

        // Seed sales
        $this->seedSales($medicineIds, $saleCount);

        $elapsed = round(microtime(true) - $start, 2);

        $this->command?->info("Done in {$elapsed}s.");
        $this->command?->info('Medicines: ' . Medicine::count());
        $this->command?->info('Sales: ' . Sale::count());
    }

    protected function seedSuppliers(): void
    {
        if (Supplier::count() > 0) {
            return;
        }

        Supplier::insert([
            [
                'name' => 'MediSource Distributors',
                'contact_person' => 'Sarah Chen',
                'email' => 'sarah@medisource.example',
                'phone' => '+1 555-0101',
                'address' => '120 Health Parkway, Boston, MA',
                'notes' => 'Primary supplier for OTC and antibiotics',
                'created_at' => now(),
            ],
            [
                'name' => 'Apex Pharma Wholesale',
                'contact_person' => 'Marcus Reid',
                'email' => 'marcus@apexpharma.example',
                'phone' => '+1 555-0144',
                'address' => '88 Warehouse Blvd, Newark, NJ',
                'notes' => 'Fast restock on chronic care meds',
                'created_at' => now(),
            ],
            [
                'name' => 'GreenLeaf Medical Supply',
                'contact_person' => 'Priya Nair',
                'email' => 'priya@greenleaf.example',
                'phone' => '+1 555-0188',
                'address' => '45 Wellness Ave, Austin, TX',
                'notes' => null,
                'created_at' => now(),
            ],
        ]);
    }

    protected function seedMedicines(int $count): array
    {
        $categories = ['Antibiotic', 'Analgesic', 'NSAID', 'Antidiabetic', 'Cardiovascular', 'Antihistamine', 'Gastrointestinal', 'Supplement', 'Respiratory'];
        $types = ['Tablet', 'Capsule', 'Strip', 'Bottle', 'Box', 'Other'];
        $manufacturers = ['Pfizer', 'GSK', 'Teva', 'Lupin', 'Sandoz', 'Zyrtec', 'Advil', 'Ventolin', 'Nature Made', 'Zithromax'];

        $chunkSize = 500;
        $ids = [];
        $rows = [];

        $this->command?->info("Generating {$count} medicines...");

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'name' => 'Medicine ' . $i,
                'generic_name' => 'Generic ' . $i,
                'category' => $categories[array_rand($categories)],
                'medicine_type' => $types[array_rand($types)],
                'manufacturer' => $manufacturers[array_rand($manufacturers)],
                'unit_price' => round(rand(300, 3000) / 100, 2),
                'cost_price' => round(rand(100, 1500) / 100, 2),
                'quantity' => rand(0, 500),
                'expiry_date' => now()->addDays(rand(30, 720)),
                'description' => 'Test medicine ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) === $chunkSize) {
                DB::table('medicines')->insert($rows);
                $rows = [];
                if ($i % 5000 === 0) {
                    $this->command?->info("  ...{$i} medicines inserted");
                }
            }
        }

        // Insert remaining
        if (!empty($rows)) {
            DB::table('medicines')->insert($rows);
        }

        return DB::table('medicines')->pluck('id')->all();
    }

    protected function seedSales(array $medicineIds, int $saleCount): void
    {
        if (empty($medicineIds)) {
            return;
        }

        $paymentMethods = ['cash', 'card'];
        $customerNames = [null, 'Walk-in', 'Ahmad Raza', 'Fatima Khan', 'Bilal Hussain', 'Sana Malik'];

        $this->command?->info("Creating {$saleCount} sales...");

        for ($i = 1; $i <= $saleCount; $i++) {
            $itemCount = rand(1, 4);
            $selectedIds = (array) array_rand(array_flip($medicineIds), min($itemCount, count($medicineIds)));

            $items = [];
            $total = 0;

            foreach ($selectedIds as $medicineId) {
                $medicine = Medicine::find($medicineId);
                if (!$medicine) {
                    continue;
                }

                $qty = rand(1, 5);
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
                'notes' => null,
            ]);

            $sale->items()->createMany($items);

            if ($i % 1000 === 0) {
                $this->command?->info("  ...{$i} sales created");
            }
        }
    }
}
