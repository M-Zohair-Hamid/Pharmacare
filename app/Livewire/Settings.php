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

    public bool $saved = false;

    protected function rules(): array
    {
        return [
            'pharmacyName' => 'required|string|max:255',
            'ownerName' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'lowStockThreshold' => 'required|integer|min:0',
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
        ]);

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
