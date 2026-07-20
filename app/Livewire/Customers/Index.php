<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
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
            $this->selected = $this->render()->getData()['customers']->pluck('id')->map(fn ($id) => (string) $id)->toArray();
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
        $customer = Customer::findOrFail($id);
        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->email = $customer->email ?? '';
        $this->phone = $customer->phone ?? '';
        $this->address = $customer->address ?? '';
        $this->notes = $customer->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        if ($this->editingId) {
            Customer::findOrFail($this->editingId)->update($payload);
        } else {
            Customer::create($payload);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Customer::findOrFail($id)->delete();
    }

    public function bulkDelete(): void
    {
        Customer::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'address', 'notes']);
        $this->resetValidation();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        $query = Customer::query();

        if ($this->q !== '') {
            $term = "%{$this->q}%";
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ilike', $term)
                    ->orWhere('email', 'ilike', $term)
                    ->orWhere('phone', 'ilike', $term);
            });
        }

        return view('livewire.customers.index', [
            'customers' => $query->orderBy('name')->paginate($this->perPage),
        ]);
    }
}
