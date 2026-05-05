-- =====================================================================
-- IPEC — Migration V5 : Signature électronique des documents (OTP email)
-- À exécuter UNE SEULE FOIS via phpMyAdmin (onglet SQL).
--
-- Workflow :
--   1. L'étudiant ouvre /etudiant/documents et clique « Signer » sur un
--      document signable (ex : formulaire standard d'inscription).
--   2. Un OTP à 6 chiffres est généré, hashé (bcrypt) et stocké dans
--      `signatures_otp`. L'OTP en clair est envoyé par e-mail à l'étudiant.
--   3. L'étudiant saisit le code dans l'UI ; on vérifie hash + expiration +
--      tentatives. Si OK : `documents.signed_at` est rempli, l'OTP marqué
--      `consumed_at`, et un bloc signature électronique apparaît sur le PDF
--      (régénéré à la volée).
--   4. La page /verification affiche le statut « signé électroniquement ».
--
-- Conformité : SES eIDAS (Signature Électronique Simple). Recevable en
-- justice avec preuves : OTP utilisé (hash), e-mail destinataire, IP,
-- user-agent, timestamp, référence + hash du document signé.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Colonnes de signature sur `documents`
-- ---------------------------------------------------------------------
ALTER TABLE documents
    ADD COLUMN signed_at           DATETIME     NULL DEFAULT NULL AFTER nb_telechargements,
    ADD COLUMN signature_meta_json JSON         NULL DEFAULT NULL AFTER signed_at,
    ADD KEY idx_signed (signed_at);

-- ---------------------------------------------------------------------
-- 2) Table `signatures_otp` — OTP en cours / consommés (audit trail)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS signatures_otp (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    etudiant_id         INT UNSIGNED        NOT NULL,
    document_id         INT UNSIGNED        NOT NULL,
    document_reference  VARCHAR(40)         NOT NULL,
    document_hash       CHAR(64)            NOT NULL,   -- sha256 du PDF tel que signé
    email               VARCHAR(255)        NOT NULL,   -- destinataire OTP
    code_hash           VARCHAR(255)        NOT NULL,   -- bcrypt
    attempts            TINYINT UNSIGNED    NOT NULL DEFAULT 0,
    expires_at          DATETIME            NOT NULL,
    consumed_at         DATETIME            NULL DEFAULT NULL,
    ip                  VARCHAR(45)         DEFAULT NULL,
    user_agent          VARCHAR(255)        DEFAULT NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_etudiant (etudiant_id),
    KEY idx_document (document_id),
    KEY idx_expires (expires_at),
    CONSTRAINT fk_sigotp_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sigotp_document
        FOREIGN KEY (document_id) REFERENCES documents(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- FIN — Vérifie la présence des colonnes signed_at / signature_meta_json
-- sur `documents` et de la table `signatures_otp`.
-- =====================================================================
