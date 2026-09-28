<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class ProgressRequest extends Model
{
    protected $fillable = [
        'project_id',
        'requested_by',
        'message',
        'phase',
        'status',
        'fulfilled_by',
        'fulfilled_at',
        'target_employee_id',
    ];

    protected $casts = [
        'fulfilled_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function fulfilledBy()
    {
        return $this->belongsTo(Employee::class, 'fulfilled_by');
    }

    public function targetEmployee()
    {
        return $this->belongsTo(Employee::class, 'target_employee_id');
    }
}