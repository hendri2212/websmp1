# Pemetaan Dependensi PPDB untuk Cleanup Terpisah

Tanggal: 6 Oktober 2026

## Keputusan dan Batas Pekerjaan

PPDB resmi ditangani Dinas Pendidikan. Target website sekolah hanya halaman informasi, jadwal, persyaratan, pengumuman, edaran, dan tautan sistem resmi jika tersedia. Website tidak menerima atau menyimpan pendaftaran siswa baru pada target akhir.

Pekerjaan ini hanya membaca kode dan mendokumentasikan dependensi. Tidak mengembangkan, memperbaiki, menguji workflow pendaftaran, menonaktifkan endpoint, menghapus berkas/data, mengubah role atau schema, commit, push, maupun deployment. Kode online saat ini masih aktif; keputusan target belum menjadi perubahan perilaku aplikasi.

Rekomendasi functional testing PPDB online pada laporan terdahulu tidak lagi berlaku. Tes mendalam pendaftaran, bukti, verifikasi, dan export pendaftar bukan prioritas Tahap 3. Tes keamanan bersama dan modul informasi tetap relevan, tanpa menjalankan alur pendaftaran.

## Peta Dependensi

| Area | Komponen Saat Ini | Dampak Cleanup |
| --- | --- | --- |
| Database informasi | ppdb_entries menyimpan section/title/content/urutan; site_settings dan config/site_data.php menyediakan konten fallback | Pertahankan untuk informasi; bukan data pendaftaran siswa |
| Database pendaftaran | ppdb_applications menyimpan identitas siswa/wali, alamat, status, catatan, consent, tahun ajaran, dan path dokumen | Kandidat pensiun; jangan DROP sekarang karena masih dipanggil helper/menu/dashboard |
| Authentication | includes/auth.php mengenali role ppdb; pendaftaran publik sendiri tidak memerlukan login admin | Pertahankan keamanan auth; menonaktifkan role saja tidak menghentikan POST publik |
| Role/permission | superadmin dan ppdb memiliki ppdb_content serta ppdb_applications; role ppdb juga memiliki dashboard/change_password | ppdb_content bisa dipertahankan; ppdb_applications bisa dicabut pada cleanup. Jangan hapus role ppdb sebelum inventaris akun dan penentuan tugas pengelola informasi |
| Validasi akun/schema role | includes/repositories.php mengizinkan tiga role; schema.sql dan helper role memakai ENUM termasuk ppdb | Tidak perlu perubahan ENUM untuk membuat halaman pasif; perubahan role parsial bisa menolak akun sah |
| Menu admin | templates/admin_header.php menampilkan PPDB dan Pendaftar PPDB serta badge jumlah pendaftar | Pertahankan menu informasi; lepaskan menu pendaftar DAN query badge, bukan hanya sembunyikan label |
| Dashboard | admin/dashboard.php menjalankan lima hitungan pendaftar, kartu, notifikasi, chart, dan tautan | Lepaskan hitungan/query serta seluruh tampilan pendaftar secara bersamaan |
| Route publik | index.php memetakan /ppdb ke pages/ppdb.php dan /ppdb/bukti ke pages/ppdb_receipt.php | Pertahankan /ppdb; pensiunkan route bukti dengan perilaku eksplisit, bukan hanya menghapus file |
| Form publik | pages/ppdb.php mencampur konten pasif dengan POST, validasi, upload, save_ppdb_application, nomor pendaftaran, dan session receipt | Kandidat pertama dinonaktifkan. Handler POST harus ditolak/dihentikan, tidak cukup menghapus form HTML |
| Bukti/session | pages/ppdb_receipt.php mencari data berdasarkan nomor/email atau session ppdb_receipt | Pensiunkan lookup/bukti dan pelepasan session key khusus; jangan hapus session/auth/CSRF bersama |
| Admin pendaftar | admin/ppdb_applications.php membaca/filter pendaftar, mengubah status/catatan, dan menghapus | Kandidat ditutup server-side, kemudian dihapus setelah semua referensi terlepas |
| Export | admin/ppdb_export.php mengirim CSV data pribadi dan menulis log export | Kandidat ditutup server-side; hilangkan tautan sebelum menghapus endpoint |
| Download privat | admin/ppdb_download.php mengambil empat field berkas dari record dengan allowlist dan containment | Kandidat ditutup server-side; bukan alasan menghapus download dokumen publik atau helper containment bersama |
| Upload pendaftaran | pages/ppdb.php memakai uploaded_document_result untuk KK/akta/rapor/foto ke storage/uploads/ppdb | Lepaskan panggilan khusus PPDB; jangan hapus helper upload, MIME/size validation, atau proteksi storage |
| Upload informasi | Dokumen PDF kategori PPDB di school_documents; admin/documents.php; pages/document_download.php | Pertahankan sebagai edaran/persyaratan publik, terpisah dari berkas pribadi calon siswa |
| Repository informasi | ppdb_entries, admin_ppdb_row, ppdb_sections_data, save_ppdb_entry, delete_ppdb_entry | Pertahankan bersama admin/ppdb.php |
| Repository pendaftaran | Generator nomor, helper consent, simpan/filter/list/count/lookup/status/hapus aplikasi | Kandidat penghapusan hanya setelah semua pemanggil kode, route, dashboard, menu, dan tes disesuaikan |
| Helper tahun ajaran | ensure_academic_year_columns memasukkan ppdb_applications bersama agenda/documents/achievements | Dependensi lintas modul kritis: hapus referensi tabel PPDB dari daftar saat cleanup, jangan hapus helper bersama |
| Backup | admin/db_backup.php mengekspor semua tabel dan ZIP storage/uploads; schema.sql ikut dalam ZIP | Backup dapat tetap membawa data/berkas PPDB lama walaupun form/endpoint ditutup; kebijakan retensi harus mencakup backup |
| Log aktivitas | Log status/hapus/export pendaftar dapat menyimpan nomor pendaftaran/filter | Log lama tidak otomatis hilang ketika fitur ditutup; tentukan retensi tanpa menghapus log seluruh website |
| Konten/navigasi/SEO | config/site_data.php, site_settings, home/settings, sitemap berbasis navigasi, privacy/index meta, FAQ, pengumuman | Pertahankan tautan /ppdb; sesuaikan klaim pendaftaran sekolah/online pada cleanup, termasuk override database bukan hanya seed |
| Seed/test | seed_final_content.php mengisi informasi dan memanggil helper tahun ajaran; smoke test memuat pendaftaran; suite guard/CSRF menyebut PPDB | Sesuaikan kontrak tes kelak tanpa menjalankan pendaftaran. Jangan hapus tes auth/role/security bersama |
| Diagnostik | production_check.php masih memeriksa storage/uploads/ppdb dan menyebut upload PPDB | Lepaskan pemeriksaan folder pendaftaran setelah cleanup; pertahankan diagnostik upload dokumen umum |

## Kandidat Aman Bersyarat untuk Dinonaktifkan

Komponen berikut khusus pendaftaran, bukan kebutuhan halaman informasi. Aman dipensiunkan sebagai satu paket perubahan terkoordinasi, bukan dihapus satu per satu sekarang:

1. Bagian form dan handler POST pendaftaran dalam pages/ppdb.php, termasuk nomor/receipt/session khusus.
2. Route /ppdb/bukti dan pages/ppdb_receipt.php, dengan respons route lama yang ditentukan saat cleanup.
3. admin/ppdb_applications.php, admin/ppdb_export.php, dan admin/ppdb_download.php beserta akses URL langsung, bukan hanya menu.
4. Permission ppdb_applications, menu/badge, hitungan/notifikasi/chart/tautan dashboard pendaftar.
5. Panggilan upload pribadi PPDB, helper pendaftaran eksklusif, dan referensi pendaftaran pada test/dokumentasi/diagnostik.

Helpers pendaftaran eksklusif di includes/repositories.php:

- next_ppdb_registration_number, ensure_ppdb_consent_columns, save_ppdb_application.
- ppdb_application_filters, admin_ppdb_applications, admin_ppdb_application_filtered_count.
- admin_ppdb_application, ppdb_application_by_registration, ppdb_application_count.
- update_ppdb_application_status, delete_ppdb_application.

File includes/repositories.php secara keseluruhan tidak boleh dihapus karena melayani hampir seluruh modul website.

## Yang Harus Dipertahankan

- Menu publik dan route /ppdb; konten informasi, jadwal, persyaratan, pengumuman, kontak, edaran.
- admin/ppdb.php, ppdb_content, ppdb_entries, dan helper informasi.
- Dokumen kategori PPDB, pengumuman, FAQ, kontak, navigation/home settings, dan sitemap yang terkait informasi.
- Authentication, session, CSRF, password hashing, prepared statement, upload validation, file containment, dan larangan akses langsung storage.
- Role ppdb sementara dapat tetap digunakan untuk editor informasi PPDB. Perubahan label/deskripsi/permission dilakukan terpisah; jangan menghapus role/akun secara otomatis atau mengubah ENUM sekarang.
- Endpoint dan helper modul lain, termasuk fungsi backup umum serta helper tahun ajaran setelah referensi khusus aplikasi PPDB dilepaskan.

## Urutan Cleanup yang Direkomendasikan

1. Inventaris metadata akun ppdb, data/berkas pendaftar dan salinan backup di lingkungan terkontrol. Tidak perlu membuka dokumen pribadi untuk pemetaan awal. Tentukan pemilik keputusan retensi; keberadaan data live belum diperiksa dalam audit ini.
2. Ubah /ppdb menjadi informasi saja dan hentikan POST pendaftaran server-side. Pensiunkan route bukti serta endpoint administrasi pendaftar dengan penanganan route lama yang eksplisit. Jangan menyatakan selesai hanya karena form/menu tersembunyi.
3. Lepaskan query header/dashboard, permission aplikasi, daftar tabel pada helper tahun ajaran, serta referensi tes/diagnostik/konten online. Pertahankan seluruh fungsi bersama.
4. Setelah tidak ada pemanggil, hapus kode eksklusif pendaftaran bila disetujui. Tahap ini tidak memerlukan DROP tabel atau perubahan ENUM.
5. Pengarsipan/penghapusan tabel, dokumen, log terkait, dan backup lama adalah pekerjaan data-retention terpisah yang memerlukan persetujuan eksplisit. Jangan menjalankan DROP/TRUNCATE/DELETE massal sebagai bagian pemetaan ini.
6. Validasi halaman informasi, penolakan POST/route lama tanpa INSERT/upload baru, auth/role, dashboard, dokumen umum, dan modul non-PPDB memakai database salinan. Tidak perlu menguji workflow pendaftaran yang sudah dipensiunkan.

## Target Akhir dan Ketidakpastian

Website Sekolah -> Menu PPDB -> Halaman Informasi -> jadwal/persyaratan/pengumuman/edaran -> tautan sistem resmi Dinas Pendidikan jika tersedia.

URL resmi belum diberikan atau diverifikasi; jangan menebak atau membuat tautan pendaftaran baru. Penambahan tautan memakai mekanisme konten yang ada bila sesuai, tanpa portal pendaftaran baru.

Halaman publik pasif tetap boleh memakai database untuk konten informasi; larangan penyimpanan mengacu pada pendaftaran/data siswa, bukan larangan CMS informasi.

Saat ini kode masih dapat menerima pendaftaran jika database tersedia. Menunda pengembangan/testing bukan penonaktifan. Jangan mempublikasikan halaman sebagai informasi-only sebelum cleanup server-side benar-benar dilaksanakan. Laporan ini bukan pernyataan kesiapan production.
