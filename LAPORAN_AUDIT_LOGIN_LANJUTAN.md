# Audit Login Lanjutan - Authentication dan Panel Admin

Tanggal: 6 Oktober 2026

## Scope

Audit alur login, credential, session, akun aktif, role, authorization, dan logout. Tidak mengubah PPDB, permission PPDB, schema, UI, atau struktur project. Tidak menjalankan workflow pendaftaran atau destructive database test. Tidak melakukan commit, push, atau deployment.

## Hasil Pemeriksaan

| Pemeriksaan | Hasil |
| --- | --- |
| Verifikasi password | password_verify terhadap hash database; tidak ditemukan perbandingan plaintext pada jalur login/perubahan password |
| Penyimpanan password | password_hash tetap digunakan |
| Credential | String tidak kosong; username menggunakan prepared statement; array credential ditolak |
| CSRF login/logout | POST dan token sesi valid wajib sebelum operasi |
| Session login | session_regenerate_id(true) harus berhasil sebelum identitas authenticated disimpan; token CSRF sebelumnya dibuang |
| Akun nonaktif | Tidak dapat login, termasuk username admin |
| Penonaktifan setelah login | Akun dibaca ulang; akses berikutnya ditolak dan identitas authentication dibersihkan |
| Role | Dari database; role GET/POST/REQUEST tidak menjadi role pemohon; role session palsu diganti oleh hasil verifikasi database |
| Authorization | require_admin_permission mendahului handler pada 22 endpoint terlindungi; role tanpa izin mendapat 403, sesi tidak valid diarahkan ke login |
| Database gagal | Fail closed, tanpa menampilkan exception sensitif |
| Logout setelah perbaikan | Seluruh session dikosongkan, session_destroy dipanggil, cookie sesi dikedaluwarsakan menggunakan scope/atribut cookie yang sama |
| Replay logout | ID sebelumnya tidak mengembalikan authentication dalam pengujian strict session PHP |

## Temuan yang Diperbaiki

1. Logout sebelumnya mengosongkan session dan melakukan session_regenerate_id(true), sehingga ID authenticated lama terhapus tetapi sesi anonim baru tetap aktif. Ini belum memenuhi permintaan penghancuran sesi sepenuhnya. Kini logout memanggil session_destroy dan tidak membuat sesi pengganti. Request login berikutnya dapat membuat sesi anonim baru melalui bootstrap untuk CSRF seperti biasa.
2. log_admin_activity sebelumnya memanggil db di luar try/catch. Exception koneksi pada tahap logging dapat menghalangi logout. Pemanggilan koneksi kini berada di dalam try/catch logger; pembersihan logout juga berada dalam finally agar tetap dijalankan jika logging melempar exception.

Perubahan kode hanya includes/auth.php dan tests/auth_security_test.php. Laporan ini ditambahkan sebagai catatan terbaru. Deskripsi logout berupa rotasi ID pada laporan terdahulu merupakan perilaku historis dan digantikan hasil di atas.

## Verifikasi

- tests/auth_security_test.php: 53 pemeriksaan lulus; tambahan meliputi penghancuran storage/status session, kegagalan koneksi logging saat logout, dan logout berulang yang tidak menciptakan sesi baru.
- tests/role_permission_test.php: 445 assertion lulus pada guard/menu nyata dengan PDO simulasi. Penyebutan role ppdb dalam fixture hanya menguji keamanan akun/role yang ada, bukan workflow pendaftaran.
- tests/session_configuration_test.php: 81 pemeriksaan lulus, termasuk HTTP localhost dan simulasi HTTPS.
- tests/error_handling_test.php: 34 pemeriksaan lulus.
- Semua 76 file PHP lolos lint; 0 conflict marker; git diff --check lulus dengan peringatan LF/CRLF saja.
- Tes memakai PDO simulasi dan file session uji; tidak terhubung ke MySQL sekolah. Suite CRUD/CSRF yang mengeksekusi probe workflow pendaftaran tidak dijalankan pada tahap ini.

## Batasan dan Tindak Lanjut

- Penghapusan cookie menggunakan setcookie yang mempertahankan path/domain/Secure/HttpOnly/SameSite. Header expired cookie saat logout authenticated melalui HTTP dengan akun MySQL nyata belum diuji; tes utama membuktikan invalidasi session server-side dan replay ID lama.
- Keberhasilan operasi session_destroy bergantung pada session backend/izin storage server. Pengujian menggunakan handler files yang berfungsi, bukan simulasi kegagalan backend session.
- Akun yang dinonaktifkan kehilangan akses pada pemeriksaan berikutnya; request yang sudah lolos guard sebelumnya dapat menyelesaikan pekerjaan.
- Authentication HTTP end-to-end, logout lintas browser, TLS/proxy dan penyimpanan session hosting masih perlu functional testing dengan database salinan.
- Rate limiting, timeout aplikasi, dan pencabutan seluruh sesi setelah perubahan password tetap di luar scope.

Kesimpulan: alur authentication yang diperiksa lulus tes terisolasi setelah perbaikan logout, dan dapat dilanjutkan ke functional testing admin terkontrol. Bukan pernyataan website siap production.
