$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$cases = @(
    [pscustomobject]@{path='.env'; ignored=$true}
    [pscustomobject]@{path='.env.production'; ignored=$true}
    [pscustomobject]@{path='.env.local'; ignored=$true}
    [pscustomobject]@{path='nested/.env.example'; ignored=$true}
    [pscustomobject]@{path='nested/.env.production.example'; ignored=$true}
    [pscustomobject]@{path='config/database.local.php'; ignored=$true}
    [pscustomobject]@{path='config/nested/database.local.ini'; ignored=$true}
    [pscustomobject]@{path='config/nested/database.local'; ignored=$true}
    [pscustomobject]@{path='config/nested/local.json'; ignored=$true}
    [pscustomobject]@{path='credentials.json'; ignored=$true}
    [pscustomobject]@{path='connection.json'; ignored=$true}
    [pscustomobject]@{path='backup/current.sql'; ignored=$true}
    [pscustomobject]@{path='nested/backups/backup.bin'; ignored=$true}
    [pscustomobject]@{path='nested/dumps/latest.dump'; ignored=$true}
    [pscustomobject]@{path='nested/logs/server.txt'; ignored=$true}
    [pscustomobject]@{path='server.log'; ignored=$true}
    [pscustomobject]@{path='storage/sessions/sess_dummy'; ignored=$true}
    [pscustomobject]@{path='sess_dummy'; ignored=$true}
    [pscustomobject]@{path='anonymous.cookies'; ignored=$true}
    [pscustomobject]@{path='dummy.session'; ignored=$true}
    [pscustomobject]@{path='nested/tmp/file.txt'; ignored=$true}
    [pscustomobject]@{path='nested/temp/file.txt'; ignored=$true}
    [pscustomobject]@{path='dummy.tmp'; ignored=$true}
    [pscustomobject]@{path='dummy.pid'; ignored=$true}
    [pscustomobject]@{path='candidate.zip'; ignored=$true}
    [pscustomobject]@{path='candidate.tar.gz'; ignored=$true}
    [pscustomobject]@{path='release/payload/index.php'; ignored=$true}
    [pscustomobject]@{path='school-release-candidate-dummy/payload/index.php'; ignored=$true}
    [pscustomobject]@{path='storage/testing/database/data.bin'; ignored=$true}
    [pscustomobject]@{path='tests/.tmp_sessions/sess_dummy'; ignored=$true}
    [pscustomobject]@{path='tests/screenshots/dummy.png'; ignored=$true}
    [pscustomobject]@{path='.idea/workspace.xml'; ignored=$true}
    [pscustomobject]@{path='.vscode/settings.json'; ignored=$true}
    [pscustomobject]@{path='.vs/state.bin'; ignored=$true}
    [pscustomobject]@{path='.DS_Store'; ignored=$true}
    [pscustomobject]@{path='Desktop.ini'; ignored=$true}
    [pscustomobject]@{path='desktop.ini'; ignored=$true}
    [pscustomobject]@{path='Thumbs.db'; ignored=$true}
    [pscustomobject]@{path='.env.example'; ignored=$false}
    [pscustomobject]@{path='.env.production.example'; ignored=$false}
    [pscustomobject]@{path='config/app.php'; ignored=$false}
    [pscustomobject]@{path='config/database.php'; ignored=$false}
    [pscustomobject]@{path='config/env.php'; ignored=$false}
    [pscustomobject]@{path='config/apache_security.conf'; ignored=$false}
    [pscustomobject]@{path='config/php_security.ini'; ignored=$false}
    [pscustomobject]@{path='.user.ini'; ignored=$false}
    [pscustomobject]@{path='database/schema.sql'; ignored=$false}
    [pscustomobject]@{path='database/migrations/dummy.sql'; ignored=$false}
    [pscustomobject]@{path='tests/fixtures/dummy.sql'; ignored=$false}
    [pscustomobject]@{path='tests/fixtures/stage3_content_cases.php'; ignored=$false}
    [pscustomobject]@{path='tests/auth_security_test.php'; ignored=$false}
    [pscustomobject]@{path='tests/gitignore_policy_test.ps1'; ignored=$false}
    [pscustomobject]@{path='README.md'; ignored=$false}
    [pscustomobject]@{path='LAPORAN_TAHAP_8A_GIT_HISTORY_CREDENTIAL.md'; ignored=$false}
    [pscustomobject]@{path='LAPORAN_TAHAP_8A_FINAL_GITIGNORE.md'; ignored=$false}
    [pscustomobject]@{path='public/uploads/.htaccess'; ignored=$false}
    [pscustomobject]@{path='public/uploads/gallery/.gitkeep'; ignored=$false}
    [pscustomobject]@{path='public/uploads/dummy.jpg'; ignored=$true}
    [pscustomobject]@{path='public/uploads/gallery/dummy.jpg'; ignored=$true}
    [pscustomobject]@{path='storage/.htaccess'; ignored=$false}
    [pscustomobject]@{path='LAPORAN_AKHIR_TAHAP_3.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_AKHIR_TAHAP_3B.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_AKHIR_TAHAP_4_PPDB.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_AKHIR_TAHAP_5.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_AKHIR_TAHAP_6.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_AUDIT_ACCESS_CONTROL.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_AUDIT_AUTHENTICATION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_AUDIT_SESSION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_AUDIT_TAHAP_1.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_CLEANUP_DATA_TESTING.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_CRUD_KONTEN.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_DATABASE_CONSISTENCY.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_ERROR_HANDLING.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_FILE_DOWNLOAD.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_LOGIN_LOGOUT.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_PRAFLIGHT.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_REGRESSION_CHECK.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_ROLE_CRUD.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_UPLOAD.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3_VALIDASI_INPUT.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_3B_RETEST.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_AUDIT_ROLE_PPDB.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_BERKAS_PPDB_LEGACY.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_DATABASE_PPDB.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_DEAD_CODE_PPDB.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_DELETE_FEEDBACK.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_ENTRY_POINT_PPDB.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_FILE_LIFECYCLE.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_HALAMAN_INFORMASI_PPDB.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_INPUT_VALIDATION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_INVENTARISASI_PPDB_LEGACY.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_MENU_ADMIN_PPDB.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_PENGUMUMAN_PUBLIK.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_PERBAIKAN_STORED_XSS.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_PPDB_INFORMATIF.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_REGRESSION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_SECURITY_ENDPOINT_PPDB.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_STRICT_ID_VALIDATION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_4_UPLOAD_SECURITY.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_AUDIT_DATABASE_ARCHITECTURE.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_BACKUP_COMPLETENESS.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_BACKUP_RETENTION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_BACKUP_SECURITY.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_CLEANUP_TEST.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_DATABASE_FAILURE_HANDLING.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_FILE_BACKUP.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_LAST_SUPERADMIN_SAFETY.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_PPDB_LEGACY_RETENTION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_REGRESSION_TEST.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_RESTORE_TEST.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_RUNTIME_SCHEMA_STABILITY.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_5_TRANSACTION_AUDIT.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_APACHE_SECURITY.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_BACKUP_EXPOSURE.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_DEFAULT_TEST_ACCOUNT.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_DIRECTORY_LISTING.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_INVENTARIS_PRODUCTION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_KOREKSI_REGRESSION_PPDB.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_REGRESSION_TAHAP_1_5.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_SENSITIVE_FILE_HTTP_TEST.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_SETUP_MAINTENANCE.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_6_UPLOAD_DIRECTORY_HARDENING.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_BACKUP_CAPACITY_NOTE.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_BUILD_RELEASE_BUNDLE.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_DEFAULT_INSTALLER_CREDENTIAL.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_FINAL_REGRESSION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_GIT_CHECK_COMMIT_GROUPING.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_HTTPS_HEADER_SIMULATION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_LOCAL_RELEASE_SMOKE_TEST.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_PRODUCTION_CONFIG_SIMULATION.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_RELEASE_BUNDLE_DEPENDENCY_CHECK.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_RELEASE_BUNDLE_SECURITY_SCAN.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_REVIEW_GIT_CHANGES.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_7_TRACKED_SECRETS.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_8A_FINAL_SECRET_SCAN_RELEASE.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_8A_FINAL_SECRET_SCAN_WORKING_TREE.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_8A_LITERAL_CREDENTIAL_LEGACY.md'; ignored=$true}
    [pscustomobject]@{path='LAPORAN_TAHAP_8A_REVIEW_DIRTY_FILES.md'; ignored=$true}
)
Push-Location $root
try {
    # PowerShell 5 adds CR to piped lines; pass paths as argv to preserve exact names.
    $paths = @($cases | ForEach-Object { $_.path })
    $actual = @(git check-ignore --no-index -- @paths)
    if ($LASTEXITCODE -gt 1) { throw 'Git ignore check failed.' }
    $failed = @($cases | Where-Object { ($actual -ccontains $_.path) -ne $_.ignored })
    [pscustomobject]@{
        status= $(if ($failed.Count -eq 0) {'PASS'} else {'FAIL'})
        checks=$cases.Count
        failures=@($failed | Select-Object path,ignored)
        files_created=$false
        git_index_changed=$false
        database_accessed=$false
    } | ConvertTo-Json -Depth 4
    if ($failed.Count -gt 0) { exit 1 }
} finally { Pop-Location }
