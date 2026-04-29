# IPEC - Genere 3 ZIP statiques distincts pour n0c (Windows PowerShell).
#
#   packages\site.zip   -> public_html\                (www.ipec.school)
#   packages\admin.zip  -> docroot admin.ipec.school
#   packages\lms.zip    -> docroot lms.ipec.school
#
# Strategie : 3 builds Vite distincts via la variable STATIC_BUILD lue par
# vite.config.ts. Chaque build prerend uniquement les routes du portail
# concerne (cf. SITE_ROUTES / ADMIN_PUBLIC_ROUTES / ETU_PUBLIC_ROUTES dans
# vite.config.ts). Ainsi :
#   - site  -> /, /admissions, /cgu, ... prerendus en .html a la racine
#   - admin -> /admin/login prerendu, le reste en SPA fallback
#   - etu   -> /etudiant/login + /etudiant/mot-de-passe-oublie prerendus
#
# Usage :  powershell -ExecutionPolicy Bypass -File scripts\package-portails.ps1

$ErrorActionPreference = "Stop"

$ROOT  = (Resolve-Path "$PSScriptRoot\..").Path
$DIST  = Join-Path $ROOT "packages"   # IMPORTANT: pas "dist" car vite build ecrit dans dist\
$PUB   = Join-Path $ROOT "public"
$BUILD = Join-Path $ROOT "dist-build"

if (Test-Path $DIST)  { Remove-Item $DIST  -Recurse -Force }
if (Test-Path $BUILD) { Remove-Item $BUILD -Recurse -Force }
New-Item -ItemType Directory -Path $DIST, $BUILD | Out-Null

function Invoke-TargetBuild {
    param([string]$Target)
    Write-Host ""
    Write-Host "==> Build STATIC_BUILD=$Target"
    if (Test-Path (Join-Path $ROOT "dist"))    { Remove-Item (Join-Path $ROOT "dist")    -Recurse -Force }
    if (Test-Path (Join-Path $ROOT ".output")) { Remove-Item (Join-Path $ROOT ".output") -Recurse -Force }
    Push-Location $ROOT
    try {
        $env:STATIC_BUILD = $Target
        & npx vite build | Out-Host
        if ($LASTEXITCODE -ne 0) { throw "vite build ($Target) a echoue" }
    } finally {
        Remove-Item Env:STATIC_BUILD -ErrorAction SilentlyContinue
        Pop-Location
    }

    # TanStack Start v1 : sortie client = dist/client/, SSR = dist/server/ (jete).
    $distClient   = Join-Path $ROOT "dist\client"
    $outputPublic = Join-Path $ROOT ".output\public"
    if (Test-Path $distClient)   { return $distClient }
    if (Test-Path $outputPublic) { return $outputPublic }
    throw "Aucune sortie de build trouvee pour $Target (ni dist\client ni .output\public)"
}

function Move-BuildOutput {
    param([string]$BuildOutput, [string]$Dest, [string[]]$AllowedHtml, [string[]]$ForbiddenSubdirs = @())
    if (Test-Path $Dest) { Remove-Item $Dest -Recurse -Force }
    New-Item -ItemType Directory -Path $Dest -Force | Out-Null
    # Sous-dossiers : tout sauf ceux interdits
    Get-ChildItem -Path $BuildOutput -Directory -Force | ForEach-Object {
        if ($ForbiddenSubdirs -notcontains $_.Name) {
            Copy-Item $_.FullName $Dest -Recurse -Force
        }
    }
    # Fichiers racine : .html filtres, le reste copie tel quel
    Get-ChildItem -Path $BuildOutput -File -Force | ForEach-Object {
        $name = $_.Name
        if ($name -like "*.html") {
            $base = [System.IO.Path]::GetFileNameWithoutExtension($name)
            if ($AllowedHtml -contains $base -or $AllowedHtml -contains "*") {
                Copy-Item $_.FullName (Join-Path $Dest $name) -Force
            }
        } else {
            Copy-Item $_.FullName (Join-Path $Dest $name) -Force
        }
    }
}

function Write-Utf8NoBom {
    param([string]$Path, [string]$Content)
    [System.IO.File]::WriteAllText($Path, $Content, (New-Object System.Text.UTF8Encoding $false))
}

function Zip-Folder {
    param([string]$Source, [string]$ZipPath)
    if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }
    Compress-Archive -Path (Join-Path $Source "*") -DestinationPath $ZipPath -Force
}

# -------------------------------------------------------------------
# 1) site.zip - www.ipec.school  (STATIC_BUILD=site)
# -------------------------------------------------------------------
$out = Invoke-TargetBuild -Target "site"
$SITE = Join-Path $BUILD "site"
# Site public : exclut les portails ET les dossiers backend reserves aux autres ZIPs.
Move-BuildOutput -BuildOutput $out -Dest $SITE -AllowedHtml @("*") -ForbiddenSubdirs @("admin","etudiant","admin-api","etudiant-api","_shared")

# Nettoie les fichiers PHP/SQL deposes par Vite a la racine et qui ne servent qu'au site
foreach ($f in @("cors.php","schema.sql","admin","etudiant")) {
    $p = Join-Path $SITE $f
    if (Test-Path $p) { Remove-Item $p -Recurse -Force }
}

# Backend PHP du site public (Force pour ecraser ce que le build aurait copie depuis public/)
Copy-Item (Join-Path $PUB "mailer.php")        $SITE -Force
Copy-Item (Join-Path $PUB "db_config.php")     $SITE -Force
Copy-Item (Join-Path $PUB "verify.php")        $SITE -Force
Copy-Item (Join-Path $PUB "_pdf_classes.php")  $SITE -Force
if (Test-Path (Join-Path $SITE "FPDF"))      { Remove-Item (Join-Path $SITE "FPDF")      -Recurse -Force }
if (Test-Path (Join-Path $SITE "PHPMailer")) { Remove-Item (Join-Path $SITE "PHPMailer") -Recurse -Force }
Copy-Item (Join-Path $PUB "FPDF")              $SITE -Recurse -Force
Copy-Item (Join-Path $PUB "PHPMailer")         $SITE -Recurse -Force

$siteHt = @"
# IPEC - www.ipec.school
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(mailer\.php|verify\.php|FPDF/|PHPMailer/) - [L]
RewriteRule ^ index.html [L]

<FilesMatch "(^db_config\.php$|^_pdf_classes\.php$|^cors\.php$)">
  Require all denied
</FilesMatch>
"@
Write-Utf8NoBom (Join-Path $SITE ".htaccess") $siteHt

Zip-Folder -Source $SITE -ZipPath (Join-Path $DIST "site.zip")
Write-Host "==> packages\site.zip OK"

# -------------------------------------------------------------------
# 2) admin.zip - admin.ipec.school  (STATIC_BUILD=admin)
# -------------------------------------------------------------------
$out = Invoke-TargetBuild -Target "admin"
$ADMIN = Join-Path $BUILD "admin"
# Admin : on garde uniquement assets + sous-dossier admin/. On vire tout le reste.
$forbidAdmin = @()
Get-ChildItem -Path $out -Directory -Force | ForEach-Object {
    if ($_.Name -ne "admin" -and $_.Name -ne "assets" -and $_.Name -ne "_build") {
        $forbidAdmin += $_.Name
    }
}
# Garde uniquement index.html (SPA fallback) et 404/200 a la racine.
Move-BuildOutput -BuildOutput $out -Dest $ADMIN -AllowedHtml @("index","404","200") -ForbiddenSubdirs $forbidAdmin

# Nettoie les fichiers PHP/SQL deposes par Vite depuis public/ (reserves au site public)
foreach ($f in @("mailer.php","verify.php","cors.php","db_config.php","schema.sql","_pdf_classes.php","sitemap.xml","robots.txt")) {
    $p = Join-Path $ADMIN $f
    if (Test-Path $p) { Remove-Item $p -Force }
}

$adminApi    = Join-Path $ADMIN "api"
$adminShared = Join-Path $adminApi "_shared"
New-Item -ItemType Directory -Path $adminShared -Force | Out-Null

Copy-Item (Join-Path $PUB "admin-api\*.php")        $adminApi    -Force
Copy-Item (Join-Path $PUB "db_config.php")          $adminShared -Force
Copy-Item (Join-Path $PUB "mailer.php")             $adminShared -Force
Copy-Item (Join-Path $PUB "_pdf_classes.php")       $adminShared -Force
Copy-Item (Join-Path $PUB "_shared\cors.php")       $adminShared -Force
Copy-Item (Join-Path $PUB "admin\_etudiants.php")   $adminShared -Force
Copy-Item (Join-Path $PUB "FPDF")                   $adminShared -Recurse -Force
Copy-Item (Join-Path $PUB "PHPMailer")              $adminShared -Recurse -Force

$adminHt = @"
# IPEC - admin.ipec.school
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^api/ - [L]
RewriteRule ^ index.html [L]
"@
Write-Utf8NoBom (Join-Path $ADMIN ".htaccess") $adminHt
Write-Utf8NoBom (Join-Path $adminShared ".htaccess") "Require all denied`r`n"

Zip-Folder -Source $ADMIN -ZipPath (Join-Path $DIST "admin.zip")
Write-Host "==> packages\admin.zip OK"

# -------------------------------------------------------------------
# 3) lms.zip - lms.ipec.school  (STATIC_BUILD=etu)
# -------------------------------------------------------------------
$out = Invoke-TargetBuild -Target "etu"
$LMS = Join-Path $BUILD "lms"
$forbidLms = @()
Get-ChildItem -Path $out -Directory -Force | ForEach-Object {
    if ($_.Name -ne "etudiant" -and $_.Name -ne "assets" -and $_.Name -ne "_build") {
        $forbidLms += $_.Name
    }
}
Move-BuildOutput -BuildOutput $out -Dest $LMS -AllowedHtml @("index","404","200") -ForbiddenSubdirs $forbidLms

# Nettoie les fichiers PHP/SQL deposes par Vite depuis public/ (reserves au site public)
foreach ($f in @("mailer.php","verify.php","cors.php","db_config.php","schema.sql","_pdf_classes.php","sitemap.xml","robots.txt")) {
    $p = Join-Path $LMS $f
    if (Test-Path $p) { Remove-Item $p -Force }
}

$lmsApi    = Join-Path $LMS "api"
$lmsShared = Join-Path $lmsApi "_shared"
New-Item -ItemType Directory -Path $lmsShared -Force | Out-Null

Copy-Item (Join-Path $PUB "etudiant-api\*.php") $lmsApi    -Force
Copy-Item (Join-Path $PUB "db_config.php")      $lmsShared -Force
Copy-Item (Join-Path $PUB "mailer.php")         $lmsShared -Force
Copy-Item (Join-Path $PUB "_pdf_classes.php")   $lmsShared -Force
Copy-Item (Join-Path $PUB "_shared\cors.php")   $lmsShared -Force
Copy-Item (Join-Path $PUB "FPDF")               $lmsShared -Recurse -Force
Copy-Item (Join-Path $PUB "PHPMailer")          $lmsShared -Recurse -Force

$lmsHt = @"
# IPEC - lms.ipec.school
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^api/ - [L]
RewriteRule ^ index.html [L]
"@
Write-Utf8NoBom (Join-Path $LMS ".htaccess") $lmsHt
Write-Utf8NoBom (Join-Path $lmsShared ".htaccess") "Require all denied`r`n"

Zip-Folder -Source $LMS -ZipPath (Join-Path $DIST "lms.zip")
Write-Host "==> packages\lms.zip OK"

Get-ChildItem $DIST | Format-Table Name, Length

Write-Host ""
Write-Host "Prochaines etapes manuelles sur n0c :"
Write-Host "  1) site.zip  -> public_html\                          (www.ipec.school)"
Write-Host "  2) admin.zip -> docroot admin.ipec.school"
Write-Host "  3) lms.zip   -> docroot lms.ipec.school"
Write-Host "  4) Creer admin\api\_shared\admin_users.php :"
Write-Host "       <?php return ['admin' => password_hash('MOT_DE_PASSE', PASSWORD_BCRYPT)];"
Write-Host "  5) Creer ..\.ipec-mailer.env (hors public_html) avec credentials SMTP"
