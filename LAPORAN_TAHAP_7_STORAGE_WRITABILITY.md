# Tahap 7 Item 15 - Storage Writability / Final Permission Requirements

Tanggal: 10 Oktober 2026. Cakupan: audit source dan daftar kebutuhan permission release, bukan penerapan permission pada Windows atau hosting.

Tidak melakukan chmod/chown/ACL, menulis probe ke folder aplikasi, mengakses database, menghapus data/file historis, mengubah bundle, commit, push, atau deploy. File aplikasi tidak berubah.

## Daftar Final Writable Runtime

Semua path relatif ke root release kecuali path hosting yang harus ditetapkan operator.

| Path | Keperluan dan akses PHP | Requirement |
| --- | --- | --- |
| `public/uploads/news` dan `thumbs/` | Buat/baca gambar berita dan thumbnail; hapus saat replace/delete/rollback | Wajib writable |
| `public/uploads/gallery` dan `thumbs/` | Gambar galeri dan thumbnail | Wajib writable |
| `public/uploads/profile` dan `thumbs/` | Logo/foto profil sekolah dan thumbnail | Wajib writable |
| `public/uploads/staff` dan `thumbs/` | Foto staf dan thumbnail | Wajib writable |
| `public/uploads/facilities` dan `thumbs/` | Gambar fasilitas dan thumbnail | Wajib writable |
| `public/uploads/achievements` dan `thumbs/` | Gambar prestasi dan thumbnail | Wajib writable |
| `public/uploads/extracurriculars` dan `thumbs/` | Gambar ekstrakurikuler dan thumbnail | Wajib writable |
| `storage/uploads/documents` | Buat/baca/hapus PDF dokumen; download melalui handler | Wajib writable, privat |
| `storage/uploads/accreditation` | Buat/baca/hapus PDF akreditasi; handler settings/download | Wajib writable, privat |
| `storage/media-cleanup` | Journal JSON, `lifecycle.lock`, retry/cleanup; memerlukan flock | Wajib writable, privat |
| `storage/backups/.temporary` | Buat/baca/hapus ZIP full backup selama request | Wajib writable untuk full ZIP backup; SQL download tidak membutuhkan file backup persisten |
| Lokasi `error_log` PHP/hosting | Menulis log internal; dapat berupa file privat atau logging service hosting | Wajib tersedia sebagai tujuan logging; bukan otomatis `storage/logs` |
| `upload_tmp_dir` PHP, atau temporary directory efektif | PHP menyimpan multipart upload sebelum validasi/move | Wajib writable oleh PHP; privat/per-site sesuai fasilitas hosting |
| `session.save_path` bila handler files | Buat/baca/hapus session | Wajib writable dan privat; production mengikuti hosting |

Tidak ada kebutuhan write runtime baru untuk PPDB legacy. File historis dipertahankan; jangan memberi akses mutasi hanya karena directory tersebut pernah digunakan untuk pendaftaran.

`storage/sessions` dibuat bootstrap khusus APP_ENV local. Mode production tidak otomatis memilih path itu; jangan menyalin session development atau menganggap semua hosting menggunakan session files. Jika handler session bukan files, akses storage/layanan mengikuti handler provider.

Tidak menemukan kebutuhan runtime cache atau temporary aplikasi tambahan dalam source bundle. Jangan memberi write ke `storage/cache`, `storage/tmp`, seluruh storage, atau seluruh root tanpa kebutuhan yang terbukti.

## Permission Linux/Unix yang Direkomendasikan

Angka adalah profil least-privilege dengan ownership terpisah yang benar, bukan perintah chmod universal. Windows menggunakan ACL setara; nilai ini tidak diterapkan ke workspace.

| Komponen | Directory | File | Ownership / akses |
| --- | --- | --- | --- |
| Source PHP/config non-secret dan parent release | 750 | 640 | Owner deploy; trusted PHP/web-reader group hanya read/traverse, tanpa write |
| Asset statis source | 750/755 | 640/644 | Read-only runtime; alternatif public-read hanya jika dibutuhkan hosting |
| Leaf gambar publik dan thumbs | 750 | 640 | Owner PHP; group static-server/backup reader bila berbeda UID; PHP write/delete, server read |
| PDF privat, journal/lock, temporary ZIP | 700 | 600 | Owner PHP; trusted backup-reader group dapat memakai 750/640 bila benar-benar diperlukan |
| Session, upload temp, log aplikasi privat | 700 | 600 | PHP/per-site service sesuai identitas; trusted log reader dapat diberi 750/640 |
| Secret configuration `.env` | Parent source tetap read/traverse sesuai identitas | 600 jika pembaca owner, atau 640 dengan group PHP read-only | Owner deploy, bukan runtime-writable; 600 tidak cocok jika PHP berbeda UID dan tidak dapat membaca |
| Backup historis / PPDB historis | 700/750 | 600/640 | Owner operator/backup service; tidak memberi runtime hak delete/write terhadap arsip |

Tidak menggunakan 777/666. 755/644 pada source tidak menjamin PHP read-only bila PHP memakai UID pemilik file: owner tetap dapat menulis dan mengganti mode. Isolasi UID deploy/PHP atau kontrol hosting setara diperlukan.

File upload tanpa executable bit POSIX masih dapat dieksekusi oleh handler PHP bila web server salah konfigurasi. Tetap wajib mempertahankan aturan larangan executable dan HTTP deny private storage. Dasar perlindungan server, parent directory, dan least privilege: [Apache Security Tips](https://httpd.apache.org/docs/2.4/misc/security_tips.html).

## Pre-Provisioning Sebelum Menjadikan Source Read-Only

1. Operator membuat seluruh tujuh directory gambar dan masing-masing `thumbs` (14 leaf), dua directory PDF, `storage/media-cleanup`, serta `storage/backups/.temporary` terlebih dahulu. Bundle hanya membawa control file `.htaccess`, tidak membawa state/runtime directories lengkap.
2. Parent `public`, `public/uploads`, `storage`, `storage/uploads`, dan `storage/backups` cukup readable/traversable, tidak perlu writable oleh PHP setelah leaf tersedia. Helper memiliki mkdir fallback; jika leaf hilang pada parent read-only, operasi terkait gagal. Jangan mengatasi provisioning yang kurang dengan membuka write source/root.
3. Source root, `admin`, `includes`, `pages`, `templates`, `config`, `public/assets`, entry point, `.htaccess`, dan `.user.ini` tidak memerlukan runtime write. Settings/profil dan activity log aplikasi tersimpan di database, bukan menulis file source/config.
4. `public/uploads/.htaccess` dan `storage/.htaccess` tetap dikelola deploy/operator pada parent non-writable. File read-only dalam directory writable masih dapat dihapus/diganti; sebisa mungkin tempatkan enforcement upload/private storage juga di konfigurasi web server yang tidak dapat diubah PHP.
5. Pastikan service umask/group/ACL menghasilkan mode file baru yang tepat. Helper image/PDF meminta mkdir 0755 dan reservasi file tidak menetapkan mode 0600 secara eksplisit; permission final dipengaruhi umask. Journal juga mengikuti umask saat fopen. ZIP backup menetapkan file 0600. Pre-provision directory dengan mode lebih ketat tidak otomatis menjamin mode semua file baru.
6. Direktori session/temp/log privat disediakan melalui konfigurasi provider, idealnya di luar DocumentRoot. Jangan memindahkan directory aplikasi project-relative tanpa audit dependency.

PHP mensyaratkan tujuan upload temporary yang dapat ditulis proses PHP; saat `upload_tmp_dir` tidak tersedia dapat terjadi penggunaan temporary directory sistem. Operator harus memverifikasi lokasi efektif, bukan hanya nilai konfigurasi yang diharapkan: [PHP Core Directives](https://www.php.net/manual/en/ini.core.php#ini.upload-tmp-dir). Session files perlu lokasi privat dan pemilik yang sesuai: [PHP Session Configuration](https://www.php.net/manual/en/session.configuration.php#ini.session.save-path).

## Gap Diagnostic Existing

`admin/production_check.php` masih memeriksa writable pada `public/uploads` dan `storage/uploads` parent. Ini terlalu luas untuk profil pre-provisioned least-privilege dan dapat memberikan false error meskipun leaf berfungsi. Sebaliknya, checker belum mencakup semua `thumbs`, PDF accreditation, media journal, temporary ZIP, maupun tujuan log/session/temp efektif.

Catatan ini tidak diatasi dengan memberi write parent atau menonaktifkan protection. Checker tidak diubah dalam item yang meminta daftar requirement ini. Penyesuaian diagnostic sebelum menjadikannya gate hosting perlu dilakukan terpisah; daftar di atas lebih lengkap daripada output checker existing.

## Validasi yang Masih Wajib di Hosting

- Verifikasi UID/GID PHP, static server, deploy, backup/log service; seluruh parent dapat dilalui tetapi source tidak dapat diganti oleh runtime.
- Verifikasi create/read/replace/delete upload dan thumbnail, PDF handler, lock/journal retry, temporary ZIP cleanup, logging, dan session pada UID web process sebenarnya.
- Verifikasi owner/group/mode file baru, bukan hanya is_writable directory; termasuk sesudah rotation log dan pembaruan release.
- Verifikasi HTTP deny untuk config/storage/backup/log/session, non-executable upload, dan listing off. Permission filesystem bukan pengganti authorization HTTP.
- Jaga backup/PPDB historis dari perubahan tak sengaja dan hindari deployment recursive yang menghapus state.

Smoke test item 12 telah membuktikan upload/backup/session pada Apache testing yang writable, tetapi bukan pembuktian source read-only dengan identitas Linux hosting berbeda. Item ini hanya audit requirement; tidak mengklaim actual permission hosting lulus. Production config positive gate item 13 tetap terbuka.

## Kesimpulan

Daftar final kebutuhan write selesai. Runtime write dibatasi ke leaf media, journal, temporary ZIP, serta storage log/session/upload-temp sesuai konfigurasi hosting. Semua source/config dan parent source harus read-only bagi runtime pada model ownership yang mendukungnya. Tidak ada perubahan aplikasi, schema, credential, permission aktual, atau deployment.
