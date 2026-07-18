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
            class="inline-flex cursor-pointer items-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md active:translate-y-0"
        >
            + Add Medicine
        </button>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input
            type="text"
            wire:model.live.debounce.300ms="q"
            placeholder="Search by name, generic name, manufacturer..."
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-teal-500/30 focus:border-teal-500 focus:ring-4 sm:max-w-sm"
        />
        <select wire:model.live="category" class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </select>
        <select wire:model.live="type" class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
            <option value="">All types</option>
            @foreach ($medicineTypes as $t)
                <option value="{{ $t }}">{{ $t }}</option>
            @endforeach
        </select>
        <select wire:model.live="stock" class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
            <option value="">All stock</option>
            <option value="ok">In stock</option>
            <option value="low">Low stock</option>
            <option value="out">Out of stock</option>
        </select>
    </div>

    @if (count($selected) > 0)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3">
            <span class="text-sm font-medium text-teal-800">{{ count($selected) }} selected</span>
            <div class="flex gap-2">
                <button
                    type="button"
                    wire:click="bulkDelete"
                    wire:confirm="Soft delete {{ count($selected) }} medicine(s)? They can be restored or force-deleted later."
                    class="cursor-pointer rounded-xl border border-amber-300 bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-800 transition-colors duration-150 hover:bg-amber-200"
                >Delete selected</button>
                <button
                    type="button"
                    wire:click="bulkForceDelete"
                    wire:confirm="Permanently delete {{ count($selected) }} medicine(s)? This cannot be undone."
                    class="cursor-pointer rounded-xl border border-rose-300 bg-rose-100 px-3 py-1.5 text-xs font-medium text-rose-800 transition-colors duration-150 hover:bg-rose-200"
                >Force delete selected</button>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="whitespace-nowrap px-4 py-3">
                            <input type="checkbox" wire:model.live="selectAll" class="cursor-pointer rounded border-slate-300" />
                        </th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Medicine</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Category</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Unit Price</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Expiry</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($medicines as $medicine)
                        <tr wire:key="med-{{ $medicine->id }}" class="border-b border-slate-50 last:border-0">
                            <td class="px-4 py-3 align-middle">
                                <input type="checkbox" wire:model.live="selected" value="{{ $medicine->id }}" class="cursor-pointer rounded border-slate-300" />
                            </td>
                            <td class="px-4 py-3 align-middle text-slate-700">
                                <div class="font-medium text-slate-900">{{ $medicine->name }}</div>
                                <div class="text-xs text-slate-500">{{ $medicine->manufacturer ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 align-middle text-slate-700">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $medicine->medicine_type }}</span>
                            </td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->category }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">
                                {{ number_format($medicine->unit_price, 2) }}
                                <span class="text-xs text-slate-400">/ {{ $medicine->medicine_type }}</span>
                            </td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->quantity }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->expiry_date?->format('M j, Y') ?? '—' }}</td>
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
                                <button wire:click="openEdit({{ $medicine->id }})" class="cursor-pointer text-sm font-medium text-teal-700 transition-colors duration-150 hover:text-teal-800">Edit</button>
                                <button
                                    wire:click="delete({{ $medicine->id }})"
                                    wire:confirm="Delete {{ $medicine->name }}? It can be restored from Trash or permanently removed."
                                    class="ml-3 cursor-pointer text-sm font-medium text-rose-600 transition-colors duration-150 hover:text-rose-700"
                                >Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-500">No medicines found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $medicines->links() }}
        </div>
    </div>

    {{-- Trash / soft-deleted medicines --}}
    @if ($trashed->count() > 0)
        <div class="mt-8">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Trash ({{ $trashed->count() }})</h2>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Medicine</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trashed as $medicine)
                                <tr wire:key="trashed-{{ $medicine->id }}" class="border-b border-slate-50 last:border-0">
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->name }}</td>
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->medicine_type }}</td>
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->deleted_at?->format('M j, Y g:i A') }}</td>
                                    <td class="px-4 py-3 align-middle text-right">
                                        <button
                                            wire:click="forceDelete({{ $medicine->id }})"
                                            wire:confirm="Permanently delete {{ $medicine->name }}? This cannot be undone and will also remove its sale/purchase history."
                                            class="cursor-pointer text-sm font-medium text-rose-600 transition-colors duration-150 hover:text-rose-700"
                                        >Force delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Create/Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 p-4 sm:items-center">
            <div class="absolute inset-0 cursor-pointer" wire:click="closeModal"></div>
            <div class="relative z-10 max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div class="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-3">
                    <h3 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Medicine' : 'Add Medicine' }}</h3>
                    <button wire:click="closeModal" class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-150 hover:bg-slate-100">Close</button>
                </div>
                <form wire:submit="save" class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
                        <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Generic Name</label>
                        <input type="text" wire:model="genericName" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                        <input type="text" wire:model="category_field" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('category_field') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Medicine Type / Unit</label>
                        <select wire:model.live="medicineType" class="w-full cursor-pointer rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @foreach ($medicineTypes as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                        @error('medicineType') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Manufacturer</label>
                        <input type="text" wire:model="manufacturer" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Quantity in Stock</label>
                        <input type="number" wire:model="quantity" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('quantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($medicineType === 'Tablet')
                        <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-teal-100 bg-teal-50/50 p-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-teal-700">Box pricing</p>
                            <div class="grid gap-3 grid-cols-2 md:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tablets per Box</label>
                                    <input type="number" min="1" step="1" wire:model.live="tabletsPerBox" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white" />
                                    @error('tabletsPerBox') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Box Price</label>
                                    <input type="number" min="0" step="0.01" wire:model.live="boxPrice" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white" />
                                    @error('boxPrice') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Unit Price <span class="normal-case text-slate-400">(per tablet)</span></label>
                                    <input type="number" step="0.01" wire:model="unitPrice" readonly class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-slate-50 text-slate-500" />
                                    @error('unitPrice') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-teal-700">
                                Unit price per tablet is calculated automatically: box price ÷ tablets per box.
                            </p>
                        </div>
                    @else
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Unit Price <span class="normal-case text-slate-400">(per {{ strtolower($medicineType) }})</span>
                            </label>
                            <input type="number" step="0.01" wire:model="unitPrice" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                            @error('unitPrice') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cost Price</label>
                        <input type="number" step="0.01" wire:model="costPrice" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('costPrice') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Expiry Date</label>
                        <input type="date" wire:model="expiryDate" min="{{ now()->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('expiryDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Description</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>

                    @if ($editingId)
                        <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stock Batches</p>
                                @if (!$showBatchForm)
                                    <button
                                        type="button"
                                        wire:click="openBatchForm"
                                        class="cursor-pointer rounded-xl border border-teal-200 bg-white px-3 py-1.5 text-xs font-medium text-teal-700 transition-colors duration-150 hover:bg-teal-50"
                                    >+ Record New Batch</button>
                                @endif
                            </div>

                            @if ($showBatchForm)
                                <div wire:key="batch-form" class="mt-3 grid gap-3 rounded-xl border border-teal-100 bg-white p-3 sm:grid-cols-2 lg:grid-cols-4">
                                    <div class="lg:col-span-2">
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Batch Number <span class="normal-case text-slate-400">(optional)</span></label>
                                        <input type="text" wire:model="batchNumber" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                                        @error('batchNumber') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Quantity</label>
                                        <input type="number" min="1" wire:model="batchQuantity" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                                        @error('batchQuantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Batch Received Date</label>
                                        <input type="date" wire:model="batchReceivedDate" max="{{ now()->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                                        @error('batchReceivedDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="lg:col-span-4">
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Batch Expiry Date</label>
                                        <input type="date" wire:model="batchExpiryDate" min="{{ now()->addDay()->format('Y-m-d') }}" class="w-full max-w-xs rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                                        @error('batchExpiryDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex justify-end gap-2 lg:col-span-4">
                                        <button type="button" wire:click="cancelBatchForm" class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                                        <button type="button" wire:click="saveBatch" class="cursor-pointer rounded-xl bg-teal-600 px-3 py-1.5 text-xs font-medium text-white transition-colors duration-150 hover:bg-teal-700">Save Batch</button>
                                    </div>
                                </div>
                            @endif

                            @if ($editingMedicineBatches->count() > 0)
                                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <table class="min-w-full text-left text-xs">
                                        <thead>
                                            <tr class="border-b border-slate-100 bg-slate-50">
                                                <th class="px-3 py-2 font-semibold uppercase tracking-wide text-slate-500">Batch #</th>
                                                <th class="px-3 py-2 font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                                                <th class="px-3 py-2 font-semibold uppercase tracking-wide text-slate-500">Received</th>
                                                <th class="px-3 py-2 font-semibold uppercase tracking-wide text-slate-500">Expiry</th>
                                                <th class="px-3 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($editingMedicineBatches as $batch)
                                                <tr wire:key="batch-{{ $batch->id }}" class="border-b border-slate-50 last:border-0">
                                                    <td class="px-3 py-2 text-slate-700">{{ $batch->batch_number ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-slate-700">{{ $batch->quantity }}</td>
                                                    <td class="px-3 py-2 text-slate-700">{{ $batch->received_date->format('M j, Y') }}</td>
                                                    <td class="px-3 py-2 {{ $batch->is_expired ? 'text-rose-600 font-medium' : 'text-slate-700' }}">
                                                        {{ $batch->expiry_date->format('M j, Y') }}
                                                    </td>
                                                    <td class="px-3 py-2 text-right">
                                                        <button
                                                            type="button"
                                                            wire:click="deleteBatch({{ $batch->id }})"
                                                            wire:confirm="Delete this batch record?"
                                                            class="cursor-pointer text-rose-600 hover:text-rose-700"
                                                        >Delete</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif (!$showBatchForm)
                                <p class="mt-3 text-xs text-slate-400">No batches recorded yet.</p>
                            @endif
                        </div>
                    @endif
                    <div class="flex justify-end gap-2 sm:col-span-2 lg:col-span-3">
                        <button type="button" wire:click="closeModal" class="cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="cursor-pointer rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-150 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md active:translate-y-0">
                            {{ $editingId ? 'Save changes' : 'Add medicine' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
