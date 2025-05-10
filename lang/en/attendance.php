<?php

return [
    'title' => 'Attendance',
    'summary' => 'Attendance Summary',
    'manage' => 'Manage Attendance',
    'current_month' => 'Current Month',
    'generate_month' => 'Generate one month data',
    'filter' => [
        'by_employee' => 'Filter by Employee',
        'all_employees' => 'All Employees',
    ],
    'status' => [
        'present' => 'Present',
        'absent' => 'Absent',
        'sick' => 'Sick',
        'leave' => 'Leave',
    ],
    'fields' => [
        'date' => 'Date',
        'status' => 'Status',
        'hours' => 'Hours',
        'employee' => 'Employee',
        'bonus' => 'Bonus',
    ],
    'messages' => [
        'generated' => 'Attendance data has been generated successfully.',
        'updated' => 'Attendance has been updated successfully.',
        'no_data' => 'No attendance data found.',
    ],
    'actions' => [
        'manage' => 'Manage',
        'save' => 'Save Attendance',
        'back' => 'Back to summary',
        'generate_payslip' => 'Generate Payslip',
        'export_pdf' => 'Export as PDF',
    ],
    'payslip' => [
        'title' => 'Payslip for :name (:month)',
        'employee_name' => 'Employee Name',
        'type' => 'Type',
        'base_salary' => 'Base Salary',
        'bonus' => 'Bonus',
        'total' => 'Total',
        'attendance_details' => 'Attendance Details',
        'date' => 'Date',
        'status' => 'Status',
        'hours' => 'Hours',
        'daily_pay' => 'Daily Pay',
        'total_paid' => 'Total Paid',
    ],
];
