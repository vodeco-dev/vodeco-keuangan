<div align="center">
  <img src="public/vodeco.webp" alt="Vodeco Logo" width="200"/>
  <h1>Vodeco Keuangan</h1>
  <p>Aplikasi manajemen keuangan pribadi dan bisnis kecil yang modern, intuitif, dan <em>open-source</em>.</p>
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
- [✨ Fitur Utama](#-fitur-utama)
- [📸 Tangkapan Layar](#-tangkapan-layar)
- [🛠️ Tumpukan Teknologi](#️-tumpukan-teknologi)
- [🚀 Panduan Memulai](#-panduan-memulai)
  - [Prasyarat](#prasyarat)
  - [Instalasi](#instalasi)
- [🧪 Menjalankan Pengujian](#-menjalankan-pengujian)
- [🤝 Berkontribusi](#-berkontribusi)
- [📄 Lisensi](#-lisensi)

---

## 📖 Tentang Proyek

**Vodeco Keuangan** adalah aplikasi web yang dirancang untuk membantu Anda mengelola keuangan dengan lebih mudah dan efisien. Baik untuk kebutuhan pribadi, <em>freelancer</em>, maupun usaha kecil, aplikasi ini menyediakan alat yang Anda butuhkan untuk melacak pemasukan, pengeluaran, utang-piutang, hingga membuat <em>invoice</em> profesional.

Dibangun dengan teknologi modern, Vodeco Keuangan menawarkan pengalaman pengguna yang bersih, cepat, dan responsif.

---

## ✨ Fitur Utama

-   **📊 Dashboard Interaktif**: Lihat ringkasan kondisi keuangan Anda secara visual, termasuk grafik pemasukan dan pengeluaran.
-   **💸 Manajemen Transaksi**: Catat semua pemasukan dan pengeluaran dengan mudah. Kelompokkan berdasarkan kategori untuk analisis yang lebih mendalam.
-   **🧾 Manajemen Invoice**: Buat, kelola, dan kirim <em>invoice</em> profesional ke klien Anda hanya dalam beberapa klik.
-   **💳 Manajemen Utang & Piutang**: Lacak semua utang yang harus Anda bayar dan piutang yang harus diterima, lengkap dengan tanggal jatuh tempo.
-   **📈 Laporan Keuangan**: Hasilkan laporan keuangan periodik untuk memahami arus kas Anda. Filter berdasarkan rentang tanggal untuk mendapatkan data yang spesifik.
-   **👥 Manajemen Pengguna**: (Untuk Admin) Kelola pengguna yang dapat mengakses aplikasi, lengkap dengan peran dan hak akses.
-   **⚙️ Pengaturan Fleksibel**: Sesuaikan pengaturan aplikasi sesuai kebutuhan Anda.

---

## 🛠️ Tumpukan Teknologi

Aplikasi ini dibangun menggunakan komponen dan teknologi terdepan di industri:

| Kategori      | Teknologi                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| :------------ | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Backend**   | <a href="https://laravel.com/"><img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel"></a> <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"></a> <a href="https://www.mysql.com/"><img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"></a>                                                                                                  |
| **Frontend**  | <a href="https://tailwindcss.com/"><img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS"></a> <a href="https://vitejs.dev/"><img src="https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite"></a> <a href="https://alpinejs.dev/"><img src="https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white" alt="Alpine.js"></a>                                                                 |
| **DevOps**    | <a href="https://github.com/features/actions"><img src="https://img.shields.io/badge/GitHub_Actions-2088FF?style=for-the-badge&logo=github-actions&logoColor=white" alt="GitHub Actions"></a> <a href="https://www.docker.com/"><img src="https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker"></a>                                                                                                                                                                                                    |
| **Testing**   | <a href="https://phpunit.de/"><img src="https://img.shields.io/badge/PHPUnit-8BC0D0?style=for-the-badge&logo=phpunit&logoColor=white" alt="PHPUnit"></a>                                                                                                                                                                                                                                                                                                                                                                                                           |

---

## 🚀 Panduan Memulai

Ikuti langkah-langkah berikut untuk menjalankan salinan proyek ini di lingkungan lokal Anda untuk tujuan pengembangan dan pengujian.

### Prasyarat

Pastikan Anda telah menginstal perangkat lunak berikut di sistem Anda:
- PHP >= 8.2
- Composer
- Node.js & NPM
- Database (misal: MySQL, PostgreSQL)

### Instalasi

1.  **Clone repositori ini:**
    ```sh
    git clone https://github.com/Vodeco/vodeco-keuangan.git
    cd vodeco-keuangan
    ```

2.  **Salin file environment:**
    ```sh
    cp .env.example .env
    ```

3.  **Install dependensi PHP (Composer):**
    ```sh
    composer install
    ```

4.  **Install dependensi JavaScript (NPM):**
    ```sh
    npm install
    ```

5.  **Generate <em>application key</em>:**
    ```sh
    php artisan key:generate
    ```

6.  **Konfigurasi database Anda di dalam file `.env`:**
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=vodeco_keuangan
    DB_USERNAME=root
    DB_PASSWORD=
    ```

7.  **Aktifkan integrasi Google Drive (opsional namun direkomendasikan untuk bukti transaksi):**
    - Buat service account khusus di Google Cloud dan unduh berkas kredensial JSON-nya.
    - Simpan path atau isi JSON tersebut pada variabel environment berikut:
      ```env
      GOOGLE_DRIVE_SERVICE_ACCOUNT_CREDENTIALS=/path/to/service-account.json
      GOOGLE_DRIVE_IMPERSONATE_USER=
      GOOGLE_DRIVE_TEAM_DRIVE_ID=
      ```
    - Bagikan folder tujuan di Google Drive kepada service account dan catat ID foldernya.
    - Buka menu **Pengaturan → Penyimpanan Bukti Transaksi**, pilih mode **Drive**, lalu isi ID folder tersebut.

8.  **Jalankan migrasi database:**
    Ini akan membuat semua tabel yang diperlukan oleh aplikasi.
    ```sh
    php artisan migrate
    ```

9.  **(Opsional) Jalankan <em>seeder</em> untuk mengisi data awal:**
    Ini akan mengisi database dengan data contoh (pengguna, kategori, dll).
    ```sh
    php artisan db:seed
    ```

10. **Compile <em>assets frontend</em>:**
    Jalankan <em>Vite development server</em>.
    ```sh
    npm run dev
    ```

11. **Jalankan <em>server development</em> lokal:**
    Buka terminal baru dan jalankan perintah ini.
    ```sh
    php artisan serve
    ```

Aplikasi sekarang seharusnya bisa diakses di `http://127.0.0.1:8000`.

---

## 🧪 Menjalankan Pengujian

Proyek ini menggunakan PHPUnit untuk <em>testing</em>. Pastikan Anda sudah membuat <em>database</em> khusus untuk <em>testing</em> dan mengkonfigurasinya di `phpunit.xml` atau `.env.testing`.

Untuk menjalankan semua <em>test case</em>, gunakan perintah Artisan berikut:
```sh
php artisan test
```

---

## 🤝 Berkontribusi

Kontribusi adalah hal yang membuat komunitas <em>open source</em> menjadi tempat yang luar biasa untuk belajar, menginspirasi, dan berkreasi. Setiap kontribusi yang Anda berikan sangat **kami hargai**.

Jika Anda memiliki saran untuk perbaikan, silakan <em>fork</em> repositori ini dan buat <em>pull request</em>. Anda juga bisa membuka <em>issue</em> dengan <em>tag</em> "enhancement". Jangan lupa untuk memberikan bintang pada proyek ini! Terima kasih sekali lagi!

1.  <em>Fork</em> Proyek ini
2.  Buat <em>Feature Branch</em> Anda (`git checkout -b feature/AmazingFeature`)
3.  <em>Commit</em> Perubahan Anda (`git commit -m 'Add some AmazingFeature'`)
4.  <em>Push</em> ke <em>Branch</em> tersebut (`git push origin feature/AmazingFeature`)
5.  Buka sebuah <em>Pull Request</em>

---

## 📄 Lisensi

Didistribusikan di bawah Lisensi MIT. Lihat `LICENSE` untuk informasi lebih lanjut.
