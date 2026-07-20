<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
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

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $contactPerson = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $notes = '';

    public array $selected = [];
    public bool $selectAll = false;

    // Infinite scroll page size, see Medicines\Index for the same pattern.
    public int $perPage = 15;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contactPerson' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    public function updatingQ(): void
    {
        $this->resetPage();
        $this->perPage = 15;
    }

    public function loadMore(): void
    {
        $this->perPage += 15;
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected = $this->render()->getData()['suppliers']->pluck('id')->map(fn ($id) => (string) $id)->toArray();
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
        $supplier = Supplier::findOrFail($id);
        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->contactPerson = $supplier->contact_person ?? '';
        $this->email = $supplier->email ?? '';
        $this->phone = $supplier->phone ?? '';
        $this->address = $supplier->address ?? '';
        $this->notes = $supplier->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $payload = [
            'name' => $validated['name'],
            'contact_person' => $validated['contactPerson'] ?: null,
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        if ($this->editingId) {
            Supplier::findOrFail($this->editingId)->update($payload);
        } else {
            Supplier::create($payload);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Supplier::findOrFail($id)->delete();
    }

    public function bulkDelete(): void
    {
        Supplier::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'contactPerson', 'email', 'phone', 'address', 'notes']);
        $this->resetValidation();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        $query = Supplier::query();

        if ($this->q !== '') {
            $term = "%{$this->q}%";
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ilike', $term)
                    ->orWhere('contact_person', 'ilike', $term)
                    ->orWhere('email', 'ilike', $term);
            });
        }

        return view('livewire.suppliers.index', [
            'suppliers' => $query->orderBy('name')->paginate($this->perPage),
        ]);
    }
}
