---
name: espace-etudiant
description: Espace étudiant /etudiant/ — auth bcrypt + sessions BDD, dashboard, factures, documents, régénération PDF à la volée. Lien admin pour créer le compte et régénérer le lien d'activation.
type: feature
---

# Espace étudiant (/etudiant/)

## Auth
- `_bootstrap.php` : démarre PHP session minimale (CSRF + flash) + sessions étudiant côté BDD.
  - Cookie `IPEC_ETU` (httpOnly, samesite Lax, path=/etudiant/), token aléatoire 64 hex.
  - Table `etudiant_sessions` : id=token, expires_at avec rolling refresh à chaque requête.
  - Helpers : `etu_current()`, `etu_require_login()`, `etu_session_create/destroy()`, `etu_log_action()`.
- Mots de passe bcrypt (PASSWORD_BCRYPT). Validation min 10 + maj/min/chiffre.
- Tokens activation/reset (table `etudiant_tokens`, sha256, usage unique, TTL 14j / 1h).
- Rate limiting fichier dans `.ipec-etu-ratelimit/` (hors web).

## Pages
- `login.php`, `logout.php`
- `activer.php?token=...` (création du mot de passe initial)
- `mot-de-passe-oublie.php` (lien envoyé/journalisé en error_log pour l'instant — pipeline mail à brancher)
- `reset-mot-de-passe.php?token=...`
- `index.php` — dashboard (KPI, candidatures, dernières factures/documents)
- `factures.php` — liste complète + totaux (dû / payé)
- `documents.php` — liste des attestations/courriers
- `profil.php` — identité (lecture seule) + changement de mot de passe
- `telecharger.php?type=facture|document&id=...` — régénère le PDF via `buildFacturePdf` ou template inline FPDF

## Layout
- `_layout.php` reprend la charte du site (paper #FBFAF7, ink #1B1F2A, primary #1F3D8A, Fraunces + Inter).
- Mode sombre persisté (`localStorage.ipec-etu-theme`).
- Topbar sticky avec logo IPEC, sidebar nav, footer minimal.

## Lien avec l'admin
- `admin/detail.php` carte "Espace étudiant" :
  - Bouton "Créer un compte étudiant" → `etudiant_create_from_candidature()` génère IPEC-ETU-AAAA-XXXX + token activation.
  - Bouton "Rattacher au compte existant" si email déjà connu.
  - Bouton "Régénérer le lien d'activation" si compte non encore activé.
- Helpers dans `admin/_etudiants.php`.
- Action `regen_activation` invalide les anciens tokens d'activation puis en émet un nouveau (14j).

## Sécurité
- `.htaccess` /etudiant/ bloque `_*`, `*.sql`, `*.env`, `*.json`, `*.lock` et désactive l'index.
- CSRF token sur tous les POST (`etu_csrf_check()`).
- Vérif d'appartenance systématique sur factures/documents avant régénération PDF.
- Reset mot de passe : invalide toutes les sessions étudiant. Changement de mot de passe : invalide les autres sessions, conserve la courante.

## TODO branchement mail
Pour l'instant, les liens d'activation et de reset sont affichés en flash admin / loggés via `error_log`. Quand le pipeline mail "étudiant" sera prêt, factoriser un `etu_send_mail()` reprenant la logique PHPMailer de `mailer.php` (SMTP process@ipec.school).
