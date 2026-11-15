# Website Sekolah PHP Native

Project PHP Native + MySQL/MariaDB dengan panel admin dan PPDB/SPMB pasif/informatif. Belum commit, push, atau deploy; hasil testing bukan pernyataan siap production.

## Setup Setelah Clone

1. Periksa requirement PHP/web SAPI, extension, database dan routing dalam [hosting checklist](HOSTING_REQUIREMENTS_CHECKLIST.md). Testing tools tidak diperlukan untuk request production.
2. Sediakan Apache-compatible development virtual host yang menunjuk root project (index.php), atau konfigurasi ekuivalen yang diuji. Jangan menjadikan public/ sebagai document root. HTTP localhost boleh dipakai untuk development.
3. Provision database lokal baru/terisolasi dan akun database milik developer dengan privilege terbatas pada database tersebut. Jangan menggunakan database sekolah atau credential production.
4. Review database/schema.sql sebelum provisioning offline: installer memakai nama database development eksplisit, bukan migration database existing. Pilih target kosong yang benar; jangan import mentah ke database berisi data. Runtime tidak membuat schema atau akun otomatis.
5. Salin .env.example menjadi .env di root project, lalu isi setiap field wajib dengan konfigurasi milik developer. Template sengaja tidak dapat langsung digunakan; tidak berisi akun/password default.
6. Sediakan storage privat dan direktori upload aktif dengan permission terbatas sesuai [panduan persiapan](DEPLOYMENT.md). Jangan menyalin session/backup/state testing atau arsip PPDB ke lokasi publik.
7. Untuk akun admin awal pada database kosong, ikuti bagian Inisialisasi Admin di bawah. Tidak ada akun demo atau password bawaan.
8. Buka URL development yang dipilih, lalu /admin/login.php. Bila konfigurasi salah, gunakan log privat dan pesan generik; jangan mengaktifkan display_errors publik.

## Menjalankan Lokal dengan MAMP (macOS)

Proyek ini memakai PHP native tanpa `package.json` atau `composer.json`. Tidak perlu menjalankan `npm install`, `composer install`, atau proses build untuk membuka website.

Untuk konfigurasi lokal yang sudah disiapkan:

1. Buka MAMP, lalu klik **Start Servers**. Pastikan Apache berjalan pada port `8888` dan MySQL pada port `8889`.
2. Buka website: [http://localhost:8888/websmp1/](http://localhost:8888/websmp1/).
3. Buka admin: [http://localhost:8888/websmp1/admin/login.php](http://localhost:8888/websmp1/admin/login.php). Gunakan akun dan password dari database yang diimport.

Database `website_sekolah` sudah diisi dari `website_sekolah.sql`; tidak perlu import ulang setiap menjalankan aplikasi. `.env` lokal sudah disiapkan dengan `APP_ENV=local`, `APP_URL=http://localhost:8888/websmp1`, `DB_HOST=127.0.0.1`, `DB_PORT=8889`, dan `DB_DATABASE=website_sekolah`. Credential database tersimpan di `.env` privat.

Apache MAMP memakai document root `/Applications/MAMP/htdocs`. Folder proyek `/Users/hendriarifin/CODE/websmp1` dihubungkan melalui symbolic link `/Applications/MAMP/htdocs/websmp1`. Jika menyiapkan ulang pada mesin dengan lokasi proyek yang sama dan link belum ada, jalankan:

```bash
ln -s /Users/hendriarifin/CODE/websmp1 /Applications/MAMP/htdocs/websmp1
```

Untuk clone baru pada mesin lain, ikuti **Setup Setelah Clone**, sesuaikan lokasi symbolic link dan `.env` dengan konfigurasi MAMP mesin tersebut. Jika memakai dump `website_sekolah.sql` milik sendiri, import ke database kosong melalui phpMyAdmin MAMP sebagai pengganti `database/schema.sql`; jangan import keduanya. Dump yang sudah berisi akun admin tidak membutuhkan inisialisasi admin baru.

Jika website tidak terbuka, periksa Apache MAMP dan symbolic link. Jika koneksi database gagal, periksa MySQL MAMP, port, nama database, serta credential di `.env`. Jika port Apache diubah, sesuaikan juga URL browser dan `APP_URL`.

## Menjalankan Lokal dengan Server PHP

Sebagai alternatif Apache MAMP, jalankan perintah berikut dari root proyek:

```bash
APP_URL=http://localhost:8080 php -S localhost:8080 preview_router.php
```

Buka [website](http://localhost:8080/) atau [login admin](http://localhost:8080/admin/login.php). MySQL MAMP tetap harus aktif pada port `8889` sesuai `.env` lokal; Apache tidak diperlukan. Perintah ini mengganti `APP_URL` hanya untuk proses server tersebut tanpa mengubah `.env`.

Gunakan `preview_router.php` agar routing aplikasi dan pembatasan akses file privat diterapkan oleh server PHP. Server ini untuk development lokal. Hentikan dengan `Ctrl+C`.

## Variabel Environment

| Variabel | Wajib | Nilai/aturan |
| --- | --- | --- |
| APP_NAME | Opsional | Nama tampilan; jika tidak diberikan, memakai nama sekolah existing. Bukan credential. |
| APP_ENV | Ya | Tepat local atau production; tidak ada default mode. |
| APP_URL | Ya | Local: path seperti /website-sekolah atau URL HTTP/HTTPS virtual host. Production: URL HTTPS absolut non-loopback, tanpa userinfo/query/fragment. |
| DB_HOST | Ya | Host/IP database milik environment; bukan placeholder yang boleh dipakai langsung. |
| DB_PORT | Ya | Integer desimal 1-65535; template 3306 hanya contoh port standar, sesuaikan instance. |
| DB_DATABASE | Ya | Nama target yang diprovision, 1-64 karakter huruf/angka/underscore. |
| DB_USERNAME | Ya | Akun yang diprovision privat, 1-80 karakter huruf/angka/underscore/titik/tanda hubung. Tidak ada akun default. |
| DB_PASSWORD | Ya | Isi credential privat milik akun tersebut. Validator local mengizinkan kosong secara eksplisit untuk kompatibilitas, tetapi template kosong bukan password default dan panduan ini tidak menyarankan akun tanpa password. Production menolak kosong. |
| DB_CHARSET | Ya | Tepat utf8mb4. |

.env.example untuk development; .env.production.example untuk struktur field production. Kedua template tidak memuat credential dan tidak masuk web bundle. .env sebenarnya di-ignore dan harus tetap privat; jangan commit atau force-add.

Resolver memeriksa $_ENV, lalu $_SERVER, lalu getenv(), kemudian .env. Nilai environment server termasuk string kosong mengalahkan nilai file; hapus override lama bila ingin memakai file. Konfigurasi dibaca sekali per proses/request; restart worker atau proses panjang setelah mengganti environment.

Parser .env existing hanya mendukung baris KEY=VALUE sederhana, komentar penuh yang dimulai #, serta trim quote luar. Tidak mendukung export, interpolasi variabel, multiline, escape shell, atau komentar inline. Isi nilai satu baris; jangan menempel contoh shell ke .env. Untuk credential dengan kebutuhan representasi khusus, gunakan environment server privat yang mempertahankan nilai persis, bukan memodifikasi parser atau menerbitkan nilai dalam laporan.

## Production Bukan Fallback Development

Production harus eksplisit. Config invalid/missing ditolak dengan error generik, bukan mencoba root/localhost/database development. Kebijakan saat ini menolak DB loopback/localhost, root, nama database development/testing yang diblokir, dan password kosong. Provider database localhost membutuhkan review kompatibilitas terpisah; jangan mengganti mode menjadi local untuk menghindari validator.

Tidak ada credential production yang disediakan repository. Ikuti [DEPLOYMENT.md](DEPLOYMENT.md) untuk persiapan release allowlist, konfigurasi privat, backup/restore, permission dan gate hosting, bukan upload seluruh project.

## Pengujian dan Script Maintenance

Folder `tests/` telah dihapus karena bukan dependensi runtime website. Suite pengujian otomatis dan script pembuat paket release yang sebelumnya berada di folder tersebut tidak lagi tersedia.

`database/maintenance_legacy_schema.php` bergantung pada preflight dari folder `tests/` sehingga tidak dapat dijalankan tanpa memulihkan dependensi pengujiannya. Jangan menghapus atau melewati guard tersebut untuk menjalankan maintenance. `database/seed_final_content.php` adalah script legacy yang tidak direkomendasikan untuk dijalankan. Setup database lokal tetap mengikuti bagian Setup Setelah Clone; admin awal memakai `database/initialize_admin.php` hanya pada tabel akun kosong.

## Inisialisasi Admin

Installer tidak menyediakan akun/password default. Akun existing tidak diubah atau diaktifkan oleh import schema.

Untuk database baru dengan tabel admins kosong, operator memakai `database/initialize_admin.php` secara offline/CLI dengan opsi `--initialize`, username dan nama lengkap eksplisit. Password unik 12-72 byte wajib diberikan melalui STDIN dari secret pipe privat, bukan argument, history, file publik atau input terminal yang terlihat. Script menolak mode HTTP, input kosong, storage nontransaksional dan database yang sudah mempunyai akun; tidak mereset akun existing.

Installer/schema/seed/script inisialisasi tidak masuk public release. Akun yang dahulu dibuat installer legacy harus ditinjau dan credential-nya dirotasi secara privat sebelum release; penghapusan default dari source tidak mengubah password database existing.

## Fitur Admin Saat Ini

- Edit profil dan pengaturan sekolah
- Atur tema warna website dari admin: warna utama, warna kedua, warna aksen, dan warna gelap/sidebar
- Atur menu navigasi publik yang tampil atau disembunyikan
- Mode maintenance halaman publik, sementara admin tetap bisa login untuk perbaikan
- Navigasi publik responsif dengan tombol menu/hamburger untuk tampilan mobile
- Role admin untuk membatasi akses superadmin, editor konten, dan operator PPDB
- Backup dan export database SQL dari panel superadmin
- Log aktivitas admin untuk login, logout, perubahan konten, penghapusan data, backup, dan ganti password
- Cek kesiapan production dari panel superadmin: environment, HTTPS, database, password awal, `.htaccess`, batas upload, dan permission folder
- Dashboard ringkasan dengan kartu statistik, ringkasan konten, agenda terdekat, dan aktivitas admin terbaru
- Notifikasi admin berupa badge pesan masuk baru di sidebar
- SEO dasar: meta description, canonical URL, Open Graph, Twitter card, sitemap.xml, dan robots.txt
- Audit keamanan final: security headers, no-cache admin, proteksi file sensitif, sanitasi nama download, dan mitigasi CSV injection
- Edit sejarah singkat, visi, misi, dan fasilitas sekolah
- CRUD agenda dan kalender kegiatan sekolah
- CRUD data guru dan staf
- CRUD berita
- Upload gambar berita
- Optimasi media upload: validasi konten/format, batas gambar 20 MiB, minimal resolusi, serta decode/re-encode dan thumbnail dengan GD yang wajib tersedia
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
- Halaman informasi PPDB/SPMB tanpa form, penyimpanan pendaftar, atau unggahan berkas pribadi
- Kebijakan privasi layanan informasi dan komunikasi sekolah
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
- Berkas PPDB legacy tetap tertutup dan memerlukan keputusan retensi; tidak ada upload/download pendaftar aktif
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

- Isi konten melalui handler admin yang berotorisasi; jangan menjalankan seed_final_content.php yang tidak direkomendasikan untuk commit/run.
- Tambahkan data guru dan staf aktif beserta foto, jabatan, mapel/bidang, dan kontak bila perlu.
- Tambahkan fasilitas sekolah asli beserta foto, kategori, lokasi, kapasitas, kondisi, dan deskripsi.
- Tambahkan agenda atau kalender kegiatan sekolah yang masih berlaku.
- Tambahkan dokumen resmi melalui admin dengan file PDF asli: kalender akademik, formulir, surat edaran, tata tertib, dan PDF sekolah.
- Tambahkan prestasi sekolah terbaru dengan foto dan tanggal prestasi.
- Tambahkan ekstrakurikuler aktif beserta pembina, jadwal, lokasi, dan deskripsi.
- Tambahkan FAQ yang sering ditanyakan orang tua, siswa, dan calon siswa.
- Tambahkan berita dan galeri dokumentasi resmi.

### PPDB

- Lengkapi informasi jalur pendaftaran.
- Lengkapi jadwal pendaftaran, verifikasi, pengumuman, dan daftar ulang.
- Lengkapi persyaratan berkas sesuai ketentuan panitia.
- Lengkapi kontak layanan panitia PPDB.
- Uji informasi PPDB dan penolakan endpoint legacy; jangan membuat pendaftar percobaan.

### Admin dan Keamanan

- Pastikan password admin awal sudah diganti.
- Buat akun operator sesuai role: `superadmin`, `editor`, dan `ppdb`.
- Pastikan tidak semua operator memakai akun `superadmin`.
- Periksa login, hak akses role, dan pengelolaan konten secara manual pada database lokal terisolasi sebelum release; suite otomatis lama sudah dihapus.
- Buka halaman `Cek Production` dan pastikan tidak ada masalah kritis.
- Jalankan backup database setelah semua konten asli masuk.
- Gunakan `Backup SQL` untuk pemulihan database saja.
- Gunakan `Paket Lengkap ZIP` untuk migrasi hosting karena berisi SQL, schema, `public/uploads`, `storage/uploads`, dan `manifest.json`.
- Jika tombol paket ZIP nonaktif, aktifkan ekstensi PHP `zip` di hosting/XAMPP.
- Atur `Tahun Ajaran Aktif` dari menu Profil & Pengaturan sebelum membuka PPDB atau mengisi agenda/dokumen/prestasi baru.
- Gunakan status `Draft` untuk konten yang belum siap tampil dan `Publish` untuk konten yang boleh muncul di halaman publik.

### Data Test

- Jangan menghapus data PPDB historis tanpa keputusan retensi.
- Hapus berita, galeri, dokumen, fasilitas, atau konten lain yang berisi kata `TEST`.
- Hapus file upload test yang tidak lagi dipakai.
- Log aktivitas boleh tetap disimpan sebagai riwayat audit internal.
