param([switch]$SelfTest)
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$findings = New-Object 'System.Collections.Generic.List[object]'
$seen = @{}

function Add-Finding([string]$file, [string]$type, [string]$origin, [string]$status) {
    $key = "$file|$type|$origin|$status"
    if (-not $seen.ContainsKey($key)) {
        $seen[$key] = $true
        $findings.Add([pscustomobject]@{ file=$file; type=$type; origin=$origin; status=$status })
    }
}

function Read-Git([string]$arguments) {
    $info = New-Object System.Diagnostics.ProcessStartInfo
    $info.FileName = 'git'; $info.Arguments = $arguments; $info.WorkingDirectory = $root
    $info.UseShellExecute = $false; $info.CreateNoWindow = $true
    $info.RedirectStandardOutput = $true; $info.RedirectStandardError = $true
    $process = [System.Diagnostics.Process]::Start($info)
    $bytes = New-Object IO.MemoryStream
    $process.StandardOutput.BaseStream.CopyTo($bytes)
    $errorText = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    if ($process.ExitCode -ne 0) { throw 'Read-only Git operation failed.' }
    $result = [Text.Encoding]::UTF8.GetString($bytes.ToArray())
    $bytes.Dispose(); $process.Dispose()
    return $result
}

function Scan-Literal([string]$file, [string]$key, [string]$value, [string]$origin) {
    $type = if ($key -match '(?i)SMTP|MAIL|EMAIL') { 'Email credential' }
        elseif ($key -match '(?i)DB_|DATABASE|DSN|username') { 'Database/account credential' }
        elseif ($key -match '(?i)SESSION') { 'Session secret' }
        elseif ($key -match '(?i)API|ACCESS.KEY') { 'API/access key' }
        elseif ($key -match '(?i)TOKEN|CSRF') { 'Token' }
        else { 'Password/secret literal' }
    $value = $value.Trim().Trim([char]34, [char]39, [char]96)
    if ($value -match '^(?:getenv|env_value|password_hash|random_bytes|bin2hex|str_repeat|configuration_string)\s*\(' -or $value.StartsWith('$')) { return }
    if ($value -eq '') { $status = 'Empty/default local or unprovisioned template; not a real secret' }
    elseif ($value -match '(?i)SYNTHETIC|SENTINEL|STAGE[0-9]|TEST_|fixture|dummy|example|invalid|nama_database|user_database|password_database|^root$|^local$') {
        $status = 'Placeholder/synthetic/local value; review context, not confirmed production credential'
    } else { $status = 'Literal detected; private context review required; rotate if used operationally' }
    Add-Finding $file $type $origin $status
}

function Scan-Text([string]$file, [string]$text, [string]$origin) {
    if ($text -match '-----BEGIN (?:RSA |EC |DSA |OPENSSH |ENCRYPTED )?PRIVATE KEY-----') {
        Add-Finding $file 'Private key' $origin 'Private-key block detected; rotate/revoke if real'
    }
    foreach ($pattern in @('AKIA[0-9A-Z]{16}', 'gh[pousr]_[A-Za-z0-9]{30,}', 'github_pat_[A-Za-z0-9_]{40,}',
        'glpat-[A-Za-z0-9_-]{20,}', 'xox[baprs]-[A-Za-z0-9-]{20,}', 'sk-(?:proj-|svcacct-)?[A-Za-z0-9_-]{30,}',
        'eyJ[A-Za-z0-9_-]{15,}\.[A-Za-z0-9_-]{15,}\.[A-Za-z0-9_-]{15,}')) {
        if ([regex]::IsMatch($text,$pattern)) { Add-Finding $file 'Provider API key/token/JWT' $origin 'Token-shaped literal detected; rotate/revoke if real' }
    }
    if ($text -match '\$2[aby]\$[0-9]{2}\$[./A-Za-z0-9]{53}') {
        Add-Finding $file 'Stored password hash' $origin 'Literal hash detected; installer legacy requires credential rotation if used'
    }
    $assignment = '(?im)["'']?(?<key>[a-z_][a-z0-9_.-]*)["'']?[ \t]*(?:=>|=|:)[ \t]*(?<q>["''])(?<value>[^"''\r\n]*)\k<q>'
    foreach ($match in [regex]::Matches($text,$assignment)) {
        if ($match.Groups['key'].Value -match '(?i)password|passwd|pwd|secret|token|api[_-]?key|access[_-]?key|private[_-]?key|^DB_USERNAME$|^SMTP_USER$|^MAIL_USERNAME$') {
            Scan-Literal $file $match.Groups['key'].Value $match.Groups['value'].Value $origin
        }
    }
    $environment = '(?im)^[ \t]*(?<key>(?:DB|SMTP|MAIL|EMAIL|SESSION|AUTH|API|AWS|APP)_[A-Z_]*(?:PASSWORD|USERNAME|SECRET|TOKEN|KEY|USER))[ \t]*=[ \t]*(?<value>[^\r\n]*)'
    foreach ($match in [regex]::Matches($text,$environment)) {
        Scan-Literal $file $match.Groups['key'].Value $match.Groups['value'].Value $origin
    }
    $resolver = '(?i)env_value\(\s*["''](?<key>[^"'']*(?:PASSWORD|USERNAME|SECRET|TOKEN|KEY))[''"]\s*,\s*["''](?<value>[^"'']*)["'']'
    foreach ($match in [regex]::Matches($text,$resolver)) { Scan-Literal $file $match.Groups['key'].Value $match.Groups['value'].Value $origin }
    $verify = 'password_verify\(\s*["''](?<value>[^"'']+)["'']'
    foreach ($match in [regex]::Matches($text,$verify)) { Scan-Literal $file 'legacy_password' $match.Groups['value'].Value $origin }
    $documentation = '(?im)^\s*[-*]?\s*(?:Password|Initial password|API key|Access token)\s*:\s*(?<value>[^\r\n]+)'
    foreach ($match in [regex]::Matches($text,$documentation)) { Scan-Literal $file 'password' $match.Groups['value'].Value $origin }
    if ($text -match '(?i)(?:mysql|postgres(?:ql)?|https?|smtp)://[^\s/:@]+:[^\s/@]+@') {
        Add-Finding $file 'Credential in connection URL' $origin 'URL userinfo detected; review context and rotate if real'
    }
    if ($file -match '(?i)(\.pfx|\.p12|\.jks|(?:^|/)id_(rsa|ed25519|ecdsa))$') {
        Add-Finding $file 'Potential private-key container' $origin 'Sensitive filename; private review required'
    }
}

try {
    if ($SelfTest) {
        Scan-Text 'synthetic-test' ('DB_PASSWORD=' + "`n" + 'NEXT_LINE=not-a-password') 'self-test'
        if ($findings.Count -ne 1 -or $findings[0].status -notlike 'Empty/default*') { throw 'Blank environment regression.' }
        Scan-Text 'synthetic-test' 'password = "SYNTHETIC_CANARY"' 'self-test'
        Scan-Text 'synthetic-test' 'token = "SYNTHETIC_CANARY"' 'self-test'
        Scan-Text 'synthetic-test' '-----BEGIN PRIVATE KEY-----' 'self-test'
        Scan-Text 'synthetic-test' ('ghp_' + ('A' * 36)) 'self-test'
        Scan-Text 'synthetic-test' 'password = "$runtimeValue"' 'self-test'
        if ($findings.Count -ne 5) { throw 'Synthetic detector regression.' }
        Write-Output 'PASS: blank environment, password, token, private-key marker, provider-token shape, dynamic-value exclusion (6 checks).'
        exit 0
    }
    $paths = (Read-Git 'ls-files --cached --others --exclude-standard').TrimEnd() -split "`n" | ForEach-Object { $_.TrimEnd("`r") } | Sort-Object -Unique
    $files = 0
    foreach ($file in $paths) {
        $path = Join-Path $root $file
        if (Test-Path -LiteralPath $path -PathType Leaf) {
            Scan-Text $file ([Text.Encoding]::UTF8.GetString([IO.File]::ReadAllBytes($path))) 'working-tree'
            $files++
        }
    }
    $indexCount = 0
    foreach ($line in ((Read-Git 'ls-files --stage') -split "`n")) {
        if ($line -match '^\d+ ([0-9a-f]{40,64}) [0-3]\t(.+)$') {
            Scan-Text $Matches[2].TrimEnd("`r") (Read-Git ('cat-file blob ' + $Matches[1])) 'index'
            $indexCount++
        }
    }
    $commits = (Read-Git 'rev-list --all').TrimEnd() -split "`n"
    $historicalPaths = @{}
    foreach ($commit in $commits) {
        $commit = $commit.TrimEnd("`r")
        if ($commit -notmatch '^[0-9a-f]{40,64}$') { throw 'Invalid history identifier.' }
        Scan-Text '(Git commit messages)' (Read-Git ('show -s --format=%B ' + $commit)) 'history'
        foreach ($line in ((Read-Git ('ls-tree -r ' + $commit)) -split "`n")) {
            if ($line -match '^\d+ blob ([0-9a-f]{40,64})\t(.+)$') {
                $blob=$Matches[1]; $file=$Matches[2].TrimEnd("`r"); $key="$blob|$file"
                if (-not $historicalPaths.ContainsKey($key)) {
                    $historicalPaths[$key]=$true; Scan-Text $file (Read-Git ('cat-file blob ' + $blob)) 'history'
                }
            }
        }
    }
    [pscustomobject]@{ working_tree_files=$files; index_entries=$indexCount; reachable_commits=$commits.Count;
        history_blob_paths=$historicalPaths.Count; staged_changes=((Read-Git 'diff --cached --name-only').Trim() -ne '');
        findings=@($findings | Sort-Object file,type,origin,status) } | ConvertTo-Json -Depth 5
} catch {
    Write-Output ('Secret audit stopped safely at scanner line ' + $_.InvocationInfo.ScriptLineNumber + '; type=' + $_.Exception.GetType().Name + '. Values suppressed.')
    exit 1
}
