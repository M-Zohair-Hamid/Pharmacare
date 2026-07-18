<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Billing History</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">
            Full billing history, all time — searchable by date or bill code. Times shown in Pakistan Standard Time (PKT).
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
            type="text"
            wire:model.live.debounce.300ms="q"
            placeholder="Search by bill code or customer name..."
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm sm:max-w-xs"
        />
        <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-slate-500">From</label>
            <input type="date" wire:model.live="dateFrom" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" />
        </div>
        <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-slate-500">To</label>
            <input type="date" wire:model.live="dateTo" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" />
        </div>
    </div>

    @if (count($selected) > 0)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3">
            <span class="text-sm font-medium text-teal-800">{{ count($selected) }} selected</span>
            <div class="flex gap-2">
                <button
                    type="button"
                    wire:click="bulkDelete"
                    wire:confirm="Soft delete {{ count($selected) }} sale(s)? They can be restored or force-deleted later."
                    class="cursor-pointer rounded-xl border border-amber-300 bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-800 transition-colors duration-150 hover:bg-amber-200"
                >Delete selected</button>
                <button
                    type="button"
                    wire:click="bulkForceDelete"
                    wire:confirm="Permanently delete {{ count($selected) }} sale(s)? This cannot be undone."
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
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Bill Code</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Date (PKT)</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Items</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}" class="border-b border-slate-50 last:border-0">
                            <td class="px-4 py-3 align-middle">
                                <input type="checkbox" wire:model.live="selected" value="{{ $sale->id }}" class="cursor-pointer rounded border-slate-300" />
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span class="font-mono text-sm font-semibold tracking-widest text-slate-900">{{ $sale->bill_code }}</span>
                            </td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $sale->created_at->timezone('Asia/Karachi')->format('j M Y, g:i A') }} PKT</td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $sale->items->count() }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ ucfirst($sale->payment_method) }}</td>
                            <td class="px-4 py-3 align-middle font-medium text-slate-900">{{ number_format($sale->total_amount, 2) }}</td>
                            <td class="px-4 py-3 align-middle text-right">
                                <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank" class="cursor-pointer text-sm font-medium text-teal-700 transition-colors duration-150 hover:text-teal-800">Receipt</a>
                                <button
                                    wire:click="delete({{ $sale->id }})"
                                    wire:confirm="Delete this sale record? It can be restored or force-deleted later."
                                    class="ml-3 cursor-pointer text-sm font-medium text-rose-600 transition-colors duration-150 hover:text-rose-700"
                                >Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">No sales found for this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $sales->links() }}</div>
    </div>

    @if ($trashed->count() > 0)
        <div class="mt-8">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Trash ({{ $trashed->count() }})</h2>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Bill Code</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at (PKT)</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trashed as $sale)
                                <tr wire:key="trashed-sale-{{ $sale->id }}" class="border-b border-slate-50 last:border-0">
                                    <td class="px-4 py-3 align-middle font-mono text-sm">{{ $sale->bill_code }}</td>
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ $sale->deleted_at?->timezone('Asia/Karachi')->format('j M Y, g:i A') }} PKT</td>
                                    <td class="px-4 py-3 align-middle text-right">
                                        <button
                                            wire:click="forceDelete({{ $sale->id }})"
                                            wire:confirm="Permanently delete this sale? This cannot be undone."
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
</div>
