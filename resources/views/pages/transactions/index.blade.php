<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div x-data="{ open: false, selected: [], selectAll: false, isBulk: false }"
            x-init="$watch('selectAll', value => { if (value) { selected = @json($transactions->pluck('id')); } else { selected = []; } })">
            <!-- Page header -->
            <div class="sm:flex sm:justify-between sm:items-center mb-8">
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                        {{ __('common.transaction.title') }}
                    </h1>
                </div>
                <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                    <!-- Add Transaction (Manual) button -->
                    <a href="{{ route('transactions.create') }}" class="btn bg-indigo-500 hover:bg-indigo-600 text-white cursor-pointer">
                        <i class="fa-solid fa-plus w-4 h-4"></i>
                        <span class="hidden xs:block ml-2">{{ __('common.transaction.add_manual') }}</span>
                    </a>
                </div>
            </div>

            @if (session('success'))
            <div class="mb-8">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            </div>
            @endif

            <!-- Bulk Delete Button -->
            <div class="mb-4 flex items-center gap-2">
                <button type="button" class="btn bg-red-500 hover:bg-red-600 text-white cursor-pointer"
                    :disabled="selected.length === 0" @click="$dispatch('open-delete-modal', {
                        isBulk: true,
                        itemIds: selected,
                        itemName: selected.length + ' transactions',
                        deleteRoute: '{{ route('transactions.bulk-destroy') }}'
                    })">
                    <i class="fa-solid fa-trash"></i> <span class="ml-2">{{ __('common.bulk_delete') }}</span>
                </button>
            </div>

            <!-- Transactions Table -->
            <x-table :title="__('Transaction')" :meta="$transactions->total()">
                <x-slot name="headerSlot">
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                        <input type="checkbox" x-model="selectAll" class="form-checkbox"
                            title="{{ __('common.select_all') }}">
                    </th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">{{
                        __('common.transaction.code') }}</th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">{{
                        __('common.transaction.user') }}</th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">{{
                        __('common.transaction.shipping_number') }}</th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">{{
                        __('common.transaction.created_at') }}</th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">{{
                        __('common.transaction.description') }}</th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">{{
                        __('common.actions_column') }}</th>
                </x-slot>
                @forelse($transactions as $transaction)
                <tr x-data="{ showDesc{{ $transaction->id }}: false }">
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <input type="checkbox" class="form-checkbox" :value="{{ $transaction->id }}"
                            x-model="selected" />
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ $transaction->transaction_code }}
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ $transaction->user->name ?? '-' }}
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ $transaction->shipping_number }}
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{
                        $transaction->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <button type="button" class="text-violet-600 hover:underline focus:outline-none cursor-pointer"
                            @click="showDesc{{ $transaction->id }} = true">{{ __('common.see_description') }}</button>
                        <div x-show="showDesc{{ $transaction->id }}"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                            style="display: none;">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-lg w-full p-6 relative">
                                <button @click="showDesc{{ $transaction->id }} = false"
                                    class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl cursor-pointer">&times;</button>
                                <h2 class="text-lg font-semibold mb-4">{{ __('common.transaction.description') }}</h2>
                                <pre class="whitespace-pre-wrap text-sm">{{ $transaction->description }}</pre>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <a href="{{ route('transactions.download_pdf', $transaction) }}"
                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600">
                            <i class="fa-solid fa-download"></i>
                        </a>
                        <button type="button" @click="$dispatch('open-delete-modal', {
                                itemId: {{ $transaction->id }},
                                itemName: '{{ $transaction->transaction_code }}',
                                deleteRoute: '{{ route('transactions.destroy', $transaction->id) }}'
                            })" class="text-red-500 hover:text-red-600 cursor-pointer ml-2" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <x-table.empty-row :colspan="7" :message="__('common.transaction.no_transactions_found')" />
                @endforelse
            </x-table>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $transactions->links() }}
            </div>

            <!-- Delete Confirmation Modal Component -->
            <x-modal.delete-confirmation>
                <x-slot:title>{{ __('common.transaction.delete_title') }}</x-slot:title>
                <template x-if="isBulk">
                    <span>{{ __('common.transaction.delete_warning') }} <span x-text="itemName"></span></span>
                </template>
                <template x-if="!isBulk">
                    {{ __('common.transaction.delete_warning') }}
                </template>
                <form x-show="isBulk" :action="deleteRoute" method="POST" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" :value="JSON.stringify(ids)">
                    <button type="submit" class="btn bg-red-600 text-white cursor-pointer">{{ __('common.actions.delete') }}</button>
                </form>
            </x-modal.delete-confirmation>
        </div>
    </div>
</x-app-layout>
