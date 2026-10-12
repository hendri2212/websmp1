# Tahap 7 Item 7 - Release Denylist

Tanggal: 10 Oktober 2026. Kebijakan kandidat untuk source bundle, bukan perintah menghapus file repository/workspace. Bundle belum dibuat; tidak ada deployment, staging, commit, push, atau akses database.

## Pasangan Allowlist

[RELEASE_DENYLIST.json](RELEASE_DENYLIST.json) menetapkan aturan path dan urutan evaluasi untuk [RELEASE_ALLOWLIST.json](RELEASE_ALLOWLIST.json). Default deny tetap berlaku: file yang tidak ada pada exact allowlist tidak boleh masuk walau tidak cocok pola deny khusus.

Pola regex case-insensitive berjalan terhadap path file relatif root dengan slash normal. Builder kelak wajib menolak path absolute, traversal, symlink/junction dan target keluar root. Manifest ini belum script packaging.

## Dependency Audit Sebelum Exclude

Request memakai index.php/admin handler, bootstrap, helper, 4 source config, templates, pages, CSS dan gambar existing. Pemeriksaan literal include serta seluruh view switch pada item 6 mencakup 78 required files, 71 PHP, 91 relasi include dan 22 view dinamis. Pencarian ulang pada source runtime item ini tidak menemukan pemuatan wajib tests/fixtures/setup/maintenance CLI/local router/report.

Satu referensi installer ditemukan pada admin/db_backup.php: database/schema.sql dibaca secara opsional jika tersedia. Source installer tidak wajib request normal dan tetap exclude; SQL backup aktual berasal dari SHOW CREATE TABLE/data DB. Tanpa installer, entry database/schema.sql ZIP dapat kosong sesuai implementasi existing. Restore memakai backup SQL aktual, bukan entry kosong itu. Perilaku ini perlu retest ketika bundle nyata dibentuk, bukan dianggap sudah diuji pada item ini.

## Tidak Masuk Source Release

| File/kelompok | Bukti/keputusan |
| --- | --- |
| .git/, .github/ dan metadata VCS/CI | Tidak diperlukan interpreter/request website; .github belum ditemukan di root saat audit, tetap ditolak jika kelak ada |
| tests/, fixture/snapshot folder, test-results, screenshot/profile/probe | Tidak ada dependency runtime wajib; test/fixture source boleh tetap repository, bukan deployment |
| LAPORAN*.md, README, DEPLOYMENT, checklist, cleanup plan, allowlist/denylist metadata | Dokumentasi offline; bukan file web runtime |
| preview_router.php, utilitas lokal, debug/phpinfo/setup script | Router development tidak diperlukan Apache production; script lain yang tidak exact-allowlisted tetap ditolak |
| database/schema.sql, seed_final_content.php, initialize_admin.php, maintenance_legacy_schema.php | Installer/seed/CLI maintenance offline; bukan runtime migration atau public hosting requirement |
| .env termasuk template; .envrc; config local; credential manifest; private key | Source resolver config tetap dipertahankan, environment/secret terisi diprovision privat terpisah |
| SQL dump/archive, backup testing/historis, log, cache/temp/session/cookie | State/data privat, bukan source bundle; tidak dihapus atau dipublikasikan |
| storage/testing, source_docs, backups, sessions, uploads, media-cleanup | Isi workspace tidak dicopy. Data approved/private runtime directory disiapkan terpisah |
| public/uploads media dan .gitkeep lokal | Media persisten bukan source; inventaris migrasi terpisah. Kontrol .htaccess adalah exception exact path |
| IDE files, node_modules, vendor, generated/dependency metadata | Tidak ada dependency vendor/node runtime ditemukan sekarang; jika berubah, re-audit manifest sebelum packaging |
| config/apache_security.conf, config/php_security.ini | Snippet operator hosting, bukan public bundle; dapat berada pada paket operasi privat |

## Jangan Salah Exclude Dependency Runtime

- **admin/db_backup.php** tetap disertakan: endpoint backup berpermission, bukan file backup atau CLI maintenance.
- **admin/production_check.php** tetap disertakan: dirujuk menu/dashboard dan server-side permission. Review manual masih berlaku; jangan menghapus endpoint karena dianggap script debug berdasarkan nama saja.
- **pages/maintenance.php** tetap disertakan: view maintenance publik yang dipilih index.php, bukan script CLI migration.
- **includes/schema_requirements.php** tetap disertakan: pemeriksaan kompatibilitas read-only yang dipakai auth/repository, bukan auto schema mutation.
- **config/app.php, database.php, env.php, site_data.php** tetap disertakan: source runtime. Jangan mengeluarkan seluruh folder config atau membuka akses HTTP-nya.
- **PPDB informasi dan tombstone** tetap disertakan untuk penolakan URL legacy: admin/ppdb.php, ppdb_applications.php, ppdb_download.php, ppdb_export.php serta pages/ppdb.php dan ppdb_receipt.php. Tidak mengaktifkan workflow atau menghapus data/file historis.
- **storage/.htaccess dan public/uploads/.htaccess** adalah exception literal untuk kontrol akses; bukan pengecualian seluruh isi storage/uploads.
- **.user.ini** exception bersyarat: hanya hosting yang menerapkannya, atau operator menyiapkan konfigurasi ekuivalen yang telah ditinjau. Blanket deny file tersembunyi tidak boleh membuang kontrol wajib .htaccess.

## Urutan Penerapan Kelak

1. Validasi path dan containment sebelum membaca/menyalin file. Reject link/junction, absolute path, traversal, atau nama yang belum dapat diverifikasi.
2. Evaluasi deny rule. Hanya exception exact path dengan rule ID dan kondisi yang tercantum dapat melewati rule tertentu; rule deny lain tetap berlaku.
3. Setelah lolos deny, file harus ada persis pada allowlist required atau conditional yang terpenuhi. Unknown file tetap ditolak; tidak copy recursive folder berdasarkan nama umum.
4. Pastikan 78 required paths tetap lengkap dan optional hosting control ditangani. Uji dependency include/view/asset serta path data yang diprovision terpisah.
5. Inspect isi arsip/bundle nyata, scan secret snapshot final, dan lakukan runtime/regression bundle test sebelum memberi approval. Jangan menyatakan bundle bersih hanya berdasarkan dokumen ini.

Pengecualian ini berlaku pada source bundle, bukan lingkungan hosting lengkap. Database, approved media/file, storage writable privat, environment production, session/log store tetap diperlukan dan diprovision terpisah; denylist tidak menginstruksikan penghapusan data itu.

## Validasi dan Batasan

JSON parse, konsistensi required/conditional allowlist vs deny, dependency path literal/view, dan canary path offline diperiksa tanpa menjalankan aplikasi/DB. Required/conditional source tidak boleh ditolak setelah exception yang sah; file sensitif/artefak canary wajib ditolak. git diff --check juga diperiksa.

Hasil pemeriksaan offline: 79 path source (78 required dan 1 conditional) tersedia dan tidak salah ditolak; 37 path dummy forbidden/unknown/traversal semuanya ditolak; 91 relasi include literal dan 22 view dinamis tidak kehilangan dependency. Kondisi .user.ini dianggap terpenuhi hanya untuk pemeriksaan konsistensi policy, bukan bukti hosting menerapkannya. Kedua JSON valid dan git diff --check lulus. Git status akhir: 234 file individual, 72 modified, 162 untracked, 0 staged.

Item ini hanya menambahkan dua file metadata/dokumentasi; tidak mengubah PHP/.htaccess/config, tidak membuat bundle, tidak menghapus pekerjaan pengguna atau data PPDB, dan tidak menjalankan lint/regression PHP baru. Policy bukan jaminan keadaan server/archive yang belum diperiksa.

Status: denylist kandidat selesai; packaging dan final approval belum dilakukan. Tidak menyatakan website siap production.
