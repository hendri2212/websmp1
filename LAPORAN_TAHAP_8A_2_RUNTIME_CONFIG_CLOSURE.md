# Tahap 8A.2 Item 1 - Runtime/Config Manual Review Closure

Tanggal: 2026-10-10. Keputusan kelayakan source untuk public Git repository, bukan persetujuan production/deployment.

## Cakupan dan keputusan

Review tujuh file yang diminta pada item 1, isi working tree, diff terhadap Git, bootstrap/caller, dependency helper, hasil regression, metadata secret scanner, dan hash inventory release. Tujuh file manual lainnya tidak ditutup pada item ini.

| File | Diperlukan runtime aplikasi? | Diperlukan repository? | Bundle saat ini? | Keputusan | Layak public Git? |
| --- | --- | --- | --- | --- | --- |
| config/app.php | Ya | Ya, resolver konfigurasi | Ya | COMMIT | Ya, source saat ini |
| config/database.php | Ya | Ya, resolver dan validator DB | Ya | COMMIT | Ya, source saat ini |
| config/env.php | Ya | Ya, loader/validator bersama | Ya | COMMIT | Ya, source saat ini |
| config/apache_security.conf | Tidak; opsional konfigurasi server | Ya, referensi hardening generik | Tidak | COMMIT | Ya, snippet saat ini |
| config/php_security.ini | Tidak; opsional konfigurasi PHP hosting | Ya, referensi hardening generik | Tidak | COMMIT | Ya, snippet saat ini |
| includes/functions.php | Ya | Ya, shared helper | Ya | COMMIT | Ya, source saat ini |
| includes/repositories.php | Ya | Ya, query/repository dan consistency | Ya | COMMIT | Ya, source saat ini |

## Pemeriksaan environment dan disclosure

| File | Secret/credential operasional tertanam | Fallback development | Local path spesifik mesin/pengguna | Debug/disclosure |
| --- | --- | --- | --- | --- |
| config/app.php | Tidak teridentifikasi | Tidak untuk environment/URL/DB; default APP_NAME hanya identitas sekolah publik | Tidak | Tidak |
| config/database.php | Tidak teridentifikasi; nama key/variabel credential bukan nilai credential | Tidak; semua nilai DB eksplisit, password kosong hanya diperbolehkan jika APP_ENV local | Tidak | Tidak; error konfigurasi generik |
| config/env.php | Tidak teridentifikasi; membaca environment/.env privat | Tidak; mode dan APP_URL wajib eksplisit | Hanya .env relatif project, bukan path profil pengguna | Tidak; tidak mencetak konfigurasi |
| config/apache_security.conf | Tidak teridentifikasi | Tidak | Tidak | Menonaktifkan TRACE/signature dan mengurangi informasi server |
| config/php_security.ini | Tidak teridentifikasi | Tidak | Tidak | expose_php Off; bukan pengaturan display_errors lengkap |
| includes/functions.php | Tidak teridentifikasi; token dibuat acak saat runtime | Fallback hostname localhost hanya untuk URL relatif/local; tidak membuat koneksi DB/fallback production | Path upload/helper relatif project | Tidak ada dump/debug; logging internal generik |
| includes/repositories.php | Tidak teridentifikasi; password berasal input sah dan di-hash | Fallback konten statis existing, bukan DB credential/session/authentication | Dependency dan media relatif project | Tidak ada dump/debug; logging internal generik |

Tidak ada alasan mengganti lima source runtime dengan template/stub: file tersebut berisi logic generik, bukan credential/config instance nyata. Mengganti dengan template-only justru berisiko memutus bootstrap dan dependency release. Dua snippet sudah generik dan dapat di-commit sebagaimana adanya, tanpa membawa konfigurasi server aktual. COMMIT TEMPLATE ONLY tidak diperlukan untuk tujuh file ini.

## Review per file

### config/app.php

Memuat config/env.php, mewajibkan mode local/production dan APP_URL yang divalidasi. APP_NAME dapat memakai nama sekolah existing sebagai default identitas publik; bukan default akun/password, domain produksi, atau koneksi development. Tidak berisi path mesin, credential, switch debug, atau konfigurasi hosting aktual. COMMIT bersama env/database dan caller bootstrap.

### config/database.php

Membaca DB_HOST/PORT/DATABASE/USERNAME/PASSWORD/CHARSET melalui validator; tidak menyediakan fallback nilai DB. Host, port, identifier, charset, dan nilai required diperiksa sebelum koneksi. Production menolak loopback, root, nama database development/testing, dan password kosong. Password kosong di local harus eksplisit, bukan fallback production. Literal pada aturan penolakan bukan credential yang akan digunakan. COMMIT source resolver, bukan .env atau configuration dump.

Kebijakan larangan loopback dapat tidak cocok dengan hosting yang hanya menyediakan DB localhost. Hal itu tetap perlu keputusan/provisioning hosting terpisah; bukan alasan menahan source generik dari Git atau melemahkan guard saat review. Positive production-like database runtime masih belum diklaim lulus melalui instance loopback testing.

### config/env.php

Prioritas environment process/server kemudian .env privat relatif project. APP_ENV wajib local atau production; URL production wajib HTTPS dan bukan loopback/userinfo URL. Key wajib tidak memiliki fallback credential. Parser mengikuti format simple key=value existing, tidak mengklaim implementasi dotenv multiline/interpolation lengkap. Environment sebenarnya harus diprovision privat; .env berisi nilai nyata tidak di-commit. COMMIT loader/validator.

### config/apache_security.conf

Hanya TraceEnable Off, ServerTokens Prod, ServerSignature Off dan komentar penerapan. Tidak ada ServerName, DocumentRoot, host, certificate/private-key path, credential, atau include file lokal nyata. COMMIT sebagai snippet referensi. Terapkan eksplisit oleh administrator pada konteks server yang sesuai, bukan menyalin seluruh file ke .htaccess atau VirtualHost. Tidak menambahkannya ke web bundle.

### config/php_security.ini

Hanya expose_php Off dan komentar bahwa penerapan melalui php.ini/hosting, bukan .user.ini/ini_set. Tidak ada error_log path, session path, credential, atau debug On. COMMIT sebagai snippet referensi, bukan pengganti php.ini lengkap. display_errors Off dan logging privat tetap memerlukan kebijakan/aplikasi/hosting yang terpisah. Tidak menambahkannya ke web bundle.

### includes/functions.php

Shared helper memuat input_validation, upload_security, media_lifecycle. Review mencakup strict positive integer ID (tanpa implicit suffix coercion), ENT_QUOTES output escaping, safe URL/scheme validation, CSRF random token/hash_equals, HTTP method guard, header/download/containment, image MIME/content decode, random filename dan rollback upload gagal.

absolute_url mengembalikan APP_URL absolut jika tersedia; konfigurasi production mewajibkan URL HTTPS absolut, sehingga hostname fallback lokal tidak menjadi fallback production. e() tetap wajib dipanggil oleh caller ketika merender attribute; safe_content_url bukan pengganti escaping. Folder upload diambil dari identifier yang divalidasi dan dibangun relatif project. Tidak ada secret literal atau debug dump. COMMIT bersama dependency helper dan caller, tanpa menghapus fungsi yang sudah bekerja.

### includes/repositories.php

Review seluruh file dan perubahan terhadap Git: query menggunakan prepared values, nama field/filter dinamis dibatasi, limit numerik dikonversi/dibatasi, strict ID untuk operasi administratif non-PPDB, hashing password account management, transaksi/row locking untuk kebijakan last-superadmin, media mutation/rollback/replace cleanup, delete row-count feedback, settings transaction, dan schema helper read-only.

ensure_academic_year_columns/ensure_news_publication_column hanya memeriksa schema yang dibutuhkan, bukan menjalankan DDL/seed saat request. INSERT akun adalah fungsi pengelolaan akun yang dipanggil handler berwenang, bukan bootstrap/default account. Repository bukan endpoint authorization mandiri; caller admin tetap memerlukan auth/permission/CSRF server-side. Handler inspected untuk operasi terkait tetap menggunakan guard tersebut.

Fallback seed_content hanya membaca konten existing; public entry point menolak DB unavailable dengan response generik dan admin authentication tetap fail-closed. Tidak ada koneksi alternatif/credential fallback. PPDB yang tersisa adalah konten informasi; tidak mengaktifkan repository/workflow pendaftar legacy. COMMIT bersama schema/media helpers dan caller. Tidak mengubah schema atau PPDB pada review ini.

## Validasi terarah saat review

- Metadata-only secret audit ulang: tujuh file tersebut menghasilkan 0 findings working-tree; hasil ini digabung review manual, bukan sertifikasi absence semua kemungkinan secret.
- Pattern scan: tidak ada actual path profil pengguna, var_dump/print_r/phpinfo, atau CREATE/ALTER/DROP TABLE pada tujuh file tersebut.
- Config/HTTP model: 1012 pemeriksaan, 56 kasus, zero network database connections.
- Auth 53, role 437, session 81, runtime-schema model 70: lulus.
- Strict ID 1133, input validation 784, XSS 79, upload model 38, file-access 11, delete feedback 24, error-disclosure 58: lulus.
- Total 12 suite terarah, 3780 pemeriksaan; tidak menjalankan ulang test CRUD/restore database nyata karena tidak ada perubahan source. Hasil runtime Tahap 8A item 10 tetap menjadi bukti historis dengan batasnya.
- PHP lint lima file PHP yang direview: 0 error. Dua snippet bukan file PHP.
- Seluruh 78 hash source runtime sesuai file-hashes kandidat school-release-candidate-20261010-150123-2bd2a735; missing/source mismatch 0. Lima source runtime tetap termasuk bundle, dua snippet tetap dikecualikan.
- Tidak mengubah payload/ZIP, config privat, schema, runtime PHP, atau assertion test; tidak membutuhkan rebuild bundle identik.

## Keputusan inventaris dan manifest

Tujuh status MANUAL REVIEW pada item 1 berubah menjadi COMMIT di TAHAP_8A_GIT_FILE_CLASSIFICATION.json dan daftar Runtime & Security pada STAGING_MANIFEST_TAHAP_8A.md. Manifest dan laporan closure ini sendiri termasuk dokumentasi repository, dikecualikan dari release. Inventaris menjadi 176 COMMIT dan 7 MANUAL REVIEW, total 183 dirty = 72 modified + 111 untracked. Tidak ada git add.

Tujuh file lain yang tetap tertahan: database/initialize_admin.php, database/maintenance_legacy_schema.php, database/schema.sql, database/seed_final_content.php, tests/admin_form_smoke_test.php, DEPLOYMENT.md, LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md. Item ini tidak memberikan keputusan final atas file di luar cakupan yang diminta.

## Risiko yang tetap terbuka

- ROTATION REQUIRED BEFORE PRODUCTION untuk credential installer historis; penggunaan nyata tidak diverifikasi dan history tidak ditulis ulang.
- Provisioning domain/HTTPS, host database yang memenuhi policy, environment privat, dan penerapan snippet server/PHP masih menjadi pekerjaan hosting terpisah.
- Positive production-like DB runtime belum tervalidasi menggunakan host yang memenuhi policy; jangan menyamakan model lulus dengan validasi hosting.
- Timeout transport upload pada regression sebelumnya belum memiliki akar masalah yang terverifikasi meskipun retest lulus.
- Kapasitas/retensi/enkripsi/snapshot backup concurrent tetap risiko operasional.
- Public Git aman untuk tujuh isi source yang ditinjau; ini bukan jaminan bebas seluruh bug aplikasi atau approval seluruh working tree/production.

Tidak staging, commit, push, deploy, membuka database production/credential nyata, membuat password production, atau menghapus pekerjaan pengguna.
