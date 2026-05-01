# Panduan Deploy Hosting

Panduan singkat untuk memindahkan website SMP Negeri 1 Kotabaru dari XAMPP lokal ke hosting.

## 1. File yang Disiapkan

- Upload seluruh isi project ke folder domain hosting, biasanya `public_html/`.
- Pastikan `.htaccess` ikut terupload.
- Jangan upload folder `storage/backups/`, `storage/source_docs/`, `tests/`, atau file backup SQL ke folder publik hosting.
- Gunakan file backup final terbaru dari `storage/backups/` hanya untuk import database dan arsip aman.

## 2. Konfigurasi `.env`

Di hosting, buat file `.env` dari `.env.production.example`.

Contoh:

```env
APP_NAME="SMP Negeri 1 Kotabaru"
APP_ENV=production
APP_URL=https://domain-sekolah.sch.id

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database_hosting
DB_USERNAME=user_database_hosting
DB_PASSWORD=password_database_yang_kuat
DB_CHARSET=utf8mb4
```

Catatan:

- `APP_URL` harus memakai domain asli.
- Jika website dipasang di subfolder, isi `APP_URL` dengan domain plus subfolder.
- Jangan membagikan isi `.env`.

## 3. Database

- Buat database dan user database dari panel hosting.
- Import file SQL backup final, bukan `schema.sql`, jika ingin membawa data lokal terbaru.
- Gunakan `schema.sql` hanya untuk instalasi kosong.
- Setelah import, cocokkan nama database, username, dan password di `.env`.

## 4. Folder Upload

Pastikan folder berikut bisa ditulis PHP:

- `public/uploads/`
- `public/uploads/news/`
- `public/uploads/gallery/`
- `public/uploads/profile/`
- `public/uploads/staff/`
- `public/uploads/facilities/`
- `public/uploads/achievements/`
- `public/uploads/extracurriculars/`
- `storage/uploads/`
- `storage/uploads/ppdb/`
- `storage/uploads/documents/`

Umumnya permission hosting cukup `755` atau `775`.

## 5. Ekstensi PHP

Aktifkan minimal:

- `pdo_mysql`
- `mysqli`
- `fileinfo`
- `gd`
- `zip`

`gd` dipakai untuk optimasi gambar. `zip` dipakai untuk paket backup lengkap.

## 6. Uji Setelah Online

- Buka beranda dan semua menu publik.
- Buka `sitemap.xml` dan `robots.txt`.
- Login admin lewat HTTPS.
- Buka `admin/production_check.php`.
- Simpan profil/pengaturan sekolah.
- Upload gambar valid.
- Coba upload file bukan gambar atau gambar di atas 2 MB untuk memastikan ditolak.
- Submit PPDB percobaan, cetak bukti, cek data di admin, export CSV, lalu hapus data test.
- Coba download dokumen publik setelah dokumen PDF asli diunggah.

## 7. Keamanan

- Ganti password admin setelah online.
- Gunakan akun operator sesuai role, jangan semua memakai `superadmin`.
- Simpan backup di luar folder publik.
- Aktifkan SSL/HTTPS.
- Hapus file test dan data dummy sebelum diumumkan ke publik.
