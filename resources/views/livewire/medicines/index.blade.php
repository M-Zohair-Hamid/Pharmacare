<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Medicines</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">
                Manage your pharmacy inventory, pricing, and stock levels.
            </p>
        </div>
        <button
            type="button"
            wire:click="openCreate"
            class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700"
        >
            + Add Medicine
        </button>
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input
            type="text"
            wire:model.live.debounce.300ms="q"
            placeholder="Search by name, SKU, manufacturer..."
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-teal-500/30 focus:border-teal-500 focus:ring-4 sm:max-w-sm"
        />
        <select wire:model.live="category" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </select>
        <select wire:model.live="stock" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
            <option value="">All stock</option>
            <option value="ok">In stock</option>
            <option value="low">Low stock</option>
            <option value="out">Out of stock</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Medicine</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Category</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Price</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($medicines as $medicine)
                        <tr wire:key="med-{{ $medicine->id }}" class="border-b border-slate-50 last:border-0">
                            <td class="px-4 py-3 align-middle text-slate-700">
                                <div class="font-medium text-slate-900">{{ $medicine->name }}</div>
                                <div class="text-xs text-slate-500">{{ $medicine->sku }} · {{ $medicine->manufacturer ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->category }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ number_format($medicine->unit_price, 2) }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->quantity }} / {{ $medicine->reorder_level }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">
                                @php $status = $medicine->stock_status; @endphp
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                                    'bg-rose-50 text-rose-700' => $status === 'out',
                                    'bg-amber-50 text-amber-700' => $status === 'low',
                                    'bg-emerald-50 text-emerald-700' => $status === 'ok',
                                ])>
                                    {{ $status === 'out' ? 'Out of stock' : ($status === 'low' ? 'Low stock' : 'In stock') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle text-right">
                                <button wire:click="openEdit({{ $medicine->id }})" class="text-sm font-medium text-teal-700 hover:text-teal-800">Edit</button>
                                <button
                                    wire:click="delete({{ $medicine->id }})"
                                    wire:confirm="Delete {{ $medicine->name }}? This cannot be undone."
                                    class="ml-3 text-sm font-medium text-rose-600 hover:text-rose-700"
                                >Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No medicines found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $medicines->links() }}
        </div>
    </div>

    {{-- Create/Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 p-4 sm:items-center">
            <div class="absolute inset-0" wire:click="closeModal"></div>
            <div class="relative z-10 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div class="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-4">
                    <h3 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Medicine' : 'Add Medicine' }}</h3>
                    <button wire:click="closeModal" class="rounded-xl px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100">Close</button>
                </div>
                <form wire:submit="save" class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
                        <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Generic Name</label>
                        <input type="text" wire:model="genericName" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                        <input type="text" wire:model="category_field" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('category_field') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Manufacturer</label>
                        <input type="text" wire:model="manufacturer" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">SKU</label>
                        <input type="text" wire:model="sku" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Unit Price</label>
                        <input type="number" step="0.01" wire:model="unitPrice" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('unitPrice') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cost Price</label>
                        <input type="number" step="0.01" wire:model="costPrice" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('costPrice') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Quantity</label>
                        <input type="number" wire:model="quantity" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('quantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Reorder Level</label>
                        <input type="number" wire:model="reorderLevel" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('reorderLevel') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Expiry Date</label>
                        <input type="date" wire:model="expiryDate" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Description</label>
                        <textarea wire:model="description" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <button type="button" wire:click="closeModal" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                            {{ $editingId ? 'Save changes' : 'Add medicine' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
