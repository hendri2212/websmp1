# Tahap 7 Item 5 - Repository Cleanup Plan

Tanggal: 10 Oktober 2026. Rencana saja: tidak melakukan staging, commit, push, deployment, penghapusan file, perubahan source aplikasi, atau akses database. Tidak menyatakan website siap production.

## Cakupan dan Aturan

Snapshot sebelum rencana ini: 229 modified/untracked individual, 72 modified dan 157 untracked, 0 staged. Daftar per file dan kategori A-J tersedia pada [review item 4](LAPORAN_TAHAP_7_REVIEW_GIT_CHANGES.md). Angka 189 merupakan snapshot lama, bukan jumlah sekarang.

Ketiga kelompok di bawah saling eksklusif untuk snapshot tersebut: 218 COMMIT TO REPOSITORY, 0 DO NOT COMMIT terkonfirmasi dalam kandidat non-ignored, dan 11 REVIEW MANUALLY. Nol bukan jaminan bebas secret: artefak privat yang ignored berada di luar daftar kandidat normal, dan pemeriksaan manual masih diwajibkan. Bila ditemukan data sensitif, pindahkan keputusan file ke REVIEW MANUALLY atau DO NOT COMMIT sebelum staging.

Rencana ini sendiri termasuk COMMIT TO REPOSITORY sebagai dokumentasi offline: total setelah penambahan menjadi 230 file, dengan 219 kandidat commit dan 11 manual. Keputusan adalah rekomendasi, bukan perintah commit atau approval seluruh diff. Tidak ada file production-only aktual yang dibuat.

## COMMIT TO REPOSITORY

Semua path dalam snapshot item 4 selain 11 pengecualian REVIEW MANUALLY di bawah masuk kelompok ini, dengan syarat review diff, scan secret snapshot final, dan test yang relevan telah disetujui. Source yang berguna harus tetap dapat direproduksi, bukan dihapus karena tidak termasuk bundle.

| File/kelompok | Alasan dipertahankan | Deployment bundle |
| --- | --- | --- |
| index.php, admin/*.php, pages/*.php, templates/*.php, public/assets/css/style.css, config/site_data.php, helper runtime lainnya | Source website dan hardening existing; pengecualian manual di bawah berlaku | Hanya runtime yang masuk allowlist; PHP internal tetap dilindungi dari akses source langsung |
| Endpoint tombstone PPDB dan halaman informasi | Menolak URL/workflow pendaftaran lama dan mempertahankan informasi existing | Pertahankan compatibility tombstone; jangan mengaktifkan mutasi PPDB |
| includes/error_handling.php, input_validation.php, media_lifecycle.php, schema_requirements.php, upload_security.php | Helper baru masih untracked tetapi diperlukan oleh caller runtime | Wajib disertakan bersama caller; tidak boleh hilang dari bundle karena hanya memakai daftar tracked lama |
| config/app.php, config/database.php, config/env.php | Resolver/validasi source, bukan credential production terisi | Sertakan source yang ditinjau; environment/credential nyata diprovision terpisah secara privat |
| .gitignore, .env.production.example | Kebijakan ignore dan template tanpa credential operasional | Exclude dari public bundle; template bukan environment production yang siap pakai |
| .htaccess, .user.ini, public/uploads/.htaccess | Kontrol HTTP, error/upload dan hardening yang sudah diuji | Sertakan sesuai hosting; review konfigurasi/enforcement tetap wajib |
| tests/*.php, tests/*.cjs, tests/stage7_secret_audit.ps1 | Regression/security/provisioning testing yang berguna; kecuali smoke test manual | EXCLUDE seluruh tests dari deployment bundle |
| tests/fixtures/*, tests/stage4_legacy_file_reference_fixture.php | Fixture source sintetis dan probe reproduksi test, bukan hasil upload/session | EXCLUDE seluruhnya dari deployment bundle |
| preview_router.php | Tool server development dengan guard existing | EXCLUDE; bukan router production |
| README.md, HOSTING_REQUIREMENTS_CHECKLIST.md | Dokumentasi instalasi dan requirement; DEPLOYMENT.md ditahan manual | EXCLUDE dokumentasi dari web release |
| LAPORAN*.md, REPOSITORY_CLEANUP_PLAN.md | Bukti audit dan keputusan historis yang aman | EXCLUDE dari web release; repository/offline saja |

### Syarat Test dan Laporan

- Tests boleh berada dalam repository jika source-nya berguna dan memakai fixture sintetis. Password/token dummy untuk test tidak boleh diganti dengan credential sekolah. Manifest koneksi, cookie/session, log, database instance, snapshot/dump nyata dan output test tidak ikut hanya karena berada di bawah tests.
- Keberadaan tests di Git tidak memberi izin mengeksekusinya pada database utama. Preflight isolasi tetap diperlukan; smoke test yang belum aman ditahan pada kelompok manual.
- Laporan audit boleh dipertahankan sebagai dokumentasi setelah review isi: tidak ada password/hash sensitif, API key, token/cookie sesi, private key, DSN bercredential, dump nyata, data siswa, atau lampiran privat.
- Jika laporan memuat materi sensitif, jangan commit versi asli. Buat versi tersunting yang hanya memuat metadata/hasil; simpan bukti asli secara privat jika diperlukan. Jangan memindahkan secret ke dokumentasi lain.
- Nama file/path source dan kategori secret boleh dilaporkan tanpa nilainya. Jangan menyertakan konfigurasi internal operasional atau identitas pribadi yang tidak diperlukan.
- Pemindaian heuristik item 3 bukan pengganti pembacaan manual dan tidak membuktikan semua laporan/fixture bebas data sensitif. Laporan lama tetap diberi konteks waktu; jangan menimpa bukti kegagalan test untuk membuat status seolah lulus.

## DO NOT COMMIT

Tidak ada credential operasional tambahan terkonfirmasi dalam kandidat modified/untracked non-ignored saat ini. Kelompok ini terutama berlaku pada state dan file privat yang sudah ignored, serta file baru yang mungkin muncul sebelum commit.

| File/pola | Keputusan |
| --- | --- |
| .env terisi, .env.*, .envrc, .direnv/, .my.cnf, config/*.local.*, config/local.* | Tetap ignored/private; pengecualian hanya template source yang telah ditinjau (.env.example dan .env.production.example) |
| credentials*.json, connection.json, bootstrap-admin.json, auth.json, key/certificate container privat | Jangan commit atau force-add; jangan tampilkan nilainya dalam laporan |
| storage/testing/** | Instance DB testing, manifest privat, snapshot/log/bukti: tidak commit dan tidak masuk release |
| storage/backups/**, backup/**, backups/**, dumps/**, dump SQL/arsip backup | Privat dan terpisah dari source; jangan hapus backup historis atau data PPDB |
| storage/sessions/**, cookie/session testing, profile browser, screenshots, tests/.tmp*/** | Tetap ignored; bukan source regression |
| storage/logs/**, cache/tmp/temp, test-results, playwright-report, coverage | Artefak runtime/testing; tetap ignored |
| storage/uploads/** dan media upload baru public/uploads/** | Data persisten, bukan source; migrasi/backup terkontrol terpisah. Pertahankan .htaccess/.gitkeep yang memang source |
| storage/media-cleanup/** | State/jurnal privat; jangan commit atau menghapus jurnal aktif tanpa audit consistency |
| storage/source_docs/** dan dokumen/data pribadi nyata | Arsip privat, bukan dependensi runtime; jangan publikasi atau hapus tanpa keputusan pemilik |
| .idea/, .vscode/, metadata OS/editor, swap/temp files | State lokal; tetap ignored |

Installer schema dan fixture SQL sintetis yang sengaja dipelihara bukan dump operasional. database/schema.sql tetap REVIEW MANUALLY, bukan di-ignore secara sembarangan. Jangan mengubah ignore sehingga source/helper/fixture penting hilang.

Ignore tidak berlaku surut untuk file yang sudah tracked dan dapat dilewati force-add. Lima media existing tracked di public/uploads yang kini cocok ignore tidak dihapus atau di-untrack pada tahap ini; keputusan arsip/reference DB memerlukan review pemilik terpisah.

## REVIEW MANUALLY

Sebelas path berikut dikeluarkan dari kelompok commit sampai review khusus selesai. Setelah disetujui, sebagian layak repository tetapi tetap bukan public bundle.

| FILE | Alasan ditahan | Bukti/kriteria sebelum persetujuan | Deployment |
| --- | --- | --- | --- |
| DEPLOYMENT.md | Catatan akhir masih menyebut seed installer dapat mengaktifkan/promosi admin, tidak sesuai working tree schema terbaru | Selaraskan dokumentasi dengan penghapusan default account; review instruksi database/retention | Offline saja |
| database/seed_final_content.php | CLI seed menggunakan koneksi aplikasi; guard CLI tidak menjamin target DB aman | Review tujuan, konten dan guard target; jangan menjalankan sebagai bagian cleanup ini | Tidak public release atau seed runtime |
| tests/admin_form_smoke_test.php | Membuat akun test melalui koneksi aplikasi | Verifikasi isolasi/preflight dan cleanup; jangan eksekusi terhadap DB sekolah | Tidak deployment |
| includes/functions.php | Shared helper dengan diff besar dan dependensi security baru | Review bersama caller, escaping/validasi/upload/lifecycle dan test terkait | Runtime setelah approval |
| includes/repositories.php | Shared repository/query dengan diff besar | Review query, guard ID/permission, transaksi dan lifecycle bersama test | Runtime setelah approval |
| database/schema.sql | Penghapusan hash/default account penting; index/history lama masih berisiko | Pastikan snapshot commit memakai working tree yang bersih dari seed akun default; jangan import ulang ke DB berisi data | Installer offline saja |
| database/initialize_admin.php | Provisioning akun awal walau CLI-only dan tanpa default password | Review input privat, empty-account guard, lock/transaction dan test; bukan membuat password production sekarang | Paket maintenance privat, bukan web release |
| database/maintenance_legacy_schema.php | DDL/backfill eksplisit, testing-only | Pastikan preflight testing tidak dapat dilewati dan tidak dipakai sebagai migration production | Testing/offline saja |
| admin/production_check.php | Literal password legacy masih dipakai sebagai detektor | Pastikan hanya deteksi, bukan fallback/provisioning; verifikasi permission; pemilik mengonfirmasi rotasi akun yang pernah memakai default | Runtime terbatas setelah approval |
| config/apache_security.conf | Snippet server-context yang memerlukan penerapan hosting eksplisit | Review kompatibilitas konteks Apache/provider; jangan memasukkan sebagai .htaccess atau menjanjikan otomatis aktif | Konfigurasi operasi terpisah |
| config/php_security.ini | Snippet php.ini, bukan pengganti seluruh konfigurasi | Review cara penerapan hosting dan verifikasi exposure setting | Konfigurasi operasi terpisah |

Credential installer historis pada index/history tidak dihapus dengan cleanup working tree. Pemilik wajib merotasi akun yang pernah menggunakan default sebelum release; audit ini tidak membaca akun nyata dan tidak menulis ulang Git history. Persetujuan file source tidak membuktikan rotasi sudah dilakukan.

## Urutan Pelaksanaan Kelak

1. Selesaikan review manual dan privacy review laporan/test. Tidak ada file yang otomatis disetujui karena jumlahnya banyak atau lint lama lulus.
2. Pertahankan state privat ignored; jika cleanup artefak diperlukan, verifikasi kepemilikan, proses aktif, bukti test dan target path terlebih dahulu. Tidak menghapus pekerjaan pengguna, data/file PPDB, backup atau sesi aktif secara massal.
3. Review source runtime bersama helper baru dan test pendamping. Staging kelak hanya path eksplisit yang disetujui; tidak menggunakan git add semua pada tahap ini.
4. Periksa snapshot staged yang sebenarnya, secret scan ulang, git diff --cached --check, dan hasil test relevan. File yang baru muncul atau di-force-add harus diperiksa ulang, bukan diasumsikan aman oleh rencana ini.
5. Buat bundle melalui allowlist runtime terpisah. Wajib exclude tests, fixtures, laporan, dokumentasi, installer/seed/maintenance, .git, env terisi, dump/backup/log/session dan instance testing. Keputusan COMMIT bukan keputusan DEPLOY.
6. Jangan memasukkan data persisten/secret produksi dari workspace sebagai bagian source bundle. Provisioning privat dan migrasi media memerlukan langkah terkontrol tersendiri.

## Validasi dan Status

Rencana mencakup semua 229 kandidat pada snapshot awal melalui aturan seluruh path dikurangi 11 pengecualian eksplisit. Rencana baru menjadi kandidat dokumentasi ke-230. Tidak menjalankan PHP/regression/database test baru karena item ini hanya menambah Markdown; hasil test tahap sebelumnya tidak diklaim sebagai test baru.

git diff --check dan status Git diperiksa setelah penulisan. Tidak ada staging, penghapusan, perubahan .gitignore/source, commit, push, deployment, atau koneksi database pada item ini. Status: rencana cleanup selesai, pelaksanaan cleanup/staging belum dilakukan; release approval tetap terpisah.
