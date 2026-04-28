-- =====================================================================
-- IPEC — Schéma de base de données (MySQL/MariaDB sur n0c)
-- À exécuter UNE SEULE FOIS via phpMyAdmin :
--   1. cPanel → phpMyAdmin
--   2. Sélectionnez la base : txuxaqftdr_IPEC_Website
--   3. Onglet "SQL" → collez ce fichier → Exécuter
-- =====================================================================

CREATE TABLE IF NOT EXISTS candidatures (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    reference       VARCHAR(32)         NOT NULL,
    -- Statut du dossier (admin gérera plus tard via UPDATE manuel)
    statut          ENUM('recue','en_cours','validee','refusee','annulee')
                                        NOT NULL DEFAULT 'recue',

    -- Identité
    civilite        VARCHAR(30)         DEFAULT NULL,
    prenom          VARCHAR(100)        NOT NULL,
    nom             VARCHAR(100)        NOT NULL,
    date_naissance  VARCHAR(20)         DEFAULT NULL,
    nationalite     VARCHAR(100)        DEFAULT NULL,

    -- Contact
    email           VARCHAR(255)        NOT NULL,
    telephone       VARCHAR(30)         DEFAULT NULL,

    -- Adresse
    rue             VARCHAR(150)        DEFAULT NULL,
    numero          VARCHAR(20)         DEFAULT NULL,
    code_postal     VARCHAR(20)         DEFAULT NULL,
    ville           VARCHAR(100)        DEFAULT NULL,
    pays_residence  VARCHAR(100)        DEFAULT NULL,

    -- Programme
    programme       VARCHAR(10)         DEFAULT NULL,
    annee           VARCHAR(80)         DEFAULT NULL,
    specialisation  VARCHAR(80)         DEFAULT NULL,
    rentree         VARCHAR(120)        DEFAULT NULL,
    annee_academique VARCHAR(20)        DEFAULT NULL,

    -- Message libre du candidat
    message         TEXT                DEFAULT NULL,

    -- Facture associée (générée à la volée mais on garde le n° pour traçabilité)
    facture_numero  VARCHAR(40)         DEFAULT NULL,

    -- Métadonnées techniques (preuve eIDAS)
    ip              VARCHAR(45)         DEFAULT NULL,
    user_agent      VARCHAR(255)        DEFAULT NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uniq_reference (reference),
    KEY idx_email     (email),
    KEY idx_created   (created_at),
    KEY idx_statut    (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
