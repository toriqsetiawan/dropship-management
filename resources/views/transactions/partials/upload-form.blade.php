<form method="POST" action="{{ route('shipping.upload.post') }}" enctype="multipart/form-data">
    @csrf
    @if($isDistributorOrSuperadmin)
        <div class="mb-4">
            <label class="block mb-1 font-medium">{{ __('Select Reseller') }}</label>
            <select name="selectedReseller" class="form-select w-full" required>
                <option value="">-- {{ __('Select Reseller') }} --</option>
                @foreach($resellers as $reseller)
                    <option value="{{ $reseller->id }}">{{ $reseller->name }}</option>
                @endforeach
            </select>
            @error('selectedReseller') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
    @endif
    <div class="mb-4">
        <label class="block mb-1 font-medium">{{ __('Upload Shipping PDF to Transaction') }}</label>
        <input type="file" name="shippingPdf" accept="application/pdf" class="form-input w-full" required>
        @error('shippingPdf') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>
    <button type="submit" class="btn bg-violet-500 hover:bg-violet-600 text-white">{{ __('Upload') }}</button>
</form>
