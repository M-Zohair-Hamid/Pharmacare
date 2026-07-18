<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Settings</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">
            Pharmacy identity used across receipts and the app.
        </p>
    </div>

    @if ($saved)
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Settings saved.
        </div>
    @endif

    <div class="max-w-2xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Pharmacy Name</label>
                <input type="text" wire:model="pharmacyName" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                @error('pharmacyName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Owner Name</label>
                <input type="text" wire:model="ownerName" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone Number</label>
                <input type="text" wire:model="phone" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Low Stock Threshold</label>
                <input type="number" min="0" wire:model="lowStockThreshold" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                <p class="mt-1 text-xs text-slate-400">Medicines at or below this quantity are flagged as low stock.</p>
                @error('lowStockThreshold') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address</label>
                <textarea wire:model="address" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
            </div>
            <div class="flex justify-end sm:col-span-2">
                <button type="submit" class="cursor-pointer rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md active:translate-y-0">
                    Save settings
                </button>
            </div>
        </form>
    </div>
</div>
