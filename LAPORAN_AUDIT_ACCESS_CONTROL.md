# Audit Access Control Endpoint Administratif

## Pemeriksaan Ulang Server-Side Authorization - Tahap 2 Lanjutan

Tanggal: 6 Oktober 2026. Bagian ini adalah hasil terbaru; bagian audit awal di bawah tetap menjadi riwayat. Tidak ada perubahan kode aplikasi, PPDB, role, schema, atau data pada pemeriksaan ulang ini.

### Cakupan dan Hasil

- Seluruh 25 file PHP admin diperiksa: 22 endpoint terlindungi serta login, index, dan logout yang mempunyai tujuan khusus.
- Tokenizer memastikan bootstrap dan require_admin_permission menjadi statement awal seluruh 22 endpoint sebelum parameter request/query/mutasi/upload/download. Menu sidebar memakai permission yang sesuai, tetapi bukan batas keamanan utama.
- Login memang dapat diakses anonim, memakai credential dan CSRF. Index hanya mengarahkan berdasarkan authentication. Logout hanya memproses POST dengan CSRF untuk membersihkan sesi; tidak memberikan akses modul atau menjalankan mutasi data sekolah.
- require_admin_permission memverifikasi akun/sesi aktif serta role database melalui require_admin/admin_can. Anonim/sesi tidak valid diarahkan ke login; role aktif tanpa permission mendapat 403 dan exit. Database gagal tetap fail closed.
- Endpoint settings, admins, db_backup, activity_logs, production_check hanya untuk superadmin. Perubahan role target pada admins bukan role pemohon, dan dibatasi allowlist role serta guard admins.

### Uji Akses Langsung

- 44 request HTTP aktual pada preview lokal: GET dan POST ke setiap endpoint terlindungi, tanpa cookie authentication, dengan role/admin_role/admin_id palsu serta parameter id/edit/delete_id/application_id/message_id. Seluruhnya HTTP 302 menuju login, tanpa mengikuti redirect.
- Pengujian tersebut hanya menguji penolakan sebelum handler. Tidak menjalankan CRUD, upload, backup, export data, atau workflow pendaftaran. Parameter ID uji tidak dipakai untuk membaca record karena guard lebih dahulu menolak.
- 100 probe guard terisolasi mencakup 20 permission dan superadmin/editor/ppdb/anonim/role tidak dikenal. Menggunakan helper/template nyata serta PDO simulasi; tidak menjalankan handler berizin dengan MySQL.
- 445 assertion role/guard/menu, 53 pemeriksaan authentication, dan 11 pemeriksaan containment file lulus.

### Kategori Temuan

| Kategori | Hasil Pemeriksaan Ulang |
| --- | --- |
| Missing authorization | Tidak ditemukan pada 22 endpoint terlindungi |
| Broken access control | Tidak ditemukan bypass URL anonim atau guard role pada jalur teruji; proteksi file/download sebelumnya tetap ada |
| Privilege escalation | Role request/session palsu tidak mengalahkan role database; editor/operator tidak memperoleh permission admins/settings/backup |
| IDOR sederhana | Parameter ID berada setelah guard modul; password sendiri memakai username sesi terverifikasi, bukan target request; tidak ditemukan jalur lintas role pada pemeriksaan ini |

Prepared statement melindungi query parameter, tetapi bukan pengganti authorization. Keputusan akses tetap dibuat oleh guard modul. Izin modul saat ini mencakup semua record dalam modul, sehingga perubahan ID oleh role yang memang berizin tidak otomatis merupakan IDOR. Tidak ada aturan ownership per record yang dapat diklaim sudah ditegakkan.

### Risiko dan Batas Verifikasi

1. HTTP authenticated untuk tiap role dan CRUD lintas ID dengan database salinan belum dilakukan. Tes guard terisolasi dan request anonim tidak membuktikan seluruh handler berizin bebas masalah.
2. Permission ppdb_applications dan endpoint pendaftar/export/download masih ada pada kode yang tersedia, walaupun target bisnis sudah PPDB pasif. Ini ketidaksesuaian scope hak legacy yang memerlukan cleanup terpisah; tidak diubah pada audit authorization ini.
3. Repository internal tidak mempunyai guard mandiri untuk setiap helper. Pemanggil endpoint saat ini dijaga; endpoint baru harus mengikuti pola yang sama. Proteksi direct access direktori internal bergantung pada router preview atau .htaccess Apache yang benar.
4. Izin masih per modul, belum per aksi/pemilik. Editor dapat mengelola semua kategori dokumen; kebijakan sekolah perlu mengonfirmasi cakupan.
5. Perlindungan superadmin aktif terakhir memakai pengecekan jumlah sebelum update/delete, tanpa transaksi/locking yang terlihat pada fungsi tersebut. Kemungkinan race saat perubahan akun bersamaan perlu diuji dengan database salinan; bukan bypass yang telah direproduksi pada audit ini.
6. Konfigurasi hosting Apache/TLS dan inventaris akun live belum diperiksa. Tidak ada commit, push, deployment, atau destructive database test.

Kesimpulan pemeriksaan ulang: guard server-side konsisten pada endpoint yang tersedia dan seluruh uji penolakan lulus. Tidak memerlukan perubahan kode tambahan pada tahap ini. Lanjutkan functional testing admin terkontrol dengan database salinan; bukan pernyataan siap production.

Tanggal: 6 Oktober 2026

## Temuan dan Perbaikan

### 1. Akses File Internal pada Preview Lokal

Terbukti dengan request HEAD ke /database/schema.sql yang sebelumnya merespons 200. Preview PHP built-in tidak menerapkan .htaccess, sehingga pembatas direktori internal dan file storage milik Apache tidak berlaku. Pengujian tidak membaca dokumen pendaftar, credential, atau data sensitif sekolah.

Perbaikan: menambahkan preview_router.php dan menjalankan ulang proses preview yang dibuat pada sesi ini dengan router tersebut. Router menolak direktori internal, dotfile, backup, file konfigurasi/dokumentasi/database, eksekusi script pada aset publik, dan path keluar dari direktori public. Endpoint admin tetap melewati handler dan guard aplikasi yang sama. Perubahan ini hanya untuk development server; konfigurasi hosting Apache tidak diubah.

Sesudah perbaikan, /database/schema.sql ditolak. URL storage PPDB, includes, config, template, test, .git, .htaccess, dan router juga ditolak. Preview tetap tersedia di http://127.0.0.1:8081/.

### 2. Validasi Prefix Direktori pada Download

admin/ppdb_download.php, pages/document_download.php, dan pages/accreditation_file.php sebelumnya membandingkan prefix realpath tanpa separator akhir. Nama folder seperti ppdb-archive berbagi prefix dengan ppdb dan dapat dianggap berada di root yang diizinkan jika path tersebut tersimpan pada record/config.

Ini kelemahan containment yang terkonfirmasi dengan fixture, bukan bukti bahwa pengguna tanpa izin saat ini dapat mengubah path berkas PPDB atau membaca record terlarang. Guard role pada endpoint admin tetap ada.

Perbaikan: helper file_is_within_directory memeriksa realpath, keberadaan file/direktori, batas separator direktori, dan perbedaan casing Windows. Tiga handler download memakai helper yang sama. ID download PPDB harus integer positif dan parameter file harus string dalam allowlist. File di luar root menghasilkan 404 sebelum readfile. Tidak ada perubahan permission atau schema.

## Cakupan Endpoint dan Guard

- Seluruh 25 file PHP dalam admin: 22 endpoint terlindungi serta login, logout, dan redirect index.
- Semua 22 endpoint memakai bootstrap lalu require_admin_permission sebelum pembacaan parameter, query modul, upload, mutasi, backup/export, atau download. Guard menggabungkan verifikasi session/akun aktif dengan allowlist permission berdasarkan role database.
- Endpoint sensitif settings, admins, db_backup, activity_logs, production_check hanya untuk superadmin. Editor/PPDB yang mengetik URL langsung mendapat 403 dalam probe guard.
- ppdb_applications, ppdb_export, ppdb_download hanya untuk superadmin/operator PPDB; editor tidak mendapatkan akses walaupun mengetahui ID pendaftar atau URL berkas.
- POST pada handler terlindungi memiliki CSRF sebelum mutasi. GET edit/filter/download tetap berada di balik guard. Login memerlukan credential dan CSRF; logout memerlukan POST dan CSRF. Index tidak menjalankan mutasi dan hanya melakukan redirect berdasarkan sesi terverifikasi.
- Pemetaan tiap role, menu, aksi, dan endpoint tetap tersedia di LAPORAN_AUDIT_ROLE_PERMISSION.md.

## Broken Access Control dan Missing Authorization

Tidak ditemukan handler admin terlindungi tanpa guard atau permission yang salah pada jalur yang diperiksa. Menu bukan batas keamanan: keputusan dibuat sebelum handler berjalan. Sesi tidak terverifikasi diarahkan ke login, role tanpa izin mendapat 403 dan exit. Kegagalan verifikasi database tetap fail closed.

Repository merupakan fungsi internal dan tidak memiliki guard mandiri untuk setiap fungsi. Saat ini semua pemanggilan administratif melalui endpoint yang diperiksa sudah diguard. Handler baru harus mengikuti aturan tersebut. Akses langsung internal diblokir .htaccess pada Apache dan router pada preview; konfigurasi Apache tetap perlu dibuktikan di lingkungan hosting.

## Privilege Escalation

Role sesi tidak disalin dari GET/POST/REQUEST. Pemeriksaan izin membaca ulang role akun dari database; mengubah role session lama tidak memberi akses. Tidak ada superadmin implisit untuk username admin dan tidak ada aktivasi/promosi akun otomatis oleh helper role.

Data role pada form admins.php adalah atribut akun target, bukan role pemohon. Hanya superadmin dengan permission admins dan CSRF yang dapat mengubahnya; role target divalidasi terhadap tiga nilai yang tersedia. Proteksi akun pemohon dan superadmin aktif terakhir dipertahankan. Mengubah ID akun pada request editor/operator tidak melewati guard admins.

## IDOR Sederhana

- ID GET edit, POST id/delete_id, message_id, application_id, dan ID download diperiksa dalam konteks permission modul sebelum query. Query record memakai tabel/kolom yang ditentukan kode dan prepared statement; ID tidak menentukan tabel atau permission.
- Role PPDB memang boleh melihat/mengubah/menghapus semua pendaftar. Editor memang boleh mengelola semua record modul konten termasuk dokumen semua kategori. Mengganti ID dalam cakupan role ini bukan pelanggaran ownership pada kebijakan yang tersedia, karena tidak ada batas pemilik/operator pada schema atau aturan saat ini.
- Ganti password memakai username sesi terverifikasi, bukan username target dari request; password lama tetap diverifikasi.
- Download PPDB memetakan empat nilai file ke field yang tetap dan memeriksa root storage sebelum mengirim berkas. Tidak ada parameter request path bebas. Pembatas lokasi diperketat dalam audit ini.
- Download dokumen publik hanya mengambil record is_active=1. Akreditasi memang merupakan informasi publik. Batas direktori pada keduanya diperketat agar tidak menjadi jalur pembacaan file internal lain.
- Bukti PPDB publik menggunakan nomor pendaftaran dan email orang tua sebagai pasangan pencarian. Ketahanan terhadap enumeration/percobaan berulang tetap perlu ditinjau; tidak diganti dengan sistem token baru dalam audit ini.

## Verifikasi

- tests/file_access_test.php: 11 pemeriksaan lulus untuk file sah, subfolder, separator, sibling prefix, traversal, file/root hilang, direktori, dan casing Windows. Fixture sementara dibersihkan; tidak menggunakan file sekolah.
- tests/role_permission_test.php: 22 guard endpoint dan 20 menu cocok; 100 probe role/permission menghasilkan 445 assertion lulus, termasuk rendering menu dan response penolakan.
- tests/auth_security_test.php: seluruh 50 pemeriksaan lulus, termasuk penonaktifan akun, role request/session palsu, database gagal, logout dan replay session ID.
- HTTP preview: 59 pemeriksaan lulus. Sebanyak 44 GET/POST anonim ke semua endpoint terlindungi membawa id/edit/delete_id dan role palsu lalu mendapat redirect ke login; 10 URL internal/script publik ditolak; 5 halaman/aset normal merespons 200.
- Seluruh 72 file PHP lolos lint menggunakan PHP 8.2.12.
- Tidak ada perubahan schema, desain, daftar permission, atau arsitektur utama. Tidak dilakukan push atau deployment.

## Batas Verifikasi dan Rekomendasi

Pengujian guard menggunakan PDO simulasi; HTTP memakai sesi anonim pada preview. Belum dilakukan login MySQL sesungguhnya atau mutasi data sekolah. Functional testing berikutnya harus menggunakan database salinan dan akun nyata untuk setiap role, termasuk ID dari modul lain, hak hapus/export, serta perubahan role lintas sesi.

Hak akses saat ini per modul, bukan per aksi atau pemilik. Bila kebijakan sekolah mengharuskan operator hanya mengelola pendaftar tertentu atau melarang export/hapus, kebutuhan itu harus didefinisikan sebagai tahap permission berikutnya; audit ini tidak mengubah kebijakan tersebut.

Untuk menjalankan preview terlindungi, gunakan router: php -S 127.0.0.1:8081 -t . preview_router.php dengan APP_URL sesuai URL preview. Menjalankan PHP built-in tanpa router akan kembali melewati proteksi .htaccess. Hosting Apache tetap perlu diuji untuk AllowOverride, rewrite, dan larangan akses direktori storage.
