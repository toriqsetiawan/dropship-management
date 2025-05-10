<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeAttendanceController extends Controller
{
    public function index()
    {
        $employees = Employee::all();

        // Attendance summary for current month
        $currentMonth = now()->format('m');
        $currentYear = now()->format('Y');
        // Calculate working days in the month (excluding Sundays)
        $startDate = Carbon::createFromDate($currentYear, $currentMonth, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            if ($date->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
        }
        $attendanceSummary = [];
        foreach ($employees as $employee) {
            $summary = [
                'present' => 0,
                'absent' => 0,
                'sick' => 0,
                'leave' => 0,
            ];
            $records = EmployeeAttendance::join('employee_salaries', 'employee_attendances.employee_salary_id', '=', 'employee_salaries.id')
                ->where('employee_salaries.employee_id', $employee->id)
                ->whereMonth('employee_attendances.date', $currentMonth)
                ->whereYear('employee_attendances.date', $currentYear)
                ->select('employee_attendances.status')
                ->get();
            foreach ($records as $record) {
                if (isset($summary[$record->status])) {
                    $summary[$record->status]++;
                }
            }
            $dailySalary = $workingDays > 0 ? $employee->salary / $workingDays : 0;
            $approximatePaidSalary = $summary['present'] * $dailySalary;
            $minimumBonus = 10000 * $summary['present'];
            $attendanceSummary[] = [
                'employee' => $employee,
                'present' => $summary['present'],
                'absent' => $summary['absent'],
                'sick' => $summary['sick'],
                'leave' => $summary['leave'],
                'approximate_paid_salary' => $approximatePaidSalary,
                'minimum_bonus' => $minimumBonus,
            ];
        }

        $totalApproximatePaidSalary = array_sum(array_column($attendanceSummary, 'approximate_paid_salary'));
        $totalPresentDays = array_sum(array_column($attendanceSummary, 'present'));
        $minimumBonus = 10000 * $totalPresentDays * count($employees);
        return view('pages.attendance.index', compact('employees', 'attendanceSummary', 'totalApproximatePaidSalary', 'minimumBonus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance' => 'required|array',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $currentMonth = now()->format('m');
        $currentYear = now()->format('Y');
        $salary = EmployeeSalary::firstOrCreate([
            'employee_id' => $employee->id,
            'month' => $currentMonth,
            'year' => $currentYear,
        ], [
            'base_salary' => 0,
        ]);

        foreach ($request->attendance as $date => $status) {
            $hours = $request->input('attendance_hours.' . $date, 8);
            EmployeeAttendance::updateOrCreate(
                [
                    'employee_salary_id' => $salary->id,
                    'date' => $date,
                ],
                [
                    'status' => $status,
                    'hours' => $hours,
                ]
            );
        }

        return redirect()->route('attendance.manage', ['employee' => $employee->id])->with('success', 'Attendance updated successfully.');
    }

    public function generateSalary()
    {
        $currentMonth = Carbon::now()->format('m');
        $currentYear = Carbon::now()->format('Y');

        // Calculate working days excluding Sundays
        $startDate = Carbon::createFromDate($currentYear, $currentMonth, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            if ($date->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
        }

        $employees = Employee::all();

        foreach ($employees as $employee) {
            $attendanceCount = EmployeeAttendance::join('employee_salaries', 'employee_attendances.employee_salary_id', '=', 'employee_salaries.id')
                ->where('employee_salaries.employee_id', $employee->id)
                ->whereMonth('employee_attendances.date', $currentMonth)
                ->whereYear('employee_attendances.date', $currentYear)
                ->where('employee_attendances.status', 'present')
                ->count();

            $dailySalary = $employee->salary / $workingDays;
            $totalSalary = $dailySalary * $attendanceCount;

            EmployeeSalary::create([
                'employee_id' => $employee->id,
                'month' => $currentMonth,
                'year' => $currentYear,
                'base_salary' => $totalSalary,
            ]);
        }

        return redirect()->route('attendance.index')->with('success', 'Salary records generated successfully.');
    }

    public function generateMonthAttendance()
    {
        $currentMonth = Carbon::now()->format('m');
        $currentYear = Carbon::now()->format('Y');

        // Calculate working days excluding Sundays
        $startDate = Carbon::createFromDate($currentYear, $currentMonth, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $employees = Employee::all();

        foreach ($employees as $employee) {
            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
                if ($date->dayOfWeek !== Carbon::SUNDAY) {
                    // Check if attendance already exists for this employee and date
                    $exists = EmployeeAttendance::join('employee_salaries', 'employee_attendances.employee_salary_id', '=', 'employee_salaries.id')
                        ->where('employee_salaries.employee_id', $employee->id)
                        ->whereDate('employee_attendances.date', $date->toDateString())
                        ->exists();
                    if (!$exists) {
                        // Find or create salary record for this month
                        $salary = EmployeeSalary::firstOrCreate([
                            'employee_id' => $employee->id,
                            'month' => $currentMonth,
                            'year' => $currentYear,
                        ], [
                            'base_salary' => 0, // You can update this later
                        ]);
                        EmployeeAttendance::create([
                            'employee_salary_id' => $salary->id,
                            'date' => $date->toDateString(),
                            'status' => 'present',
                        ]);
                    }
                }
            }
        }
        return redirect()->route('attendance.index')->with('success', 'Attendance records generated for the month.');
    }

    public function manage($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $currentMonth = now()->format('m');
        $currentYear = now()->format('Y');
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $days = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            if ($date->dayOfWeek !== \Carbon\Carbon::SUNDAY) {
                $days[] = $date->copy();
            }
        }
        $attendanceRecords = EmployeeAttendance::join('employee_salaries', 'employee_attendances.employee_salary_id', '=', 'employee_salaries.id')
            ->where('employee_salaries.employee_id', $employee->id)
            ->whereMonth('employee_attendances.date', $currentMonth)
            ->whereYear('employee_attendances.date', $currentYear)
            ->select('employee_attendances.*')
            ->get()
            ->keyBy('date');
        return view('pages.attendance.manage', compact('employee', 'days', 'attendanceRecords'));
    }

    public function generatePayslip(Request $request, $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $bonus = (int) $request->input('bonus', 0);
        $currentMonth = now()->format('m');
        $currentYear = now()->format('Y');
        $monthName = now()->format('F Y');

        // Get all attendance records for this employee for the current month
        $attendanceRecords = EmployeeAttendance::join('employee_salaries', 'employee_attendances.employee_salary_id', '=', 'employee_salaries.id')
            ->where('employee_salaries.employee_id', $employee->id)
            ->whereMonth('employee_attendances.date', $currentMonth)
            ->whereYear('employee_attendances.date', $currentYear)
            ->select('employee_attendances.*')
            ->orderBy('employee_attendances.date')
            ->get();

        // Calculate working days in the month (excluding Sundays)
        $startDate = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            if ($date->dayOfWeek !== \Carbon\Carbon::SUNDAY) {
                $workingDays++;
            }
        }
        $baseSalary = $employee->salary;
        $dailyPay = $workingDays > 0 ? $baseSalary / $workingDays : 0;
        $total = $baseSalary + $bonus;

        $pdf = Pdf::loadView('pdf.payslip', [
            'employee' => $employee,
            'attendanceRecords' => $attendanceRecords,
            'baseSalary' => $baseSalary,
            'bonus' => $bonus,
            'total' => $total,
            'monthName' => $monthName,
            'dailyPay' => $dailyPay,
        ]);

        return $pdf->download('Payslip-' . $employee->name . '-' . $monthName . '.pdf');
    }
}
