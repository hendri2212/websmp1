# Tahap 8A Item 6 - Test dan Report File Decision

Tanggal: 2026-10-10. Ini rekomendasi kelayakan repository, bukan staging, deployment, atau izin menjalankan test pada database sekolah.

## Keputusan

| Jenis | COMMIT | IGNORE | MANUAL REVIEW | Total |
| --- | ---: | ---: | ---: | ---: |
| Test/probe/tooling/fixture source | 69 | 0 | 1 | 70 |
| Laporan existing | 18 | 78 | 1 | 97 |
| Laporan keputusan ini | 1 | 0 | 0 | 1 |

28 file di dalam tests/fixtures termasuk dalam 69 source yang dipertahankan, bukan tambahan di luar jumlah tersebut. Semua tests/fixtures/laporan dikecualikan dari release bundle, termasuk yang layak COMMIT.

## Dasar peninjauan

- Meninjau inventory tracked/untracked non-ignored, fungsi regression dan dependency test, hasil secret scan item 3, privacy indicators dan konteks fixture/laporan.
- Tidak teridentifikasi secret operasional/data siswa nyata dalam source test/fixture. Tidak mengaudit atau membuka database/credential/backup sekolah.
- Email literal test berada pada domain example/test/invalid yang diperuntukkan contoh, bukan alamat pribadi yang teridentifikasi. Nama identitas, input angka panjang, token/password dummy dan error sentinel ditujukan untuk positive/negative regression, bukan credential autentikasi sekolah.
- Fixture struktur tabel memuat definisi field identitas/password, bukan dump record nyata. Fixture dengan INSERT merupakan data sintetis untuk test terisolasi, tidak boleh dianggap seed production.
- Marker private-key di self-test scanner tidak memiliki materi key lengkap. Credential testing yang dibuat saat runtime dan disimpan dalam manifest ignored tidak ikut source commit.
- Nama NIK/NISN/tanggal lahir sebagai definisi field atau payload sintetis bukan bukti data pribadi nyata. Jika di kemudian hari fixture berasal dari record sekolah, jangan commit: ganti dengan fixture sintetis, bukan sekadar menyamarkan nama.
- Source tools dapat memakai path tool XAMPP generik. Ini dependency lokal non-pribadi, bukan path profil pengguna atau credential. Portabilitas tetap perlu didokumentasikan; tidak melakukan refactor tooling.
- Scan berbasis pola dan review konteks bukan jaminan semua kemungkinan data pribadi/secret telah terdeteksi.

## Kebijakan laporan

COMMIT hanya untuk laporan keputusan/arsitektur/security yang berguna dan tidak teridentifikasi memuat secret, nilai credential, data pribadi, path profil pengguna, atau lokasi evidence privat.

IGNORE direkomendasikan untuk versi mentah laporan per-run/lokal yang memuat path mesin/profil pengguna, konfigurasi/identitas instance testing, snapshot/result/log privat atau lokasi bundle lokal. Laporan itu tetap berguna bagi operator; tidak dihapus. Jika diperlukan di repository, buat ringkasan tersunting terpisah setelah review, bukan upload versi mentah.

Laporan yang layak repository tetap merupakan catatan historis sesuai tanggal/tahapnya. Pernyataan mengenai PPDB legacy, schema mutation, atau diagnostic lama bukan konfigurasi terkini dan bukan instruksi mengaktifkan kembali fitur lama. Laporan umum website yang masih menyebut PPDB online ditahan MANUAL REVIEW sampai konteksnya diselaraskan.

Item ini memperketat keputusan COMMIT laporan pada item 5. TAHAP_8A_GIT_FILE_CLASSIFICATION.json telah diperbarui sebagai keputusan terkini. Tabel lama item 5 dipertahankan sebagai snapshot historis, bukan daftar staging terbaru.

## Daftar setiap file test/fixture/laporan

| File | Jenis | Keputusan | Alasan |
| --- | --- | --- | --- |
| LAPORAN_AKHIR_TAHAP_3.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_AKHIR_TAHAP_3B.md | REPORT | IGNORE | Laporan per-run/lokal memuat identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_AKHIR_TAHAP_4_PPDB.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_AKHIR_TAHAP_5.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_AKHIR_TAHAP_6.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_AUDIT_ACCESS_CONTROL.md | REPORT | IGNORE | Laporan per-run/lokal memuat identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_AUDIT_AUTHENTICATION.md | REPORT | IGNORE | Laporan per-run/lokal memuat path tool mesin lokal; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_AUDIT_CSRF.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_AUDIT_ERROR_HANDLING.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_AUDIT_LOGIN_LANJUTAN.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_AUDIT_ROLE_PERMISSION.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_AUDIT_SESSION.md | REPORT | IGNORE | Laporan per-run/lokal memuat path tool mesin lokal; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_AUDIT_TAHAP_1.md | REPORT | IGNORE | Laporan per-run/lokal memuat path tool mesin lokal; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_AUDIT_TAHAP_2.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_DEPENDENSI_PPDB_CLEANUP.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_ROLE_PERMISSION_PPDB_PASIF.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md | REPORT | MANUAL REVIEW | Dokumentasi lama masih menyebut PPDB online dan nama DB lokal; perlu konfirmasi/update konteks sebelum publikasi. |
| LAPORAN_TAHAP_3_CLEANUP_DATA_TESTING.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_CRUD_KONTEN.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_DATABASE_CONSISTENCY.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_ERROR_HANDLING.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_FILE_DOWNLOAD.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_LOGIN_LOGOUT.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_PRAFLIGHT.md | REPORT | IGNORE | Laporan per-run/lokal memuat path tool mesin lokal, lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_REGRESSION_CHECK.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_ROLE_CRUD.md | REPORT | IGNORE | Laporan per-run/lokal memuat path tool mesin lokal, lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_UPLOAD.md | REPORT | IGNORE | Laporan per-run/lokal memuat path tool mesin lokal, lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3_VALIDASI_INPUT.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_3B_RETEST.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_AUDIT_ROLE_PPDB.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_BERKAS_PPDB_LEGACY.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_DATABASE_PPDB.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_DEAD_CODE_PPDB.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_DELETE_FEEDBACK.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_ENTRY_POINT_PPDB.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_FILE_LIFECYCLE.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_HALAMAN_INFORMASI_PPDB.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_INPUT_VALIDATION.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_INVENTARISASI_PPDB_LEGACY.md | REPORT | IGNORE | Laporan per-run/lokal memuat path profil pengguna, lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_MENU_ADMIN_PPDB.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_PENGUMUMAN_PUBLIK.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_PERBAIKAN_STORED_XSS.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_PPDB_INFORMATIF.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_REGRESSION.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_SECURITY_ENDPOINT_PPDB.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_STRICT_ID_VALIDATION.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_4_UPLOAD_SECURITY.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_AUDIT_DATABASE_ARCHITECTURE.md | REPORT | IGNORE | Laporan per-run/lokal memuat identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_BACKUP_COMPLETENESS.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_BACKUP_RETENTION.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_BACKUP_SECURITY.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_CLEANUP_TEST.md | REPORT | IGNORE | Laporan per-run/lokal memuat identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_DATABASE_FAILURE_HANDLING.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_FILE_BACKUP.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_LAST_SUPERADMIN_SAFETY.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_PPDB_LEGACY_RETENTION.md | REPORT | IGNORE | Laporan bukti per-run/snapshot privat lokal; berguna untuk operator, bukan publikasi versi mentah. |
| LAPORAN_TAHAP_5_REGRESSION_TEST.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_RESTORE_TEST.md | REPORT | IGNORE | Laporan per-run/lokal memuat path tool mesin lokal, lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_RUNTIME_SCHEMA_STABILITY.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_5_TRANSACTION_AUDIT.md | REPORT | IGNORE | Laporan per-run/lokal memuat identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_6_APACHE_SECURITY.md | REPORT | IGNORE | Laporan per-run/lokal memuat path tool mesin lokal; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_6_AUDIT_GITIGNORE.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_BACKUP_EXPOSURE.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_6_DEFAULT_TEST_ACCOUNT.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_6_DIRECTORY_LISTING.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_6_HTTP_METHOD_ROUTING.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_HTTPS.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_INVENTARIS_PRODUCTION.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_6_KOREKSI_REGRESSION_PPDB.md | REPORT | IGNORE | Laporan bukti per-run/snapshot privat lokal; berguna untuk operator, bukan publikasi versi mentah. |
| LAPORAN_TAHAP_6_PHP_ERROR_CONFIGURATION.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_PRODUCTION_CONFIGURATION.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_PRODUCTION_FILE_PERMISSIONS.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_PUBLIC_INFORMATION_EXPOSURE.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_REGRESSION_TAHAP_1_5.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_6_SECURITY_HEADERS.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_6_SENSITIVE_FILE_HTTP_TEST.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_6_SETUP_MAINTENANCE.md | REPORT | IGNORE | Laporan per-run/lokal memuat identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_6_UPLOAD_DIRECTORY_HARDENING.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_BACKUP_CAPACITY_NOTE.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_BUILD_RELEASE_BUNDLE.md | REPORT | IGNORE | Laporan per-run/lokal memuat path profil pengguna, path tool mesin lokal; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_DEFAULT_INSTALLER_CREDENTIAL.md | REPORT | IGNORE | Laporan per-run/lokal memuat identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_FINAL_REGRESSION.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_GIT_CHECK_COMMIT_GROUPING.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_HTTPS_HEADER_SIMULATION.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_LOCAL_RELEASE_SMOKE_TEST.md | REPORT | IGNORE | Laporan per-run/lokal memuat path profil pengguna, path tool mesin lokal, lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_PRODUCTION_CONFIG_FAIL_CLOSED.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_7_PRODUCTION_CONFIG_SIMULATION.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_RELEASE_BUNDLE_DEPENDENCY_CHECK.md | REPORT | IGNORE | Laporan per-run/lokal memuat path profil pengguna, path tool mesin lokal; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_RELEASE_BUNDLE_SECURITY_SCAN.md | REPORT | IGNORE | Laporan per-run/lokal memuat path profil pengguna, path tool mesin lokal; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_REVIEW_GIT_CHANGES.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_7_STORAGE_WRITABILITY.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_7_TRACKED_SECRETS.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_8A_FINAL_SECRET_SCAN_RELEASE.md | REPORT | IGNORE | Laporan per-run/lokal memuat path profil pengguna, path tool mesin lokal; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_8A_FINAL_SECRET_SCAN_WORKING_TREE.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_8A_GIT_HISTORY_CREDENTIAL.md | REPORT | COMMIT | Keputusan/arsitektur/security berguna; tidak teridentifikasi secret, data pribadi, path profil pengguna atau evidence privat. Pertahankan sebagai dokumentasi historis, bukan status current. |
| LAPORAN_TAHAP_8A_LITERAL_CREDENTIAL_LEGACY.md | REPORT | IGNORE | Laporan per-run/lokal memuat path profil pengguna, path tool mesin lokal, lokasi evidence/state privat, identitas/config instance testing; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_8A_REVIEW_DIRTY_FILES.md | REPORT | IGNORE | Laporan per-run/lokal memuat lokasi evidence/state privat; jangan publikasi versi mentah. Ringkasan terkurasi dapat disusun terpisah. |
| LAPORAN_TAHAP_8A_TEST_REPORT_FILE_DECISION.md | REPORT | COMMIT | Keputusan file yang terkurasi; hanya path relatif source, alasan dan hitungan, tanpa identitas/config/run-path privat. |
| tests/admin_form_smoke_test.php | TEST | MANUAL REVIEW | Test legacy memuat bootstrap tanpa preflight DB terisolasi; jangan jalankan/stage sebelum guard dan cleanup ditinjau. |
| tests/auth_security_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/csrf_handler_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/delete_feedback_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/error_handling_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/file_access_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_auth_tables.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_consistency_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_content_cases.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_content_tables.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_crud_role_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/fixtures/stage3_download_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_error_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_error_http_probe.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_input_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage3_upload_apache_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/fixtures/stage3_upload_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_announcement_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_delete_feedback_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_id_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_input_validation_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_media_lifecycle_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_media_lifecycle_probe.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_ppdb_direct_entry_probe.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_ppdb_information_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_ppdb_legacy_security_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_ppdb_private_file_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage4_xss_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_backup_completeness_checks.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_backup_completeness_probe.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_backup_probe.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_failure_database.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage5_failure_router.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/fixtures/stage6_error_http_router.php | FIXTURE | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/https_readiness_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/input_validation_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/legacy_ppdb_database_access_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/maintenance_entrypoint_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/prepare_stage3_database.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/role_permission_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/runtime_schema_stability_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/session_configuration_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage3_auth_http_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage3_cleanup_test_data.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage3_content_http_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage3_database_preflight.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage4_legacy_file_reference_fixture.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage4_non_ppdb_regression.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage4_ppdb_dead_code_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage4_ppdb_public_browser_test.cjs | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_account_transaction_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_backup_restore_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/stage5_cleanup_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_connection_timeout_model_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_database_failure_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage5_file_backup_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage6_apache_security_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/stage6_error_configuration_http_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage6_security_headers_browser_test.cjs | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_build_release.ps1 | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/stage7_bundle_dependency_check.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_bundle_security_scan.ps1 | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_configuration_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_initial_admin_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage7_release_smoke_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. Referensi tool XAMPP generik bukan profil pengguna; portabilitas tool lokal perlu didokumentasikan. |
| tests/stage7_secret_audit.ps1 | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/stage8_history_credential_audit.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/strict_id_validation_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/upload_security_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |
| tests/xss_output_security_test.php | TEST | COMMIT | Source regression/probe/fixture sintetis; bukan hasil test atau credential nyata. Exclude release. |

## Dua manual review khusus

- tests/admin_form_smoke_test.php: source legacy memasukkan bootstrap tanpa preflight database terisolasi. Passwordnya dimaksudkan untuk fixture, tetapi target koneksi/cleanup belum aman untuk dieksekusi. Jangan stage atau jalankan sampai keputusan perbaikannya dibuat.
- LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md: deskripsi lama masih menyebut PPDB online. Bukan bukti credential nyata; tetap membutuhkan review konteks sebelum publikasi.

## Artefak yang tidak boleh ikut repository

Manifest koneksi/initial account terisi, environment aktual, cookie/session, dump/database testing, log, screenshot/profile browser, generated result/snapshot/upload dummy, backup dan release ZIP tetap privat/ignored. Mereka bukan source fixture. Jangan force-add hanya karena dihasilkan oleh test.

Rule .gitignore belum diubah untuk 78 laporan yang direkomendasikan IGNORE. Artinya file tersebut masih dapat tampil untracked; keputusan JSON terbaru harus dipakai untuk pengecualian staging. Implementasi ignore yang kelak disetujui sebaiknya menggunakan path eksplisit, bukan blanket tests/ atau LAPORAN* yang akan mengabaikan source penting/laporan aman.

## Perubahan dan validasi

Hanya menambahkan laporan ini, memperbarui inventaris keputusan JSON, dan memberi catatan superseded pada review item 5. Tidak mengubah source aplikasi/test/fixture/.gitignore atau isi laporan historis lain. Tidak menghapus file, menjalankan regression/database test, staging, commit, push, atau deploy.

Snapshot final yang diinventarisasi: 256 dirty = 72 modified + 184 untracked, 0 staged. Klasifikasi keseluruhan: 164 COMMIT, 78 IGNORE, 0 DELETE LOCAL ARTIFACT, 14 MANUAL REVIEW. Inventory harus cocok tepat dengan status Git tanpa duplikasi/file terlewat; git diff --check diperiksa.

**ROTATION REQUIRED BEFORE PRODUCTION** tetap berlaku. Keputusan ini tidak menyatakan Git checkpoint seluruhnya disetujui atau website siap production.
