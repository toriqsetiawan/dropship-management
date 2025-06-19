<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div x-data="{
            open: false,
            selected: [],
            selectAll: false,
            isBulk: false,
            statusFilter: 'pending',
            bulkStatus: '',
            showStatusModal: false,
            bulkUpdateStatus() {
                if (!this.bulkStatus || this.selected.length === 0) {
                    alert('{{ __('common.status.no_selection') }}');
                    return;
                }

                fetch('{{ route('transactions.update-status') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        transaction_ids: this.selected,
                        status: this.bulkStatus
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || '{{ __('common.status.update_error') }}');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __('common.status.update_error') }}');
                });
            }
        }"
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

            <!-- Status Tabs -->
            <div class="mb-6 flex gap-2">
                <button type="button" @click="statusFilter = ''" :class="statusFilter === '' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-lg font-semibold">
                    {{ __('common.all') }}
                    <span class="ml-2 text-xs font-bold">({{ $transactions->total() }})</span>
                </button>
                <button type="button" @click="statusFilter = 'pending'" :class="statusFilter === 'pending' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-lg font-semibold">
                    {{ __('common.transaction.status_pending') }}
                    <span class="ml-2 text-xs font-bold">({{ $statusCounts['pending'] ?? 0 }})</span>
                </button>
                <button type="button" @click="statusFilter = 'processed'" :class="statusFilter === 'processed' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-lg font-semibold">
                    {{ __('common.transaction.status_processed') }}
                    <span class="ml-2 text-xs font-bold">({{ $statusCounts['processed'] ?? 0 }})</span>
                </button>
                <button type="button" @click="statusFilter = 'packed'" :class="statusFilter === 'packed' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-lg font-semibold">
                    {{ __('common.transaction.status_packed') }}
                    <span class="ml-2 text-xs font-bold">({{ $statusCounts['packed'] ?? 0 }})</span>
                </button>
                <button type="button" @click="statusFilter = 'shipped'" :class="statusFilter === 'shipped' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-lg font-semibold">
                    {{ __('common.transaction.status_shipped') }}
                    <span class="ml-2 text-xs font-bold">({{ $statusCounts['shipped'] ?? 0 }})</span>
                </button>
            </div>

            <!-- Bulk Status Update -->
            <div class="mb-4 flex items-center gap-2">
                <select x-model="bulkStatus" class="form-select w-auto">
                    <option value="">{{ __('common.transaction.select_status') }}</option>
                    <option value="pending">{{ __('common.transaction.status_pending') }}</option>
                    <option value="processed">{{ __('common.transaction.status_processed') }}</option>
                    <option value="packed">{{ __('common.transaction.status_packed') }}</option>
                    <option value="shipped">{{ __('common.transaction.status_shipped') }}</option>
                </select>
                <button type="button" class="btn bg-blue-500 hover:bg-blue-600 text-white" :disabled="!bulkStatus || selected.length === 0" @click="showStatusModal = true">
                    {{ __('common.transaction.update_status') }}
                </button>
            </div>

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
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">{{ __('common.transaction.status') }}</th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">{{
                        __('common.actions_column') }}</th>
                </x-slot>
                @forelse($transactions as $transaction)
                <tr x-data="{ showDesc{{ $transaction->id }}: false }" x-show="!statusFilter || statusFilter === '{{ $transaction->status }}'">
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
                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold"
                            :class="{
                                'bg-yellow-200 text-yellow-800': '{{ $transaction->status }}' === 'pending',
                                'bg-blue-200 text-blue-800': '{{ $transaction->status }}' === 'processed',
                                'bg-purple-200 text-purple-800': '{{ $transaction->status }}' === 'packed',
                                'bg-green-200 text-green-800': '{{ $transaction->status }}' === 'shipped',
                            }">
                            {{ __('common.transaction.status_' . $transaction->status) }}
                        </span>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        @if ($transaction->status === 'pending')
                            <button
                                class="btn btn-sm btn-primary cursor-pointer"
                                @click="printPdf('{{ $transaction->shipping_pdf_path }}', '{{ $transaction->id }}')"
                                title="{{ __('common.transaction.print_pdf_title') }}"
                            >
                                <i class="fa-solid fa-print w-4 h-4 inline"></i>
                                {{ __('common.transaction.print_pdf_title') }}
                            </button>
                        @endif
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
                {{ __('common.transaction.delete_warning') }}
            </x-modal.delete-confirmation>

            <!-- Bulk Status Confirmation Modal -->
            <div
                x-show="showStatusModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
                style="display: none;"
            >
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-lg w-full p-6 relative">
                    <button @click="showStatusModal = false" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl cursor-pointer">&times;</button>
                    <div class="flex items-center mb-4">
                        <div class="shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mr-4">
                            <svg class="h-6 w-6 text-red-600" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ __('common.transaction.update_status') }}
                        </h3>
                    </div>
                    <div class="mb-6">
                        {{ __('common.transaction.confirm_update_status') }}
                    </div>
                    <div class="flex flex-row justify-end gap-2">
                        <button type="button" class="btn bg-gray-300" @click="showStatusModal = false">{{ __('common.actions.cancel') }}</button>
                        <button type="button" class="btn bg-blue-500 text-white" @click="bulkUpdateStatus(); showStatusModal = false">{{ __('common.transaction.update_status') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function printPdf(pdfPath, transactionId) {
        window.open('/transactions/print-pdf/' + transactionId, '_blank');
        fetch('/transactions/update-status-by-pdf', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ pdf_path: pdfPath })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Gagal update status');
            }
        });
    }
    </script>
</x-app-layout>
