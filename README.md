<div align="center">
  <img src="public/vodeco.webp" alt="Vodeco Logo" width="200"/>
  <h1>Vodeco Keuangan</h1>
  <p>Aplikasi manajemen keuangan terpadu untuk individu, freelancer, dan UMKM.</p>
</div>

---

## 📚 Ringkasan

Vodeco Keuangan membantu tim keuangan mencatat transaksi, menerbitkan invoice, mengelola utang-piutang, serta memantau laporan dalam satu tempat. Aplikasi ini dibangun dengan Laravel 12, Vite, dan Tailwind CSS sehingga mudah dikembangkan dan di-deploy.

---

## 📑 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Arsitektur Singkat](#-arsitektur-singkat)
- [Persiapan Lingkungan](#-persiapan-lingkungan)
- [Langkah Instalasi Cepat](#-langkah-instalasi-cepat)
- [Konfigurasi Penting](#-konfigurasi-penting)
- [Data Contoh & Akun Demo](#-data-contoh--akun-demo)
- [Alur Kerja Harian](#-alur-kerja-harian)
- [Pengujian](#-pengujian)
- [Deployment](#-deployment)
- [Kontribusi](#-kontribusi)
- [Lisensi](#-lisensi)

---

## ✨ Fitur Utama

- **Dashboard Interaktif** – Ringkasan arus kas dan metrik utama dengan filter rentang tanggal.
- **Manajemen Transaksi** – Catat pemasukan/pengeluaran, unggah bukti, dan ajukan penghapusan dengan persetujuan berjenjang.
- **Invoice Digital** – Pembuatan invoice internal/publik lengkap dengan PDF otomatis dan penelusuran status.
- **Utang & Piutang** – Pantau kewajiban dan piutang, termasuk riwayat pembayaran dan sinkronisasi dengan invoice.
- **Laporan Keuangan** – Ekspor laporan transaksi ke Excel untuk analisis lanjutan.
- **Pengaturan & Automasi** – Kelola tema, notifikasi, penyimpanan bukti, serta audit trail aktivitas pengguna.

---

## 🧱 Arsitektur Singkat

| Lapisan       | Teknologi & Keterangan |
| ------------- | ---------------------- |
| **Backend**   | Laravel 12, PHP 8.2, database relasional (MySQL/PostgreSQL/SQLite). |
| **Frontend**  | Vite, Tailwind CSS, Alpine.js untuk antarmuka reaktif. |
| **Integrasi** | DomPDF untuk PDF invoice, Maatwebsite Excel untuk ekspor, penyimpanan bukti di lokal/Google Drive. |
| **DevOps**    | Composer & NPM scripts, Laravel Sail/Docker (opsional), GitHub Actions untuk CI. |

Struktur direktori mengikuti standar Laravel sehingga pengembang baru dapat langsung mengenali letak controller, model, migrasi, dan komponen frontend.

---

## 🧰 Persiapan Lingkungan

Pastikan perangkat Anda memiliki:

- PHP ≥ 8.2 beserta ekstensi standar Laravel
- Composer versi terbaru
- Node.js & npm
- Server database (MySQL, PostgreSQL, atau SQLite)

---

## 🚀 Langkah Instalasi Cepat

1. **Clone repositori**
   ```bash
   git clone https://github.com/Vodeco/vodeco-keuangan.git
   cd vodeco-keuangan
   ```
2. **Salin konfigurasi dasar**
   ```bash
   cp .env.example .env
   ```
3. **Pasang dependensi PHP**
   ```bash
   composer install
   ```
4. **Pasang dependensi JavaScript**
   ```bash
   npm install
   ```
5. **Generate application key**
   ```bash
   php artisan key:generate
   ```
6. **Atur koneksi database** – ubah nilai `DB_*` di `.env` sesuai server Anda.
7. **(Opsional) Konfigurasi Google Drive** – isi variabel `GOOGLE_DRIVE_*` dengan kredensial service account.
8. **Jalankan migrasi**
   ```bash
   php artisan migrate
   ```
9. **(Opsional) Isi data contoh**
   ```bash
   php artisan db:seed
   ```
10. **Bangun aset frontend & jalankan dev server**
    ```bash
    npm run dev
    ```
11. **Jalankan aplikasi Laravel**
    ```bash
    php artisan serve
    ```

Aplikasi akan tersedia di `http://127.0.0.1:8000`.

---

## ⚙️ Konfigurasi Penting

Sesuaikan variabel berikut di `.env`:

- `APP_URL`, `APP_LOCALE` – URL dasar dan bahasa aplikasi.
- `SESSION_DRIVER`, `QUEUE_CONNECTION` – gunakan `database` dan jalankan migrasi terkait (`php artisan session:table`, `php artisan queue:table`, lalu `php artisan migrate`).
- `MAIL_*` – pengaturan SMTP untuk notifikasi invoice dan persetujuan transaksi.
- `FILESYSTEM_DISK` dan `GOOGLE_DRIVE_*` – pilih lokasi penyimpanan bukti transaksi.
- `CACHE_STORE` atau `REDIS_*` – aktifkan cache sesuai kebutuhan.

Jangan lupa menjalankan `php artisan storage:link` jika memakai penyimpanan lokal untuk bukti transaksi.

---

## 🧪 Data Contoh & Akun Demo

Menjalankan `php artisan migrate --seed` akan membuat akun berikut:

| Peran       | Email                     | Kata Sandi |
| ----------- | ------------------------- | ---------- |
| Admin       | `admin@vodeco.co.id`      | `masukaja` |
| Staff       | `staff@vodeco.co.id`      | `masukaja` |
| Accountant  | `accountant@vodeco.co.id` | `masukaja` |

Seeder juga menambahkan kategori transaksi dasar sehingga fitur dapat langsung dicoba.

---

## 🧭 Alur Kerja Harian

- **Mode pengembangan terpadu** – jalankan semua layanan pendukung dengan:
  ```bash
  composer dev
  ```
- **Queue worker** – pada produksi, pastikan `php artisan queue:work` berjalan agar notifikasi terkirim tepat waktu.
- **Pembersihan cache** – gunakan `php artisan cache:clear` dan `php artisan config:clear` setelah mengubah konfigurasi penting.
- **Backup data** – manfaatkan fitur ekspor Excel pada menu Pengaturan → Manajemen Data.

---

## ✅ Pengujian

- Jalankan pengujian Laravel lengkap:
  ```bash
  php artisan test
  ```
- Alternatif cepat dengan skrip Composer:
  ```bash
  composer test
  ```
- Sebelum rilis, pastikan aset frontend siap produksi:
  ```bash
  npm run build
  ```

---

## 🚢 Deployment

Saat deploy ke server produksi, jalankan perintah berikut setelah sinkronisasi kode:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Langkah ini memastikan skema database terbaru dan seluruh cache Laravel diperbarui.

---

## 🤝 Kontribusi

Kami menyambut kontribusi apa pun. Untuk memulai:

1. Fork repositori.
2. Buat branch fitur: `git checkout -b feature/NamaFitur`.
3. Commit perubahan: `git commit -m "feat: tambahkan NamaFitur"`.
4. Push branch ke origin.
5. Buka pull request dan jelaskan perubahan Anda.

Laporkan bug atau ide perbaikan melalui tab Issues. Jika proyek ini membantu, jangan ragu memberi ⭐.

---

## 📄 Lisensi

Proyek ini berlisensi MIT. Lihat berkas [`LICENSE`](LICENSE) untuk detail lengkap.
