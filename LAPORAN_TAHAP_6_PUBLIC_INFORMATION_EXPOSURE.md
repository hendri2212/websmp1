# Tahap 6 - Public Information Exposure

Tanggal: 10 Oktober 2026. Cakupan: item 15; audit source dan HTTP lokal, tanpa database sekolah/deployment.

## Pemetaan Exposure

| Informasi | Hasil audit / tindakan |
| --- | --- |
| PHP version | PHP dapat mengirim `X-Powered-By`. Header dihapus oleh runtime error/security helpers serta kedua tabel header Apache. Snippet startup `expose_php=Off` disediakan untuk menutup kasus sebelum bootstrap. |
| Apache/OS/module version | Snippet server Apache diperluas dengan `ServerTokens Prod`; respons test hanya `Server: Apache`, tanpa versi/OS/modul. |
| Server-generated signature | `ServerSignature Off` pada snippet server; respons error tidak menampilkan footer alamat/versi server. |
| Database version | Tidak ditemukan query/output versi database pada source runtime yang dipindai. Error PDO terkontrol menghasilkan respons generik. Tidak menghubungkan database untuk memeriksa versinya. |
| Internal absolute path | Tidak ditemukan path mesin internal yang dirender pada source halaman publik. Error/exception tetap generik; logging privat dipertahankan. Probes memeriksa respons terhadap path project/snapshot/server dan SQL/config detail. |
| Framework/library version | Aset publik yang diperiksa berupa CSS lokal dan JavaScript inline aplikasi; tidak ditemukan banner library/CDN/version debug yang perlu dihapus. |
| Internal username | Nama akun/identitas admin dipakai di area berizin, bukan output publik yang diperiksa. Header/footer/profil dan halaman login anonim diuji tidak membawa username mesin atau debug/test identities. Nama staf/kontak sekolah yang memang informasi publik tidak disembunyikan. |
| Debug output | Tidak ditemukan `phpinfo`, `var_dump`, `print_r`, console debug atau debugger pada output publik/source runtime yang dipindai. Test error memeriksa stack trace, SQL dan konfigurasi tidak bocor. |
| Test marker | Tidak ditemukan marker Tahap/SMOKE/STAGE pada halaman profil/header/footer/login yang dirender menggunakan konten source existing. Fixture/test files tetap private dan ditolak HTTP. |
| Sensitive comments | Tidak ditemukan komentar HTML publik sensitif pada templates/pages/aset yang dipindai. PHP comments tetap server-side; akses source privat ditutup oleh aturan yang telah diuji. |

Diagnostic `admin/production_check.php` memang menampilkan versi PHP untuk superadmin setelah authentication/permission. Dipertahankan sebagai fungsi existing, bukan exposure anonim. Referensi literal password default untuk diagnostic juga dicatat pada laporan item 14 tanpa menuliskan nilainya; tidak menjadi jalur login alternatif.

Parameter `?v=` CSS/favicon berasal dari mtime untuk cache invalidation, bukan versi PHP/database/library. Dipertahankan supaya cache aset tetap bekerja. Tidak menghapus atribusi/lisensi atau merombak UI.

## Perbaikan Terarah

1. `.htaccess`: `Header onsuccess unset X-Powered-By` dan `Header always unset X-Powered-By`.
2. `includes/error_handling.php`: menghapus header tersebut ketika memasang handler serta ketika mengirim error generik, bila header belum terkirim.
3. `includes/functions.php`: fallback `header_remove('X-Powered-By')` pada pengiriman security headers.
4. `config/apache_security.conf`: mempertahankan `TraceEnable Off`, menambah `ServerTokens Prod` dan `ServerSignature Off`. **File sekarang harus di-Include dari konteks server Apache, bukan seluruhnya di dalam VirtualHost**, karena ServerTokens server-only.
5. `config/php_security.ini`: snippet `expose_php = Off` untuk konfigurasi startup hosting. Bukan pengganti lengkap php.ini; bukan konfigurasi database, `.user.ini`, atau pengaturan `ini_set()`.

PHP menjelaskan `expose_php` sebagai pengaturan php.ini-only yang mengontrol exposure versi melalui header: [dokumentasi resmi PHP](https://www.php.net/manual/en/ini.core.php#ini.expose-php). Konteks ServerTokens dan pengaruh ServerSignature mengacu pada [dokumentasi resmi Apache](https://httpd.apache.org/docs/2.4/mod/core.html#servertokens).

Konfigurasi global XAMPP/hosting tidak diedit. Hanya server testing terisolasi memakai snippet Apache; PHP testing memakai override startup proses berdasarkan nilai snippet yang diverifikasi. Upload file snippet saja tidak otomatis mengubah konfigurasi hosting.

## Pengujian dan Bukti

| Pemeriksaan | Hasil |
| --- | --- |
| Apache lokal nyata | **4.216 pemeriksaan lulus**, tanpa database; mencakup GET/POST/HEAD, static/PHP, redirect, error, private paths dan tombstone |
| Seluruh respons pada helper HTTP suite Apache | Tidak ada X-Powered-By; Server hanya Apache; tidak ada footer error signature versi/alamat server |
| Kontrol header aplikasi | Fixture PHP sengaja mencoba mengirim banner versi dummy; Apache menghapusnya tanpa mengubah isi respons |
| Halaman publik | Profil + header/footer asli dan login anonim, root/subfolder: 200, output lengkap, tidak ada path/debug/test marker/engine banner yang diprobe |
| PHP error HTTP dengan startup expose_php Off | **164 lulus**; 8 skenario warning/exception/error/fatal/parse, termasuk sebelum bootstrap, tidak mengirim banner PHP |
| PHP fallback dengan startup expose_php On | **164 boundary checks lulus**; 7 skenario setelah handler tidak mengirim banner, 1 parse-before tetap menunjukkan banner sebagai bukti keterbatasan fallback |
| Authentication | **53 lulus** |
| Role/permission | **437 lulus** |
| Session | **81 lulus** |
| CSRF admin non-PPDB | **792 lulus** |
| CLI guards | **209 lulus** |
| PHP lint | **134 source PHP, 0 error**; snapshot storage/temporary tests dikecualikan |

Case fallback parse-before **bukan kelulusan menyembunyikan versi pada kondisi tersebut**. Test sengaja mengonfirmasi batasnya: kode PHP belum berjalan sehingga tidak dapat menghapus header startup. Solusi harus memakai expose_php Off di hosting atau penghapusan header pada web server/proxy yang efektif.

Apache snapshot memakai boundary koneksi `db() = null`. Profil publik menggunakan konten source existing dan templates asli, bukan data database sekolah. Test error menggunakan fixture dummy aman. Test model authentication/CSRF tidak membuat akun atau mengubah data database.

## File pada Item Ini

- `.htaccess`
- `includes/error_handling.php`
- `includes/functions.php`
- `config/apache_security.conf`
- `config/php_security.ini` (baru)
- `tests/stage6_apache_security_test.php`
- `tests/stage6_error_configuration_http_test.php`
- `LAPORAN_TAHAP_6_DEFAULT_TEST_ACCOUNT.md`: melengkapi inventaris diagnostic yang menggunakan pembanding password default, tanpa nilai credential.
- Laporan ini (baru).

Assertion test lama dipertahankan. Suite Apache menambah pemeriksaan banner pada setiap respons dan fixture render profil asli; suite PHP menambah mode fallback serta assertion startup boundary. Tidak mengubah test untuk menerima bocornya path/SQL/credential sebagai hasil lulus.

## Risiko Tersisa

- Efektivitas php.ini, mod_headers, ServerTokens/ServerSignature dan proxy/CDN hosting belum diverifikasi. Operator harus menerapkan snippet pada konteks yang benar dan menguji header/error sebelum publikasi.
- Server masih teridentifikasi sebagai Apache; URL `.php` dan cookie session standar juga dapat mengindikasikan teknologi. Tidak mengganti cookie/routing hanya untuk menyamarkan stack. Pengurangan banner bukan pengganti patch dan authorization.
- Tidak mengaudit data konten pada database sekolah, isi backup historis, EXIF/media historis, riwayat Git atau konfigurasi global hosting. Tidak dapat menyatakan tidak ada marker/username internal tersimpan dalam data aktual.
- Logging internal dapat berisi path/error detail; wajib tetap privat. Diagnostic superadmin tetap boleh menampilkan versi untuk operator berwenang.
- Default credential/provisioning dan gap konfigurasi production dari item sebelumnya belum ditutup oleh penghapusan banner ini.

## Cleanup dan Kesimpulan

Server/snapshot/error log/PDF/canary testing milik suite dibersihkan. Tidak menghapus backup/file historis atau mengubah akun/schema/PPDB. Tidak ada commit, push atau deployment.

**Item 15 lulus dalam cakupan source dan HTTP lokal dengan konfigurasi server testing yang digunakan.** Exposure versi/header dan signature dikurangi tanpa mengubah fungsi website. Hosting tetap memerlukan startup/server configuration yang efektif; website belum dinyatakan siap production.
