@php
    $editing = isset($productId) && $productId;
@endphp

<div>
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <strong>Validation Errors:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="space-y-8">
        <div class="space-y-8 pb-6 divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Basic Information -->
            <div class="py-8">
                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                        {{ __('product.sections.basic_info') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('product.sections.basic_info_description') }}
                    </p>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <!-- Supplier -->
                    <div class="sm:col-span-2">
                        <x-label for="supplier_id" :value="__('product.fields.supplier')" />
                        <div class="mt-1">
                            <select
                                id="supplier_id"
                                wire:model="supplier_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm"
                                required
                            >
                                <option value="">{{ __('product.select_supplier') }}</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                    </div>

                    <!-- Name -->
                    <div class="sm:col-span-12">
                        <x-label for="name" :value="__('product.fields.name')" />
                        <div class="mt-1">
                            <x-input
                                type="text"
                                id="name"
                                wire:model="name"
                                class="block w-full"
                                required
                            />
                        </div>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Image Upload -->
                    <div class="sm:col-span-6">
                        <x-label for="image" :value="__('product.fields.image') ?? 'Image'" />
                        <div class="mt-1 flex items-center space-x-4">
                            <input type="file" id="image" wire:model="image" class="hidden" />
                            <label for="image" class="cursor-pointer">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="h-24 w-24 object-cover rounded border-2 border-dashed border-gray-300 hover:border-violet-500 transition" alt="Preview" />
                                @elseif (isset($product) && $product->image)
                                    <img src="{{ $product->image_url }}" class="h-24 w-24 object-cover rounded border-2 border-dashed border-gray-300 hover:border-violet-500 transition" alt="Current" />
                                @else
                                    <img src="{{ asset('images/placeholder.png') }}" class="h-24 w-24 object-cover rounded border-2 border-dashed border-gray-300 hover:border-violet-500 transition" alt="Placeholder" />
                                @endif
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('image')" class="mt-2" />
                    </div>

                    <!-- Price Group -->
                    <div class="sm:col-span-12">
                        <h4 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-4">
                            {{ __('product.sections.price_group') }}
                        </h4>
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-3">
                            <!-- Factory Price -->
                            <div>
                                <x-label for="factory_price" :value="__('product.fields.factory_price')" />
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-700 dark:text-gray-200 sm:text-sm z-10">Rp</span>
                                    </div>
                                    <x-input
                                        type="number"
                                        step="0.01"
                                        id="factory_price"
                                        wire:model.live="factory_price"
                                        class="block w-full pl-10 z-0"
                                        required
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('factory_price')" class="mt-2" />
                            </div>

                            <!-- Distributor Price -->
                            <div>
                                <x-label for="distributor_price" :value="__('product.fields.distributor_price')" />
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-700 dark:text-gray-200 sm:text-sm z-10">Rp</span>
                                    </div>
                                    <x-input
                                        type="number"
                                        step="0.01"
                                        id="distributor_price"
                                        wire:model="distributor_price"
                                        class="block w-full pl-10 z-0"
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('distributor_price')" class="mt-2" />
                            </div>

                            <!-- Reseller Price -->
                            <div>
                                <x-label for="reseller_price" :value="__('product.fields.reseller_price')" />
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-700 dark:text-gray-200 sm:text-sm z-10">Rp</span>
                                    </div>
                                    <x-input
                                        type="number"
                                        step="0.01"
                                        id="reseller_price"
                                        wire:model="reseller_price"
                                        class="block w-full pl-10 z-0"
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('reseller_price')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attributes and Variants -->
            <div class="pt-0">
                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                        {{ __('product.sections.attributes') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('product.sections.attributes_description') }}
                    </p>
                </div>

                <div class="mt-6">
                    <script>
                    function attributeManager({ backendAttributes = [], backendVariants = [] } = {}) {
                        return {
                            attributes: [],
                            variants: [],
                            newAttributeKey: '',
                            newAttributeValues: '',
                            editIndex: null,
                            editName: '',
                            editValues: '',
                            bulkPrice: '',
                            bulkStock: '',
                            bulkSku: '',
                            bulkSkuIsPrefix: true,
                            attributeError: '',
                            addAttribute() {
                                if (this.attributes.length >= 2) {
                                    this.attributeError = 'Maximum 2 attributes allowed.';
                                    this.newAttributeKey = '';
                                    this.newAttributeValues = '';
                                    setTimeout(() => { this.attributeError = ''; }, 3000);
                                    return;
                                }
                                if (!this.newAttributeKey || !this.newAttributeValues) return;
                                this.attributes.push({
                                    name: String(this.newAttributeKey),
                                    values: String(this.newAttributeValues)
                                        .split(',')
                                        .map(v => v.trim())
                                        .filter(Boolean)
                                });
                                this.newAttributeKey = '';
                                this.newAttributeValues = '';
                                this.attributeError = '';
                                this.saveToStorage();
                                this.generateVariants();
                            },
                            startEdit(index) {
                                this.editIndex = index;
                                this.editName = this.attributes[index].name;
                                this.editValues = this.attributes[index].values.map(v => typeof v === 'object' ? v.value : v).join(', ');
                            },
                            updateAttribute() {
                                if (this.editIndex === null) return;
                                this.attributes[this.editIndex] = {
                                    name: String(this.editName),
                                    values: String(this.editValues)
                                        .split(',')
                                        .map(v => v.trim())
                                        .filter(Boolean)
                                };
                                this.editIndex = null;
                                this.editName = '';
                                this.editValues = '';
                                this.saveToStorage();
                                this.generateVariants();
                            },
                            cancelEdit() {
                                this.editIndex = null;
                                this.editName = '';
                                this.editValues = '';
                            },
                            deleteAttribute(index) {
                                this.attributes.splice(index, 1);
                                this.saveToStorage();
                                this.generateVariants();
                            },
                            saveToStorage() {
                                localStorage.setItem('productAttributes', JSON.stringify(this.attributes));
                            },
                            clearStorage() {
                                localStorage.removeItem('productAttributes');
                            },
                            generateVariants() {
                                if (!this.attributes.length) {
                                    this.variants = [];
                                    return;
                                }
                                // Ensure all values are strings
                                let valueArrays = this.attributes.map(attr =>
                                    attr.values.map(v => typeof v === 'object' ? v.value : v)
                                );
                                if (valueArrays.some(arr => !arr.length)) {
                                    this.variants = [];
                                    return;
                                }
                                let combos = this.cartesian(valueArrays);
                                let backendVariants = JSON.parse(localStorage.getItem('productVariants') || '[]');
                                this.variants = combos.map((combo, idx) => {
                                    let keyBase = combo.join('|');
                                    let key = keyBase + '|' + idx;
                                    // Match by values array
                                    let prev = backendVariants.find(v => v.values && v.values.join('|') === keyBase);
                                    return {
                                        key,
                                        values: combo,
                                        sku: prev ? prev.sku : '',
                                        stock: prev ? prev.stock : '',
                                        retail_price: prev ? prev.retail_price : ''
                                    };
                                });
                            },
                            cartesian(arr) {
                                return arr.reduce(function(a, b) {
                                    return a.flatMap(d => b.map(e => d.concat([e])));
                                }, [[]]);
                            },
                            init() {
                                this.generateVariants();
                                // Always sync backend data to localStorage on init
                                localStorage.setItem('productAttributes', JSON.stringify(backendAttributes));
                                localStorage.setItem('productVariants', JSON.stringify(backendVariants));

                                this.attributes = backendAttributes;
                                this.variants = backendVariants;
                            },
                            applyBulkPrice() {
                                this.variants.forEach(v => v.retail_price = this.bulkPrice);
                            },
                            applyBulkStock() {
                                this.variants.forEach(v => v.stock = this.bulkStock);
                            },
                            applyBulkSku() {
                                this.variants.forEach(v => {
                                    if (this.bulkSkuIsPrefix) {
                                        v.sku = (this.bulkSku ? this.bulkSku + '-' : '') + v.values.map(val => String(val).toLowerCase().replace(/\s+/g, '-')).join('-');
                                    } else {
                                        v.sku = this.bulkSku;
                                    }
                                });
                            },
                            save() {
                                const plainVariants = JSON.parse(JSON.stringify(this.variants ?? []));
                                const plainAttributes = JSON.parse(JSON.stringify(this.attributes ?? []));

                                Promise.all([
                                    $set('variants', plainVariants),
                                    $set('productAttributes', plainAttributes)
                                ]).then(() => {
                                    $call('save');
                                });
                            }
                        }
                    }
                    </script>
                    <!-- Attribute Management (Original Design, Alpine.js Logic) -->
                    <div x-data="attributeManager(JSON.parse(document.getElementById('product-form-data').textContent))">
                        <!-- Add New Attribute -->
                        <div class="mb-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <h4 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-4">
                                {{ __('product.sections.add_attribute') }}
                            </h4>
                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-2">
                                    <x-label for="newAttributeKey" :value="__('product.fields.attribute_name')" />
                                    <div class="mt-1">
                                        <input
                                            type="text"
                                            id="newAttributeKey"
                                            x-model="newAttributeKey"
                                            class="block w-full border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md h-10"
                                            placeholder="{{ __('product.placeholders.attribute_name') }}"
                                        />
                                    </div>
                                </div>
                                <div class="sm:col-span-4">
                                    <x-label for="newAttributeValues" :value="__('product.fields.attribute_values')" />
                                    <div class="mt-1">
                                        <input
                                            type="text"
                                            id="newAttributeValues"
                                            x-model="newAttributeValues"
                                            class="block w-full border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md h-10"
                                            placeholder="{{ __('product.placeholders.attribute_values') }}"
                                        />
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('product.messages.attribute_values_help') }}
                                    </p>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="pt-0">
                                        <x-button type="button" @click="addAttribute">
                                            {{ __('product.actions.add_attribute') }}
                                        </x-button>
                                    </div>
                                </div>
                            </div>
                            <template x-if="attributeError">
                                <div class="text-red-500 text-sm mt-2" x-text="attributeError"></div>
                            </template>
                            @if ($errors->has('variants') && count($variants) === 0)
                                <div class="text-red-500 text-sm mt-2">
                                    {{ $errors->first('variants') }}
                                </div>
                            @endif
                        </div>

                        <!-- Attribute List -->
                        <div class="mb-4">
                            <h5 class="font-semibold mb-2">{{ __('product.sections.attributes') }}</h5>
                            <ul>
                                <template x-for="(attribute, index) in attributes" :key="index">
                                    <li class="mb-2 flex items-center justify-between">
                                        <div>
                                            <span class="font-medium" x-text="attribute.name"></span>:
                                            <span x-text="attribute.values.map(v => typeof v === 'object' ? v.value : v).join(', ')"></span>
                                        </div>
                                        <div>
                                            <button type="button" @click="startEdit(index)" class="text-violet-500 hover:text-violet-600 mr-2">Edit</button>
                                            <button type="button" @click="deleteAttribute(index)" class="text-red-500 hover:text-red-600">Delete</button>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Edit Attribute Modal (inline, simple) -->
                        <div x-show="editIndex !== null" x-cloak class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-30">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow-lg w-full max-w-md">
                                <h4 class="text-lg font-semibold mb-4">{{ __('product.actions.edit') }} {{ __('product.fields.attribute_name') }}</h4>
                                <div class="mb-4">
                                    <x-label :value="__('product.fields.attribute_name')" />
                                    <input type="text" x-model="editName" class="block w-full border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md" />
                                </div>
                                <div class="mb-4">
                                    <x-label :value="__('product.fields.attribute_values')" />
                                    <input type="text" x-model="editValues" class="block w-full border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md" />
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('product.messages.attribute_values_help') }}</p>
                                </div>
                                <div class="flex justify-end">
                                    <x-button type="button" @click="updateAttribute" class="mr-2">{{ __('common.actions.save') }}</x-button>
                                    <x-button type="button" @click="cancelEdit" class="bg-gray-300 dark:bg-gray-600">{{ __('common.actions.cancel') }}</x-button>
                                </div>
                            </div>
                        </div>

                        <!-- Variants Table (Alpine.js) -->
                        <template x-if="variants.length > 0">
                            <div class="mt-8">
                                <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('product.sections.variants') }}
                                </h4>
                                <!-- Bulk Update Section -->
                                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ __('product.fields.bulk_price') }}
                                        </label>
                                        <div class="mt-1 relative rounded-md shadow-sm flex">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-700 dark:text-gray-200 sm:text-sm z-10">Rp</span>
                                            </div>
                                            <input type="number" min="0" x-model="bulkPrice" class="block w-full pl-10 z-0 border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md h-10" />
                                            <x-button type="button" @click="applyBulkPrice" class="ml-2 bg-black text-white">{{ __('common.actions.apply') }}</x-button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ __('product.fields.bulk_stock') }}
                                        </label>
                                        <div class="mt-1 relative rounded-md shadow-sm flex">
                                            <input type="number" min="0" x-model="bulkStock" class="block w-full border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md h-10" />
                                            <x-button type="button" @click="applyBulkStock" class="ml-2 bg-black text-white">{{ __('common.actions.apply') }}</x-button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ __('product.fields.bulk_sku_prefix') }}
                                        </label>
                                        <div class="mt-1 relative rounded-md shadow-sm flex items-center">
                                            <input type="text" x-model="bulkSku" class="block w-full border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md h-10" />
                                            <label class="ml-2 flex items-center space-x-1 text-xs">
                                                <input type="checkbox" x-model="bulkSkuIsPrefix" class="form-checkbox" />
                                                <span>Prefix</span>
                                            </label>
                                            <x-button type="button" @click="applyBulkSku" class="ml-2 bg-black text-white">{{ __('common.actions.apply') }}</x-button>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <template x-for="attr in attributes" :key="attr.name">
                                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="attr.name"></th>
                                                </template>
                                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('product.fields.price') }}</th>
                                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('product.fields.stock') }}</th>
                                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('product.fields.sku') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                            <template x-for="(variant, vIdx) in variants" :key="variant.key">
                                                <tr>
                                                    <template x-for="val in variant.values" :key="val">
                                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400" x-text="val"></td>
                                                    </template>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                        <input type="number" min="0" x-model.number="variant.retail_price" class="block w-full border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md h-10" placeholder="Rp" required />
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                        <input type="number" min="0" x-model.number="variant.stock" class="block w-full border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md h-10" required />
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                        <input type="text" x-model="variant.sku" class="block w-full border-gray-300 dark:bg-gray-900 dark:text-gray-100 rounded-md h-10" required />
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>

                        <!-- Place the button group here, after the variants table/template -->
                        <div class="pt-5">
                            <div class="flex justify-end">
                                <x-button type="button" onclick="window.history.back()" class="bg-gray-600 hover:bg-gray-700 mr-3 cursor-pointer">
                                    {{ __('common.actions.back') }}
                                </x-button>
                                <x-button
                                    type="button"
                                    class="cursor-pointer"
                                    @click="save()"
                                >
                                    {{ __('common.actions.save') }}
                                </x-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Warn on unsaved changes
    window.addEventListener('beforeunload', function (e) {
        if (localStorage.getItem('productAttributes')) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. If you leave, your data will be lost.';
        }
    });
</script>
@endpush

<script type="application/json" id="product-form-data">
    {!! json_encode([
        'backendAttributes' => $editing ? $productAttributes : [],
        'backendVariants' => $editing ? $variants : [],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
