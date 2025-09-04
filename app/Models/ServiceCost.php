<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCost extends Model
{
    use HasFactory;

    /**
     * Identifier for pass-through income.
     */
    public const PASS_THROUGH_ID = 1;

    /**
     * Identifier for down payment income.
     */
    public const DOWN_PAYMENT_ID = 2;

    protected $fillable = [
        'name',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
