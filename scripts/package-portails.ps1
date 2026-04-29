# IPEC - Genere 3 ZIP statiques distincts pour n0c (Windows PowerShell).
#
#   dist\site.zip   -> public_html\                (www.ipec.school)
#   dist\admin.zip  -> docroot admin.ipec.school
#   dist\lms.zip    -> docroot lms.ipec.school
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
$DIST  = Join-Path $ROOT "dist"
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

    $outputPublic = Join-Path $ROOT ".output\public"
    $distFolder   = Join-Path $ROOT "dist"
    if (Test-Path $outputPublic) { return $outputPublic }
    if (Test-Path $distFolder)   { return $distFolder }
    throw "Aucune sortie de build trouvee pour $Target"
}

function Move-BuildOutput {
    param([string]$BuildOutput, [string]$Dest)
    if (Test-Path $Dest) { Remove-Item $Dest -Recurse -Force }
    New-Item -ItemType Directory -Path $Dest -Force | Out-Null
    Get-ChildItem -Path $BuildOutput -Force | ForEach-Object {
        Copy-Item $_.FullName $Dest -Recurse -Force
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
Move-BuildOutput -BuildOutput $out -Dest $SITE

# Backend PHP du site public
Copy-Item (Join-Path $PUB "mailer.php")        $SITE
Copy-Item (Join-Path $PUB "db_config.php")     $SITE
Copy-Item (Join-Path $PUB "verify.php")        $SITE
Copy-Item (Join-Path $PUB "_pdf_classes.php")  $SITE
Copy-Item (Join-Path $PUB "_shared\cors.php")  $SITE
Copy-Item (Join-Path $PUB "FPDF")              $SITE -Recurse
Copy-Item (Join-Path $PUB "PHPMailer")         $SITE -Recurse

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
Write-Host "==> dist\site.zip OK"

# -------------------------------------------------------------------
# 2) admin.zip - admin.ipec.school  (STATIC_BUILD=admin)
# -------------------------------------------------------------------
$out = Invoke-TargetBuild -Target "admin"
$ADMIN = Join-Path $BUILD "admin"
Move-BuildOutput -BuildOutput $out -Dest $ADMIN

$adminApi    = Join-Path $ADMIN "api"
$adminShared = Join-Path $adminApi "_shared"
New-Item -ItemType Directory -Path $adminShared -Force | Out-Null

Copy-Item (Join-Path $PUB "admin-api\*.php")        $adminApi
Copy-Item (Join-Path $PUB "db_config.php")          $adminShared
Copy-Item (Join-Path $PUB "mailer.php")             $adminShared
Copy-Item (Join-Path $PUB "_pdf_classes.php")       $adminShared
Copy-Item (Join-Path $PUB "_shared\cors.php")       $adminShared
Copy-Item (Join-Path $PUB "admin\_etudiants.php")   $adminShared
Copy-Item (Join-Path $PUB "FPDF")                   $adminShared -Recurse
Copy-Item (Join-Path $PUB "PHPMailer")              $adminShared -Recurse

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
Write-Host "==> dist\admin.zip OK"

# -------------------------------------------------------------------
# 3) lms.zip - lms.ipec.school  (STATIC_BUILD=etu)
# -------------------------------------------------------------------
$out = Invoke-TargetBuild -Target "etu"
$LMS = Join-Path $BUILD "lms"
Move-BuildOutput -BuildOutput $out -Dest $LMS

$lmsApi    = Join-Path $LMS "api"
$lmsShared = Join-Path $lmsApi "_shared"
New-Item -ItemType Directory -Path $lmsShared -Force | Out-Null

Copy-Item (Join-Path $PUB "etudiant-api\*.php") $lmsApi
Copy-Item (Join-Path $PUB "db_config.php")      $lmsShared
Copy-Item (Join-Path $PUB "mailer.php")         $lmsShared
Copy-Item (Join-Path $PUB "_pdf_classes.php")   $lmsShared
Copy-Item (Join-Path $PUB "_shared\cors.php")   $lmsShared
Copy-Item (Join-Path $PUB "FPDF")               $lmsShared -Recurse
Copy-Item (Join-Path $PUB "PHPMailer")          $lmsShared -Recurse

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
Write-Host "==> dist\lms.zip OK"

Get-ChildItem $DIST | Format-Table Name, Length

Write-Host ""
Write-Host "Prochaines etapes manuelles sur n0c :"
Write-Host "  1) site.zip  -> public_html\                          (www.ipec.school)"
Write-Host "  2) admin.zip -> docroot admin.ipec.school"
Write-Host "  3) lms.zip   -> docroot lms.ipec.school"
Write-Host "  4) Creer admin\api\_shared\admin_users.php :"
Write-Host "       <?php return ['admin' => password_hash('MOT_DE_PASSE', PASSWORD_BCRYPT)];"
Write-Host "  5) Creer ..\.ipec-mailer.env (hors public_html) avec credentials SMTP"
