<?php

namespace App\Livewire;

use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $medicineCount = Medicine::count();
        $supplierCount = Supplier::count();
        $saleCount = Sale::count();
        $purchaseCount = Purchase::count();

        $revenue = Sale::sum('total_amount');
        $inventoryValue = Medicine::query()
            ->selectRaw('coalesce(sum(quantity * unit_price), 0) as value')
            ->value('value');

        $threshold = Setting::current()->low_stock_threshold;

        $lowStock = Medicine::query()
            ->where('quantity', '<=', $threshold)
            ->orderBy('quantity')
            ->limit(8)
            ->get();

        $expiringSoon = Medicine::query()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays(60))
            ->orderBy('expiry_date')
            ->limit(8)
            ->get();

        $recentSales = Sale::query()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $categoryBreakdown = Medicine::query()
            ->selectRaw('category, count(*) as items, coalesce(sum(quantity), 0) as stock')
            ->groupBy('category')
            ->orderByDesc('stock')
            ->get();

        return view('livewire.dashboard', [
            'medicineCount' => $medicineCount,
            'supplierCount' => $supplierCount,
            'saleCount' => $saleCount,
            'purchaseCount' => $purchaseCount,
            'revenue' => $revenue,
            'inventoryValue' => $inventoryValue,
            'lowStockThreshold' => $threshold,
            'lowStock' => $lowStock,
            'expiringSoon' => $expiringSoon,
            'recentSales' => $recentSales,
            'categoryBreakdown' => $categoryBreakdown,
        ]);
    }
}
