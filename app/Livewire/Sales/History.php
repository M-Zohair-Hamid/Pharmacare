<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class History extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public array $selected = [];
    public bool $selectAll = false;

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected = $this->render()->getData()['sales']->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function delete(int $id): void
    {
        Sale::findOrFail($id)->delete();
    }

    public function forceDelete(int $id): void
    {
        try {
            Sale::withTrashed()->findOrFail($id)->forceDelete();
            session()->flash('success', 'Sale permanently deleted.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not permanently delete this sale: ' . $e->getMessage());
        }
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            session()->flash('error', 'No sales selected.');
            return;
        }

        try {
            Sale::whereIn('id', $this->selected)->delete();
            session()->flash('success', count($this->selected) . ' sale(s) moved to trash.');
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not delete selected sales: ' . $e->getMessage());
        }
    }

    public function bulkForceDelete(): void
    {
        if (empty($this->selected)) {
            session()->flash('error', 'No sales selected.');
            return;
        }

        try {
            $count = count($this->selected);
            Sale::withTrashed()->whereIn('id', $this->selected)->forceDelete();
            session()->flash('success', $count . ' sale(s) permanently deleted.');
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not permanently delete selected sales: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = Sale::query()->with('items');

        if ($this->q !== '') {
            $term = '%' . strtolower($this->q) . '%';
            $query->where(function ($qq) use ($term) {
                $qq->whereRaw('LOWER(bill_code) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(customer_name) LIKE ?', [$term]);
            });
        }

        if ($this->dateFrom !== '') {
            $query->where('created_at', '>=', $this->dateFrom . ' 00:00:00');
        }

        if ($this->dateTo !== '') {
            $query->where('created_at', '<=', $this->dateTo . ' 23:59:59');
        }

        $sales = $query->orderByDesc('created_at')->paginate(20);
        $trashed = Sale::onlyTrashed()->orderByDesc('deleted_at')->get();

        return view('livewire.sales.history', [
            'sales' => $sales,
            'trashed' => $trashed,
        ]);
    }
}
