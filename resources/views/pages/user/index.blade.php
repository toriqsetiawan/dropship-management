<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('user.list') }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Add user button -->
                <a href="{{ route('users.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="hidden xs:block ml-2">{{ __('user.actions.create') }}</span>
                </a>
            </div>

        </div>

        @if (session('success'))
            <div class="mb-8">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- Table -->
        <x-table
            :headers="[
                __('user.fields.photo'),
                __('user.fields.name'),
                __('user.fields.email'),
                __('user.fields.role'),
                __('user.fields.created_at'),
                __('Actions')
            ]"
            :title="__('user.title')"
            :meta="$users->total()"
        >
            @forelse($users as $user)
                <tr>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="h-8 w-8 rounded-full">
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        {{ $user->name }}
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        {{ $user->email }}
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        {{ $user->role?->name }}
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        {{ $user->created_at->format('Y-m-d H:i') }}
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="flex space-x-3">
                            <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button type="button" @click="$dispatch('open-delete-modal', {
                                itemId: {{ $user->id }},
                                itemName: '{{ $user->name }}',
                                deleteRoute: '{{ route('users.destroy', $user->id) }}'
                            })" class="text-red-500 hover:text-red-600">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <x-table.empty-row :message="__('user.no_users')" />
            @endforelse
        </x-table>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $users->links() }}
        </div>

        <!-- Delete Confirmation Modal Component -->
        <x-modal.delete-confirmation>
            <x-slot:title>{{ __('user.delete_title') }}</x-slot:title>
            {{ __('user.delete_warning') }}
        </x-modal.delete-confirmation>
    </div>
</x-app-layout>