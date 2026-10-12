# Audit Role dan Permission

Tanggal: 6 Oktober 2026

## Role yang Tersedia

Ada tiga role yang konsisten pada helper auth, validasi penyimpanan akun, dan ENUM pada schema: superadmin, editor, dan ppdb (label Operator PPDB). Tidak ditemukan role tambahan. Nilai role tidak dikenal tidak mendapatkan izin dan sesi tersebut ditolak. Kondisi anonim bukan role akun.

Sumber pemetaan: includes/auth.php, seluruh admin/*.php, templates/admin_header.php, includes/repositories.php, dan database/schema.sql.

## Matriks Halaman, Aksi, Menu, dan Endpoint

Semua path pada tabel relatif terhadap folder admin/. S = superadmin, E = editor, P = operator PPDB. Ya berarti role boleh mengakses halaman/handler dan aksi yang tercantum. Izin berlaku terhadap semua data dalam modul, bukan hanya data yang dibuat sendiri.

| Endpoint | Permission | Menu Admin | Aksi yang Tersedia | S | E | P |
| --- | --- | --- | --- | --- | --- | --- |
| dashboard.php | dashboard | Dashboard | Melihat ringkasan; panel dan tautan modul mengikuti izin | Ya | Ya | Ya |
| settings.php | settings | Profil & Pengaturan | Melihat/mengubah profil, tema, menu publik, maintenance, tahun ajaran, konten beranda; upload media/akreditasi | Ya | Tidak | Tidak |
| admins.php | admins | Akun Admin | Melihat, membuat, mengubah, menghapus akun; mengatur role/status/password sesuai validasi | Ya | Tidak | Tidak |
| db_backup.php | db_backup | Backup Database | Melihat ringkasan tabel; membuat dan mengunduh backup SQL/ZIP lewat POST | Ya | Tidak | Tidak |
| activity_logs.php | activity_logs | Log Aktivitas | Melihat/mencari/filter log aktivitas admin | Ya | Tidak | Tidak |
| production_check.php | production_check | Cek Production | Memeriksa konfigurasi dan kesiapan production | Ya | Tidak | Tidak |
| facilities.php | facilities | Fasilitas | Melihat, membuat, mengubah, menghapus fasilitas; upload foto, status dan urutan | Ya | Ya | Tidak |
| agenda.php | agenda | Agenda | Melihat, membuat, mengubah, menghapus agenda; tanggal, tahun ajaran, status dan urutan | Ya | Ya | Tidak |
| documents.php | documents | Dokumen | Melihat, membuat, mengubah, menghapus dokumen semua kategori; upload PDF, status dan urutan | Ya | Ya | Tidak |
| achievements.php | achievements | Prestasi | Melihat, membuat, mengubah, menghapus prestasi; upload foto, tahun ajaran dan status | Ya | Ya | Tidak |
| extracurriculars.php | extracurriculars | Ekstrakurikuler | Melihat, membuat, mengubah, menghapus kegiatan; upload foto, pembina, jadwal dan status | Ya | Ya | Tidak |
| staff.php | staff | Guru & Staf | Melihat, membuat, mengubah, menghapus data staf; upload foto dan status | Ya | Ya | Tidak |
| news.php | news | Berita | Melihat, membuat, mengubah, menghapus berita; upload foto dan status publikasi | Ya | Ya | Tidak |
| announcements.php | announcements | Pengumuman | Melihat, membuat, mengubah, menghapus pengumuman; periode tampil dan status | Ya | Ya | Tidak |
| gallery.php | gallery | Galeri | Melihat, membuat, mengubah, menghapus galeri; upload foto dan urutan | Ya | Ya | Tidak |
| ppdb.php | ppdb_content | PPDB | Melihat, membuat, mengubah, menghapus entry informasi PPDB | Ya | Tidak | Ya |
| ppdb_applications.php | ppdb_applications | Pendaftar PPDB | Melihat/mencari/filter pendaftar; mengubah status dan catatan; menghapus pendaftar | Ya | Tidak | Ya |
| ppdb_export.php | ppdb_applications | Tautan export di Pendaftar PPDB | Mengunduh CSV data pendaftar sesuai filter lewat GET | Ya | Tidak | Ya |
| ppdb_download.php | ppdb_applications | Tautan berkas di Pendaftar PPDB | Mengunduh KK, akta, rapor, foto lewat GET id/file yang diizinkan | Ya | Tidak | Ya |
| faqs.php | faqs | FAQ | Melihat, membuat, mengubah, menghapus FAQ; kategori, status dan urutan | Ya | Ya | Tidak |
| contact_messages.php | contact_messages | Pesan Masuk | Melihat pesan; mengubah status baru/diproses/selesai; menghapus pesan | Ya | Ya | Tidak |
| change_password.php | change_password | Ganti Password | Mengubah password akun sendiri dengan verifikasi password saat ini | Ya | Ya | Ya |

Endpoint bersama:

| Endpoint | Perilaku |
| --- | --- |
| login.php | Anonim boleh melihat form dan mengirim credential dengan CSRF; akun aktif yang sudah login diarahkan ke dashboard |
| index.php | Redirect ke dashboard jika sesi terverifikasi, selain itu ke login; tidak menjalankan aksi data |
| logout.php | POST dengan CSRF valid membersihkan session dan merotasi ID; GET tidak melakukan logout |

## Ringkasan per Role

- Superadmin: 20 permission/menu utama dan 22 endpoint terlindungi, termasuk export/download PPDB. Mengelola konten, akun, pengaturan, backup, log, pemeriksaan production, PPDB, pesan dan password sendiri. Guard pengelolaan akun mencegah penghapusan akun pemohon dan penghapusan/penonaktifan/demotion superadmin aktif terakhir.
- Editor: 13 permission/menu dan 13 endpoint terlindungi. Menu: Dashboard, Fasilitas, Agenda, Dokumen, Prestasi, Ekstrakurikuler, Guru & Staf, Berita, Pengumuman, Galeri, FAQ, Pesan Masuk, Ganti Password. Dapat mengelola semua konten/pesan dalam modul tersebut, termasuk dokumen kategori PPDB, tetapi tidak mengelola entry informasi PPDB atau data pendaftar.
- Operator PPDB: 4 permission/menu dan 6 endpoint terlindungi. Menu: Dashboard, PPDB, Pendaftar PPDB, Ganti Password. Dapat mengelola informasi dan semua pendaftar PPDB, termasuk status, catatan, hapus, export, dan download berkas. Tidak mendapat menu/izin Dokumen, Pesan Masuk, konten umum, atau fitur sensitif.
- Semua role akun mendapat tombol Logout bersama. Export/download bukan menu sidebar terpisah. Dashboard menampilkan panel PPDB, pesan, konten, agenda, dan log sesuai permission server-side; badge notifikasi ditampilkan hanya pada menu yang berizin.
- Role tidak dikenal dan sesi anonim: tidak mendapat menu modul dan tidak boleh mengakses 22 endpoint terlindungi.

## Authorization Server-Side

Semua 22 endpoint terlindungi dimulai dengan bootstrap dan require_admin_permission(permission), sebelum membaca parameter request, menjalankan query modul, menerima upload, membuat backup/export, atau mengirim berkas. POST memakai guard yang sama seperti GET; menyembunyikan menu bukan mekanisme perlindungan utama.

Guard memeriksa session, keberadaan akun aktif, dan role terbaru dari database. Izin memakai allowlist admin_permissions. Sesi anonim/tidak valid diarahkan ke login. Akun aktif dengan role yang tidak memiliki permission mendapat HTTP 403 dan handler berhenti dengan exit. Mengetik URL sendiri, mengirim POST langsung, atau mengganti role pada request tidak melewati guard.

Form admins.php menerima role sebagai data akun yang sedang dikelola, tetapi handler itu hanya dapat dicapai dengan permission admins (superadmin) dan token CSRF. Tidak ditemukan penyalinan role request menjadi role sesi pemohon. Password sendiri ditentukan melalui identitas sesi yang sudah diverifikasi.

Setiap handler POST terlindungi mempunyai validasi CSRF sebelum mutasi. Export/download PPDB menggunakan GET, tetap mewajibkan permission ppdb_applications. Download juga membatasi field berkas dan lokasi storage. Repository merupakan fungsi internal, bukan endpoint HTTP; proteksi akses langsung direktori includes/config/storage/templates/database/tests bergantung pada konfigurasi Apache .htaccess.

## Verifikasi

- tests/role_permission_test.php memeriksa urutan statement dengan token PHP: bootstrap dan guard harus mendahului kode handler; permission tiap menu harus cocok dengan endpoint; POST harus memiliki validasi CSRF.
- 100 probe guard: 20 permission dikalikan superadmin/editor/ppdb/anonim/role tidak dikenal. Memanggil require_admin_permission yang sebenarnya, menggunakan PDO simulasi dan template admin sebenarnya. Permission/role, response 200/403/redirect, dan menu yang dirender diperiksa.
- Hasil: 445 assertion role/endpoint/menu lulus; 50 pemeriksaan regression authentication lulus; seluruh 70 file PHP lolos lint. git diff --check lulus dengan peringatan normalisasi LF/CRLF.
- Probe memverifikasi guard, bukan mengeksekusi seluruh CRUD/handler berizin dengan MySQL. Parameter session sengaja diberi role superadmin palsu untuk memastikan role database tetap mengendalikan keputusan.
- HTTP preview: 44 request anonim, GET dan POST ke masing-masing 22 endpoint, semuanya HTTP 302 menuju login. Ini mencakup backup, pengaturan akun, export dan download PPDB.
- Auth regression test tetap memeriksa penonaktifan akun, perubahan role, fail-closed database, dan replay session lama.
- Tidak ada perubahan permission aplikasi, schema, UI, push, atau deployment dalam audit ini. Perubahan hanya pada test dan laporan.

## Temuan dan Batas Audit

Tidak ditemukan endpoint admin terlindungi yang kehilangan guard atau salah memetakan permission. Tidak ditemukan bypass URL langsung dalam pemeriksaan ini.

Hal yang perlu dipertimbangkan untuk tahap berikutnya:

1. Izin bersifat per modul, belum memisahkan baca/tulis/hapus/export atau kepemilikan record. Hak hapus dan export seluruh pendaftar untuk operator PPDB adalah perilaku saat ini; kebijakan sekolah perlu menentukan apakah izin tersebut perlu dipersempit.
2. Editor dapat mengelola semua kategori dokumen, termasuk PPDB. Operator PPDB tidak mendapat Dokumen karena belum ada pembatas kategori server-side. Label helper Dokumen PPDB yang tersisa tidak memberikan permission tambahan.
3. Sejumlah query ringkasan/notifikasi dijalankan sebelum data disaring menurut izin; output dashboard/menu sudah disaring server-side. Tidak ada perubahan query besar dalam audit ini.
4. Helpers repository belum mempunyai guard mandiri. Aman dalam jalur endpoint yang diperiksa, tetapi handler baru wajib memasang guard sebelum memanggil fungsi tersebut.
5. Preview PHP built-in tidak menerapkan .htaccess. Proteksi direktori internal/storage harus diuji pada Apache sebelum publikasi; jangan menganggap preview sebagai server production.
6. Pengujian HTTP editor/PPDB/superadmin dengan login MySQL sebenarnya belum dilakukan. Gunakan database salinan untuk uji seluruh aksi, forged role, ID record, upload, dan pencabutan akses lintas sesi.
