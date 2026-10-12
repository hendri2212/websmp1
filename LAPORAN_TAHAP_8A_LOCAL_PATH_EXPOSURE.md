# Tahap 8A Item 7 - Local Path Exposure

Tanggal: 2026-10-10. Cakupan tracked + untracked non-ignored kandidat repository, dengan pembedaan runtime, dokumentasi, dan detector test. Tidak membuka database, credential lokal, atau backup privat.

## Hasil awal dan keputusan

- Scan awal: 275 file kandidat, 9 baris actual path pada 8 file.
- Runtime application (admin/includes/pages/templates/config/public/index): 0 actual path profil pengguna.
- README.md: 1 actual path profil pengguna pada petunjuk import. Diganti menjadi database/schema.sql relatif terhadap root project; tidak mengubah schema atau menjalankan import.
- Tujuh laporan di bawah memuat actual path lokal dan tetap direkomendasikan IGNORE oleh item 6. Tidak dihapus/ditimpa, dan nilainya tidak ditulis kembali dalam laporan ini.
- tests/stage7_release_smoke_test.php: pola regex detector error disclosure yang mengenali path pengguna. Bukan lokasi hard-coded untuk membaca/menulis file; pertahankan pemeriksaan security. Variabel run/site/test root tetap dibangun dari lokasi project/runtime dan preflight, bukan profil pengguna tetap.

| File laporan | Keputusan |
| --- | --- |
| LAPORAN_TAHAP_4_INVENTARISASI_PPDB_LEGACY.md | IGNORE: versi mentah berisi path lokal/profil pengguna; offline saja |
| LAPORAN_TAHAP_7_BUILD_RELEASE_BUNDLE.md | IGNORE: versi mentah berisi path lokal/profil pengguna; offline saja |
| LAPORAN_TAHAP_7_LOCAL_RELEASE_SMOKE_TEST.md | IGNORE: versi mentah berisi path lokal/profil pengguna; offline saja |
| LAPORAN_TAHAP_7_RELEASE_BUNDLE_DEPENDENCY_CHECK.md | IGNORE: versi mentah berisi path lokal/profil pengguna; offline saja |
| LAPORAN_TAHAP_7_RELEASE_BUNDLE_SECURITY_SCAN.md | IGNORE: versi mentah berisi path lokal/profil pengguna; offline saja |
| LAPORAN_TAHAP_8A_FINAL_SECRET_SCAN_RELEASE.md | IGNORE: versi mentah berisi path lokal/profil pengguna; offline saja |
| LAPORAN_TAHAP_8A_LITERAL_CREDENTIAL_LEGACY.md | IGNORE: versi mentah berisi path lokal/profil pengguna; offline saja |

## Batas klasifikasi

Path generik tool XAMPP/Laragon dalam petunjuk development dan test lokal tidak sama dengan identitas/path profil pengguna. Tidak memaksa mengganti toolchain lokal atau melakukan refactor test pada item ini.

Substring gaya Unix dapat muncul dalam link Windows yang dinormalisasi menjadi forward slash. Matching tidak otomatis membuktikan aplikasi menggunakan lokasi home Unix; keputusan berdasarkan konteks actual path atau detector.

Laporan dengan path lokal tidak boleh distaging hanya karena secret scanner tidak menemukan password. IGNORE adalah rekomendasi; rule .gitignore belum diterapkan untuk laporan tersebut. Gunakan inventaris keputusan JSON terbaru dan daftar path staging eksplisit kelak. Jika dibutuhkan di repository, susun ringkasan tersunting yang tidak memuat lokasi profil/evidence/bundle lokal.

## Retest dan inventory

- Scan ulang kandidat: tidak ada actual path profil pengguna dalam file berkategori COMMIT atau MANUAL REVIEW. Actual path tersisa hanya pada tujuh laporan yang berkategori IGNORE.
- Pola detector pada smoke test tetap dipertahankan; bukan temuan actual path.
- Source runtime tidak berubah; 78 file runtime tetap sesuai kandidat release terbaru. Tidak membentuk atau memodifikasi bundle.
- Laporan ini dan revisi alasan README dimasukkan ke TAHAP_8A_GIT_FILE_CLASSIFICATION.json. Final 257 dirty: 72 modified + 185 untracked; klasifikasi 165 COMMIT, 78 IGNORE, 0 DELETE LOCAL ARTIFACT, 14 MANUAL REVIEW; 0 staged.
- Inventory diperiksa terhadap setiap path status Git, tanpa duplikasi/file terlewat; git diff --check diperiksa.

## Perubahan dan batas tindakan

README.md: hanya satu path personal diganti path relatif. Inventaris JSON: keputusan/alasan dan jumlah diperbarui. Laporan ini: bukti tanpa path profil pengguna aktual.

Tidak mengubah runtime PHP/test/.gitignore/schema, menghapus laporan/data/file nyata, staging, commit, push, deployment, atau rewrite history. Tidak menjalankan regression database karena tidak ada perubahan aplikasi/test.

ROTATION REQUIRED BEFORE PRODUCTION dan gate manual review sebelumnya tetap terbuka. Tidak menyatakan website siap production.
