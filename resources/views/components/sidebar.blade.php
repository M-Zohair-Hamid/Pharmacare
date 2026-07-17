@php
$nav = [
    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '📊'],
    ['route' => 'medicines.index', 'label' => 'Medicines', 'icon' => '💊'],
    ['route' => 'sales.pos', 'label' => 'Sales / POS', 'icon' => '🧾'],
    ['route' => 'purchases.index', 'label' => 'Purchases', 'icon' => '📦'],
    ['route' => 'suppliers.index', 'label' => 'Suppliers', 'icon' => '🚚'],
    ['route' => 'customers.index', 'label' => 'Customers', 'icon' => '👥'],
];
@endphp

<aside class="flex h-full w-72 flex-col border-r border-teal-900/10 bg-slate-950 text-white">
    <div class="border-b border-white/10 px-5 py-6">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-400 to-cyan-500 text-xl shadow-lg shadow-teal-500/30">
                ⚕️
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-300">PharmaCare</p>
                <h1 class="text-lg font-semibold">Pharmacy OS</h1>
            </div>
        </div>
        <p class="mt-4 text-sm leading-6 text-slate-300">
            Inventory, sales, purchases, and customer records in one place.
        </p>
    </div>

    <nav class="flex gap-2 overflow-x-auto px-3 py-4 lg:flex-1 lg:flex-col lg:overflow-visible">
        @foreach ($nav as $item)
            @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*'); @endphp
            <a
                href="{{ route($item['route']) }}"
                @class([
                    'flex min-w-fit items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition',
                    'bg-white text-slate-950 shadow-lg shadow-black/10' => $active,
                    'text-slate-300 hover:bg-white/10 hover:text-white' => !$active,
                ])
            >
                <span class="text-base">{{ $item['icon'] }}</span>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="mt-auto border-t border-white/10 p-5">
        <div class="rounded-2xl bg-gradient-to-br from-teal-500/20 to-cyan-400/10 p-4">
            <p class="text-sm font-medium text-white">Stock health tip</p>
            <p class="mt-2 text-xs leading-5 text-slate-300">
                Review low-stock and near-expiry medicines daily to avoid stockouts and waste.
            </p>
        </div>
    </div>
</aside>
