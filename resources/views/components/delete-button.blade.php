@props(['route', 'title', 'description'])

<form action="{{ $route }}" method="POST" class="inline-block">
    @csrf
    @method('DELETE')
    <button type="submit"
            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-600"
            onclick="return confirm('{{ $description }}')">
        <i class="fa-solid fa-trash"></i>
    </button>
</form>
