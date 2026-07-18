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

    public function addToCart(int $medicineId): void
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

        if (isset($this->cart[$medicineId])) {
            if ($this->cart[$medicineId]['quantity'] + 1 > $medicine->quantity) {
                $this->addError('cart', "Not enough stock for {$medicine->name}.");
                return;
            }
            $this->cart[$medicineId]['quantity']++;
        } else {
            // Unit type/price always come from the medicine itself — not user-selectable —
            // so the sale always reflects how the medicine is actually sold (per tablet, box, etc).
            $this->cart[$medicineId] = [
                'medicine_id' => $medicine->id,
                'name' => $medicine->name,
                'quantity' => 1,
                'unit_type' => $medicine->medicine_type,
                'unit_price' => (float) $medicine->unit_price,
                'available' => $medicine->quantity,
            ];
        }

        $this->resetErrorBag('cart');
    }

    public function updateQuantity(int $medicineId, int $quantity): void
    {
        if (!isset($this->cart[$medicineId])) {
            return;
        }

        if ($quantity <= 0) {
            unset($this->cart[$medicineId]);
            return;
        }

        if ($quantity > $this->cart[$medicineId]['available']) {
            $this->addError('cart', "Only {$this->cart[$medicineId]['available']} in stock for {$this->cart[$medicineId]['name']}.");
            return;
        }

        $this->cart[$medicineId]['quantity'] = $quantity;
        $this->resetErrorBag('cart');
    }

    public function removeFromCart(int $medicineId): void
    {
        unset($this->cart[$medicineId]);
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

                    if ($medicine->quantity < $item['quantity']) {
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
                        'unit_type' => $medicine->medicine_type,
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $subtotal,
                    ];

                    $medicine->decrement('quantity', $item['quantity']);
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
            ->paginate(10);

        return view('livewire.sales.pos', [
            'medicines' => $medicines,
        ]);
    }
}
