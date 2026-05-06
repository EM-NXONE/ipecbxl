-- =====================================================================
-- IPEC — Migration v5 : évolution du cursus étudiant
-- =====================================================================
-- À exécuter UNE SEULE FOIS via phpMyAdmin.
--
-- Ajoute :
--   1. candidatures.parent_candidature_id  → chaîne historique année par année
--      candidatures.type_inscription       → initiale | passage | redoublement
--   2. etudiants.categorie ENUM élargi     → + 'diplome', + 'inactif'
--      + colonne motif_inactif (texte libre, optionnel) + date_fin_cursus
-- =====================================================================

-- 1. Candidatures : lien vers candidature précédente + type
ALTER TABLE candidatures
    ADD COLUMN parent_candidature_id INT UNSIGNED DEFAULT NULL AFTER etudiant_id,
    ADD COLUMN type_inscription ENUM('initiale','passage','redoublement')
                                NOT NULL DEFAULT 'initiale' AFTER parent_candidature_id,
    ADD KEY idx_parent_cand (parent_candidature_id),
    ADD CONSTRAINT fk_parent_candidature
        FOREIGN KEY (parent_candidature_id) REFERENCES candidatures(id)
        ON DELETE SET NULL;

-- 2. Étudiants : nouvelles catégories diplome/inactif + métadonnées
ALTER TABLE etudiants
    MODIFY COLUMN categorie ENUM('candidat','preadmis','etudiant','diplome','inactif')
                            NOT NULL DEFAULT 'candidat',
    ADD COLUMN motif_inactif    VARCHAR(255) DEFAULT NULL AFTER categorie,
    ADD COLUMN date_fin_cursus  DATE         DEFAULT NULL AFTER motif_inactif;
