$ErrorActionPreference = 'Stop'

$files = @(
    'C:\Program Files (x86)\Dapodik\webserver\conf\extra\httpd-ssl.conf',
    'C:\Program Files (x86)\Dapodik\webserver\conf\extra\httpd-sni.conf'
)

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
foreach ($file in $files) {
    Copy-Item -LiteralPath $file -Destination "$file.bak-$stamp"
}

$ssl = $files[0]
$sni = $files[1]

(Get-Content -LiteralPath $ssl -Raw).
    Replace('Listen 443', 'Listen 5443').
    Replace('<VirtualHost _default_:443>', '<VirtualHost _default_:5443>').
    Replace('ServerName www.example.com:443', 'ServerName www.example.com:5443') |
    Set-Content -LiteralPath $ssl -NoNewline

(Get-Content -LiteralPath $sni -Raw).
    Replace('Listen 443 https', 'Listen 5443 https').
    Replace('<VirtualHost _default_:443>', '<VirtualHost _default_:5443>').
    Replace('ServerName localhost:443', 'ServerName localhost:5443').
    Replace('<VirtualHost *:443>', '<VirtualHost *:5443>').
    Replace('ServerName serverone.tld:443', 'ServerName serverone.tld:5443').
    Replace('ServerName servertwo.tld:443', 'ServerName servertwo.tld:5443') |
    Set-Content -LiteralPath $sni -NoNewline

& 'C:\Program Files (x86)\Dapodik\webserver\bin\httpd.exe' -t

Write-Host ''
Write-Host 'Port SSL Dapodik berhasil diubah dari 443 ke 5443.'
Write-Host "Backup dibuat dengan suffix .bak-$stamp"
Read-Host 'Tekan Enter untuk menutup'
