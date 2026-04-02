<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabDepartment extends Model
{
    protected $table = 'lab_departments';

    protected $fillable = ['clinic_id', 'name', 'code', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function tests()
    {
        return $this->hasMany(LabTestCatalog::class, 'department_id');
    }
}
