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
     * Secara otomatis membuat transaksi pemasukan ketika status invoice diubah menjadi 'Paid'.
     */
    protected static function booted()
    {
        static::creating(function (Invoice $invoice) {
            $invoice->public_token = Str::uuid();
        });

        static::updated(function (Invoice $invoice) {
            // Cek jika status berubah menjadi 'Paid' dan sebelumnya bukan 'Paid'
            if ($invoice->isDirty('status') && $invoice->status === 'Paid') {
                // Cari kategori default untuk pemasukan invoice, misal 'Penjualan Jasa'
                // Fallback ke kategori pertama jika tidak ditemukan
                $category = Category::where('name', 'Penjualan Jasa')->orWhere('type', 'pemasukan')->first();

                // Pastikan ada kategori sebelum membuat transaksi
                if ($category) {
                    Transaction::create([
                        'category_id' => $category->id,
                        'user_id' => auth()->id(), // Gunakan user yang sedang login
                        'amount' => $invoice->total,
                        'description' => 'Pembayaran untuk Invoice #' . $invoice->number,
                        'date' => now(),
                    ]);
                }
            }
        });
    }

    /**
     * Mendapatkan item-item yang ada di dalam invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

}
