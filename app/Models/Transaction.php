<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'user_id',
        'amount',
        'description',
        'date',
        'service_cost_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Mendapatkan kategori dari transaksi.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Mendapatkan user yang membuat transaksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan jenis biaya layanan yang terkait dengan transaksi.
     */
    public function serviceCost(): BelongsTo
    {
        return $this->belongsTo(ServiceCost::class);
    }

    // Method getSummary() dihapus dari sini dan dipindahkan ke TransactionService
    // untuk pemisahan tanggung jawab (separation of concerns) yang lebih baik.
}
