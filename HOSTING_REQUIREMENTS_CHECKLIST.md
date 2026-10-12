# Hosting Requirements Checklist

Tanggal audit: 10 Oktober 2026. Tahap 6 item 17. Berdasarkan source aplikasi dan pemeriksaan CLI lokal, bukan jaminan kemampuan provider. Tidak memakai database sekolah, menginstal extension, atau deployment.

## PHP dan Platform

| Requirement | Klasifikasi | Dasar / verifikasi provider |
| --- | --- | --- |
| PHP minimum teknis **8.0** | Wajib dari source | `match`, union types, `mixed`, `str_contains/str_starts_with/str_ends_with`. Minimum ini hasil analisis source, bukan hasil menjalankan suite di PHP 8.0 |
| Cabang PHP yang masih didukung, patch terbaru | Wajib untuk rekomendasi hosting aman | Target awal yang disarankan **PHP 8.4 patch terbaru**, setelah regression pada versi/SAPI tersebut. Versi lokal yang diuji bukan bukti kompatibilitas penuh PHP 8.4/8.5 |
| PHP web SAPI: FPM/FastCGI atau mod_php yang dikonfigurasi benar | Wajib | Versi, extension, ini dan disabled functions web dapat berbeda dari CLI |
| Runtime PHP native | Existing | Tidak ditemukan kebutuhan Composer/vendor/framework, npm/Node, cURL atau shell/proc_open untuk request aplikasi production. Tool testing memiliki kebutuhan tambahan dan tidak ikut release |

PHP 8.0/8.1 tidak masuk cabang upstream yang saat audit masih didukung. PHP 8.2 mendekati akhir security support 31 Desember 2026; jangan memilih hosting hanya karena memenuhi angka minimum source. Referensi lifecycle: [PHP Supported Versions](https://www.php.net/supported-versions.php).

## Extension dan Capability

| Capability | Status kebutuhan | Bukti source / akibat jika tidak tersedia |
| --- | --- | --- |
| **PDO + pdo_mysql** | Wajib | `includes/database.php` memakai DSN MySQL dan prepared statements. PDO saja atau mysqli saja tidak cukup; driver mysql harus ada. Koneksi gagal membuat publik error generik/admin fail-closed |
| **fileinfo / finfo** | Wajib untuk upload aktif | MIME aktual gambar/PDF diverifikasi; upload ditolak bila validasi tidak tersedia |
| **mbstring** | Wajib | `mb_check_encoding`, `mb_strlen`, `mb_strtolower` dipanggil tanpa fallback pada validasi input/upload. Fallback excerpt teks tidak membuat extension ini opsional |
| **GD** | Wajib untuk gambar | Decode, resample, re-encode dan thumbnail. Tidak ada fallback menyimpan gambar mentah tanpa GD |
| **GD JPEG/PNG/WebP decode dan encode** | Wajib untuk seluruh format yang dijanjikan aplikasi | Periksa `imagecreatefromjpeg/png/webp`, `imagejpeg/png/webp`, `imagecreatetruecolor`, alpha dan resampling. Label extension gd saja tidak membuktikan codec tersedia |
| `gd.jpeg_ignore_warning` dapat diatur oleh handler | Wajib untuk jalur JPEG existing | Handler menonaktifkan ignore warning saat validasi JPEG; jika `ini_set` pengaturan ini gagal, upload JPEG ditolak |
| **zip + ZipArchive** | Wajib untuk backup lengkap ZIP | SQL backup dapat tetap bekerja tanpa ZIP, tetapi fitur backup lengkap tidak. Harus tersedia di web SAPI, bukan hanya CLI |
| Session, JSON, PCRE, hash/filter dan API core terkait | Wajib | Session/CSRF, encoding settings, validasi, hashing/password_verify, random_bytes; jangan menonaktifkan fungsi yang dipakai |
| CSPRNG `random_bytes`, `hash_equals`, password_hash/password_verify | Wajib | Token/nama upload/password security; bukan alasan mewajibkan extension openssl terpisah jika core sudah menyediakan capability tersebut |
| Filesystem persistent, realpath/is_uploaded_file/move_uploaded_file/readfile, flock | Wajib | Upload/private download, backup sementara dan lock/journal consistency; verifikasi locking pada storage provider |
| DOM/cURL/proc_open untuk test suites | Testing only | Tidak ditemukan sebagai dependency request production. Jalankan test dalam environment terpisah sesuai kebutuhan, jangan membuka fungsi shell web hanya demi suite |

GD codec tergantung build PHP/GD dan ZIP/mbstring tidak boleh diasumsikan otomatis aktif. Referensi provider: [GD installation](https://www.php.net/manual/en/image.installation.php), [PDO MySQL](https://www.php.net/manual/en/ref.pdo-mysql.php), [ZIP installation](https://www.php.net/manual/en/zip.installation.php), [mbstring installation](https://www.php.net/manual/en/mbstring.installation.php).

## Database Compatibility

- MySQL atau MariaDB yang masih dipelihara provider; bukan PostgreSQL/SQLite sebagai pengganti driver. Versi minimum numerik database belum ditetapkan lewat matrix lintas versi pada audit ini.
- **InnoDB/engine transaksional**, transactions, rollback, row locking `SELECT ... FOR UPDATE`, prepared statements, `ON DUPLICATE KEY UPDATE`, `SHOW COLUMNS`, `SHOW FULL TABLES`, `SHOW CREATE TABLE`, dan charset/collation `utf8mb4` yang kompatibel schema.
- Installer tidak menetapkan ENGINE pada seluruh tabel. Verifikasi engine tabel existing/default pada lingkungan testing milik hosting; jangan berasumsi semua tabel transaksional atau mengubah schema production dari audit ini.
- Credential privat dan account least-privilege pada database website saja. Runtime tidak memerlukan izin CREATE/ALTER/DROP atau provisioning akun otomatis. Backup perlu akses baca tabel/metadata yang relevan, bukan akses global/root.
- Verifikasi SQL mode, charset, collation, timezone, ukuran record/max_allowed_packet dan versi driver terhadap CRUD/backup/restore testing. Restore operator memakai target kosong/terisolasi, bukan fitur publik atau migration runtime.
- `PDO::ATTR_TIMEOUT=5` tersedia pada koneksi aplikasi. Ini tidak membuktikan seluruh query/HTTP request selesai dalam 5 detik; periksa timeout koneksi/driver/provider secara terpisah.

## Apache, Routing dan Konfigurasi

| Requirement | Keputusan |
| --- | --- |
| Apache 2.4-compatible atau ekuivalen yang diuji | Source memakai `Require all denied`, rewrite dan header rules. Label compatible/LiteSpeed saja tidak cukup sebagai bukti |
| **mod_rewrite** | Wajib untuk route existing seperti /profil, /berita, /ppdb; [.htaccess rewrite](https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html) harus benar-benar efektif |
| **mod_headers** | Diperlukan untuk proteksi header seluruh respons termasuk static/error/tombstone, kecuali provider menyediakan aturan ekuivalen |
| Authz/Directory/MIME rules | Private paths denied, listing off, uploads tidak dieksekusi, gambar normal tetap disajikan; verifikasi override yang dibutuhkan bukan sekadar file `.htaccess` ada |
| FPM/FastCGI | `.user.ini` atau pengaturan panel/FPM yang efektif; `.htaccess` php_flag hanya dalam blok mod_php dan bukan jaminan limit FPM |
| Nginx tanpa Apache | Tidak membaca `.htaccess`; perlu konfigurasi routing/deny/upload/header ekuivalen dan retest sebelum dianggap kompatibel |
| Document root | Struktur existing memakai `index.php` dan `admin/` di root project. Jangan langsung mengubah document root menjadi public/ tanpa perubahan arsitektur terpisah |
| HTTPS | Sertifikat valid, URL HTTPS dan proxy mapping yang benar; session Secure; tidak memaksa HTTPS development lokal |
| Konfigurasi privacy server | Apply snippet `config/apache_security.conf` dari konteks server (ServerTokens server-only); `config/php_security.ini` melalui startup PHP, bukan upload file saja |

## Upload dan Resource Limits

| Setting / resource | Source saat ini | Target / verifikasi |
| --- | --- | --- |
| Batas gambar aplikasi | **20 MiB per gambar**, JPG/JPEG/PNG/WebP | Provider harus mengizinkan body/file sebesar ini jika seluruh rentang fitur dipertahankan |
| Batas PDF aplikasi | **5 MiB per PDF**, dokumen/akreditasi | MIME dan struktur PDF tetap divalidasi; tidak terkait PPDB upload legacy |
| `file_uploads` | Dibutuhkan | **On** di web SAPI |
| `upload_max_filesize` | `.user.ini`/mod_php **20M** | **>=20M**; konfigurasi server/body limit tidak boleh lebih kecil. M PHP adalah kelipatan biner |
| `post_max_size` | **25M** | Cukup untuk satu gambar maksimum + form kecil, **tidak** untuk semua file maksimum pada settings. Target **50M** untuk 2 gambar x20 MiB + PDF 5 MiB + multipart/field normal; perlu keputusan dan perubahan konfigurasi hosting/repo yang sinkron |
| Proxy/WAF/HTTP request body cap | Belum diketahui | Tidak lebih rendah dari target POST; uji request multipart nyata, jangan hanya baca php.ini |
| `max_file_uploads` | Tidak ditetapkan aplikasi | **>=3** untuk logo, foto kepala sekolah dan akreditasi dalam satu form settings |
| `memory_limit` | **256M** | Baseline **>=256M**, bukan jaminan semua backup/dataset akan muat. Uji GD/high-resolution dan backup dengan dataset sintetis representatif |
| Dimensi gambar | 400x300 minimum; masing-masing sisi <=10.000; total <=20 juta pixel | Guard memory dapat menolak gambar walaupun byte file <=20 MiB; provider tidak boleh menganggap ukuran file sama dengan kebutuhan RAM decode |
| `max_execution_time` | Tidak ditetapkan source | Timeout finite; **60 detik sebagai titik awal evaluasi**, bukan minimum terbukti. Ukur CRUD/GD/backup. Jangan memakai unlimited sebagai solusi kapasitas |
| `max_input_time` | Tidak ditetapkan source | Sesuaikan upload/network policy; **120 detik sebagai titik awal evaluasi**, bukan jaminan upload lambat akan selesai |
| FPM termination/proxy timeout | Belum diketahui | Cocokkan dengan budget request PHP; proses dapat dibunuh provider lebih awal meski PHP limit tinggi |
| `max_input_vars` | Tidak ditetapkan source | Verifikasi settings lengkap tidak terpotong; jangan mengklaim default provider cukup tanpa submit/re-read test |
| Temp upload/session/log/backup | Memerlukan ruang dan permission | Directory privat writable terbatas, quota/inode dan lokasi session benar; lihat laporan permissions |
| Disk untuk backup lengkap | Temporary ZIP + SQL di memori, file upload dibaca | Sediakan headroom berdasarkan dataset/arsip, bukan asumsi rasio kompresi. Historical backup jangan dihapus otomatis |

PHP menjelaskan hubungan post_max_size/upload_max_filesize serta respons ketika body melebihi batas: [PHP core ini](https://www.php.net/manual/en/ini.core.php#ini.post-max-size). **50M belum diterapkan**; konfigurasi repository tetap 25M pada audit ini.

Backup SQL saat ini memakai fetchAll dan membangun string di memori; total kebutuhan meningkat bersama data. ZipArchive bukan pengganti perencanaan RAM/disk/timeout. Tidak ada ukuran database maksimum yang tervalidasi dari audit requirement ini.

## Bukti Lokal, Bukan Provider

Pemeriksaan capability tanpa bootstrap/database pada CLI XAMPP menghasilkan:

- PHP **8.2.12**; PDO/pdo_mysql, fileinfo, GD, mbstring dan codec JPEG/PNG/WebP tersedia.
- **zip/ZipArchive tidak tersedia secara default pada CLI tersebut.** Hasil backup Tahap 5 memakai override extension proses terisolasi; jangan menganggap konfigurasi hosting/default web sudah aktif.
- CLI: upload 40M, POST 40M, memory 512M, max_execution_time 0, max_input_time -1, max_file_uploads 20. Ini **bukan** nilai efektif `.user.ini`, web SAPI atau requirement production; tidak disalin ke konfigurasi hosting.
- Tidak mengakses database atau menjalankan phpinfo publik. Kompatibilitas PHP/provider yang berbeda tetap memerlukan retest privat.

## Checklist untuk Provider / Operator

Semua item berikut **belum dikonfirmasi pada hosting**:

- [ ] Versi web PHP tercatat privat; cabang supported dan patch terbaru, bukan hanya minimum source 8.0.
- [ ] PHP-FPM/mod_php SAPI, loaded ini dan effective web limits diketahui; CLI tidak dijadikan pengganti bukti web.
- [ ] PDO memiliki driver mysql; database MySQL/MariaDB dan koneksi charset utf8mb4 berfungsi pada target testing terisolasi.
- [ ] Engine transaksi/locking, metadata backup, SQL mode dan least-privilege runtime tervalidasi.
- [ ] fileinfo/finfo aktif dan mendeteksi MIME fixture gambar/PDF dengan benar.
- [ ] mbstring aktif, termasuk mb_check_encoding/mb_strlen/mb_strtolower.
- [ ] GD JPEG/PNG/WebP decode+encode/resample aktif; JPEG warning setting yang diperlukan dapat diatur.
- [ ] ZipArchive aktif pada web SAPI; ZIP dibuat/dibaca/cleanup pada testing tanpa mengaktifkan shell web.
- [ ] Session, CSPRNG, hashing, JSON/filter/PCRE dan fungsi filesystem/locking yang dipakai tidak diblokir.
- [ ] Root/subfolder routing, AllowOverride/aturan ekuivalen, private-path deny dan mod_headers efektif.
- [ ] Upload directory tidak menjalankan script; PDF privat hanya melalui handler, listing off.
- [ ] Upload file 20 MiB dan POST settings multi-file diuji; upload cap/body cap/max_file_uploads sinkron.
- [ ] Nilai POST 25M vs target 50M diputuskan eksplisit, bukan diasumsikan sudah berubah.
- [ ] Memory, timeout, max_input_vars dan temp quota diuji untuk CRUD/settings/GD/backup representatif.
- [ ] Owner/group, persistent storage, journal/flock, session privat dan logging sesuai checklist permissions.
- [ ] HTTPS/base URL/proxy/secure cookies serta startup banner/error settings benar.
- [ ] Full backup dan restore ke database testing baru berhasil pada stack/provider calon hosting.
- [ ] Authentication/role/session/CSRF/CRUD/upload/download/XSS dan tombstone PPDB regression lulus pada PHP target.
- [ ] Semua evidence capability disimpan privat; tidak meninggalkan phpinfo/diagnostic anonim atau test account pada release.

## Perubahan dan Status

Perbaikan kecil pada `admin/production_check.php`: GD tidak aktif kini menjadi error dan pesan menyatakan upload gambar ditolak, sesuai handler. GD aktif masih memerlukan verifikasi codec; diagnostic bukan checklist lengkap hosting. Tidak menambah fitur, extension, mengubah upload limit, schema, data atau struktur project.

Checklist requirement selesai; **provider compatibility belum tervalidasi**. Tidak ada deployment, commit atau push. Website belum dinyatakan siap production.

Validasi perbaikan diagnostic: role/permission **437 lulus**, Apache lokal **4.216 lulus** tanpa database, PHP lint **134 source file: 0 error**, conflict marker **0**, `git diff --check` lulus. Tidak mengulang functional database testing atau mengklaim provider telah lulus berdasarkan hasil lokal. File pada item ini: `admin/production_check.php` dan checklist ini; tidak menambah file test.
