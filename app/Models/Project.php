<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'description', // Menggabungkan kolom dari kedua branch
    ];

    /**
     * Mendapatkan klien pemilik proyek.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Mendapatkan semua transaksi yang terkait dengan proyek ini.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Mendapatkan semua invoice yang terkait dengan proyek ini.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
