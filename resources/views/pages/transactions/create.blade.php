<x-app-layout>
    <script>
    function transactionItems() {
        return {
            items: [],
            init() {

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

    function transactionForm() {
        return {
            pdfUrl: null,
            shipments: [], // array of parsed shipments
            products: @json($products),
            resellers: @json($resellers),
            selectedReseller: null,
            resellerSearch: '',
            // Extract only the product block from description
            getProductBlock(desc) {
                if (!desc) return '';
                const start = desc.indexOf('Nama Produk');
                if (start === -1) return desc;
                // Find end: next 'Variasi', 'Qty', or 10 lines after
                let lines = desc.substring(start).split(/\r?\n/);
                let endIdx = lines.length;
                for (let i = 1; i < lines.length; i++) {
                    if (/^Variasi/i.test(lines[i]) || /^Qty/i.test(lines[i])) {
                        endIdx = i + 2; // include 1-2 lines after
                        break;
                    }
                }
                return lines.slice(0, endIdx).join('\n');
            },
            // Variant dropdown helpers
            getVariantOptions(item, idx, sIdx) {
                if (!this.shipments || !this.shipments[sIdx] || !this.shipments[sIdx].items) return [];
                // Return all variants not already selected in this shipment
                let selectedIds = [];
                this.shipments[sIdx].items.forEach((it, i) => {
                    if (i !== idx && it.variant_id) selectedIds.push(String(it.variant_id));
                });
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
                // Filter by search
                if (item && item.search) {
                    return options.filter(opt =>
                        opt.sku.toLowerCase().includes(item.search.toLowerCase()) ||
                        opt.name.toLowerCase().includes(item.search.toLowerCase())
                    );
                }
                return options;
            },
            selectVariant(option, idx, sIdx) {
                const shipment = this.shipments[sIdx];
                const item = shipment.items[idx];
                item.variant_id = String(option.id);
                item.search = option.sku + ' - ' + option.name + (option.attributes ? ' (' + option.attributes + ')' : '');
                item.dropdownOpen = false;
                item.image_url = option.image_url;
            },
            handlePdfUpload(event) {
                const file = event.target.files[0];
                if (file) {
                    this.pdfUrl = URL.createObjectURL(file);
                    const formData = new FormData();
                    formData.append('shippingPdf', file);
                    fetch('{{ route('transactions.parse-pdf') }}', {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        // data is array of shipments
                        this.shipments = data.map(shipment => {
                            // For each item, try to auto-match variant
                            const items = (shipment.items || []).map(item => {
                                let matched = null;
                                this.products.forEach(product => {
                                    product.variants.forEach(variant => {
                                        if (
                                            variant.sku && variant.sku.toLowerCase() === item.sku.toLowerCase() &&
                                            (!item.variation || (variant.attributeValues && variant.attributeValues.map(a => a.value).join(',').toLowerCase().includes(item.variation.toLowerCase())))
                                        ) {
                                            matched = {
                                                variant_id: variant.id,
                                                search: variant.sku + ' - ' + product.name + (variant.attributeValues && variant.attributeValues.length ? ' (' + variant.attributeValues.map(a => a.value).join(', ') + ')' : ''),
                                                image_url: product.image_url,
                                                quantity: item.qty || 1,
                                                dropdownOpen: false
                                            };
                                        }
                                    });
                                });
                                return {
                                    ...item,
                                    variant_id: matched ? matched.variant_id : '',
                                    search: matched ? matched.search : '',
                                    image_url: matched ? matched.image_url : '',
                                    quantity: item.qty || 1,
                                    dropdownOpen: false
                                };
                            });
                            return {
                                ...shipment,
                                items: items
                            };
                        });
                        // Auto-select reseller if all shipments have the same reseller
                        if (data.length && data[0].reseller) {
                            this.selectedReseller = data[0].reseller.id;
                            this.resellerSearch = data[0].reseller.name;
                        }
                    });
                }
            },
            // For form submission
            submitForm() {
                // You can customize this to send all shipments/items as needed
                // For now, just submit as JSON
                const payload = {
                    shipments: this.shipments,
                    reseller_id: this.selectedReseller
                };
                // You can use fetch/ajax or set hidden input and submit
                // For demo, just log
                console.log(payload);
            }
        }
    }
    </script>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-7xl mx-auto">
        <div class="flex flex-col lg:flex-row gap-8" x-data="transactionForm()">
            <!-- Left: Form -->
            <div class="w-full lg:w-3/4">
                <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow p-8">
                    <!-- Page header -->
                    <div class="mb-8">
                        <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                            {{ __('common.transaction.add_manual') }}
                        </h1>
                    </div>
                    <!-- Form -->
                    <form @submit.prevent="submitForm" class="space-y-6" enctype="multipart/form-data">
                        @csrf
                        <!-- PDF Upload -->
                        <div class="mb-6">
                            <label for="shippingPdf" class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ __('common.transaction.shipping_pdf') }} <span class="text-red-500">*</span></label>
                            <div class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition cursor-pointer" @click="$refs.pdfInput.click()">
                                <input type="file" id="shippingPdf" name="shippingPdf" accept="application/pdf" class="hidden" @change="handlePdfUpload" x-ref="pdfInput">
                                <div class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold flex items-center gap-2">
                                    <i class="fa-solid fa-file-arrow-up text-lg"></i>
                                    <span x-text="pdfUrl ? '{{ __('common.transaction.change_pdf') }}' : '{{ __('common.transaction.select_pdf_file') }}'"></span>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500 mt-2">{{ __('common.transaction.pdf_only_max') }}</span>
                            </div>
                        </div>
                        <!-- Reseller Selection (for distributor/superadmin) -->
                        @if(is_distributor_or_admin(auth()->user()))
                        <div class="relative" x-data="{
                            open: false,
                            search: $root.resellerSearch || '',
                            get resellers() { return @js($resellers); },
                            get filtered() {
                                if (!this.search) return this.resellers;
                                return this.resellers.filter(r =>
                                    r.name.toLowerCase().includes(this.search.toLowerCase()) ||
                                    r.email.toLowerCase().includes(this.search.toLowerCase())
                                );
                            },
                            select(reseller) {
                                $root.selectedReseller = reseller.id;
                                this.search = reseller.name;
                                this.open = false;
                            },
                            selectedName() {
                                const found = this.resellers.find(r => r.id == $root.selectedReseller);
                                return found ? found.name : '';
                            }
                        }" x-init="$watch('$root.selectedReseller', value => {
                            if (value) {
                                this.search = this.selectedName();
                            }
                        })">
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300" for="user_id_search">
                                {{ __('common.select_reseller') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="user_id_search"
                                type="text"
                                class="form-input w-full cursor-pointer dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                                placeholder="{{ __('common.select_reseller') }}"
                                x-model="search"
                                @focus="setTimeout(() => open = true, 50)"
                                @click="setTimeout(() => open = true, 50)"
                                :value="selectedName()"
                                autocomplete="off"
                                required
                            />
                            <input type="hidden" name="user_id" :value="$root.selectedReseller">
                            <div x-show="open" @click.away="open = false" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-auto">
                                <template x-for="reseller in filtered" :key="reseller.id">
                                    <div class="px-4 py-2 cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-900/50 flex items-center gap-3" @click="select(reseller)">
                                        <img :src="reseller.profile_photo_url" alt="" class="w-8 h-8 rounded-full object-cover">
                                        <div class="flex flex-col">
                                            <span x-text="reseller.name" class="font-medium dark:text-gray-200"></span>
                                            <span x-text="reseller.email" class="text-sm text-gray-500 dark:text-gray-400"></span>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="filtered.length === 0" class="px-4 py-2 text-gray-400 dark:text-gray-500 text-sm">No results</div>
                            </div>
                            @error('user_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                        <!-- Table of shipments -->
                        <div x-show="shipments.length" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2">{{ __('common.transaction.items') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(shipment, sIdx) in shipments" :key="shipment.shipping_number">
                                        <tr>
                                            <td class="py-2 align-top">
                                                <!-- Shipping Information -->
                                                <div class="mb-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="shipment.shipping_number"></p>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block" x-text="getProductBlock(shipment.description)"></span>
                                                </div>
                                                <div class="space-y-3">
                                                    <template x-for="(item, idx) in shipment.items || []" :key="idx">
                                                        <div class="relative border p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                            <div class="flex justify-between items-start mb-2">
                                                                <span class="text-xs text-gray-500" x-text="'Item ' + (idx + 1)"></span>
                                                                <button type="button" @click.prevent.stop="shipment.items.splice(idx, 1)" class="text-red-500 hover:text-red-600">
                                                                    <i class="fa-solid fa-trash-alt"></i>
                                                                </button>
                                                            </div>
                                                            <div class="flex flex-row gap-3 w-full">
                                                                <div class="relative flex items-center gap-2 w-3/4 mt-5">
                                                                    <img :src="item.image_url" alt="" class="w-10 h-10 object-cover rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700" x-show="item.variant_id && item.image_url">
                                                                    <input
                                                                        type="text"
                                                                        class="form-input w-full cursor-pointer dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                                                                        placeholder="{{ __('common.transaction.select_product') }}"
                                                                        x-model="item.search"
                                                                        @focus="setTimeout(() => item.dropdownOpen = true, 50)"
                                                                        @input="setTimeout(() => item.dropdownOpen = true, 50)"
                                                                        :readonly="!!item.variant_id"
                                                                        autocomplete="off"
                                                                    />
                                                                    <button type="button" x-show="item.variant_id" @click.prevent.stop="item.variant_id = ''; item.search = ''; item.image_url = ''; item.dropdownOpen = false;" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                                                                        <i class="fa-solid fa-xmark"></i>
                                                                    </button>
                                                                </div>
                                                                <div class="w-1/4">
                                                                    <label class="block text-xs text-gray-500 mb-1">Quantity</label>
                                                                    <input
                                                                        type="number"
                                                                        class="form-input w-full text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                                                                        x-model="item.quantity"
                                                                        min="1"
                                                                    />
                                                                </div>
                                                                <input type="hidden" :name="'shipments['+sIdx+'][items]['+idx+'][variant_id]'" :value="item.variant_id" />
                                                                <input type="hidden" :name="'shipments['+sIdx+'][items]['+idx+'][quantity]'" :value="item.quantity" />
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <!-- Add Item Button -->
                                                    <div class="mt-4">
                                                        <button
                                                            type="button"
                                                            @click.prevent="if (!shipment.items) shipment.items = []; shipment.items.push({ variant_id: '', quantity: 1, search: '', dropdownOpen: false })"
                                                            class="w-full py-2 px-3 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 hover:border-indigo-500 dark:hover:border-indigo-500 hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors flex items-center justify-center gap-2"
                                                        >
                                                            <i class="fa-solid fa-plus"></i>
                                                            <span>Add Product</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <!-- Form actions -->
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-lg cursor-pointer">
                                {{ __('common.actions.cancel') }}
                            </a>
                            <button type="submit" class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-lg cursor-pointer">
                                {{ __('common.actions.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Right: PDF Preview -->
            <div class="w-full lg:w-1/4 flex items-start justify-center">
                <template x-if="pdfUrl">
                    <div class="w-full bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                        <embed :src="pdfUrl" type="application/pdf" class="rounded border border-gray-200 dark:border-gray-700" width="100%" height="600px" />
                    </div>
                </template>
                <template x-if="!pdfUrl">
                    <div class="w-full flex items-center justify-center h-[600px] bg-gray-50 dark:bg-gray-700 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg text-gray-400 dark:text-gray-500">
                        <span>{{ __('common.transaction.no_pdf_selected') }}</span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
