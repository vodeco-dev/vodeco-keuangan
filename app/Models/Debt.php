<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'related_party', // Tambahkan ini
        'type',
        'amount',
        'due_date',
        'status',
    ];

    // Relasi ke model Payment
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Accessor untuk menghitung total yang sudah dibayar
    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    // Accessor untuk menghitung sisa tagihan
    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    // Accessor untuk menghitung progres dalam persen
    public function getProgressAttribute()
    {
        if ($this->amount == 0) {
            return 100;
        }
        return ($this->paid_amount / $this->amount) * 100;
    }
}