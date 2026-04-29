#!/usr/bin/env bash
# IPEC — Génère 3 ZIP distincts pour n0c (Apache/PHP, sans Node.js sur le serveur).
#
#   dist/site.zip   → public_html/                (www.ipec.school)
#   dist/admin.zip  → docroot admin.ipec.school
#   dist/lms.zip    → docroot lms.ipec.school
#
# Stratégie : 3 builds Vite distincts via STATIC_BUILD (lu par vite.config.ts).
# Chaque build prerend les routes du portail concerné en .html.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/dist"
PUB="$ROOT/public"
BUILD="$ROOT/dist-build"

rm -rf "$DIST" "$BUILD"
mkdir -p "$DIST" "$BUILD"

build_target() {
    local target="$1"
    echo ""
    echo "==> Build STATIC_BUILD=$target"
    rm -rf "$ROOT/dist" "$ROOT/.output"
    (cd "$ROOT" && STATIC_BUILD="$target" npx vite build)
    if   [ -d "$ROOT/.output/public" ]; then echo "$ROOT/.output/public"
    elif [ -d "$ROOT/dist" ];           then echo "$ROOT/dist"
    else echo "ERREUR: aucune sortie de build pour $target" >&2; return 1
    fi
}

move_output() {
    local src="$1"; local dest="$2"
    rm -rf "$dest"
    mkdir -p "$dest"
    cp -R "$src"/. "$dest/"
}

# ---------------------------------------------------------------------------
# 1) site.zip — www.ipec.school
# ---------------------------------------------------------------------------
OUT="$(build_target site | tail -n1)"
SITE="$BUILD/site"
move_output "$OUT" "$SITE"

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
echo "==> dist/site.zip OK"

# ---------------------------------------------------------------------------
# 2) admin.zip — admin.ipec.school
# ---------------------------------------------------------------------------
OUT="$(build_target admin | tail -n1)"
ADMIN="$BUILD/admin"
move_output "$OUT" "$ADMIN"

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
echo "==> dist/admin.zip OK"

# ---------------------------------------------------------------------------
# 3) lms.zip — lms.ipec.school
# ---------------------------------------------------------------------------
OUT="$(build_target etu | tail -n1)"
LMS="$BUILD/lms"
move_output "$OUT" "$LMS"

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
echo "==> dist/lms.zip OK"

ls -lh "$DIST"
echo
echo "Prochaines étapes manuelles sur n0c :"
echo "  1) site.zip  → public_html/                          (www.ipec.school)"
echo "  2) admin.zip → docroot admin.ipec.school"
echo "  3) lms.zip   → docroot lms.ipec.school"
echo "  4) Créer admin/api/_shared/admin_users.php :"
echo "       <?php return ['admin' => password_hash('MOT_DE_PASSE', PASSWORD_BCRYPT)];"
echo "  5) Créer ../.ipec-mailer.env (hors public_html) avec credentials SMTP"
