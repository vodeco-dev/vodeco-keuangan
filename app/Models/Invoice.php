<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'user_id',
        'public_token',
        'number',
        'issue_date',
        'due_date',
        'status',
        'total',
        'client_name',
        'client_email',
        'client_address',
        'down_payment',
        'payment_date',
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
        'down_payment' => 'decimal:2',
    ];

    /**
     * The "booted" method of the model.
     * Penetapan token publik dilakukan ketika invoice dibuat.
     */
    protected static function booted()
    {
        static::creating(function (Invoice $invoice) {
            $invoice->public_token = Str::uuid();
        });

        // Pencatatan transaksi dipindahkan ke proses pembayaran agar lebih fleksibel
    }

    /**
     * Mendapatkan item-item yang ada di dalam invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function customerService(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
