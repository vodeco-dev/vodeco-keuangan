<?php

namespace App\Models;

use App\Enums\InvoicePortalPassphraseAccessType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvoicePortalPassphrase extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'passphrase_hash',
        'access_type',
        'is_active',
        'expires_at',
        'last_used_at',
        'usage_count',
        'created_by',
        'deactivated_at',
        'deactivated_by',
    ];

    protected $casts = [
        'access_type' => InvoicePortalPassphraseAccessType::class,
        'is_active' => 'bool',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public static function makePublicId(): string
    {
        return (string) Str::uuid();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deactivator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(InvoicePortalPassphraseLog::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    public function isUsable(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * @return array<int, string>
     */
    public function allowedTransactionTypes(): array
    {
        return $this->access_type?->allowedTransactionTypes() ?? [];
    }

    public function markAsUsed(?string $ipAddress = null, ?string $userAgent = null, string $action = 'verified'): void
    {
        $this->last_used_at = now();
        $this->usage_count = ($this->usage_count ?? 0) + 1;
        $this->save();

        $this->logs()->create([
            'action' => $action,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where(function ($query) {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function setPassphrase(string $plainPassphrase): void
    {
        $this->passphrase_hash = Hash::make($plainPassphrase);
    }
}
