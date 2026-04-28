---
name: db-schema-etudiants
description: Architecture base de données IPEC v2 — étudiants, factures multiples, documents génériques. Régénération PDF à la volée.
type: feature
---

# Schéma BDD — Espaces étudiants (v2)

Migration : `public/admin/_migration_v2_etudiants.sql` (à passer une fois via phpMyAdmin).

## Tables

- **`etudiants`** : compte connectable (email + bcrypt). Création **manuelle** par l'admin depuis la fiche candidature. Champ `numero_etudiant` (IPEC-ETU-AAAA-XXXX).
- **`candidatures.etudiant_id`** : FK ajoutée (nullable, ON DELETE SET NULL). Une candidature peut être rattachée à un étudiant existant.
- **`factures`** : générique (frais_dossier, scolarite, acompte, solde, divers). Montants en **centimes** (INT). Statut paiement étendu (en_attente / partiellement_payee / payee / annulee / remboursee).
- **`documents`** : générique (attestation, convention, courrier...). Champ `template` (nom fichier PHP/FPDF) + `data_json` pour régénérer le PDF à la volée. **Aucun fichier stocké sur disque.**
- **`etudiant_sessions`** : sessions serveur (cookie httpOnly), pas le PHP session par défaut.
- **`etudiant_tokens`** : tokens activation compte + reset mot de passe (hash sha256, usage unique).
- **`etudiant_actions`** : audit trail (login, view_facture, download_doc...).

## Règles

- **PDF jamais stockés** : factures et documents sont régénérés à la demande depuis les données SQL + template FPDF. Cohérent avec le fonctionnement actuel (mailer.php).
- **Auth étudiant = PHP natif** (bcrypt, sessions serveur). Pas de Lovable Cloud, pas de Supabase. Backend reste 100% PHP sur n0c.
- **Liaison candidature ↔ étudiant = manuelle** : l'admin déclenche la création depuis `detail.php`.
- La facture historique des frais de dossier (400 €) reste dans `candidatures.facture_numero` pour rétro-compat. Les nouvelles factures vivent dans `factures`.
