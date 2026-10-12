# Tahap 6 Item 7 - HTTPS Readiness

Tanggal: 9 Oktober 2026. Audit source, URL/template model dan session regression. Tidak menghubungi domain luar/hosting, membuka database, membuat sertifikat atau memasang SSL. Tidak commit, push atau deploy.

## Kesimpulan

Source mendukung HTTPS untuk internal asset/form/redirect ketika APP_URL dan server HTTPS signal benar. Session Secure mengikuti HTTPS detection; HTTP localhost tetap tersedia. **HTTPS penuh production belum dapat dinyatakan LULUS:** redirect enforcement, actual TLS/host/proxy dan konten eksternal live belum diverifikasi; HTTP embed masih diizinkan oleh helper konten.

## Session Secure Cookie

includes/bootstrap.php memakai request_is_https untuk session cookie Secure; HttpOnly=true dan SameSite=Lax tetap aktif. request_is_https membaca server HTTPS atau port 443, bukan HTTP_X_FORWARDED_PROTO client.

Test session_configuration_test.php lulus 81 pemeriksaan: HTTPS direct/port, HTTP localhost/off/zero, fixation guard dan untrusted forwarded header. Secure=true pada HTTPS yang dikenali, Secure=false pada localhost HTTP. Ini pemeriksaan cookie params/configuration, bukan bukti Set-Cookie dari sesi TLS di hosting sebenarnya.

Jika SSL terminates pada proxy dan backend HTTP tidak mendapatkan server signal terpercaya, Secure dapat false meski browser memakai HTTPS. APP_URL=https saja tidak membuat request dianggap HTTPS. Konfigurasi proxy/server harus diperiksa; jangan mempercayai header forwarded dari semua client.

## HTTP ke HTTPS Strategy

Root .htaccess dan helper aplikasi belum mempunyai HTTP->HTTPS redirect; HSTS juga belum dikirim. Server-level redirect mungkin ada pada hosting, tetapi hosting belum diaudit.

Strategi rekomendasi, belum diterapkan:

1. Setelah domain/sertifikat/topologi dikonfirmasi, redirect HTTP ke HTTPS di virtual host/panel/CDN production khusus domain sekolah, sebelum aplikasi PHP berjalan. Target harus origin canonical yang disetujui, bukan hasil HTTP_HOST request bebas.
2. APP_URL production harus absolute HTTPS, termasuk subfolder yang benar. Pastikan form/login tidak pernah disajikan atau mengirim credential melalui HTTP; POST HTTP jangan diproses handler mutasi. Redirect setelah credential terlanjur dikirim tidak membatalkan kebocoran transport.
3. Jika TLS lewat proxy, tentukan trusted server mapping dan bersihkan header client; uji loop redirect serta Secure cookie. Tidak menambahkan trust-proxy fallback secara sembarangan.
4. Development localhost tetap HTTP tanpa forced redirect/HSTS. Jangan mengubah root .htaccess menjadi redirect HTTPS global karena dipakai lingkungan lokal yang sama.
5. HSTS hanya dipertimbangkan setelah HTTPS penuh/sertifikat/renewal stabil. includeSubDomains/preload bukan default; perlu inventaris dan persetujuan semua domain terdampak.

Tidak membuat rule production dengan domain tebakan atau sertifikat/credential production palsu. Detail redirect status dan topologi perlu ditentukan ketika hosting tersedia, kemudian diuji pada staging.

## Mixed Content dan Hard-coded HTTP

Scan runtime config/includes/templates/pages/admin/public source menemukan satu literal http://: namespace XML sitemap di pages/sitemap.php. Namespace tersebut adalah identifier XML, bukan URL asset yang diunduh browser; tidak diubah menjadi HTTPS hanya untuk membersihkan hasil grep.

Tidak ditemukan literal HTTP network asset pada source runtime yang discan. URL HTTP pada harness testing merupakan localhost/model dan bukan konten production. Tidak membaca data DB nyata; hasil source scan tidak membuktikan konten tersimpan bebas HTTP atau resource pihak ketiga bebas redirect ke HTTP.

Gap konten:

- safe_content_url memang mengizinkan HTTP dan HTTPS; helper ini adalah validator keamanan URL, bukan enforcement HTTPS.
- video_embed_url menormalisasi YouTube ke HTTPS, tetapi video eksternal non-YouTube dengan HTTP dikembalikan sebagai HTTP. pages/home.php menggunakannya pada iframe src, sehingga konten demikian dapat menimbulkan mixed content pada halaman HTTPS.
- External announcement/button link HTTP dapat tetap tersedia. Navigasi anchor ke HTTP bukan sama dengan mixed-content subresource, tetapi tetap keluar dari transport aman. Perlu review pemilik konten.
- Logo/gambar/media internal memakai path terkelola dan APP_URL melalui helper. APP_URL production HTTP yang salah membuat asset/action/redirect internal turun ke HTTP meskipun request HTTPS.

Jangan mengubah seluruh URL eksternal HTTP menjadi HTTPS secara otomatis tanpa membuktikan endpoint mendukungnya. Tindak lanjut aman: inventory konten URL pada database testing/salinan yang disetujui, validasi policy embed HTTPS, lalu perbaiki/disable embed yang tidak memenuhi policy tanpa mengarang URL baru. Belum mengubah helper/content pada audit ini.

## Asset URL, Form Action dan Redirect

| Area | Source / hasil |
| --- | --- |
| CSS/favicon/logo/media internal | asset/base_url; mengikuti APP_URL |
| Canonical/OpenGraph/sitemap/robots | absolute_url; APP_URL absolute memakai scheme configured, relative memakai HTTPS detection dan host request |
| Public search form | action base_url('cari'); HTTPS pada model production URL HTTPS |
| Logout form | POST action base_url('admin/logout.php'); HTTPS pada model |
| Form CRUD/login tanpa action | Submit ke current document; aman transport hanya bila halaman disajikan HTTPS |
| Redirect authentication/admin | redirect_to -> Location base_url(path); mengikuti konfigurasi APP_URL |
| External iframe | HTTP fallback masih diterima, gap yang terdokumentasi |

Production APP_URL harus validated/canonical; gap validasi source dibahas item 3 dan belum diperbaiki di item ini. Absolute URL berbasis relative APP_URL dapat mengambil HTTP_HOST request, jadi enforcement origin hosting tetap penting.

## Test dan Batas Bukti

tests/https_readiness_test.php memakai subprocess per skenario, domain example.test khusus model tanpa network, tidak membuat credential dan menolak berjalan bila .env root aktual ada. Tidak mengubah config repository. Render public/admin template memakai config/site_data.php existing dan double DB unavailable; tidak memanggil PDO.

Lima skenario: local HTTP/relative URL, direct HTTPS, port 443, proxy belum dipetakan, dan production base URL HTTP yang salah.

- **390 URL/template behavior assertions lulus:** internal assets/link/action/canonical sesuai expected base, localhost tidak dipaksa HTTPS, dan perilaku gap proxy/HTTP config/embed terkonfirmasi.
- Assertion gap memang mengharapkan perilaku source sekarang; hasil PASS model bukan klaim gap keamanan telah diperbaiki atau semua requirement HTTPS production lulus.
- **81 session regression checks lulus.**
- PHP lint: **133 source PHP, 0 error**; snapshot storage dan tests/.tmp* dikecualikan.
- git diff --check: lulus; conflict marker scan diperiksa.

Test awal salah menganggap semua href pada local template harus relatif; canonical memang absolute_url. Assertion canonical dikoreksi menjadi exact absolute_url('profil'), bukan melonggarkan pemeriksaan ke scheme/host apa pun. Source aplikasi tidak diubah dan semua asset/action assertion tetap dipertahankan.

Tidak menjalankan TLS handshake, certificate validation/expiry/chain, HTTPS browser network/mixed-content console, real Set-Cookie header, proxy loop, server redirect atau semua DB content. Apache/local HTTP hasil item 5-6 tetap bukti localhost tidak dipaksa HTTPS, bukan hasil TLS baru.

## Gate Sebelum Menyatakan HTTPS Production Lulus

- Domain, valid certificate chain/renewal dan port HTTPS hosting dikonfirmasi.
- Redirect HTTP canonical production bekerja tanpa loop dan tidak berlaku pada localhost.
- Login/logout/admin/POST hanya memakai HTTPS; Secure/HttpOnly/SameSite pada actual Set-Cookie diverifikasi.
- Proxy mapping diuji tanpa mempercayai spoofed client header; APP_URL tervalidasi absolute HTTPS.
- Browser halaman publik/admin memeriksa asset/form/redirect/subresource aktual; tidak ada HTTP mixed content, termasuk konten DB dan redirect resource eksternal.
- ACME/renewal diverifikasi: hidden-path .htaccess item 5 menolak .well-known secara default. Jika dipakai hosting, perlu pengecualian minimal/mapping server yang ditinjau, bukan membuka seluruh hidden files.

## File dan Status

Ditambah: tests/https_readiness_test.php dan laporan ini. Tidak mengubah aplikasi, APP_URL, domain, session policy, .htaccess redirect, URL konten, database, PPDB atau konfigurasi TLS.

Audit kesiapan selesai, dukungan HTTPS internal terkonfirmasi pada model, tetapi enforcement/transport/content production masih pending. Tidak menyatakan website siap production. Tidak commit, push atau deploy.
