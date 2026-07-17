<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Customers</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">Everyone who's purchased from the counter.</p>
        </div>
        <button type="button" wire:click="openCreate" class="inline-flex cursor-pointer items-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md active:translate-y-0">
            + Add Customer
        </button>
    </div>

    <input type="text" wire:model.live.debounce.300ms="q" placeholder="Search customers..."
        class="mb-4 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm sm:max-w-sm" />

    @if (count($selected) > 0)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3">
            <span class="text-sm font-medium text-teal-800">{{ count($selected) }} selected</span>
            <div class="flex gap-2">
                <button
                    type="button"
                    wire:click="bulkDelete"
                    wire:confirm="Soft delete {{ count($selected) }} customer(s)? They can be restored or force-deleted later."
                    class="cursor-pointer rounded-xl border border-amber-300 bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-800 transition-colors duration-150 hover:bg-amber-200"
                >Delete selected</button>
                <button
                    type="button"
                    wire:click="bulkForceDelete"
                    wire:confirm="Permanently delete {{ count($selected) }} customer(s)? This cannot be undone."
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
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</th>
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr wire:key="cust-{{ $customer->id }}" class="border-b border-slate-50 last:border-0">
                            <td class="px-4 py-3 align-middle">
                                <input type="checkbox" wire:model.live="selected" value="{{ $customer->id }}" class="cursor-pointer rounded border-slate-300" />
                            </td>
                            <td class="px-4 py-3 align-middle font-medium text-slate-900">{{ $customer->name }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $customer->email ?? '—' }}</td>
                            <td class="px-4 py-3 align-middle text-slate-700">{{ $customer->phone ?? '—' }}</td>
                            <td class="px-4 py-3 align-middle text-right">
                                <button wire:click="openEdit({{ $customer->id }})" class="cursor-pointer text-sm font-medium text-teal-700 transition-colors duration-150 hover:text-teal-800">Edit</button>
                                <button wire:click="delete({{ $customer->id }})" wire:confirm="Delete {{ $customer->name }}?" class="ml-3 cursor-pointer text-sm font-medium text-rose-600 transition-colors duration-150 hover:text-rose-700">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">No customers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $customers->links() }}</div>
    </div>

    @if ($trashed->count() > 0)
        <div class="mt-8">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Trash ({{ $trashed->count() }})</h2>
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
                            @foreach ($trashed as $customer)
                                <tr wire:key="trashed-cust-{{ $customer->id }}" class="border-b border-slate-50 last:border-0">
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ $customer->name }}</td>
                                    <td class="px-4 py-3 align-middle text-slate-700">{{ $customer->deleted_at?->format('M j, Y g:i A') }}</td>
                                    <td class="px-4 py-3 align-middle text-right">
                                        <button
                                            wire:click="forceDelete({{ $customer->id }})"
                                            wire:confirm="Permanently delete {{ $customer->name }}? This cannot be undone."
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

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 p-4 sm:items-center">
            <div class="absolute inset-0 cursor-pointer" wire:click="closeModal"></div>
            <div class="relative z-10 max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div class="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-4">
                    <h3 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Customer' : 'Add Customer' }}</h3>
                    <button wire:click="closeModal" class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-150 hover:bg-slate-100">Close</button>
                </div>
                <form wire:submit="save" class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
                        <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                        <input type="email" wire:model="email" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</label>
                        <input type="text" wire:model="phone" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address</label>
                        <textarea wire:model="address" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                        <textarea wire:model="notes" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <button type="button" wire:click="closeModal" class="cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="cursor-pointer rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-150 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md active:translate-y-0">
                            {{ $editingId ? 'Save changes' : 'Add customer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
