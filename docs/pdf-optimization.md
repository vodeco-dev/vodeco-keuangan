# 📄 Optimasi PDF Invoice - Dokumentasi

## 🎯 Tujuan

Mengurangi penggunaan storage pada hosting dengan mengimplementasikan sistem generate PDF on-the-fly dengan caching cerdas.

## ⚙️ Fitur Utama

### 1. **Generate On-The-Fly**
- PDF di-generate saat dibutuhkan (on-demand)
- Tidak menyimpan file permanen di hosting
- Menghemat storage secara signifikan

### 2. **Smart Caching**
- Cache PDF sementara untuk akses berulang
- TTL (Time-To-Live) configurable
- Auto-invalidate saat invoice di-update

### 3. **Auto Cleanup**
- Background job membersihkan cache expired
- Berjalan otomatis setiap jam
- Bisa dijalankan manual via command

## 🔧 Konfigurasi

### Environment Variables

Tambahkan ke file `.env`:

```env
# PDF Cache Settings
PDF_CACHE_ENABLED=true              # Enable/disable PDF caching
PDF_CACHE_TTL=1440                  # Cache TTL dalam menit (default: 24 jam)
PDF_CACHE_DISK=public               # Disk untuk menyimpan cache
PDF_CACHE_PATH=invoices/cache       # Path untuk cache files

# PDF Generation Strategy
PDF_GENERATION_STRATEGY=on_demand   # Strategi: 'on_demand' atau 'persistent'
PDF_PAPER_SIZE=a4                   # Ukuran kertas PDF
```

### Config File

File konfigurasi ada di `config/pdf.php`:

```php
return [
    'cache' => [
        'enabled' => env('PDF_CACHE_ENABLED', true),
        'ttl' => env('PDF_CACHE_TTL', 1440),
        'disk' => env('PDF_CACHE_DISK', 'public'),
        'path' => env('PDF_CACHE_PATH', 'invoices/cache'),
    ],
    'generation' => [
        'strategy' => env('PDF_GENERATION_STRATEGY', 'on_demand'),
        'paper' => env('PDF_PAPER_SIZE', 'a4'),
    ],
];
```

## 📋 Strategi Generation

### On-Demand (Direkomendasikan ✅)

**Konfigurasi:**
```env
PDF_GENERATION_STRATEGY=on_demand
PDF_CACHE_ENABLED=true
PDF_CACHE_TTL=1440
```

**Keuntungan:**
- ✅ Hemat storage drastis
- ✅ Selalu up-to-date
- ✅ Auto cleanup cache lama
- ✅ Cache untuk performa

**Cara Kerja:**
1. PDF di-generate saat pertama kali diakses
2. Disimpan di cache dengan TTL
3. Request berikutnya menggunakan cache
4. Jika invoice di-update, cache di-invalidate
5. Cache expired dibersihkan otomatis setiap jam

### Persistent (Legacy)

**Konfigurasi:**
```env
PDF_GENERATION_STRATEGY=persistent
```

**Keuntungan:**
- ✅ Akses super cepat
- ✅ File permanen

**Kekurangan:**
- ❌ Menggunakan banyak storage
- ❌ File lama tidak terhapus otomatis

## 🛠️ Artisan Commands

### 1. Cleanup PDF Cache

Membersihkan cache PDF yang sudah expired:

```bash
# Normal cleanup (hapus yang expired saja)
php artisan pdf:cleanup-cache

# Dry run (lihat apa yang akan dihapus tanpa menghapus)
php artisan pdf:cleanup-cache --dry-run

# Force cleanup (hapus semua cache)
php artisan pdf:cleanup-cache --force
```

**Output Example:**
```
Starting PDF cache cleanup...
Disk: public
Cache Path: invoices/cache
TTL: 1440 minutes

Found 15 cached PDF file(s)

Deleted: invoice-001-abc123.pdf - Expired (age: 1500 minutes)
Keeping: invoice-002-def456.pdf - Still valid (age: 200 minutes)
...

Cleanup Summary:
- Total files found: 15
- Files deleted: 8
- Files skipped: 7
```

### 2. Scheduled Cleanup

Cleanup berjalan otomatis setiap jam via Laravel scheduler.

Pastikan cron sudah di-setup:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🔄 Alur Kerja (Workflow)

### Skenario 1: Akses Invoice Pertama Kali

```
User Request → Controller → InvoicePdfService
                                ↓
                        Generate PDF baru
                                ↓
                        Simpan ke cache
                                ↓
                        Return PDF ke user
```

### Skenario 2: Akses Invoice (Cache Masih Valid)

```
User Request → Controller → InvoicePdfService
                                ↓
                        Cek cache exists?
                                ↓ (Yes)
                        Cek cache valid?
                                ↓ (Yes)
                        Return cached PDF
```

### Skenario 3: Invoice Di-Update

```
Invoice Updated → InvoiceObserver
                        ↓
                Invalidate cache
                        ↓
                Cache dihapus
                        ↓
        (Next request akan generate baru)
```

### Skenario 4: Auto Cleanup

```
Hourly Scheduler → CleanupPdfCache Command
                            ↓
                    Scan cache directory
                            ↓
                    Cek TTL setiap file
                            ↓
                    Hapus yang expired
                            ↓
                    Clean metadata
```

## 📊 Estimasi Penghematan Storage

### Sebelum Optimasi (Persistent):

```
100 invoice × 500 KB/invoice = 50 MB
1000 invoice × 500 KB/invoice = 500 MB (0.5 GB)
10000 invoice × 500 KB/invoice = 5 GB
```

### Setelah Optimasi (On-Demand + Cache):

**Asumsi:**
- Cache TTL: 24 jam
- 10% invoice diakses dalam 24 jam terakhir

```
1000 invoice total
100 invoice ter-cache (10%)
100 × 500 KB = 50 MB

Penghematan: 500 MB - 50 MB = 450 MB (90% lebih hemat!)
```

**Untuk 10,000 invoice:**
```
10000 invoice total
1000 invoice ter-cache (10%)
1000 × 500 KB = 500 MB

Penghematan: 5 GB - 0.5 GB = 4.5 GB (90% lebih hemat!)
```

## 🔍 Monitoring & Troubleshooting

### Cek Status Cache

```bash
# Lihat jumlah file cache
ls -lh storage/app/public/invoices/cache/

# Cek ukuran cache directory
du -sh storage/app/public/invoices/cache/
```

### Debug Mode

Tambahkan logging untuk debug:

```php
// Di .env
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Common Issues

#### 1. PDF tidak ter-generate

**Solusi:**
- Cek permission folder `storage/app/public/`
- Jalankan: `php artisan storage:link`
- Cek logs: `storage/logs/laravel.log`

#### 2. Cache tidak terhapus otomatis

**Solusi:**
- Cek cron scheduler berjalan: `php artisan schedule:list`
- Jalankan manual: `php artisan pdf:cleanup-cache`

#### 3. Memory Error saat generate PDF

**Solusi:**
- Tingkatkan PHP memory limit di `php.ini`
- Atau tambahkan di `.env`: `PDF_CACHE_TTL=60` (kurangi TTL)

## 🚀 Migration dari Persistent ke On-Demand

Jika saat ini menggunakan strategi persistent dan ingin migrasi:

### Step 1: Backup

```bash
# Backup PDF files yang ada
tar -czf invoice-pdfs-backup.tar.gz storage/app/public/invoices/
```

### Step 2: Update Config

```env
# Ubah di .env
PDF_GENERATION_STRATEGY=on_demand
PDF_CACHE_ENABLED=true
PDF_CACHE_TTL=1440
```

### Step 3: Clear Old PDFs (Optional)

```bash
# Hapus PDF lama (setelah yakin sistem baru berjalan baik)
php artisan tinker
> Storage::disk('public')->deleteDirectory('invoices');
> Storage::disk('public')->makeDirectory('invoices/cache');
```

### Step 4: Test

```bash
# Test generate PDF
# Akses invoice via browser dan cek apakah PDF ter-generate

# Test cleanup
php artisan pdf:cleanup-cache --dry-run
```

## 📈 Best Practices

1. **Set TTL sesuai usage pattern:**
   - High traffic: 60-120 menit
   - Medium traffic: 360-720 menit (6-12 jam)
   - Low traffic: 1440 menit (24 jam)

2. **Monitor cache size secara berkala:**
   ```bash
   du -sh storage/app/public/invoices/cache/
   ```

3. **Backup regular** (untuk persistent files yang penting):
   ```bash
   php artisan backup:run
   ```

4. **Test di staging dulu** sebelum deploy ke production

5. **Setup monitoring alerts** untuk disk usage

## 🎓 Technical Details

### Cache Key Strategy

Cache path menggunakan MD5 hash dari:
- Invoice ID
- Invoice updated_at timestamp

Format: `{invoice-number}-{hash}.pdf`

Example: `INV-001-a1b2c3d4e5f6.pdf`

Ini memastikan:
- ✅ Unique per invoice
- ✅ Auto-invalidate saat update (timestamp berubah)
- ✅ Mudah di-trace

### Cleanup Algorithm

```
FOR each file in cache directory:
    age = current_time - file_modified_time
    
    IF age > TTL:
        DELETE file
        DELETE metadata from cache
    ELSE:
        KEEP file
```

### Performance Impact

- **Generate PDF:** ~500ms - 2s (tergantung kompleksitas)
- **Serve Cached PDF:** ~50ms - 200ms
- **Overhead caching:** ~10ms - 50ms

## 📞 Support

Jika ada pertanyaan atau issue:

1. Cek dokumentasi ini
2. Cek logs: `storage/logs/laravel.log`
3. Jalankan debug command: `php artisan pdf:cleanup-cache --dry-run`

---

**Last Updated:** 2025-11-07
**Version:** 1.0.0
