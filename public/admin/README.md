# Admin IPEC — Installation sur n0c

## 1. Sous-domaine
Dans cPanel → **Domains → Subdomains**, créer `admin.ipec.school` pointant vers `/public_html/admin/`.

## 2. Migration base de données
Dans phpMyAdmin, base `txuxaqftdr_IPEC_Website`, onglet SQL, exécuter le contenu de `_migration.sql` (ajoute `facture_payee`, `facture_payee_at`, `facture_payee_par` + table `admin_actions`).

## 3. Créer ton compte admin
1. Crée un fichier temporaire `public_html/_genhash.php` :
   ```php
   <?php echo password_hash('TON_MOT_DE_PASSE_FORT', PASSWORD_BCRYPT);
   ```
2. Ouvre `https://ipec.school/_genhash.php`, copie le hash affiché.
3. **Supprime immédiatement** `_genhash.php`.
4. Édite `public_html/admin/_bootstrap.php`, dans `ADMIN_USERS` :
   ```php
   const ADMIN_USERS = [
       'tonidentifiant' => '$2y$12$hash_copié_ici...',
   ];
   ```

## 4. (Optionnel) Restreindre par IP
Toujours dans `_bootstrap.php` :
```php
const ADMIN_IP_ALLOWLIST = ['1.2.3.4'];
```

## 5. Tester
- `https://admin.ipec.school/` → redirige vers `login.php`
- Connecte-toi → liste des candidatures.

## Fonctionnalités
- Liste filtrable (recherche, statut, payée/en attente)
- Fiche détail complète
- Téléchargement PDF candidature + facture (regénérés à la volée)
- Renvoi de l'e-mail au candidat (mêmes 2 PDF joints qu'à l'origine)
- Marquer la facture comme payée / annuler
- Changer le statut du dossier
- Historique horodaté de toutes les actions admin

## Sécurité
- Sessions cookies HttpOnly + SameSite=Lax + Secure (HTTPS forcé)
- Hash bcrypt des mots de passe
- Protection CSRF sur toutes les écritures
- `.htaccess` interdit l'accès direct aux fichiers `_*`, `.sql`, `.env`, `.md`
- Audit trail (`admin_actions`) pour chaque opération sensible
