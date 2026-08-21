<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::current()->update([
            'pharmacy_name' => 'Ahmad Pharmacy',
            'owner_name' => 'Ahmad Khan',
            'phone' => '021-34567890',
            'address' => 'Shop 12, Main Boulevard, Karachi',
            'low_stock_threshold' => 15,
            'refunds_enabled' => true,
            'refund_window_days' => 14,
            'receipt_paper_width' => '80',
        ]);
    }
}
