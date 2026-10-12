# Tahap 6 Item 2 - Audit .gitignore

Tanggal: 9 Oktober 2026. Perubahan hanya .gitignore dan laporan ini. Tidak mengakses database, menghapus file, mengubah Git index, commit, push atau deploy.

## Temuan Awal

- Environment hanya dilindungi oleh .env dan .env.local; .env.production serta variasi lain belum dicakup.
- State testing utama sudah di-ignore, tetapi log/dump di luar direktori tersebut, cache/temporary test lain dan IDE metadata belum dicakup konsisten.
- Upload baru hanya di-ignore pada news/gallery/profile; staf, prestasi dan ekstrakurikuler belum dicakup.
- config/database.php adalah source resolver environment yang tracked. Nilai default lokal tidak memuat password nyata; file harus dipertahankan untuk runtime, bukan di-ignore sebagai seluruh konfigurasi database.
- .env.example dan .env.production.example berisi default/placeholder saat diperiksa; keduanya dipertahankan sebagai template. Ini bukan alasan mengisi template dengan credential nyata.

## Perbaikan

| Area | Aturan |
| --- | --- |
| Environment | .env* di-ignore, dua template existing dikecualikan; .envrc/.direnv privat |
| Local configuration | config/*.local.php, config/*.local.json dan config/local.* di-ignore; source config existing tetap terlihat |
| Credential/key | Nama manifest credentials/connection/bootstrap-admin/auth JSON dan private-key/container umum di-ignore |
| Dump | SQL serta variasi compressed SQL di-ignore; database/schema.sql, SQL migration dan intentional SQL fixture dikecualikan |
| Backup/log | Direktori backup/dump/log, nama backup-*.zip/gz, .bak/.backup/.log di-ignore |
| Testing/state | storage testing/session/backup/media-cleanup/upload/log/cache/temp dan output test/browser/coverage di-ignore |
| Temporary | Direktori tmp/temp, .tmp/.temp, swap dan editor backup di-ignore |
| Upload | Seluruh file baru public/uploads di-ignore; .htaccess/.gitkeep tetap terlihat |
| IDE/OS | .idea, .vscode, *.code-workspace serta metadata OS di-ignore |

Pola dependency/build existing .next/node_modules/vendor dipertahankan. Tidak menambahkan pola yang menutupi seluruh config, includes, admin, pages, templates, database atau tests. Test source/probe dan dokumentasi boleh versioned, tetapi harus dikecualikan dari web release sesuai manifest item 1. Ignore Git tidak menentukan kelengkapan deployment vendor jika dependency runtime nantinya diperlukan.

Pola dump SQL sengaja mempunyai exception untuk source SQL yang dirancang. File SQL source baru di lokasi lain harus direview dan diberi exception spesifik jika perlu; jangan menyimpan dump bercredential sebagai migration/fixture agar lolos ignore. Arsip ZIP arbitrer tidak diblokir global agar tidak menyembunyikan asset/source yang sah; simpan backup pada lokasi/nama yang di-ignore dan review setiap arsip tambahan.

## Validasi

git check-ignore --no-index menguji path sintetis tanpa membuat file atau credential:

- 47 path sensitif/artefak: di-ignore sesuai harapan.
- 29 path source/template/control: tidak di-ignore sesuai harapan.
- Total 76 pemeriksaan, 0 kegagalan akhir.
- 129 file PHP source tetap terlihat pada inventory Git tracked/untracked yang tidak di-ignore.
- Root/upload/storage .htaccess, .user.ini, template environment, installer schema, maintenance, test source dan source runtime tetap terlihat.
- git diff --check lulus; perubahan LF/CRLF warning bila ditampilkan Git bukan error whitespace.

Percobaan test awal memakai pipeline PowerShell ke git --stdin dan menghasilkan pembacaan status/path yang tidak cocok dengan panggilan langsung. Pemeriksaan verbose langsung membuktikan rule cocok; test diulang dengan setiap path sebagai argument literal Git. Set path dan expected result tidak diubah, hasil 76/76 lulus. Tidak membuat test lulus dengan melonggarkan ignore policy.

PHP lint/runtime regression tidak diulang karena tidak ada perubahan PHP. Hasil 129 lint sebelumnya tidak diklaim sebagai run baru item ini.

## File Tracked dan Batas Perlindungan

Inventory tracked kandidat sensitif menunjukkan dua template environment dan database/schema.sql sebagai pengecualian yang disengaja. Tidak ditemukan file .env aktual, credential manifest, private key, dump operasional, log, session atau IDE tracked dalam kategori/path yang diperiksa. Pemeriksaan ini bukan pemindaian seluruh isi/history untuk semua kemungkinan secret.

Lima file media existing sudah tracked dan kini cocok dengan pola ignore:

1. public/uploads/achievements/achievements-20260501095347-7f51b40a4b5a22d7.webp.
2. public/uploads/achievements/thumbs/achievements-20260501095347-7f51b40a4b5a22d7.webp.
3. public/uploads/extracurriculars/extracurriculars-20260430032852-21b711a8e00cd097.webp.
4. public/uploads/extracurriculars/extracurriculars-20260430034048-2cd34b8d8d0def3d.webp.
5. public/uploads/staff/staff-20260430080936-c0001722902430fd.webp.

File tersebut tetap tracked dan dapat masuk commit; tidak dibuka, dihapus atau dikeluarkan dari index. Review pemilik diperlukan untuk menentukan apakah media itu asset baseline yang sengaja versioned atau data persisten yang nantinya dipisahkan. Aturan ignore hanya mencegah upload baru terambil otomatis; tidak melakukan cleanup data/file nyata.

.gitignore tidak melindungi secret yang ditulis ke file tracked seperti config/database.php atau template environment; tidak menghilangkan history dan dapat dilewati git add -f. Sebelum commit kelak, review staged diff secara privat dan lakukan secret scan atas source/history yang relevan. Jika secret pernah terpublikasi, untrack saja tidak cukup: perlu rotasi/revocation dan keputusan penanganan history terpisah. Tidak menjalankan operasi tersebut pada item ini.

## Kesimpulan

Audit/penguatan .gitignore selesai dan validasi pola lulus untuk cakupan tersedia. Source penting tidak tertutup. Tidak dapat menjamin nol risiko credential hanya dari filename ignore; tracked content, exception SQL/template, media tracked dan penggunaan force-add tetap perlu review.

Manifest deployment item 1 tetap berlaku: tests/laporan yang boleh versioned bukan berarti boleh di-upload publik. Hosting readiness belum dinyatakan lulus. Tidak commit, push atau deploy.
