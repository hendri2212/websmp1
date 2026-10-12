param(
    [Parameter(Mandatory=$true)][string]$OutputRoot,
    [Parameter(Mandatory=$true)][switch]$CandidateOnly,
    [switch]$IncludeUserIni
)
$ErrorActionPreference = 'Stop'
$root = [IO.Path]::GetFullPath((Split-Path -Parent $PSScriptRoot)).TrimEnd('\')
$output = [IO.Path]::GetFullPath($OutputRoot).TrimEnd('\')
$build = $null

function Assert-NoLinks([string]$path) {
    $cursor = $path
    while ($cursor) {
        if ((Test-Path -LiteralPath $cursor) -and ((Get-Item -LiteralPath $cursor -Force).Attributes -band [IO.FileAttributes]::ReparsePoint)) {
            throw 'Linked path is not allowed.'
        }
        $cursor = Split-Path -Parent $cursor
    }
}

function Assert-Allowed([string]$path) {
    if ($path -match '(^/|^[A-Za-z]:|\\|(^|/)\.\.?(/|$)|[*?]|[\x00-\x1f])') { throw 'Unsafe relative path.' }
    if (-not ($script:paths -ccontains $path)) { throw 'Unknown release path.' }
    foreach ($rule in $script:deny.rules) {
        if ($path -match $rule.path_regex) {
            $exceptions = @($script:deny.exact_control_exceptions | Where-Object {
                $_.path -ceq $path -and $_.exception_for_rule_ids -contains $rule.id
            })
            if ($exceptions.Count -eq 0 -or ($path -eq '.user.ini' -and -not $IncludeUserIni)) { throw 'Release denylist matched.' }
        }
    }
}

function Hash-Stream($stream) {
    $hash = [Security.Cryptography.SHA256]::Create()
    try { return ([BitConverter]::ToString($hash.ComputeHash($stream))).Replace('-', '').ToLowerInvariant() }
    finally { $hash.Dispose() }
}

try {
    if (-not $CandidateOnly) { throw 'Only candidate builds are supported.' }
    if ($output.Equals($root, [StringComparison]::OrdinalIgnoreCase) -or
        $output.StartsWith($root + '\', [StringComparison]::OrdinalIgnoreCase) -or
        $root.StartsWith($output + '\', [StringComparison]::OrdinalIgnoreCase)) { throw 'Output must be separate from the source tree.' }
    Assert-NoLinks $root
    Assert-NoLinks $output
    $allowPath = Join-Path $root 'RELEASE_ALLOWLIST.json'
    $denyPath = Join-Path $root 'RELEASE_DENYLIST.json'
    $allowHash = (Get-FileHash -LiteralPath $allowPath -Algorithm SHA256).Hash
    $denyHash = (Get-FileHash -LiteralPath $denyPath -Algorithm SHA256).Hash
    $allow = [IO.File]::ReadAllText($allowPath) | ConvertFrom-Json
    $script:deny = [IO.File]::ReadAllText($denyPath) | ConvertFrom-Json
    if ($allow.default_policy -ne 'deny' -or $deny.default_policy -ne 'deny') { throw 'Default deny is required.' }
    $script:paths = @($allow.required_source_files)
    if ($IncludeUserIni) {
        if (@($allow.conditional_source_files | Where-Object { $_.path -ceq '.user.ini' }).Count -ne 1) { throw 'Optional hosting control is not declared.' }
        $script:paths += '.user.ini'
    }
    if (@($paths | Sort-Object -Unique).Count -ne $paths.Count) { throw 'Duplicate release path.' }
    $sourceHashes = @{}
    foreach ($path in $paths) {
        Assert-Allowed $path
        $absolute = [IO.Path]::GetFullPath((Join-Path $root $path))
        if (-not $absolute.StartsWith($root + '\', [StringComparison]::OrdinalIgnoreCase) -or -not (Test-Path -LiteralPath $absolute -PathType Leaf)) { throw 'Missing or outside source file.' }
        Assert-NoLinks $absolute
        $sourceHashes[$path] = (Get-FileHash -LiteralPath $absolute -Algorithm SHA256).Hash.ToLowerInvariant()
    }
    $build = Join-Path $output ('school-release-candidate-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + [Guid]::NewGuid().ToString('N').Substring(0,8))
    if (Test-Path -LiteralPath $build) { throw 'Build destination already exists.' }
    [IO.Directory]::CreateDirectory($build) | Out-Null
    [IO.File]::WriteAllText((Join-Path $build 'INCOMPLETE'), 'Candidate build in progress; do not upload.')
    $payload = Join-Path $build 'payload'
    [IO.Directory]::CreateDirectory($payload) | Out-Null
    $inventory = @()
    foreach ($path in $paths) {
        $source = Join-Path $root $path
        $target = Join-Path $payload $path
        [IO.Directory]::CreateDirectory((Split-Path -Parent $target)) | Out-Null
        [IO.File]::Copy($source, $target, $false)
        $hash = (Get-FileHash -LiteralPath $target -Algorithm SHA256).Hash.ToLowerInvariant()
        if ($hash -ne $sourceHashes[$path]) { throw 'Source changed during copying.' }
        $inventory += [pscustomobject]@{ path=$path; bytes=(Get-Item -LiteralPath $target).Length; sha256=$hash }
    }
    $lintCount = 0
    $legacyDetectorCount = 0
    foreach ($entry in $inventory) {
        if (-not $entry.path.EndsWith('.php') -and -not $entry.path.EndsWith('.css')) { continue }
        $target = Join-Path $payload $entry.path
        $text = [IO.File]::ReadAllText($target)
        if ($text -match '(?m)^[ \t]*(<{7}|={7}|>{7})([ \t].*)?$') { throw 'Conflict marker found in payload.' }
        if ($text -match '-----BEGIN (RSA |EC |DSA |OPENSSH |ENCRYPTED )?PRIVATE KEY-----|AKIA[0-9A-Z]{16}|gh[pousr]_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{40,}|glpat-[A-Za-z0-9_-]{20,}|xox[baprs]-[A-Za-z0-9-]{20,}|sk-(proj-|svcacct-)?[A-Za-z0-9_-]{30,}|\$2[aby]\$[0-9]{2}\$[./A-Za-z0-9]{53}') { throw 'Potential operational secret detected; values suppressed.' }
        if ($text -match 'password_verify\(\s*["''][^"'']+["'']') {
            throw 'Literal password verification is not allowed in a release candidate; values suppressed.'
        }
        if ($entry.path.EndsWith('.php')) {
            $lintOutput = & 'C:\xampp\php\php.exe' -n -l $target 2>&1
            if ($LASTEXITCODE -ne 0) { throw 'PHP syntax validation failed; details suppressed.' }
            $lintCount++
        }
    }
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zipPath = Join-Path $build 'website-source.zip'
    $zip = [IO.Compression.ZipFile]::Open($zipPath, [IO.Compression.ZipArchiveMode]::Create)
    try { foreach ($entry in $inventory) { [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, (Join-Path $payload $entry.path), $entry.path, [IO.Compression.CompressionLevel]::Optimal) | Out-Null } }
    finally { $zip.Dispose() }
    $zip = [IO.Compression.ZipFile]::OpenRead($zipPath)
    try {
        if ($zip.Entries.Count -ne $paths.Count) { throw 'Unexpected ZIP entry count.' }
        $zipNames = @()
        foreach ($entry in $zip.Entries) {
            Assert-Allowed $entry.FullName
            if ($zipNames -ccontains $entry.FullName) { throw 'Duplicate ZIP entry.' }
            $zipNames += $entry.FullName
            $stream = $entry.Open()
            try { $hash = Hash-Stream $stream } finally { $stream.Dispose() }
            if ($hash -ne $sourceHashes[$entry.FullName]) { throw 'ZIP content mismatch.' }
        }
    } finally { $zip.Dispose() }
    foreach ($path in $paths) {
        if ((Get-FileHash -LiteralPath (Join-Path $root $path) -Algorithm SHA256).Hash.ToLowerInvariant() -ne $sourceHashes[$path]) { throw 'Source changed during build.' }
    }
    if ((Get-FileHash -LiteralPath $allowPath -Algorithm SHA256).Hash -ne $allowHash -or (Get-FileHash -LiteralPath $denyPath -Algorithm SHA256).Hash -ne $denyHash) { throw 'Policy changed during build.' }
    [IO.File]::Copy($allowPath, (Join-Path $build 'allowlist-policy.json'), $false)
    [IO.File]::Copy($denyPath, (Join-Path $build 'denylist-policy.json'), $false)
    $receipt = [pscustomobject]@{
        status='CANDIDATE_NOT_APPROVED_FOR_DEPLOYMENT'; created_utc=[DateTime]::UtcNow.ToString('o');
        output=$build; zip=$zipPath; files=$paths.Count; source_php=$lintCount; php_lint_errors=0;
        zip_sha256=(Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLowerInvariant();
        zip_matches_source=$true; denylist_violations=0; conflict_markers=0; environment_files=0;
        user_ini_included=[bool]$IncludeUserIni; production_credentials_provisioned=$false;
        known_legacy_password_detector_files=$legacyDetectorCount;
        secret_scan='Heuristic payload scan plus separate item-3 source audit; not proof of absence of all secrets.';
        pending_manual_review=@($allow.approval_required_before_bundle);
        approval_note='Built only as the requested local candidate; manual review and legacy credential rotation confirmation remain open.';
        persistent_data_included=$false; runtime_bundle_test_performed=$false; deployed=$false;
        allowlist_sha256=$allowHash.ToLowerInvariant(); denylist_sha256=$denyHash.ToLowerInvariant()
    }
    [IO.File]::WriteAllText((Join-Path $build 'file-hashes.json'), ($inventory | ConvertTo-Json -Depth 4))
    [IO.File]::WriteAllText((Join-Path $build 'build-receipt.json'), ($receipt | ConvertTo-Json -Depth 4))
    [IO.File]::WriteAllText((Join-Path $build 'BUILD-NOTES.txt'), "Upload candidate contents are payload/ or website-source.zip only. Do NOT upload this outer directory or receipts/policies. No environment, database, uploads/data, session, backup or logs are provided. Production config/DB/private storage/media require separate provisioning. Optional .user.ini omitted unless explicitly selected for compatible hosting. Pending manual reviews and legacy credential rotation remain open; no production approval. No application/DB runtime test performed.")
    [IO.File]::Delete((Join-Path $build 'INCOMPLETE'))
    $receipt | ConvertTo-Json -Depth 4
} catch {
    Write-Output ('Candidate build failed safely; type=' + $_.Exception.GetType().Name + '; line=' + $_.InvocationInfo.ScriptLineNumber + '. Values suppressed. Partial output, if any, is not approved.')
    exit 1
}
