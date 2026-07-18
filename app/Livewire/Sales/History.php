<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
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

    // Refund modal state
    public bool $showRefundModal = false;
    public ?int $refundingSaleId = null;
    public string $refundReason = '';

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

    public function openRefund(int $saleId): void
    {
        $sale = Sale::findOrFail($saleId);

        if (!$sale->isRefundEligible()) {
            session()->flash('error', 'This sale is not eligible for a refund.');
            return;
        }

        $this->refundingSaleId = $saleId;
        $this->refundReason = '';
        $this->showRefundModal = true;
    }

    public function cancelRefund(): void
    {
        $this->showRefundModal = false;
        $this->refundingSaleId = null;
        $this->refundReason = '';
        $this->resetValidation();
    }

    /**
     * Refunding a sale restocks every line item's quantity back onto its
     * medicine and marks the sale as refunded. It intentionally does not
     * delete the sale — the record stays for accounting/audit purposes.
     */
    public function confirmRefund(): void
    {
        if (!$this->refundingSaleId) {
            return;
        }

        $sale = Sale::with('items.medicine')->findOrFail($this->refundingSaleId);

        if (!$sale->isRefundEligible()) {
            session()->flash('error', 'This sale is no longer eligible for a refund.');
            $this->cancelRefund();
            return;
        }

        $this->validate([
            'refundReason' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                if ($item->medicine) {
                    $item->medicine->increment('quantity', $item->quantity);
                }
            }

            $sale->update([
                'refunded_at' => now('Asia/Karachi'),
                'refund_reason' => $this->refundReason ?: null,
            ]);
        });

        session()->flash('success', "Sale {$sale->bill_code} refunded and stock restored.");
        $this->cancelRefund();
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
            'settings' => \App\Models\Setting::current(),
        ]);
    }
}
