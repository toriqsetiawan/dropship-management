<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendance extends Model
{
    protected $fillable = ['employee_salary_id', 'date', 'status', 'hours'];

    public function salary()
    {
        return $this->belongsTo(EmployeeSalary::class, 'employee_salary_id');
    }

    public function employeeSalary()
    {
        return $this->belongsTo(EmployeeSalary::class);
    }

    public function getEmployeeAttribute()
    {
        return $this->employeeSalary ? $this->employeeSalary->employee : null;
    }
}
