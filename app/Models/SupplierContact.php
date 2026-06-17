<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierContact extends Model
{
    protected $fillable = [
        'name',
        'company',
        'phone',
        'email',
        'address',
        'notes',
    ];
}
