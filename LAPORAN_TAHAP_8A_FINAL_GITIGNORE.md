# Tahap 8A Item 8 - Final Gitignore

Tanggal: 2026-10-10. Tanpa staging, commit, push, deployment, penghapusan, atau koneksi database.

## Perlindungan final

| Jenis | Perlindungan |
| --- | --- |
| Environment | Semua .env* diabaikan; hanya dua template example root yang telah ditinjau dikecualikan |
| Local config | Local config root/nested, dengan atau tanpa suffix extension; manifest credential/connection dan key containers |
| Backup/dump | Direktori backup/backups/dumps root/nested, dump SQL/compressed SQL, dump/backup/bak files |
| Arsip release | ZIP, tar/tar.gz/tgz/7z; folder release/release-bundles dan kandidat build root |
| Log/session/cookie | Direktori logs/sessions, log files, sess files, session/cookie artifacts |
| Temporary | tmp/temp root/nested, tmp/temp/swap files, PID dan cache temporary |
| Testing state | Private testing/storage, browser profiles/screenshots, temporary sessions, generated results/coverage |
| IDE/OS | IDE workspace/state, swap, metadata OS desktop/macOS dan recycle metadata |
| Laporan lokal | 78 path laporan eksplisit dari keputusan item 6, bukan blanket semua laporan |

Templates example hanyalah source contoh, bukan tempat menyimpan credential nyata. Directory/file ignore bukan kontrol akses HTTP, enkripsi backup, atau release manifest.

## Source yang tetap terlihat

Dua template environment root, config resolver/runtime, snippet konfigurasi aman, .user.ini, installer schema source, migration/fixture SQL yang disengaja, seluruh source tests/fixtures berguna, laporan aman, README dan kontrol .htaccess/.gitkeep upload tidak diabaikan oleh tambahan aturan ini.

Source database/schema.sql adalah installer tanpa default account, bukan dump production; keputusan MANUAL REVIEW sebelumnya tetap berlaku. Tidak membuat pengecualian arsip umum pada fixture yang berpotensi membawa dump/secret; jika fixture arsip aman diperlukan kelak, review path spesifik sebelum pengecualian.

## Validasi

Test baru tests/gitignore_policy_test.ps1 memakai git check-ignore --no-index dengan daftar argument path dummy. Tidak menciptakan file dummy dan tidak membaca isi credential/DB. 138 kasus lulus, termasuk 78 laporan lokal, semua kategori perlindungan, serta source/template yang harus tetap terlihat.

Run awal 135 kasus gagal karena PowerShell 5 mengirim CRLF ke git check-ignore --stdin; Git memperlakukan CR sebagai bagian nama file. Bukti: path ignored normal lewat argv dikenali, sedangkan input stdin menghasilkan nama dengan trailing CR. Harness diperbaiki agar mengirim path sebagai argv, tanpa mengubah expectation atau melemahkan aturan. Retest 135 lulus, lalu 3 kasus nested template/local config ditambah dan 138 lulus.

Git final: 181 dirty = 72 modified + 109 untracked, 0 staged. Penurunan berasal dari ignore 78 laporan, bukan penghapusan. Inventaris JSON kini memuat 181 file dirty dan ignored_file_decisions terpisah untuk seluruh 78 laporan lokal. Dirty classification: 167 COMMIT, 0 IGNORE, 0 DELETE LOCAL ARTIFACT, 14 MANUAL REVIEW. Tidak ada file terlewat/duplikat; git diff --check diperiksa.

## Batas tracked dan force-add

Ignore tidak berlaku surut untuk file tracked dan dapat dilewati git add -f. Lima media upload existing tracked masih ada; tidak dihapus atau di-untrack tanpa audit referensi/pemilik. Tidak menemukan environment terisi, ZIP, log, backup/dump operasional tracked pada pemeriksaan nama/path; schema source adalah pengecualian yang disengaja.

Secret historis pada Git history/index lama tidak dicabut oleh .gitignore. ROTATION REQUIRED BEFORE PRODUCTION tetap berlaku. Review staged snapshot yang sebenarnya masih diperlukan setelah staging yang kelak disetujui.

## File berubah

.gitignore; tests/gitignore_policy_test.ps1 (baru); TAHAP_8A_GIT_FILE_CLASSIFICATION.json; laporan ini (baru).

Tidak mengubah source aplikasi, schema, release bundle, isi laporan/bukti historis, atau menghapus data/file PPDB. Tidak menjalankan runtime/database test karena perubahan hanya kebijakan repository dan test read-only. Tidak menyatakan website siap production.
