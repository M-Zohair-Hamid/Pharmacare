<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
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

    /** @var array<int, array{medicine_id:int, name:string, sku:string, quantity:int, unit_type:string, unit_price:float, available:int}> */
    public array $cart = [];

    public ?int $lastSaleId = null;
    public ?string $lastBillCode = null;

    public array $unitTypes = ['Box', 'Tablet', 'Strip', 'Bottle', 'Piece'];

    public function addToCart(int $medicineId): void
    {
        $medicine = Medicine::findOrFail($medicineId);

        if ($medicine->quantity <= 0) {
            $this->addError('cart', "{$medicine->name} is out of stock.");
            return;
        }

        if (isset($this->cart[$medicineId])) {
            if ($this->cart[$medicineId]['quantity'] + 1 > $medicine->quantity) {
                $this->addError('cart', "Not enough stock for {$medicine->name}.");
                return;
            }
            $this->cart[$medicineId]['quantity']++;
        } else {
            $this->cart[$medicineId] = [
                'medicine_id' => $medicine->id,
                'name' => $medicine->name,
                'sku' => $medicine->sku,
                'quantity' => 1,
                'unit_type' => 'Tablet',
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

    public function updateUnitType(int $medicineId, string $unitType): void
    {
        if (!isset($this->cart[$medicineId])) {
            return;
        }

        $this->cart[$medicineId]['unit_type'] = $unitType;
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
     * decrements medicine quantity, and writes sale + sale_items atomically.
     * Customer is identified by free-text name (not a dropdown); matched or
     * created against the customers table so history stays linkable.
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

                    $subtotal = $item['quantity'] * $item['unit_price'];
                    $total += $subtotal;

                    $lineItems[] = [
                        'medicine_id' => $medicine->id,
                        'quantity' => $item['quantity'],
                        'unit_type' => $item['unit_type'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $subtotal,
                    ];

                    $medicine->decrement('quantity', $item['quantity']);
                }

                $customerId = null;
                $customerName = trim($this->customerName) ?: null;

                if ($customerName) {
                    $customer = Customer::firstOrCreate(['name' => $customerName]);
                    $customerId = $customer->id;
                }

                $sale = Sale::create([
                    'customer_id' => $customerId,
                    'customer_name' => $customerName,
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
                $term = "%{$this->search}%";
                $q->where(function ($qq) use ($term) {
                    $qq->whereRaw('LOWER(name) LIKE ?', [strtolower($term)])
    			->orWhereRaw('LOWER(sku) LIKE ?', [strtolower($term)]);
                });
            })
            ->where('quantity', '>', 0)
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.sales.pos', [
            'medicines' => $medicines,
        ]);
    }
}
