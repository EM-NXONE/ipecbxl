#!/usr/bin/env bash
# IPEC — Génère 3 ZIP distincts pour n0c (Apache/PHP, sans Node.js sur le serveur).
#
#   packages/site.zip   → public_html/                (www.ipec.school)
#   packages/admin.zip  → docroot admin.ipec.school
#   packages/lms.zip    → docroot lms.ipec.school
#
# Stratégie : 3 builds Vite distincts via STATIC_BUILD (lu par vite.config.ts).
# Sortie réelle = dist/client/ (HTML + assets) + dist/server/ (SSR, jeté).
# Le dossier des ZIP s'appelle "packages/" pour ne PAS être écrasé par vite.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/packages"
PUB="$ROOT/public"
BUILD="$ROOT/dist-build"

rm -rf "$DIST" "$BUILD"
mkdir -p "$DIST" "$BUILD"

build_target() {
    local target="$1"
    echo "" >&2
    echo "==> Build STATIC_BUILD=$target" >&2
    rm -rf "$ROOT/dist" "$ROOT/.output"
    (cd "$ROOT" && STATIC_BUILD="$target" npx vite build >&2)
    if   [ -d "$ROOT/dist/client" ];    then echo "$ROOT/dist/client"
    elif [ -d "$ROOT/.output/public" ]; then echo "$ROOT/.output/public"
    else echo "ERREUR: aucune sortie de build pour $target" >&2; return 1
    fi
}

# move_output <src> <dest> "<allowed_html>" "<forbidden_dirs>"
# allowed_html = "*" pour tout garder, sinon liste de basenames sans .html
# forbidden_dirs = liste de noms de sous-dossiers a exclure
move_output() {
    local src="$1"; local dest="$2"; local allowed="$3"; local forbidden="$4"
    rm -rf "$dest"; mkdir -p "$dest"
    for d in "$src"/*/; do
        [ -d "$d" ] || continue
        local name; name="$(basename "$d")"
        if echo " $forbidden " | grep -q " $name "; then continue; fi
        cp -R "$d" "$dest/"
    done
    for f in "$src"/*; do
        [ -f "$f" ] || continue
        local name; name="$(basename "$f")"
        case "$name" in
            *.html)
                if [ "$allowed" = "*" ]; then cp "$f" "$dest/"; continue; fi
                local base="${name%.html}"
                if echo " $allowed " | grep -q " $base "; then cp "$f" "$dest/"; fi
                ;;
            *) cp "$f" "$dest/" ;;
        esac
    done
    # fichiers caches a la racine (ex: .vite, etc) - skip
}

# Purge un sous-dossier de portail (etudiant/ ou admin/) en gardant UNIQUEMENT
# les index.html prerendus et en supprimant tout le legacy PHP venu de public/.
purge_portal_subdir() {
    local folder="$1"
    [ -d "$folder" ] || return 0
    find "$folder" -type f ! -name "index.html" -delete
    find "$folder" -depth -type d -empty -delete
}

# Whitelist racine : ne garde que les entrees explicitement listees.
# Usage : restrict_portal_root <folder> "name1 name2 name3"
restrict_portal_root() {
    local folder="$1"; local keep=" $2 "
    [ -d "$folder" ] || return 0
    for entry in "$folder"/* "$folder"/.[!.]*; do
        [ -e "$entry" ] || continue
        local name; name="$(basename "$entry")"
        if ! echo "$keep" | grep -q " $name "; then
            rm -rf "$entry"
        fi
    done
}

# Liste des fichiers du SITE qui n'ont rien a faire dans admin/lms
SITE_ONLY_FILES="mailer.php verify.php cors.php db_config.php schema.sql _pdf_classes.php sitemap.xml robots.txt ipec-logo-email.png android-chrome-192x192.png android-chrome-512x512.png apple-touch-icon.png favicon-16x16.png favicon-32x32.png favicon-96x96.png site.webmanifest"

# ---------------------------------------------------------------------------
# 1) site.zip — www.ipec.school
# ---------------------------------------------------------------------------
OUT="$(build_target site)"
SITE="$BUILD/site"
move_output "$OUT" "$SITE" "*" "admin etudiant"

cp "$PUB/mailer.php"        "$SITE/"
cp "$PUB/db_config.php"     "$SITE/"
cp "$PUB/verify.php"        "$SITE/"
cp "$PUB/_pdf_classes.php"  "$SITE/"
cp "$PUB/_shared/cors.php"  "$SITE/"
cp -R "$PUB/FPDF"           "$SITE/"
cp -R "$PUB/PHPMailer"      "$SITE/"

cat > "$SITE/.htaccess" <<'HT'
# IPEC — www.ipec.school
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(mailer\.php|verify\.php|FPDF/|PHPMailer/) - [L]
RewriteRule ^ index.html [L]

<FilesMatch "(^db_config\.php$|^_pdf_classes\.php$|^cors\.php$)">
  Require all denied
</FilesMatch>
HT

(cd "$SITE" && zip -rq "$DIST/site.zip" .)
echo "==> packages/site.zip OK"

# ---------------------------------------------------------------------------
# 2) admin.zip — admin.ipec.school
# ---------------------------------------------------------------------------
OUT="$(build_target admin)"
ADMIN="$BUILD/admin"
# garde uniquement assets/, _build/ et admin/. Vire etudiant/ et tout autre.
forbid=""
for d in "$OUT"/*/; do
    [ -d "$d" ] || continue
    name="$(basename "$d")"
    case "$name" in admin|assets|_build) ;; *) forbid="$forbid $name" ;; esac
done
move_output "$OUT" "$ADMIN" "index 404 200" "$forbid"

restrict_portal_root "$ADMIN" "admin assets _build index.html favicon.ico favicon.svg"
purge_portal_subdir "$ADMIN/admin"
cat > "$ADMIN/index.html" <<'HTML'
<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">
<title>IPEC Admin</title>
<meta http-equiv="refresh" content="0; url=/admin/login">
<link rel="canonical" href="/admin/login"></head>
<body><script>location.replace('/admin/login')</script>
<a href="/admin/login">Acceder a l'espace admin</a></body></html>
HTML

mkdir -p "$ADMIN/api/_shared"
cp "$PUB/admin-api/"*.php       "$ADMIN/api/"
cp "$PUB/db_config.php"         "$ADMIN/api/_shared/"
cp "$PUB/mailer.php"            "$ADMIN/api/_shared/"
cp "$PUB/_pdf_classes.php"      "$ADMIN/api/_shared/"
cp "$PUB/_shared/cors.php"      "$ADMIN/api/_shared/"
cp "$PUB/admin/_etudiants.php"  "$ADMIN/api/_shared/"
cp -R "$PUB/FPDF"               "$ADMIN/api/_shared/"
cp -R "$PUB/PHPMailer"          "$ADMIN/api/_shared/"

cat > "$ADMIN/.htaccess" <<'HT'
# IPEC — admin.ipec.school
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^api/ - [L]
RewriteRule ^ index.html [L]
HT
echo "Require all denied" > "$ADMIN/api/_shared/.htaccess"

(cd "$ADMIN" && zip -rq "$DIST/admin.zip" .)
echo "==> packages/admin.zip OK"

# ---------------------------------------------------------------------------
# 3) lms.zip — lms.ipec.school
# ---------------------------------------------------------------------------
OUT="$(build_target etu)"
LMS="$BUILD/lms"
forbid=""
for d in "$OUT"/*/; do
    [ -d "$d" ] || continue
    name="$(basename "$d")"
    case "$name" in etudiant|assets|_build) ;; *) forbid="$forbid $name" ;; esac
done
move_output "$OUT" "$LMS" "index 404 200" "$forbid"

restrict_portal_root "$LMS" "etudiant assets _build index.html favicon.ico favicon.svg"
purge_portal_subdir "$LMS/etudiant"
cat > "$LMS/index.html" <<'HTML'
<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">
<title>IPEC LMS</title>
<meta http-equiv="refresh" content="0; url=/etudiant/login">
<link rel="canonical" href="/etudiant/login"></head>
<body><script>location.replace('/etudiant/login')</script>
<a href="/etudiant/login">Acceder a l'espace etudiant</a></body></html>
HTML

mkdir -p "$LMS/api/_shared"
cp "$PUB/etudiant-api/"*.php "$LMS/api/"
cp "$PUB/db_config.php"      "$LMS/api/_shared/"
cp "$PUB/mailer.php"         "$LMS/api/_shared/"
cp "$PUB/_pdf_classes.php"   "$LMS/api/_shared/"
cp "$PUB/_shared/cors.php"   "$LMS/api/_shared/"
cp -R "$PUB/FPDF"            "$LMS/api/_shared/"
cp -R "$PUB/PHPMailer"       "$LMS/api/_shared/"

cat > "$LMS/.htaccess" <<'HT'
# IPEC — lms.ipec.school
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^api/ - [L]
RewriteRule ^ index.html [L]
HT
echo "Require all denied" > "$LMS/api/_shared/.htaccess"

(cd "$LMS" && zip -rq "$DIST/lms.zip" .)
echo "==> packages/lms.zip OK"

ls -lh "$DIST"
echo
echo "Prochaines étapes manuelles sur n0c :"
echo "  1) site.zip  → public_html/                          (www.ipec.school)"
echo "  2) admin.zip → docroot admin.ipec.school"
echo "  3) lms.zip   → docroot lms.ipec.school"
echo "  4) Créer admin/api/_shared/admin_users.php"
echo "  5) Créer ../.ipec-mailer.env (hors public_html) avec credentials SMTP"
