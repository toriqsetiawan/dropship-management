<x-app-layout>
    <script>
    function transactionForm() {
        return {
            pdfUrl: null,
            shipments: [], // array of parsed shipments
            products: @json($products),
            resellers: @json($resellers),
            selectedReseller: null,
            resellerSearch: '',
            errors: {},
            successMessage: '',
            showSuccessAlert: false,
            loading: false,
            shippingPdfPath: null,
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
                        // Store the PDF path
                        this.shippingPdfPath = data.pdf_path;
                        // data is array of shipments
                        this.shipments = data.shipments.map(shipment => {
                            // For each item, gunakan data dari backend jika variant_id sudah ada
                            const items = (shipment.items || []).map(item => {
                                let search = '';
                                if (item.variant_id) {
                                    // Cari product dan variant dari this.products
                                    let found = null;
                                    this.products.forEach(product => {
                                        product.variants.forEach(variant => {
                                            if (variant.id == item.variant_id) {
                                                found = {
                                                    sku: variant.sku,
                                                    name: product.name,
                                                    attributes: (variant.attributeValues || []).map(a => a.value).join(', '),
                                                };
                                            }
                                        });
                                    });
                                    if (found) {
                                        search = found.sku + ' - ' + found.name + (found.attributes ? ' (' + found.attributes + ')' : '');
                                    }
                                }
                                return {
                                    ...item,
                                    search: search,
                                    quantity: item.qty || item.quantity || 1,
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
            async submitForm() {
                this.errors = {};
                this.successMessage = '';
                this.loading = true;
                try {
                    const payload = {
                        shipments: this.shipments,
                        user_id: this.selectedReseller,
                        shipping_pdf_path: this.shippingPdfPath
                    };
                    console.log('Submitting payload:', payload);
                    const res = await fetch('{{ route('transactions.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        if (data.errors) {
                            this.errors = data.errors;
                            console.log('Validation errors:', data.errors);
                        } else {
                            alert('Terjadi error');
                        }
                    } else {
                        this.successMessage = data.message || 'Data berhasil disimpan!';
                        this.showSuccessAlert = true;
                        setTimeout(() => {
                            this.showSuccessAlert = false;
                            window.location.href = '{{ route('transactions.index') }}';
                        }, 3000);
                    }
                } catch (e) {
                    console.error('Error:', e);
                    alert('Terjadi error jaringan');
                }
                this.loading = false;
            }
        }
    }
    </script>
    <div x-data="transactionForm()">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-7xl mx-auto">
            <!-- Modal Success Popup inline -->
            <div
                x-show="showSuccessAlert"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
                style="display: none;"
            >
                <div class="bg-green-100 border border-green-400 text-green-700 px-8 py-6 rounded-xl shadow-xl flex flex-col items-center relative min-w-[250px] max-w-xs">
                    <svg class="w-12 h-12 mb-2 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
                        <path stroke="currentColor" stroke-width="2" d="M9 12l2 2l4-4"/>
                    </svg>
                    <span class="block text-lg font-semibold mb-2" x-text="successMessage"></span>
                    <button type="button" @click="showSuccessAlert = false" class="absolute top-2 right-2 text-2xl leading-none text-gray-400 hover:text-gray-700">&times;</button>
                </div>
            </div>
            <div class="flex flex-col lg:flex-row gap-8 min-h-screen relative">
                <!-- Left: Form -->
                <div class="w-full lg:w-2/3 pb-8">
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
                            <div class="mb-6">
                                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300" for="user_id">
                                    {{ __('common.select_reseller') }} <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="user_id"
                                    name="user_id"
                                    x-model="selectedReseller"
                                    class="form-select w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                                    required
                                    @change="console.log('Selected reseller:', $event.target.value)"
                                >
                                    <option value="">{{ __('common.select_reseller') }}</option>
                                    <template x-for="reseller in resellers" :key="reseller.id">
                                        <option :value="reseller.id" x-text="reseller.name"></option>
                                    </template>
                                </select>
                                <div x-show="errors.user_id" class="text-red-500 text-sm mt-1" x-text="errors.user_id"></div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Selected value: <span x-text="selectedReseller"></span>
                                </div>
                            </div>
                            @endif
                            <!-- Table of shipments -->
                            <div x-show="shipments.length" class="overflow-x-auto">
                                <template x-for="(shipment, sIdx) in shipments" :key="shipment.shipping_number">
                                    <div class="py-2 align-top border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-3">
                                        <!-- Shipping Information -->
                                        <div class="mb-4">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="'#' + (sIdx + 1) + ' ' + shipment.shipping_number"></p>
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
                                                            <!-- DROPDOWN POPUP -->
                                                            <div
                                                                x-show="item.dropdownOpen && !item.variant_id"
                                                                @click.away="item.dropdownOpen = false"
                                                                class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg overflow-auto"
                                                                style="top: 100%; left: 0; max-height: 70vh;"
                                                            >
                                                                <template x-for="option in getVariantOptions(item, idx, sIdx)" :key="option.id">
                                                                    <div
                                                                        class="px-4 py-2 cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-900/50 flex items-center gap-3"
                                                                        @click="selectVariant(option, idx, sIdx)"
                                                                    >
                                                                        <img :src="option.image_url" alt="" class="w-10 h-10 object-cover rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700" x-show="option.image_url">
                                                                        <div class="flex flex-col">
                                                                            <span class="font-medium dark:text-gray-200" x-text="option.sku"></span>
                                                                            <span class="text-gray-400 dark:text-gray-500 text-sm" x-text="option.name"></span>
                                                                            <span class="text-gray-500 dark:text-gray-400 text-xs" x-text="option.attributes"></span>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                                <div x-show="getVariantOptions(item, idx, sIdx).length === 0" class="px-4 py-2 text-gray-400 dark:text-gray-500 text-sm">No results</div>
                                                            </div>
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
                                    </div>
                                </template>
                            </div>
                            <!-- Form actions -->
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-lg cursor-pointer">
                                    {{ __('common.actions.cancel') }}
                                </a>
                                <button type="submit"
                                    :disabled="loading"
                                    class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-lg cursor-pointer flex items-center gap-2"
                                >
                                    <svg x-show="loading" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    <span>{{ __('common.actions.save') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Right: PDF Preview -->
                <div class="w-full lg:w-1/3 lg:sticky lg:top-20 flex items-start justify-center self-start">
                    <template x-if="pdfUrl">
                        <div class="w-full bg-white dark:bg-gray-800 rounded-xl shadow p-4 h-screen">
                            <embed :src="pdfUrl" type="application/pdf" class="rounded border border-gray-200 dark:border-gray-700 w-full h-full" />
                        </div>
                    </template>
                    <template x-if="!pdfUrl">
                        <div class="w-full flex items-center justify-center h-screen bg-gray-50 dark:bg-gray-700 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg text-gray-400 dark:text-gray-500">
                            <span>{{ __('common.transaction.no_pdf_selected') }}</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
