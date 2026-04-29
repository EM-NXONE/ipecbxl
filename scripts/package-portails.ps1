# IPEC - Genere 3 ZIP prerendus statiques pour n0c (Windows PowerShell).
#
#   dist\site.zip   -> public_html\                (www.ipec.school)
#   dist\admin.zip  -> docroot admin.ipec.school
#   dist\lms.zip    -> docroot lms.ipec.school
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

function Build-Static {
    param([string]$Target, [string]$OutDir)
    Write-Host "==> Build statique [$Target]"
    if (Test-Path (Join-Path $ROOT "dist"))    { Remove-Item (Join-Path $ROOT "dist")    -Recurse -Force }
    if (Test-Path (Join-Path $ROOT ".output")) { Remove-Item (Join-Path $ROOT ".output") -Recurse -Force }
    Push-Location $ROOT
    try {
        $env:STATIC_BUILD = $Target
        & npm run build | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "npm run build a echoue pour $Target" }
    } finally {
        Remove-Item Env:STATIC_BUILD -ErrorAction SilentlyContinue
        Pop-Location
    }
    New-Item -ItemType Directory -Path $OutDir -Force | Out-Null
    $outputPublic = Join-Path $ROOT ".output\public"
    $distFolder   = Join-Path $ROOT "dist"
    if (Test-Path $outputPublic) {
        Copy-Item "$outputPublic\*" $OutDir -Recurse -Force
    } elseif (Test-Path $distFolder) {
        Copy-Item "$distFolder\*" $OutDir -Recurse -Force
    } else {
        throw "Aucune sortie de build trouvee pour $Target"
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
# 1) site.zip - www.ipec.school
# -------------------------------------------------------------------
$SITE = Join-Path $BUILD "site"
Build-Static -Target "site" -OutDir $SITE

Copy-Item (Join-Path $PUB "mailer.php")        $SITE
Copy-Item (Join-Path $PUB "db_config.php")     $SITE
Copy-Item (Join-Path $PUB "verify.php")        $SITE
Copy-Item (Join-Path $PUB "_pdf_classes.php")  $SITE
Copy-Item (Join-Path $PUB "FPDF")              $SITE -Recurse
Copy-Item (Join-Path $PUB "PHPMailer")         $SITE -Recurse
Copy-Item (Join-Path $PUB "admin")             $SITE -Recurse
Copy-Item (Join-Path $PUB "etudiant")          $SITE -Recurse

$siteHt = @"
# IPEC - www.ipec.school - pages prerendues + fallback SPA + endpoints PHP
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(mailer\.php|verify\.php|admin(/|$)|etudiant(/|$)|FPDF/|PHPMailer/) - [L]
RewriteRule ^ index.html [L]

<FilesMatch "(^db_config\.php$|^_etudiants\.php$|^_pdf_classes\.php$)">
  Require all denied
</FilesMatch>
"@
Write-Utf8NoBom (Join-Path $SITE ".htaccess") $siteHt

Zip-Folder -Source $SITE -ZipPath (Join-Path $DIST "site.zip")
Write-Host "==> dist\site.zip OK"

# -------------------------------------------------------------------
# 2) admin.zip - admin.ipec.school
# -------------------------------------------------------------------
$ADMIN = Join-Path $BUILD "admin"
Build-Static -Target "admin" -OutDir $ADMIN
$adminApi    = Join-Path $ADMIN "api"
$adminShared = Join-Path $adminApi "_shared"
New-Item -ItemType Directory -Path $adminShared -Force | Out-Null

Copy-Item (Join-Path $PUB "admin-api\*.php")        $adminApi
Copy-Item (Join-Path $PUB "db_config.php")          $adminShared
Copy-Item (Join-Path $PUB "mailer.php")             $adminShared
Copy-Item (Join-Path $PUB "_pdf_classes.php")       $adminShared
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
# 3) lms.zip - lms.ipec.school
# -------------------------------------------------------------------
$LMS = Join-Path $BUILD "lms"
Build-Static -Target "etu" -OutDir $LMS
$lmsApi    = Join-Path $LMS "api"
$lmsShared = Join-Path $lmsApi "_shared"
New-Item -ItemType Directory -Path $lmsShared -Force | Out-Null

Copy-Item (Join-Path $PUB "etudiant-api\*.php") $lmsApi
Copy-Item (Join-Path $PUB "db_config.php")      $lmsShared
Copy-Item (Join-Path $PUB "mailer.php")         $lmsShared
Copy-Item (Join-Path $PUB "_pdf_classes.php")   $lmsShared
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
Write-Host "  1) site.zip  -> public_html\"
Write-Host "  2) admin.zip -> docroot admin.ipec.school"
Write-Host "  3) lms.zip   -> docroot lms.ipec.school"
Write-Host "  4) Creer admin\api\_shared\admin_users.php :"
Write-Host "       <?php return ['admin' => password_hash('MOT_DE_PASSE', PASSWORD_BCRYPT)];"
Write-Host "  5) Creer ..\.ipec-mailer.env (hors public_html) avec credentials SMTP"
