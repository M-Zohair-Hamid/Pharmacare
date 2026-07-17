@props(['label', 'value', 'hint' => null, 'accent' => 'teal'])

@php
$accents = [
    'teal' => 'from-teal-500/10 to-cyan-500/5 text-teal-700',
    'amber' => 'from-amber-500/10 to-orange-500/5 text-amber-700',
    'rose' => 'from-rose-500/10 to-pink-500/5 text-rose-700',
    'indigo' => 'from-indigo-500/10 to-violet-500/5 text-indigo-700',
    'emerald' => 'from-emerald-500/10 to-teal-500/5 text-emerald-700',
];
@endphp

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)] bg-gradient-to-br p-5 ' . ($accents[$accent] ?? $accents['teal'])]) }}>
    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $label }}</p>
    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-2 text-sm text-slate-500">{{ $hint }}</p>
    @endif
</div>
