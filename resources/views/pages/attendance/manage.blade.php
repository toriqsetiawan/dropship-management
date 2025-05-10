<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-3xl mx-auto">
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold mb-2">{{ __('attendance.manage') }}: {{ $employee->name }}</h1>
            <a href="{{ route('attendance.index') }}" class="text-sm text-blue-600 hover:underline">&larr; {{ __('attendance.actions.back') }}</a>
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            @if (session('success'))
                <div class="mb-6">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                </div>
            @endif
            <form action="{{ route('attendance.store') }}" method="POST">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                <div x-data="{
                    attendance: {
                        @foreach($days as $date)
                            '{{ $date->toDateString() }}': {
                                status: '{{ $attendanceRecords[$date->toDateString()]->status ?? 'present' }}',
                                hours: {{ $attendanceRecords[$date->toDateString()]->hours ?? 8 }}
                            },
                        @endforeach
                    }
                }">
                <table class="table-auto w-full mb-6">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">{{ __('attendance.fields.date') }}</th>
                            <th class="px-4 py-2 text-left">{{ __('attendance.fields.status') }}</th>
                            <th class="px-4 py-2 text-left">{{ __('attendance.fields.hours') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $date)
                        <tr>
                            <td class="px-4 py-2">{{ $date->format('Y-m-d (D)') }}</td>
                            <td class="px-4 py-2">
                                <select
                                    x-model="attendance['{{ $date->toDateString() }}'].status"
                                    name="attendance[{{ $date->toDateString() }}]"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    x-on:change="
                                        ['leave', 'sick', 'absent'].includes(attendance['{{ $date->toDateString() }}'].status)
                                            ? attendance['{{ $date->toDateString() }}'].hours = 0
                                            : (attendance['{{ $date->toDateString() }}'].hours == 0 ? attendance['{{ $date->toDateString() }}'].hours = 8 : null)
                                    "
                                >
                                    <option value="present">{{ __('attendance.status.present') }}</option>
                                    <option value="absent">{{ __('attendance.status.absent') }}</option>
                                    <option value="sick">{{ __('attendance.status.sick') }}</option>
                                    <option value="leave">{{ __('attendance.status.leave') }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <input
                                    type="number"
                                    name="attendance_hours[{{ $date->toDateString() }}]"
                                    min="0" max="24" step="0.5"
                                    x-model.number="attendance['{{ $date->toDateString() }}'].hours"
                                    :readonly="['leave', 'sick', 'absent'].includes(attendance['{{ $date->toDateString() }}'].status)"
                                    class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <button type="submit" class="btn bg-indigo-600 hover:bg-indigo-700 text-white">{{ __('attendance.actions.save') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
