-- =====================================================================
-- IPEC Admin — Migration à exécuter UNE SEULE FOIS via phpMyAdmin
-- Base : txuxaqftdr_IPEC_Website
-- =====================================================================

-- 1) Suivi du paiement de la facture des frais de dossier (400 €)
ALTER TABLE candidatures
  ADD COLUMN facture_payee TINYINT(1) NOT NULL DEFAULT 0 AFTER facture_numero,
  ADD COLUMN facture_payee_at DATETIME NULL DEFAULT NULL AFTER facture_payee,
  ADD COLUMN facture_payee_par VARCHAR(100) NULL DEFAULT NULL AFTER facture_payee_at;

-- 2) Journal des actions admin (audit trail)
CREATE TABLE IF NOT EXISTS admin_actions (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    candidature_id  INT UNSIGNED        NOT NULL,
    action          VARCHAR(50)         NOT NULL,
    detail          VARCHAR(255)        DEFAULT NULL,
    admin_user      VARCHAR(100)        NOT NULL,
    ip              VARCHAR(45)         DEFAULT NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_candidature (candidature_id),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
