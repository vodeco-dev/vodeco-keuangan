<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Invoice extends Model
{
    public const TYPE_STANDARD = 'standard';
    public const TYPE_SETTLEMENT = 'settlement';
    public const TYPE_PASS_THROUGH_NEW = 'pass_through_new';
    public const TYPE_PASS_THROUGH_EXISTING = 'pass_through_existing';

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'public_token',
        'number',
        'issue_date',
        'due_date',
        'status',
        'total',
        'client_name',
        'client_whatsapp',
        'client_address',
        'down_payment',
        'down_payment_due',
        'payment_date',
        'customer_service_id',
        'customer_service_name',
        'created_by',
        'type',
        'reference_invoice_id',
        'settlement_token',
        'settlement_token_expires_at',
        'payment_proof_disk',
        'payment_proof_path',
        'payment_proof_filename',
        'payment_proof_original_name',
        'payment_proof_uploaded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'total' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'down_payment_due' => 'decimal:2',
        'settlement_token_expires_at' => 'datetime',
        'payment_proof_uploaded_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     * Penetapan token publik dilakukan ketika invoice dibuat.
     */
    protected static function booted()
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->public_token)) {
                $invoice->public_token = Str::uuid();
            }

            if (empty($invoice->settlement_token)) {
                $invoice->settlement_token = Str::random(64);
            }

            if (empty($invoice->settlement_token_expires_at)) {
                $invoice->settlement_token_expires_at = now()->addDays(7);
            }
        });

        // Pencatatan transaksi dipindahkan ke proses pembayaran agar lebih fleksibel
    }

    /**
     * Mendapatkan item-item yang ada di dalam invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function itemDescriptionSummary(int $maxLength = 120): string
    {
        $descriptions = $this->relationLoaded('items')
            ? $this->items->pluck('description')
            : $this->items()->pluck('description');

        $summary = $descriptions
            ->filter()
            ->map(fn ($description) => trim((string) $description))
            ->filter()
            ->implode(', ');

        if ($summary === '') {
            return 'Invoice #' . $this->number;
        }

        return Str::limit($summary, $maxLength);
    }

    public function transactionDescription(int $maxLength = 120): string
    {
        if ($this->type === self::TYPE_SETTLEMENT) {
            $referenceInvoice = $this->referenceInvoice;

            if ($referenceInvoice) {
                return $referenceInvoice->itemDescriptionSummary($maxLength);
            }
        }

        return $this->itemDescriptionSummary($maxLength);
    }

    public function customerService(): BelongsTo
    {
        return $this->belongsTo(CustomerService::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function referenceInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reference_invoice_id');
    }

    public function debt(): HasOne
    {
        return $this->hasOne(Debt::class);
    }

    public function hasValidSettlementToken(?string $token = null): bool
    {
        if (! $this->settlement_token) {
            return false;
        }

        if ($token !== null && ! hash_equals($this->settlement_token, $token)) {
            return false;
        }

        if ($this->settlement_token_expires_at && now()->greaterThan($this->settlement_token_expires_at)) {
            return false;
        }

        return true;
    }

    public function hasPaymentProof(): bool
    {
        return ! empty($this->payment_proof_path);
    }

    public function getPaymentProofUrlAttribute(): ?string
    {
        if (! $this->hasPaymentProof()) {
            return null;
        }

        $disk = $this->payment_proof_disk ?: config('filesystems.default');

        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($this->payment_proof_path)) {
                return null;
            }

            $url = $storage->url($this->payment_proof_path);

            if (is_string($url) && $url !== '') {
                return $url;
            }
        } catch (\Throwable $exception) {
            // Fallback handled below.
        }

        try {
            return route('invoices.payment-proof.show', $this);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
