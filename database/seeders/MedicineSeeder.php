<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $rows = [];

        // 25 curated, recognizable medicines - deliberately mixed stock/expiry
        // states so every dashboard condition (out-of-stock, low, expired,
        // expiring soon, healthy) has real data to render.
        $curated = [
            ['name' => 'Panadol 500mg', 'generic_name' => 'Paracetamol', 'category' => 'Analgesic', 'medicine_type' => 'Tablet', 'tablets_per_box' => 100, 'box_price' => 350, 'manufacturer' => 'GSK Pakistan', 'unit_price' => 4.00, 'cost_price' => 2.50, 'quantity' => 620, 'expiry_date' => $now->copy()->addMonths(18)],
            ['name' => 'Panadol Extra', 'generic_name' => 'Paracetamol + Caffeine', 'category' => 'Analgesic', 'medicine_type' => 'Tablet', 'tablets_per_box' => 100, 'box_price' => 420, 'manufacturer' => 'GSK Pakistan', 'unit_price' => 5.00, 'cost_price' => 3.20, 'quantity' => 340, 'expiry_date' => $now->copy()->addMonths(14)],
            ['name' => 'Brufen 400mg', 'generic_name' => 'Ibuprofen', 'category' => 'Analgesic', 'medicine_type' => 'Tablet', 'tablets_per_box' => 100, 'box_price' => 380, 'manufacturer' => 'Abbott Laboratories', 'unit_price' => 4.50, 'cost_price' => 2.80, 'quantity' => 8, 'expiry_date' => $now->copy()->addMonths(10)],
            ['name' => 'Augmentin 625mg', 'generic_name' => 'Amoxicillin + Clavulanate', 'category' => 'Antibiotic', 'medicine_type' => 'Tablet', 'tablets_per_box' => 20, 'box_price' => 900, 'manufacturer' => 'GSK Pakistan', 'unit_price' => 55.00, 'cost_price' => 38.00, 'quantity' => 150, 'expiry_date' => $now->copy()->addDays(45)],
            ['name' => 'Amoxil 500mg', 'generic_name' => 'Amoxicillin', 'category' => 'Antibiotic', 'medicine_type' => 'Capsule', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'GSK Pakistan', 'unit_price' => 12.00, 'cost_price' => 7.50, 'quantity' => 0, 'expiry_date' => $now->copy()->addMonths(8)],
            ['name' => 'Flagyl 400mg', 'generic_name' => 'Metronidazole', 'category' => 'Antibiotic', 'medicine_type' => 'Tablet', 'tablets_per_box' => 20, 'box_price' => 160, 'manufacturer' => 'Sanofi-Aventis', 'unit_price' => 8.00, 'cost_price' => 5.00, 'quantity' => 210, 'expiry_date' => $now->copy()->addMonths(20)],
            ['name' => 'Risek 20mg', 'generic_name' => 'Omeprazole', 'category' => 'Antacid', 'medicine_type' => 'Capsule', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'Getz Pharma', 'unit_price' => 15.00, 'cost_price' => 9.50, 'quantity' => 95, 'expiry_date' => $now->copy()->addMonths(11)],
            ['name' => 'Buscopan', 'generic_name' => 'Hyoscine Butylbromide', 'category' => 'Antispasmodic', 'medicine_type' => 'Tablet', 'tablets_per_box' => 20, 'box_price' => 200, 'manufacturer' => 'Sanofi-Aventis', 'unit_price' => 10.50, 'cost_price' => 6.80, 'quantity' => 130, 'expiry_date' => $now->copy()->addMonths(16)],
            ['name' => 'Calpol Syrup', 'generic_name' => 'Paracetamol Suspension', 'category' => 'Analgesic', 'medicine_type' => 'Syrup', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'GSK Pakistan', 'unit_price' => 145.00, 'cost_price' => 100.00, 'quantity' => 45, 'expiry_date' => $now->copy()->subDays(10)],
            ['name' => 'Ventolin Inhaler', 'generic_name' => 'Salbutamol', 'category' => 'Respiratory', 'medicine_type' => 'Other', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'GSK Pakistan', 'unit_price' => 320.00, 'cost_price' => 240.00, 'quantity' => 22, 'expiry_date' => $now->copy()->addMonths(9)],
            ['name' => 'Disprin', 'generic_name' => 'Aspirin', 'category' => 'Analgesic', 'medicine_type' => 'Strip', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'Reckitt Benckiser', 'unit_price' => 20.00, 'cost_price' => 13.00, 'quantity' => 300, 'expiry_date' => $now->copy()->addMonths(22)],
            ['name' => 'Centrum', 'generic_name' => 'Multivitamin', 'category' => 'Vitamin', 'medicine_type' => 'Tablet', 'tablets_per_box' => 30, 'box_price' => 1200, 'manufacturer' => 'Pfizer', 'unit_price' => 45.00, 'cost_price' => 32.00, 'quantity' => 60, 'expiry_date' => $now->copy()->addMonths(24)],
            ['name' => 'Surbex-Z', 'generic_name' => 'Vitamin B Complex + Zinc', 'category' => 'Vitamin', 'medicine_type' => 'Tablet', 'tablets_per_box' => 30, 'box_price' => 950, 'manufacturer' => 'Abbott Laboratories', 'unit_price' => 35.00, 'cost_price' => 24.00, 'quantity' => 12, 'expiry_date' => $now->copy()->addMonths(19)],
            ['name' => 'Glucophage 500mg', 'generic_name' => 'Metformin', 'category' => 'Antidiabetic', 'medicine_type' => 'Tablet', 'tablets_per_box' => 20, 'box_price' => 180, 'manufacturer' => 'Martin Dow', 'unit_price' => 9.50, 'cost_price' => 6.00, 'quantity' => 400, 'expiry_date' => $now->copy()->addMonths(13)],
            ['name' => 'Amaryl 2mg', 'generic_name' => 'Glimepiride', 'category' => 'Antidiabetic', 'medicine_type' => 'Tablet', 'tablets_per_box' => 20, 'box_price' => 340, 'manufacturer' => 'Sanofi-Aventis', 'unit_price' => 18.00, 'cost_price' => 12.50, 'quantity' => 5, 'expiry_date' => $now->copy()->addMonths(7)],
            ['name' => 'Lipitor 20mg', 'generic_name' => 'Atorvastatin', 'category' => 'Cardiac', 'medicine_type' => 'Tablet', 'tablets_per_box' => 14, 'box_price' => 700, 'manufacturer' => 'Pfizer', 'unit_price' => 52.00, 'cost_price' => 37.00, 'quantity' => 70, 'expiry_date' => $now->copy()->addMonths(15)],
            ['name' => 'Concor 5mg', 'generic_name' => 'Bisoprolol', 'category' => 'Cardiac', 'medicine_type' => 'Tablet', 'tablets_per_box' => 30, 'box_price' => 600, 'manufacturer' => 'Martin Dow', 'unit_price' => 22.00, 'cost_price' => 15.50, 'quantity' => 0, 'expiry_date' => $now->copy()->addMonths(12)],
            ['name' => 'Cetrizine 10mg', 'generic_name' => 'Cetirizine', 'category' => 'Antihistamine', 'medicine_type' => 'Tablet', 'tablets_per_box' => 100, 'box_price' => 250, 'manufacturer' => 'Highnoon Labs', 'unit_price' => 3.00, 'cost_price' => 1.80, 'quantity' => 500, 'expiry_date' => $now->copy()->addMonths(21)],
            ['name' => 'Claritine', 'generic_name' => 'Loratadine', 'category' => 'Antihistamine', 'medicine_type' => 'Tablet', 'tablets_per_box' => 10, 'box_price' => 300, 'manufacturer' => 'Bayer', 'unit_price' => 32.00, 'cost_price' => 22.00, 'quantity' => 40, 'expiry_date' => $now->copy()->subDays(3)],
            ['name' => 'Betnovate Cream', 'generic_name' => 'Betamethasone', 'category' => 'Dermatology', 'medicine_type' => 'Other', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'GSK Pakistan', 'unit_price' => 110.00, 'cost_price' => 75.00, 'quantity' => 28, 'expiry_date' => $now->copy()->addMonths(17)],
            ['name' => 'ORS Sachet', 'generic_name' => 'Oral Rehydration Salts', 'category' => 'Electrolyte', 'medicine_type' => 'Box', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'Getz Pharma', 'unit_price' => 18.00, 'cost_price' => 11.00, 'quantity' => 800, 'expiry_date' => $now->copy()->addMonths(23)],
            ['name' => 'Strepsils', 'generic_name' => 'Amylmetacresol + Dichlorobenzyl Alcohol', 'category' => 'Cough & Cold', 'medicine_type' => 'Strip', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'Reckitt Benckiser', 'unit_price' => 25.00, 'cost_price' => 16.00, 'quantity' => 14, 'expiry_date' => $now->copy()->addMonths(20)],
            ['name' => 'Benadryl Syrup', 'generic_name' => 'Diphenhydramine', 'category' => 'Cough & Cold', 'medicine_type' => 'Syrup', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'Johnson & Johnson', 'unit_price' => 165.00, 'cost_price' => 115.00, 'quantity' => 33, 'expiry_date' => $now->copy()->addDays(20)],
            ['name' => 'Voltral Gel', 'generic_name' => 'Diclofenac', 'category' => 'Analgesic', 'medicine_type' => 'Other', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'Novartis', 'unit_price' => 195.00, 'cost_price' => 140.00, 'quantity' => 18, 'expiry_date' => $now->copy()->addMonths(9)],
            ['name' => 'Neurobion Injection', 'generic_name' => 'Vitamin B1/B6/B12', 'category' => 'Vitamin', 'medicine_type' => 'Injection', 'tablets_per_box' => null, 'box_price' => null, 'manufacturer' => 'Merck', 'unit_price' => 60.00, 'cost_price' => 42.00, 'quantity' => 55, 'expiry_date' => $now->copy()->subDays(30)],
        ];

        foreach ($curated as $m) {
            $rows[] = $m + ['description' => null];
        }

        // 55 generated variants: real drug stems crossed with strengths,
        // forms, and manufacturers - gives volume/variety without inventing
        // fictional drug names.
        $stems = [
            ['name' => 'Paracetamol', 'category' => 'Analgesic'],
            ['name' => 'Ibuprofen', 'category' => 'Analgesic'],
            ['name' => 'Amoxicillin', 'category' => 'Antibiotic'],
            ['name' => 'Ciprofloxacin', 'category' => 'Antibiotic'],
            ['name' => 'Azithromycin', 'category' => 'Antibiotic'],
            ['name' => 'Omeprazole', 'category' => 'Antacid'],
            ['name' => 'Ranitidine', 'category' => 'Antacid'],
            ['name' => 'Metformin', 'category' => 'Antidiabetic'],
            ['name' => 'Glimepiride', 'category' => 'Antidiabetic'],
            ['name' => 'Atorvastatin', 'category' => 'Cardiac'],
            ['name' => 'Amlodipine', 'category' => 'Cardiac'],
            ['name' => 'Losartan', 'category' => 'Cardiac'],
            ['name' => 'Cetirizine', 'category' => 'Antihistamine'],
            ['name' => 'Loratadine', 'category' => 'Antihistamine'],
            ['name' => 'Diclofenac', 'category' => 'Analgesic'],
            ['name' => 'Metronidazole', 'category' => 'Antibiotic'],
            ['name' => 'Domperidone', 'category' => 'Antacid'],
            ['name' => 'Multivitamin', 'category' => 'Vitamin'],
            ['name' => 'Calcium + Vitamin D3', 'category' => 'Vitamin'],
            ['name' => 'Folic Acid', 'category' => 'Vitamin'],
        ];
        $strengths = ['250mg', '500mg', '10mg', '20mg', '5mg', '100mg'];
        $forms = [
            ['type' => 'Tablet', 'unit_range' => [3, 20]],
            ['type' => 'Capsule', 'unit_range' => [5, 25]],
            ['type' => 'Syrup', 'unit_range' => [80, 200]],
        ];
        $manufacturers = [
            'Highnoon Labs', 'Searle Pakistan', 'ATCO Laboratories', 'Bosch Pharmaceuticals',
            'CCL Pharmaceuticals', 'Hilton Pharma', 'OBS Pakistan', 'Barrett Hodgson',
        ];

        $seen = [];
        $needed = 55;
        $attempts = 0;
        while (count($rows) < 25 + $needed && $attempts < $needed * 10) {
            $attempts++;
            $stem = $stems[array_rand($stems)];
            $strength = $strengths[array_rand($strengths)];
            $form = $forms[array_rand($forms)];
            $manufacturer = $manufacturers[array_rand($manufacturers)];

            $name = $stem['name'] . ' ' . $strength;
            $key = $name . '|' . $manufacturer;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $unitPrice = round(random_int($form['unit_range'][0], $form['unit_range'][1]) + (random_int(0, 99) / 100), 2);
            $costPrice = round($unitPrice * 0.65, 2);
            $qtyRoll = random_int(1, 100);
            // ~8% out of stock, ~15% low stock, rest healthy - same
            // distribution intent as the curated list, spread across volume.
            $quantity = match (true) {
                $qtyRoll <= 8 => 0,
                $qtyRoll <= 23 => random_int(1, 14),
                default => random_int(50, 600),
            };
            $expiryRoll = random_int(1, 100);
            $expiry = match (true) {
                $expiryRoll <= 5 => $now->copy()->subDays(random_int(1, 60)),
                $expiryRoll <= 15 => $now->copy()->addDays(random_int(1, 59)),
                default => $now->copy()->addMonths(random_int(6, 24)),
            };

            $rows[] = [
                'name' => $name,
                'generic_name' => $stem['name'],
                'category' => $stem['category'],
                'medicine_type' => $form['type'],
                'tablets_per_box' => $form['type'] === 'Tablet' ? [10, 20, 30, 100][array_rand([10, 20, 30, 100])] : null,
                'box_price' => $form['type'] === 'Tablet' ? round($unitPrice * (random_int(10, 30)), 2) : null,
                'manufacturer' => $manufacturer,
                'unit_price' => $unitPrice,
                'cost_price' => $costPrice,
                'quantity' => $quantity,
                'expiry_date' => $expiry,
                'description' => null,
            ];
        }

        foreach ($rows as &$row) {
            $row['created_at'] = $now->copy()->subDays(random_int(30, 700));
            $row['updated_at'] = $now;
        }
        unset($row);

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('medicines')->insert($chunk);
        }
    }
}
