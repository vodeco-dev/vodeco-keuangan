<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
    ];

    /**
     * Aggregate saldo, pemasukan, and pengeluaran from transactions.
     *
     * @return array{saldo: float, pemasukan: float, pengeluaran: float}
     */
    public static function getSummary(): array
    {
        $totals = static::selectRaw(
            "SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS pemasukan, " .
            "SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS pengeluaran"
        )->first();

        $pemasukan = (float) ($totals->pemasukan ?? 0);
        $pengeluaran = (float) ($totals->pengeluaran ?? 0);
        $saldo = $pemasukan - $pengeluaran;

        return [
            'saldo' => $saldo,
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
        ];
    }
}
