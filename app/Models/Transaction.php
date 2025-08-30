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
        'project_id',
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
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Method getSummary() dihapus dari sini dan dipindahkan ke TransactionService
    // untuk pemisahan tanggung jawab (separation of concerns) yang lebih baik.
}
