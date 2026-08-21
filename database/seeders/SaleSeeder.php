<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $customers = DB::table('customers')->select('id', 'name')->get();
        $medicines = DB::table('medicines')->select('id', 'unit_price', 'medicine_type', 'quantity')->get()
            ->keyBy('id');

        if ($medicines->isEmpty()) {
            return;
        }

        // Track remaining stock in memory instead of re-querying per item -
        // at this volume (thousands of items) per-row fresh() queries would
        // be far too slow. Batches are left untouched here; this bulk layer
        // only tracks the medicines.quantity total, not FEFO consumption -
        // the curated MedicineBatchSeeder subset covers FEFO-specific testing.
        $stock = $medicines->map(fn ($m) => (int) $m->quantity)->all();
        $medicineIds = array_keys($stock);

        $now = Carbon::now('Asia/Karachi');
        $saleCount = 2200;
        $paymentMethods = ['cash', 'card', 'easypaisa', 'jazzcash'];
        $refundReasons = ['Customer changed mind', 'Wrong medicine dispensed', 'Adverse reaction reported', 'Duplicate purchase'];

        // Sequential bill codes continuing from wherever bill_code_counters
        // currently is, so live app usage after seeding won't collide.
        $counter = DB::table('bill_code_counters')->where('id', 1)->first();
        $nextCode = $counter ? $counter->next_value : 1;

        $saleRows = [];
        $saleMeta = []; // index => ['days_ago' => int, 'created_at' => Carbon]

        for ($i = 0; $i < $saleCount; $i++) {
            $daysAgo = random_int(0, 365);
            $createdAt = $now->copy()->subDays($daysAgo)->subMinutes(random_int(0, 600));
            $useCustomer = random_int(1, 100) <= 60 && $customers->isNotEmpty();
            $customer = $useCustomer ? $customers->random() : null;

            $saleRows[] = [
                'bill_code' => str_pad((string) $nextCode, 6, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id ?? null,
                'customer_name' => $customer->name ?? 'Walk-in Customer',
                'subtotal' => 0,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'notes' => null,
                'created_at' => $createdAt,
                'refunded_at' => null,
                'refund_reason' => null,
            ];
            $saleMeta[] = ['days_ago' => $daysAgo, 'created_at' => $createdAt];
            $nextCode++;
        }

        foreach (array_chunk($saleRows, 300) as $chunk) {
            DB::table('sales')->insert($chunk);
        }
        DB::table('bill_code_counters')->where('id', 1)->update(['next_value' => $nextCode]);

        $saleIds = DB::table('sales')->orderBy('id')->pluck('id')->all();
        $saleIds = array_slice($saleIds, -$saleCount);

        $itemRows = [];
        $saleUpdates = []; // sale_id => [subtotal, discount_percent, discount_amount, total_amount, refunded_at, refund_reason]

        foreach ($saleIds as $idx => $saleId) {
            $meta = $saleMeta[$idx];
            $itemCount = random_int(1, 4);
            $subtotal = 0;
            $picks = 0;
            $tries = 0;

            while ($picks < $itemCount && $tries < $itemCount * 4) {
                $tries++;
                $medicineId = $medicineIds[array_rand($medicineIds)];
                if ($stock[$medicineId] <= 0) {
                    continue;
                }

                $qty = min($stock[$medicineId], random_int(1, 10));
                $medicine = $medicines[$medicineId];
                $unitPrice = (float) $medicine->unit_price;
                $lineSubtotal = round($qty * $unitPrice, 2);

                $itemRows[] = [
                    'sale_id' => $saleId,
                    'medicine_id' => $medicineId,
                    'quantity' => $qty,
                    'unit_type' => $medicine->medicine_type,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ];

                $subtotal += $lineSubtotal;
                $stock[$medicineId] -= $qty;
                $picks++;
            }

            $discountOptions = [5, 10, 15];
            $discountPercent = random_int(1, 100) <= 30 ? $discountOptions[array_rand($discountOptions)] : 0;
            $discountAmount = round($subtotal * $discountPercent / 100, 2);
            $total = round($subtotal - $discountAmount, 2);

            $update = [
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total_amount' => $total,
                'refunded_at' => null,
                'refund_reason' => null,
            ];

            if ($meta['days_ago'] > 3 && random_int(1, 100) <= 15) {
                $update['refunded_at'] = $meta['created_at']->copy()->addDays(random_int(1, 3));
                $update['refund_reason'] = $refundReasons[array_rand($refundReasons)];
            }

            $saleUpdates[$saleId] = $update;
        }

        foreach (array_chunk($itemRows, 500) as $chunk) {
            DB::table('sale_items')->insert($chunk);
        }

        // Apply computed totals/refunds. Grouped raw CASE updates would be
        // faster still, but a plain loop over ~2,200 rows is fast enough
        // and far easier to verify is correct.
        foreach ($saleUpdates as $saleId => $update) {
            DB::table('sales')->where('id', $saleId)->update($update);
        }

        // Persist final stock levels back to medicines.
        foreach ($stock as $medicineId => $remaining) {
            DB::table('medicines')->where('id', $medicineId)->update(['quantity' => $remaining]);
        }
    }
}
