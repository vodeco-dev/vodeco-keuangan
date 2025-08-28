# Vodeco Keuangan

Aplikasi manajemen keuangan internal berbasis Laravel.

## Tujuan Aplikasi

Proyek ini menyediakan pondasi untuk membangun modul-modul keuangan seperti pencatatan transaksi, pengelolaan anggaran, dan laporan.

## Langkah Instalasi

1. Pastikan sudah terpasang **PHP**, **Composer**, **Node.js**, dan **NPM**.
2. Clone repositori ini dan masuk ke direktorinya.
3. Jalankan `composer install` untuk mengunduh dependensi PHP.
4. Jalankan `npm install` untuk mengunduh dependensi front-end.
5. Salin berkas contoh konfigurasi dengan `cp .env.example .env`.
6. Generate key aplikasi dengan `php artisan key:generate`.

## Konfigurasi `.env`

Buka berkas `.env` dan sesuaikan pengaturan dasar seperti koneksi database dan nama aplikasi. Contoh konfigurasi database:

```env
APP_NAME="Vodeco Keuangan"
APP_ENV=local
APP_KEY=
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vodeco
DB_USERNAME=root
DB_PASSWORD=
```

## Menjalankan Server

- Jalankan server pengembangan Laravel dengan:

  ```bash
  php artisan serve
  ```
- Untuk mengompilasi aset front-end jalankan:

  ```bash
  npm run dev
  ```

## Menjalankan Test

Gunakan perintah berikut untuk menjalankan seluruh test:

```bash
php artisan test
```

