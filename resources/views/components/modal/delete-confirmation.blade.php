@props([
    'confirmText' => null,
    'deleteText' => null,
    'cancelText' => null
])

<div
    x-data="{
        show: false,
        itemId: null,
        itemName: '',
        deleteRoute: '',
        init() {
            this.$watch('show', value => {
                if (value) {
                    document.body.classList.add('overflow-hidden');
                } else {
                    document.body.classList.remove('overflow-hidden');
                }
            });
        }
    }"
    @open-delete-modal.window="
        itemId = $event.detail.itemId;
        itemName = $event.detail.itemName;
        deleteRoute = $event.detail.deleteRoute;
        show = true;
    "
    @keydown.escape.window="show = false"
    x-cloak
>
    <!-- Modal Backdrop -->
    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black bg-opacity-25"
        @click="show = false"
    ></div>

    <!-- Modal Content -->
    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-4"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden px-4 py-6 sm:px-0"
    >
        <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 text-left shadow-xl transition-all sm:w-full sm:max-w-lg">
            <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                <button @click="show = false" class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                    <span class="sr-only">Close</span>
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="fa-solid fa-exclamation-triangle text-red-600"></i>
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $title ?? __('common.messages.delete_title') }}</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $confirmText ?? __('common.messages.delete_confirm') }} <span x-text="itemName" class="font-medium"></span>? {{ $slot }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <form :action="deleteRoute" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ $deleteText ?? __('common.actions.delete') }}
                    </button>
                </form>
                <button @click="show = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-base font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                    {{ $cancelText ?? __('common.actions.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
