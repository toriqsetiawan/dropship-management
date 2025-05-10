<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .info-table td { padding: 2px 8px; }
        .attendance-table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        .attendance-table th, .attendance-table td { border: 1px solid #333; padding: 4px 8px; text-align: center; }
        .attendance-table th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="header">{{ __('attendance.payslip.title', ['name' => $employee->name, 'month' => $monthName]) }}</div>
    <table class="info-table">
        <tr><td><b>{{ __('attendance.payslip.employee_name') }}:</b></td><td>{{ $employee->name }}</td></tr>
        <tr><td><b>{{ __('attendance.payslip.type') }}:</b></td><td>{{ ucfirst($employee->type) }}</td></tr>
        <tr><td><b>{{ __('attendance.payslip.base_salary') }}:</b></td><td>Rp {{ number_format($baseSalary, 0, ',', '.') }}</td></tr>
    </table>
    <h4 style="margin-top: 24px;">{{ __('attendance.payslip.attendance_details') }}</h4>
    <table class="attendance-table">
        <thead>
            <tr>
                <th>{{ __('attendance.payslip.date') }}</th>
                <th>{{ __('attendance.payslip.status') }}</th>
                <th>{{ __('attendance.payslip.hours') }}</th>
                <th>{{ __('attendance.payslip.daily_pay') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalHours = 0;
                $totalDailyPay = 0;
                $standardHours = 8;
            @endphp
            @foreach($attendanceRecords as $record)
                @php
                    $isPresent = $record->status === 'present' && $record->hours > 0;
                    $payForDay = $isPresent ? $dailyPay * ($record->hours / $standardHours) : 0;
                    $totalHours += $record->hours;
                    $totalDailyPay += $payForDay;
                    // Row style logic
                    $rowStyle = '';
                    if ($record->status === 'sick') {
                        $rowStyle = 'background-color: #fff3cd;'; // warning (yellow)
                    } elseif ($record->status === 'absent') {
                        $rowStyle = 'background-color: #f8d7da; color: #721c24;'; // danger (red)
                    } elseif ($record->status === 'leave') {
                        $rowStyle = 'background-color: #cce5ff; color: #004085;'; // info (blue)
                    } elseif ($record->status === 'present' && $record->hours != 8) {
                        $rowStyle = 'background-color: #d1ecf1; color: #0c5460;'; // info (light blue)
                    }
                @endphp
                <tr style="{{ $rowStyle }}">
                    <td>{{ $record->date }}</td>
                    <td>{{ __("attendance.status." . $record->status) }}</td>
                    <td>{{ $record->hours == 8 ? 'Full' : $record->hours }}</td>
                    <td>
                        @if($isPresent)
                            Rp {{ number_format($payForDay, 0, ',', '.') }}
                        @else
                            Rp 0
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr>
                <th colspan="3">{{ __('attendance.payslip.total') }}</th>
                <th>Rp {{ number_format($totalDailyPay, 0, ',', '.') }}</th>
            </tr>
            <tr>
                <th colspan="3">{{ __('attendance.payslip.bonus') }}</th>
                <th>Rp {{ number_format($bonus, 0, ',', '.') }}</th>
            </tr>
            @php $totalPaid = $totalDailyPay + $bonus; @endphp
            <tr>
                <th colspan="3">{{ __('attendance.payslip.total_paid') }}</th>
                <th>Rp {{ number_format($totalPaid, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>
</body>
</html>
