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
            shippingNumber: '',
            description: '',
            items: [],
            handlePdfUpload(event) {
                const file = event.target.files[0];
                if (file) {
                    this.pdfUrl = URL.createObjectURL(file);
                    // Send to backend for parsing
                    const formData = new FormData();
                    formData.append('shippingPdf', file);
                    fetch('{{ route('transactions.parse-pdf') }}', {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.shippingNumber = data.shipping_number || '';
                        this.description = data.description || '';
                        this.items = data.items || [];
                    });
                }
            }
        }
    }
    </script>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-7xl mx-auto">
        <div class="flex flex-col lg:flex-row gap-8" x-data="transactionForm()">
            <!-- Left: Form -->
            <div class="w-full lg:w-1/2">
                <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow p-8">
                    <!-- Page header -->
                    <div class="mb-8">
                        <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                            {{ __('common.transaction.add_manual') }}
                        </h1>
                    </div>
                    <!-- Form -->
                    <form action="{{ route('transactions.store') }}" method="POST" class="space-y-6" enctype="multipart/form-data">
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
                        <div x-data="{
                            open: false,
                            search: '',
                            selected: {{ old('user_id') ? old('user_id') : 'null' }},
                            resellers: @js($resellers),
                            get filtered() {
                                if (!this.search) return this.resellers;
                                return this.resellers.filter(r =>
                                    r.name.toLowerCase().includes(this.search.toLowerCase()) ||
                                    r.email.toLowerCase().includes(this.search.toLowerCase())
                                );
                            },
                            select(reseller) {
                                this.selected = reseller.id;
                                this.search = reseller.name;
                                this.open = false;
                            },
                            selectedName() {
                                const found = this.resellers.find(r => r.id == this.selected);
                                return found ? found.name : '';
                            }
                        }" class="relative">
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
                            <input type="hidden" name="user_id" :value="selected">
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
                        <!-- Shipping Number -->
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300" for="shipping_number">
                                {{ __('common.transaction.shipping_number') }} <span class="text-red-500">*</span>
                            </label>
                            <input id="shipping_number" name="shipping_number" class="form-input w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" type="text" x-model="shippingNumber" required />
                            @error('shipping_number')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300" for="description">
                                {{ __('common.transaction.description') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea id="description" name="description" class="form-textarea w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" rows="4" x-model="description" required></textarea>
                            @error('description')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Items -->
                        <div x-data="transactionItems()" x-init="init()">
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                                {{ __('common.transaction.items') }} <span class="text-red-500">*</span>
                            </label>

                            <template x-for="(item, index) in items" :key="index">
                                <div class="flex items-center gap-3 p-3 mb-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false">
                                    <div class="flex-1">
                                        <div class="relative flex items-center gap-2">
                                            <img :src="item.image_url" alt="" class="w-10 h-10 object-cover rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700" x-show="item.variant_id && item.image_url">
                                            <input
                                                type="text"
                                                class="form-input w-full cursor-pointer dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                                                placeholder="{{ __('common.transaction.select_product') }}"
                                                x-model="item.search"
                                                @focus="dropdownOpen = true"
                                                @input="dropdownOpen = true"
                                                readonly="false"
                                                :readonly="!!item.variant_id"
                                            />
                                            <button type="button" x-show="item.variant_id" @click.prevent.stop="clearVariant(index)" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                            <div
                                                x-show="dropdownOpen && !item.variant_id"
                                                class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-auto"
                                            >
                                                <template x-for="option in filterOptions(index)" :key="option.id">
                                                    <div
                                                        class="px-4 py-2 cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-900/50 flex items-center gap-3"
                                                        @click="selectVariant(index, option)"
                                                    >
                                                        <img :src="option.image_url" alt="" class="w-10 h-10 object-cover rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700" x-show="option.image_url">
                                                        <div class="flex flex-col">
                                                            <span class="font-medium dark:text-gray-200" x-text="option.sku"></span>
                                                            <span class="text-gray-400 dark:text-gray-500 text-sm" x-text="option.name"></span>
                                                            <span class="text-gray-500 dark:text-gray-400 text-xs" x-text="option.attributes"></span>
                                                        </div>
                                                    </div>
                                                </template>
                                                <div x-show="filterOptions(index).length === 0" class="px-4 py-2 text-gray-400 dark:text-gray-500 text-sm">No results</div>
                                            </div>
                                        </div>
                                        <input type="hidden" :name="'items[' + index + '][variant_id]'" :value="item.variant_id" />
                                    </div>
                                    <div class="w-32">
                                        <input type="number" :name="'items[' + index + '][quantity]'" class="form-input w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="Qty" min="1" required x-model="items[index].quantity" value="1"
                                            @keydown="
                                                if(['e','E','-','+','.'].includes($event.key)) $event.preventDefault();
                                            "
                                            @input="
                                                if ($event.target.value < 1) $event.target.value = 1;
                                                items[index].quantity = $event.target.value;
                                            "
                                        />
                                    </div>
                                    <button type="button" @click="removeItem(index)" class="p-2 bg-red-500 hover:bg-red-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-lg cursor-pointer">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </template>

                            <button type="button" @click="addItem()" class="mt-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-lg font-semibold flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-plus"></i>
                                <span>{{ __('common.transaction.add_item') }}</span>
                            </button>

                            @error('items')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
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
            <div class="w-full lg:w-1/2 flex items-start justify-center">
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
