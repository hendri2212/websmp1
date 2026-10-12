# Tahap 8A.2 Item 3 - Legacy Test Review

Tanggal: 2026-10-10. Keputusan final: **DO NOT COMMIT / rekomendasi REMOVE LEGACY TEST pada langkah terpisah**.

## Temuan

| Prioritas | Bukti | Dampak |
| --- | --- | --- |
| Tinggi | tests/admin_form_smoke_test.php:18 memuat bootstrap; :105 hanya memeriksa db_available(). Tidak ada stage3_test_database() atau guard instance/grant/marker. | CLI-only bukan isolasi database. Test mengikuti konfigurasi aplikasi dan dapat menulis ke target yang tidak disetujui. Jangan dijalankan. |
| Tinggi | :75-99 cleanup DELETE LIKE prefix; :119 dan :402 memakai prefix umum yang sama, bukan ledger ID milik satu run. | Dapat menghapus data run lain/record matching; bypass lifecycle file dan kebijakan account. Cleanup awal juga berada di luar try/finally. |
| Tinggi | :123-124 masih mengharapkan permission pendaftar; :369-397 memanggil save/lookup/status/delete pendaftaran lokal. | Bertentangan dengan PPDB pasif; helper applicant sudah tidak tersedia di repository. Jangan membuat pendaftar dummy atau mengaktifkan helper demi meluluskan test. |
| Sedang | :110-113 mengisi session superadmin langsung; :122 menguji admin_can(), sedangkan operasi konten memanggil repository langsung. | Tidak membuktikan login, password_verify, session regeneration, authorization handler, CSRF, atau akses URL/POST langsung. Label "Form" menimbulkan false confidence. |
| Sedang | :245-258 menyimpan metadata/path PDF tanpa fixture file yang benar-benar diupload. | Tidak membuktikan upload, MIME/content, download, atau consistency; CREATE/lookup/DELETE dominan, bukan seluruh UPDATE/negative cases. |
| Rendah | :133 memakai password sintetis literal; :400 mencetak pesan exception langsung. | Tidak diperlukan pada suite yang dipertahankan; detail internal dapat ikut masuk output CI/log. Nilai password tidak disalin ke laporan. |

## Coverage pengganti

| Area | Suite yang dipertahankan |
| --- | --- |
| Guard database testing | tests/stage3_database_preflight.php:10-40 memverifikasi konfigurasi, actual instance/datadir, akun, grants, dan marker. |
| Login/logout runtime | tests/stage3_auth_http_test.php, dengan preflight dan mode eksplisit; tests/auth_security_test.php dan tests/session_configuration_test.php sebagai checks pendukung. |
| CRUD dan authorization endpoint | tests/stage3_content_http_test.php mode --run dan --roles, fixture stage3_content_cases.php dan stage3_crud_role_checks.php. |
| Upload/download/input/consistency | Suite content HTTP mode --uploads, --downloads, --inputs, --ids, --validation, --consistency, --errors; fixtures khusus setiap area. |
| PPDB pasif | Suite content HTTP --ppdb-info, tests/stage4_ppdb_dead_code_test.php, dan fixture legacy security yang hanya membuktikan penolakan/tombstone. |
| Orkestrasi non-PPDB | tests/stage4_non_ppdb_regression.php memakai suite auth/content yang baru, bukan admin_form_smoke_test.php. |

Suite pengganti harus tetap memakai instance testing terverifikasi. Tabel di atas adalah pemetaan source, bukan klaim seluruh suite runtime dijalankan ulang pada review ini.

## Keputusan dan dependency

- Tidak relevan sebagai acceptance/regression test saat ini; coverage yang masih sah sudah superseded, asumsi PPDB dan keamanan lama tidak kompatibel.
- Tidak aman dipertahankan sebagai test yang direkomendasikan untuk dijalankan. Tidak diperbaiki menjadi suite tambahan karena akan menduplikasi coverage baru dan membawa workflow yang sudah dilarang.
- File legacy tidak dijalankan, tidak diedit, tidak dihapus, dan tidak di-stage. Dipertahankan sementara agar pekerjaan pengguna tidak terhapus pada review.
- README sebelumnya merekomendasikan eksekusi setelah MySQL aktif. Instruksi tersebut diganti dengan larangan menjalankan legacy dan rujukan suite terisolasi; perubahan README tidak masuk runtime release.
- File legacy sudah tracked. DO NOT COMMIT pada diff saat ini tidak menghapus versi HEAD. Sebelum checkpoint final, putuskan penghapusan tracked secara eksplisit dan audit ulang referensi/CLI entrypoint discovery; jangan menganggap pengecualian staging sudah menghilangkan risikonya.
- Release allowlist tidak menyertakan tests/; tidak ada perubahan runtime/bundle atau kebutuhan rebuild karena review dokumentasi ini.
- Dua dokumen masih MANUAL REVIEW: DEPLOYMENT.md dan LAPORAN_SINGKAT_WEBSITE_SEKOLAH.md. Disposisi tracked seed tetap gate terpisah; credential historis tetap ROTATION REQUIRED BEFORE PRODUCTION.

## Validasi review

- Targeted role/permission: 437 assertions lulus; PPDB dead-code: 34 checks lulus. Kedua test pendukung ini tidak menggunakan database; bukan eksekusi ulang CRUD runtime.
- Inventaris dan manifest cocok dengan seluruh dirty path: 0 missing, 0 extra, 0 duplicate. Git: 72 modified + 113 untracked, 0 staged, 0 unmerged; git diff --check lulus dan conflict marker 0.
- Inventaris/manifest diperbarui menjadi 185 dirty: 181 COMMIT, 2 DO NOT COMMIT, 2 MANUAL REVIEW. Angka ini merupakan snapshot review, bukan instruksi staging.
- Tidak menggunakan database production, tidak melakukan mutasi data, tidak menjalankan test legacy, tidak git add/commit/push/deploy.
