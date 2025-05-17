<div
    x-show="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
    style="display: none;"
>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-lg w-full p-6 relative">
        <button @click="show = false" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl cursor-pointer">&times;</button>
        <div class="flex items-center mb-4">
            <div class="shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mr-4">
                <svg class="h-6 w-6 text-red-600" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ $title }}
            </h3>
        </div>
        <div class="mb-6">
            {{ $content }}
        </div>
        <div class="flex flex-row justify-end gap-2">
            <button type="button" class="btn bg-gray-300" @click="show = false">{{ __('common.actions.cancel') }}</button>
            <button type="button" class="btn bg-blue-500 text-white" @click="onConfirm(); show = false">{{ __('common.transaction.update_status') }}</button>
        </div>
    </div>
</div>
