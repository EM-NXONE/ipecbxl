# Project Memory

## Core
Frais de dossier IPEC = 400 € (paiement unique, non remboursables). Affichés sur /admissions et /cgv.
Email candidat: 2 PDF joints (récap candidature + facture frais 400€). Pas de carton récap dans le HTML.
Bouton CTA mail = "Soumettez votre dossier complet" → mailto: avec In-Reply-To pour s'attacher au fil.
Backend = PHP sur n0c (mailer.php + FPDF + PHPMailer). Pas de stack Node/Cloud côté backend.
3 builds Vite séparés via STATIC_BUILD=site|admin|etu, exposés au client via import.meta.env.VITE_PORTAL.
Sous-domaines admin.ipec.school et lms.ipec.school : JAMAIS d'indexation (noindex meta + X-Robots-Tag + robots.txt Disallow), JAMAIS le site vitrine (.htaccess 301 hors /admin /etudiant /api + garde RootComponent).
Références documents : SEULEMENT celles générées par generateDocumentReference() (db_config.php) avec check d'unicité BDD. JAMAIS de fallback timestamp inventé. Reçu = sha1 déterministe de la réf facture. Communication structurée belge dérivée déterministiquement (sha256) de la référence facture → unique par facture, jamais 2 identiques.
