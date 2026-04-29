# IPEC - Genere 3 ZIP statiques distincts pour n0c (Windows PowerShell).
#
#   dist\site.zip   -> public_html\                (www.ipec.school)
#   dist\admin.zip  -> docroot admin.ipec.school
#   dist\lms.zip    -> docroot lms.ipec.school
#
# Strategie : 1 seul build Vite (TanStack Router code-split par route),
# puis chaque ZIP filtre les .html a la racine pour ne garder que les pages
# du portail concerne. Le bundle JS est partage cote client (chunks
# lazy-loades : un admin ne charge pas les chunks /etudiant/* et vice-versa).
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

# -------------------------------------------------------------------
# 0) Build unique
# -------------------------------------------------------------------
function Invoke-Build {
    Write-Host "==> Build statique unique (TanStack)"
    if (Test-Path (Join-Path $ROOT "dist"))    { Remove-Item (Join-Path $ROOT "dist")    -Recurse -Force }
    if (Test-Path (Join-Path $ROOT ".output")) { Remove-Item (Join-Path $ROOT ".output") -Recurse -Force }
    Push-Location $ROOT
    try {
        # Utilise npx vite build directement pour eviter les soucis de PATH npm
        & npx vite build | Out-Host
        if ($LASTEXITCODE -ne 0) { throw "vite build a echoue" }
    } finally {
        Pop-Location
    }
}

function Get-BuildOutputDir {
    $outputPublic = Join-Path $ROOT ".output\public"
    $distFolder   = Join-Path $ROOT "dist"
    if (Test-Path $outputPublic) { return $outputPublic }
    if (Test-Path $distFolder)   { return $distFolder }
    throw "Aucune sortie de build trouvee"
}

# Copie un build complet vers $Dest, mais ne garde a la RACINE que les
# .html dont le nom (sans extension) figure dans $AllowedHtml.
# Les sous-dossiers (assets, _build, etudiant/, admin/, etc.) sont copies
# integralement -- TanStack peut emettre des sous-pages prerendues.
function Copy-BundleFiltered {
    param(
        [string]$Source,
        [string]$Dest,
        [string[]]$AllowedHtml,        # ex: @("index", "admissions", "contact")
        [string[]]$AllowedSubdirs = @() # ex: @() ou @("etudiant","admin"). Vide = tous gardes.
    )
    New-Item -ItemType Directory -Path $Dest -Force | Out-Null

    # 1) sous-dossiers : tous copies sauf si filtre
    Get-ChildItem -Path $Source -Directory | ForEach-Object {
        if ($AllowedSubdirs.Count -eq 0 -or $AllowedSubdirs -contains $_.Name) {
            Copy-Item $_.FullName $Dest -Recurse -Force
        }
    }
    # 2) fichiers a la racine : on filtre les .html
    Get-ChildItem -Path $Source -File | ForEach-Object {
        $name = $_.Name
        if ($name -like "*.html") {
            $base = [System.IO.Path]::GetFileNameWithoutExtension($name)
            if ($AllowedHtml -contains $base) {
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

Invoke-Build
$BUILT = Get-BuildOutputDir
Write-Host "==> Build OK -> $BUILT"

# Pages publiques (= toutes les routes /src/routes/*.tsx hors admin/etudiant)
$SITE_HTML = @(
    "index","admissions","cgu","cgv","confidentialite","contact","cookies",
    "inscription","international","mentions-legales","programmes",
    "verification","vie-etudiante","404","200"
)

# -------------------------------------------------------------------
# 1) site.zip - www.ipec.school
# -------------------------------------------------------------------
$SITE = Join-Path $BUILD "site"
# On exclut les sous-dossiers admin/ et etudiant/ du site public.
$SITE_SUBDIRS = @()
Get-ChildItem -Path $BUILT -Directory | ForEach-Object {
    if ($_.Name -ne "admin" -and $_.Name -ne "etudiant") {
        $SITE_SUBDIRS += $_.Name
    }
}
Copy-BundleFiltered -Source $BUILT -Dest $SITE -AllowedHtml $SITE_HTML -AllowedSubdirs $SITE_SUBDIRS

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
# 2) admin.zip - admin.ipec.school
# -------------------------------------------------------------------
$ADMIN = Join-Path $BUILD "admin"
# On garde uniquement assets + le sous-dossier admin/ + login.html eventuel.
$ADMIN_SUBDIRS = @("admin", "assets", "_build")
$ADMIN_HTML    = @("index","admin","404","200")
Copy-BundleFiltered -Source $BUILT -Dest $ADMIN -AllowedHtml $ADMIN_HTML -AllowedSubdirs $ADMIN_SUBDIRS

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
# 3) lms.zip - lms.ipec.school
# -------------------------------------------------------------------
$LMS = Join-Path $BUILD "lms"
$LMS_SUBDIRS = @("etudiant", "assets", "_build")
$LMS_HTML    = @("index","etudiant","404","200")
Copy-BundleFiltered -Source $BUILT -Dest $LMS -AllowedHtml $LMS_HTML -AllowedSubdirs $LMS_SUBDIRS

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
