# Tahap 6 - Production File Permissions

Tanggal: 10 Oktober 2026. Cakupan: item 16, rekomendasi hosting Linux/Unix; bukan perubahan permission aktual.

**Tidak menjalankan chmod, chown, perubahan ACL, deployment, atau database test.** Hanya membaca source untuk memetakan kebutuhan baca/tulis dan menambahkan laporan ini. Mode POSIX di bawah tidak diterapkan pada workspace Windows pengguna.

## Prinsip Ownership

- Tentukan UID/GID deploy, PHP-FPM/mod_php, static web server dan pengelola backup sebelum menetapkan angka permission. Gunakan identitas terisolasi per website; jangan menjalankan PHP sebagai root.
- Model yang lebih kuat: source/config dimiliki deploy user, PHP hanya membaca; PHP boleh menulis hanya direktori state tertentu. Parent directory source/config juga tidak boleh writable oleh PHP, sebab file dapat diganti melalui directory walaupun file read-only.
- Jika shared hosting menjalankan PHP dengan UID yang sama dengan pemilik release, 644/755 tidak membuat source read-only bagi PHP: owner masih dapat menulis dan mengubah mode. Ini batas isolasi hosting, bukan masalah yang selesai hanya dengan chmod.
- Directory memerlukan bit execute untuk traversal, bukan untuk menjalankan program. File PHP biasa tidak perlu bit execute POSIX; PHP tetap dapat menginterpretasikan file yang dibaca walaupun mode file 644/640. Larangan script upload harus tetap dilakukan di konfigurasi server/aplikasi.

## Matriks Rekomendasi

| Komponen | Mode directory | Mode file | Owner / akses runtime |
| --- | --- | --- | --- |
| PHP/source: root, admin, includes, pages, templates | **750** bila trusted reader group tersedia; **755** baseline kompatibel hosting | **640** dengan reader group; **644** baseline kompatibel | Deploy owner; PHP read/traverse saja; static web server mendapat traversal yang diperlukan |
| Config PHP non-secret | **750** | **640** | Deploy owner, group PHP hanya baca; tidak writable runtime; direct HTTP tetap denied |
| Credential `.env` / config lokal sensitif | **700** bila owner sama dengan pembaca, atau **750** dengan trusted PHP reader group | **600**, atau **640** hanya bila PHP memerlukan group-read | Jangan world-readable. Owner/group harus benar agar PHP tetap dapat membaca; jangan memaksakan 600 jika PHP berbeda UID tanpa akses lain |
| Aset statis source CSS/gambar/logo | **755**, atau **750** dengan web-server reader group | **644**, atau **640** dengan group tersebut | Read-only bagi runtime; tidak perlu write PHP |
| Gambar upload publik aktif | **750** dengan static-server reader group; **755** bila model hosting mengharuskan public-read | **640** dengan reader group; **644** bila diperlukan | Writable leaf milik PHP; web server read/traverse; tanpa executable file; server hanya melayani format gambar yang diizinkan |
| Upload PDF privat `storage/uploads/documents`, `accreditation` | **700**; **750** bila trusted reader/backup group diperlukan | **600**; **640** dengan trusted group | PHP read/write/cleanup; akses melalui handler, bukan static URL |
| Backup ZIP sementara `storage/backups/.temporary` | **700** | **600** | PHP membuat/membaca/menghapus file; sesuai mekanisme aplikasi saat ini |
| Backup historis / arsip | **700** untuk operator backup; **750** jika ada trusted backup group | **600**, atau **640** untuk trusted backup group | Owner operator/deploy/backup service. Runtime tidak memerlukan hak mutasi terhadap arsip historis |
| Log aplikasi privat | **700** atau **750** trusted log-reader group | **600** atau **640** | PHP append log aplikasi; operator/log collector membaca; jangan memberi PHP write ke directory konfigurasi/service log server |
| Storage parent | **750** dengan group PHP read/traverse; **700** hanya jika identitas pembaca sesuai | Proteksi/config di parent **640/600** menurut reader | Pre-create leaf writable; parent tidak perlu writable runtime bila semua leaf sudah tersedia |
| `storage/media-cleanup` | **700** | **600** | PHP menulis journal/lock dan melakukan retry/cleanup; jangan menjadikannya read-only |
| Session PHP dan temporary upload privat | **700** per website | **600** | PHP saja; gunakan direktori session/temp terisolasi, jangan folder shared world-readable |

Angka alternatif bergantung pada ownership dan kebutuhan pembaca, bukan anjuran menaikkan permission saat terjadi error. **Jangan memakai 777/666** dan jangan menjalankan perubahan recursive yang menyamakan semua file/directory.

Untuk source dan aset baseline 755/644, pertimbangkan risiko local read dari akun hosting lain. Pada multi-tenant hosting, isolasi per-user dan trusted groups/ACL lebih penting daripada mengandalkan world-readable defaults. `umask 0027` dapat dipertimbangkan jika group readership sudah dirancang; private state memerlukan 0077 atau penetapan mode eksplisit. Jangan menerapkan satu umask global tanpa menguji static uploads, session, log, backup dan hasil file yang dibuat aplikasi.

## Kebutuhan Aplikasi Existing

- `includes/functions.php` menulis gambar ke `public/uploads/{news,gallery,staff,achievements,facilities,extracurriculars,profile}` beserta `thumbs/`; PDF ke `storage/uploads/{documents,accreditation}`. Replace/delete memerlukan write + traversal pada **directory induk file**, bukan hanya write pada file.
- Helper upload masih membuat directory dengan mode permintaan 0755; file baru juga dipengaruhi owner/group/umask service. Rekomendasi 700/750 tidak otomatis dijamin source saat ini. Hosting perlu pre-provision directory dan menguji mode efektif file baru; tidak mengubah helper pada tahap rekomendasi ini.
- `includes/media_lifecycle.php` menggunakan private journal/lock di `storage/media-cleanup`; directory harus writable oleh PHP agar consistency/retry tetap bekerja.
- `admin/db_backup.php` sudah meminta directory sementara 0700 dan file 0600. Hindari membuat runtime membutuhkan write pada root project: pre-create `storage`, `storage/backups` dan `.temporary` dengan ownership yang sesuai.
- `storage/backups` tidak perlu menjadi satu directory besar yang seluruhnya writable PHP. Pisahkan hak file historis dan subdirectory sementara; akses parent hanya secukupnya untuk mencapai `.temporary`.
- Bootstrap memakai `storage/sessions` khusus development local. Production memakai session handler/save_path hosting; jangan menyalin session lokal dan jangan berasumsi setting production otomatis menunjuk folder itu.
- `error_log()` mengikuti konfigurasi logging PHP/hosting. Lokasi log production belum ditetapkan oleh source; pilih lokasi privat di luar web root bila didukung.
- Legacy PPDB tidak membutuhkan directory writable baru. Data/file historis jangan dihapus atau diubah secara otomatis; arsip manual dilakukan pada tahap terpisah.

## Source vs Directory Writable

`.htaccess`, `.user.ini`, source/config dan konfigurasi server harus dikelola deploy/operator, bukan form upload. File read-only yang berada di directory writable masih dapat dihapus/diganti oleh pemilik hak write directory; sehingga melindungi `.htaccess` dengan 444 saja tidak cukup. Pada hosting yang memungkinkan, tempatkan aturan larangan executable upload dan deny private storage pada konfigurasi server di luar tree writable.

Default least-privilege dan risiko write directory/log serta override konfigurasi dijelaskan pada [Apache Security Tips](https://httpd.apache.org/docs/2.4/misc/security_tips.html). PHP juga mengingatkan risiko session save_path yang terbuka bagi pengguna lain; lokasi session harus privat: [PHP Session Configuration](https://www.php.net/manual/en/session.configuration.php#ini.session.save-path).

**Permission filesystem bukan authorization HTTP.** File 600 yang bisa dibaca proses web masih dapat dikirim melalui HTTP bila routing/handler mengizinkannya. Tetap wajib memakai aturan deny storage/config/backup, directory listing off, server-side authorization dan hardening upload. Sebaliknya, tidak adanya bit executable pada file upload tidak mencegah handler PHP mengeksekusinya.

Lokasi backup/log/config privat di luar document root lebih kuat jika struktur hosting mendukungnya. Saat ini aplikasi masih memakai path project-relative; rekomendasi lokasi tersebut bukan instruksi memindahkan directory atau mengganti document root langsung ke `public/`, yang dapat memutus front controller/admin.

## Checklist Verifikasi Hosting

1. Identifikasi identitas PHP dan web server; verifikasi deploy owner, trusted group serta hak traversal semua parent.
2. PHP dapat membaca source/config tetapi tidak menulis/mengganti source atau config dalam model UID terpisah.
3. CRUD upload/create/replace/delete berhasil hanya pada writable leaf; static gambar tetap 200, PDF privat melalui handler tetap bekerja.
4. Backup sementara dapat dibuat/download/cleanup; PHP tidak dapat menghapus arsip historis pada model permissions yang dipilih.
5. Log internal, journal dan session tetap writable sesuai owner; hak log service/parent tidak diperluas sembarangan.
6. Periksa mode/owner/group file **baru** setelah upload/backup/log rotation; mode directory awal saja tidak cukup. Directory group inheritance/ACL harus mengikuti fasilitas hosting, bukan asumsi.
7. GET/POST langsung config, backup, log, session dan storage tetap denied; upload script tidak dieksekusi dan listing tidak muncul.
8. Pastikan account hosting lain tidak memperoleh akses private files. Review SELinux/AppArmor/ACL bila tersedia; jangan mematikannya untuk mengatasi permission error.

Checklist ini **belum dijalankan pada hosting**. Tidak memberikan command chmod massal karena path, UID/GID dan model hosting belum diketahui.

## Status

Rekomendasi item 16 selesai. Hanya laporan ini ditambahkan; tidak ada perubahan mode/ACL/owner aktual, source aplikasi, schema, credential, database, file historis, commit, push atau deployment.

Permission hosting belum tervalidasi dan website belum dinyatakan siap production. Nilai final harus ditentukan bersama konfigurasi PHP/web server provider dan diverifikasi melalui checklist di atas.
