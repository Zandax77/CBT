# Panduan Deployment CBT Sekolah ke Hostinger (hPanel)

Dokumentasi ini memandu proses deployment aplikasi **CBT ExamBro Laravel** ke hosting **Hostinger** menggunakan panel kontrol **hPanel**.

---

## 📋 Ringkasan File Khusus Deployment yang Sudah Disiapkan

1. **`.htaccess` (Root)**: Otomatis mengarahkan domain utama ke folder `/public` dan memblokir akses publik langsung ke file sensitif (`.env`, `composer.json`, `app/`, dll).
2. **`public/.htaccess`**: Konfigurasi routing Laravel, proteksi header PWA, dan penonaktifan cache HTTP untuk `sw.js`.
3. **`public/build/`**: Aset Vite (CSS & JS) sudah ter-compile sehingga server tidak memerlukan Node.js / NPM.
4. **`.env.production.example`**: Template konfigurasi production yang disesuaikan untuk server Hostinger.
5. **Endpoint Setup Otomatis (`/hpanel-setup`)**: Memungkinkan migrasi database, storage link, dan caching langsung melalui browser (tanpa perlu membuka terminal SSH).

---

## 🚀 Langkah 1: Buat Database MySQL di hPanel

1. Masuk ke **hPanel Hostinger**.
2. Buka menu **Databases** ➔ **MySQL Databases**.
3. Buat database baru:
   - **MySQL Database Name**: contoh `cbt` (hasilnya misal: `u123456789_cbt`)
   - **MySQL Username**: contoh `cbtuser` (hasilnya misal: `u123456789_cbtuser`)
   - **Password**: Buat password yang kuat dan catat baik-baik.
4. Klik **Create**.

---

## 📦 Langkah 2: Upload File ke Hostinger

Anda bisa memilih salah satu dari 2 metode di bawah:

### Metode A: Menggunakan Git di hPanel (Direkomendasikan)
1. Di hPanel, buka menu **Advanced** ➔ **Git**.
2. Masukkan URL Repository GitHub/GitLab Anda.
3. **Branch**: `main`
4. **Install Path**: biarkan `public_html`
5. Klik **Create**.
6. Setiap kali ada update kode di Git, Anda cukup menekan tombol **Deploy** di halaman Git hPanel.

### Metode B: Upload File ZIP via File Manager
1. Di komputer lokal, kompres seluruh folder proyek menjadi file `.zip` (**kecuali** folder `node_modules`, `.git`, dan file `.env` lokal).
   *(Pastikan folder `vendor` dan `public/build` ikut terkompres)*.
2. Di hPanel, buka **Files** ➔ **File Manager**.
3. Masuk ke direktori `public_html`.
4. Upload file `.zip` Anda, lalu klik kanan dan pilih **Extract**.

---

## ⚙️ Langkah 3: Konfigurasi File `.env`

1. Di dalam **File Manager** hPanel pada direktori proyek Anda:
   - Salin isi dari file `.env.production.example` ke file baru bernama `.env` (atau edit file `.env` yang ada).
2. Sesuaikan baris-baris berikut:
   ```dotenv
   APP_NAME="CBT Sekolah"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://namadomain-anda.com

   # Kunci keamanan (generate via terminal atau gunakan key yang ada)
   APP_KEY=base64:...

   # Database MySQL dari Langkah 1
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=u123456789_cbt
   DB_USERNAME=u123456789_cbtuser
   DB_PASSWORD=PasswordDatabaseAnda

   FILESYSTEM_DISK=public
   SESSION_DRIVER=database
   ```
3. Simpan perubahan file `.env`.

---

## 🐘 Langkah 4: Pastikan Versi PHP Sesuai

1. Di hPanel, buka menu **Advanced** ➔ **PHP Configuration**.
2. Pilih **PHP 8.2** atau **PHP 8.3**.
3. Di tab **PHP Extensions**, pastikan ekstensi berikut aktif (biasanya sudah aktif secara default):
   - `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `zip`.
4. Klik **Save**.

---

## ⚡ Langkah 5: Jalankan Setup Otomatis (Migrasi, Storage Link, & Cache)

Pilih salah satu cara di bawah ini:

### Opsi 1: Lewat Web Browser (Paling Praktis, Tanpa Perlu SSH)
Buka browser Anda dan akses URL berikut:
```text
https://namadomain-anda.com/hpanel-setup?key=APP_KEY_ANDA
```
*(Ganti `APP_KEY_ANDA` dengan nilai `APP_KEY` yang tertera di file `.env` Anda, atau nilai `DEPLOY_SECRET` jika disetel).*

Endpoint ini akan otomatis:
- Menghubungkan penyimpanan publik (`php artisan storage:link`).
- Menjalankan migrasi seluruh tabel database (`php artisan migrate --force`).
- Mengaktifkan cache konfigurasi, rute, dan view untuk kecepatan optimal.
- Membuat akun admin default pertama kali.

### Opsi 2: Menggunakan SSH Terminal (Jika Ada Akses SSH)
1. Buka menu **Advanced** ➔ **SSH Access** di hPanel.
2. Buka terminal (atau gunakan SSH Console bawaan browser Hostinger), masuk ke folder proyek:
   ```bash
   cd public_html
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## 🔒 Langkah 6: Login & Verifikasi

1. Buka website Anda: `https://namadomain-anda.com`.
2. Login menggunakan akun bawaan pertama kali:
   - **Username**: `admin`
   - **Password**: `Password123!`
3. Segera ganti password admin Anda di dashboard setelah berhasil masuk.
4. Uji instalasi PWA di smartphone (Android via Chrome dan iOS via Safari).
