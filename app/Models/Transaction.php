<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; // <-- Import DB Facade

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'amount',
        'description',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Menghitung ringkasan keuangan (pemasukan, pengeluaran, saldo).
     *
     * @return array
     */
    public static function getSummary()
    {
        // Menghitung total pemasukan
        $pemasukan = self::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('categories.type', 'pemasukan')
            ->sum('transactions.amount');

        // Menghitung total pengeluaran
        $pengeluaran = self::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('categories.type', 'pengeluaran')
            ->sum('transactions.amount');

        // Menghitung saldo
        $saldo = $pemasukan - $pengeluaran;

        return [
            'pemasukan'   => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'saldo'       => $saldo,
        ];
    }
}