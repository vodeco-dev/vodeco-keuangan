<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PassThroughPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'name',
        'customer_type',
        'daily_balance',
        'duration_days',
        'maintenance_fee',
        'account_creation_fee',
        'is_active',
    ];

    protected $casts = [
        'daily_balance' => 'decimal:2',
        'maintenance_fee' => 'decimal:2',
        'account_creation_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function totalAdBudget(): float
    {
        return round($this->daily_balance * $this->duration_days, 2);
    }

    public function customerLabel(): string
    {
        return $this->customer_type === 'new' ? 'Pelanggan Baru' : 'Pelanggan Lama';
    }
}
