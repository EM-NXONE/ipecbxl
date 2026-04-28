-- =====================================================================
-- IPEC — Migration V3 : identité étudiant ≠ e-mail
-- À exécuter via phpMyAdmin si la migration V2 a déjà été appliquée.
--
-- Objectif : permettre à plusieurs étudiants différents de partager le
-- même e-mail, et utiliser prénom + nom + date de naissance pour le
-- rattachement candidature → compte étudiant.
-- =====================================================================

-- 1) Supprime l'ancienne contrainte unique sur email si elle existe.
-- Si phpMyAdmin indique que l'index n'existe pas, ignore simplement cette ligne.
ALTER TABLE etudiants DROP INDEX uniq_email;

-- 2) Remplace par un index simple pour garder des recherches rapides par email.
ALTER TABLE etudiants ADD INDEX idx_email (email);

-- 3) Ajoute l'index d'identité civile utilisé par l'admin.
ALTER TABLE etudiants ADD INDEX idx_identite (nom, prenom, date_naissance);