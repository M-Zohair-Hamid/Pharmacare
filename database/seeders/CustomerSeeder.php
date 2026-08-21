<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $firstNames = [
            'Ahmed', 'Sara', 'Bilal', 'Fatima', 'Hassan', 'Ayesha', 'Usman', 'Mahnoor',
            'Zeeshan', 'Rabia', 'Omar', 'Nida', 'Tariq', 'Sana', 'Kamran', 'Hira',
            'Adeel', 'Zainab', 'Faisal', 'Amna', 'Junaid', 'Iqra', 'Rashid', 'Komal',
            'Waqas', 'Sadia', 'Imran', 'Anum', 'Salman', 'Mehwish', 'Asad', 'Farah',
            'Naveed', 'Sidra', 'Shahzad', 'Bushra', 'Ali', 'Maria', 'Danish', 'Hafsa',
        ];
        $lastNames = [
            'Raza', 'Khan', 'Hussain', 'Iqbal', 'Ali', 'Siddiqui', 'Farooq', 'Aslam',
            'Baig', 'Yousuf', 'Sheikh', 'Malik', 'Mehmood', 'Javed', 'Shah', 'Qureshi',
            'Abbasi', 'Chaudhry', 'Butt', 'Ansari', 'Rizvi', 'Soomro', 'Bhatti', 'Gill',
        ];

        $count = 150;
        $used = [];
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            do {
                $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
                $phone = sprintf('03%02d-%07d', random_int(0, 49), random_int(1000000, 9999999));
                $key = $name . $phone;
            } while (isset($used[$key]));
            $used[$key] = true;

            $rows[] = [
                'name' => $name,
                'email' => null,
                'phone' => $phone,
                'address' => null,
                'notes' => null,
                'created_at' => Carbon::now()->subDays(random_int(1, 700)),
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('customers')->insert($chunk);
        }
    }
}
