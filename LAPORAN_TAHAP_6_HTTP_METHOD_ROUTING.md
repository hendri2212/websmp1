# Tahap 6 - HTTP Method dan Routing

Tanggal: 10 Oktober 2026. Cakupan: item 13; tanpa deployment atau database sekolah.

## Temuan dan Perbaikan

1. Bootstrap sebelumnya tidak menolak metode di luar GET/HEAD/POST. Helper `require_http_methods()` sekarang mengembalikan 405, `Allow`, pesan generik dan `Cache-Control: no-store`. Guard bootstrap web berada sebelum session start dan pemuatan koneksi database.
2. Router publik sebelumnya hanya membatasi metode halaman PPDB. Sekarang seluruh route publik read-only menerima GET/HEAD; hanya `/kontak` menerima POST untuk form existing yang tetap memakai CSRF/validasi. Penanganan tombstone dilakukan sebelum guard route publik.
3. HEAD dokumen sebelumnya berpotensi menjalankan increment download count. View sekarang memvalidasi dokumen/path dan mengirim header, lalu berhenti pada HEAD sebelum increment/readfile. GET tetap mengirim file dan mencatat statistik download.
4. TRACE diproses oleh core Apache dan tidak berhasil ditutup lewat guard PHP maupun percobaan rewrite `.htaccess`. Rule percobaan dihapus. Ditambahkan `config/apache_security.conf` berisi `TraceEnable Off`, untuk Include eksplisit oleh konfigurasi server/VirtualHost; server Apache test benar-benar memuat file ini.

`TraceEnable Off` bukan directive `.htaccess`; ketika aktif, Apache menolak TRACE dengan 405. Referensi: [dokumentasi resmi Apache](https://httpd.apache.org/docs/2.4/mod/core.html#traceenable). File konfigurasi tersebut tidak otomatis diterapkan hanya dengan mengunggah source website.

## Matriks Perilaku

| Endpoint | GET | HEAD | POST | PUT/PATCH/DELETE/OPTIONS | TRACE |
| --- | --- | --- | --- | --- | --- |
| Route informasi publik, termasuk `/ppdb` dan download | Baca | Header tanpa body; dokumen tidak dihitung sebagai download | 405 + Allow GET, HEAD | 405 sebelum koneksi DB | 405 oleh Apache yang memakai snippet |
| `/kontak` | Form | Tanpa body | Form existing + CSRF/validasi | 405 | 405 server |
| Login admin | Form | Tanpa body | Credential + CSRF | 405 | 405 server |
| CRUD/account/settings/backup admin | Authentication + permission; baca/form, tidak menjalankan aksi mutasi | Sama, tanpa body | Authentication + permission + CSRF/validasi | 405 sebelum auth/DB | 405 server |
| Logout | Redirect; tidak menjalankan logout | Redirect tanpa body; tidak menjalankan logout | Logout hanya dengan CSRF valid | 405 | 405 server |
| Tombstone file langsung: `admin/ppdb_applications.php`, `admin/ppdb_export.php`, `admin/ppdb_download.php`, `pages/ppdb_receipt.php` | 410 | 410 tanpa body | 410 | 410 | 405 server |
| Tombstone route `/ppdb/bukti` | 410 | 410 tanpa body | 410 | 405 guard bootstrap | 405 server |

GET/HEAD admin dapat membaca akun/session untuk verifikasi serta menghasilkan audit keamanan saat invalidasi. Itu bukan akses untuk create/update/delete akun/konten melalui GET. Penghitung download dokumen pada GET adalah statistik fitur existing, bukan mutasi administratif; tidak dihapus. Session/CSRF initialization juga tetap diperlukan pada request baca.

Panel read-only admin masih dapat merespons POST dengan tampilan bila tidak memiliki handler mutasi. Audit tidak mengklaim seluruh URL read-only admin memiliki allowlist GET-only; tidak ditemukan operasi data dari POST pada panel tersebut. Parameter query delete/status serta token di query tidak menggantikan body POST yang valid. Tidak ditemukan mekanisme method override dari request.

## Bukti Test

Semua test HTTP memakai Apache loopback, snapshot runtime dan boundary koneksi `db() = null`, tidak database sekolah. Pengujian view dokumen memakai PDF dummy dan repository counter terinstrumentasi tanpa query database. Test handler mengeksekusi blok POST asli dengan operation probes, bukan mutasi database nyata.

| Test | Hasil |
| --- | --- |
| Apache nyata, root/subfolder | **2.052 pemeriksaan lulus**; GET/POST existing, HEAD, unsupported methods, sensitive files, routing dan tombstone |
| View download dokumen asli | HEAD 200/body kosong/counter tidak dipanggil; GET 200/file identik/counter dipanggil pada kedua mount |
| CSRF admin non-PPDB + metode | **792 lulus**, 17 handler, 49 varian aksi |
| CSRF seluruh handler aktif + metode | **872 lulus**, 19 handler termasuk kontak dan konten PPDB informatif; bukan pendaftaran PPDB |
| Role/permission | **437 lulus** |
| Authentication termasuk logout/replay | **53 lulus** |
| Session | **81 lulus** |
| CLI entry guards | **209 lulus** |
| PPDB dead-code boundary | **34 lulus** |
| Legacy database boundary | **9 lulus**, tanpa data pendaftar nyata |
| Runtime schema model | **70 lulus** |
| Error/disclosure HTTP | **155 lulus** |
| PHP lint | **134 source PHP, 0 error**, mengecualikan storage snapshots/temporary tests |
| Conflict marker / `git diff --check` | **0 marker / lulus** |
| Diff schema/config database | Kosong; worktree tetap dirty, **181 modified/untracked entries** |

Test CSRF diperluas: payload dengan token valid tetap tidak mencapai operasi ketika method HEAD, PUT, PATCH, DELETE, OPTIONS atau TRACE, termasuk parameter query. Assertion POST/GET dan token invalid yang lama dipertahankan.

Satu ekspektasi test baru dikoreksi berdasarkan bukti routing: PUT ke `pages/ppdb_receipt.php` langsung menghasilkan 410, bukan 405, karena URL file memang melewati tombstone langsung tanpa bootstrap. Tidak ada perubahan aplikasi PPDB untuk memaksakan hasil tersebut. Percobaan TRACE gagal sebelum konfigurasi server ditambahkan; target penolakan tetap dipertahankan, tidak menerima TRACE sebagai hasil lulus.

## File pada Item Ini

- `includes/functions.php`: helper metode HTTP.
- `includes/bootstrap.php`: guard metode web sebelum session/database.
- `index.php`: allowlist metode route publik, mempertahankan kontak dan tombstone.
- `pages/document_download.php`: HEAD tidak increment/stream.
- `config/apache_security.conf`: snippet server/VirtualHost baru, bukan konfigurasi database.
- `tests/stage6_apache_security_test.php`: metode CURL nyata, HEAD/counter fixture, matriks method dan tombstone, Include snippet.
- `tests/csrf_handler_test.php`: menambah probes metode tanpa mengurangi assertion lama.
- Laporan ini.

Aturan `.htaccess` tahap sebelumnya dipertahankan; tidak ada rule TRACE percobaan yang ditinggalkan. Source tombstone dan database PPDB tidak diubah. Tidak ada perubahan schema/credential/UI, commit, push atau deployment.

## Risiko / Batas Validasi

- Hosting harus menerapkan `TraceEnable Off` atau padanan server/proxy dan menguji TRACE kembali. Ini masih persyaratan hosting yang belum diterapkan ke lingkungan production.
- OPTIONS CORS tidak tersedia pada endpoint aplikasi; website saat ini tidak menyediakan API cross-origin. Jangan membuka OPTIONS atau metode lain tanpa kebutuhan dan authorization yang jelas.
- HEAD tetap menjalankan verifikasi akun dan query baca normal. Audit ini mencegah aksi CRUD/backup/logout dan increment download dari HEAD, bukan menjanjikan seluruh sistem bebas efek session/log.
- Test ini tidak mengulang seluruh CRUD/upload dengan database nyata; regression handler/authorization dan HTTP lokal telah dijalankan, hasil functional database tahap sebelumnya tetap menjadi referensi.
- Database unavailable pada snapshot membuat route baca publik menghasilkan 503 generik, bukan halaman normal. HEAD/GET routing dan guard diuji dalam kondisi tersebut; konten normal tetap mengacu pada testing tahap sebelumnya.

## Cleanup dan Kesimpulan

Server/snapshot Apache dan canary CLI milik test dihentikan/dibersihkan. PDF/counter fixture hanya berada pada snapshot disposable. Tidak dibuat data sintetis database dan tidak disentuh backup/data/file historis.

**Item 13 lulus dalam cakupan lokal dengan konfigurasi Apache test yang memuat snippet keamanan.** Endpoint sensitif tidak menjalankan mutasi administratif melalui GET/HEAD, metode tidak didukung ditolak, dan legacy PPDB tidak diaktifkan kembali. Website belum dinyatakan siap production; persyaratan konfigurasi hosting masih terbuka.
