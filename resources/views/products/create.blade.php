@extends('layouts.app')

@push('scripts')
<script>
    let attributeCount = 0;

    function addAttribute() {
        const container = document.getElementById('attributes-container');
        const attributeDiv = document.createElement('div');
        attributeDiv.className = 'border rounded-lg p-4 mb-4';
        attributeDiv.innerHTML = `
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-medium">Attribute ${attributeCount + 1}</h3>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-500 hover:text-red-700">Remove</button>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Attribute Name</label>
                    <input type="text" name="attributes[${attributeCount}][name]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Attribute Values (comma separated)</label>
                    <input type="text" name="attributes[${attributeCount}][values]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g., Black, White, Black and White" required>
                </div>
            </div>
        `;
        container.appendChild(attributeDiv);
        attributeCount++;
    }

    // Add first attribute on page load
    document.addEventListener('DOMContentLoaded', function() {
        addAttribute();
    });
</script>
@endpush

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Create Product</h1>
    </div>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Basic Information</h2>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                    <input type="text" name="sku" id="sku" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></textarea>
                </div>
            </div>

            <!-- Product Details -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Product Details</h2>

                <div>
                    <label for="size_chart" class="block text-sm font-medium text-gray-700">Size Chart</label>
                    <textarea name="size_chart" id="size_chart" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label for="weight" class="block text-sm font-medium text-gray-700">Weight (kg)</label>
                    <input type="number" step="0.01" name="weight" id="weight" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="dimensions" class="block text-sm font-medium text-gray-700">Dimensions (LxWxH)</label>
                    <input type="text" name="dimensions" id="dimensions" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="images" class="block text-sm font-medium text-gray-700">Product Images</label>
                    <input type="file" name="images[]" id="images" multiple accept="image/*" class="mt-1 block w-full">
                </div>
            </div>

            <!-- Dynamic Attributes -->
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Attributes</h2>
                    <button type="button" onclick="addAttribute()" class="bg-blue-500 text-white px-3 py-1 rounded text-sm">Add Attribute</button>
                </div>
                <div id="attributes-container">
                    <!-- Attributes will be added here dynamically -->
                </div>
            </div>

            <!-- Pricing -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Pricing</h2>
                @foreach($roles as $role)
                    @if(auth()->user()->role_id == 1) {{-- Admin can see all pricing --}}
                        <div class="border rounded-lg p-4">
                            <h3 class="font-medium mb-2">{{ $role->name }} Pricing</h3>
                            <input type="hidden" name="prices[{{ $role->id }}][role_id]" value="{{ $role->id }}">

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="buy_price_{{ $role->id }}" class="block text-sm font-medium text-gray-700">Buy Price</label>
                                    <input type="number" step="0.01" name="prices[{{ $role->id }}][buy_price]" id="buy_price_{{ $role->id }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label for="sell_price_{{ $role->id }}" class="block text-sm font-medium text-gray-700">Sell Price</label>
                                    <input type="number" step="0.01" name="prices[{{ $role->id }}][sell_price]" id="sell_price_{{ $role->id }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label for="reseller_price_{{ $role->id }}" class="block text-sm font-medium text-gray-700">Reseller Price</label>
                                    <input type="number" step="0.01" name="prices[{{ $role->id }}][reseller_price]" id="reseller_price_{{ $role->id }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                            </div>
                        </div>
                    @elseif(auth()->user()->role_id == 2 && $role->slug != 'product_owner') {{-- Product Owner can see all except their own pricing --}}
                        <div class="border rounded-lg p-4">
                            <h3 class="font-medium mb-2">{{ $role->name }} Pricing</h3>
                            <input type="hidden" name="prices[{{ $role->id }}][role_id]" value="{{ $role->id }}">

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="buy_price_{{ $role->id }}" class="block text-sm font-medium text-gray-700">Buy Price</label>
                                    <input type="number" step="0.01" name="prices[{{ $role->id }}][buy_price]" id="buy_price_{{ $role->id }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label for="sell_price_{{ $role->id }}" class="block text-sm font-medium text-gray-700">Sell Price</label>
                                    <input type="number" step="0.01" name="prices[{{ $role->id }}][sell_price]" id="sell_price_{{ $role->id }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label for="reseller_price_{{ $role->id }}" class="block text-sm font-medium text-gray-700">Reseller Price</label>
                                    <input type="number" step="0.01" name="prices[{{ $role->id }}][reseller_price]" id="reseller_price_{{ $role->id }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                            </div>
                        </div>
                    @elseif(auth()->user()->role_id == 3 && $role->slug == 'reseller') {{-- Reseller can only see reseller pricing --}}
                        <div class="border rounded-lg p-4">
                            <h3 class="font-medium mb-2">{{ $role->name }} Pricing</h3>
                            <input type="hidden" name="prices[{{ $role->id }}][role_id]" value="{{ $role->id }}">

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="buy_price_{{ $role->id }}" class="block text-sm font-medium text-gray-700">Buy Price</label>
                                    <input type="number" step="0.01" name="prices[{{ $role->id }}][buy_price]" id="buy_price_{{ $role->id }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label for="sell_price_{{ $role->id }}" class="block text-sm font-medium text-gray-700">Sell Price</label>
                                    <input type="number" step="0.01" name="prices[{{ $role->id }}][sell_price]" id="sell_price_{{ $role->id }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label for="reseller_price_{{ $role->id }}" class="block text-sm font-medium text-gray-700">Reseller Price</label>
                                    <input type="number" step="0.01" name="prices[{{ $role->id }}][reseller_price]" id="reseller_price_{{ $role->id }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Create Product</button>
            <a href="{{ route('products.index') }}" class="ml-4 text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
</div>
@endsection
