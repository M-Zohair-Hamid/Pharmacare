<?php

namespace App\Livewire;

use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Unified Bin — one page listing soft-deleted records from every module,
 * kept in clearly separate sections (not merged into one list) so a
 * deleted medicine is never confused with a deleted sale/receipt, etc.
 * Each section has its own restore()/forceDelete() action scoped to its
 * own model.
 */
#[Layout('layouts.app')]
class Bin extends Component
{
    /** Which section tab is active: medicines | sales | purchases | suppliers | customers */
    #[Url]
    public string $tab = 'medicines';

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    // ---------------------------------------------------------------
    // Medicines
    // ---------------------------------------------------------------

    public function restoreMedicine(int $id): void
    {
        Medicine::onlyTrashed()->findOrFail($id)->restore();
        session()->flash('success', 'Medicine restored.');
    }

    public function forceDeleteMedicine(int $id): void
    {
        try {
            Medicine::withTrashed()->findOrFail($id)->forceDelete();
            session()->flash('success', 'Medicine permanently deleted.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not permanently delete this medicine: ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------------
    // Sales
    // ---------------------------------------------------------------

    public function restoreSale(int $id): void
    {
        Sale::onlyTrashed()->findOrFail($id)->restore();
        session()->flash('success', 'Sale restored.');
    }

    public function forceDeleteSale(int $id): void
    {
        try {
            Sale::withTrashed()->findOrFail($id)->forceDelete();
            session()->flash('success', 'Sale permanently deleted.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not permanently delete this sale: ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------------
    // Purchases
    // ---------------------------------------------------------------

    public function restorePurchase(int $id): void
    {
        Purchase::onlyTrashed()->findOrFail($id)->restore();
        session()->flash('success', 'Purchase restored.');
    }

    public function forceDeletePurchase(int $id): void
    {
        try {
            Purchase::withTrashed()->findOrFail($id)->forceDelete();
            session()->flash('success', 'Purchase permanently deleted.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not permanently delete this purchase: ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------------
    // Suppliers
    // ---------------------------------------------------------------

    public function restoreSupplier(int $id): void
    {
        Supplier::onlyTrashed()->findOrFail($id)->restore();
        session()->flash('success', 'Supplier restored.');
    }

    public function forceDeleteSupplier(int $id): void
    {
        try {
            Supplier::withTrashed()->findOrFail($id)->forceDelete();
            session()->flash('success', 'Supplier permanently deleted.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not permanently delete this supplier: ' . $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.bin', [
            'trashedMedicines' => Medicine::onlyTrashed()->orderByDesc('deleted_at')->get(),
            'trashedSales' => Sale::onlyTrashed()->orderByDesc('deleted_at')->get(),
            'trashedPurchases' => Purchase::onlyTrashed()->with('supplier')->orderByDesc('deleted_at')->get(),
            'trashedSuppliers' => Supplier::onlyTrashed()->orderByDesc('deleted_at')->get(),
        ]);
    }
}
