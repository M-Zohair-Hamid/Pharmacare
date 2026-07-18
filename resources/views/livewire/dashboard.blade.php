<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Pharmacy Dashboard</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">
                Monitor inventory health, sales performance, and items that need attention.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sales.pos') }}" class="inline-flex cursor-pointer items-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md active:translate-y-0">New Sale</a>
            <a href="{{ route('medicines.index') }}" class="inline-flex cursor-pointer items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-all duration-150 hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md active:translate-y-0">Manage Stock</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Medicines" :value="$medicineCount" hint="Active catalog items" accent="teal" />
        <x-stat-card label="Revenue" :value="number_format($revenue, 2)" :hint="$saleCount . ' sales recorded'" accent="emerald" />
        <x-stat-card label="Inventory Value" :value="number_format($inventoryValue, 2)" hint="Retail valuation" accent="indigo" />
        <x-stat-card label="Stock Alerts" :value="$lowStock->count()" :hint="$lowStock->where('quantity', '<=', 0)->count() . ' out of stock'" accent="rose" />
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <x-stat-card label="Suppliers" :value="$supplierCount" accent="teal" />
        <x-stat-card label="Purchases" :value="$purchaseCount" accent="indigo" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Low Stock</h2>
                    <p class="mt-1 text-sm text-slate-500">At or below {{ $lowStockThreshold }} units</p>
                </div>
                <a href="{{ route('medicines.index') }}" class="cursor-pointer text-sm font-medium text-teal-700 transition-colors duration-150 hover:text-teal-800">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Medicine</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStock as $item)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle">
                                    <div class="font-medium text-slate-900">{{ $item->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->medicine_type }} · {{ $item->category }}</div>
                                </td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 align-middle">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-rose-50 text-rose-700' => $item->quantity <= 0,
                                        'bg-amber-50 text-amber-700' => $item->quantity > 0,
                                    ])>
                                        {{ $item->quantity <= 0 ? 'Out of stock' : 'Low stock' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-sm text-slate-500">All stock levels look healthy.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Expiring Soon</h2>
                <p class="mt-1 text-sm text-slate-500">Within the next 60 days</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Medicine</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Expiry</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expiringSoon as $item)
                            @php $days = (int) floor(now()->diffInDays($item->expiry_date, false)); @endphp
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle">
                                    <div class="font-medium text-slate-900">{{ $item->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->medicine_type }} · qty {{ $item->quantity }}</div>
                                </td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $item->expiry_date->format('M j, Y') }}</td>
                                <td class="px-4 py-3 align-middle">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-rose-50 text-rose-700' => $days <= 30,
                                        'bg-amber-50 text-amber-700' => $days > 30,
                                    ])>
                                        {{ $days < 0 ? 'Expired' : $days . 'd' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-sm text-slate-500">No near-expiry items.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-5">
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm xl:col-span-3">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Recent Sales</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest transactions at the counter (PKT)</p>
                </div>
                <a href="{{ route('sales.history') }}" class="cursor-pointer text-sm font-medium text-teal-700 transition-colors duration-150 hover:text-teal-800">Full history</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Sale</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSales as $sale)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-4 py-3 align-middle">
                                    <div class="font-medium text-slate-900">{{ $sale->bill_code }}</div>
                                    <div class="text-xs text-slate-500">{{ $sale->created_at->timezone('Asia/Karachi')->format('M j, Y g:i A') }}</div>
                                </td>
                                <td class="px-4 py-3 align-middle text-slate-700">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                                <td class="px-4 py-3 align-middle capitalize text-slate-700">{{ $sale->payment_method }}</td>
                                <td class="px-4 py-3 align-middle font-medium text-slate-900">{{ number_format($sale->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-sm text-slate-500">No sales yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Stock by Category</h2>
                <p class="mt-1 text-sm text-slate-500">Quantity distribution across therapeutic groups</p>
            </div>
            <div class="space-y-3 p-5">
                @forelse ($categoryBreakdown as $cat)
                    @php $max = max($categoryBreakdown->max('stock'), 1); $width = max(8, ($cat->stock / $max) * 100); @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-800">{{ $cat->category }}</span>
                            <span class="text-slate-500">{{ $cat->items }} items · {{ $cat->stock }} units</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-linear-to-r from-teal-500 to-cyan-400" style="width: {{ $width }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No categories yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
