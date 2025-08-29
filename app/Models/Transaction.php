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
        // Menghitung total pemasukan
        $pemasukan = self::query()
            ->whereHas('category', function ($query) {
                $query->where('type', 'pemasukan');
            })
            ->sum('amount');

        // Menghitung total pengeluaran
        $pengeluaran = self::query()
            ->whereHas('category', function ($query) {
                $query->where('type', 'pengeluaran');
            })
            ->sum('amount');

        // Menghitung saldo
        $saldo = $pemasukan - $pengeluaran;

        return [
            'pemasukan'   => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'saldo'       => $saldo,
        ];
    }
}
