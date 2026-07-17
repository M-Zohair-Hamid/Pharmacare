<?php

namespace App\Livewire\Purchases;

use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $supplierId = null;
    public string $notes = '';

    /** @var array<int, array{medicine_id:int, name:string, sku:string, quantity:int, unit_cost:float}> */
    public array $items = [];

    public string $pickMedicineId = '';
    public int $pickQuantity = 1;
    public string $pickUnitCost = '0';

    public array $selected = [];
    public bool $selectAll = false;

    public function addItem(): void
    {
        $this->validate([
            'pickMedicineId' => 'required|exists:medicines,id',
            'pickQuantity' => 'required|integer|min:1',
            'pickUnitCost' => 'required|numeric|min:0',
        ]);

        $medicine = Medicine::findOrFail($this->pickMedicineId);

        $this->items[$medicine->id] = [
            'medicine_id' => $medicine->id,
            'name' => $medicine->name,
            'sku' => $medicine->sku,
            'quantity' => (int) $this->pickQuantity,
            'unit_cost' => (float) $this->pickUnitCost,
        ];

        $this->reset(['pickMedicineId', 'pickQuantity', 'pickUnitCost']);
        $this->pickQuantity = 1;
        $this->pickUnitCost = '0';
    }

    public function removeItem(int $medicineId): void
    {
        unset($this->items[$medicineId]);
    }

    public function getItemsTotalProperty(): float
    {
        return collect($this->items)->sum(fn ($item) => $item['quantity'] * $item['unit_cost']);
    }

    /**
     * Mirrors seed.ts purchase insertion pattern: creates purchase + purchase_items,
     * and increments medicine stock (the inverse of the sale flow).
     */
    public function receive(): void
    {
        if (empty($this->items)) {
            $this->addError('items', 'Add at least one item to receive.');
            return;
        }

        DB::transaction(function () {
            $total = 0;
            $lineItems = [];

            foreach ($this->items as $item) {
                $subtotal = $item['quantity'] * $item['unit_cost'];
                $total += $subtotal;

                $lineItems[] = [
                    'medicine_id' => $item['medicine_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ];

                Medicine::where('id', $item['medicine_id'])->increment('quantity', $item['quantity']);
            }

            $purchase = Purchase::create([
                'supplier_id' => $this->supplierId,
                'total_amount' => $total,
                'notes' => $this->notes ?: null,
            ]);

            $purchase->items()->createMany($lineItems);
        });

        $this->showModal = false;
        $this->reset(['items', 'supplierId', 'notes']);
        $this->resetErrorBag();
    }

    public function openCreate(): void
    {
        $this->reset(['items', 'supplierId', 'notes', 'pickMedicineId', 'pickQuantity', 'pickUnitCost']);
        $this->pickQuantity = 1;
        $this->pickUnitCost = '0';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected = $this->render()->getData()['purchases']->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function delete(int $id): void
    {
        Purchase::findOrFail($id)->delete();
    }

    public function forceDelete(int $id): void
    {
        Purchase::withTrashed()->findOrFail($id)->forceDelete();
    }

    public function bulkDelete(): void
    {
        Purchase::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function bulkForceDelete(): void
    {
        Purchase::withTrashed()->whereIn('id', $this->selected)->forceDelete();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function render()
    {
        $purchases = Purchase::query()
            ->with('supplier')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'suppliers' => Supplier::orderBy('name')->get(),
            'medicines' => Medicine::orderBy('name')->get(),
            'trashed' => Purchase::onlyTrashed()->with('supplier')->orderByDesc('deleted_at')->get(),
        ]);
    }
}
