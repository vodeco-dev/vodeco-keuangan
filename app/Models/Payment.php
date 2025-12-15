<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'debt_id',
        'transaction_id',
        'amount',
        'payment_date',
        'notes',
    ];

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
