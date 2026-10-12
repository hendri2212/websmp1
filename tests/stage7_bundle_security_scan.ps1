param([Parameter(Mandatory=$true)][string]$BundleDirectory)
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$findings = New-Object 'System.Collections.Generic.List[object]'
$seen = @{}
try {
    # Load only detector definitions, never the Git scanner's entry point.
    $tokens = $null; $errors = $null
    $ast = [Management.Automation.Language.Parser]::ParseFile((Join-Path $PSScriptRoot 'stage7_secret_audit.ps1'), [ref]$tokens, [ref]$errors)
    if (@($errors).Count) { throw 'Detector parse failed.' }
    foreach ($name in @('Add-Finding','Scan-Literal','Scan-Text')) {
        $definitions = @($ast.FindAll({ param($node) $node -is [Management.Automation.Language.FunctionDefinitionAst] }, $true) | Where-Object { $_.Name -ceq $name })
        if ($definitions.Count -ne 1) { throw 'Detector definition mismatch.' }
        . ([scriptblock]::Create($definitions[0].Extent.Text))
    }
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $bundle = [IO.Path]::GetFullPath($BundleDirectory)
    $receipt = Get-Content -LiteralPath (Join-Path $bundle 'build-receipt.json') -Raw | ConvertFrom-Json
    $allow = Get-Content -LiteralPath (Join-Path $bundle 'allowlist-policy.json') -Raw | ConvertFrom-Json
    $deny = Get-Content -LiteralPath (Join-Path $bundle 'denylist-policy.json') -Raw | ConvertFrom-Json
    $inventory = Get-Content -LiteralPath (Join-Path $bundle 'file-hashes.json') -Raw | ConvertFrom-Json
    if ($receipt.user_ini_included) { throw 'This scan expects the existing 78-file candidate without conditional .user.ini.' }
    $zipPath = Join-Path $bundle 'website-source.zip'
    if ((Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLowerInvariant() -cne $receipt.zip_sha256) { throw 'Archive integrity mismatch.' }
    if ((Get-FileHash -LiteralPath (Join-Path $bundle 'allowlist-policy.json') -Algorithm SHA256).Hash.ToLowerInvariant() -cne $receipt.allowlist_sha256 -or
        (Get-FileHash -LiteralPath (Join-Path $bundle 'denylist-policy.json') -Algorithm SHA256).Hash.ToLowerInvariant() -cne $receipt.denylist_sha256) { throw 'Policy integrity mismatch.' }
    $zip = [IO.Compression.ZipFile]::OpenRead($zipPath)
    $entryNames = @(); $localReferences = @(); $markerReferences = @(); $scanRecords = @()
    try {
        foreach ($entry in $zip.Entries) {
            $path = $entry.FullName
            if ($path -match '(^/|^[A-Za-z]:|\\|(^|/)\.\.?(/|$)|[*?]|[\x00-\x1f])' -or $entryNames -ccontains $path) { throw 'Unsafe or duplicate archive path.' }
            $entryNames += $path
            if ($allow.required_source_files -cnotcontains $path) { throw 'Unexpected archive path.' }
            foreach ($rule in $deny.rules) {
                if ($path -match $rule.path_regex) {
                    $exception = @($deny.exact_control_exceptions | Where-Object { $_.path -ceq $path -and $_.exception_for_rule_ids -contains $rule.id })
                    if (-not $exception.Count) { throw 'Forbidden archive path.' }
                }
            }
            if ($entry.Length -gt 33554432) { throw 'Unexpected large source entry.' }
            $memory = New-Object IO.MemoryStream
            $stream = $entry.Open()
            try { $stream.CopyTo($memory) } finally { $stream.Dispose() }
            $bytes = $memory.ToArray(); $memory.Dispose()
            $sha = [Security.Cryptography.SHA256]::Create()
            try { $hash = ([BitConverter]::ToString($sha.ComputeHash($bytes))).Replace('-','').ToLowerInvariant() } finally { $sha.Dispose() }
            $expected = @($inventory | Where-Object { $_.path -ceq $path })
            if ($expected.Count -ne 1 -or $expected[0].sha256 -cne $hash) { throw 'Entry integrity mismatch.' }
            $text = [Text.Encoding]::UTF8.GetString($bytes)
            Scan-Text $path $text 'zip-entry'
            if ($text -match '(?m)^[ \t]*(<{7}|={7}|>{7})([ \t].*)?$') { $scanRecords += [pscustomobject]@{ file=$path; type='Conflict marker'; status='BLOCKING' } }
            if ($text -match '(?im)^--[ \t]+(MySQL|MariaDB)[ \t]+dump|^CREATE TABLE[ \t]|^INSERT INTO[ \t]') { $scanRecords += [pscustomobject]@{ file=$path; type='Database dump content'; status='BLOCKING; source SQL strings require private review' } }
            if ($text -match '(?i)TEST_TAHAP[0-9A-Z_]*|SYNTHETIC_CANARY|TEST_STAGE[0-9]|TEST_SENTINEL') { $scanRecords += [pscustomobject]@{ file=$path; type='Synthetic/test marker'; status='BLOCKING' } }
            if ($text -match '(?i)new\s+PDO\(\s*["''][^"'']+["'']\s*,\s*["''][^"'']*["'']\s*,\s*["''][^"'']*["'']') { $scanRecords += [pscustomobject]@{ file=$path; type='Literal PDO connection credentials'; status='BLOCKING' } }
            $line = 0; $function = '(file scope)'
            foreach ($row in ($text -split "`n")) {
                $line++
                if ($row -match '^function\s+([a-zA-Z0-9_]+)\s*\(') { $function = $Matches[1] }
                if ($row -match '(?i)localhost|127\.0\.0\.1|::1|website_sekolah|DB_USERNAME|DB_PASSWORD') {
                    $localReferences += [pscustomobject]@{ file=$path; line=$line; function=$function; type='Localhost/development/DB configuration reference'; status='Context review required; values suppressed' }
                }
                if ($row -match '(?i)fixture|dummy|debug|snapshot|phpinfo|TEST_TAHAP') {
                    $markerReferences += [pscustomobject]@{ file=$path; line=$line; function=$function; type='Fixture/debug/test vocabulary'; status='Context review required; values suppressed' }
                }
            }
        }
    } finally { $zip.Dispose() }
    if (@($allow.required_source_files | Where-Object { $entryNames -cnotcontains $_ }).Count) { throw 'Missing required archive entries.' }
    [pscustomobject]@{
        zip=$zipPath; zip_sha256=$receipt.zip_sha256; entries=$entryNames.Count; archive_integrity='PASS';
        exact_allowlist='PASS'; denylist='PASS'; extraction_performed=$false; raw_values_printed=$false;
        findings=@($findings | Sort-Object file,type,status); blocking_content=$scanRecords;
        localhost_configuration_references=$localReferences; vocabulary_references=$markerReferences;
        conclusion='REVIEW_REQUIRED: detector matches are not automatically operational secrets, and known default-password detection must be reported explicitly.'
    } | ConvertTo-Json -Depth 6
} catch {
    Write-Output ('Bundle scan failed safely; type=' + $_.Exception.GetType().Name + '; line=' + $_.InvocationInfo.ScriptLineNumber + '. Values suppressed.')
    exit 1
}
