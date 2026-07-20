<?php

namespace App\Livewire\Sales;

use App\Models\Medicine;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Pos extends Component
{
    use WithPagination;

    public string $search = '';
    public string $customerName = '';
    public string $paymentMethod = 'cash';
    public string $notes = '';

    /** @var array<int, array{medicine_id:int, name:string, quantity:int, unit_type:string, unit_price:float, available:int}> */
    public array $cart = [];

    public ?int $lastSaleId = null;
    public ?string $lastBillCode = null;

    // Infinite scroll page size, see Medicines\Index for the same pattern.
    public int $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->perPage = 10;
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function addToCart(int $medicineId, string $saleUnit = 'tablet'): void
    {
        $medicine = Medicine::findOrFail($medicineId);

        if ($medicine->quantity <= 0) {
            $this->addError('cart', "{$medicine->name} is out of stock.");
            return;
        }

        if ($medicine->is_expired) {
            $this->addError('cart', "{$medicine->name} is expired and cannot be sold.");
            return;
        }

        $sellableAsBox = $medicine->medicine_type === 'Tablet'
            && $medicine->tablets_per_box
            && $medicine->tablets_per_box > 0
            && $medicine->box_price;

        if ($saleUnit === 'box' && !$sellableAsBox) {
            $saleUnit = 'tablet';
        }

        $unitsPerSaleUnit = $saleUnit === 'box' ? (int) $medicine->tablets_per_box : 1;
        $unitPrice = $saleUnit === 'box' ? (float) $medicine->box_price : (float) $medicine->unit_price;
        $unitLabel = $saleUnit === 'box' ? 'Box' : $medicine->medicine_type;

        // Cart key includes the sale unit so "1 box" and "1 tablet" of the
        // same medicine can sit as separate lines in the same order.
        $cartKey = $medicineId . '-' . $saleUnit;

        if (isset($this->cart[$cartKey])) {
            $newQuantity = $this->cart[$cartKey]['quantity'] + 1;
            if ($newQuantity * $unitsPerSaleUnit > $medicine->quantity) {
                $this->addError('cart', "Not enough stock for {$medicine->name}.");
                return;
            }
            $this->cart[$cartKey]['quantity'] = $newQuantity;
        } else {
            if ($unitsPerSaleUnit > $medicine->quantity) {
                $this->addError('cart', "Not enough stock for {$medicine->name}.");
                return;
            }

            $this->cart[$cartKey] = [
                'medicine_id' => $medicine->id,
                'name' => $medicine->name,
                'quantity' => 1,
                'sale_unit' => $saleUnit,
                'unit_type' => $unitLabel,
                'unit_price' => $unitPrice,
                'units_per_sale_unit' => $unitsPerSaleUnit,
                'available' => $medicine->quantity,
                'sellable_as_box' => $sellableAsBox,
            ];
        }

        $this->resetErrorBag('cart');
    }

    public function updateQuantity(string $cartKey, int $quantity): void
    {
        if (!isset($this->cart[$cartKey])) {
            return;
        }

        if ($quantity <= 0) {
            unset($this->cart[$cartKey]);
            return;
        }

        $unitsNeeded = $quantity * $this->cart[$cartKey]['units_per_sale_unit'];

        if ($unitsNeeded > $this->cart[$cartKey]['available']) {
            $maxAllowed = intdiv($this->cart[$cartKey]['available'], $this->cart[$cartKey]['units_per_sale_unit']);
            $this->addError('cart', "Only {$maxAllowed} {$this->cart[$cartKey]['unit_type']}(s) in stock for {$this->cart[$cartKey]['name']}.");
            return;
        }

        $this->cart[$cartKey]['quantity'] = $quantity;
        $this->resetErrorBag('cart');
    }

    public function removeFromCart(string $cartKey): void
    {
        unset($this->cart[$cartKey]);
    }

    public function getCartTotalProperty(): float
    {
        return collect($this->cart)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
    }


    /**
     * Mirrors offline-sales.ts createOfflineSale(): validates stock,
     * blocks expired medicines, decrements medicine quantity, and writes
     * sale + sale_items atomically. Customer is a free-text label only
     * (no customer records/table anymore).
     */
    public function checkout()
    {
        if (empty($this->cart)) {
            $this->addError('cart', 'Cart is empty.');
            return;
        }

        try {
            $sale = DB::transaction(function () {
                $total = 0;
                $lineItems = [];

                foreach ($this->cart as $item) {
                    // Lock the row to prevent race conditions on stock.
                    $medicine = Medicine::where('id', $item['medicine_id'])->lockForUpdate()->firstOrFail();

                    $unitsNeeded = $item['quantity'] * $item['units_per_sale_unit'];

                    if ($medicine->quantity < $unitsNeeded) {
                        throw new \RuntimeException("Insufficient stock for {$medicine->name}.");
                    }

                    if ($medicine->expiry_date !== null && $medicine->expiry_date->isPast()) {
                        throw new \RuntimeException("{$medicine->name} is expired and cannot be sold.");
                    }

                    $subtotal = $item['quantity'] * $item['unit_price'];
                    $total += $subtotal;

                    $lineItems[] = [
                        'medicine_id' => $medicine->id,
                        'quantity' => $item['quantity'],
                        'unit_type' => $item['unit_type'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $subtotal,
                    ];

                    $medicine->decrement('quantity', $unitsNeeded);
                }

                $sale = Sale::create([
                    'customer_name' => trim($this->customerName) ?: null,
                    'total_amount' => $total,
                    'payment_method' => $this->paymentMethod,
                    'notes' => $this->notes ?: null,
                ]);

                $sale->items()->createMany($lineItems);

                return $sale;
            });

            $this->lastSaleId = $sale->id;
            $this->lastBillCode = $sale->bill_code;
            $this->cart = [];
            $this->customerName = '';
            $this->notes = '';
            $this->resetErrorBag('cart');

            $this->dispatch('sale-completed', saleId: $sale->id);
        } catch (\RuntimeException $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    public function render()
    {
        $medicines = Medicine::query()
            ->when($this->search !== '', function ($q) {
                $term = '%' . strtolower($this->search) . '%';
                $q->where(function ($qq) use ($term) {
                    $qq->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(generic_name) LIKE ?', [$term]);
                });
            })
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.sales.pos', [
            'medicines' => $medicines,
        ]);
    }
}
