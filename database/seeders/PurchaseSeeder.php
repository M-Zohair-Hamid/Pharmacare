<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = DB::table('suppliers')->pluck('id')->all();
        $medicines = DB::table('medicines')->select('id', 'cost_price')->get();

        if (empty($suppliers) || $medicines->isEmpty()) {
            return;
        }

        $now = Carbon::now('Asia/Karachi');
        $purchaseCount = 300;

        $purchaseRows = [];
        for ($i = 0; $i < $purchaseCount; $i++) {
            $purchaseRows[] = [
                'supplier_id' => $suppliers[array_rand($suppliers)],
                'total_amount' => 0, // filled after items are computed
                'notes' => null,
                'created_at' => $now->copy()->subDays(random_int(1, 365))->subMinutes(random_int(0, 600)),
            ];
        }

        // Insert purchases first (need real IDs to attach items to), then
        // fetch back the IDs the DB assigned in insertion order.
        foreach (array_chunk($purchaseRows, 200) as $chunk) {
            DB::table('purchases')->insert($chunk);
        }
        $purchaseIds = DB::table('purchases')->orderBy('id')->pluck('id')->all();
        $purchaseIds = array_slice($purchaseIds, -$purchaseCount);

        $itemRows = [];
        $totalsByPurchase = [];
        $qtyIncrements = [];

        foreach ($purchaseIds as $purchaseId) {
            $itemCount = random_int(1, 5);
            $picked = $medicines->random(min($itemCount, $medicines->count()));
            $total = 0;

            foreach ($picked as $medicine) {
                $qty = random_int(20, 250);
                $unitCost = (float) $medicine->cost_price > 0 ? (float) $medicine->cost_price : 1;
                $subtotal = round($qty * $unitCost, 2);

                $itemRows[] = [
                    'purchase_id' => $purchaseId,
                    'medicine_id' => $medicine->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                ];

                $total += $subtotal;
                $qtyIncrements[$medicine->id] = ($qtyIncrements[$medicine->id] ?? 0) + $qty;
            }

            $totalsByPurchase[$purchaseId] = round($total, 2);
        }

        foreach (array_chunk($itemRows, 500) as $chunk) {
            DB::table('purchase_items')->insert($chunk);
        }

        foreach ($totalsByPurchase as $purchaseId => $total) {
            DB::table('purchases')->where('id', $purchaseId)->update(['total_amount' => $total]);
        }

        foreach ($qtyIncrements as $medicineId => $addedQty) {
            DB::table('medicines')->where('id', $medicineId)->increment('quantity', $addedQty);
        }
    }
}
