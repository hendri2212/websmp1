# Tahap 6 Item 4 - PHP Error Configuration

Tanggal: 9 Oktober 2026. Hardening konfigurasi PHP/web, controlled HTTP test localhost, regression error/session dan lint. Tidak membuka database atau memakai credential nyata. Tidak commit, push atau deploy.

## Perubahan

- .user.ini: display_errors=Off, display_startup_errors=Off, log_errors=On untuk hosting yang memuat per-directory PHP INI.
- .htaccess: php_flag display_errors Off, display_startup_errors Off dan log_errors On di dalam blok mod_php existing; tidak menaruh php_flag di luar guard pada hosting non-mod_php.
- Handler includes/error_handling.php existing dipertahankan: display dimatikan saat bootstrap web, exception response generik, logging tanpa exception message/trace arguments dan shutdown response generik.
- Tidak membuat path log hard-coded atau credential. Tujuan log tetap dikonfigurasi oleh PHP/server; harus privat dengan ACL/retensi yang sesuai.

Development HTTP localhost tetap dapat berjalan; warning web disembunyikan sama seperti handler existing sebelumnya. PHP CLI tidak otomatis mengikuti .user.ini. Tidak mengaktifkan display_errors untuk development web karena credential/detail internal juga tidak boleh terbuka di sana.

## Controlled HTTP Test

Harness tests/stage6_error_configuration_http_test.php memvalidasi flag .user.ini dan .htaccess, lalu menjalankan PHP server sementara di loopback pada port bebas dengan flag display/log identik. Tidak memuat koneksi DB/bootstrap database. Router fixture memerlukan token acak nonempty, loopback client dan cli-server; bukan endpoint production. Fixture/testing tetap harus dikecualikan dari web release sesuai manifest Tahap 6.

PHP built-in server tidak memuat .user.ini. Flag diterapkan eksplisit pada subprocess test; hasil tidak diklaim sebagai bukti PHP-FPM/Apache hosting sudah menerapkan file konfigurasi.

| Skenario | Hasil HTTP |
| --- | --- |
| User warning | 200, body JSON bersih; display kedua jenis error 0, logging 1 |
| Native warning undefined key | 200, tidak ada warning/details di response |
| PDOException sintetis tanpa query DB | 500, pesan generik persis |
| RuntimeException | 500, pesan generik persis |
| PHP Error | 500, pesan generik persis |
| Fatal E_USER_ERROR | 500, pesan generik persis |
| ParseError sesudah handler | 500, pesan generik persis |
| Included parse failure sebelum handler | 500, tidak membocorkan detail; bukan janji body generik dari aplikasi yang belum berjalan |

HTTP headers/body diperiksa terhadap sentinel credential, SQL, DSN, stack trace, filesystem path, root project, internal configuration dan warning/fatal text. Untuk error aplikasi, buffer partial output dibuang dan Cache-Control no-store diperiksa. Body warning tidak tercampur warning PHP.

Private log diperiksa: warning tercatat internal dan kelas PDOException tersedia tanpa exception message dari logger aplikasi. Native PHP warning/fatal log masih dapat berisi konteks sensitif/SQL/path; itu alasan log wajib privat, bukan alasan menampilkan log ke pengguna. Tidak mengklaim semua log bebas secret. Semua sentinel dummy, tidak ada credential production dibuat.

Hasil akhir: **113/113 PHP configuration dan HTTP disclosure checks lulus.** Ini bukan simulasi genuine PHP engine startup failure; kegagalan startup/main entry parse sebelum aplikasi memerlukan display Off pada konfigurasi efektif server.

## Regression dan Validasi

- tests/error_handling_test.php: 58 error disclosure checks lulus.
- tests/session_configuration_test.php: 81 checks lulus; HTTP lokal tidak dipaksa memakai Secure cookie.
- PHP lint source aplikasi/test/maintenance: 131 file, 0 error. Snapshot storage dan tests/.tmp* dikecualikan.
- Conflict marker scan: 0.
- git diff --check: lulus.
- database/schema.sql dan config/database.php: tanpa diff.
- Controlled fixture, log dan direktori stage6-error-* dibersihkan; process server milik test dihentikan, bukan process PHP lain. Harness menggunakan finally agar cleanup juga dijalankan pada assertion failure.

Tidak mengulang semua CRUD/auth/backup suites karena perubahan terbatas error configuration dan fixture. Hasil Tahap 5 tetap bukti historis, bukan run baru item ini.

## File Diubah/Ditambah

1. .user.ini: aturan display/log server-level.
2. .htaccess: aturan mod_php dalam blok existing, perubahan tahap sebelumnya tetap dipertahankan.
3. tests/stage6_error_configuration_http_test.php: harness HTTP tanpa DB dan cleanup.
4. tests/fixtures/stage6_error_http_router.php: controlled error fixture, tidak boleh deploy.
5. Laporan ini.

Tidak mengubah handler authentication, permission, CSRF, PPDB, schema, credential atau desain UI.

## Gate Hosting yang Belum Terverifikasi

1. Verifikasi SAPI dan penegakan konfigurasi: CGI/FPM/per-directory .user.ini atau php.ini/pool; mod_php dengan izin override .htaccess yang sesuai. Keberadaan file bukan bukti aturan aktif. Setting yang dikunci hosting harus ditangani pengelola server, bukan dipaksa lewat aplikasi.
2. Konfigurasi efektif display_errors dan display_startup_errors harus Off sebelum aplikasi dimuat. Jika per-directory setting tidak efektif, tetapkan melalui panel hosting/php.ini/pool; tidak mengubah konfigurasi mesin/global PHP pada audit ini.
3. log_errors boleh On, tetapi lokasi error_log harus privat di luar akses HTTP atau dilindungi server, tidak readable anonim, dengan permission/rotation/retention dan kapasitas yang sesuai. Tidak menggunakan root web sebagai lokasi log publik.
4. Uji response server-level/main entry startup/parse/404/500 pada staging yang disetujui; jangan expose phpinfo, test router atau payload melalui hosting publik. Server error page/banner/proxy belum diuji pada item lokal ini.
5. Source deny .log/.ini dan storage existing harus benar-benar ditegakkan hosting. Jangan menganggap .gitignore atau mode file saja mencegah HTTP disclosure.

## Keputusan

**LULUS untuk konfigurasi source dan controlled local HTTP disclosure yang diuji.** Warning/error tidak membocorkan detail di response pada skenario tersedia; internal logging tetap ada. Hosting readiness keseluruhan belum lulus sampai konfigurasi efektif server dan privasi log diverifikasi. Tidak menyatakan website siap production; tidak commit, push atau deploy.
