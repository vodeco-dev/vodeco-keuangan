<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email', // Menambahkan email untuk kebutuhan invoicing
    ];

    /**
     * Mendapatkan semua proyek yang dimiliki oleh klien.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Mendapatkan semua invoice yang dimiliki oleh klien.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
