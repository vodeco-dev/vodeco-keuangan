<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'user_id',
        'amount',
        'description',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public static function getSummary(): array
    {
        $userId = auth()->id();

        $pemasukan = self::query()
            ->where('user_id', $userId)
            ->whereHas('category', function ($query) {
                $query->where('type', 'pemasukan');
            })
            ->sum('amount');

        $pengeluaran = self::query()
            ->where('user_id', $userId)
            ->whereHas('category', function ($query) {
                $query->where('type', 'pengeluaran');
            })
            ->sum('amount');

        $saldo = $pemasukan - $pengeluaran;

        return [
            'pemasukan'   => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'saldo'       => $saldo,
        ];
    }
}
