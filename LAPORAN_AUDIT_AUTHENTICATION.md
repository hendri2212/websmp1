# Audit Authentication, Session, dan Authorization

Tanggal: 6 Oktober 2026

## Hasil dan Perbaikan

Audit menelusuri login, credential, password verification, session, status akun, role, authorization, dan logout. Temuan authentication pada laporan Tahap 1 berikut telah diperbaiki; laporan Tahap 1 tetap menjadi catatan kondisi saat audit tersebut dilakukan.

| Pemeriksaan | Hasil |
| --- | --- |
| Password login | Menggunakan password_verify terhadap hash database; plaintext tersimpan ditolak pada pengujian |
| Penyimpanan password | password_hash pada pembuatan/perubahan password tetap dipertahankan |
| Query credential | Prepared statement; input username tidak menjadi bagian SQL |
| Session login | session_regenerate_id(true) wajib berhasil sebelum status authenticated dibuat |
| Sumber identitas | ID, username, dan role berasal dari akun database, bukan parameter request |
| Akun nonaktif | Tidak dapat login, termasuk akun bernama admin |
| Akun dinonaktifkan/dihapus setelah login | Pemeriksaan akses berikutnya menolak dan membersihkan identitas session |
| Role berubah | Authorization menggunakan role database terbaru; role session tidak menjadi sumber otoritatif |
| Role tidak dikenal | Login dan session ditolak; tidak ada fallback superadmin berdasarkan username |
| Database gagal | Login dan akses authenticated ditolak; session authentication dibersihkan |
| Logout | Seluruh isi session dibersihkan, ID dirotasi, session ID sebelumnya dihapus |
| Replay session setelah logout | Session ID lama tidak dapat digunakan untuk autentikasi dalam pengujian session PHP |
| CSRF login/logout | Pemeriksaan dipertahankan; parameter token berbentuk array ditolak tanpa TypeError |
| Session configuration | strict mode, cookie-only, HttpOnly, SameSite=Lax, Secure pada HTTPS; URL session ID dinonaktifkan |

## File yang Diubah

- `includes/auth.php`: membuang aktivasi/promosi otomatis akun admin; memvalidasi session dan akun database pada pemeriksaan login/izin; membuang fallback role; menolak credential tidak valid dan kegagalan database; memastikan rotasi login; membersihkan session saat logout.
- `includes/bootstrap.php`: memastikan session ID hanya dikirim melalui cookie, bukan URL; menonaktifkan tampilan detail error PHP di area admin dan mengaktifkan logging sisi server.
- `admin/login.php` dan `admin/logout.php`: menolak token CSRF non-string.
- `tests/auth_security_test.php`: regression test terisolasi untuk alur auth dan session.
- `tests/admin_form_smoke_test.php`: pemeriksaan daftar izin PPDB tidak lagi memalsukan role session superadmin. Pengujian login/authorization akun PPDB dilakukan oleh regression test baru.

Seluruh endpoint admin selain login/logout dan redirect index menggunakan require_admin_permission. Endpoint index/login memeriksa status akun melalui admin_is_logged_in; logout hanya memproses POST dengan token CSRF valid. Form pengelolaan akun memang menerima role baru, tetapi berada di balik permission admins yang hanya dimiliki superadmin; role tersebut bukan role sesi pemohon.

## Verifikasi

Perintah regression test:

```powershell
& 'C:\xampp\php\php.exe' tests/auth_security_test.php
```

- **50 pemeriksaan lulus**, mencakup credential salah/array, plaintext password, username menyerupai SQL injection, akun admin nonaktif, pencabutan akun, perubahan role, role request/session palsu, kegagalan koneksi/prepare/execute/fetch database, penolakan akses tanpa membocorkan exception, pemulihan akses superadmin aktif, logout, dan replay session lama.
- Test menggunakan PDO test double dan penyimpanan session PHP lokal sesungguhnya. Tidak terhubung ke MySQL atau menulis schema/data sekolah.
- Lint rekursif: **69 file PHP lulus, 0 gagal**, menggunakan PHP 8.2.12.
- git diff --check lulus dengan peringatan normalisasi LF/CRLF.
- Tidak ada perubahan schema, fitur baru, redesign, push, atau deployment.
- Pemeriksaan bootstrap tambahan membuktikan display_errors/display_startup_errors nonaktif dan log_errors aktif di area admin, sekalipun display_errors sebelumnya diaktifkan.
- Preview HTTP: beranda dan halaman login merespons 200 tanpa SQLSTATE, fatal error, PDO exception, atau stack trace dalam HTML. Pemeriksaan ini tidak membuktikan login MySQL sungguhan.

## Pemeriksaan Ulang Dua Temuan Kritis Tahap 1

### Aktivasi Otomatis Akun Admin

Versi lama ensure_admin_role_columns menjalankan UPDATE yang memaksa username admin menjadi superadmin dan is_active=1. Karena helper dipanggil saat login/pemeriksaan sesi, keputusan penonaktifan akun dapat dibatalkan tanpa tindakan operator. Perilaku ini tidak diperlukan untuk autentikasi normal: otoritas role dan status aktif harus berasal dari data akun yang dikelola secara eksplisit.

UPDATE tersebut sudah dihapus pada perbaikan sebelumnya dan tidak ditemukan lagi pada helper saat audit ulang ini. Helper mempertahankan tugas kompatibilitas kolom yang sudah ada, tanpa mengubah role/status akun. Login dan pemeriksaan sesi juga tidak lagi memanggil helper ini. Tidak ada schema atau akun database sekolah yang diubah.

Pengujian membuktikan superadmin aktif tetap memiliki akses, admin nonaktif tetap ditolak, dan akun admin yang dinonaktifkan setelah login kehilangan akses. Proteksi pada save_admin_user/delete_admin_user untuk akun pemohon dan superadmin aktif terakhir tetap dipertahankan berdasarkan pemeriksaan kode; proteksi CRUD ini belum diuji dengan MySQL. Keberadaan superadmin aktif dalam database sekolah belum diverifikasi karena audit tidak terhubung ke database tersebut. Akun yang memang nonaktif tidak dipulihkan secara otomatis.

Catatan terpisah: database/schema.sql masih mempunyai perintah seed ON DUPLICATE KEY UPDATE yang menetapkan akun admin aktif/superadmin saat file instalasi diimpor ulang. Itu bukan helper runtime; jangan gunakan import instalasi sebagai mekanisme perbaikan autentikasi pada database berjalan. File tersebut tidak diubah atau dijalankan dalam audit ini.

### Fail-Closed Saat Database Gagal

Versi lama mengembalikan status aktif ketika database tidak tersedia. Versi sekarang mengembalikan false dan membersihkan semua field identitas autentikasi jika koneksi tidak tersedia, query gagal, akun hilang/nonaktif, atau role tidak valid. require_admin mengarahkan sesi yang tidak terverifikasi ke login; admin_can tidak memberikan izin berdasarkan role session lama. Setelah database pulih, akun aktif dapat login kembali secara normal.

Exception database pada login/verifikasi sesi ditangkap tanpa dicetak. Konfigurasi bootstrap kini juga menyembunyikan error runtime PHP di area admin untuk menghindari kebocoran dari operasi lain, dengan logging sisi server tetap aktif. Login menampilkan pesan generik ketika autentikasi gagal. Error startup sebelum bootstrap berjalan tetap merupakan tanggung jawab konfigurasi PHP/server hosting.

## Batas Verifikasi dan Tahap Berikutnya

Tidak ditemukan jalur bypass pada alur yang diperiksa setelah perbaikan, tetapi regression test terisolasi tidak menggantikan functional testing HTTP dengan MySQL dan browser.

Perlu diuji pada database salinan: cookie/header nyata, login/logout melalui HTTP, CSRF yang salah/hilang, akses URL admin langsung untuk setiap role, perubahan role/penonaktifan dari sesi operator lain, dan replay cookie lama melalui browser. Permintaan yang sudah lolos authorization sebelum akun dinonaktifkan dapat tetap menyelesaikan pekerjaan; pencabutan berlaku pada pemeriksaan akses berikutnya.

Pembatasan percobaan login, timeout session, dan pencabutan semua sesi setelah perubahan password belum ditambahkan karena merupakan pekerjaan lanjutan. Helper schema lama di repository tetap ada, tetapi login dan pemeriksaan session tidak lagi memanggil helper role/schema. Tidak ada helper schema yang dijalankan terhadap database sekolah dalam pengujian ini.
