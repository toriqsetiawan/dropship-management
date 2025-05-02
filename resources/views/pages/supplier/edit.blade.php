<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('supplier.edit') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">
                        {{ __('supplier.edit_title') }}
                    </h3>
                    <form action="{{ route('supplier.update', $supplier->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-6">
                            <!-- Name -->
                            <div>
                                <x-label for="name" :value="__('supplier.name')" />
                                <x-input id="name" type="text" name="name" :value="old('name', $supplier->name)" required autofocus class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <!-- Email -->
                            <div>
                                <x-label for="email" :value="__('supplier.email')" />
                                <x-input id="email" type="email" name="email" :value="old('email', $supplier->email)" required class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <!-- Phone -->
                            <div>
                                <x-label for="phone" :value="__('supplier.phone')" />
                                <x-input id="phone" type="text" name="phone" :value="old('phone', $supplier->phone)" required class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>

                            <!-- Status -->
                            <div>
                                <x-label for="status" :value="__('supplier.status')" />
                                <select id="status" name="status" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    <option value="active" {{ old('status', $supplier->status) == 'active' ? 'selected' : '' }}>{{ __('supplier.status_active') }}</option>
                                    <option value="inactive" {{ old('status', $supplier->status) == 'inactive' ? 'selected' : '' }}>{{ __('supplier.status_inactive') }}</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <x-button type="button" onclick="window.history.back()" class="bg-gray-600 hover:bg-gray-700 mr-3">
                                    {{ __('common.actions.back') }}
                                </x-button>
                                <x-button>
                                    {{ __('common.actions.save') }}
                                </x-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
