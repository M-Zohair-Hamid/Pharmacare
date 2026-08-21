<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $namedFirms = [
            'GSK Pakistan Ltd', 'Getz Pharma (Pvt) Ltd', 'Abbott Laboratories Pakistan',
            'Sanofi-Aventis Pakistan', 'Highnoon Laboratories', 'Searle Pakistan Ltd',
            'Martin Dow Pharmaceuticals', 'Ferozsons Laboratories', 'Novartis Pharma Pakistan',
            'Pfizer Pakistan Ltd', 'Bosch Pharmaceuticals', 'Wilson\'s Pharmaceuticals',
            'ATCO Laboratories', 'OBS Pakistan (Pvt) Ltd', 'Hilton Pharma',
            'Barrett Hodgson Pakistan', 'CCL Pharmaceuticals', 'Werrick Pharmaceuticals',
            'Scilife Pharma (Pvt) Ltd', 'Genome Pharmaceuticals',
        ];

        $contactFirst = ['Faisal', 'Ayesha', 'Usman', 'Zara', 'Bilal', 'Hina', 'Kamran', 'Sana', 'Adeel', 'Nadia'];
        $contactLast = ['Mahmood', 'Raza', 'Tariq', 'Sheikh', 'Ahmed', 'Malik', 'Yousuf', 'Aslam', 'Farooq', 'Baig'];
        $cities = ['Karachi', 'Lahore', 'Islamabad', 'Faisalabad', 'Rawalpindi', 'Multan', 'Peshawar'];

        $rows = [];
        foreach ($namedFirms as $i => $name) {
            $contact = $contactFirst[$i % count($contactFirst)] . ' ' . $contactLast[($i + 3) % count($contactLast)];
            $slug = strtolower(str_replace([' ', "'", '(', ')'], ['', '', '', ''], $name));
            $rows[] = [
                'name' => $name,
                'contact_person' => $contact,
                'email' => substr($slug, 0, 20) . '@' . 'supplier' . $i . '.com.pk',
                'phone' => sprintf('0%d1-111-%03d-%03d', random_int(2, 4), random_int(100, 999), random_int(100, 999)),
                'address' => $cities[$i % count($cities)] . ', Pakistan',
                'notes' => null,
                'created_at' => Carbon::now()->subDays(random_int(30, 700)),
            ];
        }

        DB::table('suppliers')->insert($rows);
    }
}
