<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'user_id',
        'amount',
        'description',
        'date',
        'proof_disk',
        'proof_directory',
        'proof_path',
        'proof_filename',
        'proof_original_name',
        'proof_remote_id',
        'proof_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Mendapatkan kategori dari transaksi.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Mendapatkan user yang membuat transaksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Method getSummary() dihapus dari sini dan dipindahkan ke TransactionService
    // untuk pemisahan tanggung jawab (separation of concerns) yang lebih baik.

    public function getProofUrlAttribute(): ?string
    {
        if (!$this->proof_path) {
            return null;
        }

        $fullPath = $this->proof_full_path;

        if ($this->proof_disk === 'drive') {
            if ($this->proof_remote_id) {
                return 'https://drive.google.com/file/d/'.$this->proof_remote_id.'/view?usp=drivesdk';
            }

            $baseLink = Setting::get('transaction_proof_drive_link');

            if ($baseLink && $this->proof_path) {
                return rtrim($baseLink, '/').'/'.ltrim($this->proof_path, '/');
            }

            return null;
        }

        if ($this->proof_disk === 'local') {
            if ($this->proof_token) {
                return route('transactions.proof.show', ['transaction' => $this->proof_token]);
            }

            return null;
        }

        if ($this->proof_disk && in_array($this->proof_disk, array_keys(config('filesystems.disks', [])), true) && $fullPath) {
            return Storage::disk($this->proof_disk)->url($fullPath);
        }

        return null;
    }

    public function getProofFullPathAttribute(): ?string
    {
        if (!$this->proof_path) {
            return null;
        }

        $path = ltrim($this->proof_path, '/');
        $directory = trim((string) $this->proof_directory, '/');

        return $directory ? $directory.'/'.$path : $path;
    }
}
