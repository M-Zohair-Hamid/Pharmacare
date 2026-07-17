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

    public function resetBillCode(int $saleId): void
    {
        $sale = Sale::withTrashed()->findOrFail($saleId);
        $sale->update(['bill_code' => Sale::generateBillCode()]);
    }

    public function delete(int $id): void
    {
        Sale::findOrFail($id)->delete();
    }

    public function forceDelete(int $id): void
    {
        Sale::withTrashed()->findOrFail($id)->forceDelete();
    }

    public function bulkDelete(): void
    {
        Sale::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function bulkForceDelete(): void
    {
        Sale::withTrashed()->whereIn('id', $this->selected)->forceDelete();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function render()
    {
        $query = Sale::query()->with(['customer', 'items']);

        if ($this->q !== '') {
            $term = "%{$this->q}%";
            $query->where(function ($qq) use ($term) {
                $qq->where('bill_code', 'ilike', $term)
                    ->orWhere('customer_name', 'ilike', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', $term));
            });
        }

        if ($this->dateFrom !== '') {
            $query->where('created_at', '>=', $this->dateFrom . ' 00:00:00');
        }

        if ($this->dateTo !== '') {
            $query->where('created_at', '<=', $this->dateTo . ' 23:59:59');
        }

        $sales = $query->orderByDesc('created_at')->paginate(20);
        $trashed = Sale::onlyTrashed()->with('customer')->orderByDesc('deleted_at')->get();

        return view('livewire.sales.history', [
            'sales' => $sales,
            'trashed' => $trashed,
        ]);
    }
}
