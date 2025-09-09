<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'public_token',
        'number',
        'issue_date',
        'due_date',
        'status',
        'total',
        'client_name',
        'client_email',
        'client_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'total' => 'decimal:2',
    ];

    /**
     * The "booted" method of the model.
     * Secara otomatis membuat transaksi pemasukan ketika status invoice diubah menjadi 'Paid'.
     */
    protected static function booted()
    {
        static::creating(function (Invoice $invoice) {
            $invoice->public_token = Str::uuid();
        });
    }

    /**
     * Mendapatkan item-item yang ada di dalam invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get all of the payments for the invoice.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
