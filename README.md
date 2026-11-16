# Website Sekolah — MVC PHP Sederhana

Website sekolah berbasis PHP native + MySQL/MariaDB dengan struktur MVC prosedural, panel admin, dan PPDB/SPMB pasif/informatif. Tanpa framework, Composer, atau build npm.

## Struktur Folder MVC

```text
app/
├── bootstrap.php          # Memuat konfigurasi, session, helper, dan model
├── controllers/
│   ├── site.php           # Routing dan persiapan data halaman publik
│   ├── admin/             # Handler login, CRUD, pengaturan, dan backup
│   └── files/             # Respons PDF, sitemap, robots, dan endpoint pensiun
├── models/                # Query dan operasi data per fitur
├── views/
│   ├── site/              # Tampilan publik
│   ├── admin/             # Tampilan admin
│   └── layouts/           # Header dan footer bersama
└── core/                  # Koneksi PDO, render view, validasi, upload, dan helper
config/
├── routes.php             # Pemetaan URL admin dan endpoint kompatibilitas
├── app.php                # Konfigurasi aplikasi
├── database.php           # Konfigurasi database
├── env.php                # Pembaca .env
public/                    # CSS, gambar, dan upload publik
storage/                   # Session, dokumen privat, dan backup
database/                  # Schema dan script CLI
index.php                  # Satu pintu masuk request aplikasi
preview_router.php         # Router untuk server PHP lokal
```

Alur request: `index.php` → controller → model → view. `config/routes.php` memetakan URL admin secara eksplisit; halaman publik dipilih di `app/controllers/site.php`. URL existing seperti `/admin/news.php` tetap berlaku walaupun folder `admin/` di root sudah dipindahkan.

Controller memeriksa izin, memvalidasi request, memanggil model, lalu mengirim data ke `render_view()`. Model berisi fungsi query per fitur, misalnya `app/models/news.php`. View hanya menampilkan data dan form; pemrosesan POST dilakukan di controller. Layout dirender melalui `app/core/view.php`.

Untuk menambah fitur, tambahkan fungsi data pada model, handler pada controller, dan tampilan pada view. Daftarkan URL admin di `config/routes.php`, atau route publik di controller site. Gunakan helper CSRF, validasi, dan izin yang sudah tersedia. Folder `app/`, `config/`, `database/`, dan `storage/` diblokir dari akses HTTP langsung; jalankan server lokal dengan `preview_router.php`.

## Setup Setelah Clone

1. Siapkan PHP 8.0 atau lebih baru, MySQL/MariaDB, dan ekstensi PHP `pdo_mysql`, `mbstring`, serta `gd` untuk pemrosesan gambar. Ekstensi `zip` diperlukan untuk backup ZIP. Untuk hosting Nginx, sesuaikan contoh `config/nginx.conf`.
2. Arahkan document root Nginx ke root project (`index.php`), atau gunakan server PHP lokal dengan perintah di bawah. Jangan menjadikan `public/` sebagai document root. HTTP localhost boleh dipakai untuk development.
3. Provision database lokal baru/terisolasi dan akun database milik developer dengan privilege terbatas pada database tersebut. Jangan menggunakan database sekolah atau credential production.
4. Tentukan nama database sendiri, lalu buat database kosong dengan charset `utf8mb4` dan collation `utf8mb4_unicode_ci`.
5. Salin .env.example menjadi .env di root project, lalu isi setiap field wajib dengan konfigurasi milik developer. Template sengaja tidak dapat langsung digunakan; tidak berisi akun/password default.
6. Isi `DB_DATABASE` sesuai nama database yang dibuat, lalu jalankan `php database/migrate.php` dari root proyek. Alternatif: pilih database tersebut di phpMyAdmin dan import `database/schema.sql`.
7. Pastikan PHP dapat menulis ke `storage/` dan `public/uploads/`, dengan permission terbatas pada pengguna server. Jangan menyalin session, backup, atau arsip PPDB ke lokasi publik.
8. Untuk akun admin awal pada database kosong, ikuti bagian Inisialisasi Admin di bawah. Script inisialisasi menyediakan akun default seperti dijelaskan di bawah.
9. Buka URL development yang dipilih, lalu /admin/login.php. Bila konfigurasi salah, gunakan log privat dan pesan generik; jangan mengaktifkan display_errors publik.

## Menjalankan Lokal dengan MAMP (macOS)

Proyek ini memakai PHP native tanpa `package.json` atau `composer.json`. Tidak perlu menjalankan `npm install`, `composer install`, atau proses build untuk membuka website.

Untuk konfigurasi lokal yang sudah disiapkan:

1. Buka MAMP, lalu aktifkan MySQL pada port `8889`.
2. Jalankan server PHP dengan perintah pada bagian berikutnya.
3. Buka [website](http://localhost:8080/) atau [login admin](http://localhost:8080/admin/login.php). Gunakan akun dan password dari database yang diimport.

Database lokal `website_sekolah` terakhir diisi dari `website_sekolah_test_stage3.sql`; tidak perlu import ulang setiap menjalankan aplikasi. `.env` lokal sudah disiapkan dengan `APP_ENV=local`, `APP_URL=http://localhost:8888/websmp1`, `DB_HOST=127.0.0.1`, `DB_PORT=8889`, dan `DB_DATABASE=website_sekolah`. Credential database tersimpan di `.env` privat.

Apache MAMP tidak diperlukan untuk menjalankan aplikasi. Proyek tidak lagi menyediakan `.htaccess`; gunakan server PHP lokal atau konfigurasi Nginx.

Untuk clone baru pada mesin lain, ikuti **Setup Setelah Clone**, sesuaikan `.env` dengan konfigurasi database mesin tersebut. Jika memakai dump `website_sekolah.sql` milik sendiri, import ke database kosong melalui phpMyAdmin MAMP sebagai pengganti `database/schema.sql`; jangan import keduanya. Dump yang sudah berisi akun admin tidak membutuhkan inisialisasi admin baru.

Jika website tidak terbuka, periksa proses server PHP atau Nginx. Jika koneksi database gagal, periksa MySQL MAMP, port, nama database, serta credential di `.env`. Jika port web diubah, sesuaikan juga URL browser dan `APP_URL`.

## Menjalankan Lokal dengan Server PHP

Jalankan perintah berikut dari root proyek:

```bash
APP_URL=http://localhost:8080 php -S localhost:8080 preview_router.php
```

Buka [website](http://localhost:8080/) atau [login admin](http://localhost:8080/admin/login.php). MySQL MAMP tetap harus aktif pada port `8889` sesuai `.env` lokal; Apache tidak diperlukan. Perintah ini mengganti `APP_URL` hanya untuk proses server tersebut tanpa mengubah `.env`.

Gunakan `preview_router.php` agar routing aplikasi dan pembatasan akses file privat diterapkan oleh server PHP. Server ini untuk development lokal. Hentikan dengan `Ctrl+C`.

## Hosting dengan Nginx

`config/nginx.conf` adalah contoh server block, bukan konfigurasi yang otomatis dimuat aplikasi. Sesuaikan `server_name`, `root`, dan `fastcgi_pass` dengan domain, folder deployment, dan PHP-FPM server. Salin atau include konfigurasi tersebut melalui konfigurasi Nginx hosting, lalu validasi dengan `nginx -t` sebelum reload.

Routing diteruskan ke `index.php`, termasuk URL admin lama. Hanya front controller yang dieksekusi PHP-FPM. Akses HTTP ke folder privat, file tersembunyi, dump SQL, dan konfigurasi diblokir. Folder upload publik hanya melayani format gambar/video yang didukung. Konfigurasi HTTPS/sertifikat dan batas PHP seperti `upload_max_filesize`, `post_max_size`, `memory_limit`, serta `expose_php` ditetapkan di hosting/PHP-FPM.

Setelah memasang konfigurasi, periksa `/admin`, halaman publik, dan pastikan `/.env`, `/app/bootstrap.php`, `/storage/`, serta URL dump SQL ditolak. Contoh Nginx ini belum diuji pada server hosting. Folder `storage/sessions/` menyimpan session lokal dan akan terisi kembali saat aplikasi diakses; mengosongkannya membuat pengguna lokal perlu login ulang.

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

`.env.example` menyediakan struktur konfigurasi development. Untuk production, isi field yang sama dengan `APP_ENV=production` dan nilai sesuai hosting. .env sebenarnya di-ignore dan harus tetap privat; jangan commit atau force-add.

Resolver memeriksa $_ENV, lalu $_SERVER, lalu getenv(), kemudian .env. Nilai environment server termasuk string kosong mengalahkan nilai file; hapus override lama bila ingin memakai file. Konfigurasi dibaca sekali per proses/request; restart worker atau proses panjang setelah mengganti environment.

Parser .env existing hanya mendukung baris KEY=VALUE sederhana, komentar penuh yang dimulai #, serta trim quote luar. Tidak mendukung export, interpolasi variabel, multiline, escape shell, atau komentar inline. Isi nilai satu baris; jangan menempel contoh shell ke .env. Untuk credential dengan kebutuhan representasi khusus, gunakan environment server privat yang mempertahankan nilai persis, bukan memodifikasi parser atau menerbitkan nilai dalam laporan.

## Production Bukan Fallback Development

Production harus eksplisit. Config invalid/missing ditolak dengan error generik, bukan mencoba root/localhost/database development. Kebijakan saat ini menolak DB loopback/localhost, root, nama database development/testing yang diblokir, dan password kosong. Provider database localhost membutuhkan review kompatibilitas terpisah; jangan mengganti mode menjadi local untuk menghindari validator.

Tidak ada credential production yang disediakan repository. Untuk hosting, siapkan konfigurasi privat, backup database dan media, permission direktori, serta aturan Nginx. Manifest allowlist/denylist dan pembuat paket otomatis lama sudah dihapus karena tidak dipakai aplikasi.

## Pengujian dan Script Maintenance

Folder `tests/` telah dihapus karena bukan dependensi runtime website. Suite pengujian otomatis dan script pembuat paket release yang sebelumnya berada di folder tersebut tidak lagi tersedia.

Konten publik hanya berasal dari database. File konten default dan script maintenance legacy telah dihapus. Tabel kosong menampilkan daftar kosong; runtime tidak mengisi database otomatis.

## Migration melalui CLI

Buat database terlebih dahulu, lalu isi konfigurasi `.env`, termasuk `DB_DATABASE`. Jalankan dari root proyek:

```bash
php database/migrate.php
```

Script membaca koneksi dari `.env` dan menjalankan `database/schema.sql` pada database tersebut. Script hanya berjalan lewat CLI. Tabel yang belum ada dibuat dengan `CREATE TABLE IF NOT EXISTS`; menjalankan ulang tidak menghapus tabel atau data existing.

Migration ini untuk setup struktur awal. Perubahan kolom atau indeks pada tabel existing memerlukan SQL `ALTER TABLE` terpisah; mengubah `schema.sql` lalu menjalankan ulang tidak memperbarui tabel tersebut. MySQL menjalankan DDL dengan implicit commit, sehingga kegagalan di tengah proses dapat meninggalkan sebagian tabel sudah dibuat. Setelah penyebabnya diperbaiki, jalankan ulang.

## Seeder Konten Teks (Opsional)

Sumber seed hanya dipakai saat CLI diminta secara eksplisit; tidak dimuat runtime website. Jalankan migration terlebih dahulu. Seeder ini belum dijalankan dalam proses refactor.

```bash
php database/seed_final_content.php --seed
```

Seeder menambahkan pengaturan/konten teks ke `site_settings`, 15 fasilitas tanpa foto ke `school_facilities`, 6 ekskul tanpa foto ke `extracurriculars`, 19 informasi PPDB (termasuk 4 kriteria) ke `ppdb_entries`, 3 agenda, 2 pengumuman, dan 8 FAQ. Data seed awal perlu ditinjau, termasuk tanggal agenda. Record existing dengan kunci/judul/nama yang sama dilewati; pengaturan existing tidak ditimpa.

Seeder tidak membuat guru/staf, berita, galeri, prestasi, dokumen, foto kepala sekolah, gambar, video, maupun berkas akreditasi. Data/media tersebut diisi lewat admin. Isi seed tidak muncul di website sampai disimpan ke database. Pada database kosong, menu navigasi dan teks publik juga kosong sampai diisi dari admin atau seed teks.

## Inisialisasi Admin

`database/schema.sql` hanya membuat struktur tabel. Untuk membuat akun pertama pada database dengan tabel `admins` kosong, jalankan dari root proyek:

```bash
php database/initialize_admin.php --initialize
```

Akun yang dibuat:

- Username: `admin`
- Password: `12345` (disimpan sebagai hash)
- Nama: `Administrator`
- Role: `superadmin`, status aktif

Script menggunakan konfigurasi database dari `.env`, hanya berjalan lewat CLI, dan menolak tabel akun yang sudah berisi data. Menjalankan ulang tidak mereset akun existing.

## Fitur Admin Saat Ini

- Edit profil dan pengaturan sekolah
- Atur tema warna website dari admin: warna utama, warna kedua, warna aksen, dan warna gelap/sidebar
- Atur menu navigasi publik yang tampil atau disembunyikan
- Mode maintenance halaman publik, sementara admin tetap bisa login untuk perbaikan
- Navigasi publik responsif dengan tombol menu/hamburger untuk tampilan mobile
- Role admin untuk membatasi akses superadmin, editor konten, dan operator PPDB
- Backup dan export database SQL dari panel superadmin
- Cek kesiapan production dari panel superadmin: environment, HTTPS, database, password awal, konfigurasi Nginx, batas upload, dan permission folder
- Dashboard ringkasan dengan kartu statistik, ringkasan konten, agenda terdekat
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

- Isi konten melalui admin. Seeder teks tersedia untuk inisialisasi opsional seperti dijelaskan di atas.
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
