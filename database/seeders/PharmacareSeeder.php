<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PharmacareSeeder extends Seeder
{
    public function run(): void
    {
        if (Medicine::count() > 0) {
            return;
        }

        $suppliers = Supplier::insert([
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

        $customers = [
            Customer::create(['name' => 'Walk-in Customer', 'phone' => 'N/A', 'notes' => 'Default counter customer']),
            Customer::create(['name' => 'James Carter', 'email' => 'james.carter@example.com', 'phone' => '+1 555-2001', 'address' => '14 Maple Street']),
            Customer::create(['name' => 'Elena Rodriguez', 'email' => 'elena.r@example.com', 'phone' => '+1 555-2002', 'address' => '902 River Road']),
            Customer::create(['name' => 'Amina Hassan', 'email' => 'amina.h@example.com', 'phone' => '+1 555-2003']),
        ];

        $inDays = fn (int $days) => now()->addDays($days);

        $medicines = [
            Medicine::create(['name' => 'Amoxicillin 500mg', 'generic_name' => 'Amoxicillin', 'category' => 'Antibiotic', 'manufacturer' => 'Pfizer', 'sku' => 'AMX-500', 'unit_price' => 12.50, 'cost_price' => 6.20, 'quantity' => 145, 'reorder_level' => 30, 'expiry_date' => $inDays(280), 'description' => 'Capsule antibiotic for bacterial infections']),
            Medicine::create(['name' => 'Paracetamol 500mg', 'generic_name' => 'Acetaminophen', 'category' => 'Analgesic', 'manufacturer' => 'GSK', 'sku' => 'PCM-500', 'unit_price' => 4.25, 'cost_price' => 1.40, 'quantity' => 320, 'reorder_level' => 50, 'expiry_date' => $inDays(420), 'description' => 'Pain and fever relief tablets']),
            Medicine::create(['name' => 'Ibuprofen 200mg', 'generic_name' => 'Ibuprofen', 'category' => 'NSAID', 'manufacturer' => 'Advil', 'sku' => 'IBU-200', 'unit_price' => 5.75, 'cost_price' => 2.10, 'quantity' => 18, 'reorder_level' => 25, 'expiry_date' => $inDays(190), 'description' => 'Anti-inflammatory pain relief']),
            Medicine::create(['name' => 'Metformin 500mg', 'generic_name' => 'Metformin HCl', 'category' => 'Antidiabetic', 'manufacturer' => 'Teva', 'sku' => 'MET-500', 'unit_price' => 9.90, 'cost_price' => 3.80, 'quantity' => 88, 'reorder_level' => 20, 'expiry_date' => $inDays(360), 'description' => 'Type 2 diabetes management']),
            Medicine::create(['name' => 'Lisinopril 10mg', 'generic_name' => 'Lisinopril', 'category' => 'Cardiovascular', 'manufacturer' => 'Lupin', 'sku' => 'LIS-10', 'unit_price' => 11.20, 'cost_price' => 4.50, 'quantity' => 6, 'reorder_level' => 15, 'expiry_date' => $inDays(45), 'description' => 'ACE inhibitor for blood pressure']),
            Medicine::create(['name' => 'Cetirizine 10mg', 'generic_name' => 'Cetirizine', 'category' => 'Antihistamine', 'manufacturer' => 'Zyrtec', 'sku' => 'CET-10', 'unit_price' => 7.40, 'cost_price' => 2.90, 'quantity' => 210, 'reorder_level' => 40, 'expiry_date' => $inDays(500), 'description' => 'Allergy relief tablets']),
            Medicine::create(['name' => 'Omeprazole 20mg', 'generic_name' => 'Omeprazole', 'category' => 'Gastrointestinal', 'manufacturer' => 'Sandoz', 'sku' => 'OME-20', 'unit_price' => 8.60, 'cost_price' => 3.25, 'quantity' => 0, 'reorder_level' => 20, 'expiry_date' => $inDays(150), 'description' => 'Proton pump inhibitor for acid reflux']),
            Medicine::create(['name' => 'Vitamin D3 1000 IU', 'generic_name' => 'Cholecalciferol', 'category' => 'Supplement', 'manufacturer' => 'Nature Made', 'sku' => 'VIT-D3', 'unit_price' => 14.00, 'cost_price' => 5.50, 'quantity' => 75, 'reorder_level' => 15, 'expiry_date' => $inDays(600), 'description' => 'Daily vitamin D supplement']),
            Medicine::create(['name' => 'Azithromycin 250mg', 'generic_name' => 'Azithromycin', 'category' => 'Antibiotic', 'manufacturer' => 'Zithromax', 'sku' => 'AZI-250', 'unit_price' => 18.75, 'cost_price' => 8.40, 'quantity' => 42, 'reorder_level' => 12, 'expiry_date' => $inDays(25), 'description' => 'Macrolide antibiotic pack']),
            Medicine::create(['name' => 'Salbutamol Inhaler', 'generic_name' => 'Albuterol', 'category' => 'Respiratory', 'manufacturer' => 'Ventolin', 'sku' => 'SAL-INH', 'unit_price' => 22.50, 'cost_price' => 11.00, 'quantity' => 34, 'reorder_level' => 10, 'expiry_date' => $inDays(310), 'description' => 'Rescue inhaler for asthma']),
        ];

        $supplierRecord = Supplier::first();

        $purchase = Purchase::create([
            'supplier_id' => $supplierRecord->id,
            'total_amount' => 620.00,
            'notes' => 'Opening stock replenishment',
        ]);

        $purchase->items()->createMany([
            ['medicine_id' => $medicines[0]->id, 'quantity' => 50, 'unit_cost' => 6.20, 'subtotal' => 310.00],
            ['medicine_id' => $medicines[1]->id, 'quantity' => 100, 'unit_cost' => 1.40, 'subtotal' => 140.00],
            ['medicine_id' => $medicines[5]->id, 'quantity' => 40, 'unit_cost' => 2.90, 'subtotal' => 116.00],
        ]);

        $sale1 = Sale::create([
            'customer_id' => $customers[1]->id,
            'total_amount' => 29.25,
            'payment_method' => 'card',
            'notes' => 'Prescription refill',
        ]);

        $sale1->items()->createMany([
            ['medicine_id' => $medicines[0]->id, 'quantity' => 1, 'unit_price' => 12.50, 'subtotal' => 12.50],
            ['medicine_id' => $medicines[1]->id, 'quantity' => 2, 'unit_price' => 4.25, 'subtotal' => 8.50],
            ['medicine_id' => $medicines[5]->id, 'quantity' => 1, 'unit_price' => 7.40, 'subtotal' => 7.40],
        ]);

        $sale2 = Sale::create([
            'customer_id' => $customers[2]->id,
            'total_amount' => 31.40,
            'payment_method' => 'cash',
        ]);

        $sale2->items()->createMany([
            ['medicine_id' => $medicines[3]->id, 'quantity' => 2, 'unit_price' => 9.90, 'subtotal' => 19.80],
            ['medicine_id' => $medicines[4]->id, 'quantity' => 1, 'unit_price' => 11.20, 'subtotal' => 11.20],
        ]);
    }
}
