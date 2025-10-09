<div align="center">
  <img src="public/vodeco.webp" alt="Vodeco Logo" width="200"/>
  <h1>Vodeco Keuangan</h1>
  <p>Aplikasi manajemen keuangan terintegrasi untuk pribadi, freelancer, dan UMKM.</p>
</div>

<!-- Badges -->
<div align="center">
  <!-- GitHub Actions CI -->
  <a href="https://github.com/Vodeco/vodeco-keuangan/actions/workflows/ci.yml">
    <img src="https://github.com/Vodeco/vodeco-keuangan/actions/workflows/ci.yml/badge.svg" alt="Build Status">
  </a>
  <!-- License -->
  <a href="LICENSE">
    <img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="MIT License">
  </a>
  <!-- Laravel Version -->
  <a href="https://laravel.com">
    <img src="https://img.shields.io/badge/Laravel-v12.0-FF2D20.svg?style=flat&logo=laravel" alt="Laravel v12.0">
  </a>
  <!-- PHP Version -->
  <a href="https://www.php.net">
    <img src="https://img.shields.io/badge/PHP-%5E8.2-777BB4.svg?style=flat&logo=php" alt="PHP ^8.2">
  </a>
</div>

---

## 📜 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Unggulan](#-fitur-unggulan)
- [Peran Pengguna & Hak Akses](#-peran-pengguna--hak-akses)
- [Modul & Alur Bisnis](#-modul--alur-bisnis)
- [Integrasi & Automasi](#-integrasi--automasi)
- [Tumpukan Teknologi](#-tumpukan-teknologi)
- [Panduan Memulai](#-panduan-memulai)
  - [Prasyarat](#prasyarat)
  - [Instalasi](#instalasi)
- [Konfigurasi Lingkungan](#-konfigurasi-lingkungan)
- [Data Contoh & Akun Demo](#-data-contoh--akun-demo)
- [Operasional Harian](#-operasional-harian)
- [Pengujian & Kualitas](#-pengujian--kualitas)
- [Deployment](#-deployment)
- [Berkontribusi](#-berkontribusi)
- [Lisensi](#-lisensi)

---

## 📖 Tentang Proyek

**Vodeco Keuangan** adalah aplikasi web untuk mengelola arus kas, utang-piutang, dan dokumen bisnis secara menyeluruh. Sistem ini menyatukan pencatatan transaksi, penagihan, dan pelaporan keuangan sehingga tim finansial dapat bekerja lebih cepat dan akurat.【F:routes/web.php†L24-L95】

Aplikasi dibangun dengan Laravel 12 dan antarmuka modern yang responsif. Fokus utama kami adalah kejelasan data, otomasi proses keuangan, dan audit trail yang kuat.【F:composer.json†L5-L44】【F:app/Models/ActivityLog.php†L5-L20】

---

## ✨ Fitur Unggulan

- **Dashboard Interaktif** – Ringkasan keuangan real-time yang dapat difilter berdasarkan bulan atau rentang tanggal.【F:routes/web.php†L33-L36】
- **Manajemen Transaksi Lengkap** – Pencatatan pemasukan/pengeluaran, unggah bukti transaksi, dan permintaan penghapusan dengan persetujuan berjenjang.【F:app/Http/Controllers/TransactionController.php†L33-L158】【F:app/Http/Controllers/UserDeletionRequestController.php†L9-L23】
- **Invoice Digital** – Pembuatan invoice publik & internal, PDF otomatis, pelacakan status, dan sinkronisasi ke utang/piutang serta transaksi saat pelunasan.【F:app/Http/Controllers/InvoiceController.php†L22-L237】
- **Utang & Piutang** – Pencatatan kewajiban, riwayat pembayaran, dan integrasi otomatis dengan pembayaran invoice.【F:app/Http/Controllers/InvoiceController.php†L200-L237】【F:routes/web.php†L43-L64】
- **Laporan Keuangan** – Ekspor laporan transaksi ke Excel (XLSX) dengan filter periode untuk analisis lanjutan.【F:routes/web.php†L66-L75】【F:app/Http/Controllers/SettingController.php†L90-L133】
- **Pengaturan Fleksibel** – Konfigurasi tema, notifikasi, penyimpanan bukti (server maupun Google Drive), dan ekspor data langsung dari UI.【F:app/Http/Controllers/SettingController.php†L17-L133】
- **Audit Trail & Keamanan** – Log aktivitas admin, workflow persetujuan, dan notifikasi email/database untuk setiap aksi penting.【F:routes/web.php†L96-L112】【F:app/Notifications/TransactionApproved.php†L8-L44】

---

## 👥 Peran Pengguna & Hak Akses

| Peran        | Akses Utama |
| :----------- | :---------- |
| **Admin**    | Semua modul termasuk manajemen pengguna, persetujuan penghapusan, dan audit log.【F:routes/web.php†L96-L112】 |
| **Accountant** | Dashboard, transaksi, kategori, utang/piutang, invoice, laporan, pengaturan, dan ekspor data.【F:routes/web.php†L30-L88】 |
| **Staff**    | Pencatatan transaksi, pengajuan penghapusan, pengelolaan invoice personal, dan akses modul pendukung.【F:routes/web.php†L30-L88】 |

Autorisasi dijalankan melalui middleware `role` serta kebijakan per sumber daya (`authorizeResource`) untuk memastikan setiap aksi sesuai hak akses pengguna.【F:routes/web.php†L30-L112】【F:app/Http/Controllers/InvoiceController.php†L22-L27】

---

## 🧩 Modul & Alur Bisnis

### Dashboard
Visualisasi arus kas, saldo bersih, dan metrik lainnya dengan opsi filter otomatis berdasarkan bulan berjalan atau rentang tanggal khusus.【F:app/Http/Controllers/TransactionController.php†L36-L82】

### Transaksi
- CRUD transaksi pemasukan/pengeluaran dengan validasi ketat dan pemisahan kategori pemasukan/pengeluaran.
- Unggah bukti transaksi yang disimpan aman di server atau Google Drive melalui `TransactionProofService`.
- Permintaan penghapusan transaksi disalurkan ke admin untuk persetujuan dengan notifikasi ke pengaju.【F:app/Http/Controllers/TransactionController.php†L83-L166】【F:app/Notifications/TransactionApproved.php†L8-L44】

### Kategori
Pengelompokan transaksi dan utang dalam kategori dinamis, dicache untuk performa optimal.【F:routes/web.php†L38-L42】【F:app/Http/Controllers/TransactionController.php†L43-L82】

### Invoice
- Pembuatan invoice internal/public, generasi nomor otomatis, dan dukungan multi-item.
- Portal publik untuk membuat invoice oleh mitra, serta tautan publik untuk melihat PDF tanpa login.【F:app/Http/Controllers/InvoiceController.php†L39-L192】
- Pembayaran invoice otomatis memperbarui status, mencatat down payment/lunas, membuat transaksi pemasukan, dan menjaga konsistensi data utang.【F:app/Http/Controllers/InvoiceController.php†L192-L237】

### Utang & Piutang
Pencatatan kewajiban dan piutang usaha lengkap dengan preferensi kategori dan workflow pembayaran/penandaan gagal bayar.【F:routes/web.php†L43-L64】

### Laporan
Halaman laporan hanya untuk Admin & Accountant dengan ekspor Excel melalui `Maatwebsite\Excel`. Format file dan rentang tanggal dapat disesuaikan dari UI.【F:routes/web.php†L66-L75】【F:app/Http/Controllers/SettingController.php†L90-L133】

### Pengaturan Aplikasi
Kelola tema antarmuka, pengingat email/database, konfigurasi penyimpanan bukti (server vs Google Drive), dan ekspor data masif dari satu tempat.【F:app/Http/Controllers/SettingController.php†L17-L133】

### Layanan Pelanggan & CRM Ringan
Kelola daftar kontak customer service untuk memetakan invoice ke tim penanggung jawab serta menyediakan portal publik terpisah untuk pembuatan invoice.【F:app/Http/Controllers/CustomerServiceController.php†L11-L31】【F:app/Http/Controllers/InvoiceController.php†L43-L107】

### Permintaan Penghapusan & Audit Log
Pengguna dapat memonitor status permintaan penghapusan transaksi, sementara admin mengelola antrian persetujuan dan meninjau log aktivitas lengkap.【F:app/Http/Controllers/UserDeletionRequestController.php†L9-L23】【F:routes/web.php†L96-L112】【F:app/Models/ActivityLog.php†L5-L20】

---

## 🔁 Integrasi & Automasi

- **PDF Generator** – DomPDF digunakan untuk membuat invoice berformat A4 siap unduh/tayang publik.【F:app/Http/Controllers/InvoiceController.php†L18-L190】
- **Ekspor Excel** – Ekspor transaksi (per rentang tanggal) via `TransactionsExport` untuk backup dan analisis lanjutan.【F:app/Http/Controllers/SettingController.php†L90-L133】
- **Penyimpanan Bukti Fleksibel** – Dukungan server lokal maupun Google Drive dengan token akses publik/privat untuk bukti transaksi.【F:app/Models/Transaction.php†L41-L104】
- **Notifikasi Dua Kanal** – Setiap permintaan penghapusan menghasilkan notifikasi email + database sehingga mudah dilacak.【F:app/Notifications/TransactionApproved.php†L18-L44】
- **Antrian Proses** – Semua notifikasi dan tugas berat siap dijalankan melalui driver `database` sehingga aman untuk beban besar.【F:.env.example†L30-L46】

---

## 🛠️ Tumpukan Teknologi

| Kategori      | Teknologi |
| :------------ | :-------- |
| **Backend**   | Laravel 12, PHP 8.2, Database SQL (MySQL/PostgreSQL/SQLite).【F:composer.json†L5-L33】 |
| **Frontend**  | Vite, Tailwind CSS, Alpine.js untuk antarmuka dinamis dan responsif.【F:package.json†L1-L23】 |
| **DevOps**    | GitHub Actions untuk CI/CD, Docker (opsional), Laravel Sail untuk lingkungan containerized.【F:composer.json†L35-L70】 |
| **Testing**   | PHPUnit 11, Laravel Test Runner dengan konfigurasi database terisolasi.【F:composer.json†L53-L63】 |

---

## 🚀 Panduan Memulai

Ikuti langkah berikut untuk menyiapkan proyek secara lokal.

### Prasyarat

- PHP >= 8.2 beserta ekstensi standar Laravel.
- Composer terbaru.
- Node.js & NPM.
- Server database (MySQL, PostgreSQL, atau SQLite).

### Instalasi

1. **Clone repositori & masuk ke folder proyek**
   ```sh
   git clone https://github.com/Vodeco/vodeco-keuangan.git
   cd vodeco-keuangan
   ```
2. **Salin file environment**
   ```sh
   cp .env.example .env
   ```
3. **Install dependensi PHP**
   ```sh
   composer install
   ```
4. **Install dependensi JavaScript**
   ```sh
   npm install
   ```
5. **Generate application key**
   ```sh
   php artisan key:generate
   ```
6. **Konfigurasi database di `.env`**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=vodeco_keuangan
   DB_USERNAME=root
   DB_PASSWORD=
   ```
7. **(Opsional) Aktifkan integrasi Google Drive**
   - Buat service account di Google Cloud, unduh kredensial JSON, dan isi variabel berikut:
     ```env
     GOOGLE_DRIVE_SERVICE_ACCOUNT_CREDENTIALS=/path/to/service-account.json
     GOOGLE_DRIVE_IMPERSONATE_USER=
     GOOGLE_DRIVE_TEAM_DRIVE_ID=
     ```
   - Berikan akses ke folder tujuan di Google Drive dan catat ID foldernya.
8. **Jalankan migrasi database**
   ```sh
   php artisan migrate
   ```
9. **(Opsional) Isi data contoh**
   ```sh
   php artisan db:seed
   ```
10. **Jalankan Vite dev server**
    ```sh
    npm run dev
    ```
11. **Jalankan server aplikasi**
    ```sh
    php artisan serve
    ```

Aplikasi kini tersedia di `http://127.0.0.1:8000`.

---

## ⚙️ Konfigurasi Lingkungan

Sesuaikan variabel berikut di `.env`:

- `APP_URL`, `APP_LOCALE` – URL dasar dan lokal aplikasi.
- `SESSION_DRIVER=database` & `QUEUE_CONNECTION=database` – pastikan migrasi session dan queue dijalankan (`php artisan session:table`, `queue:table`).【F:.env.example†L18-L46】
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_*` – konfigurasi email produksi untuk notifikasi invoice & penghapusan transaksi.【F:.env.example†L47-L55】
- `FILESYSTEM_DISK`, `GOOGLE_DRIVE_*` – pilih lokasi penyimpanan bukti transaksi (lokal atau Google Drive).【F:.env.example†L31-L46】
- `CACHE_STORE`, `REDIS_*` – sesuaikan jika menggunakan cache selain database.【F:.env.example†L31-L46】

Jangan lupa menjalankan `php artisan storage:link` apabila menggunakan filesystem lokal untuk bukti transaksi.

---

## 🧪 Data Contoh & Akun Demo

Menjalankan `php artisan migrate --seed` akan membuat akun berikut:

| Peran | Email | Kata Sandi |
| :---- | :---- | :--------- |
| Admin | `admin@vodeco.co.id` | `masukaja` |
| Staff | `staff@vodeco.co.id` | `masukaja` |
| Accountant | `accountant@vodeco.co.id` | `masukaja` |

Seeder juga menambahkan kategori dasar untuk pemasukan dan pengeluaran sehingga Anda dapat langsung mencoba fitur transaksi dan invoice.【F:database/seeders/UserSeeder.php†L15-L39】

---

## 🧭 Operasional Harian

- **Mode Dev All-in-One** – Jalankan server Laravel, queue listener, live log, dan Vite sekaligus:
  ```sh
  composer dev
  ```
  Perintah ini menggunakan `concurrently` untuk menjaga workflow terintegrasi.【F:composer.json†L71-L85】【F:package.json†L6-L23】
- **Queue Worker** – Untuk produksi, jalankan `php artisan queue:work` agar notifikasi & pekerjaan berat terselesaikan tepat waktu.
- **Pembersihan Cache** – Gunakan `php artisan cache:clear` dan `php artisan config:clear` saat mengganti konfigurasi penting.
- **Backup Data** – Manfaatkan ekspor Excel pada menu Pengaturan → Manajemen Data untuk mengambil snapshot transaksi.【F:app/Http/Controllers/SettingController.php†L90-L133】

---

## ✅ Pengujian & Kualitas

- Jalankan pengujian PHP lengkap:
  ```sh
  php artisan test
  ```
- Gunakan skrip composer untuk membersihkan konfigurasi dan menjalankan test terautomasi:
  ```sh
  composer test
  ```
- Gunakan `npm run build` sebelum rilis untuk memastikan aset frontend siap produksi.

---

## 🚢 Deployment

Saat melakukan deploy ke hosting/production, jalankan perintah berikut untuk memperbarui skema database dan menyegarkan cache Laravel:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Perintah `optimize:clear` akan menghapus cache konfigurasi, rute, event, dan view. Langkah ini penting terutama setelah menambahkan rute atau fitur baru seperti pengelolaan paket pass through. Tanpa membersihkan cache rute, Laravel tidak akan mengenali rute baru sehingga form atau tombol terkait bisa hilang di lingkungan produksi meskipun di lokal berfungsi normal.

---

## 🤝 Berkontribusi

Kontribusi Anda sangat berarti! Silakan fork repositori ini dan ajukan *pull request* untuk perbaikan atau fitur baru. Panduan singkat:

1. Fork repositori ini.
2. Buat branch fitur (`git checkout -b feature/FiturBaru`).
3. Commit perubahan (`git commit -m "feat: tambahkan fitur X"`).
4. Push ke branch (`git push origin feature/FiturBaru`).
5. Ajukan pull request dan jelaskan perubahan Anda secara ringkas.

Laporkan bug atau ide perbaikan melalui tab Issues. Jangan lupa berikan ⭐ pada proyek ini jika Anda merasa terbantu!

---

## 📄 Lisensi

Proyek ini dirilis di bawah Lisensi MIT. Lihat berkas [`LICENSE`](LICENSE) untuk detail lengkapnya.
