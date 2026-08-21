<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MedicineBatchSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $medicines = DB::table('medicines')->select('id', 'name')->get();
        if ($medicines->isEmpty()) {
            return;
        }

        // Batches sit ON TOP of the base quantity MedicineSeeder set - every
        // batch here adds to that medicine's total quantity so
        // Medicine::fefoSources() has real dated stock to work with.
        $subset = $medicines->random(min(60, $medicines->count()));

        $batchRows = [];
        $qtyIncrements = []; // medicine_id => total qty added

        foreach ($subset as $medicine) {
            $batchCount = random_int(1, 2);
            for ($b = 0; $b < $batchCount; $b++) {
                $receivedDaysAgo = random_int(1, 180);
                $qty = random_int(10, 150);
                $expiryRoll = random_int(1, 100);
                $expiry = match (true) {
                    $expiryRoll <= 8 => $now->copy()->subDays(random_int(1, 45)),
                    $expiryRoll <= 20 => $now->copy()->addDays(random_int(1, 59)),
                    default => $now->copy()->addMonths(random_int(6, 24)),
                };

                $batchRows[] = [
                    'medicine_id' => $medicine->id,
                    'batch_number' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $medicine->name), 0, 3)) . '-' . $now->copy()->subDays($receivedDaysAgo)->format('ymd') . '-' . $b,
                    'quantity' => $qty,
                    'received_date' => $now->copy()->subDays($receivedDaysAgo)->toDateString(),
                    'expiry_date' => $expiry->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $qtyIncrements[$medicine->id] = ($qtyIncrements[$medicine->id] ?? 0) + $qty;
            }
        }

        foreach (array_chunk($batchRows, 200) as $chunk) {
            DB::table('medicine_batches')->insert($chunk);
        }

        foreach ($qtyIncrements as $medicineId => $addedQty) {
            DB::table('medicines')->where('id', $medicineId)->increment('quantity', $addedQty);
        }

        // Sync each affected medicine's expiry_date to its soonest-expiring
        // source (base stock or a batch), matching Medicine::syncExpiryFromBatches().
        foreach (array_keys($qtyIncrements) as $medicineId) {
            $baseExpiry = DB::table('medicines')->where('id', $medicineId)->value('expiry_date');
            $soonestBatch = DB::table('medicine_batches')
                ->where('medicine_id', $medicineId)
                ->orderBy('expiry_date')
                ->value('expiry_date');

            $candidates = array_filter([$baseExpiry, $soonestBatch]);
            if (!empty($candidates)) {
                $earliest = min($candidates);
                DB::table('medicines')->where('id', $medicineId)->update(['expiry_date' => $earliest]);
            }
        }
    }
}
