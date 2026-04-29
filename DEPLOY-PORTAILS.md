# IPEC — Déploiement des 3 portails sur n0c

3 sous-domaines, 1 base MySQL partagée, 0 build externe.

```
www.ipec.school    → public_html/                    (site vitrine + form candidature)
admin.ipec.school  → docroot du sous-domaine admin   (dashboard interne)
lms.ipec.school    → docroot du sous-domaine lms     (espace étudiant)
```

## 1. Générer les ZIP

```bash
bash scripts/package-portails.sh
```

Produit `dist/site.zip`, `dist/admin.zip`, `dist/lms.zip`.

## 2. Arborescence sur n0c (après dézippage)

### `admin.ipec.school` (idem pour `lms.ipec.school`)

```
docroot/
├── index.html              ← SPA TanStack (build Vite)
├── assets/                 ← JS/CSS bundlés
├── .htaccess               ← rewrite SPA + bypass /api
└── api/
    ├── login.php
    ├── me.php
    ├── logout.php
    ├── dashboard.php
    ├── candidatures.php
    ├── candidature.php
    ├── candidature-action.php
    ├── candidature-pdf.php
    ├── etudiants.php
    ├── _bootstrap.php
    └── _shared/            ← Require all denied (htaccess)
        ├── db_config.php
        ├── mailer.php
        ├── _pdf_classes.php
        ├── _etudiants.php       (admin uniquement)
        ├── admin_users.php      ← À CRÉER À LA MAIN (cf. §3)
        ├── FPDF/
        └── PHPMailer/
```

### `www.ipec.school`

Inchangé : SPA + `mailer.php`, `verify.php`, `db_config.php`, `FPDF/`, `PHPMailer/`,
plus les anciens dossiers `/admin/` et `/etudiant/` conservés en fallback legacy.

## 3. Fichiers à créer à la main (jamais commit)

### `admin/api/_shared/admin_users.php`

```php
<?php
return [
    'admin' => password_hash('TON_MOT_DE_PASSE_FORT', PASSWORD_BCRYPT),
];
```

Tu peux générer le hash en SSH :
```bash
php -r "echo password_hash('TON_MDP', PASSWORD_BCRYPT), PHP_EOL;"
```

### `../.ipec-mailer.env` (un niveau au-dessus de `public_html`)

```
SMTP_HOST=mail.ipec.school
SMTP_PORT=465
SMTP_SECURE=ssl
SMTP_USER=process@ipec.school
SMTP_PASS=...
```

Ce fichier est lu par `mailer.php` partagé entre les 3 portails.

## 4. Sous-domaines & cookies

- Cookie session admin : `IPEC_ADMIN`, scope `admin.ipec.school` uniquement.
- Cookie session étudiant : `IPEC_ETU`, scope `lms.ipec.school` uniquement.
- Pas de cookie cross-subdomain → aucune fuite, chaque portail isolé.

## 5. Sécurité — checklist

- [x] `db_config.php` non accessible HTTP (`Require all denied` dans `_shared/.htaccess`)
- [x] `admin_users.php` jamais commité (pas dans le ZIP)
- [x] Mots de passe bcrypt (admin) + bcrypt (étudiants, créés par lien d'activation)
- [x] Tokens activation/reset stockés en SHA-256, usage unique, expiration
- [x] Sessions étudiantes en BDD (`etudiant_sessions`), rotation `expires_at` à chaque requête
- [x] CORS strict (3 origines : prod + preview Lovable)
- [x] Rate-limit fichier sur login/reset
- [x] Cookies `httpOnly` + `Secure` + `SameSite=Lax`
