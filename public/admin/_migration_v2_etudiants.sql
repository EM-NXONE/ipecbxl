-- =====================================================================
-- IPEC — Migration V2 : Espaces étudiants, documents & factures multiples
-- Base : txuxaqftdr_IPEC_Website
-- À exécuter UNE SEULE FOIS via phpMyAdmin (onglet SQL).
--
-- Objectif :
--   1. Créer une table `etudiants` (compte connectable, indépendant de la candidature)
--   2. Lier une candidature à un étudiant (création manuelle par l'admin)
--   3. Créer une table `factures` générique (frais dossier, scolarité, autres)
--   4. Créer une table `documents` générique (attestations, conventions, courriers...)
--   5. Sessions étudiantes + tokens (activation compte, reset mot de passe)
--   6. Journal d'accès étudiant (audit)
--
-- Principe : aucun fichier PDF n'est stocké sur disque.
--   - Les factures sont régénérées à la volée depuis `factures` (FPDF).
--   - Les documents sont régénérés à la volée depuis `documents` + template.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1) ETUDIANTS — comptes connectables
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS etudiants (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,

    -- Identifiants de connexion/contact : plusieurs étudiants peuvent partager le même e-mail
    email               VARCHAR(255)        NOT NULL,
    password_hash       VARCHAR(255)        DEFAULT NULL,  -- bcrypt ; NULL = compte pas encore activé
    email_verifie       TINYINT(1)          NOT NULL DEFAULT 0,

    -- Identité : utilisée pour rattacher les candidatures à un étudiant réel
    civilite            VARCHAR(30)         DEFAULT NULL,
    prenom              VARCHAR(100)        NOT NULL,
    nom                 VARCHAR(100)        NOT NULL,
    date_naissance      VARCHAR(20)         DEFAULT NULL,
    nationalite         VARCHAR(100)        DEFAULT NULL,
    telephone           VARCHAR(30)         DEFAULT NULL,

    -- Numéro étudiant interne IPEC (ex : IPEC-ETU-2026-XXXX)
    numero_etudiant     VARCHAR(40)         DEFAULT NULL,

    -- Statut du compte
    statut              ENUM('actif','suspendu','archive') NOT NULL DEFAULT 'actif',

    -- Suivi connexions
    derniere_connexion  DATETIME            DEFAULT NULL,
    derniere_ip         VARCHAR(45)         DEFAULT NULL,

    -- Métadonnées
    cree_par_admin      VARCHAR(100)        DEFAULT NULL,  -- admin_user qui a créé le compte
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_email (email),
    KEY idx_identite (nom, prenom, date_naissance),
    UNIQUE KEY uniq_numero_etudiant (numero_etudiant),
    KEY idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 2) Lien CANDIDATURE → ETUDIANT (1 candidature peut être rattachée à 1 étudiant)
-- ---------------------------------------------------------------------
ALTER TABLE candidatures
    ADD COLUMN etudiant_id INT UNSIGNED NULL DEFAULT NULL AFTER id,
    ADD KEY idx_etudiant (etudiant_id),
    ADD CONSTRAINT fk_candidatures_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
        ON DELETE SET NULL ON UPDATE CASCADE;


-- ---------------------------------------------------------------------
-- 3) FACTURES — table générique (frais dossier, scolarité, divers)
--    La facture des frais de dossier (400 €) historique reste dans
--    `candidatures.facture_numero` pour compatibilité ; les NOUVELLES
--    factures émises depuis l'espace étudiant vivent ici.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS factures (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    numero              VARCHAR(40)         NOT NULL,  -- IPEC-FACT-AAAA-XXXXXX

    -- Destinataire
    etudiant_id         INT UNSIGNED        NOT NULL,
    candidature_id      INT UNSIGNED        DEFAULT NULL,  -- optionnel, si la facture est liée à une candidature

    -- Type / catégorie
    type                ENUM('frais_dossier','scolarite','acompte','solde','divers')
                                            NOT NULL DEFAULT 'divers',
    libelle             VARCHAR(255)        NOT NULL,  -- ex : "Frais de scolarité - 1ère année - Bachelor"
    description         TEXT                DEFAULT NULL,

    -- Montants (en centimes pour éviter les flottants)
    montant_ht_cents    INT UNSIGNED        NOT NULL DEFAULT 0,
    tva_taux            DECIMAL(5,2)        NOT NULL DEFAULT 0.00,  -- 0 = exonéré (enseignement)
    montant_ttc_cents   INT UNSIGNED        NOT NULL,
    devise              CHAR(3)             NOT NULL DEFAULT 'EUR',

    -- Échéance & paiement
    date_emission       DATE                NOT NULL,
    date_echeance       DATE                DEFAULT NULL,
    statut_paiement     ENUM('en_attente','partiellement_payee','payee','annulee','remboursee')
                                            NOT NULL DEFAULT 'en_attente',
    paye_at             DATETIME            DEFAULT NULL,
    paye_par_admin      VARCHAR(100)        DEFAULT NULL,
    moyen_paiement      VARCHAR(50)         DEFAULT NULL,  -- virement, CB, espèces, autre
    reference_paiement  VARCHAR(100)        DEFAULT NULL,  -- ref bancaire / transaction

    -- Visibilité
    visible_etudiant    TINYINT(1)          NOT NULL DEFAULT 1,  -- masquable si brouillon

    -- Métadonnées
    cree_par_admin      VARCHAR(100)        DEFAULT NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uniq_numero (numero),
    KEY idx_etudiant (etudiant_id),
    KEY idx_candidature (candidature_id),
    KEY idx_statut (statut_paiement),
    KEY idx_type (type),
    CONSTRAINT fk_factures_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_factures_candidature
        FOREIGN KEY (candidature_id) REFERENCES candidatures(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 4) DOCUMENTS — table générique (attestations, conventions, courriers...)
--    Le PDF n'est PAS stocké : il est régénéré à la volée depuis `template`
--    + `data_json` (variables d'interpolation).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS documents (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    reference           VARCHAR(40)         NOT NULL,  -- IPEC-DOC-AAAA-XXXXXX

    -- Destinataire
    etudiant_id         INT UNSIGNED        NOT NULL,
    candidature_id      INT UNSIGNED        DEFAULT NULL,

    -- Type & rendu
    type                ENUM('attestation_inscription','attestation_scolarite',
                             'convention_stage','releve_notes','certificat',
                             'courrier','recu','autre')
                                            NOT NULL DEFAULT 'autre',
    template            VARCHAR(100)        NOT NULL,  -- nom du template PHP/FPDF utilisé pour régénérer
    titre               VARCHAR(255)        NOT NULL,
    description         TEXT                DEFAULT NULL,

    -- Données d'interpolation (variables nécessaires à la régénération)
    data_json           JSON                DEFAULT NULL,

    -- Cycle de vie
    statut              ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'publie',
    visible_etudiant    TINYINT(1)          NOT NULL DEFAULT 1,
    notifie_etudiant    TINYINT(1)          NOT NULL DEFAULT 0,  -- email envoyé ?
    notifie_at          DATETIME            DEFAULT NULL,

    -- Validité (pour attestations datées)
    date_emission       DATE                NOT NULL,
    valide_jusqu_au     DATE                DEFAULT NULL,

    -- Suivi accès étudiant
    vu_etudiant_at      DATETIME            DEFAULT NULL,
    nb_telechargements  INT UNSIGNED        NOT NULL DEFAULT 0,

    -- Métadonnées
    cree_par_admin      VARCHAR(100)        DEFAULT NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uniq_reference (reference),
    KEY idx_etudiant (etudiant_id),
    KEY idx_candidature (candidature_id),
    KEY idx_type (type),
    KEY idx_statut (statut),
    CONSTRAINT fk_documents_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_documents_candidature
        FOREIGN KEY (candidature_id) REFERENCES candidatures(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 5) SESSIONS étudiantes (cookie de session côté serveur, plus sûr que PHP session par défaut)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS etudiant_sessions (
    id              CHAR(64)            NOT NULL,  -- token aléatoire (hex)
    etudiant_id     INT UNSIGNED        NOT NULL,
    ip              VARCHAR(45)         DEFAULT NULL,
    user_agent      VARCHAR(255)        DEFAULT NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at      DATETIME            NOT NULL,
    PRIMARY KEY (id),
    KEY idx_etudiant (etudiant_id),
    KEY idx_expires (expires_at),
    CONSTRAINT fk_sessions_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 6) TOKENS — activation de compte & reset mot de passe (usage unique)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS etudiant_tokens (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    etudiant_id     INT UNSIGNED        NOT NULL,
    type            ENUM('activation','reset_password') NOT NULL,
    token_hash      CHAR(64)            NOT NULL,  -- sha256 du token envoyé par mail
    expires_at      DATETIME            NOT NULL,
    used_at         DATETIME            DEFAULT NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_token (token_hash),
    KEY idx_etudiant (etudiant_id),
    KEY idx_expires (expires_at),
    CONSTRAINT fk_tokens_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 7) JOURNAL des actions étudiant (audit)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS etudiant_actions (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    etudiant_id     INT UNSIGNED        NOT NULL,
    action          VARCHAR(50)         NOT NULL,  -- login, logout, view_facture, download_doc, ...
    detail          VARCHAR(255)        DEFAULT NULL,
    ip              VARCHAR(45)         DEFAULT NULL,
    user_agent      VARCHAR(255)        DEFAULT NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_etudiant (etudiant_id),
    KEY idx_created (created_at),
    CONSTRAINT fk_etu_actions_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- FIN — Vérifie dans phpMyAdmin que les 6 nouvelles tables sont créées
-- et que la colonne `etudiant_id` est bien apparue dans `candidatures`.
-- =====================================================================
