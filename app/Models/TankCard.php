<?php

namespace App\Models;

use App\Casts\PostgresBoolean;
use Illuminate\Database\Eloquent\Model;

/**
 * One "Our Work" tank card as edited in Settings (see App\Support\TankCatalog::cards()): either the admin's
 * changes to a built-in tank type, or a card the admin added (is_custom, with its own type name).
 */
class TankCard extends Model
{
    protected $primaryKey = 'slug';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['slug', 'image_url', 'badge', 'blurb', 'sort_order', 'is_visible', 'is_custom', 'type', 'capacity_label'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_visible' => PostgresBoolean::class,
        'is_custom'  => PostgresBoolean::class,
    ];

    protected static function booted(): void
    {
        // the catalog keeps the cards in memory for the request — refresh it after any change
        static::saved(fn () => \App\Support\TankCatalog::flush());
        static::deleted(fn () => \App\Support\TankCatalog::flush());
    }
}
