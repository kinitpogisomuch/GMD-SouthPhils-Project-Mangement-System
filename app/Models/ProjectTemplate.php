<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplate extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'project_name',
        'tank_items',
        'materials',
    ];

    protected $casts = [
        'tank_items' => 'array',
        'materials'  => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
