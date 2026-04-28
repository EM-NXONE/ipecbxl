# Project Memory

## Core
Frais de dossier IPEC = 400 € (paiement unique, non remboursables). Affichés sur /admissions et /cgv.
Email candidat: 2 PDF joints (récap candidature + facture frais 400€). Pas de carton récap dans le HTML.
Bouton CTA mail = "Soumettez votre dossier complet" → mailto: avec In-Reply-To pour s'attacher au fil.
Backend = PHP sur n0c (mailer.php + FPDF + PHPMailer). Pas de stack Node/Cloud côté backend.
Auth étudiant = PHP natif bcrypt + sessions BDD (cookie IPEC_ETU httpOnly, 30j rolling). Pas de Supabase.
PDF jamais stockés sur disque : toujours régénérés à la volée depuis SQL + template FPDF.
Portail étudiant = sous-domaine **lms.ipec.school** (DocumentRoot n0c → public_html/etudiant). Liens internes via etu_url(), liens absolus (mail/admin) via https://lms.ipec.school. Cookie path adapté automatiquement (/etudiant/ legacy ou / sur lms).

## Memories
- [DB schema étudiants](mem://features/db-schema-etudiants) — Tables etudiants, factures, documents, sessions, tokens, audit. Migration v2.
- [Espace étudiant](mem://features/espace-etudiant) — Pages PHP, auth, génération PDF à la volée, lien admin, sous-domaine lms.ipec.school.
