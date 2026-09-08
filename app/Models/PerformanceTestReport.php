<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceTestReport extends Model
{
    protected $fillable = [
        'project_id',
        'client_name',
        'project_location',
        'subject',
        'report_date',
        'test_items',
        'test_photos',
        'conducted_by_name',
        'conducted_by_role',
        'noted_by_name',
        'noted_by_role',
    ];

    protected $casts = [
        'report_date' => 'date',
        'test_items'  => 'array',
        'test_photos' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
