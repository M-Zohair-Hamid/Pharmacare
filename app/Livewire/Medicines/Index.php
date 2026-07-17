<?php

namespace App\Livewire\Medicines;

use App\Models\Medicine;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $stock = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public string $genericName = '';
    public string $category_field = 'General';
    public string $manufacturer = '';
    public string $sku = '';
    public string $unitPrice = '0';
    public string $costPrice = '0';
    public int $quantity = 0;
    public int $reorderLevel = 10;
    public ?string $expiryDate = null;
    public string $description = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'genericName' => 'nullable|string|max:255',
            'category_field' => 'required|string|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'sku' => 'required|string|max:100|unique:medicines,sku,' . ($this->editingId ?? 'NULL'),
            'unitPrice' => 'required|numeric|min:0',
            'costPrice' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'reorderLevel' => 'required|integer|min:0',
            'expiryDate' => 'nullable|date',
            'description' => 'nullable|string',
        ];
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingStock(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $medicine = Medicine::findOrFail($id);
        $this->editingId = $medicine->id;
        $this->name = $medicine->name;
        $this->genericName = $medicine->generic_name ?? '';
        $this->category_field = $medicine->category;
        $this->manufacturer = $medicine->manufacturer ?? '';
        $this->sku = $medicine->sku;
        $this->unitPrice = (string) $medicine->unit_price;
        $this->costPrice = (string) $medicine->cost_price;
        $this->quantity = $medicine->quantity;
        $this->reorderLevel = $medicine->reorder_level;
        $this->expiryDate = $medicine->expiry_date?->format('Y-m-d');
        $this->description = $medicine->description ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $payload = [
            'name' => $validated['name'],
            'generic_name' => $validated['genericName'] ?: null,
            'category' => $validated['category_field'],
            'manufacturer' => $validated['manufacturer'] ?: null,
            'sku' => $validated['sku'],
            'unit_price' => $validated['unitPrice'],
            'cost_price' => $validated['costPrice'],
            'quantity' => $validated['quantity'],
            'reorder_level' => $validated['reorderLevel'],
            'expiry_date' => $validated['expiryDate'] ?: null,
            'description' => $validated['description'] ?: null,
        ];

        if ($this->editingId) {
            Medicine::findOrFail($this->editingId)->update($payload);
        } else {
            Medicine::create($payload);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Medicine::findOrFail($id)->delete();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'genericName', 'manufacturer', 'sku',
            'unitPrice', 'costPrice', 'quantity', 'reorderLevel',
            'expiryDate', 'description',
        ]);
        $this->category_field = 'General';
        $this->unitPrice = '0';
        $this->costPrice = '0';
        $this->reorderLevel = 10;
        $this->resetValidation();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        $query = Medicine::query();

        if ($this->q !== '') {
            $term = "%{$this->q}%";
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ilike', $term)
                    ->orWhere('generic_name', 'ilike', $term)
                    ->orWhere('sku', 'ilike', $term)
                    ->orWhere('manufacturer', 'ilike', $term);
            });
        }

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if ($this->stock === 'low') {
            $query->whereColumn('quantity', '<=', 'reorder_level')->where('quantity', '>', 0);
        } elseif ($this->stock === 'out') {
            $query->where('quantity', 0);
        } elseif ($this->stock === 'ok') {
            $query->whereColumn('quantity', '>', 'reorder_level');
        }

        $medicines = $query->orderBy('name')->paginate(15);
        $categories = Medicine::query()->distinct()->orderBy('category')->pluck('category');

        return view('livewire.medicines.index', [
            'medicines' => $medicines,
            'categories' => $categories,
        ]);
    }
}
