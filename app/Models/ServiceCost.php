<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCost extends Model
{
    use HasFactory;

    /**
     * ID untuk pendapatan pass-through.
     */
    public const PASS_THROUGH_ID = 1;

    /**
     * ID untuk pendapatan uang muka (down payment).
     */
    public const DOWN_PAYMENT_ID = 2;

    /**
     * Slug untuk pendapatan pass-through.
     */
    public const PASS_THROUGH_SLUG = 'pass-through';

    /**
     * Slug untuk pendapatan uang muka (down payment).
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
