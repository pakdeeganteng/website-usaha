# DapurHarian

Website pemesanan masakan rumahan dengan katalog menu, akun pelanggan, pre-order, dan panel administrasi untuk mengelola pesanan serta laporan keuangan.

## Teknologi

- PHP 8.3 atau lebih baru
- Laravel 13
- SQLite (default) atau MySQL
- Node.js dan npm
- Vite, Bootstrap 5, dan Bootstrap Icons

## Fitur utama

- Registrasi dan login pelanggan serta administrator
- Katalog menu dengan kategori, ketersediaan, dan detail produk
- Pre-order dan riwayat pesanan pelanggan
- Pengelolaan status pesanan dari panel administrator
- Pencatatan pembayaran, pengeluaran, dan pengembalian dana
- Laporan keuangan dengan ekspor PDF dan Excel

## Instalasi

Pastikan PHP, Composer, Node.js, dan npm sudah terpasang, lalu jalankan dari direktori proyek:

```powershell
composer install
if (!(Test-Path .env)) { Copy-Item .env.example .env }
if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite }
php artisan key:generate
php artisan migrate --seed
npm ci
npm run build
```

File `.env` digunakan untuk konfigurasi lokal dan tidak boleh dimasukkan ke repository. SQLite dipakai secara default; sesuaikan `.env` jika menggunakan MySQL.

Untuk membuat akun administrator, isi `ADMIN_EMAIL` dan `ADMIN_INITIAL_PASSWORD` di `.env` dengan kredensial lokal yang aman, lalu jalankan:

```powershell
php artisan db:seed --class=AdminUserSeeder
```

Jangan memakai atau mengunggah password awal yang sama ke lingkungan publik.

## Menjalankan website

Jalankan server Laravel:

```powershell
php artisan serve
```

Buka URL lokal yang ditampilkan Artisan. Untuk pengembangan frontend dengan hot reload, jalankan `npm run dev` di terminal terpisah.

Jalankan pengujian dengan:

```powershell
php artisan test
```
