<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplate extends Model
{
    protected $fillable = [
        'name',
        'project_name',
        'tank_items',
    ];

    protected $casts = [
        'tank_items' => 'array',
    ];
}
