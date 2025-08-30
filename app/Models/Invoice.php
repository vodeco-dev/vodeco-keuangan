<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'project_id',
        'number',
        'issue_date',
        'due_date',
        'status',
        'total',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'total' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::updated(function (Invoice $invoice) {
            if ($invoice->isDirty('status') && $invoice->status === 'Paid') {
                $category = Category::first();
                $user = User::first();

                if ($category && $user) {
                    Transaction::create([
                        'category_id' => $category->id,
                        'user_id' => $user->id,
                        'amount' => $invoice->total,
                        'description' => 'Invoice ' . $invoice->number,
                        'date' => now(),
                    ]);
                }
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
