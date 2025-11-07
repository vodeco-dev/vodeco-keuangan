# 🚀 PDF Optimization - Quick Start

## ✅ Implementasi Selesai!

Sistem optimasi PDF sudah berhasil diimplementasikan dengan fitur:

- ✅ **Generate PDF On-The-Fly** - PDF di-generate saat dibutuhkan
- ✅ **Smart Caching** - Cache dengan TTL untuk performa
- ✅ **Auto Cleanup** - Cleanup otomatis setiap jam
- ✅ **Configurable** - Mudah dikonfigurasi via .env
- ✅ **Backward Compatible** - Masih support mode persistent

---

## 📋 Quick Start

### 1. Update Environment Variables

Tambahkan ke file `.env`:

```env
# PDF Settings (sudah optimal secara default)
PDF_GENERATION_STRATEGY=on_demand
PDF_CACHE_ENABLED=true
PDF_CACHE_TTL=1440
```

### 2. Buat Directory Cache (jika belum ada)

```bash
mkdir -p storage/app/public/invoices/cache
chmod -R 775 storage/app/public/invoices
```

### 3. Test Manual Cleanup

```bash
# Dry run (lihat apa yang akan dihapus)
php artisan pdf:cleanup-cache --dry-run

# Actual cleanup
php artisan pdf:cleanup-cache
```

### 4. Pastikan Scheduler Berjalan

Cleanup akan berjalan otomatis setiap jam. Pastikan cron sudah di-setup:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🎯 Cara Kerja

### Mode: On-Demand (Default ✅)

```
Akses Invoice → Cek Cache → Generate jika perlu → Serve PDF
                    ↓
            (Auto-expire 24 jam)
                    ↓
            Cleanup setiap jam
```

**Keuntungan:**
- 💾 Hemat 90% storage
- 🔄 Selalu up-to-date
- 🧹 Auto cleanup

### Mode: Persistent (Legacy)

```
Buat Invoice → Generate PDF → Simpan Permanen → Serve PDF
```

**Untuk mengaktifkan:**
```env
PDF_GENERATION_STRATEGY=persistent
```

---

## 📊 Estimasi Penghematan

| Jumlah Invoice | Storage Sebelum | Storage Setelah | Penghematan |
|----------------|-----------------|-----------------|-------------|
| 1,000          | 500 MB          | 50 MB           | **90%** 🎉  |
| 10,000         | 5 GB            | 500 MB          | **90%** 🎉  |
| 100,000        | 50 GB           | 5 GB            | **90%** 🎉  |

*Asumsi: Cache TTL 24 jam, 10% invoice diakses dalam 24 jam terakhir*

---

## 🛠️ Commands

### Cleanup PDF Cache

```bash
# Normal cleanup (hapus yang expired)
php artisan pdf:cleanup-cache

# Dry run (preview tanpa menghapus)
php artisan pdf:cleanup-cache --dry-run

# Force cleanup (hapus semua)
php artisan pdf:cleanup-cache --force
```

### Monitor Cache Size

```bash
# Lihat ukuran cache
du -sh storage/app/public/invoices/cache/

# Lihat jumlah file
ls -1 storage/app/public/invoices/cache/ | wc -l
```

---

## ⚙️ Konfigurasi Lanjutan

Sesuaikan di `.env` sesuai kebutuhan:

```env
# Cache TTL (dalam menit)
# High traffic: 60-120
# Medium traffic: 360-720
# Low traffic: 1440 (24 jam)
PDF_CACHE_TTL=1440

# Path cache (bisa diubah)
PDF_CACHE_PATH=invoices/cache

# Disable caching (jika perlu)
PDF_CACHE_ENABLED=false
```

---

## 🔍 Troubleshooting

### PDF tidak muncul?

```bash
# Cek permission
ls -la storage/app/public/

# Cek symbolic link
php artisan storage:link

# Cek logs
tail -f storage/logs/laravel.log
```

### Cache tidak terhapus?

```bash
# Cek scheduler
php artisan schedule:list

# Test manual cleanup
php artisan pdf:cleanup-cache -v
```

### Memory error?

```php
// Kurangi TTL di .env
PDF_CACHE_TTL=60
```

---

## 📚 Dokumentasi Lengkap

Lihat dokumentasi lengkap di: [`docs/pdf-optimization.md`](docs/pdf-optimization.md)

Dokumentasi mencakup:
- ✅ Penjelasan detail setiap fitur
- ✅ Workflow diagram
- ✅ Migration guide
- ✅ Best practices
- ✅ Technical details

---

## 🎓 File yang Diubah/Dibuat

### File Baru:
- ✅ `config/pdf.php` - Konfigurasi PDF
- ✅ `app/Console/Commands/CleanupPdfCache.php` - Command cleanup
- ✅ `docs/pdf-optimization.md` - Dokumentasi lengkap
- ✅ `PDF_OPTIMIZATION_README.md` - Quick start guide

### File Dimodifikasi:
- ✅ `app/Services/InvoicePdfService.php` - Support caching & on-demand
- ✅ `app/Http/Controllers/InvoiceController.php` - Support strategy baru
- ✅ `app/Observers/InvoiceObserver.php` - Auto invalidate cache
- ✅ `app/Console/Kernel.php` - Scheduled cleanup
- ✅ `.env.example` - Tambah konfigurasi PDF

---

## ✨ Status Update

**Apakah status di PDF akan berubah?**
✅ Ya, PDF selalu update dengan data terbaru saat diakses

**Dalam 1 file?**
✅ Ya, setiap invoice punya 1 file cache yang di-update sesuai perubahan

**PDF disimpan di hosting?**
✅ Hanya cache sementara (24 jam default), bukan permanen

**Cara optimal agar tidak disimpan?**
✅ Sudah diimplementasikan! On-demand strategy + auto cleanup

---

## 🎉 Selesai!

Sistem sudah siap digunakan. PDF akan:
1. ✅ Di-generate on-the-fly saat diakses
2. ✅ Di-cache untuk performa (24 jam)
3. ✅ Auto-cleanup setiap jam
4. ✅ Hemat 90% storage

**Tidak perlu action tambahan!** Sistem sudah berjalan otomatis. 🚀

---

**Questions?** Baca dokumentasi lengkap di `docs/pdf-optimization.md`
