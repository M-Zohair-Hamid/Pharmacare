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
            'customers' => ['Customers', $trashedCustomers->count()],
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
        @php $medicineIds = $trashedMedicines->pluck('id')->all(); @endphp
        @if (count($medicineIds) > 0)
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                    <input
                        type="checkbox"
                        class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        @checked(count($medicineIds) > 0 && count(array_diff($medicineIds, $selected['medicines'])) === 0)
                        wire:click="toggleSelectAll('medicines', {{ json_encode($medicineIds) }})"
                    />
                    Select all
                    <span class="text-xs font-normal text-slate-400">({{ count($selected['medicines']) }} selected)</span>
                </label>
                <button
                    type="button"
                    wire:click="deleteAllSelected('medicines')"
                    wire:confirm="Permanently delete {{ count($selected['medicines']) }} selected medicine(s)? This cannot be undone and will also remove their sale/purchase history."
                    @disabled(count($selected['medicines']) === 0)
                    class="cursor-pointer rounded-xl bg-rose-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                >Delete all selected</button>
            </div>
        @endif
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Medicine</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trashedMedicines as $medicine)
                            <tr wire:key="bin-medicine-{{ $medicine->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle">
                                    <input type="checkbox" class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500" wire:model="selected.medicines" value="{{ $medicine->id }}" />
                                </td>
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
                            <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">No deleted medicines.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Sales --}}
    @if ($tab === 'sales')
        @php $saleIds = $trashedSales->pluck('id')->all(); @endphp
        @if (count($saleIds) > 0)
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                    <input
                        type="checkbox"
                        class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        @checked(count($saleIds) > 0 && count(array_diff($saleIds, $selected['sales'])) === 0)
                        wire:click="toggleSelectAll('sales', {{ json_encode($saleIds) }})"
                    />
                    Select all
                    <span class="text-xs font-normal text-slate-400">({{ count($selected['sales']) }} selected)</span>
                </label>
                <button
                    type="button"
                    wire:click="deleteAllSelected('sales')"
                    wire:confirm="Permanently delete {{ count($selected['sales']) }} selected sale(s)? This cannot be undone."
                    @disabled(count($selected['sales']) === 0)
                    class="cursor-pointer rounded-xl bg-rose-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                >Delete all selected</button>
            </div>
        @endif
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Bill Code</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at (PKT)</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trashedSales as $sale)
                            <tr wire:key="bin-sale-{{ $sale->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle">
                                    <input type="checkbox" class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500" wire:model="selected.sales" value="{{ $sale->id }}" />
                                </td>
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
                            <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">No deleted sales.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Purchases --}}
    @if ($tab === 'purchases')
        @php $purchaseIds = $trashedPurchases->pluck('id')->all(); @endphp
        @if (count($purchaseIds) > 0)
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                    <input
                        type="checkbox"
                        class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        @checked(count($purchaseIds) > 0 && count(array_diff($purchaseIds, $selected['purchases'])) === 0)
                        wire:click="toggleSelectAll('purchases', {{ json_encode($purchaseIds) }})"
                    />
                    Select all
                    <span class="text-xs font-normal text-slate-400">({{ count($selected['purchases']) }} selected)</span>
                </label>
                <button
                    type="button"
                    wire:click="deleteAllSelected('purchases')"
                    wire:confirm="Permanently delete {{ count($selected['purchases']) }} selected purchase(s)? This cannot be undone."
                    @disabled(count($selected['purchases']) === 0)
                    class="cursor-pointer rounded-xl bg-rose-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                >Delete all selected</button>
            </div>
        @endif
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Purchase</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Supplier</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trashedPurchases as $purchase)
                            <tr wire:key="bin-purchase-{{ $purchase->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle">
                                    <input type="checkbox" class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500" wire:model="selected.purchases" value="{{ $purchase->id }}" />
                                </td>
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
                            <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">No deleted purchases.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Suppliers --}}
    @if ($tab === 'suppliers')
        @php $supplierIds = $trashedSuppliers->pluck('id')->all(); @endphp
        @if (count($supplierIds) > 0)
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                    <input
                        type="checkbox"
                        class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        @checked(count($supplierIds) > 0 && count(array_diff($supplierIds, $selected['suppliers'])) === 0)
                        wire:click="toggleSelectAll('suppliers', {{ json_encode($supplierIds) }})"
                    />
                    Select all
                    <span class="text-xs font-normal text-slate-400">({{ count($selected['suppliers']) }} selected)</span>
                </label>
                <button
                    type="button"
                    wire:click="deleteAllSelected('suppliers')"
                    wire:confirm="Permanently delete {{ count($selected['suppliers']) }} selected supplier(s)? This cannot be undone."
                    @disabled(count($selected['suppliers']) === 0)
                    class="cursor-pointer rounded-xl bg-rose-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                >Delete all selected</button>
            </div>
        @endif
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trashedSuppliers as $supplier)
                            <tr wire:key="bin-supplier-{{ $supplier->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle">
                                    <input type="checkbox" class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500" wire:model="selected.suppliers" value="{{ $supplier->id }}" />
                                </td>
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
                            <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">No deleted suppliers.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Customers --}}
    @if ($tab === 'customers')
        @php $customerIds = $trashedCustomers->pluck('id')->all(); @endphp
        @if (count($customerIds) > 0)
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                    <input
                        type="checkbox"
                        class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        @checked(count($customerIds) > 0 && count(array_diff($customerIds, $selected['customers'])) === 0)
                        wire:click="toggleSelectAll('customers', {{ json_encode($customerIds) }})"
                    />
                    Select all
                    <span class="text-xs font-normal text-slate-400">({{ count($selected['customers']) }} selected)</span>
                </label>
                <button
                    type="button"
                    wire:click="deleteAllSelected('customers')"
                    wire:confirm="Permanently delete {{ count($selected['customers']) }} selected customer(s)? This cannot be undone."
                    @disabled(count($selected['customers']) === 0)
                    class="cursor-pointer rounded-xl bg-rose-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                >Delete all selected</button>
            </div>
        @endif
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Deleted at</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trashedCustomers as $customer)
                            <tr wire:key="bin-customer-{{ $customer->id }}" class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle">
                                    <input type="checkbox" class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 focus:ring-teal-500" wire:model="selected.customers" value="{{ $customer->id }}" />
                                </td>
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
                            <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">No deleted customers.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
