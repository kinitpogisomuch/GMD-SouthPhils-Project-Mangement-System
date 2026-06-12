<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Casts a boolean attribute for storage as 'true'/'false' text instead of
 * PHP true/false, since PDO binds PHP booleans as integers (0/1) which
 * Postgres rejects for boolean columns (SQLSTATE 42804). Storing as text
 * lets Postgres apply its implicit text->boolean cast on UPDATE/INSERT,
 * while still keeping Eloquent's dirty-check accurate.
 */
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
