<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-7xl mx-auto" x-data="{ showPayslipModal: false, selectedEmployee: null, selectedEmployeeName: '', bonus: 0 }">
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('attendance.summary') }}</h1>
            </div>
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <form action="{{ route('attendance.generate-month') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn bg-blue-600 hover:bg-blue-700 text-white">{{ __('attendance.generate_month') }}</button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-8">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <form method="GET" class="flex items-center mb-6">
            <label for="employee_filter" class="mr-2 text-sm font-medium text-gray-700">{{ __('attendance.filter.by_employee') }}:</label>
            <select name="employee" id="employee_filter" onchange="this.form.submit()" class="block w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('attendance.filter.all_employees') }}</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ request('employee') == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                @endforeach
            </select>
        </form>

        <x-table
            :headers="[
                __('attendance.fields.employee'),
                __('attendance.status.present'),
                __('attendance.status.absent'),
                __('attendance.status.sick'),
                __('attendance.status.leave'),
                __('attendance.fields.approximate_paid_salary'),
                __('attendance.fields.minimum_bonus'),
                __('common.actions_column')
            ]"
            :title="__('attendance.summary') . ' (' . __('attendance.current_month') . ')'"
        >
            @forelse($attendanceSummary as $summary)
                @if(!request('employee') || request('employee') == $summary['employee']->id)
                <tr>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ $summary['employee']->name }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ $summary['present'] }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ $summary['absent'] }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ $summary['sick'] }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ $summary['leave'] }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">Rp {{ number_format($summary['approximate_paid_salary'], 0, ',', '.') }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">Rp {{ number_format($summary['minimum_bonus'], 0, ',', '.') }}</td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <a href="{{ route('attendance.manage', ['employee' => $summary['employee']->id]) }}" class="text-violet-500 hover:text-violet-600">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button type="button"
                            class="btn btn-link hover:text-green-700 ml-2 cursor-pointer"
                            @click="selectedEmployee = {{ $summary['employee']->id }}; selectedEmployeeName = '{{ addslashes($summary['employee']->name) }}'; showPayslipModal = true; bonus = 0;"
                        >
                           <i class="fa-solid fa-file-invoice-dollar me-2"></i> {{ __('attendance.actions.generate_payslip', ['name' => $summary['employee']->name]) }}
                        </button>
                    </td>
                </tr>
                @endif
            @empty
                <x-table.empty-row :message="__('attendance.messages.no_data')" />
            @endforelse
            <tr>
                <td colspan="5" class="text-center font-semibold px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">{{ __('attendance.fields.approximate_paid_salary') }}</td>
                <td class="font-bold">Rp {{ number_format($totalApproximatePaidSalary, 0, ',', '.') }}</td>
                <td class="font-bold">Rp {{ number_format(collect($attendanceSummary)->sum('minimum_bonus'), 0, ',', '.') }}</td>
                <td class="font-bold">Rp {{ number_format($totalApproximatePaidSalary + collect($attendanceSummary)->sum('minimum_bonus'), 0, ',', '.') }}</td>
            </tr>
        </x-table>
        <!-- Payslip Modal -->
        <div x-show="showPayslipModal" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-40" x-cloak>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">{{ __('attendance.actions.generate_payslip') }} <span x-text="selectedEmployeeName"></span></h2>
                <form :action="'/attendance/payslip/' + selectedEmployee" method="GET" target="_blank">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('attendance.fields.bonus') }}</label>
                    <input type="number" name="bonus" x-model="bonus" class="form-input w-full mb-4 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" min="0" step="1000">
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn bg-gray-500 hover:bg-gray-600 text-white" @click="showPayslipModal = false">{{ __('employee.actions.cancel') }}</button>
                        <button type="submit" class="btn bg-green-600 hover:bg-green-700 text-white">{{ __('attendance.actions.export_pdf') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
