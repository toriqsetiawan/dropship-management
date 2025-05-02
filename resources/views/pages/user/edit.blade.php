<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('user.edit') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">
                        {{ __('user.edit_title') }}
                    </h3>
                    <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-6">
                            <!-- Name -->
                            <div>
                                <x-label for="name" :value="__('user.fields.name')" />
                                <x-input id="name" type="text" name="name" :value="old('name', $user->name)" required autofocus class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <!-- Email -->
                            <div>
                                <x-label for="email" :value="__('user.fields.email')" />
                                <x-input id="email" type="email" name="email" :value="old('email', $user->email)" required class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <!-- Role -->
                            <div>
                                <x-label for="role" :value="__('user.fields.role')" />
                                <select id="role" name="role" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    <option value="">{{ __('Select Role') }}</option>
                                    @foreach(\App\Models\Role::all() as $role)
                                        <option value="{{ $role->name }}" {{ old('role', $user->role) == $role->name ? 'selected' : '' }}>
                                            {{ $role->description }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </div>

                            <!-- Password -->
                            <div>
                                <x-label for="password" :value="__('user.fields.password')" />
                                <x-input id="password" type="password" name="password" class="mt-1 block w-full" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Leave blank to keep current password') }}</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <!-- Password Confirmation -->
                            <div>
                                <x-label for="password_confirmation" :value="__('user.fields.password_confirmation')" />
                                <x-input id="password_confirmation" type="password" name="password_confirmation" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>

                            <!-- Current Photo -->
                            @if ($user->profile_photo_path)
                                <div>
                                    <x-label :value="__('Current Photo')" />
                                    <img src="{{ Storage::url($user->profile_photo_path) }}" alt="{{ $user->name }}" class="mt-2 h-20 w-20 rounded-full object-cover">
                                </div>
                            @endif

                            <!-- Photo -->
                            <div>
                                <x-label for="photo" :value="__('user.fields.photo')" />
                                <input type="file" id="photo" name="photo" accept="image/*" class="mt-1 block w-full text-sm text-gray-900 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300" />
                                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
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
