# Laporan Konsolidasi Authentication dan Authorization

Tanggal: 6 Oktober 2026

Tujuan: memastikan fondasi authentication, session, role, permission, dan authorization cukup stabil untuk functional testing Tahap 3. Laporan ini tidak menyatakan website siap production. Angka lint pada laporan audit terdahulu adalah hasil historis; angka akhir saat ini adalah 76 file PHP.

Keputusan PPDB terbaru: pendaftaran resmi ditangani Dinas Pendidikan; website sekolah ditargetkan menjadi informasi-only. Pada repository yang diperiksa, role/permission/menu/endpoint pendaftar dan handler publik lama masih terlihat; implementasi pasif sepenuhnya belum dapat dikonfirmasi pada salinan ini. Tidak ada perubahan tambahan PPDB pada Tahap 2 lanjutan. Workflow pendaftaran tidak dikembangkan, diperbaiki, atau diuji mendalam. Dependensi cleanup dicatat di LAPORAN_DEPENDENSI_PPDB_CLEANUP.md dan LAPORAN_ROLE_PERMISSION_PPDB_PASIF.md.

## A. Authentication Flow

1. Login diproses melalui POST dengan token CSRF session yang valid.
2. Credential wajib berupa string; input berbentuk array atau credential kosong ditolak.
3. Akun dicari menggunakan prepared statement, dengan syarat is_active=1.
4. Role akun harus dikenal. Password diperiksa menggunakan password_verify terhadap hash database, bukan dibandingkan sebagai plaintext. password_hash tetap digunakan pada penyimpanan/perubahan password.
5. Setelah credential valid, session_regenerate_id(true) harus berhasil sebelum identitas authenticated dibentuk. Token CSRF lama dibuang.
6. ID, username, dan role sesi berasal dari database, bukan GET/POST/REQUEST.
7. Pemeriksaan akses membaca ulang akun, status aktif, dan role database. Akun hilang/nonaktif, role tidak dikenal, serta kegagalan database menolak akses dan membersihkan identitas authentication.
8. require_admin_permission memverifikasi authentication dan allowlist permission sebelum handler administratif berjalan. Sesi tidak valid diarahkan ke login; role tanpa izin mendapat 403 dan exit.
9. Logout hanya memproses POST dengan CSRF valid, mengosongkan seluruh session, memanggil session_destroy, dan mengedaluwarsakan cookie dengan scope/atribut yang sama. Logout tidak membuat sesi pengganti; replay ID lama ditolak dalam pengujian terisolasi. Kegagalan logging tidak menghalangi pembersihan.

Tidak ditemukan bypass authentication pada jalur yang diperiksa. Hal ini bukan bukti bahwa seluruh kombinasi HTTP/CRUD dengan MySQL sudah diuji.

## B. Session Security

| Aspek | Status |
| --- | --- |
| HttpOnly | Aktif |
| Secure | Aktif ketika HTTPS terdeteksi; tidak dipaksakan pada HTTP localhost |
| SameSite | Lax |
| Lifetime/path | Cookie sesi browser, path / |
| Strict mode | Aktif; session ID asing ditolak |
| Cookie-only | Aktif; pengiriman session ID melalui URL dinonaktifkan |
| Fixation/regeneration | Login mengganti ID dengan penghapusan ID lama; gagal regenerasi berarti login ditolak |
| Logout | Data sesi dibersihkan, penyimpanan session dihancurkan, cookie dikedaluwarsakan |
| Development | Storage session lokal digunakan hanya untuk APP_ENV=local, request web, dan handler files |
| HTTPS proxy | Header forwarded tidak dipercaya langsung; konfigurasi proxy tepercaya belum diuji |

Cookie dan CSRF sesi anonim bertahan pada HTTP preview. Regenerasi sesi authenticated diuji memakai session PHP nyata dan PDO simulasi. TLS hosting dan login MySQL nyata belum diverifikasi.

## C. Role yang Aktif dalam Kode

- superadmin: 20 permission, 20 menu modul, 22 endpoint terlindungi.
- editor: 13 permission/menu dan 13 endpoint terlindungi.
- ppdb, label Operator PPDB: 4 permission/menu dan 6 endpoint terlindungi, termasuk export/download pendaftar.
- Tidak ditemukan role akun lain. Anonim bukan role akun; role tidak dikenal ditolak.

Aktif berarti masih dikenali oleh kode, bukan jumlah akun live yang telah diverifikasi. Inventaris akun database sekolah tidak dilakukan. Role ppdb juga digunakan untuk informasi ppdb_content sehingga tidak dihapus. Permission ppdb_applications adalah dependensi legacy di luar target pasif, bukan hak yang direkomendasikan untuk target akhir.

## D. Permission Matrix

Path relatif terhadap admin/. Ya mencakup akses halaman, menu/tautan, dan aksi pada handler sesuai kolom aksi. Authorization server-side berlaku sebelum query modul, mutasi, upload, export, backup, atau pengiriman berkas. Permission saat ini bersifat per modul, bukan per pemilik record atau per aksi.

| Endpoint / Menu | Permission | Aksi | Superadmin | Editor | PPDB |
| --- | --- | --- | --- | --- | --- |
| dashboard.php / Dashboard | dashboard | Ringkasan sesuai izin | Ya | Ya | Ya |
| settings.php / Profil & Pengaturan | settings | Pengaturan, konten profil, upload media/akreditasi | Ya | Tidak | Tidak |
| admins.php / Akun Admin | admins | CRUD akun, role, status, password | Ya | Tidak | Tidak |
| db_backup.php / Backup Database | db_backup | Buat/unduh SQL atau ZIP melalui POST | Ya | Tidak | Tidak |
| activity_logs.php / Log Aktivitas | activity_logs | Baca, pencarian/filter log | Ya | Tidak | Tidak |
| production_check.php / Cek Production | production_check | Diagnostik konfigurasi | Ya | Tidak | Tidak |
| facilities.php / Fasilitas | facilities | CRUD, status, urutan, upload foto | Ya | Ya | Tidak |
| agenda.php / Agenda | agenda | CRUD, jadwal, tahun ajaran, status | Ya | Ya | Tidak |
| documents.php / Dokumen | documents | CRUD semua kategori, upload PDF, status | Ya | Ya | Tidak |
| achievements.php / Prestasi | achievements | CRUD, foto, tahun ajaran, status | Ya | Ya | Tidak |
| extracurriculars.php / Ekstrakurikuler | extracurriculars | CRUD, foto, jadwal, pembina, status | Ya | Ya | Tidak |
| staff.php / Guru & Staf | staff | CRUD, foto, status | Ya | Ya | Tidak |
| news.php / Berita | news | CRUD, foto, publikasi | Ya | Ya | Tidak |
| announcements.php / Pengumuman | announcements | CRUD, periode, status | Ya | Ya | Tidak |
| gallery.php / Galeri | gallery | CRUD, foto, urutan | Ya | Ya | Tidak |
| faqs.php / FAQ | faqs | CRUD, kategori, status, urutan | Ya | Ya | Tidak |
| contact_messages.php / Pesan Masuk | contact_messages | Baca, status, hapus | Ya | Ya | Tidak |
| ppdb.php / PPDB | ppdb_content | CRUD informasi PPDB | Ya | Tidak | Ya |
| ppdb_applications.php / Pendaftar PPDB legacy | ppdb_applications | Baca/filter semua pendaftar, status, catatan, hapus | Ya | Tidak | Ya |
| ppdb_export.php / Tautan export legacy | ppdb_applications | Export CSV melalui GET | Ya | Tidak | Ya |
| ppdb_download.php / Tautan berkas legacy | ppdb_applications | Download berkas dari field/root yang diizinkan | Ya | Tidak | Ya |
| change_password.php / Ganti Password | change_password | Password sendiri dengan verifikasi password lama | Ya | Ya | Ya |

Endpoint bersama: login.php menerima credential dan CSRF; index.php hanya mengarahkan ke dashboard/login; logout.php membutuhkan POST dan CSRF. Export/download PPDB berupa tautan, bukan menu sidebar tersendiri. Semua role mendapat tombol logout. Editor boleh mengelola dokumen kategori PPDB tetapi tidak mendapat akses pendaftar; operator PPDB tidak mendapat permission documents.

Matriks dengan kolom ROLE | MENU | HALAMAN | CREATE | EDIT | DELETE | UPLOAD tersedia lengkap di LAPORAN_ROLE_PERMISSION_PPDB_PASIF.md. Upload tersedia pada pengaturan dan modul foto/PDF, bukan agenda/pengumuman/FAQ/pesan/akun. Backup/export CREATE merujuk pada artefak unduhan, bukan record pendaftar.

## E. Temuan Broken Access Control

- Preview PHP built-in dahulu mengabaikan .htaccess sehingga /database/schema.sql dapat diakses. Router preview kini menolak direktori internal, konfigurasi, storage, backup, dotfile, dan script pada aset publik. Proteksi Apache hosting belum dibuktikan.
- Pembandingan prefix direktori download dahulu dapat menerima folder sibling dengan prefix sama. Helper containment canonical realpath dengan batas separator kini dipakai oleh download PPDB, dokumen publik, dan akreditasi.
- Tidak ditemukan missing authorization atau permission salah pada 22 endpoint terlindungi. Guard mendahului kode handler; menyembunyikan menu bukan kontrol utama.
- Tidak ditemukan role request yang menjadi role pemohon. Role target pada pengelolaan akun hanya dapat diubah oleh superadmin dengan CSRF.
- IDOR sederhana lintas role tidak ditemukan pada jalur yang diperiksa. Semua record dalam modul memang berada dalam cakupan role berizin; tidak ada kebijakan ownership record saat ini. Pengujian CRUD HTTP lintas ID menggunakan database salinan tetap diperlukan.

## F. Perbaikan yang Dilakukan

- Menyelesaikan 29 blok conflict pada 10 file, dengan PPDB online, dokumen, edaran, kontak, dan fitur lama dipertahankan sesuai struktur project.
- Menghapus aktivasi/promosi otomatis admin serta fallback superadmin berdasarkan username.
- Memvalidasi ulang identitas, akun aktif, dan role dari database; menerapkan fail closed pada authentication/authorization.
- Memperketat tipe credential, regenerasi login, pembersihan logout, dan cookie/session configuration.
- Pada tahap lanjutan, mengganti logout berbasis rotasi menjadi session_destroy dan cookie expiry; memindahkan koneksi logging ke dalam try/catch, dengan pembersihan logout dalam finally.
- Memperbaiki persistensi session development lokal tanpa memaksakan Secure pada HTTP localhost.
- Memperketat containment download dan melindungi preview dari akses file internal.
- Menolak token CSRF malformed/kosong tanpa TypeError; mempertahankan hash_equals dan random_bytes.
- Menyembunyikan error web, menambahkan respons exception/fatal generik, serta menghapus host/port database dan nilai password awal dari tampilan diagnostik. Logging internal tetap aktif.
- Menambahkan regression test terisolasi dan laporan audit.
- Memperluas tes CSRF dengan mode --admin-only tanpa PPDB, penolakan token query-only dan lintas sesi; memperluas sentinel error untuk path absolut, SQL, dan konfigurasi server. Tidak menambah fitur aplikasi.

Tidak menambah fitur bisnis, redesign UI, mengubah schema atau struktur besar, commit, push, maupun deployment. Destructive database smoke test tidak dijalankan.

## G. Status Akun Admin Otomatis Aktif Kembali

Sudah diperbaiki: helper runtime tidak mengaktifkan atau mempromosikan admin otomatis. Login/verifikasi sesi tidak memanggil helper schema role. Admin yang eksplisit nonaktif tetap ditolak; superadmin aktif tetap dapat login pada pengujian.

Proteksi akun pemohon dan superadmin aktif terakhir pada pengelolaan akun dipertahankan berdasarkan pemeriksaan kode, tetapi CRUD tersebut belum diuji dengan MySQL. Keberadaan superadmin aktif pada database sekolah belum diperiksa. Tidak ada akun nyata diubah atau dipulihkan otomatis.

Pengecualian penting: seed instalasi dalam database/schema.sql masih memiliki ON DUPLICATE KEY UPDATE yang dapat mengaktifkan/mempromosikan akun admin bila diimpor ulang. File ini tidak diubah atau dijalankan; jangan impor ulang schema pada database berjalan sebagai perbaikan authentication.

## H. Status Fail-Open Ketika Database Gagal

Sudah diperbaiki menjadi fail closed. Koneksi tidak tersedia atau kegagalan prepare/execute/fetch tidak memberikan akses berdasarkan sesi lama. Identitas authentication dibersihkan dan login ditolak dengan pesan generik. Superadmin aktif dapat login kembali setelah database pulih dalam pengujian.

Error web setelah handler terpasang tidak menampilkan credential, SQL, konfigurasi, atau stack trace. Error sebelum bootstrap berjalan tetap membutuhkan konfigurasi server yang benar. Permintaan yang sudah lolos authorization sebelum penonaktifan dapat menyelesaikan pekerjaannya; pencabutan berlaku pada pemeriksaan akses berikutnya.

## I. CSRF Coverage

- Validasi akhir menjalankan 17 handler POST admin non-PPDB; 29 form POST menyertakan csrf_field; 49 variasi payload diuji.
- Token hilang/salah/kosong/array/angka, query-only, serta token stale/lintas sesi ditolak sebelum operasi mutasi pertama; GET tidak mencapai operasi bisnis POST.
- 498 assertion lulus menggunakan cabang handler nyata dengan panggilan mutasi diganti probe. Ini bukan pengujian keberhasilan CRUD/upload/backup penuh.
- Endpoint informasi/pendaftaran PPDB lama serta form kontak publik diperiksa statis pada audit ulang, tidak dieksekusi dalam suite --admin-only. Hasil lama 21 handler/35 form/487 assertion pada laporan awal adalah riwayat, bukan cakupan tes terbaru.
- GET export PPDB menulis log, download dokumen publik menambah penghitung, dan helper ensure_* lama dapat mengubah schema/data pada request baca. Maka seluruh efek samping aplikasi tidak diklaim telah bebas CSRF; pemisahan migrasi merupakan tindak lanjut tersendiri.

## J. Hasil PHP Lint dan Test

| Pemeriksaan | Hasil terakhir |
| --- | --- |
| PHP lint rekursif, PHP 8.2.12 | 76 file lulus; 0 error |
| Conflict marker / unmerged index | 0 / 0 |
| Authentication | 53 pemeriksaan lulus |
| Role/permission | 445 assertion; 100 probe guard; 22 endpoint; 20 menu |
| Session configuration | 81 pemeriksaan pada 9 skenario lulus |
| CSRF admin non-PPDB | 498 assertion; 17 handler; 29 form; 49 variasi payload |
| File containment | 11 pemeriksaan lulus |
| Error disclosure | 58 pemeriksaan lulus |
| git diff --check | Lulus; peringatan LF/CRLF saja |
| Perubahan SQL/schema/migration terhadap HEAD | Tidak ada |
| DDL baru pada diff aplikasi | 0 |

Authentication, role/permission, dan CSRF --admin-only dijalankan ulang pada validasi akhir. Session/containment/error adalah hasil tes terakhir pada audit masing-masing setelah perbaikan. Total assertion/pemeriksaan enam suite: 1.146, bukan 1.146 skenario bisnis berbeda. Suite admin_form_smoke_test tidak dijalankan karena menulis/menghapus data dan memuat workflow pendaftaran.

HTTP preview audit ulang memverifikasi 44 GET/POST anonim dengan role/ID palsu ke 22 endpoint terlindungi semuanya diarahkan ke login. Audit session menjalankan 7 pemeriksaan HTTP localhost: HTTP 200, atribut cookie, persistensi cookie/CSRF, dan penggantian ID asing. Pengujian TLS, login/logout akun MySQL sebenarnya, CRUD, upload, backup penuh, dan revocation lintas browser belum dilakukan. Tidak menjalankan alur pendaftaran atau export data sekolah.

Tidak menjalankan destructive database test atau mengubah data/schema sekolah. Status schema di atas merujuk pada file/diff repository, bukan pembandingan schema live sebelum/sesudah yang tidak dilakukan.

## K. File yang Diubah

File tracked yang diubah selama rangkaian audit:

- .gitignore, .htaccess, README.md.
- admin/admins.php, admin/login.php, admin/logout.php, admin/ppdb_download.php, admin/production_check.php.
- includes/auth.php, includes/bootstrap.php, includes/database.php, includes/functions.php.
- pages/accreditation_file.php, pages/document_download.php, pages/kebijakan_privasi.php, pages/ppdb.php, pages/profil.php.
- templates/admin_footer.php, templates/footer.php.
- tests/admin_form_smoke_test.php: penyesuaian pemeriksaan permission saja; test tidak dijalankan.

File kode/test baru:

- includes/error_handling.php, preview_router.php.
- tests/auth_security_test.php, tests/role_permission_test.php, tests/session_configuration_test.php.
- tests/csrf_handler_test.php, tests/file_access_test.php, tests/error_handling_test.php.

Laporan audit baru: LAPORAN_AUDIT_TAHAP_1.md, LAPORAN_AUDIT_AUTHENTICATION.md, LAPORAN_AUDIT_ROLE_PERMISSION.md, LAPORAN_AUDIT_ACCESS_CONTROL.md, LAPORAN_AUDIT_SESSION.md, LAPORAN_AUDIT_CSRF.md, LAPORAN_AUDIT_ERROR_HANDLING.md, dan laporan konsolidasi ini.

Laporan lanjutan: LAPORAN_AUDIT_LOGIN_LANJUTAN.md, LAPORAN_DEPENDENSI_PPDB_CLEANUP.md, LAPORAN_ROLE_PERMISSION_PPDB_PASIF.md. Perubahan PPDB pada daftar di atas berasal dari penyelesaian konflik/audit terdahulu sebelum keputusan pasif; tidak ada perubahan PPDB tambahan pada rangkaian lanjutan. Validasi akhir hanya menjalankan pemeriksaan, bukan mengedit kode.

templates/header.php sudah berstatus modified sebelum audit dan tidak diedit oleh pekerjaan ini. LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md sudah untracked sebelum audit stabilitas. Working tree masih berisi perubahan yang belum di-commit; tidak ada staging/commit/push/deployment dalam rangkaian audit.

## L. Risiko yang Belum Diselesaikan

1. Login/logout, CRUD admin umum, upload, dan backup dengan MySQL nyata belum diuji end-to-end. Proteksi superadmin terakhir perlu functional testing; pengecekan jumlah sebelum update/delete tanpa locking yang terlihat berpotensi race saat perubahan bersamaan. Belum direproduksi.
2. Helper ensure_* lama dapat membuat tabel/menambah kolom atau mengisi data pada request web. Tidak direfactor dan tidak dijalankan terhadap database sekolah dalam tes terisolasi.
3. Seed instalasi dapat mengaktifkan admin bila schema diimpor ulang. Tidak diubah karena batasan schema.
4. Permission masih per modul, bukan per aksi/pemilik; editor boleh mengelola semua kategori dokumen. Role/permission/endpoint pendaftar legacy masih terlihat dan perlu cleanup terpisah, tanpa penghapusan role secara mendadak.
5. Rate limiting login, timeout aplikasi session, dan revocation seluruh sesi setelah perubahan password belum ditambahkan.
6. Risiko pendaftaran PPDB lama (enumeration bukti, konkurensi nomor, tanggal lahir, berkas tertinggal) tetap catatan historis. Tidak direkomendasikan pengembangan/perbaikan atau testing mendalam karena workflow tidak akan digunakan; prioritaskan penonaktifan terkoordinasi pada cleanup terpisah.
7. Cookie expiry logout authenticated melalui HTTP nyata, serta kegagalan backend session_destroy belum diuji. Regresi server-side memakai handler files yang berfungsi.
8. Apache AllowOverride/rewrite/larangan storage, TLS/proxy tepercaya, izin file/log/session, serta konfigurasi display_errors produksi belum dibuktikan. Preview harus dijalankan dengan router.
9. Error startup sebelum bootstrap tidak tercakup handler runtime. Jika output sudah terkirim, header/status dan konten sebelumnya tidak dapat ditarik kembali.
10. Log warning/fatal bawaan PHP mungkin memuat detail internal; akses/retensi/lokasi log harus dibatasi. Query verifikasi akses berulang perlu pengujian performa, tanpa mengorbankan fail closed.
11. Dokumentasi batas gambar 2 MB berbeda dari helper 20 MB; verifikasi dan sinkronisasi pada tahap berikutnya. Target PPDB pasif sudah diputuskan, tetapi kesesuaian implementasi penuh pada salinan kode ini belum terbukti.

Temuan di luar scope dicatat, tidak diselesaikan dengan fitur baru atau refactor besar.

## M. Rekomendasi Masuk ke Tahap 3

Ya, fondasi yang diperiksa cukup stabil untuk melanjutkan functional testing terkontrol di lokal/staging dengan database salinan dan data sintetis. Ini bukan pernyataan siap production.

Prasyarat dan prioritas:

- Jangan menggunakan database sekolah utama, menjalankan import schema/seed ulang, atau destructive smoke test. Helper runtime legacy berarti database salinan wajib digunakan saat pengujian penuh.
- Pastikan tersedia superadmin aktif pada salinan tanpa mengaktifkan akun nonaktif secara otomatis.
- Uji login/logout tiap role, direct URL/POST, forged role/ID, perubahan role/penonaktifan lintas sesi, outage database, dan replay cookie.
- Uji token CSRF hilang/salah/stale/lintas sesi serta valid pada CRUD admin umum, akun, status, upload, dan backup.
- Uji perlindungan superadmin terakhir, termasuk perubahan bersamaan pada database salinan. Tidak menguji/memperbaiki workflow pendaftaran PPDB; hanya verifikasi halaman informasi pasif bila implementasinya tersedia.
- Jangan menyamakan lulus audit auth dengan selesainya cleanup PPDB. Bila source pasif ada di branch/lokasi lain, konfirmasi salinan kerja sebelum perubahan selanjutnya; jangan publikasi fitur lama sebagai informasi-only.
- Verifikasi Apache/proxy/TLS dan sanitasi error HTTP pada staging secara terpisah; jangan deployment atau publikasi dalam tahap ini.
