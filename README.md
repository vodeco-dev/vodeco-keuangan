## **Dokumentasi Fitur dan Alur Penggunaan Aplikasi Keuangan**

Dokumentasi ini terbagi menjadi dua bagian utama: **Panduan Pengguna** yang ditujukan untuk pengguna akhir aplikasi, dan **Dokumentasi Teknis** untuk para *developer*.

-----

### **Bagian 1: Panduan Pengguna (User Guide)**

#### **1.1. Panduan Memulai (Getting Started)**

Selamat datang di aplikasi Vodeco Keuangan\! Panduan ini akan membantu Anda memulai penggunaan aplikasi, mulai dari pendaftaran hingga memahami fitur-fitur utama.

**Langkah 1: Registrasi Akun**

1.  Buka halaman utama aplikasi dan klik tombol "**Register**".
2.  Isi formulir pendaftaran dengan informasi yang diperlukan:
      * **Name**: Nama lengkap Anda.
      * **Email Address**: Alamat email yang akan digunakan untuk login.
      * **Password**: Kata sandi yang aman.
      * **Confirm Password**: Ulangi kata sandi Anda.
3.  Klik tombol "**Register**" untuk membuat akun Anda.

**Langkah 2: Login ke Aplikasi**

1.  Setelah registrasi berhasil, Anda akan diarahkan ke halaman login, atau Anda bisa mengaksesnya melalui URL `/login`.
2.  Masukkan **Email** dan **Password** yang telah Anda daftarkan.
3.  Klik tombol "**Log in**".

**Langkah 3: Mengenal Tampilan Utama (Dashboard)**
Setelah berhasil login, Anda akan disambut oleh halaman **Dashboard**. Di sini Anda dapat melihat ringkasan kondisi keuangan Anda, termasuk:

  * Grafik pemasukan dan pengeluaran.
  * Transaksi terbaru.
  * Status utang dan piutang.

#### **1.2. Dokumentasi per Fitur**

Berikut adalah penjelasan fitur-fitur utama yang tersedia di aplikasi:

**A. Manajemen Transaksi**
Fitur ini adalah inti dari aplikasi, tempat Anda mencatat semua pemasukan dan pengeluaran.

  * **Melihat Transaksi:**
      * Akses menu **"Transactions"** dari *sidebar*.
      * Di halaman ini, Anda akan melihat daftar semua transaksi yang pernah Anda catat, diurutkan berdasarkan tanggal terbaru.
  * **Menambah Transaksi Baru:**
    1.  Klik tombol **"+ Tambah Transaksi"**.
    2.  Isi formulir dengan detail berikut:
          * **Tanggal**: Tanggal transaksi terjadi.
          * **Kategori**: Pilih kategori yang sesuai (misal: "Gaji", "Biaya Operasional"). Kategori ini menentukan apakah transaksi tersebut adalah **Pemasukan** atau **Pengeluaran**.
          * **Jenis Biaya Layanan** (Opsional): Khusus untuk pemasukan, pilih jenisnya. Jika ini adalah pendapatan bersih agensi, biarkan kosong atau pilih "Agency Fee". Jika ini adalah uang titipan klien (misal: biaya iklan), pilih "Pass-Through".
          * **Jumlah (Rp)**: Nominal transaksi.
          * **Deskripsi**: Catatan singkat mengenai transaksi.
    3.  Klik "**Simpan**".

**B. Laporan Keuangan**
Fitur ini menyajikan visualisasi dan ringkasan data keuangan Anda dalam periode tertentu.

1.  Akses menu **"Reports"** dari *sidebar*.
2.  Anda dapat memfilter laporan berdasarkan rentang tanggal (**Tanggal Mulai** dan **Tanggal Selesai**).
3.  Laporan akan menampilkan:
      * **Total Pemasukan**: Jumlah semua pemasukan pada periode yang dipilih.
      * **Total Pengeluaran**: Jumlah semua pengeluaran pada periode yang dipilih.
      * **Selisih**: Perbedaan antara pemasukan dan pengeluaran.
      * **Agency Gross Income**: Pendapatan bersih agensi (Total Pemasukan dikurangi pemasukan *Pass-Through*).
      * **Grafik Pemasukan vs Pengeluaran**.

**C. Manajemen Utang**
Catat dan kelola semua utang (yang harus Anda bayar) dan piutang (yang harus orang lain bayar kepada Anda).

1.  Akses menu **"Debts"** dari *sidebar*.
2.  **Untuk Mencatat Utang/Piutang Baru:**
      * Isi formulir di bagian atas halaman.
      * **Tipe**: Pilih **"Utang"** (Anda berutang) atau **"Piutang"** (Anda memberi utang).
      * **Jumlah**: Nominal utang/piutang.
      * **Pemberi/Penerima**: Nama orang atau pihak terkait.
      * **Tanggal Jatuh Tempo**: Batas waktu pembayaran.
      * Klik **"Simpan"**.
3.  **Untuk Melunasi Utang/Piutang:**
      * Pada daftar utang/piutang, klik tombol **"Lunasi"** pada item yang relevan.
      * Konfirmasi pembayaran, dan statusnya akan berubah menjadi **"Lunas"**.

**D. Manajemen Invoice**
Buat dan kelola tagihan (*invoice*) untuk klien Anda secara profesional.

1.  Akses menu **"Invoices"** dari *sidebar*.
2.  Klik **"+ Buat Invoice Baru"**.
3.  Isi detail *invoice*: nama klien, nomor *invoice*, tanggal, dan tanggal jatuh tempo.
4.  Tambahkan item-item tagihan pada bagian **"Invoice Items"**, lengkap dengan deskripsi, jumlah, dan harga.
5.  Total akan dihitung otomatis. Klik **"Simpan"** untuk membuat *invoice*.

#### **1.3. Contoh Studi Kasus**

  * **Cara Mencatat Biaya Operasional Proyek A:**

    1.  Masuk ke menu **"Transactions"** -\> **"+ Tambah Transaksi"**.
    2.  Pilih **Kategori** "Biaya Operasional" (atau kategori pengeluaran lain yang sesuai).
    3.  Masukkan **Jumlah** biaya yang dikeluarkan.
    4.  Pada **Deskripsi**, tulis "Biaya operasional untuk Proyek A".
    5.  Klik **"Simpan"**.

  * **Langkah-Langkah Membuat Invoice untuk Klien B:**

    1.  Masuk ke menu **"Invoices"** -\> **"+ Buat Invoice Baru"**.
    2.  Isi **"Billed To"** dengan nama "Klien B".
    3.  Isi nomor *invoice* dan tanggal yang relevan.
    4.  Pada bagian **"Invoice Items"**, tambahkan baris baru.
    5.  Isi deskripsi (misal: "Jasa Pembuatan Website"), `Qty` (1), dan `Price` (misal: 5.000.000).
    6.  Klik **"Simpan"**.

-----

### **Bagian 2: Dokumentasi Teknis (Technical Documentation)**

#### **2.1. Dokumentasi API**

Saat ini, aplikasi ini dibangun sebagai aplikasi web monolitik (bukan berbasis API). Semua interaksi data terjadi melalui rute web yang me-*render* tampilan Blade. Tidak ada *endpoint* API publik yang tersedia untuk dikonsumsi oleh layanan eksternal.

#### **2.2. Arsitektur Aplikasi**

Aplikasi ini dibangun menggunakan **Laravel Framework** dengan pola arsitektur **Model-View-Controller (MVC)**.

  * **Model**: Merepresentasikan struktur data dan logika bisnis. Terletak di `app/Models/`.
      * `User.php`: Model untuk data pengguna.
      * `Transaction.php`: Model untuk data transaksi.
      * `Category.php`, `Debt.php`, `Invoice.php`, dll.
  * **View**: Bertanggung jawab untuk menampilkan data kepada pengguna. Menggunakan *template engine* **Blade**. Terletak di `resources/views/`.
      * `transactions/index.blade.php`: Menampilkan daftar transaksi.
      * `reports/index.blade.php`: Menampilkan halaman laporan.
  * **Controller**: Menerima input dari pengguna, berinteraksi dengan *Model*, dan menentukan *View* mana yang akan ditampilkan. Terletak di `app/Http/Controllers/`.
      * `TransactionController.php`: Mengelola logika untuk CRUD transaksi.
      * `ReportController.php`: Menyiapkan data untuk halaman laporan.
  * **Service Layer**: Logika bisnis yang kompleks dipisahkan ke dalam kelas *Service* untuk menjaga *Controller* tetap ramping. Terletak di `app/Services/`.
      * `TransactionService.php`: Berisi logika untuk menghitung *Agency Gross Income*.
      * `DebtService.php`: Mengelola logika terkait pembayaran utang.
  * **Routing**: Definisi URL dan pemetaannya ke *Controller* diatur dalam `routes/web.php`.
  * **Frontend**: Menggunakan **Tailwind CSS** untuk *styling* dan **Vite** untuk kompilasi *assets* (CSS & JS).

#### **2.3. Panduan Kontribusi**

Berikut adalah panduan bagi *developer* yang ingin berkontribusi pada proyek ini.

**1. Instalasi Lingkungan Lokal**

```bash
# 1. Clone repositori dari GitHub
git clone [URL_REPOSITORI_ANDA]
cd [NAMA_FOLDER_PROYEK]

# 2. Salin file environment
cp .env.example .env

# 3. Install dependency PHP (Composer)
composer install

# 4. Install dependency JavaScript (NPM)
npm install

# 5. Generate application key
php artisan key:generate

# 6. Konfigurasi database Anda di dalam file .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=vodeco_keuangan
# DB_USERNAME=root
# DB_PASSWORD=

# 7. Jalankan migrasi dan seeder database
php artisan migrate --seed

# 8. Compile assets frontend
npm run dev

# 9. Jalankan server development lokal
php artisan serve
```

Aplikasi sekarang seharusnya bisa diakses di `http://127.0.0.1:8000`.

**2. Menjalankan *Testing***
Proyek ini menggunakan PHPUnit untuk *testing*. Pastikan Anda sudah membuat *database* khusus untuk *testing* (jika diperlukan) dan mengkonfigurasinya di `phpunit.xml`.

```bash
# Menjalankan semua tests
php artisan test
```

File-file *test* terletak di dalam direktori `tests/Feature` dan `tests/Unit`.

**3. Standar *Coding***

  * Ikuti standar *coding* **PSR-12** untuk kode PHP.
  * Gunakan nama variabel, fungsi, dan kelas yang jelas dan deskriptif dalam bahasa Inggris.
  * Pastikan untuk menulis *test* untuk setiap fitur atau perbaikan *bug* yang Anda tambahkan.
  * Jaga agar *Controller* tetap ramping dengan memindahkan logika bisnis ke dalam *Service Class* jika memungkinkan.