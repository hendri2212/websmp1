# Audit Konfigurasi Session

Tanggal: 6 Oktober 2026

## Hasil

| Aspek | Konfigurasi dan Verifikasi |
| --- | --- |
| HttpOnly | true; terbukti pada cookie HTTP preview |
| Secure | true ketika HTTPS terdeteksi; false pada HTTP localhost |
| SameSite | Lax; terbukti pada header Set-Cookie preview |
| Cookie lifetime/path | Browser-session cookie, path / |
| Strict mode | session.use_strict_mode=1; ID cookie asing ditolak |
| Cookie-only | session.use_only_cookies=1; session.use_trans_sid=0 |
| Session fixation | ID sesi tidak dikenal tidak diterima; login merotasi ID sebelum membuat identitas authenticated |
| Session regeneration | session_regenerate_id(true) setelah login berhasil; kegagalan rotasi login menolak autentikasi |
| Logout invalidation | Seluruh isi sesi dikosongkan, session_destroy menghancurkan penyimpanan sesi, cookie dikedaluwarsakan; replay ID pra-logout ditolak pada regression test |
| Development localhost | HTTP cookie tidak diberi Secure; cookie dan token CSRF bertahan antar-request |

## Perubahan Terbatas

1. Deteksi HTTPS dipusatkan pada request_is_https di includes/functions.php. HTTPS on/ON dan port 443 dalam bentuk string/integer dikenali; HTTPS off/OFF/0 pada port HTTP tidak mengaktifkan Secure. Bootstrap, URL absolut berbasis request, dan pemeriksaan production memakai deteksi yang sama.
2. APP_URL berawalan https tidak dipakai untuk memaksakan cookie Secure pada request HTTP. Header X-Forwarded-Proto dari request tidak langsung dipercaya sebagai indikator HTTPS.
3. Pengujian HTTP pertama menemukan cookie sesi berubah dan token CSRF tidak bertahan pada preview yang menggunakan session.save_path default XAMPP (C:\xampp\tmp). Dengan header Cookie eksplisit pun sesi tidak bertahan. Penyebab sistem/file permission yang tepat belum dibuktikan; penyimpanan development di dalam project mengatasi kegagalan tersebut.
4. Untuk APP_ENV=local, request non-CLI, dan session.save_handler=files, bootstrap memakai storage/sessions dengan permission pembuatan 0770. Direktori berada di area storage yang diblokir oleh router preview dan .htaccess, serta diabaikan Git. Di production atau pada handler session lain, konfigurasi storage server tetap digunakan. Pengujian CLI tetap memakai direktori test yang ditentukan skrip.

Tidak mengubah schema, role, fitur, UI, atau mekanisme password. Tidak melakukan push atau deployment.

Pemeriksaan ulang Tahap 2 lanjutan: konfigurasi cookie/bootstrap tidak memerlukan perubahan tambahan. Logout yang diperbaiki pada audit login terbaru menggunakan session_destroy, bukan rotasi menjadi sesi anonim. Kegagalan logging database tidak menghalangi penghancuran sesi. Deskripsi lama pada laporan sebelumnya merupakan riwayat.

## Verifikasi

- tests/session_configuration_test.php: **81 pemeriksaan lulus** pada 9 skenario terisolasi: HTTP localhost, HTTPS off/OFF/0, HTTPS on/ON, port 443 string/integer, dan header forwarded yang tidak dipercaya.
- Bootstrap nyata dimuat pada tiap skenario; memeriksa HttpOnly, Secure, SameSite, lifetime/path, strict mode, cookie-only, trans_sid, dan penolakan ID cookie asing.
- HTTP localhost nyata: HttpOnly=true, Secure=false, SameSite=Lax, ID cookie bertahan pada request berikutnya, dan token CSRF tetap sama pada sesi yang sama setelah perbaikan storage.
- Pengujian HTTP ID cookie asing juga membuktikan server menerbitkan ID baru.
- tests/auth_security_test.php dijalankan ulang: **53 pemeriksaan lulus**, termasuk rotasi/penghapusan ID login, penghancuran storage/status sesi saat logout, replay ID lama, kegagalan logging saat logout, dan logout berulang. Memakai session PHP nyata dan PDO simulasi tanpa database sekolah.
- Pemeriksaan HTTP localhost dijalankan ulang: **7 pemeriksaan lulus** untuk HTTP 200, HttpOnly, SameSite=Lax, Secure tidak dipaksakan pada HTTP, persistensi cookie dan token CSRF, serta penggantian ID cookie asing. Tidak menjalankan POST login/logout atau workflow pendaftaran.
- Lint terakhir seluruh project: **76 file PHP lolos**, bukan jumlah historis 73; git diff --check lulus dengan peringatan normalisasi LF/CRLF. Pada pemeriksaan sesi terbaru hanya laporan ini diubah, tidak ada perubahan kode tambahan.

## Batas Verifikasi

HTTPS diverifikasi melalui simulasi variabel server, belum menggunakan koneksi TLS ke hosting. Login dengan akun MySQL sebenarnya belum diuji. Preview HTTP membuktikan sesi anonim/form dapat bertahan, sedangkan rotasi sesi authenticated dan penghancuran logout diuji melalui regression test terisolasi. Header cookie expired pada logout authenticated melalui HTTP belum diverifikasi; atribut/scope cookie logout diperiksa dari kode. Keberhasilan session_destroy saat backend session gagal juga belum disimulasikan.

Pada hosting di balik reverse proxy/TLS termination, server harus meneruskan indikator HTTPS tepercaya ke PHP. Jangan menerima X-Forwarded-Proto secara bebas dari pengguna. Timeout sesi dan pencabutan seluruh sesi setelah perubahan password tetap pekerjaan lanjutan; tidak ditambahkan dalam audit ini.
