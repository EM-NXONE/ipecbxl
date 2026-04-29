#!/usr/bin/env bash
# IPEC — Génère 3 ZIP prêts à uploader sur n0c (Apache/PHP, sans Node.js).
#
#   dist/site.zip   → public_html/                (www.ipec.school)
#   dist/admin.zip  → docroot admin.ipec.school
#   dist/lms.zip    → docroot lms.ipec.school
#
# Chaque cible est PRÉRENDUE STATIQUEMENT (HTML pré-généré pour les pages
# publiques) via STATIC_BUILD=site|admin|etu. Les pages authentifiées
# fonctionnent en SPA via fallback .htaccess → index.html.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/dist"
PUB="$ROOT/public"
BUILD="$ROOT/dist-build"

rm -rf "$DIST" "$BUILD"
mkdir -p "$DIST" "$BUILD"

build_static() {
    local target="$1"   # site | admin | etu
    local outdir="$2"   # dossier où copier le résultat
    echo "==> Build statique [$target]"
    rm -rf "$ROOT/dist" "$ROOT/.output"
    (cd "$ROOT" && STATIC_BUILD="$target" npm run build >/dev/null)
    mkdir -p "$outdir"
    if [ -d "$ROOT/.output/public" ]; then
        cp -R "$ROOT/.output/public/." "$outdir/"
    elif [ -d "$ROOT/dist" ]; then
        cp -R "$ROOT/dist/." "$outdir/"
    else
        echo "ERREUR: pas de sortie de build trouvée pour $target" >&2
        exit 1
    fi
}

# ---------------------------------------------------------------------------
# 1) site.zip — www.ipec.school
# ---------------------------------------------------------------------------
SITE="$BUILD/site"
build_static site "$SITE"

cp "$PUB/mailer.php"       "$SITE/"
cp "$PUB/db_config.php"    "$SITE/"
cp "$PUB/verify.php"       "$SITE/"
cp "$PUB/_pdf_classes.php" "$SITE/"
cp -R "$PUB/FPDF"          "$SITE/"
cp -R "$PUB/PHPMailer"     "$SITE/"
# Pages PHP legacy en fallback
cp -R "$PUB/admin"    "$SITE/"
cp -R "$PUB/etudiant" "$SITE/"

cat > "$SITE/.htaccess" <<'HT'
# IPEC — www.ipec.school — pages prerendues + fallback SPA + endpoints PHP
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(mailer\.php|verify\.php|admin(/|$)|etudiant(/|$)|FPDF/|PHPMailer/) - [L]
RewriteRule ^ index.html [L]

<FilesMatch "(^db_config\.php$|^_etudiants\.php$|^_pdf_classes\.php$)">
  Require all denied
</FilesMatch>
HT

(cd "$SITE" && zip -rq "$DIST/site.zip" .)
echo "==> dist/site.zip OK"

# ---------------------------------------------------------------------------
# 2) admin.zip — admin.ipec.school
# ---------------------------------------------------------------------------
ADMIN="$BUILD/admin"
build_static admin "$ADMIN"
mkdir -p "$ADMIN/api/_shared"

cp "$PUB/admin-api/"*.php       "$ADMIN/api/"
cp "$PUB/db_config.php"         "$ADMIN/api/_shared/"
cp "$PUB/mailer.php"            "$ADMIN/api/_shared/"
cp "$PUB/_pdf_classes.php"      "$ADMIN/api/_shared/"
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

cat > "$ADMIN/api/_shared/.htaccess" <<'HT'
Require all denied
HT

(cd "$ADMIN" && zip -rq "$DIST/admin.zip" .)
echo "==> dist/admin.zip OK"

# ---------------------------------------------------------------------------
# 3) lms.zip — lms.ipec.school
# ---------------------------------------------------------------------------
LMS="$BUILD/lms"
build_static etu "$LMS"
mkdir -p "$LMS/api/_shared"

cp "$PUB/etudiant-api/"*.php "$LMS/api/"
cp "$PUB/db_config.php"      "$LMS/api/_shared/"
cp "$PUB/mailer.php"         "$LMS/api/_shared/"
cp "$PUB/_pdf_classes.php"   "$LMS/api/_shared/"
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

cat > "$LMS/api/_shared/.htaccess" <<'HT'
Require all denied
HT

(cd "$LMS" && zip -rq "$DIST/lms.zip" .)
echo "==> dist/lms.zip OK"

ls -lh "$DIST"
echo
echo "Prochaines étapes manuelles sur n0c :"
echo "  1) site.zip  → public_html/                          (dézipper sur place)"
echo "  2) admin.zip → docroot du sous-domaine admin.ipec.school"
echo "  3) lms.zip   → docroot du sous-domaine lms.ipec.school"
echo "  4) Créer admin/api/_shared/admin_users.php :"
echo "       <?php return ['admin' => password_hash('MOT_DE_PASSE', PASSWORD_BCRYPT)];"
echo "  5) Créer ../.ipec-mailer.env (hors public_html) avec les credentials SMTP"
