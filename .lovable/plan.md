## Objectif

Arrêter de créer une nouvelle candidature à chaque passage / redoublement.
Une candidature = le dossier d'admission initial unique. Un étudiant
**progresse** : on met à jour son étape courante, on génère les nouvelles
factures et documents de l'année, mais on ne duplique plus son dossier.

## Modèle cible

```
etudiants (1) ──── (1) candidatures   ← dossier d'admission, unique
    │
    ├── factures (n)         ← chaque facture porte son année académique + étape
    └── documents (n)        ← idem (attestation année N, diplôme, etc.)
```

- `candidatures` : reste créée 1× à l'inscription. Plus jamais dupliquée.
  Garde `programme` / `annee` / `annee_academique` / `rentree` comme
  **valeurs initiales** (jamais réécrites — c'est l'historique d'admission).
- `etudiants` : porte désormais l'**état courant** du cursus
  (`etape_courante`, `annee_academique_courante`, `rentree_courante`).
  C'est ce qui pilote l'UI admin, le bouton "Passer à l'année suivante",
  les libellés d'attestation d'inscription définitive, etc.
- `factures` + `documents` : ajout d'une colonne `annee_academique`
  (déjà présente comme snapshot dans `data_json` côté documents, mais
  on la promeut en colonne pour le filtrage / regroupement). On garde
  `candidature_id` (pour rappel du dossier d'origine) mais le tri par
  année se fait sur cette colonne.

## Changements

### 1. Schéma — migration v7

```sql
ALTER TABLE etudiants
  ADD COLUMN etape_courante VARCHAR(10) NULL AFTER categorie,        -- 'PAA-1' ... 'PEA-2'
  ADD COLUMN annee_academique_courante VARCHAR(20) NULL,
  ADD COLUMN rentree_courante VARCHAR(50) NULL;

ALTER TABLE factures
  ADD COLUMN annee_academique VARCHAR(20) NULL AFTER libelle,
  ADD COLUMN etape_cursus VARCHAR(10) NULL AFTER annee_academique;   -- snapshot 'PAA-2'

ALTER TABLE documents
  ADD COLUMN annee_academique VARCHAR(20) NULL AFTER titre,
  ADD COLUMN etape_cursus VARCHAR(10) NULL AFTER annee_academique;

-- Backfill : remplir les colonnes depuis candidatures pour l'existant.
UPDATE factures f JOIN candidatures c ON c.id = f.candidature_id
   SET f.annee_academique = c.annee_academique;
UPDATE documents d JOIN candidatures c ON c.id = d.candidature_id
   SET d.annee_academique = c.annee_academique;

-- État courant des étudiants = candidature la plus récente (initialisation).
UPDATE etudiants e
   JOIN (SELECT etudiant_id, MAX(id) AS cid FROM candidatures GROUP BY etudiant_id) m
     ON m.etudiant_id = e.id
   JOIN candidatures c ON c.id = m.cid
   SET e.annee_academique_courante = c.annee_academique,
       e.rentree_courante = c.rentree;
-- (etape_courante : helper PHP la déduira au premier accès.)
```

Les colonnes `parent_candidature_id` et `type_inscription` (v5) deviennent
inutiles pour les nouveaux flux mais on les **conserve** pour ne pas casser
les candidatures déjà dupliquées en base. Aucune nouvelle ligne avec
`type_inscription != 'initiale'` ne sera créée.

### 2. `public/admin/_cursus.php`

- **Supprimer** `cursus_create_next_candidature()`.
- `cursus_evoluer($mode, $annee, $rentree)` réécrit :
  1. lit la candidature initiale + l'étudiant ;
  2. déduit l'étape courante depuis `etudiants.etape_courante`
     (fallback : depuis la candidature) ;
  3. calcule la prochaine étape (passage) ou garde la même (redoublement) ;
  4. **met à jour `etudiants`** : `etape_courante`, `annee_academique_courante`,
     `rentree_courante`, `categorie='etudiant'` (reste étudiant — règle
     mémorisée) ;
  5. appelle `etudiant_create_factures_scolarite()` adapté pour
     **ne plus prendre la candidature comme année cible** mais
     `($etudiant_id, $candidature_id_origine, $etape, $annee_aca, $rentree)` ;
  6. crée attestation de réussite + diplôme PAA-3 comme aujourd'hui,
     mais étiquetés via l'étape qui vient d'être validée (passé) plutôt
     que via la candidature parent.
- `cursus_describe_for($etudiant)` : prend désormais l'étudiant (pas la
  dernière candidature) et lit `etape_courante`.

### 3. `public/admin/_etudiants.php`

- `etudiant_create_factures_scolarite()` :
  - signature : `(PDO $pdo, array $etudiant, string $etape, string $anneeAca, string $rentree, ?int $candidatureId, string $adminUser)`.
  - écrit `annee_academique` + `etape_cursus` sur chaque facture créée.
  - garde-fou d'idempotence : `WHERE etudiant_id = ? AND annee_academique = ? AND type='scolarite'`.
- Génération attestation préadmission / inscription définitive :
  même pivot — basé sur `etape_courante` + `annee_academique_courante`.

### 4. APIs & PDFs

- `factures.php`, `documents.php` (admin + etudiant) :
  `ORDER BY annee_academique DESC, created_at DESC`, regroupement par
  `annee_academique` (déjà fait côté UI dans la dernière itération — il
  suffit de lire la colonne au lieu de joindre `candidatures`).
- `facture-pdf.php`, `_pdf_classes.php` : les en-têtes "Année académique"
  lisent `factures.annee_academique` (au lieu de joindre `candidatures.annee_academique`
  qui aurait pu changer dans l'ancien modèle).

### 5. Front admin (`AdminCursusActions.tsx`)

- Reçoit `etudiant.etape_courante` au lieu de la dériver des candidatures.
- Le carton "Historique cursus" (qui listait les candidatures successives)
  est remplacé par "Historique des années" listant
  `factures.annee_academique` distinct + étape associée.
- Bouton "Passer à l'année suivante" : inchangé côté UX, appelle la même
  API mais celle-ci ne crée plus de candidature.

### 6. UI étudiant (factures + documents)

Déjà groupées par année académique — la donnée vient maintenant de la
colonne dédiée, pas d'un join sur `candidatures`. Aucune nouvelle UX.

### 7. Nettoyage / mémoires

- Mettre à jour la mémoire `mem://features/cursus-evolution` :
  > La progression ne crée plus de nouvelle candidature. L'état du cursus
  > vit sur `etudiants.etape_courante` + `annee_academique_courante`.
  > Les factures/documents portent leur propre `annee_academique`.

## Hors scope

- Pas de migration destructive : les candidatures dupliquées passées
  (type_inscription='passage'/'redoublement') restent en base, simplement
  plus produites. Si tu veux qu'on les fusionne plus tard, on en fera
  une mig dédiée.
- Le formulaire d'inscription publique n'est pas touché.
- Le site vitrine n'est pas touché.

## Validation

- `bun run build` (TS strict).
- Vérifier que les écrans admin/étudiant continuent d'afficher les
  factures/documents existants groupés par année (backfill OK).
- Tester sur un étudiant fictif : passer PAA-1 → PAA-2 doit
  • mettre à jour `etudiants.etape_courante='PAA-2'`,
  • générer 3 factures de scolarité avec `annee_academique` = celle saisie,
  • générer attestation de réussite PAA-1,
  • **ne créer aucune nouvelle ligne dans `candidatures`**.
