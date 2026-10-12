# Audit CSRF Aksi Administratif

## Pemeriksaan Ulang Tahap 2 Lanjutan - Admin Non-PPDB

Tanggal: 6 Oktober 2026. Hasil terbaru ini membatasi eksekusi probe pada admin non-PPDB sesuai keputusan pengguna. Angka 21 handler/487 assertion di bagian audit awal adalah riwayat, bukan cakupan pengujian ulang ini.

### Hasil Endpoint

Tidak ditemukan mutasi bisnis administratif tanpa validasi CSRF pada 17 handler yang diuji: achievements, admins, agenda, announcements, change_password, contact_messages, db_backup, documents, extracurriculars, facilities, faqs, gallery, login, logout, news, settings, staff.

| Kategori | Endpoint / Perilaku |
| --- | --- |
| Tambah/edit/hapus konten | achievements, agenda, announcements, documents, extracurriculars, facilities, faqs, gallery, news, staff: operasi berada setelah cabang penolakan token |
| Perubahan status | Checkbox status pada simpan konten serta cabang status contact_messages berada setelah token valid |
| Pengelolaan akun | admins memvalidasi token sebelum save_admin_user/delete_admin_user, termasuk role/status/password target |
| Password sendiri | change_password memvalidasi token sebelum update_admin_password; password lama tetap diverifikasi |
| Upload | Semua panggilan upload foto/PDF pada konten dan settings berada setelah CSRF valid; validasi MIME/ukuran tetap dipertahankan |
| Pengaturan | settings memvalidasi token sebelum upload dan save_site_sections |
| Backup SQL/ZIP | db_backup memvalidasi token sebelum build_database_backup/build_full_backup_zip, file sementara, log export, dan pengiriman hasil. Inventaris tabel sebelumnya hanya dibaca |
| Login/logout | POST dan CSRF valid wajib sebelum authentication/session diubah |

pages/kontak.php diperiksa statis: save_contact_message berada setelah pemeriksaan database, token CSRF, dan field wajib. Handler admin/ppdb.php, admin/ppdb_applications.php, dan POST publik PPDB lama hanya diperiksa statis, tanpa menjalankan probe/workflow pendaftaran atau mengubah kode PPDB. Tidak menganggap fitur lama sudah hilang hanya karena target PPDB pasif.

### Bukti Pengujian Terisolasi

Perintah: php tests/csrf_handler_test.php --admin-only.

- 17 handler POST, 29 form POST dengan csrf_field, 49 variasi payload, dan 498 assertion lulus.
- Token hilang/salah/kosong/array/angka tidak mencapai operasi mutasi pertama.
- Token lama atau milik session lain ditolak terhadap token session aktif.
- Token valid pada query string tidak menggantikan token POST yang hilang.
- GET tidak mencapai operasi bisnis POST; token POST valid mencapai operasi pertama pada cabang yang memenuhi field wajib.
- Token session dibuat dengan random_bytes dan dibandingkan melalui hash_equals; SameSite bukan pengganti pemeriksaan server-side.
- Probe mempertahankan kondisi/percabangan handler asli, tetapi mengganti fungsi mutasi/upload/backup/auth dengan penghenti operasi. Tidak membuat record, upload, backup, atau koneksi MySQL sekolah.
- Pemeriksaan valid token berhenti pada operasi pertama, bukan membuktikan keberhasilan keseluruhan CRUD atau bahwa semua operasi sesudahnya telah dieksekusi.
- 53 regression authentication lulus; seluruh 76 file PHP lolos lint; git diff --check lulus dengan peringatan LF/CRLF saja.

### Perubahan dan Batasan

Hanya tests/csrf_handler_test.php dan laporan ini diubah. Test mendapat mode --admin-only yang mengecualikan seluruh file admin berawalan ppdb dan semua halaman publik; mode lama tetap tersedia tetapi tidak dijalankan dalam audit ulang ini. Tidak ada perubahan kode aplikasi/PPDB/schema, permission, UI, commit, push, deployment, atau destructive database test.

Helper ensure_* lama masih dapat menjalankan CREATE/ALTER/UPDATE pada request baca sebelum validasi CSRF bisnis; penghitung unduhan publik dan log export GET juga memiliki efek samping. Maka tidak diklaim bahwa semua efek samping database di seluruh website sudah tervalidasi CSRF. Hal ini dicatat untuk cleanup/migrasi terpisah, tanpa refactor besar pada tahap ini.

Functional testing HTTP dengan session authenticated, token stale/lintas sesi, dan database salinan masih diperlukan. Request anonim yang ditolak authentication saja bukan bukti CSRF. Kesimpulan ini tidak menyatakan website siap production.

Tanggal: 6 Oktober 2026

## Hasil Utama

Validasi CSRF benar-benar dipanggil dalam cabang handler sebelum aksi create, update, delete, perubahan status, pengelolaan akun, pengelolaan PPDB, upload, dan pembuatan backup. Tidak ditemukan mutasi bisnis administratif yang diperiksa berjalan melalui GET atau tanpa validasi token.

Temuan yang diperbaiki: csrf_token_is_valid sebelumnya bertipe parameter nullable string, sehingga token request berbentuk array/angka dapat menghasilkan TypeError sebelum ditolak. Parameter kini mixed dan helper menolak non-string, token kosong, atau token sesi yang tidak berbentuk string. csrf_token juga memulihkan token sesi yang kosong/rusak dengan random_bytes. Perbandingan tetap menggunakan hash_equals terhadap token session server-side.

## Pemetaan Handler

Semua endpoint admin pada tabel memakai authentication/authorization yang sudah diaudit sebelumnya. Nomor baris menunjukkan lokasi validasi saat audit ini; penambahan kode di masa depan dapat menggesernya.

| Endpoint | Validasi CSRF | Aksi setelah Token Valid |
| --- | --- | --- |
| admin/achievements.php | Baris 13 | Create/update/delete, status publikasi, upload foto |
| admin/admins.php | Baris 13 | Create/update/delete akun, role, status aktif, password |
| admin/agenda.php | Baris 11 | Create/update/delete, tahun ajaran, status |
| admin/announcements.php | Baris 10 | Create/update/delete, periode dan status tampil |
| admin/change_password.php | Baris 13 | Perubahan password sendiri setelah konfirmasi/verifikasi |
| admin/contact_messages.php | Baris 9 | Hapus pesan, ubah status |
| admin/db_backup.php | Baris 165 | Membuat/mengunduh SQL atau paket ZIP |
| admin/documents.php | Baris 12 | Create/update/delete, status, upload PDF |
| admin/extracurriculars.php | Baris 11 | Create/update/delete, status, upload foto |
| admin/facilities.php | Baris 12 | Create/update/delete, status, upload foto |
| admin/faqs.php | Baris 11 | Create/update/delete, status dan urutan |
| admin/gallery.php | Baris 11 | Create/update/delete, upload foto dan urutan |
| admin/login.php | Baris 10 | Percobaan autentikasi dan pembuatan session |
| admin/logout.php | Baris 4, kondisi POST | Pembersihan/rotasi session |
| admin/news.php | Baris 10 | Create/update/delete, status publikasi, upload foto |
| admin/ppdb.php | Baris 10 | Create/update/delete informasi PPDB |
| admin/ppdb_applications.php | Baris 20 | Hapus pendaftar, ubah status dan catatan |
| admin/settings.php | Baris 45 | Simpan pengaturan, upload logo/foto/PDF akreditasi |
| admin/staff.php | Baris 10 | Create/update/delete, status, upload foto |

Tambahan: form publik pages/ppdb.php dan pages/kontak.php juga memvalidasi token sebelum upload/simpan pendaftaran atau pesan. Total cakupan pengujian: 19 handler POST admin dan 2 handler POST publik.

## Urutan dan Perilaku Penolakan

- Handler CRUD memakai cabang if token tidak valid -> pesan error; seluruh cabang delete/update/create/upload berada pada elseif/else setelah pemeriksaan tersebut.
- Handler login/logout memeriksa metode POST dan CSRF sebelum operasi authentication/session.
- Backup mungkin membaca inventaris tabel sebelum pemeriksaan CSRF, tetapi pembentukan SQL/ZIP, pembuatan file backup sementara, serta pengiriman hasil hanya berada pada cabang token valid. Kedua mode backup menggunakan validasi yang sama.
- Perubahan status melalui checkbox is_active ikut dalam handler simpan yang tervalidasi. Perubahan status pesan dan pendaftar memiliki cabang khusus setelah CSRF. Mengirim delete_id tidak melewati validasi.
- Upload helper baru dipanggil setelah validasi, sehingga token tidak valid tidak mencapai penyimpanan berkas. Validasi MIME, ukuran, dan upload PHP tetap dipertahankan.
- Token tidak diterima dari query string sebagai pengganti token POST. Token pembanding berasal dari session, bukan request. SameSite=Lax tetap perlindungan tambahan, bukan pengganti validasi CSRF.
- Login berhasil merotasi token dengan menghapus token lama; logout membersihkan seluruh session. Form memerlukan token dari session yang sedang digunakan.

## Bukti Pengujian

tests/csrf_handler_test.php menggunakan token_get_all untuk mengambil cabang POST dari file handler nyata. Kondisi, urutan validasi, dan percabangan dipertahankan; panggilan mutasi data/upload/backup/auth diganti pencatat uji yang menghentikan eksekusi pada operasi pertama. Database dianggap tersedia agar pemeriksaan tidak lulus hanya karena db_available false. Tidak ada koneksi MySQL, penyimpanan upload, pembuatan backup, atau perubahan data sekolah.

Untuk 60 variasi payload create/update/delete/status/backup:

- Token hilang, salah, kosong, array, dan angka tidak mencapai operasi.
- Token session valid mencapai operasi pertama setelah validasi/field wajib.
- GET dengan token valid tetap tidak mencapai operasi.

Ini pengujian alur penjagaan sebelum operasi pertama, bukan uji keberhasilan seluruh CRUD/backup/upload sebenarnya. Label variasi create/update pada handler seperti login/status dapat melewati cabang sama; jumlah 60 adalah variasi payload, bukan 60 aksi bisnis berbeda.

PHP tokenizer dan DOM juga memeriksa bahwa setiap form method=post dalam sumber template menyertakan pemanggilan csrf_field di dalam form, bukan sekadar helper tersedia di file lain.

- **21 handler POST diperiksa**.
- **35 form POST memiliki field token**.
- **60 variasi payload diuji**.
- **487 assertion CSRF lulus**.
- **50 pemeriksaan authentication regression tetap lulus**.
- **Seluruh 74 file PHP lolos lint**.
- git diff --check lulus dengan peringatan normalisasi LF/CRLF.

## Aksi GET dan Masalah di Luar Mutasi Bisnis

ppdb_export.php memakai GET untuk unduhan CSV dan menulis log aktivitas export. ppdb_download.php memakai GET untuk membaca berkas. Keduanya tetap mewajibkan permission; tidak mengubah status/data pendaftar. Migrasi export ke POST tidak dilakukan dalam audit ini.

GET download dokumen publik menaikkan penghitung unduhan. Helper ensure_* lama masih dapat membuat tabel/menambah kolom/mengisi academic_year default saat fungsi read/halaman dipanggil, termasuk pada request yang belum memvalidasi CSRF. Karena itu, hasil audit tidak berarti semua efek samping database dalam seluruh aplikasi sudah bebas dari request GET. Pemisahan migrasi schema/data dari request web tetap direkomendasikan sebagai pekerjaan terpisah, tanpa mengubah arsitektur besar pada audit ini.

Functional testing dengan browser, akun tiap role, dan database salinan masih diperlukan untuk memastikan token session berbeda/stale ditolak pada seluruh alur HTTP serta operasi yang valid berhasil sampai selesai. Request administratif anonim yang ditolak authentication saja tidak dipakai sebagai bukti CSRF.

## File yang Diubah

- includes/functions.php: penolakan tipe token malformed dan token kosong/rusak.
- tests/csrf_handler_test.php: pemeriksaan cabang handler dan form POST.
- Laporan ini.

Tidak mengubah schema, daftar permission, UI, atau struktur endpoint. Tidak dilakukan push atau deployment.
