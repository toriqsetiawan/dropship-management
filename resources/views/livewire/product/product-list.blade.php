<div>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        @if (session('success'))
            <div
                x-data="{ show: true }"
                x-show="show"
                x-init="setTimeout(() => show = false, 3000)"
                class="mb-4 p-4 px-8 py-4 bg-green-100 border border-green-400 text-green-700 rounded transition-all duration-500"
            >
                {{ session('success') }}
            </div>
        @endif
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('product.title') }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Search -->
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('product.search_placeholder') }}"
                        class="form-input pl-9 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300"
                    >
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>

                <!-- Add product button -->
                <a href="{{ route('products.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="hidden xs:block ml-2">{{ __('product.actions.create') }}</span>
                </a>
            </div>
        </div>

        <!-- Table -->
        <x-table
            :headers="[
                ['text' => 'Image', 'align' => 'left'],
                ['text' => __('product.fields.name'), 'align' => 'left'],
                ['text' => __('product.fields.variants'), 'align' => 'left'],
                ['text' => __('product.fields.factory_price'), 'align' => 'right'],
                ['text' => __('product.fields.distributor_price'), 'align' => 'right'],
                ['text' => __('product.fields.reseller_price'), 'align' => 'right'],
                ['text' => __('common.actions_column'), 'align' => 'left']
            ]"
            :title="__('product.all_products')"
            :meta="$products->total()"
        >
            @forelse ($products as $product)
                <tr>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <img src="{{ $product->image_url }}" alt="Product Image" class="h-12 w-12 object-cover rounded" />
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $product->name }}</div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div x-data="{ open: false }">
                            <span
                                @click="open = true"
                                class="text-gray-800 dark:text-gray-100 cursor-pointer underline decoration-dotted"
                            >
                                {{ $product->variants_count ?? 0 }} {{ __('product.variant_count') }}
                            </span>
                            <!-- Modal -->
                            <div
                                x-show="open"
                                x-transition.opacity
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                                style="display: none;"
                            >
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-lg w-full p-6 relative overflow-auto" style="max-height: 80vh;">
                                    <button @click="open = false" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl cursor-pointer">&times;</button>
                                    <h3 class="text-lg font-semibold mb-4">{{ __('product.variant_details') }}</h3>
                                    @if($product->variants_count)
                                        <ul>
                                            @foreach($product->variants as $variant)
                                                <li class="mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                                    <strong>SKU:</strong> {{ $variant->sku }}<br>
                                                    <strong>Stock:</strong> {{ $variant->stock }}<br>
                                                    <strong>Price:</strong> {{ number_format($variant->retail_price) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-gray-500">{{ __('product.no_variants') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="text-gray-800 dark:text-gray-100 text-right">
                            {{ number_format($product->factory_price) }}
                        </div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="text-gray-800 dark:text-gray-100 text-right">
                            {{ number_format($product->distributor_price) }}
                        </div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="text-gray-800 dark:text-gray-100 text-right">
                            {{ number_format($product->reseller_price) }}
                        </div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap w-px">
                        <div class="space-x-1">
                            <a href="{{ route('products.edit', $product) }}" class="text-violet-500 hover:text-violet-600">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button
                                type="button"
                                x-on:click="$dispatch('open-delete-modal', {
                                    itemId: {{ $product->id }},
                                    itemName: '{{ $product->name }}',
                                    deleteRoute: '{{ route('products.destroy', $product->id) }}'
                                })"
                                class="text-red-500 hover:text-red-600 cursor-pointer">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <x-table.empty-row :message="__('product.no_products')" />
            @endforelse
        </x-table>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $products->links() }}
        </div>

        <!-- Delete Confirmation Modal Component -->
        <x-modal.delete-confirmation
            :title="__('product.delete_title')"
            :confirm-text="__('product.messages.delete_confirm')"
            :delete-text="__('product.actions.delete')"
            :cancel-text="__('common.actions.cancel')"
        >
            {{ __('product.delete_warning') }}
        </x-modal.delete-confirmation>
    </div>
</div>
