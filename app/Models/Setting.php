<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    public static function read(string $key, string $default): string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }
}
