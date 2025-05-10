<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('employee.title') }}</h1>
            </div>
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <a href="{{ route('employees.create') }}" class="btn bg-indigo-500 hover:bg-indigo-600 text-white font-semibold px-4 py-2 rounded-lg">{{ __('employee.actions.create') }}</a>
            </div>
        </div>
        @if(session('success'))
            <div class="mb-8">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            </div>
        @endif
        <x-table
            :headers="[
                __('employee.fields.name'),
                __('employee.fields.salary'),
                __('employee.fields.type'),
                __('common.actions_column')
            ]"
            :title="__('employee.title')"
            :meta="$employees->count()"
        >
            @forelse($employees as $employee)
                <tr>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ $employee->name }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">Rp {{ number_format($employee->salary, 0, ',', '.') }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap capitalize">{{ __('employee.types.' . $employee->type) }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap w-px">
                        <div class="space-x-1">
                            <a href="{{ route('employees.edit', $employee) }}" class="text-violet-500 hover:text-violet-600">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this employee?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-600 cursor-pointer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <x-table.empty-row :colspan="4" :message="__('employee.messages.no_data')" />
            @endforelse
        </x-table>
    </div>
</x-app-layout>
