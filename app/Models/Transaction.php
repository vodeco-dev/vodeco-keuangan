<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

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
        'amount',
        'description',
        'date',
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
     * Mendefinisikan relasi ke model Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Menghitung ringkasan keuangan (pemasukan, pengeluaran, saldo).
     *
     * @return array
     */
    public static function getSummary(): array
    {
        return Cache::remember('transaction_summary', 300, function () {
            $summary = self::query()
                ->join('categories', 'transactions.category_id', '=', 'categories.id')
                ->selectRaw('
                    SUM(CASE WHEN categories.type = "pemasukan" THEN transactions.amount ELSE 0 END) AS pemasukan,
                    SUM(CASE WHEN categories.type = "pengeluaran" THEN transactions.amount ELSE 0 END) AS pengeluaran
                ')
                ->first();

            $pemasukan = $summary->pemasukan ?? 0;
            $pengeluaran = $summary->pengeluaran ?? 0;

            return [
                'pemasukan'   => $pemasukan,
                'pengeluaran' => $pengeluaran,
                'saldo'       => $pemasukan - $pengeluaran,
            ];
        });
    }
}
