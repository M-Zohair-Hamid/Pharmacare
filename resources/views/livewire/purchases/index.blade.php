<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Purchases</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">Stock received from suppliers.</p>
        </div>
        <button type="button" wire:click="openCreate" class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700">
            + Receive Stock
        </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Purchase</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Supplier</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Items</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr wire:key="pur-{{ $purchase->id }}" class="border-b border-slate-50 last:border-0">
                            <td class="px-4 py-3 align-middle">
                                <div class="font-medium text-slate-900">#{{ $purchase->id }}</div>
                                <div class="text-xs text-slate-500">{{ $purchase->created_at->format('M j, Y g:i A') }}</div>
                            </td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $purchase->supplier->name ?? '—' }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $purchase->items_count }}</td>
                            <td class="px-4 py-3 align-middle font-medium text-slate-900">{{ number_format($purchase->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">No purchases recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $purchases->links() }}</div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 p-4 sm:items-center">
            <div class="absolute inset-0" wire:click="closeModal"></div>
            <div class="relative z-10 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div class="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-4">
                    <h3 class="text-lg font-semibold text-slate-900">Receive Stock</h3>
                    <button wire:click="closeModal" class="rounded-xl px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100">Close</button>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Supplier</label>
                        <select wire:model="supplierId" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="">— Select supplier —</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Add item</p>
                        <div class="grid gap-3 sm:grid-cols-4">
                            <select wire:model="pickMedicineId" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
                                <option value="">Select medicine</option>
                                @foreach ($medicines as $medicine)
                                    <option value="{{ $medicine->id }}">{{ $medicine->name }} ({{ $medicine->sku }})</option>
                                @endforeach
                            </select>
                            <input type="number" min="1" wire:model="pickQuantity" placeholder="Qty" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                            <input type="number" step="0.01" min="0" wire:model="pickUnitCost" placeholder="Unit cost" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        </div>
                        @error('pickMedicineId') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @error('pickQuantity') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @error('pickUnitCost') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <button type="button" wire:click="addItem" class="mt-3 rounded-xl bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-900">Add to purchase</button>
                    </div>

                    @error('items') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                    <div class="space-y-2">
                        @forelse ($items as $medicineId => $item)
                            <div wire:key="pitem-{{ $medicineId }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-sm">
                                <div>
                                    <span class="font-medium text-slate-900">{{ $item['name'] }}</span>
                                    <span class="text-slate-500"> · {{ $item['quantity'] }} × {{ number_format($item['unit_cost'], 2) }}</span>
                                </div>
                                <button wire:click="removeItem({{ $medicineId }})" class="text-xs text-rose-600 hover:text-rose-700">Remove</button>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No items added yet.</p>
                        @endforelse
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-sm font-medium text-slate-700">Total</span>
                        <span class="text-lg font-semibold text-slate-900">{{ number_format($this->itemsTotal, 2) }}</span>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                        <textarea wire:model="notes" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="closeModal" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="button" wire:click="receive" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Receive stock</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
