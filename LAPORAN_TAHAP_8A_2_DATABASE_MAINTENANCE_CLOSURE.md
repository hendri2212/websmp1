# Tahap 8A.2 Item 2 - Database/Maintenance Review Closure

Tanggal: 2026-10-10. Keputusan repository saja, tanpa staging/commit/push/deploy atau operasi production.

## Keputusan final per file

| File | Diperlukan repository? | Production web deployment? | CLI-only? | Keputusan |
| --- | --- | --- | --- | --- |
| database/initialize_admin.php | Ya, provisioning awal offline yang terkontrol | Tidak; dapat digunakan operator offline untuk instalasi kosong setelah persetujuan terpisah | Ya, guard sebelum koneksi | COMMIT |
| database/maintenance_legacy_schema.php | Ya, compatibility tool testing-only | Tidak; bukan migration production | Ya, guard sebelum preflight | COMMIT |
| database/schema.sql | Ya, definisi struktur installer tanpa akun default | Tidak dalam bundle; provisioning schema offline memerlukan target/plan terpisah | Tidak berlaku, file SQL statis | COMMIT |
| database/seed_final_content.php | Tidak diperlukan untuk kandidat commit saat ini | Tidak | Ya, tetapi guard CLI saja tidak menjamin keamanan target | DO NOT COMMIT |

Tiga file COMMIT tetap dikecualikan dari release allowlist. Source tidak diganti template: initializer/maintenance berisi logic generik tanpa credential, dan schema adalah installer struktur, bukan dump. Seed dipertahankan di working tree; tidak dijalankan, dihapus, di-untrack, atau di-refactor.

## Credential, data, mutasi, dan HTTP

| File | Default password/credential tertanam | Data nyata tertanam | Operasi berisiko/destruktif | Dapat dijalankan anonim melalui HTTP? |
| --- | --- | --- | --- | --- |
| initialize_admin.php | Tidak; password eksplisit dari STDIN, di-hash | Tidak; username/nama/password berasal input operator | INSERT satu superadmin pada tabel akun kosong; menolak akun existing; bukan DROP/DELETE/migration | Tidak; GET/POST ditolak 403 sebelum database |
| maintenance_legacy_schema.php | Tidak | Tidak; defaults struktur/tahun ajaran bukan record individu | ALTER ADD, CREATE TABLE dan UPDATE backfill; tidak ada DROP/TRUNCATE/DELETE, tetapi DDL bisa partial commit | Tidak; GET/POST ditolak 403 sebelum preflight |
| schema.sql | Tidak; tidak ada INSERT akun/hash default | Tidak; hanya struktur dan default field | CREATE DATABASE/TABLE IF NOT EXISTS dan USE; bukan migration/update schema existing | Tidak dieksekusi PHP; source download tetap harus diblokir oleh web server |
| seed_final_content.php | Tidak ada default password/hash; identitas session admin sintetis bukan password | Berisi klaim konten/statistik/jadwal/kontak sekolah, bukan fixture sintetis; otorisasi/provenance publikasi tidak diverifikasi | Menimpa stats/identity settings, menambah konten dan audit; tanpa guard target/konfirmasi atau transaksi seluruh job; tidak ada DROP, tetapi write bisa merusak konsistensi/data existing | Tidak; GET/POST ditolak 403 sebelum bootstrap; risiko utama eksekusi CLI |

Tidak menemukan record siswa individual tertanam pada source yang direview. Tidak membuka database atau backup sekolah untuk membuktikan isi data operasional. Klaim konten sekolah pada seed tidak disalin ke laporan ini.

## Initializer

- Guard PHP_SAPI cli berada sebelum require/database, dan membutuhkan argumen --initialize serta username/nama eksplisit.
- Password dibaca dari private STDIN pipe, tidak dari default, source, URL, environment fallback, atau argv password. Input kosong, terlalu pendek/panjang, whitespace-only dan karakter kontrol ditolak.
- Validasi username/full-name dilakukan sebelum koneksi; password_hash digunakan untuk storage. Tidak mencetak password/hash/DSN/error internal.
- Memakai advisory lock per database, InnoDB check, transaksi dan row lock sebelum memastikan admins benar-benar kosong. Semua akun existing, termasuk nonaktif, menyebabkan penolakan; tidak reset/promosi/aktivasi akun lama.
- Rollback dan release lock berada pada catch/finally; error generik. Tidak mengubah schema.
- Koneksi berasal konfigurasi aplikasi yang sudah direview, sehingga operator CLI tetap bertanggung jawab atas target yang diprovision. CLI-only bukan autentikasi operator tambahan dan bukan alasan menjalankannya tanpa otorisasi.

Keputusan COMMIT untuk utility offline, tidak menyertakan credential operator atau memasukkannya ke public web bundle.

## Maintenance legacy schema

- CLI guard sebelum include preflight; tanpa mode sah tidak ada operasi. Apply membutuhkan --apply --confirm-testing-schema.
- Memakai tests/stage3_database_preflight.php yang memverifikasi instance testing aktual, datadir, restricted grant dan marker; tidak memakai koneksi/fallback config aplikasi production.
- Plan memeriksa seluruh tabel dasar terlebih dahulu, kemudian menyusun ADD column, academic-year backfill dan CREATE audit table jika diperlukan. Tidak menyentuh tabel pendaftar PPDB atau mengaktifkan endpoint legacy.
- DDL tidak dijamin rollback; defaults column baru juga bisa mengubah interpretasi record lama. Ini alasan tool tetap testing-only dan tidak menjadi migration production.
- Pada review ini hanya --plan dijalankan pada instance testing terverifikasi: 0 operasi, tanpa mutasi. --apply tidak dijalankan.
- Tidak ada password/hash default, seed akun, DROP TABLE/COLUMN, atau penghapusan data.

Keputusan COMMIT bersama guard preflight dan config/site_data.php yang dibutuhkan; tidak dimasukkan release.

## Schema installer

- Source SQL struktur, bukan dump/seed; 16 definisi tabel IF NOT EXISTS, tanpa INSERT/default account/hash credential.
- CREATE DATABASE/USE masih memakai nama database development existing. Jangan import file ini mentah ke production atau database berisi data; provisioning target offline memerlukan rencana yang disetujui. Tidak mengubah nama/schema pada review ini.
- IF NOT EXISTS bukan mekanisme upgrade schema existing. Runtime schema check tidak mengeksekusi installer ini otomatis.
- Enum role legacy dan struktur tabel PPDB historis dipertahankan; keberadaan definisi tidak mengaktifkan workflow pendaftaran.
- File SQL tidak mempunyai PHP_SAPI guard; perlindungan adalah pengecualian release dan denial database directory/.sql pada web server. File ini tidak boleh menjadi anonymous source download.

Keputusan COMMIT sebagai source installer terdokumentasi, bukan public deployment artifact atau destructive migration.

## Seed final content: DO NOT COMMIT

Alasan spesifik:

1. Mengambil koneksi aplikasi dari bootstrap, bukan strict testing preflight. Tidak mempunyai --plan/--apply, konfirmasi target, atau empty-database guard; menjalankan CLI saja langsung memulai job.
2. save_site_sections menimpa statistik/identitas existing. seed_when_missing tidak melindungi settings overwrite dan tidak menjamin seluruh job atomik; kegagalan tengah proses dapat menyisakan sebagian perubahan.
3. Mengisi session admin_logged_in/admin_id/admin_username/admin_role secara sintetis untuk operasi/logging, bukan berasal autentikasi operator. Ini dapat membuat audit actor tidak akurat. Bukan HTTP auth bypass karena CLI guard tetap berjalan, tetapi bukan praktik yang layak dipertahankan sebagai initializer resmi.
4. Konten sekolah bukan dataset regression sintetis; belum ada verifikasi persetujuan publikasi dan relevansi tanggal/data. Tidak diperlukan oleh runtime, release, atau proses pembuatan initial superadmin.
5. Tidak ada password default, namun ketiadaan secret tidak otomatis membuat script yang menulis data aman.

Tidak melakukan refactor besar, menambahkan mekanisme seed baru, atau menjalankan job untuk membuktikan hasil. File tetap ada untuk keputusan operator terpisah. Jika kelak diperlukan, target/write guard, provenance konten dan audit actor harus ditinjau terlebih dahulu.

### Batas file yang sudah tracked

Seed sudah tracked di HEAD, dan versi HEAD juga memiliki guard CLI tetapi mengandung settings write/session sintetis. DO NOT COMMIT untuk perubahan saat ini tidak menghapus seed lama dari repository. Ignore pun tidak berlaku surut. Tidak menjalankan git rm/revert/delete atau rewrite history.

tests/maintenance_entrypoint_test.php masih menguji path seed sebagai CLI entry point; path yang sudah tracked tetap ada pada clone. Jika operator memilih menghapus/menonaktifkan tracked seed kelak, perubahan source/test/documentation harus dibuat terarah dan diuji lagi. Jangan menyatakan checkpoint staged sudah setara working tree sebelum disposisi tracked seed diputuskan dan revision final diuji.

## Validasi saat review

- Preflight database testing: instance, datadir, grants dan marker terverifikasi; tidak mengakses database sekolah.
- tests/maintenance_entrypoint_test.php: 245 pemeriksaan pada 40 entry point lulus. GET/POST terhadap salinan byte script ditolak 403 sebelum canary bootstrap; tidak ada database/migration dijalankan oleh HTTP test.
- tests/stage7_initial_admin_test.php --run: 30 pemeriksaan lulus pada database testing terisolasi. Password invalid ditolak; akun valid tersimpan sebagai hash; akun existing tidak diubah/diaktifkan; dua initializer concurrent menghasilkan satu akun; lock dilepas. Record sintetis dan direktori fixture dibersihkan oleh harness.
- tests/stage6_apache_security_test.php: 4216 actual Apache security/routing checks lulus, tanpa koneksi DB. Termasuk database/maintenance/seed/schema path dan .sql source denial. Fixture source SQL dummy, bukan import schema.
- Maintenance --plan testing: 0 operasi, PLAN ONLY. Tidak menjalankan apply/seed atau import installer.
- Secret scanner metadata-only pada keempat file: 0 findings working-tree; pemeriksaan konteks manual tetap menjadi dasar keputusan.
- PHP lint tiga script: 0 error; schema.sql bukan PHP.
- Dry-run cleanup akhir memverifikasi seluruh tabel aplikasi testing kosong dan marker retained; tidak menghapus data nyata.
- Bundle/runtime source tidak berubah; keempat path tetap di luar allowlist. Tidak rebuild atau deploy.

## Inventaris, manifest, dan status akhir

TAHAP_8A_GIT_FILE_CLASSIFICATION.json dan STAGING_MANIFEST_TAHAP_8A.md diperbarui: tiga COMMIT, satu DO NOT COMMIT, ditambah laporan terkurasi ini sebagai dokumentasi yang dikecualikan release. Total 184 dirty = 72 modified + 112 untracked, keputusan 180 COMMIT / 1 DO NOT COMMIT / 3 MANUAL REVIEW.

Tiga manual review tersisa: tests/admin_form_smoke_test.php, DEPLOYMENT.md, LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md. Disposisi tracked seed dan rotasi credential historis tetap gate terpisah. Keputusan repository bukan pernyataan website siap production.

Hanya inventaris, manifest, dan laporan baru diubah. Tidak mengubah empat source database, schema produksi/testing, runtime aplikasi, PPDB, assertion test, credential, release bundle atau Git index. Tidak staging, commit, push, deploy, atau menghapus data/file pengguna.

## Item 7 - Static Database Script Safety Audit

Audit lanjutan tanggal 2026-10-10. Empat file dalam database/ diperiksa penuh atau melalui SQL/call-site tracing. Tidak mengeksekusi script, SQL, maintenance plan/apply, initializer, seed, restore, maupun database test pada item ini. Hasil runtime pada bagian sebelumnya adalah bukti historis, bukan run baru.

| File/component | Operasi dan klasifikasi | Guard / keputusan |
| --- | --- | --- |
| database/schema.sql:1-219 | CREATE DATABASE IF NOT EXISTS, USE target development, 16 CREATE TABLE IF NOT EXISTS beserta indeks/constraint. Instalasi DDL; tidak ada DROP/TRUNCATE/ALTER, INSERT/UPDATE/DELETE akun. | Import offline eksplisit, bukan script PHP sehingga tidak memiliki guard PHP_SAPI. HTTP database/ dan .sql ditolak oleh aturan Apache; tidak masuk release. COMMIT untuk provisioning database kosong yang ditinjau, bukan migration existing atau import mentah production. |
| database/initialize_admin.php:4-41 | INSERT satu akun superadmin dengan password_hash; SELECT metadata, GET_LOCK dan FOR UPDATE adalah baca/locking, bukan UPDATE account. Transaksi dengan rollback pada kegagalan. | CLI-only sebelum koneksi, tepat --initialize beserta username/nama, STDIN privat, input tervalidasi, akun existing ditolak, engine InnoDB dan advisory lock wajib. Tidak ada default password, UPDATE/DELETE akun, atau DDL. COMMIT, maintenance privat saja. Target mengikuti config eksplisit; operator tetap wajib memverifikasi target, ini bukan testing-only script. |
| database/maintenance_legacy_schema.php:6-79 | ALTER TABLE ADD kolom yang belum ada; CREATE TABLE admin_activity_logs beserta indeks bila belum ada; UPDATE academic_year kosong pada agenda/dokumen/prestasi. Maintenance DDL + backfill data, bukan account UPDATE. | CLI-only; --plan read-only, write hanya --apply --confirm-testing-schema; guard actual testing instance/grants/datadir/marker sebelum SQL. COMMIT repository, exclude release; tidak untuk production. DDL dapat implicit commit/selesai sebagian. |
| database/seed_final_content.php:6-297 | INSERT konten via save_*; UPSERT site_settings via save_site_sections dapat mengganti settings existing. INSERT log aktivitas secara tidak langsung. Tidak ada INSERT/UPDATE akun atau SQL DROP/TRUNCATE/ALTER/DELETE langsung di script. | CLI-only tetapi tanpa flag persetujuan write atau preflight testing; memakai config aplikasi dan session admin buatan. Seluruh run tidak atomic walaupun beberapa helper memakai transaksi. DO NOT COMMIT / jangan jalankan; file tracked tetap menunggu disposisi terpisah. |
| admin/db_backup.php:25-69 | SHOW CREATE TABLE/SELECT dibaca; DROP TABLE IF EXISTS, CREATE dan INSERT ditulis sebagai teks dump. Saat restore, dump berpotensi mengganti tabel/data target. | Backup bukan execution DDL pada database sumber. Permission db_backup, POST dan CSRF; restore manual memerlukan review target/statement dan backup. Tidak boleh auto-import pada request normal. |
| includes/repositories.php:255-397 dan includes/auth.php:208-238 | INSERT/UPDATE/DELETE akun adalah DML fitur admin; UPDATE password merupakan operasi ganti password eksplisit. | Bukan maintenance atau seed runtime. Caller admin/admins.php dan admin/change_password.php memeriksa permission, POST, CSRF; prepared statements, hashing, serta transaksi/row-lock/last-superadmin policy pada pengelolaan akun. Tidak mengubah schema. |

### Interpretasi Keyword

- DROP/TRUNCATE tidak ditemukan sebagai operasi pada keempat script database current. DROP dalam dump backup adalah destructive restore instruction dan tetap harus diperlakukan hati-hati, bukan dihapus hanya untuk membuat scan terlihat bersih.
- FOR UPDATE adalah row locking; ON UPDATE CURRENT_TIMESTAMP merupakan deklarasi schema. Keduanya bukan bukti script sedang mengubah role/status akun. CREATE INDEX disertakan sebagai deklarasi indeks pada CREATE TABLE, bukan migration runtime tersembunyi.
- ALTER ADD is_active DEFAULT 1 atau role DEFAULT editor hanya menambah kolom yang belum ada. Pada schema lama tanpa kolom, nilai default dapat memengaruhi seluruh row existing sehingga membutuhkan review sebelum apply; ini berbeda dari mengaktifkan kembali akun yang sudah eksplisit nonaktif. Kolom existing tidak diubah oleh maintenance ini.
- Seed memanggil ensure_academic_year_columns/ensure_news_publication_column; helper current hanya require_schema_columns/SHOW COLUMNS. Pemanggilan itu bukan ALTER tersembunyi, tetapi tidak membuat seed aman untuk target yang belum diverifikasi.
- INSERT log aktivitas saat login/logout/aksi admin merupakan audit data yang disengaja, bukan pembuatan akun default. Tidak semua write pada request normal salah; yang dilarang adalah schema migration/seed maintenance otomatis.

### Runtime Reachability dan Status

Pencarian caller source admin/includes/config/pages/templates/index tidak menemukan include/require initializer, maintenance, atau seed. Satu referensi schema.sql pada backup adalah pembacaan file opsional untuk arsip, bukan eksekusi SQL. Compatibility helper role/log/news/academic read-only; tidak ada DDL executable pada request normal yang ditemukan dalam tracing ini.

Script approved memiliki action eksplisit dan guard yang sesuai; schema SQL bergantung pada provisioning operator dan server HTTP denial. Seed legacy belum memenuhi target explicit write/isolation dan tidak dinyatakan aman: exclusion staging tidak menghapus versi tracked HEAD. Disposisi tersebut tetap gate sebelum checkpoint. Audit tidak mengubah aplikasi/schema atau menghapus legacy.

Inventaris terbaru mengikuti item 6: 187 dirty (73 modified + 114 untracked), 185 COMMIT, 2 DO NOT COMMIT, 0 MANUAL REVIEW. Angka/item 2 di atas dipertahankan sebagai snapshot historis.
