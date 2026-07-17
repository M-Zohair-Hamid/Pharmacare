<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? 'PharmaCare | Pharmacy Management System' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:pl-72">
        <button
            type="button"
            @click="sidebarOpen = !sidebarOpen"
            :aria-label="sidebarOpen ? 'Close navigation' : 'Open navigation'"
            class="fixed left-4 top-4 z-50 inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-lg lg:hidden"
        >
            <span class="text-xl leading-none">☰</span>
        </button>

        <button
            type="button"
            x-show="sidebarOpen"
            @click="sidebarOpen = false"
            aria-label="Close navigation overlay"
            class="fixed inset-0 z-40 cursor-default bg-slate-950/40 lg:hidden"
        ></button>

        <div
            class="fixed inset-y-0 left-0 z-40 transform transition-transform duration-300 ease-out lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <x-sidebar />
        </div>

        <main class="px-4 py-6 pt-20 sm:px-6 lg:px-8 lg:py-8 lg:pt-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
