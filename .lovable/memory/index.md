# Project Memory

## Core
Frais de dossier IPEC = 400 € (paiement unique, non remboursables). Affichés sur /admissions et /cgv.
Email candidat: 2 PDF joints (récap candidature + facture frais 400€). Pas de carton récap dans le HTML.
Bouton CTA mail = "Soumettez votre dossier complet" → mailto: avec In-Reply-To pour s'attacher au fil.
Backend = PHP sur n0c (mailer.php + FPDF + PHPMailer). Pas de stack Node/Cloud côté backend.
Auth étudiant = PHP natif bcrypt + sessions serveur (table etudiant_sessions). Pas de Supabase/Cloud.
PDF jamais stockés sur disque : toujours régénérés à la volée depuis SQL + template FPDF.

## Memories
- [DB schema étudiants](mem://features/db-schema-etudiants) — Tables etudiants, factures, documents, sessions, tokens, audit. Migration v2.
