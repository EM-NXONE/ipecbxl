#!/usr/bin/env bash
# IPEC — Génère 3 ZIP distincts pour n0c (Apache/PHP, sans Node.js sur le serveur).
#
#   dist/site.zip   → public_html/                (www.ipec.school)
#   dist/admin.zip  → docroot admin.ipec.school
#   dist/lms.zip    → docroot lms.ipec.school
#
# Stratégie : 1 seul build Vite, puis chaque ZIP filtre les .html à la racine
# pour ne garder que les pages du portail concerné. Le bundle JS est partagé
# côté client mais code-splitté par route (un admin ne charge pas les chunks
# /etudiant/* et inversement).

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/dist"
PUB="$ROOT/public"
BUILD="$ROOT/dist-build"

rm -rf "$DIST" "$BUILD"
mkdir -p "$DIST" "$BUILD"

echo "==> Build statique unique (TanStack)"
rm -rf "$ROOT/dist" "$ROOT/.output"
(cd "$ROOT" && npx vite build)

if   [ -d "$ROOT/.output/public" ]; then BUILT="$ROOT/.output/public"
elif [ -d "$ROOT/dist" ];           then BUILT="$ROOT/dist"
else echo "ERREUR: aucune sortie de build" >&2; exit 1
fi
echo "==> Build OK -> $BUILT"

# Copie filtrée : ne garde à la RACINE que les .html dont le nom (sans .html)
# est dans $2 (liste séparée par espaces). Sous-dossiers : ceux dans $3, ou
# tous si $3 = "ALL".
copy_filtered() {
    local dest="$1"; local allowed_html="$2"; local allowed_dirs="$3"
    mkdir -p "$dest"
    # sous-dossiers
    for d in "$BUILT"/*/; do
        [ -d "$d" ] || continue
        local name; name="$(basename "$d")"
        if [ "$allowed_dirs" = "ALL" ] || echo " $allowed_dirs " | grep -q " $name "; then
            cp -R "$d" "$dest/"
        fi
    done
    # fichiers racine
    for f in "$BUILT"/*; do
        [ -f "$f" ] || continue
        local name; name="$(basename "$f")"
        case "$name" in
            *.html)
                local base="${name%.html}"
                if echo " $allowed_html " | grep -q " $base "; then
                    cp "$f" "$dest/"
                fi
                ;;
            *)
                cp "$f" "$dest/"
                ;;
        esac
    done
}

SITE_HTML="index admissions cgu cgv confidentialite contact cookies inscription international mentions-legales programmes verification vie-etudiante 404 200"

# ---------------------------------------------------------------------------
# 1) site.zip — www.ipec.school
# ---------------------------------------------------------------------------
SITE="$BUILD/site"
# Tous les sous-dossiers SAUF admin/ et etudiant/
SITE_DIRS=""
for d in "$BUILT"/*/; do
    [ -d "$d" ] || continue
    name="$(basename "$d")"
    [ "$name" = "admin" ] && continue
    [ "$name" = "etudiant" ] && continue
    SITE_DIRS="$SITE_DIRS $name"
done
copy_filtered "$SITE" "$SITE_HTML" "$SITE_DIRS"

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
ADMIN="$BUILD/admin"
copy_filtered "$ADMIN" "index admin 404 200" "admin assets _build"

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
LMS="$BUILD/lms"
copy_filtered "$LMS" "index etudiant 404 200" "etudiant assets _build"

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
