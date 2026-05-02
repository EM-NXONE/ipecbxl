<?php
/**
 * IPEC Admin — Helpers gestion des comptes étudiants
 *
 * Régles :
 *  - Création MANUELLE par l'admin depuis une fiche candidature.
 *  - Auth = PHP natif bcrypt (pas de Supabase).
 *  - Le compte est créé ACTIF avec le mot de passe par défaut "Student1".
 *    L'étudiant peut le changer ensuite depuis son espace (/etudiant/profil).
 *  - Numéro étudiant format IPEC-ETU-AAAA-XXXX (4 hex majuscules).
 */

declare(strict_types=1);

/** Mot de passe par défaut pour tout compte étudiant créé/réinitialisé par l'admin. */
const ETU_DEFAULT_PASSWORD = 'Student1';

/**
 * Génère un numéro étudiant unique : IPEC-ETU-AAAA-XXXX
 */
function etudiant_generate_numero(PDO $pdo): string {
    $year = date('Y');
    for ($i = 0; $i < 6; $i++) {
        $suffix = strtoupper(bin2hex(random_bytes(2))); // 4 hex
        $num = 'IPEC-ETU-' . $year . '-' . $suffix;
        $stmt = $pdo->prepare("SELECT 1 FROM etudiants WHERE numero_etudiant = ? LIMIT 1");
        $stmt->execute([$num]);
        if (!$stmt->fetchColumn()) {
            return $num;
        }
    }
    return 'IPEC-ETU-' . $year . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Cherche un étudiant par identité civile : prénom + nom + date de naissance.
 * L'e-mail reste un contact/login, pas une clé d'identité.
 */
function etudiant_find_by_identity(PDO $pdo, string $prenom, string $nom, ?string $dateNaissance): ?array {
    $dateNaissance = trim((string)$dateNaissance);
    if (trim($prenom) === '' || trim($nom) === '' || $dateNaissance === '') {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM etudiants
                           WHERE LOWER(TRIM(prenom)) = LOWER(TRIM(?))
                             AND LOWER(TRIM(nom)) = LOWER(TRIM(?))
                             AND date_naissance = ?
                           LIMIT 1");
    $stmt->execute([trim($prenom), trim($nom), $dateNaissance]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Crée un token de réinitialisation (legacy — conservé pour compat reset_password
 * éventuel). N'est plus utilisé pour l'activation initiale (mdp par défaut).
 */
function etudiant_create_token(PDO $pdo, int $etudiantId, string $type = 'reset_password', int $ttlSeconds = 7 * 24 * 3600): string {
    if (!in_array($type, ['reset_password'], true)) {
        throw new InvalidArgumentException('Type token invalide.');
    }
    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);
    $exp   = date('Y-m-d H:i:s', time() + $ttlSeconds);
    $pdo->prepare(
        "INSERT INTO etudiant_tokens (etudiant_id, type, token_hash, expires_at)
         VALUES (?, ?, ?, ?)"
    )->execute([$etudiantId, $type, $hash, $exp]);
    return $token;
}

/**
 * Crée un compte étudiant à partir d'une candidature et le rattache.
 *
 * @return array{etudiant_id:int, numero:string, default_password:string, deja_existant:bool}
 */
function etudiant_create_from_candidature(PDO $pdo, array $candidature, string $adminUser): array {
    $email = trim(strtolower((string)$candidature['email']));
    if ($email === '') {
        throw new RuntimeException("La candidature n'a pas d'e-mail.");
    }
    if (trim((string)($candidature['prenom'] ?? '')) === '' || trim((string)($candidature['nom'] ?? '')) === '' || trim((string)($candidature['date_naissance'] ?? '')) === '') {
        throw new RuntimeException("Prénom, nom et date de naissance sont requis pour créer ou rattacher un compte étudiant.");
    }

    $existing = etudiant_find_by_identity($pdo, (string)$candidature['prenom'], (string)$candidature['nom'], (string)$candidature['date_naissance']);
    if ($existing) {
        // Rattache la candidature s'il manque le lien
        if (empty($candidature['etudiant_id']) || (int)$candidature['etudiant_id'] !== (int)$existing['id']) {
            $pdo->prepare("UPDATE candidatures SET etudiant_id = ? WHERE id = ?")
                ->execute([(int)$existing['id'], (int)$candidature['id']]);
        }
        // Si le compte existait sans mot de passe (cas legacy), on lui pose le mot de passe par défaut.
        if (empty($existing['password_hash'])) {
            $pdo->prepare("UPDATE etudiants SET password_hash=?, email_verifie=1, statut='actif' WHERE id=?")
                ->execute([password_hash(ETU_DEFAULT_PASSWORD, PASSWORD_BCRYPT), (int)$existing['id']]);
        }
        // (Re)synchronise les documents historiques pour cette candidature
        etudiant_sync_documents_historiques($pdo, (int)$existing['id'], $candidature, $adminUser);
        return [
            'etudiant_id'      => (int)$existing['id'],
            'numero'           => (string)$existing['numero_etudiant'],
            'default_password' => ETU_DEFAULT_PASSWORD,
            'deja_existant'    => true,
        ];
    }

    $pdo->beginTransaction();
    try {
        $numero = etudiant_generate_numero($pdo);
        $hash   = password_hash(ETU_DEFAULT_PASSWORD, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "INSERT INTO etudiants
                (email, password_hash, email_verifie,
                 civilite, prenom, nom, date_naissance, nationalite, telephone,
                 numero_etudiant, statut, cree_par_admin)
             VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, 'actif', ?)"
        );
        $stmt->execute([
            $email,
            $hash,
            $candidature['civilite'] ?: null,
            $candidature['prenom'],
            $candidature['nom'],
            $candidature['date_naissance'] ?: null,
            $candidature['nationalite'] ?: null,
            $candidature['telephone'] ?: null,
            $numero,
            $adminUser,
        ]);
        $etuId = (int)$pdo->lastInsertId();

        $pdo->prepare("UPDATE candidatures SET etudiant_id = ? WHERE id = ?")
            ->execute([$etuId, (int)$candidature['id']]);

        // Synchronise les documents historiques de la candidature dans factures + documents.
        etudiant_sync_documents_historiques($pdo, $etuId, $candidature, $adminUser);

        $pdo->commit();
        return [
            'etudiant_id'      => $etuId,
            'numero'           => $numero,
            'default_password' => ETU_DEFAULT_PASSWORD,
            'deja_existant'    => false,
        ];
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Génère une référence courte (IPEC-FACT-AAAA-XXXXXX ou IPEC-DOC-AAAA-XXXXXX).
 */
function etudiant_generate_ref(PDO $pdo, string $kind): string {
    $kind = strtoupper($kind); // FACT | DOC
    $table = $kind === 'FACT' ? 'factures' : 'documents';
    $col   = $kind === 'FACT' ? 'numero'   : 'reference';
    $year = date('Y');
    for ($i = 0; $i < 6; $i++) {
        $suffix = strtoupper(bin2hex(random_bytes(3)));
        $ref = 'IPEC-' . $kind . '-' . $year . '-' . $suffix;
        $stmt = $pdo->prepare("SELECT 1 FROM `$table` WHERE `$col` = ? LIMIT 1");
        $stmt->execute([$ref]);
        if (!$stmt->fetchColumn()) return $ref;
    }
    return 'IPEC-' . $kind . '-' . $year . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Insère (idempotent) la facture des frais de dossier (400 €) + le document
 * récap candidature pour qu'ils apparaissent dans l'espace étudiant.
 *
 * Idempotent : on ne crée pas de doublon si déjà présent pour cette candidature.
 */
function etudiant_sync_documents_historiques(PDO $pdo, int $etudiantId, array $candidature, string $adminUser): void {
    $candId = (int)$candidature['id'];

    // ---- 1) Facture frais de dossier 400 € ----
    $stmt = $pdo->prepare("SELECT id FROM factures
                            WHERE candidature_id = ? AND type = 'frais_dossier' LIMIT 1");
    $stmt->execute([$candId]);
    if (!$stmt->fetchColumn()) {
        $numero = (string)($candidature['facture_numero'] ?? '') ?: etudiant_generate_ref($pdo, 'FACT');
        $payee  = !empty($candidature['facture_payee']);
        $emis   = !empty($candidature['created_at'])
            ? date('Y-m-d', strtotime((string)$candidature['created_at']))
            : date('Y-m-d');
        $pdo->prepare(
            "INSERT INTO factures
                (numero, etudiant_id, candidature_id, type, libelle, description,
                 montant_ht_cents, tva_taux, montant_ttc_cents, devise,
                 date_emission, date_echeance,
                 statut_paiement, paye_at, paye_par_admin, moyen_paiement,
                 visible_etudiant, cree_par_admin)
             VALUES (?, ?, ?, 'frais_dossier', ?, ?,
                     40000, 0.00, 40000, 'EUR',
                     ?, ?,
                     ?, ?, ?, ?,
                     1, ?)"
        )->execute([
            $numero, $etudiantId, $candId,
            'Frais de dossier IPEC',
            'Traitement de la candidature ' . ($candidature['reference'] ?? ''),
            $emis, $emis,
            $payee ? 'payee' : 'en_attente',
            $payee ? ($candidature['created_at'] ?? date('Y-m-d H:i:s')) : null,
            $payee ? $adminUser : null,
            $payee ? 'virement' : null,
            $adminUser,
        ]);
    }

    // ---- 2) Document : récapitulatif de candidature ----
    $stmt = $pdo->prepare("SELECT id FROM documents
                            WHERE candidature_id = ? AND template = 'recap_candidature' LIMIT 1");
    $stmt->execute([$candId]);
    if (!$stmt->fetchColumn()) {
        $ref = etudiant_generate_ref($pdo, 'DOC');
        $emis = !empty($candidature['created_at'])
            ? date('Y-m-d', strtotime((string)$candidature['created_at']))
            : date('Y-m-d');
        // On stocke TOUT le contenu de la candidature dans data_json pour
        // permettre à buildCandidaturePdf() de régénérer le PDF à l'identique.
        // On n'inclut pas les champs binaires/lourds (il n'y en a pas ici).
        $data = $candidature;
        unset($data['etudiant_id']); // bruit interne
        $pdo->prepare(
            "INSERT INTO documents
                (reference, etudiant_id, candidature_id, type, template,
                 titre, description, data_json, statut, visible_etudiant,
                 date_emission, cree_par_admin)
             VALUES (?, ?, ?, 'autre', 'recap_candidature',
                     ?, ?, ?, 'publie', 1,
                     ?, ?)"
        )->execute([
            $ref, $etudiantId, $candId,
            'Récapitulatif de candidature',
            null,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            $emis, $adminUser,
        ]);
    }
}

/* =====================================================================
 * FACTURES DE SCOLARITÉ — génération automatique en 3 tranches
 *
 * Conditions de déclenchement (toutes requises) :
 *   - candidature.statut       = 'validee'   (= "Acceptée" côté admin)
 *   - candidature.facture_payee = 1          (frais de dossier 400 € encaissés)
 *   - candidature rattachée à un compte etudiants.id
 *
 * Plan (cf. CGV /cgv et /admissions) :
 *   - PAA : 3000 € + 1475 € + 1475 €  (total 5950 €)
 *   - PEA : 3000 € + 2075 € + 2075 €  (total 7150 €)
 *
 * Échéances (calendaires, basées sur la rentrée choisie) :
 *   - T1 (3 000 €) : à la confirmation d'inscription → +30 jours
 *   - T2 (solde/2) : ~15 jours avant la date de rentrée
 *   - T3 (solde/2) : 31 janvier de l'année académique
 *                    (ou +6 mois après T2 pour rentrée février)
 *
 * Idempotent : si une facture de type 'scolarite' existe déjà pour
 * (etudiant_id, candidature_id), on ne fait rien.
 *
 * @return array{created:bool, count:int, reason?:string}
 * ===================================================================== */
function etudiant_create_factures_scolarite(PDO $pdo, array $candidature, string $adminUser): array {
    // --- Garde-fous ---
    if (($candidature['statut'] ?? '') !== 'validee') {
        return ['created' => false, 'count' => 0, 'reason' => 'statut non validé'];
    }
    if (empty($candidature['facture_payee'])) {
        return ['created' => false, 'count' => 0, 'reason' => 'frais de dossier non payés'];
    }
    if (empty($candidature['etudiant_id'])) {
        return ['created' => false, 'count' => 0, 'reason' => 'aucun compte étudiant rattaché'];
    }
    $etuId  = (int)$candidature['etudiant_id'];
    $candId = (int)$candidature['id'];

    // Idempotence : si déjà une facture scolarité, on s'arrête.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM factures
                            WHERE candidature_id = ? AND type = 'scolarite'");
    $stmt->execute([$candId]);
    if ((int)$stmt->fetchColumn() > 0) {
        return ['created' => false, 'count' => 0, 'reason' => 'déjà générées'];
    }

    // --- Programme : PAA vs PEA (prend le préfixe du libellé) ---
    $progRaw = strtoupper(trim((string)($candidature['programme'] ?? '')));
    $isPEA = (strpos($progRaw, 'PEA') === 0);
    $isPAA = (strpos($progRaw, 'PAA') === 0);
    if (!$isPAA && !$isPEA) {
        return ['created' => false, 'count' => 0, 'reason' => 'programme inconnu (ni PAA ni PEA)'];
    }
    $progLabel = $isPEA ? 'PEA' : 'PAA';

    // Montants en centimes (TTC, exonérés TVA enseignement)
    $t1Cents = 300000; // 3 000 €
    $soldeCents = $isPEA ? 415000 : 295000;     // 4 150 € (PEA) / 2 950 € (PAA)
    $trancheSolde = (int)round($soldeCents / 2); // 2 075 € (PEA) / 1 475 € (PAA)

    // --- Échéances ---
    $today = new DateTimeImmutable('today');
    $emission = $today->format('Y-m-d');

    // T1 : à la confirmation d'inscription → 30 jours
    $t1Echeance = $today->modify('+30 days')->format('Y-m-d');

    // Tente d'extraire la date de rentrée depuis candidature.rentree.
    // Format attendu (cf. inscription.tsx) : "Septembre — JJ/MM/AAAA" ou "Février — JJ/MM/AAAA".
    $rentreeStr = (string)($candidature['rentree'] ?? '');
    $rentreeDate = null;
    if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $rentreeStr, $m)) {
        $rentreeDate = DateTimeImmutable::createFromFormat('!d/m/Y', "{$m[1]}/{$m[2]}/{$m[3]}");
    }
    $isFebruary = (stripos($rentreeStr, 'février') !== false || stripos($rentreeStr, 'fevrier') !== false);

    if ($rentreeDate) {
        // T2 : 15 jours avant la rentrée
        $t2Echeance = $rentreeDate->modify('-15 days')->format('Y-m-d');
        if ($isFebruary) {
            // T3 : +6 mois après T2 pour les rentrées de février
            $t3Echeance = $rentreeDate->modify('-15 days')->modify('+6 months')->format('Y-m-d');
        } else {
            // T3 : 31 janvier de l'année académique (l'année civile suivant la rentrée septembre)
            $t3Year = (int)$rentreeDate->format('Y') + 1;
            $t3Echeance = $t3Year . '-01-31';
        }
    } else {
        // Fallback si la rentrée n'est pas parsable : étalement +60j / +180j
        $t2Echeance = $today->modify('+60 days')->format('Y-m-d');
        $t3Echeance = $today->modify('+180 days')->format('Y-m-d');
    }

    // S'assurer qu'aucune échéance n'est antérieure à T1
    if ($t2Echeance < $t1Echeance) $t2Echeance = $t1Echeance;
    if ($t3Echeance < $t2Echeance) $t3Echeance = $t2Echeance;

    $rentreeLabel = $rentreeStr !== '' ? $rentreeStr : 'rentrée à venir';

    $tranches = [
        [
            'libelle'     => "Frais de scolarité {$progLabel} — 1ʳᵉ tranche",
            'description' => "Première tranche due à la confirmation d'inscription ({$rentreeLabel}).",
            'montant'     => $t1Cents,
            'echeance'    => $t1Echeance,
        ],
        [
            'libelle'     => "Frais de scolarité {$progLabel} — 2ᵉ tranche",
            'description' => "Deuxième tranche exigible avant le début du programme ({$rentreeLabel}).",
            'montant'     => $trancheSolde,
            'echeance'    => $t2Echeance,
        ],
        [
            'libelle'     => "Frais de scolarité {$progLabel} — 3ᵉ tranche (solde)",
            'description' => $isFebruary
                ? "Solde des droits de scolarité — exigible 6 mois après le début du programme."
                : "Solde des droits de scolarité — exigible avant le 31 janvier de l'année académique.",
            'montant'     => $trancheSolde,
            'echeance'    => $t3Echeance,
        ],
    ];

    $pdo->beginTransaction();
    try {
        $insert = $pdo->prepare(
            "INSERT INTO factures
                (numero, etudiant_id, candidature_id, type, libelle, description,
                 montant_ht_cents, tva_taux, montant_ttc_cents, devise,
                 date_emission, date_echeance,
                 statut_paiement, visible_etudiant, cree_par_admin)
             VALUES (?, ?, ?, 'scolarite', ?, ?,
                     ?, 0.00, ?, 'EUR',
                     ?, ?,
                     'en_attente', 1, ?)"
        );
        foreach ($tranches as $t) {
            $numero = etudiant_generate_ref($pdo, 'FACT');
            $insert->execute([
                $numero, $etuId, $candId,
                $t['libelle'], $t['description'],
                $t['montant'], $t['montant'],
                $emission, $t['echeance'],
                $adminUser,
            ]);
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return ['created' => true, 'count' => count($tranches)];
}

