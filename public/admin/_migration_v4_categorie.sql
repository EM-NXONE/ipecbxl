-- =====================================================================
-- IPEC — Migration V4 : Catégorie de compte (candidat / preadmis / etudiant)
-- À exécuter UNE SEULE FOIS via phpMyAdmin (onglet SQL).
--
-- Workflow :
--   1. Soumission du formulaire d'admission       → categorie = 'candidat'
--      (compte auto-créé avec mdp par défaut "Student1", accès limité :
--       facture frais de dossier + récap candidature).
--   2. Statut candidature 'validee' + frais 400 € payés
--      → génération des 3 factures de scolarité   → categorie = 'preadmis'
--      (accès complet aux factures + lettre de préadmission).
--   3. 1ʳᵉ tranche de scolarité marquée payée     → categorie = 'etudiant'
--      (accès plein + LMS à venir).
-- =====================================================================

ALTER TABLE etudiants
    ADD COLUMN categorie ENUM('candidat','preadmis','etudiant')
        NOT NULL DEFAULT 'candidat' AFTER statut,
    ADD KEY idx_categorie (categorie);

-- Mise à niveau des comptes existants : tous ceux déjà créés manuellement
-- par l'admin sont considérés "etudiant" (rétro-compat).
UPDATE etudiants SET categorie = 'etudiant'
 WHERE password_hash IS NOT NULL;

-- Comptes preadmis : ceux ayant au moins une facture de scolarité générée
-- mais aucune facture T1 payée.
UPDATE etudiants e
   JOIN (
        SELECT etudiant_id
          FROM factures
         WHERE type = 'scolarite'
         GROUP BY etudiant_id
   ) sf ON sf.etudiant_id = e.id
   LEFT JOIN (
        SELECT etudiant_id
          FROM factures
         WHERE type = 'scolarite' AND statut_paiement = 'payee'
         GROUP BY etudiant_id
   ) sfp ON sfp.etudiant_id = e.id
   SET e.categorie = 'preadmis'
 WHERE sfp.etudiant_id IS NULL;

-- Comptes etudiant : ceux ayant au moins une facture scolarité payée.
UPDATE etudiants e
   JOIN (
        SELECT etudiant_id
          FROM factures
         WHERE type = 'scolarite' AND statut_paiement = 'payee'
         GROUP BY etudiant_id
   ) sfp ON sfp.etudiant_id = e.id
   SET e.categorie = 'etudiant';
