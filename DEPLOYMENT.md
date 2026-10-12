# Panduan Persiapan Release dan Hosting

Status: dokumentasi persiapan, bukan izin deployment. Belum commit, push, atau deploy. Kesiapan production belum dinyatakan. Seluruh simulasi memakai database testing terisolasi, bukan database sekolah.

## 1. Release Terkontrol

- Gunakan builder tests/stage7_build_release.ps1 dan path eksplisit RELEASE_ALLOWLIST.json, dengan pengecualian RELEASE_DENYLIST.json. Jangan upload seluruh repository atau menyalin folder secara blanket.
- Struktur existing memakai index.php pada root project. Jangan mengganti document root menjadi public/ tanpa audit routing terpisah.
- Tests, fixture, installer/schema/seed/initializer, maintenance, laporan, Git metadata, konfigurasi lokal, dump, backup, log, dan hasil testing tidak masuk public release.
- Konfigurasi environment, database, serta media existing diprovision terpisah. Bundle source bukan backup data.
- Ikuti STAGING_MANIFEST_TAHAP_8A.md; disposisi file tracked legacy masih menjadi gate checkpoint. Jangan git add semua.

## 2. Konfigurasi Eksplisit

- APP_ENV=production memerlukan APP_URL HTTPS valid dan konfigurasi database eksplisit. Konfigurasi invalid menghasilkan error generik, tanpa fallback development.
- Gunakan environment server atau .env privat di root aplikasi. Struktur field aman tersedia dalam .env.production.example; nilai kosong bukan konfigurasi siap pakai.
- .env terisi tidak boleh di-commit atau masuk release source. Jika diprovision pada root aplikasi, verifikasi HTTP deny dan permission privat sebelum membuka situs.
- Kebijakan production saat ini menolak database loopback/localhost, akun root, nama database development yang diblokir, dan password kosong. Jika provider hanya menyediakan database localhost, hentikan persiapan sampai kompatibilitas ditinjau; jangan mengakali dengan APP_ENV=local.
- Development memakai APP_ENV=local dan konfigurasi development eksplisit, bukan fallback production.
- Jangan mencatat credential, DSN terisi, atau konfigurasi privat pada error/laporan publik. Review ini tidak membuat credential production.

## 3. Database dan Akun Awal

- Provisioning/restore hanya dilakukan operator berwenang pada target yang disetujui setelah backup dan verifikasi target. Audit ini tidak menjalankan migration/restore production.
- Untuk migrasi data, gunakan SQL backup aktual yang telah diverifikasi. Review target dan statement SQL sebelum import; jangan import backup testing/data sintetis ke database utama.
- database/schema.sql adalah installer offline untuk database kosong, bukan migration database existing. File memuat pemilihan database development yang harus ditinjau saat provisioning; jangan import mentah ke hosting atau database berisi data.
- Schema current tidak membuat akun default atau menjalankan seed akun. database/seed_final_content.php tidak direkomendasikan untuk commit/run; jangan menjadikannya prosedur deployment.
- Database baru tanpa akun dapat diinisialisasi dengan database/initialize_admin.php CLI-only dari paket maintenance privat setelah target/dependency disetujui. Script tidak ikut public release dan menolak database yang sudah memiliki akun.
- Username/nama harus eksplisit; password unik 12-72 byte melalui STDIN privat, bukan argument, command history, file publik, atau input terminal yang terlihat. Tidak ada password bawaan.
- Credential installer historis: ROTATION REQUIRED BEFORE PRODUCTION. Verifikasi/rotasi akun terkait secara privat; penggunaan nyata belum diverifikasi. Review ini tidak mengakses database sekolah.
- Runtime tidak membuat tabel/kolom, mengisi seed, atau mengaktifkan kembali akun nonaktif. Akun runtime tidak memerlukan privilege schema mutation.

## 4. Maintenance

- database/maintenance_legacy_schema.php CLI-only dan testing-only, dengan guard tests/stage3_database_preflight.php; bukan koneksi utama.
- Mode --plan read-only. --apply --confirm-testing-schema memerlukan persetujuan terpisah untuk database testing terverifikasi; jangan bersamaan dengan request/test lain.
- DDL dapat selesai sebagian; periksa ulang plan bila gagal. Script bukan migration production atau installer database kosong.
- Jangan menjalankan tests/admin_form_smoke_test.php: tidak memiliki guard isolasi DB dan masih mengasumsikan pendaftaran PPDB aktif.

## 5. Storage dan Permission

- Upload gambar aktif: public/uploads/ dengan subfolder news, gallery, profile, staff, facilities, achievements, dan extracurriculars. Pertahankan penolakan executable serta directory listing.
- PDF privat: storage/uploads/documents/ dan storage/uploads/accreditation/, melalui download handler yang memvalidasi path.
- State/lock/jurnal privat: storage/media-cleanup/. Workspace ZIP privat: storage/backups/.temporary/. Session/log/temp upload memakai lokasi privat hosting.
- storage/uploads/ppdb/ adalah arsip legacy, bukan kebutuhan write aktif. Jangan hapus, membuka download publik, atau mengaktifkan upload.
- Source PHP tidak memerlukan write access web process. Batasi write berdasarkan owner/group pada direktori runtime yang diperlukan; rujuk LAPORAN_TAHAP_6_PRODUCTION_FILE_PERMISSIONS.md dan LAPORAN_TAHAP_7_STORAGE_WRITABILITY.md.
- Migrasi media existing memerlukan inventaris referensi DB/file yang disetujui. Jangan menimpa jurnal atau menghapus file nyata otomatis.

## 6. Hosting dan Error Handling

- Ikuti HOSTING_REQUIREMENTS_CHECKLIST.md untuk verifikasi versi/SAPI PHP, PDO + pdo_mysql, fileinfo, mbstring, GD beserta codec, ZipArchive untuk backup lengkap, transaksi database, serta routing/security Apache atau ekuivalen yang diuji. mysqli bukan dependency koneksi runtime.
- Batas aplikasi: gambar 20 MiB, PDF 5 MiB. Uji multipart, memory, timeout, dan web SAPI. post_max_size repository 25M belum menjamin submit settings multi-file maksimum; jangan menganggap rekomendasi limit sudah diterapkan.
- Verifikasi display_errors=Off, logging privat, HTTPS, secure cookies, header keamanan, sensitive-file deny, listing off, dan upload non-executable pada stack target. File .htaccess saja bukan bukti aturan efektif.
- .user.ini bersyarat pada SAPI yang menerapkannya. config/apache_security.conf dan config/php_security.ini adalah snippet operator, bukan otomatis aktif karena di-upload.

## 7. Backup dan PPDB Legacy

- Backup memerlukan permission server-side, POST, dan CSRF. SQL berisi schema aktual/data; ZIP lengkap juga mencakup upload dan manifest, bukan credential environment.
- ZIP dapat memiliki entry database/schema.sql kosong ketika installer tidak ada dalam release. Restore data memakai database/backup-*.sql aktual, bukan entry installer tersebut.
- Workspace ZIP bukan katalog backup permanen. Simpan hasil unduhan pada storage privat; administrator harus menentukan kapasitas, retensi, akses/enkripsi, dan kebijakan konsistensi snapshot. Jangan menganggap backup final selalu tersedia di folder aplikasi.
- Data/tabel/file PPDB historis tetap dipertahankan termasuk bila tercakup full backup. Jangan mempublikasikan atau menghapusnya selama persiapan.
- PPDB/SPMB hanya informasi existing. Endpoint pendaftaran/export/berkas/status legacy tetap tombstone; jangan membuat pendaftar percobaan atau URL Dinas berdasarkan asumsi.

## 8. Gate Validasi

- Sebelum deployment yang kelak diizinkan: final secret/staged review, release scan, PHP lint, dependency check, serta smoke/regression dari bundle pada environment/database testing terisolasi.
- Uji halaman publik/pengumuman/PPDB informasi, login/logout/session, permission URL/POST langsung, CSRF, CRUD non-PPDB, upload/download, input/XSS, consistency, fail-closed konfigurasi/DB, serta backup/restore ke target testing baru.
- Verifikasi HTTPS/header serta penolakan file sensitif/executable upload pada stack calon hosting. Hasil Apache lokal bukan jaminan konfigurasi provider.
- Cleanup hanya data/file sintetis milik run teridentifikasi; jangan menghapus record hanya karena mengandung kata TEST.
- Smoke setelah deployment nyata memerlukan izin, konfigurasi dan backup yang disetujui terpisah. Jangan destructive testing pada database utama.

Dokumen ini tidak menjalankan deployment, commit, push, perubahan permission, provisioning credential production, atau schema migration.
