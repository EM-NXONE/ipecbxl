-- =====================================================================
-- IPEC — Migration v7 : la progression NE CRÉE PLUS de candidature.
-- =====================================================================
-- À exécuter UNE SEULE FOIS via phpMyAdmin.
--
-- Modèle cible :
--   - 1 candidature par étudiant (le dossier d'admission initial, jamais dupliqué).
--   - L'état courant du cursus vit sur la fiche `etudiants`.
--   - Les factures et documents portent leur propre année académique + étape
--     (snapshot indépendant de la candidature initiale).
--
-- Les colonnes v5 (parent_candidature_id, type_inscription) restent en base
-- pour ne pas casser les candidatures déjà dupliquées par l'ancien flux.
-- Aucune nouvelle ligne ne sera créée par les passages d'année.
-- =====================================================================

-- 1) État courant du cursus, sur l'étudiant
ALTER TABLE etudiants
    ADD COLUMN etape_courante              VARCHAR(10) DEFAULT NULL AFTER categorie,
    ADD COLUMN annee_academique_courante   VARCHAR(20) DEFAULT NULL AFTER etape_courante,
    ADD COLUMN rentree_courante            VARCHAR(80) DEFAULT NULL AFTER annee_academique_courante;

-- 2) Année académique + étape — snapshot sur chaque facture
ALTER TABLE factures
    ADD COLUMN annee_academique            VARCHAR(20) DEFAULT NULL AFTER libelle,
    ADD COLUMN etape_cursus                VARCHAR(10) DEFAULT NULL AFTER annee_academique,
    ADD KEY idx_annee_academique (annee_academique);

-- 3) Idem pour les documents (attestations, diplôme, etc.)
ALTER TABLE documents
    ADD COLUMN annee_academique            VARCHAR(20) DEFAULT NULL AFTER titre,
    ADD COLUMN etape_cursus                VARCHAR(10) DEFAULT NULL AFTER annee_academique,
    ADD KEY idx_doc_annee_academique (annee_academique);

-- 4) Backfill : remplir les colonnes depuis la candidature liée (existant)
UPDATE factures f
   JOIN candidatures c ON c.id = f.candidature_id
    SET f.annee_academique = c.annee_academique
  WHERE f.annee_academique IS NULL AND c.annee_academique IS NOT NULL;

UPDATE documents d
   JOIN candidatures c ON c.id = d.candidature_id
    SET d.annee_academique = c.annee_academique
  WHERE d.annee_academique IS NULL AND c.annee_academique IS NOT NULL;

-- Étape (PAA-1, PEA-2, ...) déduite de programme + 1er chiffre de annee
UPDATE factures f
   JOIN candidatures c ON c.id = f.candidature_id
    SET f.etape_cursus = CONCAT(
            UPPER(LEFT(TRIM(c.programme), 3)),
            '-',
            COALESCE(NULLIF(REGEXP_SUBSTR(c.annee, '[0-9]'), ''), '1')
        )
  WHERE f.etape_cursus IS NULL
    AND c.programme IS NOT NULL
    AND UPPER(LEFT(TRIM(c.programme), 3)) IN ('PAA','PEA');

UPDATE documents d
   JOIN candidatures c ON c.id = d.candidature_id
    SET d.etape_cursus = CONCAT(
            UPPER(LEFT(TRIM(c.programme), 3)),
            '-',
            COALESCE(NULLIF(REGEXP_SUBSTR(c.annee, '[0-9]'), ''), '1')
        )
  WHERE d.etape_cursus IS NULL
    AND c.programme IS NOT NULL
    AND UPPER(LEFT(TRIM(c.programme), 3)) IN ('PAA','PEA');

-- 5) État courant des étudiants : initialisé depuis la dernière candidature
--    (qu'elle soit l'initiale ou un duplicat v5 issu d'un passage d'année).
UPDATE etudiants e
   JOIN (
        SELECT etudiant_id, MAX(id) AS cid
          FROM candidatures
         WHERE etudiant_id IS NOT NULL
         GROUP BY etudiant_id
   ) m ON m.etudiant_id = e.id
   JOIN candidatures c ON c.id = m.cid
    SET e.annee_academique_courante = COALESCE(e.annee_academique_courante, c.annee_academique),
        e.rentree_courante          = COALESCE(e.rentree_courante,          c.rentree),
        e.etape_courante            = COALESCE(
            e.etape_courante,
            CONCAT(
                UPPER(LEFT(TRIM(c.programme), 3)),
                '-',
                COALESCE(NULLIF(REGEXP_SUBSTR(c.annee, '[0-9]'), ''), '1')
            )
        )
  WHERE c.programme IS NOT NULL
    AND UPPER(LEFT(TRIM(c.programme), 3)) IN ('PAA','PEA');
