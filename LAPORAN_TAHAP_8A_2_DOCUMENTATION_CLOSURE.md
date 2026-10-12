# Tahap 8A.2 Item 4 - Documentation Review Closure

Tanggal: 2026-10-10. Review source/dokumentasi saja; tidak memakai database atau menjalankan deployment.

## Keputusan

| File | Keputusan review | Status setelah update | Release |
| --- | --- | --- | --- |
| DEPLOYMENT.md | UPDATE THEN COMMIT | COMMIT direkomendasikan, belum di-stage | EXCLUDED |
| LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md | UPDATE THEN COMMIT | COMMIT direkomendasikan, belum di-stage | EXCLUDED |

## Temuan dan Perbaikan

- DEPLOYMENT.md sebelumnya memuat path absolut executable lokal, klaim seed schema yang mengaktifkan/promosi akun, batas gambar 2 MB, dependency mysqli, dan daftar writable yang mencampur arsip PPDB dengan upload aktif. Backup final juga diasumsikan tersedia di folder aplikasi.
- Panduan diganti menjadi persiapan release: allowlist/denylist, konfigurasi eksplisit tanpa fallback, kebijakan provider loopback, initializer tanpa password bawaan, schema offline bukan migration, maintenance testing-only, dan larangan menjalankan test legacy.
- Batas gambar diselaraskan dengan source menjadi 20 MiB dan PDF 5 MiB; gap POST multi-file tetap dicatat. Storage PDF akreditasi, journal/lock, ZIP sementara, dan arsip PPDB dijelaskan terpisah; tidak mengubah limit atau permission runtime.
- Backup aktual dan restore dibedakan dari installer/schema entry opsional; snapshot/retensi/enkripsi/kapasitas tetap keputusan operasional, bukan sistem baru.
- Laporan singkat sebelumnya menyebut pendaftaran online, upload/verifikasi/export pendaftar, nama database development, dan klaim resmi yang dapat disalahartikan sebagai sudah terbit.
- Laporan sekarang menggambarkan PPDB pasif dan role informasi, mempertahankan historical data, menghapus detail database environment, dan membatasi klaim hasil testing serta status belum deploy.

## Keamanan Isi

- Review awal tidak menemukan actual secret atau password literal pada kedua dokumen; istilah credential/password adalah instruksi pengelolaan, bukan nilai credential.
- Path lokal absolut ditemukan pada panduan lama dan sudah dihilangkan. Path yang dipertahankan adalah path repository/runtime relatif untuk dependency yang relevan.
- Tidak memuat identitas pendaftar, kontak pribadi, akun nyata, credential/hash, host database nyata, DSN terisi, atau konfigurasi privat. Nama sekolah adalah identitas publik project.
- Historical credential tetap ROTATION REQUIRED BEFORE PRODUCTION. Tidak memverifikasi account production, menampilkan credential, atau rewrite history.
- Dokumen aman sebagai kandidat repository berdasarkan review isi saat ini, tetapi tidak ikut deployment bundle. Ini bukan persetujuan website siap production.

## Dasar Verifikasi

Dicocokkan dengan config/env.php, config/database.php, database/schema.sql, database/initialize_admin.php, database/maintenance_legacy_schema.php, includes/functions.php, admin/db_backup.php, RELEASE_ALLOWLIST.json, RELEASE_ALLOWLIST.md, HOSTING_REQUIREMENTS_CHECKLIST.md, dan closure review sebelumnya.

Targeted secret scanner untuk kedua dokumen dan laporan closure menghasilkan 0 finding pada working tree. Dua flag DEPLOYMENT.md berasal dari index lama dan history, berkategori placeholder/synthetic/local database value, bukan actual secret operasional yang terkonfirmasi. Index lama bukan berarti ada perubahan staged; review ini tidak mengubah index/history. Nilai tidak ditampilkan. Scan path absolut/key/credential URL pada kedua dokumen lulus.

Inventaris dan manifest cocok dengan seluruh dirty path: 0 missing, 0 extra, 0 duplicate. Git diff --check lulus; conflict marker 0, staged 0, unmerged 0. Tidak menjalankan regression runtime baru karena hanya dokumentasi/inventaris berubah, dan tidak mengklaim hasil test lama sebagai eksekusi baru. Runtime/release source tidak berubah, sehingga tidak rebuild release.

## Inventaris dan Gate

- Snapshot setelah closure: 186 dirty, terdiri dari 72 modified dan 114 untracked; rekomendasi 184 COMMIT, 2 DO NOT COMMIT, 0 MANUAL REVIEW. Manifest memakai daftar file eksplisit.
- DO NOT COMMIT: database/seed_final_content.php dan tests/admin_form_smoke_test.php. Keduanya sudah tracked; pengecualian staging tidak menghapus versi HEAD. Disposisi versi tracked harus diputuskan terpisah sebelum checkpoint final.
- Tidak ada git add, commit, push, deploy, penghapusan data/file nyata, provisioning credential, atau schema migration.

## Item 5 - Dependency Consistency

Pemeriksaan lanjutan read-only pada 2026-10-10:

- Seluruh 78 path required_source_files tersedia. Sebanyak 69 path dirty direkomendasikan COMMIT; 9 path lainnya clean tracked. Tidak ada runtime path yang DO NOT COMMIT, MANUAL REVIEW, atau untracked tanpa rekomendasi commit.
- Hash seluruh 78 file identik dengan inventory release candidate school-release-candidate-20261010-150123-2bd2a735. Dengan staging eksplisit seluruh dependency COMMIT yang dibutuhkan dan mempertahankan file clean tracked, source payload dapat sama byte-for-byte dengan candidate tersebut. Belum dibuat commit atau checkout kandidat baru; ini verifikasi proyeksi manifest, bukan klaim revision Git final sudah diuji.
- Delapan dependency build/validation/template/conditional config dan enam dependency repository support diperiksa sebagai COMMIT. Maintenance tetap bergantung pada tests/stage3_database_preflight.php yang juga COMMIT; bukan dependency public release.
- Config app/database/env/site_data masuk runtime. Tidak perlu COMMIT TEMPLATE ONLY sebagai pengganti karena resolver/config source aman sudah disetujui; .env.production.example tersedia untuk struktur field, bukan credential siap pakai. Environment privat tidak boleh dicopy dari workspace ke commit/bundle.
- Dependency checker dijalankan ulang terhadap ZIP existing: 91 include literal, 2 include dinamis dengan 22 target view, 46 referensi asset; unresolved include 0, missing include/view/asset 0. Archive tidak berubah.
- Release PHP lint dijalankan ulang: 71 PHP, 0 error. Tidak mengeksekusi aplikasi, database, backup/restore, atau smoke runtime baru. Kesetaraan source terhadap candidate yang sebelumnya diuji tidak menggantikan regression revision final/stack hosting.
- Kedua DO NOT COMMIT (seed_final_content.php dan admin_form_smoke_test.php) bukan dependency runtime atau source builder. schema.sql hanya pembacaan opsional pada backup; restore tetap memakai dump aktual. Exclude installer tidak menyebabkan missing require/include.
- Byte identik pada payload bukan jaminan ZIP hasil rebuild memiliki hash sama karena metadata archive/build dapat berubah. Perbandingan yang relevan adalah path dan isi file runtime.
- External dependency tetap diperlukan: konfigurasi eksplisit, database/schema kompatibel, extension PHP/server rules, media approved, serta private writable directories. Konten URL/media dari database dan layanan eksternal tidak disertifikasi oleh static source scan.
- Target dependency source/release LULUS dalam cakupan static/hash/lint. Gate tracked legacy disposition, rotasi credential historis, review staged content, dan pengujian revision final tetap berlaku sebelum checkpoint/deployment.

## Item 6 - Config Reproducibility

- Review README menemukan setup lama masih menyarankan root/password kosong, upload seluruh repository, production localhost, dan seed legacy. Bagian setup/config diganti panduan setelah clone, variabel wajib, dependency/provisioning offline, larangan database sekolah, dan rujukan DEPLOYMENT.md current. Instruksi seed dan batas gambar usang di bagian berikutnya juga dikoreksi.
- .env.example kini hanya berisi mode local, nama tampilan existing, port/charset contoh dan field URL/database kosong. Tidak ada username/password default; template belum valid sampai developer mengisi konfigurasi sendiri. .env.production.example tetap field kosong untuk production.
- APP_ENV, APP_URL, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD dan DB_CHARSET didokumentasikan dengan aturan validator. APP_NAME opsional sebagai nama tampilan, bukan fallback credential.
- README menjelaskan prioritas environment server terhadap file, nilai kosong yang tetap mengalahkan file, cache proses, dan keterbatasan parser .env sederhana. Developer tidak perlu mengetahui credential nyata milik orang lain, tetapi tetap harus memprovision akun/secret lokal sendiri secara privat.
- Kompatibilitas local yang mengizinkan password kosong secara eksplisit tetap existing; tidak diubah menjadi default account dan tidak disarankan sebagai provisioning. Production tetap fail-closed dan tidak menerima konfigurasi template kosong.
- .gitignore sudah mengabaikan .env* dengan exception exact .env.example/.env.production.example. Template development kini direkomendasikan COMMIT di manifest dan tetap EXCLUDED dari release. Tidak ada perubahan runtime/config resolver, credential nyata, atau bundle source.
- Snapshot terbaru: 187 dirty = 73 modified + 114 untracked; 185 COMMIT, 2 DO NOT COMMIT, 0 MANUAL REVIEW. Snapshot item terdahulu tetap sebagai catatan historis.
- Validasi ulang stage7_configuration_test.php: 1012 checks / 56 cases lulus, 0 koneksi database jaringan. Kedua template kosong diuji terhadap resolver dan ditolak dengan pesan generik tanpa koneksi DB.
- Inventaris/manifest cocok tanpa missing/extra/duplicate. .env dan .env.local ignored, kedua example tidak ignored. git diff --check lulus, conflict marker 0, staged 0, unmerged 0. Tidak ada test diubah untuk memperoleh hasil lulus.

## Item 8-9 - Final Secret Scan dan Target Commit Set

Scanner metadata-only dijalankan ulang terhadap working tree, index, dan history; 14 file review difilter berdasarkan origin working-tree. Satu finding adalah literal password sintetis pada tests/admin_form_smoke_test.php. Review caller membuktikan literal dipakai untuk akun test pada save_admin_user, bukan credential default installer/runtime; nilainya tidak dicatat. File tidak dijalankan dan tetap DO NOT COMMIT. Tidak mengubah test demi membuat scanner terlihat tanpa finding.

Hasil konteks: 0 actual secret operasional dan 0 predictable default credential runtime/installer teridentifikasi pada 14 file. Tetap ada satu literal test credential; temuan index/history lama tidak dianggap hilang dan rotasi historical credential tetap diwajibkan sebelum production. Scan berbasis pola dan review bukan jaminan tidak ada secret di seluruh kemungkinan sumber.

STAGING_MANIFEST_FINAL.md dibuat dengan FILE, CATEGORY, DECISION, COMMIT GROUP dan REASON untuk seluruh 188 dirty kandidat, termasuk manifest itu sendiri. Keputusan: 186 COMMIT, 2 DO NOT COMMIT, 0 MANUAL REVIEW; lima group mengikuti manifest Tahap 8A. Sembilan clean tracked runtime dependency dicatat terpisah, bukan calon diff yang perlu staging ulang. State privat/ignored tidak dimasukkan daftar kandidat dan tetap dilarang force-add.

Tidak melakukan staging, commit, push, deploy, rewrite history, mutation database, atau rebuild release. Disposisi dua legacy tracked masih gate checkpoint; target manifest lengkap tidak berarti repository final sudah disetujui untuk commit.

## Item 10 - Simulated Committed Tree

Simulasi nyata dibuat dari git archive HEAD, kemudian hanya 186 file berkeputusan COMMIT ditimpa dengan kandidat working-tree. Tidak memakai git add/commit/worktree atau mengubah index. File tracked DO NOT COMMIT tetap versi HEAD; untracked yang tidak disetujui dan ignored local state tidak ikut. Tree hasil berisi 206 file repository, tidak memiliki .git atau credential environment terisi. Inventory origin/hash disimpan privat.

Builder, dependency checker dan detector dari tree simulasi dijalankan, bukan versi source working tree. Harness smoke juga berasal dari tree tersebut, disalin byte-identik ke runner terpisah agar provisioning privat tidak mencemari repository murni. Runner memakai salinan connection/provisioning manifest testing privat dan junction datadir yang diverifikasi oleh preflight; tidak menyalin database ke release atau mengganti helper aplikasi. Tree repository murni tetap hash-identik sebelum/sesudah eksekusi.

### Hasil

- Source tree: 140 PHP lint, 0 error. Release dibangun dari tree: 78 runtime files, 71 PHP lint, 0 error; exact allowlist/denylist/integrity lulus, environment files 0, persistent data tidak ikut.
- Dependency check dari tree: 0 missing include/view/asset, 0 unresolved include; archive tidak berubah.
- Secret scan tree: 33 metadata flags, bukan 33 actual secrets. Review konteks meliputi template kosong, fixture/model sintetis, marker/detector regex dan label permission. Literal pada legacy test versi HEAD tetap ada; DO NOT COMMIT tidak menghapusnya. Tidak ada actual secret operasional teridentifikasi atau default credential runtime yang ditemukan dalam review konteks ini.
- Release scanner menghasilkan REVIEW_REQUIRED karena satu literal-shaped match pada includes/auth.php: label permission change_password, bukan password. Enam referensi loopback/config merupakan validator atau diagnostic/manual local handling, bukan credential fallback; vocabulary pada backup adalah komentar opsional schema. Blocking content 0. Kesimpulan automated scanner dipertahankan, tidak diubah agar terlihat PASS; context review menutup temuan release sebagai non-secret.
- Apache release smoke: 739/739 lulus. Mencakup public/CRUD/role/CSRF/upload/download/backup authorization/login/logout/session replay/PPDB tombstone, tanpa menjalankan pendaftaran PPDB.
- Cleanup verified: seluruh 16 tabel aplikasi testing kosong, marker tetap ada; session/cookie/dummy upload/environment test dibersihkan harness, copied connection manifests dihapus, Apache berhenti. Instance testing asli tidak dihapus dan data production tidak disentuh.

### Kegagalan dan Bukti Retest

Percobaan pertama selesai pada 614 checks dengan FAIL pada authorized full backup ZIP, bukan dianggap lulus. Temporary ZIP path mencapai 263 karakter. Probe dummy tanpa DB membuktikan ZipArchive::open mengembalikan error 5 pada path panjang, sedangkan path 91 karakter berhasil create/add/close. Retest memakai runner path pendek; source aplikasi dan assertion tidak berubah, lalu 739 checks lulus. Run gagal dan run sukses dipertahankan sebagai bukti privat.

Candidate terbaru memiliki enam perbedaan hash dibanding candidate lama: admin/index.php, pages/agenda.php, pages/akademik.php, pages/maintenance.php, pages/robots.php, storage/.htaccess. Keenamnya clean tracked dari git archive memakai LF, sedangkan working-tree candidate lama memakai CRLF. Isi identik setelah normalisasi newline; tidak ada perubahan fungsional yang ditemukan dan smoke membuktikan perilaku. Jangan mengklaim byte-for-byte ZIP/payload identik dengan candidate lama.

Log privat juga mencatat warning image_path pada fallback daftar berita kosong; tidak tampil pada respons dan smoke tetap lulus. Ini dicatat sebagai risiko existing untuk tindak lanjut terarah, tidak mengubah runtime dalam simulasi ini.

Evidence privat: storage/testing/committed-tree-20261010-222051-4d506f36/ dengan repository-inventory.json, summary.json, dependency-result.json, repository-secret-findings.json, bundle-secret-scan.json dan smoke-result.json. Bundle: releases/school-release-candidate-20261010-222101-a1162dad/website-source.zip di bawah evidence tersebut. Evidence run gagal tersimpan pada storage/testing/committed-tree-20261010-221928-d62705d5/. Semua artifact/script orkestrasi lokal tetap ignored, bukan kandidat commit/release.

Kesimpulan item 10: simulated committed tree dapat membangun release yang lulus lint/dependency/contextual secret review dan 739 smoke checks pada environment testing terisolasi. Dokumentasi hasil ditambahkan setelah snapshot diuji; perubahan tersebut hanya laporan/manifest, tidak mengubah runtime payload. Disposisi tracked legacy, historical credential rotation, final staged review dan hosting verification tetap gate terpisah. Belum commit, push, deploy, atau siap production.

## Item 11 - Regression Simulated Committed Tree

Regression dijalankan dari runner byte-identik snapshot item 10, bukan runtime source working tree. Sebelum dan sesudah test, seluruh 206 path repository dibandingkan dengan inventory hash snapshot. Environment/config testing diprovision privat terpisah; semua operasi database memakai preflight actual instance, grants, datadir dan marker pada instance terisolasi yang sama. Tidak menjalankan seed atau test admin_form_smoke_test legacy, tidak mengaktifkan pendaftaran PPDB, dan tidak mengakses database sekolah.

| Cakupan | Hasil aktual |
| --- | --- |
| Authentication model / login-logout HTTP | 53 / 47 lulus |
| Role-permission model / direct POST runtime | 437 / 387 lulus |
| Session / CSRF admin / CSRF seluruh aktif | 81 / 792 / 872 lulus |
| Input model / strict ID model / XSS model | 784 / 1133 / 79 lulus |
| Upload model / file access / delete feedback / error model | 38 / 11 / 24 / 58 lulus |
| Legacy file reference / PPDB dead-code | 9 / 34 lulus |
| CRUD runtime / upload / download | 414 / 531 / 68 lulus |
| Input-XSS runtime / ID / field validation | 314 / 614 / 131 lulus |
| DB-file consistency / pengumuman / error HTTP | 210 / 25 / 260 lulus |
| PPDB informasi/tombstone/browser setelah retest | 2071/2071 lulus |
| Production fail-closed configuration | 1012 checks, 56 cases, zero network database connections |
| Schema model / actual read-only runtime | 70 / 5 lulus |
| Account transaction/rollback/concurrency dengan --run | 72 lulus |
| Database failure handling | 397 lulus |

### Kegagalan Setup dan Retest

- Orkestrator awal stage4_non_ppdb_regression berhenti pada PPDB browser: 2070/2071 lulus, satu gagal karena private playwright-core toolbox tidak diprovision. Semua suite sebelumnya exit 0. result.json orkestrator tetap passed=false; tidak ditulis ulang menjadi true.
- Toolbox existing versi 1.60.0 diprovision sebagai dependency testing privat di runner, tidak ke tree murni/release. Retest berikutnya tetap 2070/2071 karena Chrome spawn EPERM dalam sandbox, bukan assertion aplikasi. Error MODULE_NOT_FOUND dan EPERM terverifikasi dari log privat.
- Retest berikutnya dengan izin peluncuran headless Chrome lulus 2071/2071, memakai source/assertion sama dan guard loopback/DB yang sama. Bukti setiap kegagalan dipertahankan; provisioning dan izin launch diperbaiki, tidak melemahkan test.
- Pemanggilan account transaction awal tanpa --run hanya menampilkan CLI-only dan exit 0. Itu bukan test lulus. Orkestrasi lokal dikoreksi lalu --run benar-benar mengeksekusi 72 checks yang lulus. Tidak mengubah file test atau assertion agar memperoleh hasil.
- README ditambahkan catatan Node/browser/playwright-core/private toolbox dan asumsi executable Windows harness untuk developer baru. Perubahan setelah snapshot ini hanya dokumentasi, bukan runtime/test source.

### Cleanup, Equivalence, dan Batas Kesimpulan

Cleanup helper dari runner dijalankan --apply setelah memverifikasi snapshot ownership, path containment/hash, database kosong dan listener testing berhenti. Verifikasi dry-run akhir: 14 owned snapshots, 0 candidates / 0 bytes. Seluruh 16 tabel aplikasi testing kosong dan marker retained; copied connection manifests dihapus setelah setiap run. Data/file production tidak disentuh. Seluruh 206 runner source hashes tetap sama.

Tidak ditemukan perbedaan keamanan/fungsi runtime dalam cakupan di atas akibat dua diff DO NOT COMMIT; versi HEAD kedua file masih terdapat pada simulasi, tetapi tidak menjadi dependency/entrypoint runtime release. Risiko CLI legacy tetap terbuka, bukan dibuktikan aman oleh regression. Sembilan clean tracked dependency, termasuk enam beda LF/CRLF, tetap bekerja dalam snapshot aktual.

Backup authorization dan SQL/full ZIP sudah diuji nyata dari release pada item 10 (739 smoke checks). Backup/restore penuh tidak dijalankan ulang pada item 11; hasil restore terdahulu tidak diklaim sebagai run baru. Audit ini tidak menjamin hosting/production atau semua workflow yang tidak diuji.

Evidence privat: storage/testing/regression8a2-20261010-222501/ (orkestrator awal gagal), regression8a2-closure-20261010-223454/ (EPERM), regression8a2-closure-20261010-223658/ (PPDB/browser PASS), regression8a2-closure-20261010-223819/ (suite keamanan tambahan), regression8a2-final-20261010-223954/ (akun --run, cleanup dan source hash). Log/result asli runner tetap di storage/testing/r8-2344227d/storage/testing/stage3-mariadb/. Seluruh evidence dan script orkestrasi lokal ignored, bukan kandidat commit.

Kesimpulan: regression relevan LULUS sebagai hasil gabungan suite/retest yang tercatat, bukan klaim orchestrator awal lulus. Disposisi tracked legacy, credential rotation dan final staged snapshot review tetap gate checkpoint. Tidak git add/commit/push/deploy.
