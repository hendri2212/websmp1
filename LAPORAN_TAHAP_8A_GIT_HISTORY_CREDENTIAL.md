# Tahap 8A Item 2 - Git History Credential

Tanggal: 2026-10-10.

## Keputusan

**ROTATION REQUIRED BEFORE PRODUCTION**

Status penggunaan nyata tidak dapat diverifikasi tanpa pemeriksaan instalasi oleh operator berwenang. Tidak membuka database production, database testing, konfigurasi lokal berisi credential, atau backup data nyata pada audit ini.

## Bukti read-only

Audit meninjau empat commit reachable pada repository lokal dengan git rev-list, ls-tree, dan show. Tidak fetch remote atau melakukan rewrite history.

Pada setiap commit tersebut:

- admin/production_check.php memuat literal credential installer sebagai pembanding diagnostic.
- database/schema.sql memuat INSERT akun admins dan hash password historis.
- password_verify membuktikan literal diagnostic cocok dengan satu hash schema historis. Nilai password/hash hanya diproses di memori dan tidak dicetak atau disimpan kembali.
- README.md memuat literal yang sama sebagai dokumentasi credential awal.

| File | Type of secret | Status |
| --- | --- | --- |
| admin/production_check.php (history/index lama) | Literal password installer historis | Cocok dengan hash seed historis. Working tree diagnostic sudah bersih pada item 1. |
| database/schema.sql (history/index lama) | Hash password akun installer historis | Dapat diverifikasi menggunakan literal historis. Seed akun sudah dihapus dari working tree. |
| README.md (history/index lama) | Credential awal terdokumentasi | Masih dapat dibaca dari history/index lama; telah dihapus dari working tree. |

Hash/literal tidak dicantumkan dalam laporan. Audit ini membuktikan hubungan literal dan hash, bukan keberadaan akun production.

## Jawaban status credential

### Apakah masih dapat digunakan?

Secara kriptografis YA untuk hash historis yang tidak berubah. Login current menggunakan password_verify terhadap hash database, memerlukan akun aktif dan role valid. Tidak ada pencabutan otomatis khusus credential historis. Karena itu, jika instalasi masih memiliki akun dengan hash tersebut, akun aktif, dan role valid, login dengan credential historis masih mungkin berhasil.

Penggunaan nyata: BELUM TERKONFIRMASI. Tidak mencoba login pada website/database sekolah.

### Apakah akun terkait masih mungkin ada?

YA. Installer historis menyisipkan akun awal; perubahan source tidak menghapus atau mengganti record existing. Initializer baru menolak database yang sudah memiliki akun, tanpa melakukan reset/promosi/aktivasi. Tidak ada bukti operator telah menghapus, menonaktifkan, atau merotasi akun lama. Salinan database atau restore backup historis juga dapat membawa kembali hash lama.

### Apakah hanya development lama?

TIDAK DAPAT DIPASTIKAN. Credential berada pada installer dan dokumentasi akun awal, bukan sekadar fixture test. Tidak tersedia bukti pembatasan penggunaannya hanya pada development. Perlakukan sebagai credential yang telah terekspos, bukan sebagai risiko yang otomatis hilang karena source kini bersih.

## Tindakan operator sebelum production

1. Inventarisasi akun pada instalasi yang akan digunakan, termasuk akun default lama dan akun lain yang mungkin memakai ulang credential tersebut. Lakukan secara privat dengan otorisasi; jangan menaruh credential atau hash dalam laporan/chat.
2. Rotasi credential akun terdampak ke password unik kuat melalui mekanisme pengelolaan akun yang sah; atau nonaktifkan akun yang tidak diperlukan. Pastikan minimal satu superadmin valid tetap aktif dan akses penggantinya sudah diverifikasi sebelum menonaktifkan akun lama.
3. Cabut sesi terdampak melalui prosedur operasional yang ditinjau. Perubahan password saja tidak boleh diasumsikan otomatis mencabut seluruh sesi existing.
4. Catat konfirmasi rotasi tanpa nilai password/hash: akun yang diperiksa, waktu, operator, tindakan, dan verifikasi akses. Jika credential pernah dipakai ulang pada layanan lain, rotasi penggunaan tersebut juga.
5. Lindungi backup historis yang memuat hash lama. Setelah restore dari backup lama, ulangi review/rotasi sebelum akses publik dibuka; jangan mengimport ulang seed akun historis.

Tidak melakukan rotasi nyata pada pekerjaan ini. Gate tetap terbuka sampai ada konfirmasi operator.

## Git history

Tidak menjalankan filter-repo, filter-branch, reset, rebase, amend, force push, gc, atau penghapusan history. Tidak staging, commit, push, atau deployment. Menghapus nilai dari working tree tidak menghapus salinannya pada history/index lama dan tidak membatalkan credential yang sudah diketahui pihak lain.

Jika rewrite history ingin diputuskan kemudian: backup repository lengkap terlebih dahulu, analisis branch/tag/remote dan dampaknya pada clone/fork serta kolaborator, lalu tentukan prosedur terpisah. Rewrite history bukan pengganti rotasi credential. Audit lokal ini tidak mengklaim mencakup remote refs yang belum tersedia atau clone/fork lain.

## Validasi dan perubahan

- Test baru: tests/stage8_history_credential_audit.php, CLI-only; hanya operasi Git read-only, pembandingan hash di memori, dan pembacaan dua source current. Tidak meng-include bootstrap atau menghubungi database.
- Hasil: PASS_READ_ONLY_HISTORY_AUDIT; 4 commit ditinjau, masing-masing 1 hash cocok, current diagnostic literal false, current schema account seed false, database_accessed false, history_modified false, raw_credentials_printed false.
- PHP lint seluruh source non-artifact: 140 PHP, 0 error. git diff --check: lulus. Git akhir: 251 dirty (72 modified, 179 untracked), 0 staged.
- Tidak mengubah source aplikasi, release bundle, schema, akun, atau data PPDB pada item ini.
- Laporan ini dan test baru adalah satu-satunya file yang ditambahkan pada item 2. Test berada di luar release allowlist.

## Kesimpulan

Identifikasi history selesai. Risiko penggunaan nyata BELUM ditutup: **ROTATION REQUIRED BEFORE PRODUCTION**. Tidak menyatakan seluruh Tahap 8A lulus, seluruh perubahan Git selesai direview, atau website siap production.
