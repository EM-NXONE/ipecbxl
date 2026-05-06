-- =====================================================================
-- IPEC — Migration v6 : ajout des types documents 'attestation' et 'diplome'
-- =====================================================================
-- À exécuter UNE SEULE FOIS via phpMyAdmin.
--
-- Ajoute deux nouvelles valeurs à l'ENUM documents.type pour supporter :
--   - attestation_reussite_annee (passage d'année)
--   - attestation_reussite       (fin de cursus PEA-2)
--   - diplome_bachelier          (fin de PAA-3)
-- =====================================================================

ALTER TABLE documents
    MODIFY COLUMN type ENUM(
        'attestation_inscription','attestation_scolarite','attestation',
        'convention_stage','releve_notes','certificat',
        'courrier','recu','diplome','autre'
    ) NOT NULL DEFAULT 'autre';
