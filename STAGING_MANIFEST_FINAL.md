# Final Staging Manifest

Tanggal pembaruan: 2026-10-12. Rekomendasi saja; tidak git add, commit, push, atau deploy. Bagian Tahap 8A.2 di bawah adalah bukti historis, bukan revalidasi revision terbaru.

## Cakupan dan Gate

Seluruh 190 dirty kandidat: 73 modified + 117 untracked. Keputusan: 188 COMMIT, 2 DO NOT COMMIT, 0 MANUAL REVIEW. COMMIT adalah rekomendasi kandidat file, bukan persetujuan staging/commit tanpa menutup gate atau persetujuan production.

- Path relatif repository. Klasifikasi bersumber dari TAHAP_8A_GIT_FILE_CLASSIFICATION.json; grouping mengikuti STAGING_MANIFEST_TAHAP_8A.md.
- Dua DO NOT COMMIT sudah tracked. Mengecualikan diff tidak menghapus versi HEAD; disposisi tracked seed/test legacy harus diputuskan terpisah sebelum checkpoint.
- Credential installer historis: ROTATION REQUIRED BEFORE PRODUCTION. Tidak rewrite history atau akses database sekolah.
- Group adalah kategori logis, bukan jaminan commit parsial mandiri. Runtime/security harus koheren; maintenance membutuhkan guard; tests membutuhkan runtime/fixture; builder membutuhkan allowlist/denylist.
- File ignored bukan kandidat: environment privat, config lokal, dump, backup, database/datadir testing, log/session/temp, generated release ZIP/payload, IDE/OS artifact, serta laporan per-run lokal. DO NOT COMMIT; jangan force-add atau git add folder secara blanket.
- Tests/fixture/dokumentasi/maintenance/tooling tidak ikut public release meskipun layak repository. Config/data/media privat diprovision terpisah.
- Gate validator container video ditutup untuk negative fixture yang dilaporkan dan targeted cases pada bagian perbaikan di akhir dokumen. Parser bukan decoder seluruh codec; tidak menjamin semua kemungkinan kerusakan bitstream dapat dideteksi. Release ZIP sebelumnya masih memakai validator lama dan perlu rebuild sebelum checkpoint.
- Video sekolah mendapat YouTube error 150 sementara pembanding berhasil. Investigasi A/B dan fallback desktop/mobile selesai pada bagian akhir: pembatasan embed berasal dari sisi YouTube/video, bukan parsing URL aplikasi. Embed video sekolah tetap tidak dijamin; link Tonton di YouTube tersedia tanpa bypass. Candidate sebelum perubahan fallback perlu rebuild.
- Release candidate historis tidak mencakup sembilan diff runtime terbaru. Item 4 membentuk candidate sementara dari source terbaru untuk dependency/secret scan; full smoke terhadap candidate tersebut dan simulated committed tree terbaru masih belum dijalankan.

## Final Secret Scan 14 File

File: config/app.php, config/database.php, config/env.php, config/apache_security.conf, config/php_security.ini, includes/functions.php, includes/repositories.php, database/initialize_admin.php, database/maintenance_legacy_schema.php, database/schema.sql, database/seed_final_content.php, tests/admin_form_smoke_test.php, DEPLOYMENT.md, LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md.

Scanner ulang: satu finding working-tree pada tests/admin_form_smoke_test.php, password literal untuk akun sintetis smoke test. Nilai tidak ditampilkan. Bukan default installer/runtime atau secret operasional terkonfirmasi; file DO NOT COMMIT dan tidak dijalankan.

Review konteks: 0 actual secret operasional dan 0 predictable default credential runtime/installer teridentifikasi pada 14 file. Ini bukan klaim 0 literal test credential atau jaminan scanner sempurna. Index/history dipisahkan dari working tree; rotasi historis tetap gate.

## Seluruh Dirty Kandidat

| FILE | CATEGORY | DECISION | COMMIT GROUP | REASON |
| --- | --- | --- | --- | --- |
| .env.example | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Template development tanpa credential/default account; field wajib kosong agar provisioning eksplisit. README mendokumentasikan resolver dan environment. Exclude release. |
| .env.production.example | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| .gitignore | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| .htaccess | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| .user.ini | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| admin/achievements.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/activity_logs.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/admins.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/agenda.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/announcements.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/change_password.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/contact_messages.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/dashboard.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/db_backup.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/documents.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/extracurriculars.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/facilities.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/faqs.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/gallery.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/login.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/logout.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/news.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/ppdb_applications.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/ppdb_download.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/ppdb_export.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/ppdb.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| admin/production_check.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Literal legacy dihapus; permission dan warning diagnostic lulus smoke item 1. Rotasi nyata tetap gate terpisah. |
| admin/settings.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Dua sumber video Upload/YouTube; upload MP4/MOV 20 MB, hostname/ID ketat, path file existing dinormalisasi, CSRF/permission dan transaksi existing dipertahankan. |
| admin/staff.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| config/apache_security.conf | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Tahap 8A.2 item 1: snippet hardening server-context generik, tanpa secret/path/config host nyata; layak repository. Penerapan provider terpisah, tidak masuk payload web. |
| config/app.php | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Tahap 8A.2 item 1: resolver runtime environment eksplisit, tanpa credential/domain produksi tertanam; default APP_NAME hanya identitas publik, bukan fallback database. |
| config/database.php | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Tahap 8A.2 item 1: resolver DB tanpa fallback credential; seluruh nilai berasal environment, production fail-closed. Kesesuaian host database provider tetap gate deployment, bukan alasan mengecualikan source. |
| config/env.php | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Tahap 8A.2 item 1: loader environment dan validator mode/HTTPS tanpa secret/default development; .env aktual tetap privat/ignored. Policy tidak dilonggarkan untuk hosting. |
| config/php_security.ini | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Tahap 8A.2 item 1: snippet expose_php Off generik, tanpa path/credential atau debug; referensi repository, bukan pengganti php.ini atau payload web. |
| config/site_data.php | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| database/initialize_admin.php | MAINTENANCE/INITIALIZER | COMMIT | 2. Maintenance / Initializer | Tahap 8A.2 item 2: CLI-only offline initializer, password STDIN eksplisit tanpa default, hashing/lock/transaksi dan empty-account guard; HTTP ditolak sebelum koneksi. Repository saja, bukan public release. |
| database/maintenance_legacy_schema.php | MAINTENANCE/INITIALIZER | COMMIT | 2. Maintenance / Initializer | Tahap 8A.2 item 2: CLI-only testing maintenance, strict preflight dan konfirmasi apply; plan terisolasi 0 operasi, tidak menjalankan apply. DDL/backfill berisiko partial commit; tidak untuk production/deployment. |
| database/schema.sql | MAINTENANCE/INITIALIZER | COMMIT | 2. Maintenance / Initializer | Tahap 8A.2 item 2: installer struktur tanpa INSERT/default account/hash/data nyata; legacy PPDB definition dipertahankan. CREATE/USE nama DB development memerlukan provisioning offline terpisah, bukan migration atau web release. |
| database/seed_final_content.php | MAINTENANCE/INITIALIZER | DO NOT COMMIT | 2. Maintenance / Initializer | Tahap 8A.2 item 2: seed konten sekolah, bukan fixture; tanpa isolasi/konfirmasi target, menimpa settings dan memalsukan identitas audit via session. Tidak diperlukan runtime/release. Pertahankan file, jangan jalankan/stage; sudah tracked sehingga disposisi versi HEAD perlu keputusan terpisah. |
| DEPLOYMENT.md | DOCUMENTATION | COMMIT | 5. Documentation | UPDATE THEN COMMIT selesai: panduan persiapan diselaraskan dengan config fail-closed, schema tanpa default akun, release allowlist, storage/backup privat; path absolut dihapus. Dokumentasi offline, bukan izin deployment. |
| HOSTING_REQUIREMENTS_CHECKLIST.md | DOCUMENTATION | COMMIT | 5. Documentation | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| includes/auth.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Helper runtime wajib bersama caller; sudah tercakup regression, bukan credential lokal. |
| includes/bootstrap.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Helper runtime wajib bersama caller; sudah tercakup regression, bukan credential lokal. |
| includes/database.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Helper runtime wajib bersama caller; sudah tercakup regression, bukan credential lokal. |
| includes/error_handling.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Helper runtime wajib bersama caller; sudah tercakup regression, bukan credential lokal. |
| includes/functions.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Parser YouTube exact-host/ID 11 karakter, embed hasil aplikasi, canonical upload path dan helper MP4/MOV dengan nama acak; field tidak menyimpan iframe HTML. Validator container masih memiliki gate terpisah. |
| includes/input_validation.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Validasi enum sumber video upload/youtube; validasi input existing tetap digunakan. Tidak mengubah authorization atau schema. |
| includes/media_lifecycle.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Managed path MP4/MOV ditambahkan; video tidak menghasilkan path thumbnail; mekanisme transaksi, journal dan verifikasi referensi existing dipertahankan. |
| includes/repositories.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Tahap 8A.2 item 1: repository/caller, transaksi/locking/lifecycle dan schema-read-only ditinjau; parameter password di-hash, bukan literal credential. Fallback konten bukan fallback autentikasi/DB; runtime release tetap identik. |
| includes/schema_requirements.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Helper runtime wajib bersama caller; sudah tercakup regression, bukan credential lokal. |
| includes/upload_security.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Validasi bounded MP4/MOV box/track/sample, offset media dan framing AVC/HEVC; menolak container palsu yang dilaporkan. Tidak menambah executable runtime atau mengubah limit/lifecycle. |
| index.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| LAPORAN_AUDIT_CSRF.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_AUDIT_ERROR_HANDLING.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_AUDIT_LOGIN_LANJUTAN.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_AUDIT_ROLE_PERMISSION.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_AUDIT_TAHAP_2.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_DEPENDENSI_PPDB_CLEANUP.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_ROLE_PERMISSION_PPDB_PASIF.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md | AUDIT REPORT | COMMIT | 5. Documentation | UPDATE THEN COMMIT selesai: laporan ringkas PPDB pasif, role informatif, status testing dan gate deployment; tanpa nama DB environment, credential atau data pribadi. Exclude release. |
| LAPORAN_TAHAP_6_AUDIT_GITIGNORE.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_HTTP_METHOD_ROUTING.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_HTTPS.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_PHP_ERROR_CONFIGURATION.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_PRODUCTION_CONFIGURATION.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_PRODUCTION_FILE_PERMISSIONS.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_PUBLIC_INFORMATION_EXPOSURE.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_SECURITY_HEADERS.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_7_PRODUCTION_CONFIG_FAIL_CLOSED.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_7_STORAGE_WRITABILITY.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_8A_2_DATABASE_MAINTENANCE_CLOSURE.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan terkurasi database/maintenance, tanpa secret/data individu/path profil pengguna; menjelaskan CLI/HTTP guard, risiko seed dan batas tracked. Bukan izin menjalankan maintenance production. |
| LAPORAN_TAHAP_8A_2_DOCUMENTATION_CLOSURE.md | AUDIT REPORT | COMMIT | 5. Documentation | Laporan review dokumen terkurasi, tanpa credential/data pribadi/path absolut; bukan hasil deployment. |
| LAPORAN_TAHAP_8A_2_LEGACY_TEST_CLOSURE.md | AUDIT REPORT | COMMIT | 5. Documentation | Laporan review terkurasi tanpa credential/data pribadi; keputusan legacy test dan coverage pengganti, bukan hasil runtime baru. |
| LAPORAN_TAHAP_8A_2_RUNTIME_CONFIG_CLOSURE.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan terkurasi tujuh file runtime/config, tanpa credential/path pribadi/evidence privat; review repository saja, bukan approval production. |
| LAPORAN_TAHAP_8A_FINAL_GITIGNORE.md | AUDIT REPORT | COMMIT | 5. Documentation | Dokumentasi policy ignore final yang tersunting, tanpa secret atau path profil pengguna. |
| LAPORAN_TAHAP_8A_GIT_HISTORY_CREDENTIAL.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_8A_LOCAL_PATH_EXPOSURE.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan local path tersunting, tanpa nilai path personal; dokumentasi repository, bukan release. |
| LAPORAN_TAHAP_8A_TEST_REPORT_FILE_DECISION.md | AUDIT REPORT | COMMIT | 5. Documentation | Keputusan file yang terkurasi; hanya path relatif source, alasan dan hitungan, tanpa identitas/config/run-path privat. |
| pages/404.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/accreditation_file.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/accreditation_view.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/berita_detail.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/berita.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/cari.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/document_download.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/dokumen.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/ekstrakurikuler.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/faq.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/fasilitas.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/galeri.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/guru_staf.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/home.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Hero prestasi dari repository existing, maksimum tiga, fallback dan escaping; card identitas redundan dihapus sesuai revisi. Player file lokal dipisahkan dari iframe YouTube responsif dengan API autoplay. |
| pages/kebijakan_privasi.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/kesiswaan.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/kontak.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/ppdb_receipt.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/ppdb.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/prestasi_detail.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/prestasi.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/profil.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| pages/sitemap.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| preview_router.php | RELEASE TOOLING | COMMIT | 4. Release Tooling | Router development CLI/testing; tidak termasuk bundle. |
| public/assets/css/style.css | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Styling terarah hero prestasi dan kontrol, responsive 16:9 video, error message serta pilihan sumber; bukan redesign modul lain. |
| public/uploads/.htaccess | SECURITY/CONFIG | COMMIT | 1. Runtime & Security | Kontrol keamanan upload source, bukan data upload. |
| README.md | DOCUMENTATION | COMMIT | 5. Documentation | Dokumentasi repository; path profil pengguna pada petunjuk import telah diganti dengan database/schema.sql relatif. |
| RELEASE_ALLOWLIST.json | RELEASE TOOLING | COMMIT | 4. Release Tooling | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| RELEASE_ALLOWLIST.md | DOCUMENTATION | COMMIT | 5. Documentation | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| RELEASE_DENYLIST.json | RELEASE TOOLING | COMMIT | 4. Release Tooling | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| RELEASE_DENYLIST.md | DOCUMENTATION | COMMIT | 5. Documentation | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| REPOSITORY_CLEANUP_PLAN.md | DOCUMENTATION | COMMIT | 5. Documentation | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| STAGING_MANIFEST_FINAL.md | REVIEW MANIFEST | COMMIT | 5. Documentation | Manifest final eksplisit seluruh dirty kandidat dengan category/decision/group/reason; tanpa nilai secret, tidak ikut release. |
| STAGING_MANIFEST_TAHAP_8A.md | REVIEW MANIFEST | COMMIT | 5. Documentation | Manifest path eksplisit, kelompok dan dependency gate; tanpa secret/path profil pengguna. Bukan izin staging atau deployment. |
| TAHAP_8A_GIT_FILE_CLASSIFICATION.json | REVIEW MANIFEST | COMMIT | 5. Documentation | Source/template/kebijakan/dokumentasi repository; exclude dari web release bila tidak ada di allowlist. |
| templates/admin_footer.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Toggle field Upload/YouTube dan disabled input inactive; status disabled server/database tetap dihormati. Validasi server tetap wajib. |
| templates/admin_header.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| templates/footer.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Carousel prestasi 5 detik dengan pause/focus/reduced motion; autoplay video muted ketika visible memakai API YouTube resmi. Tidak menavigasi ke YouTube atau mengubah auth/PPDB. |
| templates/header.php | APPLICATION SOURCE | COMMIT | 1. Runtime & Security | Source runtime existing/hardening; pertahankan dependency dan regression. |
| tests/admin_form_smoke_test.php | TEST | DO NOT COMMIT | 3. Regression Tests | Review legacy ditutup: bootstrap tanpa isolasi DB, cleanup prefix lintas run, session sintetis, dan workflow pendaftaran PPDB obsolete. Superseded suite HTTP terisolasi. Jangan jalankan; file tracked dipertahankan, disposisi penghapusan HEAD terpisah. |
| tests/auth_security_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/csrf_handler_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/delete_feedback_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/error_handling_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/file_access_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_auth_tables.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_consistency_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_content_cases.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_content_tables.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_crud_role_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/fixtures/stage3_download_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_error_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_error_http_probe.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_input_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_upload_apache_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/fixtures/stage3_upload_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_announcement_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_delete_feedback_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_id_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_input_validation_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_media_lifecycle_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_media_lifecycle_probe.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_ppdb_direct_entry_probe.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_ppdb_information_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_ppdb_legacy_security_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_ppdb_private_file_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_xss_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_backup_completeness_checks.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_backup_completeness_probe.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_backup_probe.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_failure_database.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_failure_router.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage6_error_http_router.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/gitignore_policy_test.ps1 | TEST | COMMIT | 3. Regression Tests | Regression metadata-only git check-ignore; dummy paths, no database/file mutation. |
| tests/https_readiness_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/input_validation_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/legacy_ppdb_database_access_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/maintenance_entrypoint_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/prepare_stage3_database.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/role_permission_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/runtime_schema_stability_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/session_configuration_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage3_auth_http_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage3_cleanup_test_data.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage3_content_http_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage3_database_preflight.php | MAINTENANCE/INITIALIZER | COMMIT | 2. Maintenance / Initializer | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage4_legacy_file_reference_fixture.php | FIXTURE | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage4_non_ppdb_regression.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage4_ppdb_dead_code_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage4_ppdb_public_browser_test.cjs | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_account_transaction_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_backup_restore_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/stage5_cleanup_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_connection_timeout_model_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_database_failure_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_file_backup_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage6_apache_security_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/stage6_error_configuration_http_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage6_security_headers_browser_test.cjs | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_build_release.ps1 | RELEASE TOOLING | COMMIT | 4. Release Tooling | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/stage7_bundle_dependency_check.php | RELEASE TOOLING | COMMIT | 4. Release Tooling | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_bundle_security_scan.ps1 | RELEASE TOOLING | COMMIT | 4. Release Tooling | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_configuration_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_initial_admin_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_release_smoke_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/stage7_secret_audit.ps1 | RELEASE TOOLING | COMMIT | 4. Release Tooling | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage8_history_credential_audit.php | RELEASE TOOLING | COMMIT | 4. Release Tooling | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/strict_id_validation_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/upload_security_test.php | TEST | COMMIT | 3. Regression Tests | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/profile_video_source_test.php | TEST | COMMIT | 3. Regression Tests | Test baru 51 checks pure tanpa database: hostname/ID YouTube, URL palsu/injection, enum sumber dan canonical referensi upload. Nilai sintetis, exclude release. |
| tests/profile_video_container_test.php | TEST | COMMIT | 3. Regression Tests | Regression 26 checks menggunakan fixture video sintetis lokal: MP4/MOV/fragmented valid dan corrupt container/sample. Fixture tidak di-commit, FFmpeg hanya alat testing opsional, bukan dependency runtime. |
| tests/xss_output_security_test.php | TEST | COMMIT | 3. Regression Tests | 79 checks XSS; contoh ID lama tujuh karakter diganti ID sintetis 11 karakter dan unknown host/query array wajib ditolak, sesuai kontrak YouTube-only baru. Bukan pelemahan assertion. Exclude release. |

## Dependency Clean Tracked

Sembilan file runtime clean tracked tidak memerlukan staging ulang, tetapi wajib dipertahankan pada revision final; daftar diverifikasi terpisah dari dirty kandidat.

- admin/index.php
- pages/agenda.php
- pages/akademik.php
- pages/maintenance.php
- pages/robots.php
- public/favicon.png
- public/kepala-sekolah.jpeg
- public/logo-sekolah.png
- storage/.htaccess

Seluruh 78 required runtime (69 dirty COMMIT + 9 clean tracked) hash identik candidate pada item 5; missing dependency 0, release lint 71 PHP tanpa error. Item 8-9 adalah scan/manifest, bukan smoke runtime baru. Config template aman tersedia; environment/database/media/storage dan hosting tetap external requirements. Scan staged content dan pengujian revision Git final tetap wajib pada checkpoint terpisah.

## Item 10 - Simulation Gate

HEAD archive + 186 approved overlays benar-benar dibangun menjadi tree 206 file; tracked DO NOT COMMIT tetap HEAD. Builder/checker/scanner dan harness berasal dari tree simulasi. Repository murni tidak memuat private testing state; runner terpisah hanya memprovision database testing terverifikasi. Source lint 140 PHP, release lint 71 PHP: 0 error; missing runtime dependency 0; smoke release 739/739 lulus setelah runner path dipendekkan karena batas Windows/libzip. Run awal FAIL tetap disimpan, assertion/source tidak diubah.

Secret flags ditinjau sebagai template/fixture/detector/label, bukan disembunyikan; release scanner raw REVIEW_REQUIRED, blocking content 0, actual secret operasional teridentifikasi 0. Enam perbedaan payload hash terhadap candidate lama hanya LF/CRLF pada clean tracked files; isi ternormalisasi sama dan smoke lulus. Bukti/metode/risiko terdapat pada laporan documentation closure item 10. Dokumentasi hasil ini ditambahkan setelah snapshot; runtime tidak berubah. Tracked legacy disposition dan rotasi historis tetap gate checkpoint.

## Item 11 - Regression Gate

Regression relevan dari simulated-tree runner lulus berdasarkan gabungan suite/retest: auth/login/logout/session/role/CSRF, CRUD, upload/download, input/XSS/ID, DB-file consistency, pengumuman/error, PPDB passive/browser, production fail-closed, schema read-only dan transaksi akun/concurrency/database failure. Detail jumlah checks, log gagal dan retest tercantum pada laporan documentation closure item 11.

Orkestrator awal tetap recorded FAIL karena private toolbox browser belum tersedia; retest berikutnya menghadapi sandbox Chrome EPERM; retest dengan provisioning/izin yang benar lulus 2071 PPDB checks tanpa mengubah assertion. Account test akhirnya benar-benar dijalankan --run dan 72 checks lulus; exit 0 CLI-only sebelumnya tidak dihitung. Cleanup akhir 0 candidates, 16 tabel testing kosong, seluruh 206 source hashes unchanged. Tidak ditemukan regression runtime akibat dua diff DO NOT COMMIT dalam cakupan test; risiko CLI legacy HEAD tetap gate terpisah. README diperjelas mengenai dependency testing, bukan mengubah source yang diuji.

## Final Check Revision - 2026-10-12

### Review Terbaru

Dibanding payload release tervalidasi Tahap 8A.2, sembilan file runtime berubah: admin/settings.php, includes/functions.php, includes/input_validation.php, includes/media_lifecycle.php, includes/upload_security.php, pages/home.php, public/assets/css/style.css, templates/admin_footer.php, templates/footer.php. Semuanya sudah mempunyai baris kandidat COMMIT, dengan alasan diperbarui. tests/profile_video_source_test.php ditambahkan; tests/xss_output_security_test.php mempunyai kontrak YouTube-only baru yang terdokumentasi.

- Hero: data repository existing, maksimum tiga, satu slide terlihat, interval 5 detik, fallback, escaping, responsive dan kontrol motion/focus. Judul Spensa Update dipertahankan sesuai working tree pengguna. Card Website Resmi redundan dihapus sesuai revisi.
- Video: dua sumber eksplisit, MP4/MOV 20 MB, allowlist hostname dan ID 11 karakter, iframe dibentuk aplikasi, bukan input HTML. Field inactive tidak ikut POST. Autoplay muted dimulai ketika area visible; tidak melakukan redirect.
- Lifecycle: canonical path pada form upload menjaga referensi file; transaksi/rollback/journal existing tetap dipakai. Test runtime sebelumnya mencakup empat arah switching dan real SQL rollback, dengan file dummy dibersihkan serta settings testing dipulihkan. Tidak diuji ulang pada final check metadata ini.
- Tidak ada schema baru. profileVideoSource adalah properti JSON siteHome pada site_settings existing. Tidak ada perubahan runtime auth/role/session, PPDB, backup atau limit dalam revisi video tersebut.

### Validasi Baru

- PHP lint seluruh kandidat source tracked/untracked non-ignored: 141 PHP, 0 error.
- profile_video_source_test: 51 lulus; xss_output_security_test: 79 lulus; input_validation_test: 784 lulus; upload_security_test existing: 38 lulus.
- git diff --check exit 0; conflict marker scan non-ignored exit 1 tanpa match: 0 marker. Staged 0, unmerged 0.
- Manifest 189 baris dan klasifikasi 189 file cocok tepat dengan dirty set; tidak ada kandidat hilang atau tambahan. 187 COMMIT, 2 DO NOT COMMIT. Kelompok: Runtime/Security 75; Maintenance/Initializer 4; Regression Tests 65; Release Tooling 8; Documentation 35.
- login privat, harness/screenshot/probe di storage/testing, FFmpeg portable dan video upload tetap ignored. Jangan force-add folder atau file tersebut.

### Gate yang Belum Ditutup

1. uploaded_video_has_basic_structure masih hanya structural gate; fixture inert tanpa stream lolos finfo dan gate aplikasi. Test upload existing tidak membuktikan masalah ini sudah selesai. Perbaikan validator dan test permanen diperlukan sebelum final checkpoint dinyatakan lulus.
2. Video sekolah tetap mendapat YouTube error 150; video pembanding berhasil autoplay. Jangan menyatakan video sekolah sudah berhasil embed. Tidak mengubah pengaturan channel atau menghapus audio/data pengguna.
3. Checkbox Kartu Logo pada admin masih ada meski card hero sudah dihapus; perlu keputusan cleanup UI kecil, bukan redesign.
4. Release lama bukan representasi source terbaru. Candidate sementara terbaru dibangun/di-lint/diperiksa dependency dan secret pada item 4 di bawah, tetapi full smoke release serta scan staged content belum dilakukan; hasil historis 739 checks tidak otomatis berlaku pada revision ini.
5. Dua tracked legacy DO NOT COMMIT masih berada di HEAD; disposisinya belum diputuskan. Credential historis perlu rotasi sebelum production, tanpa rewrite history pada tahap ini.

Status: manifest lengkap sebagai rekomendasi kandidat, tetapi FINAL CHECK BELUM LULUS TANPA CATATAN. Tidak ada git add, commit, push, deploy, perubahan source aplikasi atau akses database production pada final check ini.

## Item 4 - Source Validation, Secret dan Dependency Check

Pemeriksaan ulang 2026-10-12; bukti per-run berada di storage/testing/final-check-20261012/ (ignored, bukan kandidat commit atau web release).

- Lint source non-ignored: 141 PHP, 0 error. Targeted pure tests: video source 51, XSS 79, input validation 784 dan upload existing 38 lulus.
- Test browser source: hero/fallback/layout 1440/768/390/320, kontrol DOM dua/tiga slide, reduced motion dan form video 1440/768/390 lulus. Tidak membuat record prestasi; real-record carousel masih gap.
- Real runtime video pada database testing terverifikasi: upload/pemutaran MP4/MOV muted, empat arah switching, penolakan domain palsu/POST injection, cleanup replacement dan rollback setelah real SQL error lulus. Settings testing dipulihkan, file dummy tersisa 0. Ini pengujian source, bukan full smoke candidate.
- Negative video container probe GAGAL: finfo video/mp4 dan structural gate menerima container inert tanpa track video valid. Exit probe 1; artifact dihapus. Jangan menyatakan keseluruhan targeted/security suite lulus.
- Secret scanner membaca 207 file working tree, 91 index entries dan 4 reachable commits. Raw flags: 33 working-tree, 12 index, 12 history (57 total), bukan 57 secret. Review konteks mengidentifikasi template kosong, permission label, fixture/payload sintetis, concatenation/detector/self-test markers; tidak teridentifikasi actual secret operasional baru pada kandidat working tree. Literal test legacy tetap DO NOT COMMIT. Credential installer historis tetap ROTATION REQUIRED BEFORE PRODUCTION; tidak rewrite history.
- Candidate sementara source terbaru: school-release-candidate-20261012-214042-3addf0d1, 78 runtime files, 71 PHP lint 0 error, ZIP SHA256 acf658ebea47448b9cdf07ae2ae00ccaa34dd1301a83fdff341e5dd1eb605657. Disimpan terpisah di temporary directory lokal; lokasi lengkap hanya pada bukti ignored. Bukan deployment atau approval production.
- Dependency checker: 91 literal include, 2 dynamic include sites/22 views, 45 asset references; missing/unresolved runtime dependency 0. External YouTube/API/network, database/media/config/extensions tidak disertifikasi oleh pemeriksaan source ZIP.
- Candidate secret scan: integrity/allowlist/denylist PASS, blocking content 0. Raw REVIEW_REQUIRED dengan satu flag permission label auth dan enam localhost references pada validator/local diagnostics, plus satu optional schema vocabulary reference. Nilai secret tidak ditampilkan. Full release smoke dan staged-content scan tetap pending.
- Git: 189 dirty; staged 0, unmerged 0; diff --check exit 0; marker scan non-ignored exit 1 tanpa match (0 markers). Tidak ada staging, commit, push, deploy atau perubahan source aplikasi pada item ini.

Kesimpulan item 4: pemeriksaan diminta sudah dijalankan, tetapi negative upload validation masih FAIL dan error YouTube 150 tetap unresolved. Gate final checkpoint belum ditutup.

## Item 5-6 - Final Candidate dan Short Release Smoke

- Candidate baru: school-release-candidate-20261012-214920-52d01145; 78 runtime files, 71 PHP lint, 0 error. ZIP SHA256 acf658ebea47448b9cdf07ae2ae00ccaa34dd1301a83fdff341e5dd1eb605657, identik dengan candidate item 4 karena source runtime tidak berubah.
- Lokasi lokal terpisah dari source: temporary root school-final-release-20261012. Bukan deployment; tidak memuat environment credential atau data persisten.
- Dependency check ZIP PASS: 0 missing/unresolved dependency. Security scan integrity/allowlist/denylist PASS, blocking content 0; raw context flags sama dengan item 4, bukan klaim scanner tanpa flag.
- Short smoke dijalankan pada file yang diekstrak dan diverifikasi dari ZIP tersebut, menggunakan Apache loopback terisolasi dan database testing 127.0.0.1:33317 yang diverifikasi preflight. Tidak menggunakan database production.
- Hasil final 374/374 checks PASS: integritas file/archive, public homepage/profil/berita/galeri/dokumen/agenda/PPDB, penolakan dashboard anonim, login akun testing existing, dashboard/settings, logout/invalidation, sensitive file protection, legacy registration GET/POST tombstones, schema tetap dan jumlah record konten tetap.
- Ini short smoke, bukan full regression 739 checks. Tidak menjalankan CRUD/upload/backup atau memastikan playback YouTube eksternal. Konten/media existing tidak disalin ke bundle; data-driven resource tidak disertifikasi oleh scope ini.
- Harness lokal pertama salah memasukkan admin/ppdb.php sebagai tombstone; static audit membuktikan file tersebut mengelola informasi PPDB existing. Daftar diperbaiki ke ppdb_applications/export/download/receipt. Pemeriksaan schema berikutnya dinormalisasi hanya untuk counter AUTO_INCREMENT, karena event login/logout normal menambah admin_activity_logs, bukan DDL. Tidak mengubah aplikasi untuk membuat test lulus.
- Apache smoke dihentikan; .env testing, cookie jar dan session smoke dihapus. Login/logout audit events normal dipertahankan di database testing, bukan dihapus. Bukti dan harness berada di storage/testing/final-check-20261012/ yang ignored.
- Kedua diff legacy tetap DO NOT COMMIT: database/seed_final_content.php dan tests/admin_form_smoke_test.php. Tidak ada staging, commit, push, deployment atau perubahan runtime source.

Kesimpulan item 5-6: build dan short smoke LULUS dalam scope tersebut. Final checkpoint belum mendapat kelulusan keamanan penuh karena negative validator video dan YouTube error 150 pada item 4 tetap terbuka; historical credential rotation tetap diperlukan sebelum production.

## Perbaikan Terarah - Validator Video Lokal

- Mengganti pencarian string metadata dengan pembacaan box bertingkat, movie/track/media header, visual sample description, sample count/timing/chunk mapping dan offset yang harus menunjuk data mdat di dalam file. Fragmented MP4 didukung melalui trex/tfhd/trun; AVC/HEVC memakai pemeriksaan length-prefixed sample. Batas box/sample mencegah loop metadata tanpa batas.
- Negative fixture lama tetap MIME video/mp4 tetapi sekarang ditolak. Tidak mengubah finfo/filename gate, limit 20 MiB, tujuan upload, mekanisme reserve/transaction/commit/rollback, schema atau endpoint YouTube.
- Test container 26/26 PASS: tiga fixture encoded MP4/MOV/fragmented; truncation, zeroed encoded data, fake track, nested invalid size, invalid chunk offset/count/configuration, duplicate moov dan trailing garbage. Test tidak mengakses database; fixture sintetis dibuat lokal, tidak di-commit.
- Test existing upload 38/38 dan profile video source 51/51 PASS. Runtime browser testing: MP4/MOV playback, empat arah source switching, real SQL rollback, negative fake-track MP4/MOV pada endpoint upload, file lama tetap ada dan tidak ada orphan; settings dipulihkan dan upload dummy dibersihkan.
- Tidak melakukan audit besar atau memperbaiki YouTube error 150 pada subtask ini. Tidak staging/commit/push/deploy. Release candidate item 5-6 tetap bukti sebelum perubahan validator dan perlu rebuild terpisah.

## Perbaikan Terarah - YouTube Error 150 dan Fallback

- Parser existing mendukung youtube.com/watch?v=, youtu.be/ dan youtube.com/shorts/. Hostname exact allowlist, tanpa userinfo/custom port, Video ID 11 karakter; iframe menggunakan https://www.youtube.com/embed/ID, bukan watch URL atau tag video. Tidak perlu perubahan parser atau schema.
- A/B browser nyata dari origin lokal yang sama: pembanding M7lc1UVf-VE berhasil muted autoplay; sekolah bbWLfO9AE6M menghasilkan onError 150. Desktop 1440 dan mobile 390 konsisten; origin/HTTP Referer sesuai. Hasil ini menunjukkan penolakan embed spesifik video dari sisi YouTube, bukan bukti penyebab terperinci seperti klaim lagu tertentu.
- Dokumentasi resmi https://developers.google.com/youtube/iframe_api_reference#Events menyatakan 150 ekuivalen 101 (pemutaran embedded tidak diizinkan); berbeda dari 153 untuk identifikasi client/Referer. Tidak mencoba bypass atau mengubah konfigurasi channel/video.
- pages/home.php menambahkan bar fallback dengan link canonical watch yang dibentuk dari ID tervalidasi dan escaped; target _blank dengan rel noopener noreferrer. Link selalu tersedia termasuk tanpa JavaScript. templates/footer.php menampilkan pesan generik pada onError, menyembunyikannya kembali saat PLAYING. CSS hanya mengatur bar fallback responsif di area video.
- Targeted checks: parser source 51/51, XSS 79/79 PASS; perbandingan live A/B desktop/mobile PASS, pesan/link error terlihat pada B, A tetap autoplay dan pesan tersembunyi; tidak redirect otomatis; iframe tetap 16:9 dan tidak overflow; no-JS fallback visible PASS. Screenshot mobile diperiksa. Harness no-JS awal memakai role locator yang tidak menemukan link meski DOM berisi link; verifikasi diganti menjadi DOM locator disertai teks/href/visibility yang tetap wajib benar, bukan meniadakan assertion aplikasi.
- Test A hanya mengganti ID dalam respons browser untuk pembanding, tidak menulis settings/database. Bukti dan harness berada di storage/testing/manual-preview/ ignored; tidak ada production access. Source PHP lint terkait dan git diff --check divalidasi ulang.
- Status: investigasi dan penanganan fallback selesai; video sekolah masih tidak dapat diputar embedded dan pengguna diarahkan hanya melalui klik eksplisit Tonton di YouTube. Release ZIP terdahulu belum memuat validator/fallback baru, sehingga perlu rebuild sebelum final checkpoint. Tidak staging, commit, push atau deploy.

## Final Targeted Recheck - Setelah Dua Perbaikan

Validasi terbaru menggantikan status blocker aplikasi pada catatan historis di atas, tanpa menghapus bukti kegagalan sebelumnya.

- Corrupt video validation: PASS dalam skenario tersedia, 26/26 container checks termasuk original fake-track regression, truncated/invalid references dan corrupted sample framing.
- Valid MP4/MOV: PASS pada encoded fixture MP4/MOV/fragmented dan browser upload/playback. Targeted runtime juga lulus untuk source switching, real SQL rollback, mempertahankan file lama saat gagal, cleanup orphan dan pemulihan settings database testing.
- YouTube URL/embed: PASS 51/51 checks; watch, youtu.be dan shorts menghasilkan ID/embed benar, domain palsu dan input injection ditolak.
- YouTube public embed: PASS desktop/mobile. Video sekolah: error 150 dari sisi YouTube/video, bukan kesalahan parser atau pemakaian watch URL sebagai iframe; tidak bypass. Fallback Tonton di YouTube: PASS desktop/mobile serta tanpa JavaScript.
- PHP lint hanya empat file terkait perubahan: includes/upload_security.php, pages/home.php, templates/footer.php dan tests/profile_video_container_test.php; 4 PHP, 0 error.
- git diff --check: exit 0 memakai konfigurasi Git repository existing; warning konversi LF/CRLF tidak mengubah hasil pemeriksaan.
- Runtime dependency tidak bertambah: tanpa package/extension/executable baru, include baru atau schema baru. Tidak menjalankan full regression, audit ulang, release rebuild atau operasi Git write.
- READY FOR COMMIT & PUSH = YES untuk set 188 kandidat COMMIT yang tercantum di manifest ini, melalui staging terarah pada tahap yang disetujui pengguna; bukan git add semua working tree. Ini rekomendasi version-control checkpoint, bukan persetujuan deployment atau klaim seluruh codec/semua payload telah tersertifikasi.
- Dua diff legacy tetap DO NOT COMMIT: database/seed_final_content.php dan tests/admin_form_smoke_test.php. File privat, fixture/media hasil test, log/session, dump/backup dan generated release ZIP tetap tidak boleh di-commit. Versi legacy yang sudah tracked di HEAD tidak hilang dengan mengecualikan diff; disposisi penghapusan runtime/history bukan bagian tahap ini.
- Historical credential rotation tetap wajib sebelum production. Release ZIP lama masih memuat validator/fallback lama; perlu build source terbaru dan validasi release pada tahap release berikutnya, bukan alasan menjalankan ulang full audit saat targeted recheck ini.

Tidak melakukan git add, commit, push atau deploy. Database yang digunakan hanya testing terisolasi.
