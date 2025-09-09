<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payable_id',
        'payable_type',
        'amount',
        'payment_date',
        'notes',
    ];

    public function payable()
    {
        return $this->morphTo();
    }
}
