<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('supplier.title') }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add supplier button -->
                <a href="{{ route('supplier.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="hidden xs:block ml-2">{{ __('supplier.add_new') }}</span>
                </a>
            </div>

        </div>

        <!-- Table -->
        <x-table
            :headers="[
                __('supplier.name'),
                __('supplier.email'),
                __('supplier.phone'),
                __('supplier.status'),
                __('supplier.actions')
            ]"
            :title="__('supplier.all_suppliers')"
            :meta="$suppliers->total()"
        >
            @forelse($suppliers as $supplier)
                <tr>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $supplier->name }}</div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="text-gray-800 dark:text-gray-100">{{ $supplier->email }}</div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="text-gray-800 dark:text-gray-100">{{ $supplier->phone }}</div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="inline-flex font-medium rounded-full text-center px-2.5 py-0.5 {{ $supplier->status === 'active' ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-500' }}">
                            {{ __('supplier.' . $supplier->status) }}
                        </div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap w-px">
                        <div class="space-x-1">
                            <a href="{{ route('supplier.edit', $supplier->id) }}" class="text-violet-500 hover:text-violet-600">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button
                                type="button"
                                @click="$dispatch('open-delete-modal', {
                                    itemId: {{ $supplier->id }},
                                    itemName: '{{ $supplier->name }}',
                                    deleteRoute: '{{ route('supplier.destroy', $supplier->id) }}'
                                })"
                                class="text-red-500 hover:text-red-600">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <x-table.empty-row :message="__('supplier.no_suppliers')" />
            @endforelse
        </x-table>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $suppliers->links() }}
        </div>

        <!-- Delete Confirmation Modal Component -->
        <x-modal.delete-confirmation>
            <x-slot:title>{{ __('supplier.delete_title') }}</x-slot:title>
            {{ __('supplier.delete_warning') }}
        </x-modal.delete-confirmation>
    </div>
</x-app-layout>
