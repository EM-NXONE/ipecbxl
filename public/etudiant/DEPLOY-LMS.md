# Déploiement du portail étudiant sur lms.ipec.school

## 1. DNS (chez ton registrar)

Ajoute un enregistrement A (ou CNAME selon le setup n0c) :

    Type   Name   Valeur
    A      lms    <IP_PUBLIQUE_DE_TON_HÉBERGEMENT_n0c>

L'IP est visible dans cPanel → "Server Information" → "Shared IP Address".
Si n0c te donne un CNAME (ex : `username.n0c.com`), utilise plutôt :

    CNAME  lms    username.n0c.com.

Attends la propagation (jusqu'à 1 h, parfois plus).

## 2. Sous-domaine dans cPanel

cPanel → **Domains** → **Create A New Domain**

  - Domain          : lms.ipec.school
  - Document Root   : public_html/etudiant         ← CRUCIAL : pointe vers le dossier existant

➡️ Ne crée **pas** un nouveau dossier vide : on réutilise le code déjà en place
   sous `public_html/etudiant/`. Ainsi rien n'est dupliqué et toute mise à jour
   du code via FTP/Git est immédiatement visible aux deux endroits.

Décoche la case "Share document root" si elle est proposée.

## 3. SSL

cPanel → **SSL/TLS Status** → coche `lms.ipec.school` → **Run AutoSSL**.
Let's Encrypt prendra ~5-15 minutes pour émettre le certificat.

## 4. Vérification

  - https://lms.ipec.school/login.php          → page de connexion
  - https://lms.ipec.school/activer.php?...    → activation
  - https://lms.ipec.school/                   → redirige vers index.php (DirectoryIndex)

L'ancien chemin `https://ipec.school/etudiant/login.php` continue de fonctionner
(rétro-compatibilité). Si tu veux forcer la redirection vers le sous-domaine,
ajoute dans `public/etudiant/.htaccess` :

    RewriteEngine On
    RewriteCond %{HTTP_HOST} ^(www\.)?ipec\.school$ [NC]
    RewriteRule ^(.*)$ https://lms.ipec.school/$1 [R=301,L]

## 5. Variables d'environnement / mailer

Aucune modification nécessaire : le mailer (`public/mailer.php`) et la BDD
(`public/db_config.php`) sont communs aux deux sous-domaines (admin, lms,
ipec.school) puisque tout pointe vers le même filesystem.

## 6. Notes techniques

- Le helper `etu_base_path()` détecte `lms.*` et bascule entre `""` (sous-domaine)
  et `/etudiant` (legacy). Tous les liens internes utilisent `etu_url()`.
- Les liens absolus envoyés par e-mail (activation, reset) utilisent
  `etu_absolute_url()` qui force `https://lms.ipec.school/...`.
- Le cookie `IPEC_ETU` a un `path` adapté automatiquement :
    - `/` sur lms.ipec.school
    - `/etudiant/` sur le legacy
- Les sessions BDD (`etudiant_sessions`) sont partagées : un étudiant connecté
  sur l'un voit son cookie marcher sur l'autre s'il garde le même hôte.
