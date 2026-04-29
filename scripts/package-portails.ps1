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

function Invoke-Build {
    param([string]$Target)
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
}

function Get-BuildOutputDir {
    $outputPublic = Join-Path $ROOT ".output\public"
    $distFolder   = Join-Path $ROOT "dist"
    if (Test-Path $outputPublic) { return $outputPublic }
    if (Test-Path $distFolder)   { return $distFolder }
    throw "Aucune sortie de build trouvee"
}

# Copie le bundle, mais en ne gardant que les .html "autorises" a la racine.
# Les assets JS/CSS/img (sous _build, assets, etc.) sont toujours copies tels quels.
function Copy-BundleFiltered {
    param(
        [string]$Source,
        [string]$Dest,
        [string[]]$AllowedHtml  # noms de fichiers HTML autorises a la racine, ex: @("index.html","login.html")
    )
    New-Item -ItemType Directory -Path $Dest -Force | Out-Null

    # 1) tous les sous-dossiers (assets etc.) : copie integrale
    Get-ChildItem -Path $Source -Directory | ForEach-Object {
        Copy-Item $_.FullName $Dest -Recurse -Force
    }
    # 2) fichiers a la racine : on filtre les .html
    Get-ChildItem -Path $Source -File | ForEach-Object {
        $name = $_.Name
        if ($name -like "*.html") {
            if ($AllowedHtml -contains $name) {
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
# 1) site.zip - www.ipec.school  (toutes les pages publiques prerendues)
# -------------------------------------------------------------------
$SITE = Join-Path $BUILD "site"
Invoke-Build -Target "site"
$siteOut = Get-BuildOutputDir
# Pour le site : on garde TOUS les .html (toutes les pages publiques sont prerendues)
New-Item -ItemType Directory -Path $SITE -Force | Out-Null
Copy-Item "$siteOut\*" $SITE -Recurse -Force

Copy-Item (Join-Path $PUB "mailer.php")        $SITE
Copy-Item (Join-Path $PUB "db_config.php")     $SITE
Copy-Item (Join-Path $PUB "verify.php")        $SITE
Copy-Item (Join-Path $PUB "_pdf_classes.php")  $SITE
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

<FilesMatch "(^db_config\.php$|^_etudiants\.php$|^_pdf_classes\.php$)">
  Require all denied
</FilesMatch>
"@
Write-Utf8NoBom (Join-Path $SITE ".htaccess") $siteHt

Zip-Folder -Source $SITE -ZipPath (Join-Path $DIST "site.zip")
Write-Host "==> dist\site.zip OK"

# -------------------------------------------------------------------
# 2) admin.zip - admin.ipec.school  (seul login.html prerendu, reste = SPA)
# -------------------------------------------------------------------
$ADMIN = Join-Path $BUILD "admin"
Invoke-Build -Target "admin"
$adminOut = Get-BuildOutputDir
# On copie uniquement les HTML utiles : index.html (SPA fallback) + admin/login si present
Copy-BundleFiltered -Source $adminOut -Dest $ADMIN -AllowedHtml @("index.html", "200.html", "404.html")

# Si TanStack a emis admin/login.html dans un sous-dossier, on le garde tel quel via la copie des dossiers,
# sinon le fallback SPA prendra le relais.

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
# 3) lms.zip - lms.ipec.school  (login + mot-de-passe-oublie prerendus, reste = SPA)
# -------------------------------------------------------------------
$LMS = Join-Path $BUILD "lms"
Invoke-Build -Target "etu"
$lmsOut = Get-BuildOutputDir
Copy-BundleFiltered -Source $lmsOut -Dest $LMS -AllowedHtml @("index.html", "200.html", "404.html")

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
