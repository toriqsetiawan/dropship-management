@extends('layouts.app')

@section('content')
<div x-data="transactionStatus" class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Status Management -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <select x-model="selectedStatus" class="form-select rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <option value="">{{ __('common.transaction.select_status') }}</option>
                <option value="pending">{{ __('common.transaction.status_pending') }}</option>
                <option value="processed">{{ __('common.transaction.status_processed') }}</option>
                <option value="packed">{{ __('common.transaction.status_packed') }}</option>
                <option value="shipped">{{ __('common.transaction.status_shipped') }}</option>
            </select>
            <button id="bulk-update-btn"
                    @click="confirmUpdate()"
                    :disabled="selectedTransactions.length === 0"
                    class="btn btn-primary">
                {{ __('common.transaction.update_status') }}
            </button>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox"
                               @click="selectedTransactions = $event.target.checked ? transactions.map(t => t.id) : []"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('common.transaction.shipping_number') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('common.transaction.recipient') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('common.transaction.items') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('common.transaction.created_at') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('common.transaction.status') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('common.actions_column') }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox"
                               x-model="selectedTransactions"
                               value="{{ $transaction->id }}"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $transaction->shipping_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $transaction->recipient }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            @foreach($transaction->items as $item)
                                <div>{{ $item->quantity }}x {{ $item->variant->product->name }} ({{ $item->variant->sku }})</div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $transaction->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            @if($transaction->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($transaction->status === 'processed') bg-blue-100 text-blue-800
                            @elseif($transaction->status === 'packed') bg-purple-100 text-purple-800
                            @else bg-green-100 text-green-800
                            @endif">
                            {{ __('common.transaction.status_' . $transaction->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <button @click="confirmUpdate({{ $transaction->id }})"
                                    class="text-blue-600 hover:text-blue-900">
                                {{ __('common.status.update') }}
                            </button>
                            <a href="{{ route('transactions.edit', $transaction) }}"
                               class="text-indigo-600 hover:text-indigo-900">
                                {{ __('common.actions.edit') }}
                            </a>
                            <form action="{{ route('transactions.destroy', $transaction) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('{{ __('common.messages.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    {{ __('common.actions.delete') }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        {{ __('common.transaction.no_transactions_found') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>

<!-- Status Update Confirmation Modal -->
<div x-data="{ show: false }"
     x-show="show"
     x-on:open-modal.window="show = true"
     x-on:close-modal.window="show = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ __('common.status.update') }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                {{ __('common.status.confirm_update') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button"
                        @click="updateStatus()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('common.status.update') }}
                </button>
                <button type="button"
                        @click="show = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('common.actions.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('transactionStatus', () => ({
        selectedTransactions: [],
        selectedStatus: '',
        isUpdating: false,
        showConfirmModal: false,
        transactions: @json($transactions->items()),

        init() {
            this.$watch('selectedTransactions', (value) => {
                const bulkUpdateBtn = document.getElementById('bulk-update-btn');
                if (bulkUpdateBtn) {
                    bulkUpdateBtn.disabled = value.length === 0;
                }
            });
        },

        async updateStatus(transactionId = null) {
            if (this.isUpdating) return;

            const ids = transactionId ? [transactionId] : this.selectedTransactions;
            if (!ids.length) {
                alert(__('common.status.no_selection'));
                return;
            }

            this.isUpdating = true;
            try {
                const response = await fetch('{{ route("transactions.update-status") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        transaction_ids: ids,
                        status: this.selectedStatus
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                alert(error.message || __('common.status.update_error'));
            } finally {
                this.isUpdating = false;
                this.showConfirmModal = false;
                this.selectedStatus = '';
            }
        },

        confirmUpdate(transactionId = null) {
            if (!this.selectedStatus) {
                alert(__('common.status.select_status'));
                return;
            }
            this.showConfirmModal = true;
        }
    }));
});
</script>
@endpush
@endsection
