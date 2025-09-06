<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCost extends Model
{
    use HasFactory;

    /**
     * Slug for pass-through income.
     */
    public const PASS_THROUGH_SLUG = 'pass-through';

    /**
     * Slug for down payment income.
     */
    public const DOWN_PAYMENT_SLUG = 'down-payment';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
