<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Settings extends Component
{
    public string $pharmacyName = '';
    public string $ownerName = '';
    public string $phone = '';
    public string $address = '';
    public int $lowStockThreshold = 10;
    public bool $refundsEnabled = false;
    public ?int $refundWindowDays = null;
    public string $receiptPaperWidth = '80';

    public bool $saved = false;

    protected function rules(): array
    {
        return [
            'pharmacyName' => 'required|string|max:255',
            'ownerName' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'lowStockThreshold' => 'required|integer|min:0',
            'refundsEnabled' => 'boolean',
            'refundWindowDays' => $this->refundsEnabled
                ? 'required|integer|min:1|max:365'
                : 'nullable|integer|min:1|max:365',
            'receiptPaperWidth' => 'required|in:58,80',
        ];
    }

    protected function messages(): array
    {
        return [
            'refundWindowDays.required' => 'Enter how many days after purchase a refund is allowed.',
        ];
    }

    public function mount(): void
    {
        $settings = Setting::current();
        $this->pharmacyName = $settings->pharmacy_name;
        $this->ownerName = $settings->owner_name ?? '';
        $this->phone = $settings->phone ?? '';
        $this->address = $settings->address ?? '';
        $this->lowStockThreshold = $settings->low_stock_threshold ?? 10;
        $this->refundsEnabled = $settings->refunds_enabled ?? false;
        $this->refundWindowDays = $settings->refund_window_days;
        $this->receiptPaperWidth = $settings->receipt_paper_width ?? '80';
    }

    public function toggleRefunds(): void
    {
        $this->refundsEnabled = !$this->refundsEnabled;
    }

    public function save(): void
    {
        $validated = $this->validate();

        Setting::current()->update([
            'pharmacy_name' => $validated['pharmacyName'],
            'owner_name' => $validated['ownerName'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'low_stock_threshold' => $validated['lowStockThreshold'],
            'refunds_enabled' => $validated['refundsEnabled'],
            'refund_window_days' => $validated['refundsEnabled'] ? $validated['refundWindowDays'] : null,
            'receipt_paper_width' => $validated['receiptPaperWidth'],
        ]);

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
