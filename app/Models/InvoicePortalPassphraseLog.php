<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePortalPassphraseLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_portal_passphrase_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    public function passphrase(): BelongsTo
    {
        return $this->belongsTo(InvoicePortalPassphrase::class, 'invoice_portal_passphrase_id');
    }
}
