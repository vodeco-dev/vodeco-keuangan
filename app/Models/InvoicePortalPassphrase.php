<?php

namespace App\Models;

use App\Enums\InvoicePortalPassphraseAccessType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvoicePortalPassphrase extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'passphrase_hash',
        'access_type',
        'label',
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

    public function creatorCustomerService(): HasOne
    {
        return $this->hasOne(CustomerService::class, 'user_id', 'created_by');
    }

    public function creatorCustomerServiceId(): ?int
    {
        if ($this->relationLoaded('creatorCustomerService')) {
            return $this->creatorCustomerService?->id;
        }

        return $this->creatorCustomerService()->value('id');
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

    public function displayLabel(): string
    {
        $name = trim((string) $this->label);

        if ($name === '') {
            return $this->access_type?->label() ?? 'Tidak diketahui';
        }

        $typeLabel = $this->access_type?->label();

        return $typeLabel ? sprintf('%s (%s)', $name, $typeLabel) : $name;
    }

    public function canManageInvoice(Invoice $invoice): bool
    {
        $customerServiceId = $this->creatorCustomerServiceId();

        if ($invoice->customer_service_id) {
            if (! $customerServiceId || (int) $invoice->customer_service_id !== (int) $customerServiceId) {
                return false;
            }
        }

        if ($invoice->created_by && (int) $invoice->created_by !== (int) $this->created_by) {
            return false;
        }

        if (! $invoice->customer_service_id && ! $invoice->created_by) {
            $fingerprint = $this->normalizeLabel($invoice->customer_service_name);

            if ($fingerprint !== '' && ! in_array($fingerprint, $this->knownLabelFingerprints(), true)) {
                return false;
            }
        }

        return true;
    }

    public function labelMatches(?string $value): bool
    {
        $fingerprint = $this->normalizeLabel($value);

        if ($fingerprint === '') {
            return false;
        }

        return in_array($fingerprint, $this->knownLabelFingerprints(), true);
    }

    /**
     * @return array<int, string>
     */
    protected function knownLabelFingerprints(): array
    {
        $labels = [
            $this->displayLabel(),
            $this->label,
            optional($this->creator)->name,
        ];

        $fingerprints = [];

        foreach ($labels as $label) {
            $normalized = $this->normalizeLabel($label);

            if ($normalized === '') {
                continue;
            }

            $fingerprints[$normalized] = $normalized;
        }

        return array_values($fingerprints);
    }

    protected function normalizeLabel(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '';
        }

        return preg_replace('/\s+/u', ' ', mb_strtolower($normalized, 'UTF-8'));
    }
}
