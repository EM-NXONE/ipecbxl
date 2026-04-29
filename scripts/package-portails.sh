#!/usr/bin/env bash
# IPEC — Génère 3 ZIP prêts à uploader sur n0c :
#   dist/site.zip   → public_html/                (site principal www.ipec.school)
#   dist/admin.zip  → public_html_admin/          (admin.ipec.school)
#   dist/lms.zip    → public_html_lms/            (lms.ipec.school)
#
# Le contenu de _shared/ est COPIÉ tel quel dans admin.zip ET lms.zip
# (db_config.php, mailer.php, _pdf_classes.php, _etudiants.php, FPDF/, PHPMailer/).
# admin_users.php n'est PAS inclus : à créer à la main sur n0c.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/dist"
PUB="$ROOT/public"
BUILD="$ROOT/dist-build"

rm -rf "$DIST" "$BUILD"
mkdir -p "$DIST" "$BUILD"

# ---------------------------------------------------------------------------
# 1) site.zip = build Vite (front public) + mailer.php + dépendances mail
#    + dossiers /admin/ et /etudiant/ (fallback legacy si sous-domaines absents)
# ---------------------------------------------------------------------------
echo "==> Build front (npm run build)"
cd "$ROOT" && npm run build >/dev/null

SITE="$BUILD/site"
mkdir -p "$SITE"
cp -R "$ROOT/dist/"* "$SITE/" 2>/dev/null || true   # output Vite (peut s'appeler dist/)
# Fallback : si Vite a écrit ailleurs, on tente .output ou build
[ -d "$ROOT/.output/public" ] && cp -R "$ROOT/.output/public/"* "$SITE/" || true

# Endpoints/utilitaires PHP du site principal
cp "$PUB/mailer.php"      "$SITE/"
cp "$PUB/db_config.php"   "$SITE/"
cp "$PUB/verify.php"      "$SITE/"
cp "$PUB/_pdf_classes.php" "$SITE/"
cp -R "$PUB/FPDF"         "$SITE/"
cp -R "$PUB/PHPMailer"    "$SITE/"
# Pages PHP legacy (admin/etudiant accessibles via /admin/ et /etudiant/ si besoin)
cp -R "$PUB/admin"    "$SITE/"
cp -R "$PUB/etudiant" "$SITE/"

# .htaccess SPA (TanStack côté client) — ne réécrit PAS /api, /admin, /etudiant, /mailer.php, /verify.php
cat > "$SITE/.htaccess" <<'HT'
# IPEC — Site principal (www.ipec.school) — SPA TanStack + endpoints PHP
RewriteEngine On
# Laisse passer les vrais fichiers/dossiers
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
# Laisse passer les endpoints PHP / dossiers admin & etudiant
RewriteRule ^(mailer\.php|verify\.php|admin(/|$)|etudiant(/|$)|FPDF/|PHPMailer/) - [L]
# Tout le reste → SPA
RewriteRule ^ index.html [L]

<FilesMatch "(^db_config\.php$|^_etudiants\.php$|^_pdf_classes\.php$)">
  Require all denied
</FilesMatch>
HT

cd "$BUILD/site" && zip -rq "$DIST/site.zip" . && cd "$ROOT"
echo "==> dist/site.zip OK"

# ---------------------------------------------------------------------------
# 2) admin.zip = build Vite + dossier api/ (admin-api renommé) + _shared/
#    Cible : sous-domaine admin.ipec.school
# ---------------------------------------------------------------------------
ADMIN="$BUILD/admin"
mkdir -p "$ADMIN/api/_shared"
cp -R "$SITE/"*  "$ADMIN/" 2>/dev/null || true   # même bundle SPA
# Mais on N'inclut PAS /etudiant/ ni /admin/ legacy ici (pas pertinent sur ce sous-domaine)
rm -rf "$ADMIN/admin" "$ADMIN/etudiant"

# Endpoints API admin
cp "$PUB/admin-api/"*.php "$ADMIN/api/"

# Dépendances partagées
cp "$PUB/db_config.php"    "$ADMIN/api/_shared/"
cp "$PUB/mailer.php"       "$ADMIN/api/_shared/"
cp "$PUB/_pdf_classes.php" "$ADMIN/api/_shared/"
cp "$PUB/admin/_etudiants.php" "$ADMIN/api/_shared/"
cp -R "$PUB/FPDF"          "$ADMIN/api/_shared/"
cp -R "$PUB/PHPMailer"     "$ADMIN/api/_shared/"
# admin_users.php : NON inclus, à créer à la main sur n0c (cf. README)

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
# Aucune URL HTTP ne doit lire les helpers partagés (inclus côté serveur uniquement)
Require all denied
HT

cd "$BUILD/admin" && zip -rq "$DIST/admin.zip" . && cd "$ROOT"
echo "==> dist/admin.zip OK"

# ---------------------------------------------------------------------------
# 3) lms.zip = build Vite + dossier api/ (etudiant-api renommé) + _shared/
#    Cible : sous-domaine lms.ipec.school
# ---------------------------------------------------------------------------
LMS="$BUILD/lms"
mkdir -p "$LMS/api/_shared"
cp -R "$SITE/"* "$LMS/" 2>/dev/null || true
rm -rf "$LMS/admin" "$LMS/etudiant"

cp "$PUB/etudiant-api/"*.php "$LMS/api/"

cp "$PUB/db_config.php"    "$LMS/api/_shared/"
cp "$PUB/mailer.php"       "$LMS/api/_shared/"
cp "$PUB/_pdf_classes.php" "$LMS/api/_shared/"
cp -R "$PUB/FPDF"          "$LMS/api/_shared/"
cp -R "$PUB/PHPMailer"     "$LMS/api/_shared/"

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

cd "$BUILD/lms" && zip -rq "$DIST/lms.zip" . && cd "$ROOT"
echo "==> dist/lms.zip OK"

ls -lh "$DIST"
echo
echo "Prochaines étapes manuelles sur n0c :"
echo "  1) Uploader site.zip  dans public_html/                          (et dézipper)"
echo "  2) Uploader admin.zip dans le docroot du sous-domaine admin.ipec.school"
echo "  3) Uploader lms.zip   dans le docroot du sous-domaine lms.ipec.school"
echo "  4) Créer admin/api/_shared/admin_users.php :"
echo "       <?php return ['admin' => password_hash('MOT_DE_PASSE', PASSWORD_BCRYPT)];"
echo "  5) Créer le fichier ../.ipec-mailer.env (hors public_html) avec les credentials SMTP"
