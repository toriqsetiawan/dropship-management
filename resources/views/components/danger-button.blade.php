<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-hidden focus:border-red-700 focus:ring-3 focus:ring-red-200 active:bg-red-600 disabled:opacity-25 transition cursor-pointer']) }}>
    {{ $slot }}
</button>
