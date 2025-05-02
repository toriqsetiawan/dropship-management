@props(['value', 'for' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium mb-1', 'for' => $for]) }}>
    {{ $value ?? $slot }}
</label>
