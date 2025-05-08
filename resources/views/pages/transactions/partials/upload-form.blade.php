<form method="POST" action="{{ route('shipping.upload.post') }}" enctype="multipart/form-data">
    @csrf
    @if(is_distributor_or_admin(auth()->user()))
        <div
            x-data="{
                open: false,
                search: '',
                selected: {{ old('selectedReseller') ? old('selectedReseller') : 'null' }},
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
                }
            }"
            x-init="if (selected && resellers.length) { const found = resellers.find(r => r.id == selected); if (found) search = found.name; }"
            class="relative mb-6"
        >
            <label class="block text-sm font-medium mb-1" for="user_id_search">
                {{ __('common.select_reseller') }} <span class="text-red-500">*</span>
            </label>
            <input
                id="user_id_search"
                type="text"
                class="form-input w-full cursor-pointer"
                placeholder="{{ __('common.select_reseller') }}"
                x-model="search"
                @focus="setTimeout(() => open = true, 50)"
                @click="setTimeout(() => open = true, 50)"
                autocomplete="off"
                required
            />
            <input type="hidden" name="selectedReseller" :value="selected">
            <div x-show="open" @click.away="open = false" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                <template x-for="reseller in filtered" :key="reseller.id">
                    <div class="px-4 py-2 cursor-pointer hover:bg-indigo-100 flex items-center gap-3" @click="select(reseller)">
                        <img :src="reseller.profile_photo_url" alt="" class="w-8 h-8 rounded-full object-cover">
                        <div class="flex flex-col">
                            <span x-text="reseller.name" class="font-medium"></span>
                            <span x-text="reseller.email" class="text-sm text-gray-500"></span>
                        </div>
                    </div>
                </template>
                <div x-show="filtered.length === 0" class="px-4 py-2 text-gray-400 text-sm">No results</div>
            </div>
            @error('selectedReseller')
            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
    @endif
    <div class="mb-4">
        <label class="block mb-1 font-medium sr-only">{{ __('common.upload_shipping_pdf_to_transaction') }}</label>
        <div
            x-data="{
                isDragging: false,
                fileName: '',
                handleDrop(e) {
                    e.preventDefault();
                    this.isDragging = false;
                    const files = e.dataTransfer.files;
                    if (files.length) {
                        this.$refs.fileInput.files = files;
                        this.fileName = files[0].name;
                    }
                }
            }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop="handleDrop($event)"
            class="flex flex-col items-center justify-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300 transition-colors duration-200"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 10l-4-4m0 0l-4 4m4-4v12" />
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ __('common.upload_shipping_document') }}</h2>
            <p class="text-gray-500 mb-6">{{ __('common.drag_drop_pdf') }}</p>
            <input
                type="file"
                name="shippingPdf"
                id="shippingPdf"
                accept="application/pdf"
                class="hidden"
                x-ref="fileInput"
                @change="fileName = $event.target.files[0]?.name || ''"
                required
                :disabled="$root.resellerSelected !== undefined && !$root.resellerSelected"
            >
            <label for="shippingPdf"
                class="px-8 py-3 rounded-md bg-gray-600 hover:bg-gray-700 text-white font-semibold cursor-pointer transition mb-2"
                :class="{ 'ring-2 ring-violet-400': isDragging, 'opacity-50 pointer-events-none': $root.resellerSelected !== undefined && !$root.resellerSelected }"
            >
                {{ __('common.select_pdf') }}
            </label>
            <template x-if="fileName">
                <span class="text-sm text-gray-700 mt-2" x-text="fileName"></span>
            </template>
            @error('shippingPdf') <span class="text-red-600 text-sm mt-2">{{ $message }}</span> @enderror
        </div>
    </div>
    <button type="submit"
        class="w-full flex items-center justify-center gap-2 px-6 py-3 mt-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-semibold text-base shadow transition focus:outline-none focus:ring-2 focus:ring-violet-400 focus:ring-offset-2 cursor-pointer"
        :disabled="$root.resellerSelected !== undefined && !$root.resellerSelected"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
        </svg>
        {{ __('common.upload') }}
    </button>
</form>
