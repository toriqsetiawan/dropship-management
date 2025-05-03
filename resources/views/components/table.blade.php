@props([
    'headers' => [],
    'rows' => [],
    'meta' => null,
    'title' => '',
    'description' => ''
])

<div class="bg-white dark:bg-gray-800 rounded-xl relative">
    @if($title)
    <header class="px-5 py-4">
        <h2 class="font-semibold text-gray-800 dark:text-gray-100">
            {{ $title }}
            @if($meta)
            <span class="text-gray-400 dark:text-gray-500 font-medium">{{ $meta }}</span>
            @endif
        </h2>
        @if($description)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endif
    </header>
    @endif

    <div>
        <div class="overflow-x-auto">
            <table class="table-auto w-full">
                <!-- Table header -->
                <thead class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/20 border-t border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        @foreach($headers as $header)
                        <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                            <div class="font-semibold {{ is_array($header) ? ($header['align'] === 'right' ? 'text-right' : 'text-left') : 'text-left' }}">
                                {{ is_array($header) ? $header['text'] : $header }}
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <!-- Table body -->
                <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700">
                    {{ $slot }}
                </tbody>
            </table>
        </div>
    </div>
</div>
