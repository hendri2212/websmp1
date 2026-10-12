# Audit Role dan Permission setelah Keputusan PPDB Pasif

Tanggal: 6 Oktober 2026

## Dasar dan Batas Pemeriksaan

Pemetaan ini berasal dari kode repository yang tersedia, bukan asumsi bahwa seluruh cleanup PPDB sudah diterapkan. Pemeriksaan statis menemukan role, menu, permission, endpoint pendaftar, dan handler POST pendaftaran yang masih tersisa. Karena itu, keputusan PPDB pasif belum dapat dikonfirmasi sepenuhnya pada salinan kode ini. Tidak dilakukan perubahan PPDB atau pengujian workflow pendaftaran.

Audit tidak mengakses database sekolah: jumlah akun aktual dengan setiap role dan statusnya belum diketahui. Tidak menghapus role, akun, endpoint, schema, atau data. Perubahan tahap ini hanya laporan.

## Role yang Masih Dikenali

| Role | Label Kode | Permission | Menu Modul | Endpoint Terlindungi |
| --- | --- | ---: | ---: | ---: |
| superadmin | Superadmin | 20 | 20 | 22 |
| editor | Editor Konten | 13 | 13 | 13 |
| ppdb | Operator PPDB | 4 | 4 | 6 |

Role tidak dikenal dan sesi anonim tidak memiliki akses modul administratif. Export/download pendaftar bukan menu sidebar terpisah. Logout merupakan kontrol bersama, bukan permission modul terpisah.

## Permission Matrix

Kolom ROLE berisi semua role yang boleh mengakses baris tersebut. Role yang tidak disebutkan tidak boleh mengakses menu/halaman/aksi itu. Path halaman relatif terhadap admin/. Ya berarti aksi tersedia pada handler dalam permission modul, bukan jaminan semua payload valid atau semua record dapat dihapus. Mutasi tetap memerlukan CSRF dan validasi.

| ROLE | MENU | HALAMAN | CREATE | EDIT | DELETE | UPLOAD |
| --- | --- | --- | --- | --- | --- | --- |
| superadmin, editor, ppdb | Dashboard | dashboard.php | Tidak | Tidak | Tidak | Tidak |
| superadmin | Profil & Pengaturan | settings.php | Tidak | Ya | Tidak | Ya: logo, foto, PDF akreditasi |
| superadmin | Akun Admin | admins.php | Ya | Ya: role/status/password | Ya, dengan proteksi akun | Tidak |
| superadmin | Backup Database | db_backup.php | Ya: artefak SQL/ZIP | Tidak | Tidak | Tidak |
| superadmin | Log Aktivitas | activity_logs.php | Tidak | Tidak | Tidak | Tidak |
| superadmin | Cek Production | production_check.php | Tidak | Tidak | Tidak | Tidak |
| superadmin, editor | Fasilitas | facilities.php | Ya | Ya | Ya | Ya: foto |
| superadmin, editor | Agenda | agenda.php | Ya | Ya | Ya | Tidak |
| superadmin, editor | Dokumen | documents.php | Ya | Ya | Ya | Ya: PDF |
| superadmin, editor | Prestasi | achievements.php | Ya | Ya | Ya | Ya: foto |
| superadmin, editor | Ekstrakurikuler | extracurriculars.php | Ya | Ya | Ya | Ya: foto |
| superadmin, editor | Guru & Staf | staff.php | Ya | Ya | Ya | Ya: foto |
| superadmin, editor | Berita | news.php | Ya | Ya | Ya | Ya: foto |
| superadmin, editor | Pengumuman | announcements.php | Ya | Ya | Ya | Tidak |
| superadmin, editor | Galeri | gallery.php | Ya | Ya | Ya | Ya: foto |
| superadmin, editor | FAQ | faqs.php | Ya | Ya | Ya | Tidak |
| superadmin, editor | Pesan Masuk | contact_messages.php | Tidak | Ya: status | Ya | Tidak |
| superadmin, ppdb | PPDB: informasi | ppdb.php | Ya: informasi | Ya: informasi | Ya: informasi | Tidak |
| superadmin, ppdb | Pendaftar PPDB: legacy | ppdb_applications.php | Tidak | Ya: status/catatan | Ya | Tidak |
| superadmin, ppdb | Tautan export: legacy | ppdb_export.php | Ya: artefak CSV | Tidak | Tidak | Tidak |
| superadmin, ppdb | Tautan berkas: legacy | ppdb_download.php | Tidak | Tidak | Tidak | Tidak: hanya download |
| superadmin, editor, ppdb | Ganti Password | change_password.php | Tidak | Ya: akun sendiri | Tidak | Tidak |

Penjelasan:

- CREATE backup/export berarti pembuatan artefak unduhan, bukan pembuatan akun atau record pendaftar. Matriks ini hanya memetakan aksi panel admin; form publik pendaftaran tidak termasuk CREATE administratif.
- Pengaturan mempunyai aksi simpan/edit profil dan konten; tidak ada workflow tambah/hapus record pengaturan terpisah pada panel.
- Status publikasi konten tercakup EDIT; status pesan/pendaftar dicantumkan khusus.
- Penghapusan akun dibatasi untuk akun pemohon dan superadmin aktif terakhir sesuai validasi repository. Proteksi CRUD ini belum diuji dengan MySQL.
- Editor dapat mengelola seluruh kategori dokumen, termasuk PDF kategori PPDB. Role ppdb tidak mendapat documents dan tidak dapat mengunggah edaran melalui menu tersebut.
- Login dan index adalah endpoint bersama tanpa permission modul. Logout hanya POST dengan CSRF. Akses publik informasi PPDB tidak membutuhkan role admin.

## Authorization Server-Side

Semua 22 endpoint terlindungi dimulai dengan bootstrap dan require_admin_permission sebelum handler. Identitas, akun aktif, dan role terbaru diverifikasi dari database. admin_can menggunakan allowlist permission; request role atau session role palsu tidak menggantikan keputusan database. Anonim/sesi tidak valid diarahkan ke login, role tanpa permission mendapat 403 dan exit.

Menyembunyikan menu tidak mematikan endpoint. Permission ppdb_applications masih memberikan akses langsung ke pendaftar/export/download pada kode yang tersedia; ini dependensi legacy, bukan missing authorization pada kebijakan kode saat ini.

## Apakah Role PPDB Sudah Tidak Diperlukan?

Fungsi operator pendaftaran tidak diperlukan dalam target informasi-only. Namun role ppdb belum dapat dianggap tidak diperlukan sepenuhnya: role itu juga memberi hak kelola informasi ppdb_content dan password sendiri. Menghapus role dari allowlist authentication akan menolak akun ppdb yang masih sah, termasuk pengelola informasi.

Dependensi role/permission yang masih ditemukan:

1. includes/auth.php: role options, label/deskripsi, allowlist login/session, admin_permissions, dan helper kompatibilitas ENUM.
2. includes/repositories.php: allowedRoles pada penyimpanan akun; tabel/helper informasi dan pendaftaran; helper tahun ajaran masih mencakup ppdb_applications.
3. database/schema.sql: admins.role ENUM masih memasukkan ppdb; tidak diubah.
4. admin/admins.php: pilihan role dari helper dan teks tugas operator yang masih menyebut pendaftaran.
5. templates/admin_header.php: menu PPDB, Pendaftar PPDB, dan query badge pendaftar.
6. admin/dashboard.php: query hitungan pendaftar, chart/notifikasi, dan tautan sesuai ppdb_applications.
7. admin/ppdb.php: ppdb_content untuk CRUD informasi, masih sesuai target halaman pasif.
8. admin/ppdb_applications.php, ppdb_export.php, ppdb_download.php: permission legacy ppdb_applications.
9. tests/auth_security_test.php dan tests/role_permission_test.php: fixture/kontrak role ppdb. Tes ini menguji authentication/guard, bukan workflow pendaftaran.
10. Log historis dapat memuat label role ppdb; akun live mungkin memakai role tersebut, tetapi belum diinventarisasi.

## Rekomendasi, Bukan Perubahan

- Pertahankan role ppdb sementara. Bila sekolah membutuhkan pengelola khusus informasi PPDB, role dapat tetap digunakan dengan permission dashboard, ppdb_content, change_password setelah cleanup disetujui; perubahan label/deskripsi dapat dilakukan terpisah tanpa schema baru.
- ppdb_applications adalah kandidat permission legacy yang tidak diperlukan dalam target pasif. Pencabutan harus disertai penanganan endpoint, menu, query badge/dashboard, dan kontrak test; jangan mengubahnya pada audit laporan ini.
- Jika pengelolaan informasi nantinya hanya dilakukan superadmin/editor, inventaris akun ppdb terlebih dahulu dan tentukan perubahan akses secara eksplisit. Jangan otomatis memindahkan semua operator ke editor karena itu memperluas hak ke seluruh konten/pesan; editor juga belum mendapat ppdb_content pada kode saat ini.
- Jangan menghapus nilai ENUM atau helper bersama demi menghapus label operator. Cleanup role/schema/data membutuhkan tahap dan persetujuan tersendiri.
- Konfirmasi salinan kerja yang memuat implementasi PPDB pasif bila perubahan dibuat di branch/lokasi lain. Tidak melakukan switch branch, merge, atau menghapus kode pada tahap ini.

## Verifikasi dan Batas Hasil

Sumber: includes/auth.php, templates/admin_header.php, seluruh guard/aksi admin, includes/repositories.php, admin/admins.php, dan database/schema.sql. Pemetaan CREATE/EDIT/DELETE/UPLOAD berdasarkan panggilan handler aktual, bukan hanya nama menu.

Tes role/permission dijalankan ulang: 445 assertion lulus, meliputi 22 endpoint, 100 probe guard, dan 20 menu. Tes memakai PDO simulasi dan template nyata tanpa koneksi MySQL atau mutasi data sekolah. Pengujian guard tidak membuktikan keberhasilan CRUD/upload berizin; functional testing modul admin umum tetap dilakukan dengan database salinan. Tidak menjalankan form pendaftaran, export berisi data sekolah, atau test pendaftaran mendalam.

Kesimpulan: terdapat tiga role yang masih dikenali. Role ppdb memiliki dependensi aktif dan belum aman dihapus langsung. Hak pengelolaan pendaftar perlu ditinjau sebagai cleanup legacy terpisah; audit ini tidak menyatakan website siap production.
