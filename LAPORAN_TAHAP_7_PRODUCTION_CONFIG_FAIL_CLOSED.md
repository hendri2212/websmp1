# Tahap 7 Item 1 - Production Configuration Fail Closed

Tanggal: 10 Oktober 2026. Cakupan khusus konfigurasi aplikasi/database. Tidak menyatakan seluruh Tahap 7 selesai atau website siap production.

## Hasil

**LULUS pada validasi source, model konfigurasi dan controlled HTTP yang diuji.** Tidak ada fallback localhost/root/database/password/base URL development saat konfigurasi production missing/invalid. Development berjalan hanya dengan konfigurasi lokal eksplisit.

## Perubahan Runtime

- Environment server/proses kini didahulukan dari .env. .env lokal tidak dapat mengalahkan APP_ENV=production yang disetel server. Prioritas: $_ENV, $_SERVER, getenv, .env, lalu default hanya untuk nilai nonkoneksi yang memang masih memiliki default.
- APP_ENV wajib berupa `local` atau `production`; missing/empty/unknown ditolak. Tidak ada default local implisit.
- APP_URL wajib eksplisit. Production wajib absolute HTTPS tanpa userinfo, query/fragment, whitespace/control/unsafe URL characters atau localhost/loopback. Local boleh path relatif terhadap origin seperti /website-sekolah atau absolute HTTP/HTTPS.
- DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_CHARSET wajib tersedia eksplisit pada kedua mode. Local mengizinkan password kosong hanya bila key tersedia eksplisit. Production menolak empty/whitespace-only password.
- Port harus desimal canonical 1-65535, bukan cast suffix/float/exponent. Host, database/user identifiers dan charset divalidasi sebelum membentuk DSN. Charset dibatasi utf8mb4 sesuai aplikasi existing. Password dipertahankan sebagai string, bukan disanitasi destruktif; NUL ditolak.
- Production menolak localhost/loopback/unspecified addresses termasuk IPv4-mapped loopback dan numeric/hex host alias, username root, database fallback website_sekolah dan nama bersegmen dev/development/local/test/testing.
- Bootstrap memvalidasi config app/database sebelum session dibuat dan sebelum koneksi database/authorization/query dijalankan. Public/login/admin dengan config invalid mendapatkan 503, body generik, no-store, tanpa Set-Cookie.
- db() existing tetap menangkap kegagalan config/koneksi menjadi null yang dicache; tidak membuka PDO ketika config invalid dan tidak fallback/retry dengan credential lain.
- .env.production.example kini membiarkan URL/host/database/user/password kosong. Template belum diisi sengaja gagal validasi; tidak menyediakan credential production palsu.

APP_NAME masih mempunyai default nama sekolah karena metadata nonkoneksi; bukan default environment/credential. Tidak menambahkan auto-detection production dari hostname/request. Operator wajib mengatur APP_ENV dengan benar: file local yang sengaja dipasang tanpa deklarasi production server tetap merupakan konfigurasi local eksplisit, bukan sesuatu yang dapat dideteksi sebagai salah oleh aplikasi secara otomatis.

## Kebijakan Development dan Hosting

Development memakai APP_ENV=local, APP_URL dan seluruh DB_* secara eksplisit melalui environment privat/.env. .env.example existing menyediakan struktur local; tidak membuat .env aktual atau membuka koneksi default root pada mesin pengguna. HTTP localhost tetap boleh dan cookie Secure tidak dipaksa.

Production membutuhkan APP_ENV=production dan konfigurasi privat lengkap. Tidak menampilkan atau meminta password production dalam chat. Tidak menggunakan resolver konfigurasi ini untuk mencoba host/credential production.

**Localhost production ditolak termasuk bila ditulis eksplisit**, mengikuti kebijakan ketat item ini. Hosting yang hanya menyediakan MySQL localhost/socket atau penamaan akun/database yang tidak sesuai allowlist membutuhkan keputusan compatibility tersendiri; jangan mengubah mode menjadi local untuk melewati guard. Validasi string tidak membuktikan DNS, database yang dituju, least-privilege atau credential benar; hal itu tetap gate environment target.

Timezone, HTTPS redirect/trusted proxy, kapasitas/retensi backup dan credential default installer tidak diubah pada item ini.

## Test

| Pemeriksaan baru/ulang | Hasil |
| --- | --- |
| Config model + controlled HTTP | 1012 pemeriksaan, 56 kasus, lulus |
| Invalid config sebelum PDO constructor | Tidak ada percobaan constructor pada setiap kasus invalid |
| Valid fixture config + connection failure model | Constructor spy melempar kegagalan, db() null/cache; tanpa jaringan database |
| HTTP kontrol public/login/admin path | Config invalid 503 generik/no-store/tanpa session cookie; valid config mencapai bootstrap control |
| Explicit local HTTP | Berjalan; Secure=false sesuai HTTP |
| Production server vs .env local | Server production menang; tidak downgraded oleh file |
| Authentication unit/model | 53 lulus |
| Role/permission unit/endpoint guards | 437 lulus |
| Session configuration | 81 lulus |
| HTTPS URL/template model | 314 lulus; HTTP APP_URL production kini ditolak |
| CSRF admin non-PPDB / seluruh handler aktif | 792 / 872 lulus |
| Actual Apache security/routing | 4216 lulus, tanpa database |
| PHP error HTTP startup / fallback | 164 / 164 lulus |
| Connection timeout model | Lulus; null/cache tanpa koneksi jaringan |
| Runtime schema stability model | 70 lulus |
| PHP lint | 135 source PHP, 0 error |
| Conflict marker source | 0 pada scan setelah edit konfigurasi |
| git diff --check | Lulus |
| Diff database/schema.sql | Tidak ada |

HTTP config suite memakai bootstrap asli di snapshot privat dan route kontrol, bukan klaim menjalankan login credential/CRUD nyata pada URL tersebut. PDO diganti constructor spy yang tidak membuka socket. Tidak membuka database production maupun testing pada pekerjaan item ini. Full CRUD/backup/restore database tidak diulang; hasil Tahap 6 tetap bukti historis, bukan run baru item 1.

## Penyesuaian Test dan Bukti Alasannya

- Session suite kini menyediakan mode/URL/DB fixture local eksplisit; assertion fixation/cookie/security tidak dikurangi.
- Apache fixture .env sentinel diberi konfigurasi local eksplisit yang aman; koneksi tetap dibatasi db() null. Semua assertion sensitive-file/routing/header tetap berjalan.
- HTTPS case production-http-base sebelumnya sengaja menerima HTTP sebagai bukti gap. Setelah app URL guard diperbaiki, case sekarang mewajibkan exception generik. Jumlah assertion menjadi 314 karena konfigurasi invalid berhenti sebelum rendering; case production HTTPS dan pemeriksaan template lain tetap berjalan. Ini perubahan kontrak aplikasi yang disengaja, bukan menyembunyikan test gagal.
- Error HTTP suite awal gagal pada `warning HTTP status`: send_security_headers memanggil current_path, yang membaca app config tanpa APP_ENV/APP_URL. Jadi fixture berhenti dengan error konfigurasi sebelum warning control mencapai respons 200; bukan kebocoran atau error handler rusak. Menambahkan dua nilai local pada environment server fixture mengembalikan seluruh assertion error/disclosure semula; retest kedua mode 164/164 lulus.
- New config test memperluas missing/empty/type/DSN injection/URL/port/loopback/role-independent gating serta konflik file/server, dengan ekspektasi spesifik. Tidak mengubah assertion menjadi menerima sembarang status error.

## File yang Diubah

Source/config:

- config/env.php: precedence dan validator konfigurasi bersama.
- config/app.php: mode/URL wajib tervalidasi.
- config/database.php: koneksi wajib eksplisit dan tervalidasi.
- includes/bootstrap.php: validation gate sebelum session/database.
- .env.production.example: field sensitif/URL tetap kosong untuk provisioning privat.

Test/laporan:

- tests/stage7_configuration_test.php (baru).
- tests/session_configuration_test.php.
- tests/https_readiness_test.php.
- tests/stage6_apache_security_test.php.
- tests/stage6_error_configuration_http_test.php.
- Laporan ini.

Git tetap dirty dengan perubahan Tahap 1-6 dipertahankan. Sebelum laporan ini: 194 entri modified/untracked, bukan 194 perubahan baru item ini. Config/database.php sekarang memang berubah untuk menutup gap; pernyataan tanpa diff pada laporan Tahap 6 merupakan bukti historis sebelum item ini.

## Cleanup dan Batas Testing

Snapshot config/HTTP server milik test dihentikan dan folder stage7-config-* dibersihkan. Test final menempatkan session fixture pada directory privat milik snapshot yang ikut cleanup; tidak menjalankan purge session pengguna/server secara global.

Percobaan pertama sebelum pengetatan fixture session menggunakan session.save_path default PHP untuk tiga request production-valid anonim. ID file session tersebut tidak direkam sehingga tidak dihapus dengan pencarian global yang berpotensi menyentuh session lain. Tidak ada credential/identitas login atau data siswa dalam kontrol tersebut. Ini residu session anonim testing yang mungkin masih ada pada temp PHP lokal, bukan data/database production; fixture telah diperbaiki agar run berikutnya tidak mengulanginya. Tidak mengklaim seluruh session store global telah dibersihkan.

## Keputusan Lanjutan

Item 1 lulus pada cakupan tersedia. Masih perlu provisioning/compatibility dan verifikasi web SAPI/provider sebenarnya pada pre-deployment final check. Risiko default credential installer dan pembentukan bundle merupakan pekerjaan item selanjutnya; belum diselesaikan oleh laporan ini.

Tidak commit, push, deploy, menggunakan credential/database production, menghapus data nyata, mengubah schema, redesign/menambah fitur publik atau mengaktifkan kembali PPDB.
