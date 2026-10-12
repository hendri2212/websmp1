# Tahap 8A - Prepare Staging Manifest

Tanggal: 2026-10-10. Manifest rekomendasi saja; git add tidak dijalankan.

## Dasar dan status

- Snapshot awal item 11: 181 dirty = 72 modified + 109 untracked; 0 staged.
- Dicocokkan dengan TAHAP_8A_GIT_FILE_CLASSIFICATION.json dan Git status aktual.
- Tahap 8A.2 item 1 menutup tujuh runtime/config menjadi COMMIT. Item 2 menutup tiga initializer/maintenance/schema menjadi COMMIT dan seed menjadi DO NOT COMMIT. Item 3 menetapkan test legacy DO NOT COMMIT; item 4 memperbarui dua dokumen dan menutup keduanya menjadi COMMIT.
- Inventaris setelah item 9: 186 rekomendasi, 0 tertahan, 2 DO NOT COMMIT; total 188 dirty (73 modified + 115 untracked), termasuk kedua manifest dan empat laporan closure. STAGING_MANIFEST_FINAL.md memuat category/decision/group/reason setiap dirty kandidat. Template .env.example direkomendasikan COMMIT, bukan release.
- Keputusan inventaris/manifest, laporan, README, dan dua dokumen lama diperbarui; tidak mengubah source runtime, test, atau credential.
- Seluruh path di bawah relatif terhadap root repository. Daftar eksplisit, tanpa wildcard.
- Tidak ada git add, commit, push, deploy, penghapusan, atau perubahan Git history.

## Ringkasan kelompok

| Commit group | Direkomendasikan | Manual review | DO NOT COMMIT |
| --- | ---: | ---: | ---: |
| 1. Runtime & Security | 75 | 0 | 0 |
| 2. Maintenance / Initializer | 4 | 0 | 1 |
| 3. Regression Tests | 64 | 0 | 1 |
| 4. Release Tooling | 8 | 0 | 0 |
| 5. Documentation | 35 | 0 | 0 |
| Total | 186 | 0 | 2 |

## Dependency gate sebelum checkpoint

1. Bootstrap memuat includes/functions.php, config/app.php melalui app_config(), config/database.php, includes/database.php, includes/auth.php, dan includes/repositories.php. Kedua config memuat config/env.php. Kelima dependency inti telah direview dan sekarang tercantum dalam group 1; pertahankan satu perubahan runtime yang koheren. Closure ini bukan persetujuan provisioning hosting/credential production.
2. includes/functions.php bergantung pada includes/input_validation.php, includes/upload_security.php, dan includes/media_lifecycle.php; helper/repository/caller admin-public-template harus dipertahankan dalam satu perubahan runtime yang koheren.
3. database/initialize_admin.php bergantung pada includes/database.php dan konfigurasi. database/maintenance_legacy_schema.php langsung memuat tests/stage3_database_preflight.php dan config/site_data.php. Guard preflight masuk group 2 agar dependency maintenance eksplisit, tanpa memindahkan file. Initializer/maintenance/schema telah direview untuk repository saja, bukan public release. Seed memuat bootstrap runtime tetapi bukan dependency runtime; perubahan seed tidak direkomendasikan untuk staging.
4. Regression group 3 menggunakan runtime group 1, guard group 2, seluruh fixture yang tercantum, serta initializer/schema untuk test yang relevan. tests/stage7_initial_admin_test.php membaca database/schema.sql dan database/initialize_admin.php. Jangan menyatakan setiap commit parsial sudah mandiri/lulus hanya berdasarkan regression seluruh working tree.
5. tests/stage7_build_release.ps1 membaca RELEASE_ALLOWLIST.json dan RELEASE_DENYLIST.json, lalu source runtime group 1. Release scanner/dependency checker/smoke memerlukan output build lokal yang tidak di-commit. Policy Markdown berada di group 5 sebagai dokumentasi, bukan dependency eksekusi.
6. Private testing configuration harus diprovision terpisah. File connection, bootstrap credential, datadir, marker/run artifacts, ZIP release, dan backup tidak pernah menjadi dependency yang boleh di-stage.
7. Nomor group adalah kategori logis, bukan instruksi menjalankan lima commit sekarang. Final checkpoint ditahan sampai dependency manual yang wajib sudah diputuskan. Historical credential: ROTATION REQUIRED BEFORE PRODUCTION, tanpa rewrite history.
8. database/seed_final_content.php sudah tracked di HEAD. DO NOT COMMIT pada perubahan working tree tidak menghapus versi lama dari repository atau menghilangkan dependency test CLI guard atas path tersebut. Jangan mengklaim snapshot staged identik dengan working tree regression. Tentukan disposisi versi tracked secara terpisah sebelum checkpoint akhir; tidak delete/untrack/revert pada review ini.
9. tests/admin_form_smoke_test.php juga sudah tracked. Keputusan DO NOT COMMIT bukan penghapusan versi HEAD: rekomendasi penghapusan legacy harus disetujui/diterapkan secara terpisah sebelum checkpoint, dengan audit referensi dan discovery entrypoint. Jangan menjalankan test ini; README sudah menunjuk suite pengganti terisolasi.
10. Item 5 dependency consistency: 78 required runtime tersedia dan identik dengan inventory candidate yang telah diuji (69 dirty COMMIT, 9 clean tracked), 0 overlap DO NOT COMMIT. Builder/validator/template/support dependency telah disetujui COMMIT. Include/view/asset checker dan release lint lulus; tidak ada runtime config yang memerlukan template pengganti. Staging kelak harus mempertahankan seluruh dependency tersebut, lalu memvalidasi revision final; hasil static/hash ini bukan smoke runtime baru. Environment, DB/media/storage privat diprovision terpisah.
11. Item 7 static database safety: schema CREATE offline, initializer INSERT akun eksplisit CLI, maintenance ALTER/CREATE/backfill testing-only, dan dump backup dengan DROP/CREATE/INSERT telah diklasifikasikan dalam laporan database closure. Tidak menjalankan SQL. Seed tetap DO NOT COMMIT karena tidak memiliki explicit write confirmation/isolated-target guard; versi tracked harus diputuskan, bukan dianggap aman karena CLI-only. Dump restore destructive tidak boleh di-auto-import atau salah target.

## 1. Runtime & Security

Kode panel admin, public entry points, template, asset, resolver konfigurasi aman, dan helper security. Tujuh file runtime/config yang sebelumnya tertahan sekarang COMMIT berdasarkan laporan closure item 1. Dua snippet server hanya referensi repository dan tetap tidak masuk payload web; penerapan hosting terpisah.

### Direkomendasikan untuk git add setelah gate terpenuhi

```text
.env.example
.env.production.example
.gitignore
.htaccess
.user.ini
admin/achievements.php
admin/activity_logs.php
admin/admins.php
admin/agenda.php
admin/announcements.php
admin/change_password.php
admin/contact_messages.php
admin/dashboard.php
admin/db_backup.php
admin/documents.php
admin/extracurriculars.php
admin/facilities.php
admin/faqs.php
admin/gallery.php
admin/login.php
admin/logout.php
admin/news.php
admin/ppdb.php
admin/ppdb_applications.php
admin/ppdb_download.php
admin/ppdb_export.php
admin/production_check.php
admin/settings.php
admin/staff.php
config/apache_security.conf
config/app.php
config/database.php
config/env.php
config/php_security.ini
config/site_data.php
includes/auth.php
includes/bootstrap.php
includes/database.php
includes/error_handling.php
includes/functions.php
includes/input_validation.php
includes/media_lifecycle.php
includes/repositories.php
includes/schema_requirements.php
includes/upload_security.php
index.php
pages/404.php
pages/accreditation_file.php
pages/accreditation_view.php
pages/berita.php
pages/berita_detail.php
pages/cari.php
pages/document_download.php
pages/dokumen.php
pages/ekstrakurikuler.php
pages/faq.php
pages/fasilitas.php
pages/galeri.php
pages/guru_staf.php
pages/home.php
pages/kebijakan_privasi.php
pages/kesiswaan.php
pages/kontak.php
pages/ppdb.php
pages/ppdb_receipt.php
pages/prestasi.php
pages/prestasi_detail.php
pages/profil.php
pages/sitemap.php
public/assets/css/style.css
public/uploads/.htaccess
templates/admin_footer.php
templates/admin_header.php
templates/footer.php
templates/header.php
```

## 2. Maintenance / Initializer

Initializer/schema/maintenance direkomendasikan repository tetapi tidak public release. Guard testing bersama tetap berada di tests/; tidak ada perubahan struktur project. Seed diberi DO NOT COMMIT karena risiko target/write/audit dan konten sekolah yang bukan fixture. Tidak menjalankan initializer/seed/maintenance terhadap production.

### Direkomendasikan untuk git add setelah gate terpenuhi

```text
database/initialize_admin.php
database/maintenance_legacy_schema.php
database/schema.sql
tests/stage3_database_preflight.php
```

### DO NOT COMMIT - jangan git add

```text
database/seed_final_content.php
```

## 3. Regression Tests

Regression source dan fixture sintetis; bukan hasil run, cookie, session, dump, backup, atau credential testing. Tests tidak ikut deployment bundle.

### Direkomendasikan untuk git add setelah gate terpenuhi

```text
tests/auth_security_test.php
tests/csrf_handler_test.php
tests/delete_feedback_test.php
tests/error_handling_test.php
tests/file_access_test.php
tests/fixtures/stage3_auth_tables.php
tests/fixtures/stage3_consistency_checks.php
tests/fixtures/stage3_content_cases.php
tests/fixtures/stage3_content_tables.php
tests/fixtures/stage3_crud_role_checks.php
tests/fixtures/stage3_download_checks.php
tests/fixtures/stage3_error_checks.php
tests/fixtures/stage3_error_http_probe.php
tests/fixtures/stage3_input_checks.php
tests/fixtures/stage3_upload_apache_checks.php
tests/fixtures/stage3_upload_checks.php
tests/fixtures/stage4_announcement_checks.php
tests/fixtures/stage4_delete_feedback_checks.php
tests/fixtures/stage4_id_checks.php
tests/fixtures/stage4_input_validation_checks.php
tests/fixtures/stage4_media_lifecycle_checks.php
tests/fixtures/stage4_media_lifecycle_probe.php
tests/fixtures/stage4_ppdb_direct_entry_probe.php
tests/fixtures/stage4_ppdb_information_checks.php
tests/fixtures/stage4_ppdb_legacy_security_checks.php
tests/fixtures/stage4_ppdb_private_file_checks.php
tests/fixtures/stage4_xss_checks.php
tests/fixtures/stage5_backup_completeness_checks.php
tests/fixtures/stage5_backup_completeness_probe.php
tests/fixtures/stage5_backup_probe.php
tests/fixtures/stage5_failure_database.php
tests/fixtures/stage5_failure_router.php
tests/fixtures/stage6_error_http_router.php
tests/gitignore_policy_test.ps1
tests/https_readiness_test.php
tests/input_validation_test.php
tests/legacy_ppdb_database_access_test.php
tests/maintenance_entrypoint_test.php
tests/prepare_stage3_database.php
tests/role_permission_test.php
tests/runtime_schema_stability_test.php
tests/session_configuration_test.php
tests/stage3_auth_http_test.php
tests/stage3_cleanup_test_data.php
tests/stage3_content_http_test.php
tests/stage4_legacy_file_reference_fixture.php
tests/stage4_non_ppdb_regression.php
tests/stage4_ppdb_dead_code_test.php
tests/stage4_ppdb_public_browser_test.cjs
tests/stage5_account_transaction_test.php
tests/stage5_backup_restore_test.php
tests/stage5_cleanup_test.php
tests/stage5_connection_timeout_model_test.php
tests/stage5_database_failure_test.php
tests/stage5_file_backup_test.php
tests/stage6_apache_security_test.php
tests/stage6_error_configuration_http_test.php
tests/stage6_security_headers_browser_test.cjs
tests/stage7_configuration_test.php
tests/stage7_initial_admin_test.php
tests/stage7_release_smoke_test.php
tests/strict_id_validation_test.php
tests/upload_security_test.php
tests/xss_output_security_test.php
```

### DO NOT COMMIT - jangan git add

```text
tests/admin_form_smoke_test.php
```

Review ditutup: tidak ada guard database testing, cleanup prefix lintas run, session superadmin buatan, dan workflow pendaftaran PPDB obsolete. Superseded oleh suite auth/content HTTP terisolasi; file tetap dipertahankan, tidak dijalankan atau dihapus pada review ini.

## 4. Release Tooling

Policy allowlist/denylist dan tooling build/audit/release; preview_router.php adalah router development CLI-server, bukan entry point production. Berada di repository tidak berarti ikut hosting.

### Direkomendasikan untuk git add setelah gate terpenuhi

```text
RELEASE_ALLOWLIST.json
RELEASE_DENYLIST.json
preview_router.php
tests/stage7_build_release.ps1
tests/stage7_bundle_dependency_check.php
tests/stage7_bundle_security_scan.ps1
tests/stage7_secret_audit.ps1
tests/stage8_history_credential_audit.php
```

## 5. Documentation

Dokumentasi terkurasi, keputusan klasifikasi, dan manifest ini. Laporan per-run lokal tetap ignored. Dua dokumen lama sudah diperbarui dan direview; keduanya tidak masuk release.

### Direkomendasikan untuk git add setelah gate terpenuhi

```text
DEPLOYMENT.md
HOSTING_REQUIREMENTS_CHECKLIST.md
LAPORAN_AUDIT_CSRF.md
LAPORAN_AUDIT_ERROR_HANDLING.md
LAPORAN_AUDIT_LOGIN_LANJUTAN.md
LAPORAN_AUDIT_ROLE_PERMISSION.md
LAPORAN_AUDIT_TAHAP_2.md
LAPORAN_DEPENDENSI_PPDB_CLEANUP.md
LAPORAN_ROLE_PERMISSION_PPDB_PASIF.md
LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md
LAPORAN_TAHAP_6_AUDIT_GITIGNORE.md
LAPORAN_TAHAP_6_HTTPS.md
LAPORAN_TAHAP_6_HTTP_METHOD_ROUTING.md
LAPORAN_TAHAP_6_PHP_ERROR_CONFIGURATION.md
LAPORAN_TAHAP_6_PRODUCTION_CONFIGURATION.md
LAPORAN_TAHAP_6_PRODUCTION_FILE_PERMISSIONS.md
LAPORAN_TAHAP_6_PUBLIC_INFORMATION_EXPOSURE.md
LAPORAN_TAHAP_6_SECURITY_HEADERS.md
LAPORAN_TAHAP_7_PRODUCTION_CONFIG_FAIL_CLOSED.md
LAPORAN_TAHAP_7_STORAGE_WRITABILITY.md
LAPORAN_TAHAP_8A_2_DATABASE_MAINTENANCE_CLOSURE.md
LAPORAN_TAHAP_8A_2_DOCUMENTATION_CLOSURE.md
LAPORAN_TAHAP_8A_2_LEGACY_TEST_CLOSURE.md
LAPORAN_TAHAP_8A_2_RUNTIME_CONFIG_CLOSURE.md
LAPORAN_TAHAP_8A_FINAL_GITIGNORE.md
LAPORAN_TAHAP_8A_GIT_HISTORY_CREDENTIAL.md
LAPORAN_TAHAP_8A_LOCAL_PATH_EXPOSURE.md
LAPORAN_TAHAP_8A_TEST_REPORT_FILE_DECISION.md
README.md
RELEASE_ALLOWLIST.md
RELEASE_DENYLIST.md
REPOSITORY_CLEANUP_PLAN.md
STAGING_MANIFEST_TAHAP_8A.md
STAGING_MANIFEST_FINAL.md
TAHAP_8A_GIT_FILE_CLASSIFICATION.json
```

## Status manual review

Tidak ada file MANUAL REVIEW tersisa dalam inventaris ini. DEPLOYMENT.md dan LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md berstatus UPDATE THEN COMMIT yang sudah diselesaikan; rekomendasi final COMMIT untuk repository saja. Disposisi dua file DO NOT COMMIT yang sudah tracked dan historical credential rotation tetap gate terpisah.

## Tidak masuk daftar staging

- Semua file ignored tetap ignored; tidak memakai force-add.
- database/seed_final_content.php: DO NOT COMMIT perubahan saat ini; file tetap tracked, tidak dihapus/untrack/revert. Disposisi versi HEAD masih memerlukan keputusan terpisah, bukan otomatis selesai oleh pengecualian staging.
- tests/admin_form_smoke_test.php: DO NOT COMMIT perubahan saat ini; test unsafe/obsolete, jangan dijalankan. File tracked tetap ada sampai keputusan penghapusan terpisah, bukan masuk group test yang direkomendasikan.
- .env dan config lokal bercredential, private key, token, database connection manifests, SMTP/backup secret.
- storage/testing/, database testing/datadir, cookie/session, fixture hasil run, log, temporary upload dan canary.
- Database dump, backup historis/testing, hasil restore, generated ZIP/release payload dan receipt/hash output build lokal.
- Git metadata, IDE/OS artifact, local snapshot, serta laporan per-run/lokal yang sudah di-ignore.
- Daftar 79 laporan lokal ignored tetap mengikuti audit sebelumnya; laporan FINAL_REGRESSION.md berada di storage/testing yang ignored.
- Contoh aman .env.production.example boleh di repository sesuai group 1; ini tidak memberi izin menambahkan .env sebenarnya.
- File runtime/assets yang sudah tracked dan tidak dirty tidak perlu git add ulang; tetap merupakan dependency release sesuai allowlist.

## Penggunaan dan verifikasi

1. Review diff persis setiap file yang akan dipilih; jangan git add . atau folder/wildcard secara menyeluruh.
2. Selesaikan gate/manual review yang relevan sebelum memilih group. Berstatus direkomendasikan bukan bukti approval production.
3. Pada langkah staging terpisah yang kelak diizinkan, gunakan nama file eksplisit dari blok direkomendasikan. Jangan memasukkan blok tertahan sebelum statusnya disetujui, dan jangan memasukkan blok DO NOT COMMIT.
4. Setelah staging kelak diizinkan: review staged name/status/diff, secret scan staged content, dan diff --check; pastikan tidak ada ignored artifact yang force-added.
5. Seluruh regression bekerja pada gabungan working tree; ulangi pemeriksaan revision final setelah checkpoint disetujui. Timeout transport pada run regression awal tetap merupakan catatan terbuka.
6. Manifest ini tidak menjadikan website siap production atau mengizinkan commit/push/deploy.

## Validasi manifest

Daftar setelah Tahap 8A.2 item 9 diverifikasi: setiap dirty file muncul tepat sekali, termasuk template .env.example serta kedua manifest dan empat laporan closure; tidak ada path hilang/duplikat/ignored. Keputusan 186 COMMIT, 0 MANUAL REVIEW, 2 DO NOT COMMIT sesuai inventaris. Total 188 dirty, 0 staged. Tidak ada operasi index Git. Disposisi tracked seed/test legacy masih menjadi gate checkpoint terpisah; manifest final bukan approval deployment.
