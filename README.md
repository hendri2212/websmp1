# Website Sekolah PHP Native

Project ini sudah dimigrasikan ke `PHP native + MySQL` dan disiapkan agar nyaman dijalankan di `XAMPP` maupun `Laragon`.

## Struktur Folder

- `admin/` - panel admin untuk login, pengaturan sekolah, berita, galeri, dan PPDB
- `config/` - konfigurasi aplikasi, database, dan data awal sekolah
- `database/` - file `schema.sql` untuk membuat database dan tabel
- `includes/` - bootstrap, helper, autentikasi, dan repository data
- `pages/` - halaman publik website
- `public/` - aset statis, logo, foto kepala sekolah, dan folder upload
- `templates/` - layout header dan footer publik maupun admin
- `index.php` - entry point website
- `.htaccess` - routing sederhana untuk Apache

## Menjalankan di XAMPP

1. Pindahkan folder project ini ke:
   - `C:\xampp\htdocs\website-sekolah`
2. Jalankan `Apache` dan `MySQL` dari XAMPP Control Panel.
3. Buka `phpMyAdmin`.
4. Import file:
   - `C:\Users\LENOVO\Documents\Project Website Sekolah\database\schema.sql`
5. Salin `.env.example` menjadi `.env`, lalu sesuaikan bila perlu.
6. Akses website:
   - `http://localhost/website-sekolah`
7. Akses admin:
   - `http://localhost/website-sekolah/admin/login.php`

## Menjalankan di Laragon

1. Pindahkan folder project ini ke:
   - `C:\laragon\www\website-sekolah`
2. Jalankan `Start All` dari Laragon.
3. Import file `database/schema.sql` ke MySQL.
4. Salin `.env.example` menjadi `.env`, lalu sesuaikan `APP_URL` dan database bila perlu.
5. Akses website:
   - `http://website-sekolah.test`
6. Akses admin:
   - `http://website-sekolah.test/admin/login.php`

## Konfigurasi Environment

Website membaca konfigurasi dari file `.env`. File ini tidak ikut disimpan ke repository agar password database tidak bocor.

1. Salin file contoh:
   - `.env.example` menjadi `.env`
2. Untuk XAMPP lokal, nilai umum:
   - `APP_ENV=local`
   - `APP_URL=/website-sekolah`
   - `DB_HOST=127.0.0.1`
   - `DB_DATABASE=website_sekolah`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=`
3. Untuk hosting production, sesuaikan:
   - `APP_ENV=production`
   - `APP_URL=https://domain-sekolah.sch.id`
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai panel hosting.

<<<<<<< HEAD
=======
Untuk hosting, bisa gunakan `.env.production.example` sebagai template awal. Panduan ringkas deploy juga tersedia di `DEPLOYMENT.md`.

>>>>>>> 98147e3 (upload pertama)
## Checklist Deploy Hosting

Gunakan checklist ini saat website sudah akan dipindahkan dari XAMPP/Laragon lokal ke hosting production.

### 1. Persiapan Sebelum Upload

- Pastikan semua fitur utama sudah diuji di lokal: login admin, berita, galeri, settings, PPDB, dokumen, guru, agenda, fasilitas, FAQ, dan export data.
- Ganti password admin awal dari menu admin.
- Buat akun admin/operator sesuai kebutuhan role: `superadmin`, `editor`, atau `ppdb`.
- Export backup database terbaru dari menu backup admin atau dari phpMyAdmin.
- Siapkan domain sekolah dan aktifkan SSL/HTTPS dari panel hosting.
- Pastikan hosting mendukung PHP 8.0 atau lebih baru dan MySQL/MariaDB.

### 2. Upload File Website

- Upload isi project ke folder hosting, biasanya `public_html/` atau folder domain yang disediakan hosting.
- Jangan upload file backup SQL ke folder publik.
- Buat file `.env` langsung di hosting berdasarkan `.env.example`.
- Pastikan file `.htaccess` ikut terupload agar routing, proteksi file sensitif, dan aturan keamanan Apache tetap aktif.

### 3. Konfigurasi `.env` Production

Contoh konfigurasi umum di hosting:

```env
APP_NAME="Website Sekolah"
APP_ENV=production
APP_URL=https://domain-sekolah.sch.id

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database_hosting
DB_USERNAME=user_database_hosting
DB_PASSWORD=password_database_hosting
DB_CHARSET=utf8mb4
```

Catatan penting:

- `APP_URL` harus memakai domain asli dan `https://`.
- Jangan bagikan isi `.env` karena berisi akses database.
- Jika website dipasang di subfolder, sesuaikan `APP_URL` dengan path subfolder tersebut.

### 4. Setup Database Hosting

- Buat database dan user database dari panel hosting.
- Beri user database akses penuh ke database website.
- Import file `database/schema.sql` untuk instalasi baru, atau import file backup SQL terbaru jika memindahkan data dari lokal.
- Cocokkan nama database, username, dan password di `.env`.

### 5. Permission Folder Upload

Pastikan folder berikut bisa ditulis oleh PHP:

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

Gunakan permission sesuai rekomendasi hosting, umumnya `755` atau `775`. Jangan jadikan folder `storage/` sebagai folder publik.

### 6. Uji Setelah Live

- Buka halaman beranda dan semua menu publik.
- Login admin dengan HTTPS.
- Ubah password admin jika belum dilakukan.
- Simpan pengaturan sekolah.
- Upload gambar valid untuk berita, galeri, fasilitas, prestasi, atau ekstrakurikuler.
- Coba upload file tidak valid atau file terlalu besar untuk memastikan ditolak.
- Submit PPDB dari halaman publik dan cetak bukti pendaftaran.
- Cek data PPDB di admin, download berkas, ubah status, dan export CSV/Excel.
- Coba download dokumen publik.
- Buka `sitemap.xml` dan `robots.txt`.
- Jalankan backup SQL atau Paket Lengkap ZIP dari panel admin.

### 7. Keamanan Production

- Pastikan password admin awal sudah diganti.
- Gunakan password database yang kuat dan berbeda dari password admin.
- Simpan backup SQL/ZIP di tempat aman, bukan di folder publik website.
- Hapus file percobaan, screenshot sensitif, atau catatan password sebelum upload.
- Gunakan akun admin sesuai kebutuhan, jangan semua operator diberi akses `superadmin`.
- Pastikan SSL aktif dan website otomatis memakai `https://`.

### 8. Troubleshooting Singkat

- Error 500 biasanya terkait versi PHP, file `.env`, permission folder, atau konfigurasi database.
- Error koneksi database biasanya karena `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, atau `DB_PASSWORD` tidak cocok.
- Upload gagal biasanya karena permission folder atau batas ukuran upload di hosting.
- Routing 404 biasanya karena `.htaccess` tidak terupload atau Apache rewrite belum aktif.

## Login Admin Awal

- Username: `admin`
- Password: `admin123`

Login awal tersebut berasal dari data `admins` di `database/schema.sql`, bukan fallback di kode aplikasi. Segera ganti password admin setelah database aktif dan website siap dipakai.
Role admin tersedia: `superadmin`, `editor`, dan `ppdb`. Superadmin dapat mengelola akun operator dari panel admin.

## Fitur Admin Saat Ini

- Edit profil dan pengaturan sekolah
- Atur tema warna website dari admin: warna utama, warna kedua, warna aksen, dan warna gelap/sidebar
- Atur menu navigasi publik yang tampil atau disembunyikan
- Mode maintenance halaman publik, sementara admin tetap bisa login untuk perbaikan
- Navigasi publik responsif dengan tombol menu/hamburger untuk tampilan mobile
- Role admin untuk membatasi akses superadmin, editor konten, dan operator PPDB
- Backup dan export database SQL dari panel superadmin
- Log aktivitas admin untuk login, logout, perubahan konten, penghapusan data, backup, export PPDB, dan ganti password
- Cek kesiapan production dari panel superadmin: environment, HTTPS, database, password awal, `.htaccess`, batas upload, dan permission folder
- Dashboard ringkasan dengan kartu statistik, status PPDB, ringkasan konten, agenda terdekat, dan aktivitas admin terbaru
- Notifikasi admin berupa badge jumlah PPDB baru dan pesan masuk baru di sidebar
- SEO dasar: meta description, canonical URL, Open Graph, Twitter card, sitemap.xml, dan robots.txt
- Audit keamanan final: security headers, no-cache admin, proteksi file sensitif, sanitasi nama download, dan mitigasi CSV injection
- Edit sejarah singkat, visi, misi, dan fasilitas sekolah
- CRUD agenda dan kalender kegiatan sekolah
- CRUD data guru dan staf
- CRUD berita
- Upload gambar berita
- Optimasi media upload: validasi format, batas 2 MB, minimal resolusi, dan kompresi gambar otomatis bila ekstensi PHP `gd` aktif
- Upload gambar membuat versi utama dan thumbnail otomatis di folder `thumbs/`; kartu publik memakai thumbnail agar halaman lebih ringan.
- CRUD pengumuman penting untuk banner beranda
- CRUD galeri
- Upload gambar galeri
- CRUD dokumen PDF sekolah untuk kalender akademik, formulir, surat edaran, tata tertib, dan file resmi lain
- Halaman publik download dokumen dengan filter kategori
- CRUD prestasi sekolah dengan foto, kategori, tingkat lomba, peraih, dan tanggal prestasi
- Halaman publik prestasi sekolah dengan filter kategori
- CRUD ekstrakurikuler dengan foto, kategori, pembina, jadwal, lokasi, dan deskripsi
- Halaman publik ekstrakurikuler dengan filter kategori
- CRUD entry PPDB
- Form pendaftaran PPDB online dengan upload berkas
- Kebijakan privasi dan persetujuan penggunaan data pada form PPDB
- Verifikasi status pendaftar PPDB dari admin
- Filter, pencarian, dan export CSV/Excel data pendaftar PPDB
- Kelola pesan masuk dari form kontak publik
- CRUD fasilitas sekolah dengan foto, kategori, lokasi, kapasitas, kondisi, dan deskripsi
- Halaman publik fasilitas sekolah dengan filter kategori
- CRUD FAQ atau pertanyaan umum dengan kategori, status aktif, dan urutan
- Halaman publik FAQ dengan filter kategori

## Catatan Upload

- Gambar berita masuk ke `public/uploads/news/`
- Gambar galeri masuk ke `public/uploads/gallery/`
- Logo sekolah dan foto kepala sekolah masuk ke `public/uploads/profile/`
- Foto guru dan staf masuk ke `public/uploads/staff/`
- Foto fasilitas masuk ke `public/uploads/facilities/`
- Foto prestasi masuk ke `public/uploads/achievements/`
- Foto ekstrakurikuler masuk ke `public/uploads/extracurriculars/`
- Berkas PPDB masuk ke `storage/uploads/ppdb/` dan hanya bisa diunduh dari admin
- Dokumen PDF sekolah masuk ke `storage/uploads/documents/` dan hanya bisa diunduh lewat halaman dokumen publik
- Pastikan folder tersebut bisa ditulis oleh PHP saat berjalan di Apache

## Checklist Finalisasi Konten Asli

Gunakan checklist ini setelah fitur website selesai diuji dan sebelum website dipublikasikan.

### Profil Sekolah

- Lengkapi nomor telepon atau WhatsApp resmi sekolah.
- Lengkapi email resmi sekolah.
- Lengkapi sejarah singkat sekolah.
- Pastikan visi dan misi sudah sesuai dokumen resmi.
- Pastikan alamat sudah lengkap sampai kecamatan, kabupaten, provinsi, dan kode pos bila ada.
- Ganti logo dan foto kepala sekolah dengan file resmi terbaru.
- Isi jumlah siswa, jumlah guru dan staf, jumlah ekstrakurikuler, dan jumlah prestasi.

### Konten Publik

<<<<<<< HEAD
- Tambahkan data guru dan staf aktif beserta foto, jabatan, mapel/bidang, dan kontak bila perlu.
- Tambahkan fasilitas sekolah asli beserta foto, kategori, lokasi, kapasitas, kondisi, dan deskripsi.
- Tambahkan agenda atau kalender kegiatan sekolah yang masih berlaku.
- Tambahkan dokumen resmi: kalender akademik, formulir, surat edaran, tata tertib, dan PDF sekolah.
=======
- Jika database baru dibuat ulang, jalankan `C:\xampp\php\php.exe database\seed_final_content.php` untuk mengisi konten dasar final dari data sekolah yang tersedia.
- Tambahkan data guru dan staf aktif beserta foto, jabatan, mapel/bidang, dan kontak bila perlu.
- Tambahkan fasilitas sekolah asli beserta foto, kategori, lokasi, kapasitas, kondisi, dan deskripsi.
- Tambahkan agenda atau kalender kegiatan sekolah yang masih berlaku.
- Tambahkan dokumen resmi melalui admin dengan file PDF asli: kalender akademik, formulir, surat edaran, tata tertib, dan PDF sekolah.
>>>>>>> 98147e3 (upload pertama)
- Tambahkan prestasi sekolah terbaru dengan foto dan tanggal prestasi.
- Tambahkan ekstrakurikuler aktif beserta pembina, jadwal, lokasi, dan deskripsi.
- Tambahkan FAQ yang sering ditanyakan orang tua, siswa, dan calon siswa.
- Tambahkan berita dan galeri dokumentasi resmi.

### PPDB

- Lengkapi informasi jalur pendaftaran.
- Lengkapi jadwal pendaftaran, verifikasi, pengumuman, dan daftar ulang.
- Lengkapi persyaratan berkas sesuai ketentuan panitia.
- Lengkapi kontak layanan panitia PPDB.
- Uji form PPDB dengan satu data percobaan, lalu hapus data test sebelum publikasi.

### Admin dan Keamanan

- Pastikan password admin awal sudah diganti.
- Buat akun operator sesuai role: `superadmin`, `editor`, dan `ppdb`.
- Pastikan tidak semua operator memakai akun `superadmin`.
<<<<<<< HEAD
=======
- Jalankan smoke test form admin dari terminal project dengan `C:\xampp\php\php.exe tests\admin_form_smoke_test.php` setelah MySQL XAMPP aktif.
>>>>>>> 98147e3 (upload pertama)
- Buka halaman `Cek Production` dan pastikan tidak ada masalah kritis.
- Jalankan backup database setelah semua konten asli masuk.
- Gunakan `Backup SQL` untuk pemulihan database saja.
- Gunakan `Paket Lengkap ZIP` untuk migrasi hosting karena berisi SQL, schema, `public/uploads`, `storage/uploads`, dan `manifest.json`.
- Jika tombol paket ZIP nonaktif, aktifkan ekstensi PHP `zip` di hosting/XAMPP.
- Atur `Tahun Ajaran Aktif` dari menu Profil & Pengaturan sebelum membuka PPDB atau mengisi agenda/dokumen/prestasi baru.
- Gunakan status `Draft` untuk konten yang belum siap tampil dan `Publish` untuk konten yang boleh muncul di halaman publik.

### Data Test

- Hapus pendaftar PPDB test.
- Hapus berita, galeri, dokumen, fasilitas, atau konten lain yang berisi kata `TEST`.
- Hapus file upload test yang tidak lagi dipakai.
- Log aktivitas boleh tetap disimpan sebagai riwayat audit internal.
