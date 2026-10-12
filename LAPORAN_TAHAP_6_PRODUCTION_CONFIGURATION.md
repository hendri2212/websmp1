# Tahap 6 Item 3 - Audit Production Configuration

Tanggal: 9 Oktober 2026. Audit source konfigurasi, model tanpa koneksi DB, serta test session/error existing. Tidak memakai credential production, tidak membuat credential palsu, tidak menjalankan migration, commit, push atau deployment. Item ini hanya menambah laporan; belum mengubah konfigurasi runtime.

## Kesimpulan

Development dan production memakai resolver environment yang sama. Parameter tersedia, tetapi pemisahan belum enforced: APP_ENV=production tidak mewajibkan konfigurasi production lengkap/valid dan masih dapat memakai default development. Hosting readiness item konfigurasi **BELUM LULUS** sampai gap berikut ditangani atau diverifikasi pada environment staging yang disetujui.

Tidak ditemukan credential production hard-coded pada file konfigurasi runtime yang diperiksa. config/database.php memuat default lokal, bukan secret production. Ini bukan secret scan menyeluruh terhadap seluruh isi/history repository; secret dapat masuk ke source tracked jika diedit kelak.

## Resolver dan Prioritas

config/env.php membaca .env root dan mencache hasil. Urutan nilai: .env -> $_ENV -> $_SERVER -> getenv -> default source. app_config juga cache per proses; db cache koneksi/null.

- .env aktual root tidak tersedia pada saat audit. Template .env.example dan .env.production.example tersedia dan memakai default/placeholder, bukan nilai hosting yang sudah diverifikasi.
- .env lokal yang ikut terunggah dapat mengalahkan environment server. APP_ENV di server saja tidak cukup membuktikan konfigurasi production terpakai.
- Nilai kosong yang tersedia dianggap konfigurasi, bukan otomatis fallback. Tidak ada pemeriksaan lengkap bahwa semua nilai penting valid/nonempty.
- Parser .env bersifat sederhana: split pada tanda sama-dengan pertama, trim whitespace/quote; bukan parser dotenv lengkap. Tidak ada interpolation/escape/comment-inline handling yang terdokumentasi. Provision secret harus menguji parsing secara privat, bukan menampilkan password.
- .env berada dalam pohon project, sehingga deny HTTP/permission filesystem wajib. .gitignore mengecualikan secret tetapi bukan kontrol HTTP dan tidak melindungi edit secret pada file tracked.

## Matriks Development vs Production

| Area | Implementasi sekarang | Development | Production / temuan |
| --- | --- | --- | --- |
| Environment | APP_ENV, default local | Lokal tetap tersedia | Hanya label/cabang session; bukan security/config gate |
| Database host | DB_HOST, default loopback | Cocok untuk DB lokal bila sengaja disetel | Tidak diwajibkan eksplisit; jangan menganggap localhost selalu salah, karena hosting dapat memakai DB lokal yang sah |
| Database name | DB_DATABASE, default website_sekolah | Default lokal | Nama production tidak tervalidasi/wajib; dapat jatuh ke database lokal bernama sama |
| Username | DB_USERNAME, default root | Default existing lokal | Akun hosting least-privilege harus diprovision; tidak ada guard untuk fallback root |
| Password | DB_PASSWORD, default kosong | Default existing lokal | Nilai hilang/kosong tetap diteruskan PDO; jangan membuat/mengisi password contoh sebagai credential production |
| Port/charset | Cast DB_PORT, default 3306; DB_CHARSET utf8mb4 | Existing | Port suffix menjadi integer; charset/DSN component tidak memiliki allowlist validasi konfigurasi |
| Base URL | APP_URL, default /website-sekolah | Mendukung subfolder lokal | Tidak ada validasi HTTPS/origin/domain/userinfo/karakter kontrol; setting production dapat tetap relatif atau invalid |
| HTTPS detection | HTTPS server atau SERVER_PORT=443 | HTTP localhost menghasilkan Secure=false | Header proxy diabaikan; TLS termination memerlukan mapping server/trusted proxy yang benar |
| Timezone | Tidak disetel aplikasi | Mengikuti PHP server | PHP dan DB timezone harus ditetapkan/selaras sesuai keputusan sekolah; belum diverifikasi |
| Debug | Tidak ada APP_DEBUG yang dipakai | Tidak ada toggle debug aplikasi | Menambahkan APP_DEBUG ke .env tidak mengubah behavior; jangan mengandalkan variable yang tidak dibaca |
| display_errors | Bootstrap web mematikan error/startup display | Error internal tidak muncul setelah bootstrap | Perlu OFF juga di PHP/server, termasuk kegagalan sebelum bootstrap |
| Logging | Bootstrap web log_errors=1; error_log internal | Mengikuti konfigurasi PHP | Tidak ada path/log rotation/retention aplikasi; lokasi dan ACL server belum diverifikasi |
| Upload image | public/uploads/<folder> hard-coded relatif root | Root existing | Sama di production; provision writable dengan script execution denied |
| Upload PDF | storage/uploads/documents atau accreditation | Root existing | Sama di production; privat HTTP, download melalui handler terkontrol |
| Backup SQL | Response download authorized | Tidak persisten dari endpoint | Sensitif termasuk data/hash akun; simpan hasil download privat |
| Backup ZIP | storage/backups/.temporary relatif root | Private guard source | Sama di production; deny HTTP, permission/storage/cleanup harus diverifikasi |

Upload directory dan backup directory tidak mempunyai environment variable untuk pindah ke lokasi eksternal. Setting UPLOAD_DIR/BACKUP_DIR buatan di .env tidak digunakan source sekarang. Tidak memindahkan path atau memperkenalkan konfigurasi baru pada audit ini karena berpengaruh pada media lifecycle/repository/backup/relasi file.

## Temuan Prioritas

### 1. Production Fallback Development

Model menetapkan APP_ENV=production tanpa DB/URL settings dan tidak membuka PDO. Source tetap menghasilkan default DB lokal, user root/password kosong dan base URL lokal. Ini membuktikan konfigurasi production tidak fail-closed ketika incomplete; bukan authentication fail-open, yang sudah diperbaiki dan tetap lulus.

Rekomendasi perbaikan terarah berikutnya: validasi environment yang diizinkan; ketika production, wajibkan konfigurasi DB/URL eksplisit dan valid, tanpa menyentuh credential sebenarnya. Tolak konfigurasi incomplete dengan error generik sebelum koneksi; jangan log isi secret. Akun DB production harus disetujui/least-privilege, tidak memakai fallback root. Tetap pertahankan localhost HTTP untuk development. Guard ini belum diimplementasikan pada item audit.

### 2. URL dan Host

base_url menggabungkan APP_URL tanpa validasi konfigurasi. Model URL dengan scheme invalid dipertahankan config, bukan ditolak. Ini bukan bukti eksploitasi request-user XSS: APP_URL adalah input konfigurasi server, bukan form publik; tetap harus tervalidasi sebelum release.

absolute_url memakai HTTP_HOST yang dibatasi karakter bila APP_URL bukan absolute. Pembatasan karakter bukan allowlist host/origin. Production harus memakai URL HTTPS canonical milik sekolah, termasuk subfolder jika digunakan, serta host enforcement hosting. Domain template existing bukan domain production yang telah disetujui.

### 3. HTTPS dan Reverse Proxy

request_is_https tidak mempercayai HTTP_X_FORWARDED_PROTO mentah, sehingga client tidak dapat hanya memakai header itu untuk mengubah hasil. Hal tersebut perlu dipertahankan. Namun jika proxy terminates TLS dan backend menerima HTTP tanpa server mapping, cookie session dapat kehilangan Secure dan absolute URL dapat memakai HTTP.

Periksa topologi hosting terlebih dahulu; mapping HTTPS harus dilakukan oleh server/trusted proxy dengan header client yang dibersihkan. Jangan memperbaiki dengan mempercayai semua forwarded headers atau mengandalkan APP_URL saja untuk menyatakan request HTTPS. Belum ada konfigurasi trusted proxy dan belum ada hosting target yang diuji.

### 4. Timezone PHP/DB

Tidak ditemukan date_default_timezone_set atau konfigurasi timezone aplikasi. PHP memakai date/strtotime untuk jadwal/nama file dan repository memakai NOW()/CURRENT_TIMESTAMP database untuk jadwal pengumuman/agenda/log.

PHP CLI lokal pada audit melaporkan Europe/Berlin. Ini berbeda dari konteks waktu pengguna, tetapi bukan bukti timezone PHP web/DB hosting. Tidak mengakses database untuk memeriksa timezone server. Sekolah/pengelola perlu menetapkan zona waktu resmi serta konvensi penyimpanan tanggal, kemudian menyelaraskan PHP dan database secara eksplisit dan menguji batas jadwal. Tidak mengonversi data tanggal historis secara otomatis.

### 5. Error dan Logging

configure_web_error_handling dipanggil awal bootstrap web: display_errors=0, display_startup_errors=0, log_errors=1. Exception pengguna generik; log exception memuat kelas/file/baris, tidak message/trace argument yang berpotensi memuat secret. Path internal di log memerlukan akses privat. Warning PHP dapat tetap mencatat detail internal pada log; log bukan artefak publik.

.user.ini existing hanya upload/post/memory; tidak menetapkan error display/logging/timezone. Error sebelum bootstrap, startup/parse entry point dan server error page perlu kontrol hosting, bukan hanya ini_set aplikasi. APP_ENV tidak membuat development dan production berbeda pada handler web; kedua mode menggunakan error generik setelah bootstrap.

db() menangkap exception koneksi dan mengembalikan null tanpa mencatat detail. Aman untuk disclosure, tetapi observabilitas koneksi kurang lengkap. Lokasi log, rotation/retention/monitoring dan permission belum ditentukan aplikasi. Tidak mengaktifkan debug atau mencatat DSN/password untuk menambah observabilitas.

### 6. Upload dan Backup

Path fixed sesuai struktur existing. Upload memakai validasi konten/path/media lifecycle; image/PDF directory meminta mode 0755, bukan bukti ACL efektif pada semua hosting. Backup meminta directory 0700/file 0600, containment dan cleanup; storage berada dalam pohon project sehingga deny HTTP harus ditegakkan server.

Jangan memakai permission 777 atau membuat direktori legacy PPDB writable hanya untuk mengikuti checklist lama. Endpoint PPDB tetap nonaktif; arsip/file existing tetap dipertahankan privat. File upload production tidak dipindahkan atau dihapus pada audit ini.

## Pemeriksaan Runtime Lokal Tanpa Database

| Pemeriksaan | Hasil |
| --- | --- |
| Model konfigurasi/precedence/fallback/URL/port/HTTPS | 15 assertion lulus; mengonfirmasi perilaku termasuk gap, bukan 15 security requirement production yang lulus |
| Session configuration existing | 81 pemeriksaan lulus: HTTP lokal, HTTPS langsung/port, fixation guard, forwarded header tidak dipercaya |
| Error disclosure existing | 58 pemeriksaan lulus: warning/exception/fatal setelah handler dipasang |
| Koneksi database | Tidak dibuka |
| Credential production | Tidak dibaca/dibuat |

Model berjalan pada subprocess tanpa .env root, mengubah environment proses itu saja, tidak menulis config/session production dan tidak memanggil db(). Model memakai default source existing, bukan membuat akun/password production sintetis. Session test memakai state test lokal dan menghancurkan session miliknya.

Snapshot PHP CLI, bukan PHP web/hosting:

- display_errors=1 dan display_startup_errors=1 sebelum handler aplikasi.
- log_errors=1, error_reporting=22527; tujuan log dikonfigurasi, path sengaja tidak ditampilkan.
- timezone Europe/Berlin; session handler files.
- upload_max_filesize/post_max_size 40M, memory_limit 512M.

Batas tersebut berbeda dari .user.ini existing 20M/25M/256M. CLI tidak membuktikan penerapan .user.ini pada web SAPI. Target hosting dan PHP web harus diperiksa tersendiri. Percobaan awal php -r gagal karena quoting command PowerShell; probe diulang via stdin dan berhasil tanpa mengubah aplikasi.

## Rekomendasi Pemisahan Operasional

1. Release source berisi resolver/config PHP, bukan secret. Provision environment hosting atau .env privat dengan satu sumber nilai yang disengaja; hindari .env lokal yang override environment server.
2. Development memakai parameter lokal yang eksplisit; production membutuhkan guard konfigurasi lengkap/valid. Jangan membuat credential contoh untuk menutupi nilai missing.
3. Tetapkan APP_URL HTTPS canonical yang dikonfirmasi pemilik, APP_ENV production dan akun DB terbatas berdasarkan konfigurasi hosting sebenarnya. Jangan menebak host, database, user atau password.
4. Audit server HTTPS/proxy, timezone PHP/DB, error display dan private logging sebelum release; jangan mempublikasikan phpinfo/config dump untuk pemeriksaan.
5. Provision hanya upload/private state yang diperlukan dengan permission sesuai akun PHP, deny direct storage serta eksekusi upload. Backup/download tetap permission+CSRF dan private storage.
6. DEPLOYMENT.md masih memiliki arahan luas upload seluruh isi project dan permission upload legacy. Manifest Tahap 6 item 1 lebih ketat; jangan mengikuti instruksi lama mentah. Penyelarasan dokumen/control hosting dapat dilakukan pada item berikutnya; tidak deploy pada tahap audit.

## Status Akhir

Audit selesai; pemisahan development/production baru tersedia secara konfigurasi, belum cukup ketat untuk dinyatakan production-ready. Risiko utama: fallback development pada production, URL/port tanpa validasi, timezone implicit, proxy mapping serta error/log/filesystem hosting belum diverifikasi.

Hanya laporan ini ditambahkan. PHP aplikasi/config/template environment tidak berubah, credential tetap tidak di-hard-code, PPDB tidak diubah. git diff --check diperiksa; PHP lint seluruh project tidak diulang karena tidak ada edit PHP. Tidak commit, push atau deploy.
