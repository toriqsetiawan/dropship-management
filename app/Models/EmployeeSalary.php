<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
    protected $fillable = ['employee_id', 'month', 'year', 'base_salary'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendances()
    {
        return $this->hasMany(EmployeeAttendance::class);
    }
}
