<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    protected $fillable = ['key', 'value'];
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    public static function get(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }
}

