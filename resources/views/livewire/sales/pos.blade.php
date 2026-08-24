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
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search medicine by name or generic name..."
                class="mb-4 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" />

            <div class="mb-4 flex flex-col gap-3 sm:flex-row">
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
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Medicine</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Unit Price</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">In stock</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Expiry</th>
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($medicines as $medicine)
                                <tr wire:key="pick-{{ $medicine->id }}" class="border-b border-slate-50 last:border-0">
                                    <td class="px-4 py-3 align-middle">
                                        <div class="font-medium text-slate-900">{{ $medicine->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $medicine->manufacturer ?? '—' }}</div>
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $medicine->medicine_type }}</span>
                                    </td>
                                    <td class="px-4 py-3 align-middle text-slate-700">
                                        {{ number_format($medicine->unit_price, 2) }}
                                        <span class="text-xs text-slate-400">/ {{ $medicine->medicine_type }}</span>
                                    </td>
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ $medicine->quantity }}</td>
                                    <td class="px-4 py-3 align-middle text-slate-700">
                                        {{ $medicine->expiry_date?->format('M j, Y') ?? '—' }}
                                        @php $expiryStatus = $medicine->expiry_status; @endphp
                                        @if ($expiryStatus)
                                            <span @class([
                                                'ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                'bg-rose-50 text-rose-700' => $expiryStatus === 'expired',
                                                'bg-amber-50 text-amber-700' => $expiryStatus === 'soon',
                                                'bg-emerald-50 text-emerald-700' => $expiryStatus === 'ok',
                                            ])>
                                                {{ $expiryStatus === 'expired' ? 'Expired' : ($expiryStatus === 'soon' ? 'Soon' : 'OK') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-middle text-right">
                                        <button wire:click="addToCart({{ $medicine->id }})" class="cursor-pointer rounded-xl bg-teal-600 px-3 py-1.5 text-xs font-medium text-white transition-all duration-150 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md active:translate-y-0">Add</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No medicines in stock match your search.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-4 text-center">
                    @if ($medicines->hasMorePages())
                        <div wire:intersect.margin.200px="loadMore" wire:loading.remove wire:target="loadMore" class="h-1"></div>
                        <div wire:loading wire:target="loadMore" class="flex items-center justify-center gap-2 text-xs text-slate-400">
                            <svg class="h-4 w-4 animate-spin text-teal-600" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Loading more…
                        </div>
                    @else
                        <p class="text-xs text-slate-400">Showing all {{ $medicines->total() }} medicines.</p>
                    @endif
                </div>
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
                                    <p class="text-xs text-slate-500">
                                        {{ number_format($item['unit_price'], 2) }} / {{ $item['unit_type'] }}
                                    </p>
                                </div>
                                <button wire:click="removeFromCart('{{ $medicineId }}')" class="cursor-pointer text-xs text-rose-600 transition-colors duration-150 hover:text-rose-700">Remove</button>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <input
                                    type="number"
                                    min="1"
                                    max="{{ $item['available'] }}"
                                    value="{{ $item['quantity'] }}"
                                    wire:change="updateQuantity('{{ $medicineId }}', $event.target.value)"
                                    class="w-20 rounded-lg border border-slate-200 px-2 py-1 text-sm"
                                />
                                @if ($item['sellable_as_box'])
                                    <select
                                        wire:change="changeCartUnit('{{ $medicineId }}', $event.target.value)"
                                        class="cursor-pointer rounded-lg border border-slate-200 px-2 py-1 text-sm"
                                    >
                                        <option value="tablet" @selected($item['sale_unit'] === 'tablet')>Tablet(s)</option>
                                        <option value="box" @selected($item['sale_unit'] === 'box')>Box(es)</option>
                                    </select>
                                @else
                                    <span class="text-sm text-slate-500">{{ $item['unit_type'] }}(s)</span>
                                @endif
                                <span class="ml-auto text-sm font-medium text-slate-900">
                                    {{ number_format($item['quantity'] * $item['unit_price'], 2) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No items yet. Add medicines from the list.</p>
                    @endforelse
                </div>

                <div class="mt-4 border-t border-slate-100 pt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-700">Bill Total</span>
                        <span class="text-sm font-medium text-slate-900">{{ number_format($this->cartTotal, 2) }}</span>
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-2">
                        <label for="discountPercent" class="text-sm font-medium text-slate-700">Discount %</label>
                        <div class="flex items-center gap-1">
                            <input
                                id="discountPercent"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                placeholder="0"
                                wire:model.live.debounce.300ms="discountPercent"
                                class="w-20 rounded-lg border border-slate-200 px-2 py-1 text-right text-sm"
                            />
                            <span class="text-sm text-slate-500">%</span>
                        </div>
                    </div>

                    @if ($this->discountPercentValue > 0)
                        <div class="mt-2 flex items-center justify-between text-rose-600">
                            <span class="text-sm font-medium">Discount ({{ rtrim(rtrim(number_format($this->discountPercentValue, 2), '0'), '.') }}%)</span>
                            <span class="text-sm font-medium">&minus; {{ number_format($this->discountAmount, 2) }}</span>
                        </div>
                    @endif

                    <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2">
                        <span class="text-sm font-semibold text-slate-700">Your Bill</span>
                        <span class="text-lg font-semibold text-slate-900">{{ number_format($this->netTotal, 2) }}</span>
                    </div>
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
