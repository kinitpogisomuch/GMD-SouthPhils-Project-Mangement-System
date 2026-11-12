<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class PostgresBoolean implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array($value, [1, '1', 't', 'true', 'T', 'TRUE'], true);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value ? 'true' : 'false';
    }
}
