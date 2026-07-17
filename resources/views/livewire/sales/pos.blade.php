<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Sales / POS</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">Ring up a sale and print a receipt.</p>
    </div>

    @if ($lastSaleId)
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Sale #{{ $lastSaleId }} completed. Bill code: <span class="font-mono font-semibold tracking-widest">{{ $lastBillCode }}</span>
            <a href="{{ route('sales.receipt', $lastSaleId) }}" target="_blank" class="ml-2 cursor-pointer font-medium underline">Print receipt</a>
        </div>
    @endif

    @error('cart')
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Product picker --}}
        <div class="lg:col-span-2">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search medicine by name or SKU..."
                class="mb-4 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" />

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Medicine</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Price</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">In stock</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($medicines as $medicine)
                                <tr wire:key="pick-{{ $medicine->id }}" class="border-b border-slate-50 last:border-0">
                                    <td class="px-4 py-3 align-middle">
                                        <div class="font-medium text-slate-900">{{ $medicine->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $medicine->sku }} · {{ $medicine->medicine_type }}</div>
                                    </td>
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ number_format($medicine->unit_price, 2) }}</td>
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->quantity }}</td>
                                    <td class="px-4 py-3 align-middle text-right">
                                        <button wire:click="addToCart({{ $medicine->id }})" class="cursor-pointer rounded-xl bg-teal-600 px-3 py-1.5 text-xs font-medium text-white transition-all duration-150 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md active:translate-y-0">Add</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">No medicines in stock match your search.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">{{ $medicines->links() }}</div>
            </div>
        </div>

        {{-- Cart --}}
        <div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cart</h3>

                <div class="mt-4 space-y-3">
                    @forelse ($cart as $medicineId => $item)
                        <div wire:key="cart-{{ $medicineId }}" class="border-b border-slate-50 pb-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $item['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ number_format($item['unit_price'], 2) }} each</p>
                                </div>
                                <button wire:click="removeFromCart({{ $medicineId }})" class="cursor-pointer text-xs text-rose-600 transition-colors duration-150 hover:text-rose-700">Remove</button>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <input
                                    type="number"
                                    min="1"
                                    max="{{ $item['available'] }}"
                                    value="{{ $item['quantity'] }}"
                                    wire:change="updateQuantity({{ $medicineId }}, $event.target.value)"
                                    class="w-16 rounded-lg border border-slate-200 px-2 py-1 text-sm"
                                />
                                <select
                                    wire:change="updateUnitType({{ $medicineId }}, $event.target.value)"
                                    class="w-full cursor-pointer rounded-lg border border-slate-200 px-2 py-1 text-sm"
                                >
                                    @foreach ($unitTypes as $unitType)
                                        <option value="{{ $unitType }}" @selected($item['unit_type'] === $unitType)>{{ $unitType }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No items yet. Add medicines from the list.</p>
                    @endforelse
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                    <span class="text-sm font-medium text-slate-700">Total</span>
                    <span class="text-lg font-semibold text-slate-900">{{ number_format($this->cartTotal, 2) }}</span>
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Customer Name</label>
                        <input type="text" wire:model="customerName" placeholder="Walk-in (leave blank) or type a name"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Method</label>
                        <select wire:model="paymentMethod" class="w-full cursor-pointer rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="mobile">Mobile</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                        <textarea wire:model="notes" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>
                </div>

                <button
                    wire:click="checkout"
                    wire:loading.attr="disabled"
                    class="mt-4 w-full cursor-pointer rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Complete Sale
                </button>
            </div>
        </div>
    </div>
</div>
