<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">{{ __('employee.edit_title') }}</h1>
        <form action="{{ route('employees.update', $employee) }}" method="POST" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('employee.fields.name') }}</label>
                <input type="text" name="name" id="name" class="form-input w-full" value="{{ old('name', $employee->name) }}" required>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <label for="salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('employee.fields.salary_per_month') }}</label>
                <input type="number" name="salary" id="salary" class="form-input w-full" value="{{ old('salary', $employee->salary) }}" min="0" required>
                <x-input-error :messages="$errors->get('salary')" class="mt-2" />
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('employee.fields.type') }}</label>
                <select name="type" id="type" class="form-select w-full" required>
                    <option value="">{{ __('employee.fields.type') }}</option>
                    <option value="fulltime" {{ old('type', $employee->type) == 'fulltime' ? 'selected' : '' }}>{{ __('employee.types.fulltime') }}</option>
                    <option value="parttime" {{ old('type', $employee->type) == 'parttime' ? 'selected' : '' }}>{{ __('employee.types.parttime') }}</option>
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('employees.index') }}" class="btn bg-gray-500 hover:bg-gray-600 text-white">{{ __('employee.actions.cancel') }}</a>
                <button type="submit" class="btn bg-indigo-500 hover:bg-indigo-600 text-white">{{ __('employee.actions.update') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
