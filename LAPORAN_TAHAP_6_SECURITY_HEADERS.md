# Tahap 6 Item 8 - Security Headers

Tanggal: 9 Oktober 2026. Audit source, hardening kompatibel, actual local Apache HTTP/browser checks dan regression tanpa database. Tidak commit, push atau deploy.

## Audit Awal

includes/functions.php send_security_headers sudah mengirim X-Content-Type-Options, Referrer-Policy, X-Frame-Options dan Permissions-Policy setelah bootstrap. Admin juga mempunyai no-store/no-cache. Respons statis, Apache rejection dan tombstone yang sengaja tidak bootstrap belum mempunyai coverage header yang sama dari source Apache.

Website memakai inline script pada footer publik/admin, inline style theme, style attributes dashboard, onsubmit delete confirmation serta oncontextmenu viewer. Ada iframe video eksternal dan iframe PDF same-origin. CSP default-src/script-src/style-src ketat tanpa nonce/hash/refactor dapat memutus behavior tersebut.

## Keputusan dan Perubahan

| Header | Keputusan |
| --- | --- |
| X-Content-Type-Options | nosniff dipertahankan dan diperluas ke Apache responses |
| Referrer-Policy | strict-origin-when-cross-origin dipertahankan |
| X-Frame-Options | SAMEORIGIN dipertahankan |
| Content-Security-Policy | Hanya frame-ancestors 'self', enforcement framing minimal yang selaras SAMEORIGIN |
| Permissions-Policy | camera=(), microphone=(), geolocation=() dipertahankan; tidak ada kebutuhan API tersebut ditemukan pada workflow source yang diaudit |
| Strict-Transport-Security | Tidak ditambahkan: TLS/redirect/proxy/renewal hosting belum tervalidasi dan localhost harus tetap HTTP |

PHP helper menambah CSP framing minimal. Root .htaccess menambah blok mod_headers guarded dengan Header onsuccess unset dan Header always set untuk lima baseline header. Pattern ini menghindari header ganda pada tabel response Apache yang berbeda sekaligus menjangkau respons non-2xx; sesuai [dokumentasi resmi Apache mod_headers](https://httpd.apache.org/docs/2.4/mod/mod_headers.html).

CSP frame-ancestors mengatur siapa yang boleh membingkai halaman, bukan whitelist resource iframe yang dimuat halaman. Policy minimal ini tidak menetapkan default-src/script-src/style-src/frame-src/object-src/form-action, sehingga tidak membatasi inline script/style existing atau iframe keluar. Dasar semantik: [W3C CSP, frame-ancestors](https://www.w3.org/TR/CSP3/#directive-frame-ancestors).

Ini bukan CSP lengkap untuk pertahanan XSS dan bukan pengganti escaping/validasi. Tidak menambahkan unsafe-inline sebagai klaim CSP kuat, tidak upgrade URL eksternal otomatis, tidak mengaktifkan upgrade-insecure-requests, HSTS, includeSubDomains atau preload.

## Actual Apache HTTP Test

tests/stage6_apache_security_test.php diperluas memuat mod_headers dan memeriksa lima header dengan nilai tepat, masing-masing satu kali tanpa duplikasi. HSTS harus tidak ada pada local HTTP.

Coverage di mount root dan subfolder:

- PHP control/login 200.
- Static CSS/logo/gambar upload 200, byte/hash tetap cocok.
- Private config rejection 403.
- Public front-controller DB-unavailable response 503 generik.
- Authentication redirect 302.
- Legacy tombstone GET/POST 410.
- Routing/listing/upload/PPDB method checks sebelumnya tetap berjalan.

**936 Apache security/routing assertions lulus** pada mode browser, termasuk pemeriksaan orchestrator browser. Tidak ada database connection: source snapshot menggunakan boundary db() null yang eksplisit. Bukan full CRUD test atau hosting deployment test.

Tidak mengklaim setiap server-level 400 yang terjadi sebelum per-directory processing pasti mempunyai header .htaccess. Early protocol/startup errors, FPM/CDN/proxy dan error page hosting perlu konfigurasi virtual host/server dan verifikasi tersendiri.

## Browser Compatibility dan Frame Protection

tests/stage6_security_headers_browser_test.cjs memakai Chrome headless dan Playwright lokal pada viewport 360/1440. Network dibatasi ke dua origin localhost fixture; tidak menghubungi YouTube atau hosting.

Fixture public memakai template/header/footer asli dengan boundary DB unavailable dan beberapa safe controls. **22/22 browser checks lulus**:

- Inline script berjalan.
- Inline style dan style attribute diterapkan.
- Inline click event handler berjalan.
- Menu publik mobile membuka nav; nav desktop tetap terlihat tanpa tombol mobile tersembunyi.
- Login admin asli 200 dengan field CSRF login/logout.
- Tidak ada page script error pada template/login yang diuji.
- Same-origin iframe dapat memuat template control.
- Cross-origin frame dari localhost ke 127.0.0.1 pada port yang sama ditolak; origin memang berbeda.

Policy ini selaras X-Frame-Options existing; test membuktikan kombinasi browser yang digunakan bekerja, bukan isolasi kontribusi tiap mekanisme sendiri. Test tidak memuat PDF viewer nyata, semua form CRUD, keseluruhan dashboard/DB content atau video luar; tidak mengklaim semuanya sudah browser-tested.

Percobaan awal browser ditolak sandbox spawn EPERM; diulang dengan izin proses lokal. Percobaan selanjutnya menemukan assertion test keliru mencoba klik tombol menu desktop yang CSS existing sembunyikan. Source CSS membuktikan tombol mobile-only; assertion diperbaiki untuk click mobile dan visible desktop navigation. Pemeriksaan CSP/script/style/frame tetap dipertahankan dan aplikasi tidak diubah agar memenuhi test yang keliru.

## PHP Fallback / Error Test

Controlled PHP server tanpa Apache mod_headers juga diuji. Fixture memanggil send_security_headers sesudah error handler, memeriksa lima baseline header tepat satu kali dan tidak ada HSTS. Error/warning response tetap tidak membocorkan sentinel SQL/credential/path/config/trace.

**155/155 controlled HTTP error/header checks lulus** (113 checks sebelumnya ditambah 42 fallback-header checks). Parse failure sebelum handler dipasang tidak memperoleh seluruh PHP baseline; perlindungan server-level tetap diperlukan. App generic error sendiri mempertahankan nosniff/no-store.

## Regression dan Validasi

| Pemeriksaan | Hasil |
| --- | --- |
| Actual Apache plus browser orchestration | 936 lulus |
| Browser compatibility | 22 lulus |
| Controlled PHP error/header HTTP | 155 lulus |
| Error disclosure model | 58 lulus |
| Authentication model | 53 lulus |
| Role/permission model | 437 lulus |
| CSRF semua handler aktif | 548 lulus |
| Session | 81 lulus |
| HTTPS URL/template model | 390 lulus, termasuk gap assertions yang tetap didokumentasikan item 7 |
| PHP lint | 133 source files, 0 error |
| JavaScript syntax | node --check lulus |
| Conflict markers | 0 |
| git diff --check | Lulus |
| Schema/config DB diff | Tidak ada |

Test authentication/role/CSRF tersebut memakai model/probe, bukan login atau CRUD pada database production. No database used pada fixture HTTP/Apache/browser. Tidak mengulang seluruh runtime DB/backup suites. Source snapshot, safe canary, log dan browser/server milik testing dibersihkan; tidak ada stage6-* fixture tersisa pada pemeriksaan akhir.

## File Diubah

- includes/functions.php: CSP frame-ancestors minimal pada helper existing.
- .htaccess: baseline header mod_headers guarded; aturan access/listing/error/routing sebelumnya dipertahankan.
- tests/stage6_apache_security_test.php: exact/deduplicated header assertions, fixture dan optional --browser.
- tests/stage6_security_headers_browser_test.cjs: browser compatibility probe baru.
- tests/fixtures/stage6_error_http_router.php: PHP fallback header probe.
- tests/stage6_error_configuration_http_test.php: fallback header assertions.
- Laporan ini.

Tidak mengubah desain, schema, credential, PPDB workflow atau menghapus file/data nyata.

## Risiko dan Rekomendasi Berikutnya

1. Apache guard tidak menjamin mod_headers aktif pada hosting. PHP bootstrap fallback tidak melindungi static/tombstone/early server errors tanpa equivalent server headers; verifikasi effective server config.
2. CSP penuh perlu tahap Report-Only resource policy di staging, inventory origin nyata, nonce/hash strategy untuk script/style dan penanganan event/style attributes. Jangan enforce ketat sebelum browser test seluruh workflow. Tidak membuat reporting endpoint baru atau mengirim laporan ke URL eksternal.
3. CSP frame baseline sekarang enforced; Report-Only rencana berikutnya bukan pengganti enforcement frame yang sudah tersedia. Same-origin PDF harus dipertahankan, video provider/origin aktual harus dipetakan sebelum frame-src dibatasi.
4. Central policy source Apache dan PHP harus diselaraskan. Jika hosting sudah memiliki CSP lebih ketat, review/merge kebijakan sebelum memakai Header always set agar tidak menimpa/memperlemah policy host. Jangan menambahkan policy ganda dengan asumsi hasilnya lebih longgar.
5. Permissions-Policy menolak kamera/mic/geolocation yang tidak dipakai situs; kebutuhan baru/provider yang benar-benar memerlukan fitur itu harus direview, bukan dibuka ke semua origin.
6. HSTS masih pending sampai HTTPS penuh dan renewal/redirect/proxy terbukti aman. Jangan menerapkannya pada localhost, subdomain belum dipetakan atau origin belum memiliki TLS yang valid.

## Status

**LULUS untuk baseline headers, framing minimal dan compatibility pada skenario local HTTP/browser yang tersedia.** CSP resource policy lengkap dan HSTS sengaja belum diaktifkan. Hosting readiness/website siap production tidak dinyatakan lulus; enforcement server tujuan dan cakupan browser penuh tetap memerlukan audit. Tidak commit, push atau deploy.
