<?php

return [
    'title' => 'Absensi',
    'summary' => 'Ringkasan Absensi',
    'manage' => 'Kelola Absensi',
    'current_month' => 'Bulan Ini',
    'generate_month' => 'Buat data satu bulan',
    'filter' => [
        'by_employee' => 'Filter berdasarkan Karyawan',
        'all_employees' => 'Semua Karyawan',
    ],
    'status' => [
        'present' => 'Hadir',
        'absent' => 'Tidak Hadir',
        'sick' => 'Sakit',
        'leave' => 'Cuti',
    ],
    'fields' => [
        'date' => 'Tanggal',
        'status' => 'Status',
        'hours' => 'Jam',
        'employee' => 'Karyawan',
        'bonus' => 'Bonus',
        'approximate_paid_salary' => 'Perkiraan Gaji Dibayar',
        'minimum_bonus' => 'Bonus Minimum',
    ],
    'messages' => [
        'generated' => 'Data absensi berhasil dibuat.',
        'updated' => 'Absensi berhasil diperbarui.',
        'no_data' => 'Tidak ada data absensi ditemukan.',
    ],
    'actions' => [
        'manage' => 'Kelola',
        'save' => 'Simpan Absensi',
        'back' => 'Kembali ke ringkasan',
        'generate_payslip' => 'Buat Slip Gaji',
        'export_pdf' => 'Ekspor ke PDF',
    ],
    'payslip' => [
        'title' => 'Slip Gaji :name (:month)',
        'employee_name' => 'Nama Karyawan',
        'type' => 'Tipe',
        'base_salary' => 'Gaji Pokok',
        'bonus' => 'Bonus',
        'total' => 'Total',
        'attendance_details' => 'Rincian Absensi',
        'date' => 'Tanggal',
        'status' => 'Status',
        'hours' => 'Jam',
        'daily_pay' => 'Gaji Harian',
        'total_paid' => 'Total Dibayar',
    ],
];
