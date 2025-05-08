<form method="POST" action="{{ route('shipping.upload.post') }}" enctype="multipart/form-data" x-data="{ resellerSelected: '{{ old('selectedReseller') ?? '' }}' }">
    @csrf
    @if($isDistributorOrSuperadmin)
        <div class="mb-6 w-full flex flex-col items-center">
            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">{{ __('common.select_reseller') }}</label>
            <select name="selectedReseller"
                    x-model="resellerSelected"
                    class="form-select w-full max-w-xs rounded-lg border-gray-300 focus:ring-violet-500 focus:border-violet-500"
                    required>
                <option value="">-- {{ __('common.select_reseller') }} --</option>
                @foreach($resellers as $reseller)
                    <option value="{{ $reseller->id }}">{{ $reseller->name }}</option>
                @endforeach
            </select>
            @error('selectedReseller') <span class="text-red-600 text-sm mt-2">{{ $message }}</span> @enderror
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
