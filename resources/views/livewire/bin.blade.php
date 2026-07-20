<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Bin</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">
            Deleted items from every module, kept separate by type. Restore an item or remove it permanently.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    {{-- Tabs --}}
    <div class="mb-5 flex flex-wrap gap-2 border-b border-slate-200">
        @foreach ([
            'medicines' => ['Medicines', $trashedMedicines->count()],
            'sales' => ['Sales / Receipts', $trashedSales->count()],
            'purchases' => ['Purchases', $trashedPurchases->count()],
            'suppliers' => ['Suppliers', $trashedSuppliers->count()],
        ] as $key => [$label, $count])
            <button
                type="button"
                wire:click="setTab('{{ $key }}')"
                class="cursor-pointer border-b-2 px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $tab === $key ? 'border-teal-600 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                {{ $label }}
                <span class="ml-1 inline-flex items-center rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">{{ $count }}</span>
            </button>
        @endforeach
    </div>

    {{-- Medicines --}}
    @if ($tab === 'medicines')
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
                        @forelse ($trashedMedicines as $medicine)
                            <tr wire:key="bin-medicine-{{ $medicine->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->name }}</td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->medicine_type }}</td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->deleted_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-3 align-middle text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <button wire:click="restoreMedicine({{ $medicine->id }})" class="cursor-pointer text-sm font-medium text-teal-600 transition-colors duration-150 hover:text-teal-700">Restore</button>
                                        <button
                                            wire:click="forceDeleteMedicine({{ $medicine->id }})"
                                            wire:confirm="Permanently delete {{ $medicine->name }}? This cannot be undone and will also remove its sale/purchase history."
                                            class="cursor-pointer text-sm font-medium text-rose-600 transition-colors duration-150 hover:text-rose-700"
                                        >Force delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">No deleted medicines.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Sales --}}
    @if ($tab === 'sales')
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
                        @forelse ($trashedSales as $sale)
                            <tr wire:key="bin-sale-{{ $sale->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle font-mono text-sm">{{ $sale->bill_code }}</td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $sale->deleted_at?->timezone('Asia/Karachi')->format('j M Y, g:i A') }} PKT</td>
                                <td class="px-4 py-3 align-middle text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <button wire:click="restoreSale({{ $sale->id }})" class="cursor-pointer text-sm font-medium text-teal-600 transition-colors duration-150 hover:text-teal-700">Restore</button>
                                        <button
                                            wire:click="forceDeleteSale({{ $sale->id }})"
                                            wire:confirm="Permanently delete this sale? This cannot be undone."
                                            class="cursor-pointer text-sm font-medium text-rose-600 transition-colors duration-150 hover:text-rose-700"
                                        >Force delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">No deleted sales.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Purchases --}}
    @if ($tab === 'purchases')
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Purchase</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Supplier</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trashedPurchases as $purchase)
                            <tr wire:key="bin-purchase-{{ $purchase->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle text-slate-700">#{{ $purchase->id }}</td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $purchase->supplier->name ?? '—' }}</td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $purchase->deleted_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-3 align-middle text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <button wire:click="restorePurchase({{ $purchase->id }})" class="cursor-pointer text-sm font-medium text-teal-600 transition-colors duration-150 hover:text-teal-700">Restore</button>
                                        <button
                                            wire:click="forceDeletePurchase({{ $purchase->id }})"
                                            wire:confirm="Permanently delete purchase #{{ $purchase->id }}? This cannot be undone."
                                            class="cursor-pointer text-sm font-medium text-rose-600 transition-colors duration-150 hover:text-rose-700"
                                        >Force delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">No deleted purchases.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Suppliers --}}
    @if ($tab === 'suppliers')
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trashedSuppliers as $supplier)
                            <tr wire:key="bin-supplier-{{ $supplier->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $supplier->name }}</td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $supplier->deleted_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-3 align-middle text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <button wire:click="restoreSupplier({{ $supplier->id }})" class="cursor-pointer text-sm font-medium text-teal-600 transition-colors duration-150 hover:text-teal-700">Restore</button>
                                        <button
                                            wire:click="forceDeleteSupplier({{ $supplier->id }})"
                                            wire:confirm="Permanently delete {{ $supplier->name }}? This cannot be undone."
                                            class="cursor-pointer text-sm font-medium text-rose-600 transition-colors duration-150 hover:text-rose-700"
                                        >Force delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-12 text-center text-sm text-slate-500">No deleted suppliers.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Customers --}}
    @if ($tab === 'customers')
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trashedCustomers as $customer)
                            <tr wire:key="bin-customer-{{ $customer->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $customer->name }}</td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $customer->deleted_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-3 align-middle text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <button wire:click="restoreCustomer({{ $customer->id }})" class="cursor-pointer text-sm font-medium text-teal-600 transition-colors duration-150 hover:text-teal-700">Restore</button>
                                        <button
                                            wire:click="forceDeleteCustomer({{ $customer->id }})"
                                            wire:confirm="Permanently delete {{ $customer->name }}? This cannot be undone."
                                            class="cursor-pointer text-sm font-medium text-rose-600 transition-colors duration-150 hover:text-rose-700"
                                        >Force delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-12 text-center text-sm text-slate-500">No deleted customers.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
