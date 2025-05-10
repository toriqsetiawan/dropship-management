<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['name', 'salary', 'type'];

    public function salaries()
    {
        return $this->hasMany(EmployeeSalary::class);
    }
}
