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

    #[Url]
    public string $type = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public string $genericName = '';
    public string $category_field = 'General';
    public string $medicineType = 'Tablet';
    public string $manufacturer = '';
    public string $unitPrice = '0';
    public string $costPrice = '0';
    public int $quantity = 0;
    public ?string $expiryDate = null;
    public string $description = '';

    // Bulk selection
    public array $selected = [];
    public bool $selectAll = false;

    public array $medicineTypes = Medicine::TYPES;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'genericName' => 'nullable|string|max:255',
            'category_field' => 'required|string|max:100',
            'medicineType' => 'required|string|in:' . implode(',', Medicine::TYPES),
            'manufacturer' => 'nullable|string|max:255',
            'unitPrice' => 'required|numeric|min:0',
            'costPrice' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'expiryDate' => 'nullable|date|after_or_equal:today',
            'description' => 'nullable|string',
        ];
    }

    protected function messages(): array
    {
        return [
            'expiryDate.after_or_equal' => 'This medicine is already expired. Expired medicines cannot be added or sold.',
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

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected = $this->render()->getData()['medicines']->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
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
        $this->medicineType = $medicine->medicine_type ?? 'Tablet';
        $this->manufacturer = $medicine->manufacturer ?? '';
        $this->unitPrice = (string) $medicine->unit_price;
        $this->costPrice = (string) $medicine->cost_price;
        $this->quantity = $medicine->quantity;
        $this->expiryDate = $medicine->expiry_date?->format('Y-m-d');
        $this->description = $medicine->description ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        // Belt-and-suspenders: explicitly block expired dates even if validation is bypassed client-side.
        if (!empty($validated['expiryDate']) && \Carbon\Carbon::parse($validated['expiryDate'])->endOfDay()->isPast()) {
            $this->addError('expiryDate', 'This medicine is already expired. Expired medicines cannot be added.');
            return;
        }

        $payload = [
            'name' => $validated['name'],
            'generic_name' => $validated['genericName'] ?: null,
            'category' => $validated['category_field'],
            'medicine_type' => $validated['medicineType'],
            'manufacturer' => $validated['manufacturer'] ?: null,
            'unit_price' => $validated['unitPrice'],
            'cost_price' => $validated['costPrice'],
            'quantity' => $validated['quantity'],
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

    /**
     * Force delete permanently removes the medicine and any sale/purchase line
     * items pointing at it (medicine_id FKs are cascadeOnDelete at the DB level),
     * so this can never crash with a foreign key violation.
     */
    public function forceDelete(int $id): void
    {
        try {
            Medicine::withTrashed()->findOrFail($id)->forceDelete();
            session()->flash('success', 'Medicine permanently deleted.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not permanently delete this medicine: ' . $e->getMessage());
        }
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            session()->flash('error', 'No medicines selected.');
            return;
        }

        try {
            Medicine::whereIn('id', $this->selected)->delete();
            session()->flash('success', count($this->selected) . ' medicine(s) moved to trash.');
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not delete selected medicines: ' . $e->getMessage());
        }
    }

    public function bulkForceDelete(): void
    {
        if (empty($this->selected)) {
            session()->flash('error', 'No medicines selected.');
            return;
        }

        try {
            $count = count($this->selected);
            Medicine::withTrashed()->whereIn('id', $this->selected)->forceDelete();
            session()->flash('success', $count . ' medicine(s) permanently deleted.');
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not permanently delete selected medicines: ' . $e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'genericName', 'manufacturer',
            'unitPrice', 'costPrice', 'quantity',
            'expiryDate', 'description',
        ]);
        $this->category_field = 'General';
        $this->medicineType = 'Tablet';
        $this->unitPrice = '0';
        $this->costPrice = '0';
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
            $term = '%' . strtolower($this->q) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(generic_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(manufacturer) LIKE ?', [$term]);
            });
        }

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if ($this->type !== '') {
            $query->where('medicine_type', $this->type);
        }

        $threshold = \App\Models\Setting::current()->low_stock_threshold;

        if ($this->stock === 'low') {
            $query->where('quantity', '>', 0)->where('quantity', '<=', $threshold);
        } elseif ($this->stock === 'out') {
            $query->where('quantity', 0);
        } elseif ($this->stock === 'ok') {
            $query->where('quantity', '>', $threshold);
        }

        $medicines = $query->orderBy('name')->paginate(15);
        $categories = Medicine::query()->distinct()->orderBy('category')->pluck('category');
        $trashed = Medicine::onlyTrashed()->orderByDesc('deleted_at')->get();

        return view('livewire.medicines.index', [
            'medicines' => $medicines,
            'categories' => $categories,
            'trashed' => $trashed,
            'lowStockThreshold' => $threshold,
        ]);
    }
}
