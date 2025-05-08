<x-app-layout>
    <script>
    function transactionItems() {
        return {
            items: [],
            init() {
                this.$nextTick(() => {
                    this.items.forEach((_, index) => {
                        this.initSelect2(index);
                    });
                });
            },
            initSelect2(index) {
                const select = document.querySelector(`#variant-select-${index}`);
                if (select && !$(select).data('select2')) {
                    $(select).select2({
                        width: '100%',
                        placeholder: '{{ __('common.select_product') }}',
                        templateResult: function(data) {
                            if (!data.id) return data.text;
                            const sku = $(data.element).data('sku') || '';
                            const name = $(data.element).data('name') || '';
                            const attributes = data.text.split(' - ')[1] || '';
                            return $(
                                `<div><span class='font-medium'>${sku}</span> <span class='text-gray-400'>${name}</span><div class='text-sm text-gray-500'>${attributes}</div></div>`
                            );
                        },
                        templateSelection: function(data) {
                            if (!data.id) return data.text;
                            const sku = $(data.element).data('sku') || '';
                            const name = $(data.element).data('name') || '';
                            const attributes = data.text.split(' - ')[1] || '';
                            return $(
                                `<div><span class='font-medium'>${sku}</span> <span class='text-gray-400'>${name}</span><span class='text-gray-500 ml-2'>${attributes}</span></div>`
                            );
                        },
                        allowClear: false
                    }).on('select2:select select2:unselect', () => {
                        this.refreshAllSelect2();
                    });
                }
            },
            refreshAllSelect2() {
                this.items.forEach((item, idx) => {
                    const select = document.querySelector(`#variant-select-${idx}`);
                    if (select) {
                        $(select).find('option').each((_, opt) => {
                            const val = opt.value;
                            // Disable if selected in another item
                            const isSelectedElsewhere = this.items.some((it, i) => i !== idx && it.variant_id == val);
                            opt.disabled = isSelectedElsewhere;
                        });
                        // Refresh Select2 to reflect disabled state
                        $(select).trigger('change.select2');
                    }
                });
            },
            addItem() {
                const index = this.items.length;
                this.items.push({ variant_id: '', quantity: 1, search: '' });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            getVariantOptions(index) {
                // Return all variants not already selected in other items
                const selectedIds = this.items.filter((it, idx) => idx !== index).map(it => it.variant_id);
                let options = [];
                this.products.forEach(product => {
                    product.variants.forEach(variant => {
                        if (!selectedIds.includes(String(variant.id))) {
                            options.push({
                                id: variant.id,
                                sku: variant.sku,
                                name: product.name,
                                attributes: (variant.attributeValues || []).map(a => a.value).join(', '),
                                image_url: product.image_url,
                                product_id: product.id
                            });
                        }
                    });
                });
                return options;
            },
            filterOptions(index) {
                const search = this.items[index].search.toLowerCase();
                return this.getVariantOptions(index).filter(opt =>
                    opt.sku.toLowerCase().includes(search) ||
                    opt.name.toLowerCase().includes(search)
                );
            },
            selectVariant(index, option) {
                this.items[index].variant_id = String(option.id);
                this.items[index].search = option.sku + ' - ' + option.name + (option.attributes ? ' (' + option.attributes + ')' : '');
                this.items[index].dropdownOpen = false;
                this.items[index].image_url = option.image_url;
            },
            clearVariant(index) {
                this.items[index].variant_id = '';
                this.items[index].search = '';
                this.items[index].image_url = '';
            },
            products: @json($products),
        }
    }
    </script>
    <style>
        .cart-item-row {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px 0 rgba(16,30,54,0.06);
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .cart-item-row .form-select,
        .cart-item-row .form-input {
            min-height: 36px;
            font-size: 0.95rem;
        }
        .cart-item-row .form-select {
            background: #f9fafb;
        }
        .cart-item-row .form-input {
            background: #f9fafb;
        }
        .cart-item-row .btn {
            height: 36px;
            width: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
        }
        .add-item-btn {
            background: #6366f1;
            color: #fff;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.5rem 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px 0 rgba(16,30,54,0.06);
            transition: background 0.2s;
        }
        .add-item-btn:hover {
            background: #4f46e5;
        }
    </style>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="max-w-2xl mx-auto">
            <!-- Page header -->
            <div class="mb-8">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                    {{ __('common.transaction.add_manual') }}
                </h1>
            </div>

            <!-- Form -->
            <form action="{{ route('transactions.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Reseller Selection (for distributor/superadmin) -->
                @if($isDistributorOrSuperadmin)
                <div>
                    <label class="block text-sm font-medium mb-1" for="user_id">
                        {{ __('common.select_reseller') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="user_id" name="user_id" class="form-select w-full" required>
                        <option value="">{{ __('common.select_reseller') }}</option>
                        @foreach($resellers as $reseller)
                        <option value="{{ $reseller->id }}" {{ old('user_id') == $reseller->id ? 'selected' : '' }}>
                            {{ $reseller->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('user_id')
                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
                @endif

                <!-- Shipping Number -->
                <div>
                    <label class="block text-sm font-medium mb-1" for="shipping_number">
                        {{ __('common.transaction.shipping_number') }} <span class="text-red-500">*</span>
                    </label>
                    <input id="shipping_number" name="shipping_number" class="form-input w-full" type="text" value="{{ old('shipping_number') }}" required />
                    @error('shipping_number')
                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium mb-1" for="description">
                        {{ __('common.transaction.description') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea id="description" name="description" class="form-textarea w-full" rows="4" required>{{ old('description') }}</textarea>
                    @error('description')
                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Items -->
                <div x-data="transactionItems()" x-init="init()">
                    <label class="block text-sm font-medium mb-1">
                        {{ __('common.transaction.items') }} <span class="text-red-500">*</span>
                    </label>

                    <template x-for="(item, index) in items" :key="index">
                        <div class="cart-item-row" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false">
                            <div class="flex-1">
                                <div class="relative flex items-center gap-2">
                                    <img :src="item.image_url" alt="" class="w-10 h-10 object-cover rounded border border-gray-200 bg-white" x-show="item.variant_id && item.image_url">
                                    <input
                                        type="text"
                                        class="form-input w-full cursor-pointer"
                                        placeholder="{{ __('common.select_product') }}"
                                        x-model="item.search"
                                        @focus="dropdownOpen = true"
                                        @input="dropdownOpen = true"
                                        readonly="false"
                                        :readonly="!!item.variant_id"
                                    />
                                    <button type="button" x-show="item.variant_id" @click.prevent.stop="clearVariant(index)" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    <div
                                        x-show="dropdownOpen && !item.variant_id"
                                        class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto"
                                    >
                                        <template x-for="option in filterOptions(index)" :key="option.id">
                                            <div
                                                class="px-4 py-2 cursor-pointer hover:bg-indigo-100 flex items-center gap-3"
                                                @click="selectVariant(index, option)"
                                            >
                                                <img :src="option.image_url" alt="" class="w-10 h-10 object-cover rounded border border-gray-200 bg-white" x-show="option.image_url">
                                                <div class="flex flex-col">
                                                    <span class="font-medium" x-text="option.sku"></span>
                                                    <span class="text-gray-400 text-sm" x-text="option.name"></span>
                                                    <span class="text-gray-500 text-xs" x-text="option.attributes"></span>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="filterOptions(index).length === 0" class="px-4 py-2 text-gray-400 text-sm">No results</div>
                                    </div>
                                </div>
                                <input type="hidden" :name="'items[' + index + '][variant_id]'" :value="item.variant_id" />
                            </div>
                            <div class="w-32">
                                <input type="number" :name="'items[' + index + '][quantity]'" class="form-input w-full" placeholder="Qty" min="1" required x-model="items[index].quantity" value="1"
                                    @keydown="
                                        if(['e','E','-','+','.'].includes($event.key)) $event.preventDefault();
                                    "
                                    @input="
                                        if ($event.target.value < 1) $event.target.value = 1;
                                        items[index].quantity = $event.target.value;
                                    "
                                />
                            </div>
                            <button type="button" @click="removeItem(index)" class="btn bg-red-500 hover:bg-red-600 text-white cursor-pointer">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </template>

                    <button type="button" @click="addItem()" class="add-item-btn mt-2">
                        <i class="fa-solid fa-plus"></i>
                        <span>{{ __('common.add_item') }}</span>
                    </button>

                    @error('items')
                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Form actions -->
                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('transactions.index') }}" class="btn bg-gray-500 hover:bg-gray-600 text-white cursor-pointer">
                        {{ __('common.actions.cancel') }}
                    </a>
                    <button type="submit" class="btn bg-indigo-500 hover:bg-indigo-600 text-white cursor-pointer">
                        {{ __('common.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
