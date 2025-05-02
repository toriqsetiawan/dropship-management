<div class="relative" x-data="{ open: false }">
    <button
        @click="open = !open"
        class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
    >
        <span class="flex items-center">
            <img src="{{ asset('images/flags/' . app()->getLocale() . '.svg') }}" class="w-5 h-5 mr-2" alt="{{ strtoupper(app()->getLocale()) }}">
            {{ strtoupper(app()->getLocale()) }}
        </span>
        <svg class="w-5 h-5 ml-2 -mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="origin-top-right z-10 absolute top-full min-w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1 right-0"
        role="menu"
        aria-orientation="vertical"
        aria-labelledby="language-menu"
    >
        @foreach(['en' => 'English', 'id' => 'Indonesia'] as $locale => $language)
            <a
                href="{{ route('language.switch', $locale) }}"
                class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ app()->getLocale() === $locale ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                role="menuitem"
            >
                <img src="{{ asset('images/flags/' . $locale . '.svg') }}" class="w-5 h-5 mr-2" alt="{{ strtoupper($locale) }}">
                {{ $language }}
            </a>
        @endforeach
    </div>
</div>
