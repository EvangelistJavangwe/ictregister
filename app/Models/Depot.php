<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Depot extends Model
{
    protected $fillable = ['name'];

    /** All depot names, alphabetical — for autocomplete lists. */
    public static function names()
    {
        return static::orderBy('name')->pluck('name');
    }

    /** Add a depot to the shared list if it's new (case-insensitive), so it's suggested everywhere from now on. */
    public static function rememberIfNew(?string $name): void
    {
        $name = trim((string) $name);
        if ($name === '') {
            return;
        }

        $exists = static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists();
        if (!$exists) {
            static::create(['name' => $name]);
        }
    }
}
