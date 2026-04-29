# Déploiement des portails admin / étudiant sur n0c

Ce projet produit **3 builds statiques** différents depuis le même code source,
chacun destiné à un sous-domaine de n0c.

| Cible           | Commande                              | Sous-domaine cible      |
|-----------------|---------------------------------------|-------------------------|
| Site public     | `STATIC_BUILD=site npm run build`     | `ipec.school`           |
| Portail admin   | `STATIC_BUILD=admin npm run build`    | `admin.ipec.school`     |
| Portail étudiant| `STATIC_BUILD=etu npm run build`      | `lms.ipec.school`       |

Chaque build produit `dist/` à uploader à la racine du sous-domaine
correspondant, **avec en plus** :

- les fichiers PHP de l'API (cf. ci-dessous)
- un `.htaccess` SPA fallback (cf. ci-dessous)

---

## 1. Site public → `ipec.school`

```bash
STATIC_BUILD=site npm run build
```

Upload `dist/` à la racine. Les pages listées dans `SITE_ROUTES` sont
prerendées en HTML, le reste est servi par fallback SPA.

---

## 2. Portail admin → `admin.ipec.school`

### a) Build React

```bash
STATIC_BUILD=admin npm run build
```

Upload `dist/` à la racine de `admin.ipec.school`.

### b) API PHP

Crée un dossier `/api/` à la racine de `admin.ipec.school` et upload tout le
contenu de `public/admin-api/` dedans :

```
admin.ipec.school/
├── index.html              ← React build
├── assets/                 ← React build
├── .htaccess               ← SPA fallback (voir plus bas)
└── api/
    ├── _bootstrap.php
    ├── login.php
    ├── logout.php
    ├── me.php
    ├── candidatures.php
    ├── candidature.php
    └── .htaccess
```

### c) `.htaccess` racine (SPA fallback + API)

À placer dans la racine de `admin.ipec.school` :

```apache
RewriteEngine On

# Laisser passer les fichiers existants (assets React, /api/*.php, etc.)
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Tout le reste → index.html (SPA fallback)
RewriteRule ^ index.html [L]
```

### d) Identifiants admin

Les comptes admin sont définis dans `public/admin/_bootstrap.php` (constante
`ADMIN_USERS`). Ce fichier doit rester accessible à l'API PHP — il est inclus
par `public/admin-api/_bootstrap.php`.

---

## 3. Portail étudiant → `lms.ipec.school`

### a) Build React

```bash
STATIC_BUILD=etu npm run build
```

Upload `dist/` à la racine de `lms.ipec.school`.

### b) API PHP

Idem : crée `/api/` et upload `public/etudiant-api/*` dedans.

```
lms.ipec.school/
├── index.html
├── assets/
├── .htaccess
└── api/
    ├── _bootstrap.php
    ├── login.php
    ├── logout.php
    ├── me.php
    ├── factures.php
    ├── documents.php
    ├── activer.php
    ├── reset-password.php
    ├── mot-de-passe-oublie.php
    └── .htaccess
```

### c) `.htaccess` racine

Identique à celui de admin, mais on conserve aussi l'accès direct aux PDF
téléchargés depuis l'ancien `telecharger.php` (au moins le temps de la migration) :

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^ index.html [L]
```

---

## 4. Auth & cookies — important

- **Même origine** : React et API PHP servis depuis le même sous-domaine,
  donc cookies `SameSite=Lax` (config par défaut PHP). Aucun problème CORS
  en production.
- **Dev Lovable** : si tu testes le React depuis `ipecbxl.lovable.app` qui
  appelle l'API PHP de `admin.ipec.school`, c'est cross-origin :
    - ajoute `ipecbxl.lovable.app` à `CORS_ALLOWED_ORIGINS` dans
      `public/{admin,etudiant}-api/_bootstrap.php` (déjà fait)
    - passe les cookies de session en `SameSite=None; Secure` :
      modifie `etu_session_create()` dans `public/etudiant/_bootstrap.php`
      et `session.cookie_samesite` dans `public/admin/_bootstrap.php`.

---

## 5. État actuel (squelette)

Ce qui est **fonctionnel end-to-end** :
- Connexion / déconnexion / `me` admin et étudiant
- Mots de passe oubliés / reset / activation (côté React + endpoints PHP avec
  consommation de tokens — l'envoi d'email reste à brancher sur `mailer.php`)

Ce qui est **stub** (squelette React + endpoint PHP minimal qui renvoie les
données brutes, mais l'UI ne les affiche pas encore) :
- Liste candidatures (admin)
- Détail candidature (admin)
- Factures (étudiant)
- Documents (étudiant)
- Profil + changement de mot de passe (étudiant)

À faire dans les prochaines itérations :
- Brancher les listes sur les endpoints existants
- Téléchargement PDF (réutiliser `public/etudiant/telecharger.php`)
- Action « marquer comme payée » côté admin
- Création d'un étudiant + envoi du lien d'activation
