# Tahap 7 Item 6 - Release Allowlist

Tanggal: 10 Oktober 2026. Allowlist kandidat berdasarkan dependency source yang ada, bukan asumsi contoh folder. Tidak membentuk bundle, menyalin data, menjalankan aplikasi/database, deployment, staging, commit atau push.

## Manifest

[RELEASE_ALLOWLIST.json](RELEASE_ALLOWLIST.json) berisi **78 path source/kontrol wajib secara eksplisit**, termasuk **71 PHP**, dan **1 file hosting bersyarat (.user.ini)**. Tidak ada wildcard untuk menerima seluruh isi folder. File baru yang tidak tercantum tetap ditolak sampai dependency/review diperbarui.

Manifest dan dokumen ini layak repository sebagai metadata offline, tetapi keduanya tidak masuk source bundle. Status kandidat bukan approval release: review manual item 5, rotasi akun legacy yang relevan, dan konfigurasi hosting tetap gate terpisah.

## Struktur Nyata

- Entry point publik: **index.php pada root project**. Routing Apache fallback mengarah ke file ini.
- Entry point admin: **admin/index.php, login/logout dan handler admin yang disebut satu per satu dalam JSON**.
- Tidak ditemukan public/index.php atau assets/ root yang menjadi entry point/dependensi aplikasi sekarang. Jangan mengganti document root menjadi public/ tanpa perubahan routing/struktur yang terpisah.
- public/ menyediakan public/assets/css/style.css, public/favicon.png, public/logo-sekolah.png dan public/kepala-sekolah.jpeg. Tidak ditemukan dependency local JS bundle/font/library/vendor yang perlu ditambahkan pada snapshot ini. Script inline tetap berada pada template/view.
- pages/ bukan kumpulan entry point publik yang boleh dipanggil langsung: view/handler dipilih index.php. Akses langsung dibatasi .htaccess, dengan pengecualian tombstone receipt legacy.

## Dependency Map

| Caller | Dependency | Alasan source harus disertakan |
| --- | --- | --- |
| index.php; admin handler aktif | includes/bootstrap.php | Bootstrap konfigurasi, session, header, auth dan repository |
| bootstrap.php | error_handling.php, functions.php, database.php, auth.php, repositories.php | Error generik, helper request, koneksi/query dan authorization |
| functions.php | input_validation.php, upload_security.php, media_lifecycle.php, config/app.php | ID/input/escaping, validasi konten upload, lifecycle file dan URL aplikasi |
| auth.php; repositories.php | schema_requirements.php | Pemeriksaan schema read-only, bukan migration runtime |
| database.php; bootstrap.php; db_backup.php | config/database.php | Validasi konfigurasi DB eksplisit tanpa credential hard-coded |
| config/app.php; config/database.php | config/env.php | Resolver environment dan validasi development/production |
| repositories.php | config/site_data.php | Konten/default existing yang tetap digunakan oleh site_content |
| index.php | 22 path view dinamis yang ditentukan switch dan 5 handler PHP langsung | Seluruh 27 file pages tercakup: HTML, 404/maintenance, robots/sitemap, download dan tombstone |
| index.php; admin/auth handlers | 4 templates | Header/footer publik/admin; asset dan menu |
| templates header/admin_header | public/assets/css/style.css; public/favicon.png | CSS shared dan icon; path file dipakai pula untuk versioning filemtime |
| site_data.php; view/header/footer | public/logo-sekolah.png; public/kepala-sekolah.jpeg | Branding dan gambar existing/default; jangan hilangkan hanya karena DB dapat menyimpan path lain |
| settings/CRUD upload; media lifecycle | public/uploads subfolder; storage/uploads/documents dan accreditation | Data persisten terpisah dari source bundle |
| download handler | storage/uploads/documents; storage/uploads/accreditation | File PDF privat melalui containment/handler, bukan static public |
| media lifecycle | storage/media-cleanup | Lock/jurnal runtime privat; tidak copy jurnal testing |
| backup handler | storage/backups/.temporary | Workspace ZIP privat; backup historis bukan source dependency |
| menu admin/dashboard | production_check.php; db_backup.php; ppdb.php | Diagnostic/backup berpermission dan pengelolaan informasi PPDB masih dirujuk |
| legacy URL direct | ppdb_applications.php; ppdb_download.php; ppdb_export.php; ppdb_receipt.php | Tombstone kompatibilitas yang menolak workflow lama; bukan pemulihan fitur pendaftaran |

## Ringkasan Source Allowlist

Folder berikut hanya ringkasan; **gunakan path file eksplisit JSON**, bukan copy folder secara rekursif.

| Kelompok | File | Keputusan |
| --- | ---: | --- |
| index.php | 1 | Front controller publik |
| admin PHP | 25 | Handler existing, auth, diagnostic dan tombstone |
| includes PHP | 10 | Seluruh helper runtime termasuk helper keamanan untracked |
| config PHP | 4 | app.php, database.php, env.php, site_data.php; deny HTTP direct |
| pages PHP | 27 | Seluruh route/view/handler yang dirujuk index |
| templates PHP | 4 | Header/footer shared; deny HTTP direct |
| CSS | 1 | public/assets/css/style.css |
| Gambar branding | 3 | Favicon, logo, kepala sekolah existing |
| Kontrol Apache | 3 | .htaccess, public/uploads/.htaccess, storage/.htaccess |
| TOTAL wajib | 78 | Exact source paths; bukan data persisten |
| .user.ini | 1 bersyarat | Hosting CGI/FastCGI yang menerapkannya, atau equivalent config operator |

Kontrol Apache wajib untuk kandidat Apache-compatible ini. Hosting lain membutuhkan aturan ekuivalen yang ditinjau dan diuji; menghapus .htaccess bukan cara melewati gate. PHP source di config/includes/templates/pages wajib tersedia bagi interpreter, bukan boleh dikirim sebagai source/download.

## Konfigurasi Aman vs Secret

config/app.php, database.php dan env.php adalah source resolver/validasi, bukan salinan credential terisi. config/site_data.php adalah konten/default existing. Keempatnya masuk source release setelah review.

.env.example/.env.production.example tidak diperlukan request normal dan tidak masuk web bundle. Environment production/APP_URL/credential harus tersedia eksplisit melalui environment server atau .env privat yang diprovision terpisah, tidak dari salinan workspace. Tidak membuat credential production pada tahap ini.

config/apache_security.conf dan config/php_security.ini adalah snippet operator, bukan source runtime: simpan pada paket operasi offline di luar bundle publik. .user.ini disertakan hanya bila hosting menerapkannya; jangan menganggap file ini menjamin semua konfigurasi PHP/server.

## Data dan Direktori - Provision Terpisah

- public/uploads/news, gallery, profile, staff, facilities, achievements, extracurriculars: direktori upload gambar aktif; thumbs dibuat sesuai lifecycle. Hanya kontrol public/uploads/.htaccess masuk source allowlist.
- storage/uploads/documents dan accreditation: PDF privat; sediakan direktori writable dan file existing yang disetujui melalui inventaris migrasi terpisah.
- storage/media-cleanup: state/lock/jurnal privat. Instalasi baru dapat mulai kosong; update tidak boleh menimpa/menghapus jurnal produksi existing.
- storage/backups/.temporary: direktori privat untuk ZIP transient; tidak copy backup historis atau file temporary lokal.
- Production session memakai session store hosting privat, bukan copy storage/sessions lokal. Log privat diprovision melalui hosting.
- Media existing, termasuk media tracked di public/uploads, tidak otomatis masuk source bundle. Perlu inventaris referensi DB/hash/konten yang disetujui; jangan hapus atau mempublikasikan file PPDB historis.
- Database/schema kompatibel, konten DB dan data upload adalah dependency runtime eksternal. Manifest source lengkap tidak berarti instalasi kosong siap digunakan.
- Tidak mengimpor seed atau schema, menyalin backup, mengubah permission, membuat direktori hosting, atau mengaktifkan endpoint PPDB.

## Dependency Opsional Backup

db_backup.php membaca database/schema.sql hanya jika tersedia; tidak require installer itu. Jika tidak ada, ZIP masih memakai SQL backup aktual dari SHOW CREATE TABLE dan data database, sedangkan entry database/schema.sql menjadi kosong sesuai implementasi existing.

Karena installer bukan runtime dependency, database/** tetap dikecualikan dari source bundle. Restore harus menggunakan SQL backup aktual, bukan entry installer kosong. Ini pemeriksaan source, bukan restore test baru. Perilaku bundle tanpa installer perlu divalidasi kembali saat bundle testing berikutnya; jangan mengklaim hasil Tahap 5 otomatis menguji bundle yang belum dibentuk.

## Exclude Wajib

Default deny mencakup semua path tidak terdaftar. Secara khusus: tests/fixtures/probe/snapshot; database installer/seed/initializer/maintenance; preview_router.php; utilitas lokal; .git/.gitignore; env terisi; laporan/README/dokumentasi/manifest; testing instance; dump/backup/log/session/cookie/key; config local; IDE/cache/temp; media/data privat.

Tidak ada vendor dependency runtime yang ditemukan pada snapshot ini; vendor/node_modules tidak disertakan. Bila kelak dependency nyata ditambahkan, revisi manifest berdasarkan dependency tersebut, bukan blanket copy folder.

## Pemeriksaan dan Batasan

- Seluruh 78 required files tersedia; duplicate/path traversal tidak diizinkan.
- Pemeriksaan static include menghasilkan 91 relasi require/include literal yang lengkap dalam allowlist.
- Dua pemuatan dinamis __DIR__ + view di index tidak diperlakukan sebagai include file literal: 22 nilai view ditelusuri dari switch, seluruhnya tersedia dalam manifest.
- Seluruh 27 pages tercakup oleh view atau handler langsung; 4 template, source konfigurasi dan asset lokal yang disebut di atas tercakup.
- Pemeriksaan read-only tidak menjalankan PHP aplikasi, query DB, HTTP/runtime test atau backup/restore baru. Dependency dari URL/path konten DB, iframe eksternal, dan media existing belum dapat dipastikan tanpa inventaris terpisah.
- Parser literal bukan analisis semantik seluruh kemungkinan include; allowlist perlu diperiksa ulang setelah perubahan source. Symlink/junction dan path keluar root harus ditolak pada builder kelak.
- Manifest belum builder: aturan exclude dan exception explicit harus diterapkan dengan benar pada packaging berikutnya. public/uploads/.htaccess serta storage/.htaccess adalah exception exact path, bukan izin menyalin isi directory.
- Review manual includes/functions.php, includes/repositories.php dan admin/production_check.php tetap berlaku meski diperlukan runtime. Jangan mengeluarkan dependency hanya untuk menghindari review.
- Source aplikasi tidak diubah. Tidak menjalankan PHP lint/regression baru karena hanya JSON/Markdown ditambahkan; JSON parse, file/path coverage dan git diff --check diperiksa.

Status: **allowlist kandidat selesai**. Bundle belum dibentuk, deployment tidak dilakukan, dan kesiapan production tidak dinyatakan.
