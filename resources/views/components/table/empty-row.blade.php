@props(['colspan' => 5, 'message'])

<tr>
    <td colspan="{{ $colspan }}" class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
        <div class="text-center text-gray-500 dark:text-gray-400">{{ $message }}</div>
    </td>
</tr>
