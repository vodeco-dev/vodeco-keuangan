<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonInterval;

class RecurringRevenue extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'user_id',
        'amount',
        'frequency',
        'next_run',
        'paused',
        'description',
    ];

    protected $casts = [
        'next_run' => 'datetime',
        'paused' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get CarbonInterval from frequency string.
     */
    public function interval(): CarbonInterval
    {
        return match ($this->frequency) {
            'daily' => CarbonInterval::day(),
            'weekly' => CarbonInterval::week(),
            'yearly' => CarbonInterval::year(),
            default => CarbonInterval::month(),
        };
    }
}
