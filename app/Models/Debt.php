<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debt extends Model
{
    use HasFactory;

    public const TYPE_PASS_THROUGH = 'pass_through';
    public const TYPE_DOWN_PAYMENT = 'down_payment';
    public const STATUS_BELUM_LUNAS = 'belum lunas';
    public const STATUS_LUNAS = 'lunas';

    protected $fillable = [
        'description',
        'related_party',
        'type',
        'amount',
        'due_date',
        'status',
        'category_id',
        'user_id',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    public function getProgressAttribute()
    {
        if ($this->amount == 0) {
            return 100;
        }
        return ($this->paid_amount / $this->amount) * 100;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
