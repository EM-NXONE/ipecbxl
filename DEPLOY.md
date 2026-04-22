# Déploiement IPEC sur n0c — Guide complet

Ce guide explique comment **déployer le site IPEC chez n0c** sans dépendre
de Lovable. Vous gardez la possibilité d'éditer le projet dans Lovable plus
tard si besoin — Lovable et n0c ne s'empêchent pas mutuellement.

---

## 🗺 Vue d'ensemble

```
┌─────────────┐   git clone    ┌──────────────┐   npm run build    ┌──────────┐
│   GitHub    │ ─────────────► │ Votre PC     │ ─────────────────► │  n0c     │
│ (le code)   │                │ (build local)│   upload FTP       │ (le site)│
└─────────────┘                └──────────────┘                    └──────────┘
```

- **GitHub** : la source de vérité du code (synchronisé avec Lovable).
- **Votre PC** : construit la version statique du site (HTML/JS/CSS).
- **n0c** : héberge le site final + le script PHP `mailer.php`.

---

## 1️⃣ Prérequis sur votre ordinateur

Installez ces outils une fois pour toutes :

| Outil | Pourquoi | Lien |
|---|---|---|
| **Node.js v20+** | Pour builder le site | https://nodejs.org/ |
| **Git** | Pour cloner le repo | https://git-scm.com/ |
| **Un client FTP/SFTP** | Pour uploader sur n0c | [FileZilla](https://filezilla-project.org/) (gratuit) |

Vérifiez l'installation dans un terminal :
```bash
node --version    # doit afficher v20.x.x ou plus
git --version
```

---

## 2️⃣ Récupérer le code depuis GitHub

```bash
git clone <URL_DE_VOTRE_REPO_GITHUB>
cd <nom-du-dossier>
npm install
```

> 💡 L'URL GitHub se trouve dans Lovable : bouton **GitHub** en haut à droite
> → vous serez redirigé vers le repo, copiez l'URL avec le bouton **Code**.

---

## 3️⃣ Builder la version statique (mode prerender)

⚠️ **TRÈS IMPORTANT** : utilisez la variable d'environnement `STATIC_BUILD=1`,
sinon le build produit du code serveur pour Cloudflare et **pas de fichiers
HTML** (vous obtiendriez `dist/client/` + `dist/server/` sans aucun `.html`).

**Sur macOS / Linux :**
```bash
STATIC_BUILD=1 npm run build
```

**Sur Windows (PowerShell) :**
```powershell
$env:STATIC_BUILD=1; npm run build
```

**Sur Windows (cmd.exe) :**
```cmd
set STATIC_BUILD=1 && npm run build
```

Cette commande :
1. Compile le site React.
2. Lance un serveur temporaire en local.
3. Visite chaque route (`/`, `/contact`, `/programmes`, etc.) et **sauvegarde
   le HTML rendu** dans `dist/client/`.
4. Vous obtenez `dist/client/index.html` + un `dist/client/<route>/index.html`
   par page → SEO complet, chaque page indexable indépendamment.

À la fin, vous devez voir un message `[prerender] Prerendered 12 pages:` listant
toutes vos routes. Si vous voyez `Prerendered 0 pages` → le `STATIC_BUILD=1`
n'a pas été pris en compte, recommencez.

---

## 4️⃣ Préparer les fichiers à uploader

Après le build, **tout est dans `dist/client/`**. C'est ce dossier (et son
contenu) qu'il faut uploader dans `public_html/` de n0c.

Structure attendue après upload :

```
public_html/
├── index.html              ← page d'accueil pré-rendue
├── assets/                 ← JS, CSS, fonts (générés par Vite, hashés)
│
├── admissions/index.html   ← une page HTML par route
├── cgu/index.html
├── cgv/index.html
├── confidentialite/index.html
├── contact/index.html
├── cookies/index.html
├── inscription/index.html
├── international/index.html
├── mentions-legales/index.html
├── programmes/index.html
│
├── .htaccess               ← À AJOUTER MANUELLEMENT depuis public/.htaccess
│                              (le build ne le copie pas automatiquement)
│
├── mailer.php              ← déjà copié par le build (depuis public/)
└── PHPMailer/              ← déjà copié par le build (depuis public/)
    └── src/
        ├── Exception.php
        ├── PHPMailer.php
        └── SMTP.php
```

> 💡 Le dossier `dist/server/` produit par le build **ne sert à rien sur n0c**.
> Vous pouvez l'ignorer complètement, c'est juste un sous-produit technique
> du prerender.

**Et HORS de `public_html/`** (un niveau au-dessus, ex: `/home/VOTRE_USER/`) :

```
.ipec-mailer.env            ← contient SMTP_HOST, SMTP_USER, SMTP_PASS, etc.
```

---

## 5️⃣ Procédure d'upload (FileZilla)

1. Connectez-vous à n0c en SFTP avec les identifiants fournis par n0c.
2. Naviguez dans `public_html/` côté serveur.
3. **Avant le premier déploiement**, sauvegardez le contenu existant
   (clic droit → télécharger).
4. Uploadez tout le contenu du dossier de build (`dist/` ou `.output/public/`)
   dans `public_html/`.
5. Uploadez en plus :
   - `public/.htaccess` → `public_html/.htaccess`
   - `public/mailer.php` → `public_html/mailer.php`
   - `public/PHPMailer/` → `public_html/PHPMailer/`
6. Vérifiez que `.ipec-mailer.env` existe bien **un niveau au-dessus** de
   `public_html/` (cf. configuration SMTP).

---

## 6️⃣ Configuration SMTP (à faire UNE FOIS)

Créez le fichier `/home/VOTRE_USER/.ipec-mailer.env` avec ce contenu
(adaptez les valeurs — demandez-les au support n0c si besoin) :

```
SMTP_HOST=mail.ipec.school
SMTP_PORT=465
SMTP_SECURE=ssl
SMTP_USER=process@ipec.school
SMTP_PASS=LE_MOT_DE_PASSE_DE_LA_BOITE
```

Puis verrouillez les permissions :
```bash
chmod 600 ~/.ipec-mailer.env
```

> 🔒 Ce fichier ne doit JAMAIS être dans `public_html/` (il serait
> téléchargeable par n'importe qui depuis le web).

---

## 7️⃣ Pointer le domaine ipec.school vers n0c

Dans le panneau DNS de votre registrar (là où vous avez acheté le domaine) :

| Type | Nom | Valeur |
|---|---|---|
| A | @ | (IP fournie par n0c) |
| A | www | (même IP) |

Et pour la délivrabilité des e-mails (à configurer côté n0c) :

| Type | Nom | Valeur |
|---|---|---|
| TXT (SPF) | @ | `v=spf1 include:_spf.n0c.com ~all` |
| TXT (DKIM) | (fourni par n0c) | (fourni par n0c) |
| TXT (DMARC) | _dmarc | `v=DMARC1; p=none; rua=mailto:postmaster@ipec.school` |

Le support n0c peut activer SPF/DKIM en un clic depuis leur panneau.

---

## 8️⃣ Vérification finale

Une fois en ligne, testez :

- [ ] La page d'accueil s'affiche : `https://ipec.school/`
- [ ] Les liens internes marchent : cliquer sur "Programmes", "Contact", etc.
- [ ] **Le refresh d'une sous-page marche** : aller sur `/contact`, recharger
      la page (F5). Si vous voyez 404 → le `.htaccess` n'est pas pris en compte.
- [ ] Le formulaire de contact envoie un mail à `contact@ipec.school`
- [ ] Le formulaire d'inscription envoie un mail à `admission@ipec.school`
- [ ] Les e-mails reçus n'arrivent **pas** dans les spams (sinon : DKIM/SPF
      à vérifier avec n0c)

---

## 🔄 Pour les futures mises à jour

Quand vous modifiez le site (dans Lovable ou en local) :

1. `git pull` (si vous avez modifié dans Lovable, pour récupérer les changements)
2. `npm run build`
3. Re-uploader le contenu du dossier de build dans `public_html/`
   (en remplaçant les anciens fichiers — `.htaccess`, `mailer.php` et
   `PHPMailer/` ne changent pas, vous pouvez les laisser tels quels).

---

## ❓ En cas de problème

| Symptôme | Cause probable | Solution |
|---|---|---|
| Page blanche | Fichiers JS/CSS pas uploadés | Vérifier que `assets/` est bien présent |
| 404 au refresh d'une sous-page | `.htaccess` absent ou ignoré | Vérifier qu'il est bien à la racine de `public_html/` et que mod_rewrite est activé chez n0c |
| Formulaire renvoie "Origin not allowed" | Le domaine appelant n'est pas dans la whitelist du PHP | Ajouter votre domaine dans `$allowedOrigins` de `mailer.php` |
| Formulaire renvoie "Configuration SMTP manquante" | Le `.env` n'est pas trouvé | Vérifier le chemin et les permissions de `.ipec-mailer.env` |
| E-mails dans les spams | SPF/DKIM/DMARC pas activés | Demander à n0c d'activer DKIM pour `process@ipec.school` |

---

✅ **Une fois ces étapes faites, votre site est 100% autonome chez n0c.
Lovable n'est plus nécessaire pour qu'il fonctionne.**
