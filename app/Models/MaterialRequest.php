<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $fillable = [
        'material',
        'quantity',
        'unit',
        'project',
        'supplier',
        'requested_date',
        'status',
    ];

    protected $casts = [
        'requested_date' => 'date',
    ];
}