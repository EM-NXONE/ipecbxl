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

// _academic_dates.php + _etu_notify.php sont packagés soit à côté (admin/_shared, site/),
// soit dans le parent (dev: public/admin/ → public/*.php).
(function(){
    foreach (['_academic_dates.php', '_etu_notify.php'] as $f) {
        foreach ([__DIR__ . '/' . $f, __DIR__ . '/../' . $f] as $p) {
            if (is_file($p)) { require_once $p; break; }
        }
    }
})();

/** Mot de passe par défaut pour tout compte étudiant créé/réinitialisé par l'admin. */
const ETU_DEFAULT_PASSWORD = 'Student1';

/**
 * Déduit la clé d'étape ('PAA-1' ... 'PEA-2') depuis programme + libellé année.
 * Renvoie null si non identifiable.
 */
function etudiant_step_from_programme_annee(?string $programme, ?string $annee): ?string {
    $p = strtoupper(trim((string)$programme));
    if (strpos($p, 'PEA') === 0) $base = 'PEA';
    elseif (strpos($p, 'PAA') === 0) $base = 'PAA';
    else return null;
    if (!preg_match('/(\d)/u', (string)$annee, $m)) return null;
    return $base . '-' . $m[1];
}

/**
 * Renvoie l'état courant du cursus d'un étudiant :
 *   ['etape' => 'PAA-2', 'annee_academique' => '2027-2028', 'rentree' => 'Rentrée principale']
 *
 * Priorité : colonnes etudiants.etape_courante / annee_academique_courante /
 * rentree_courante (renseignées par cursus_evoluer). Sinon, fallback sur la
 * candidature initiale fournie en paramètre.
 */
function etudiant_current_cursus(?array $etudiant, ?array $candidatureFallback = null): array {
    $etape  = $etudiant['etape_courante'] ?? null;
    $annee  = $etudiant['annee_academique_courante'] ?? null;
    $rentree= $etudiant['rentree_courante'] ?? null;
    if (!$etape && $candidatureFallback) {
        $etape = etudiant_step_from_programme_annee(
            $candidatureFallback['programme'] ?? null,
            $candidatureFallback['annee']     ?? null
        );
    }
    if (!$annee   && $candidatureFallback) $annee   = $candidatureFallback['annee_academique'] ?? null;
    if (!$rentree && $candidatureFallback) $rentree = $candidatureFallback['rentree']          ?? null;
    return ['etape' => $etape, 'annee_academique' => $annee, 'rentree' => $rentree];
}

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
/**
 * Crée (idempotent) un compte étudiant minimal au moment de la SOUMISSION
 * d'une candidature. Le compte démarre en categorie='candidat' avec le mot
 * de passe par défaut "Student1" (l'étudiant pourra le changer ensuite).
 *
 * Si un compte existe déjà pour cette identité civile, on le rattache à la
 * candidature SANS écraser sa catégorie ni son mot de passe.
 *
 * Pensé pour être appelé depuis mailer.php juste après l'INSERT INTO
 * candidatures. NE doit PAS faire échouer la soumission en cas d'erreur :
 * l'appelant capture les exceptions.
 *
 * @return array{etudiant_id:int, numero:string, deja_existant:bool}
 */
function etudiant_create_minimal_for_candidature(PDO $pdo, int $candidatureId, array $candidature): array {
    if (trim((string)($candidature['prenom'] ?? '')) === ''
        || trim((string)($candidature['nom'] ?? '')) === ''
        || trim((string)($candidature['date_naissance'] ?? '')) === '') {
        throw new RuntimeException("Identité incomplète (prénom/nom/date_naissance) pour créer le compte candidat.");
    }
    $email = trim(strtolower((string)($candidature['email'] ?? '')));
    if ($email === '') throw new RuntimeException("E-mail manquant pour créer le compte candidat.");

    $existing = etudiant_find_by_identity($pdo, (string)$candidature['prenom'], (string)$candidature['nom'], (string)$candidature['date_naissance']);
    if ($existing) {
        $pdo->prepare("UPDATE candidatures SET etudiant_id = ? WHERE id = ?")
            ->execute([(int)$existing['id'], $candidatureId]);
        return [
            'etudiant_id'   => (int)$existing['id'],
            'numero'        => (string)$existing['numero_etudiant'],
            'deja_existant' => true,
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
                 numero_etudiant, statut, categorie, cree_par_admin)
             VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?, ?, 'actif', 'candidat', ?)"
        );
        $stmt->execute([
            $email, $hash,
            $candidature['civilite']       ?: null,
            $candidature['prenom'],
            $candidature['nom'],
            $candidature['date_naissance'] ?: null,
            $candidature['nationalite']    ?: null,
            $candidature['telephone']      ?: null,
            $numero,
            'auto:soumission',
        ]);
        $etuId = (int)$pdo->lastInsertId();
        $pdo->prepare("UPDATE candidatures SET etudiant_id = ? WHERE id = ?")
            ->execute([$etuId, $candidatureId]);
        $pdo->commit();
        // Mail de bienvenue (non bloquant) — uniquement à la création initiale.
        // Le lien renvoie sur la page de connexion en demandant un retour vers
        // /etudiant/profil, où l'étudiant pourra changer son mot de passe.
        if (function_exists('etu_notify_send_welcome')) {
            $firstLoginUrl = 'https://lms.ipec.school/etudiant/login?next=/etudiant/profil';
            try { etu_notify_send_welcome($pdo, $etuId, ETU_DEFAULT_PASSWORD, $firstLoginUrl); }
            catch (\Throwable $e) { error_log('[etudiant_create_minimal] welcome mail: ' . $e->getMessage()); }
        }
        return ['etudiant_id' => $etuId, 'numero' => $numero, 'deja_existant' => false];
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Met à jour la catégorie d'un étudiant (candidat → preadmis → etudiant),
 * uniquement vers une catégorie "supérieure" (jamais de retour arrière auto).
 */
function etudiant_set_categorie(PDO $pdo, int $etudiantId, string $newCategorie): void {
    $order = ['candidat' => 1, 'preadmis' => 2, 'etudiant' => 3];
    if (!isset($order[$newCategorie])) return;
    $stmt = $pdo->prepare("SELECT categorie FROM etudiants WHERE id = ?");
    $stmt->execute([$etudiantId]);
    $cur = (string)($stmt->fetchColumn() ?: 'candidat');
    if (($order[$cur] ?? 0) >= $order[$newCategorie]) return;
    $pdo->prepare("UPDATE etudiants SET categorie = ? WHERE id = ?")
        ->execute([$newCategorie, $etudiantId]);
}

/**
 * Promeut un étudiant en categorie='etudiant' si au moins une facture de
 * scolarité (n'importe quelle tranche, généralement la T1) est marquée payée.
 * Idempotent.
 */
function etudiant_promote_if_scolarite_paid(PDO $pdo, int $etudiantId): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM factures
                            WHERE etudiant_id = ? AND type = 'scolarite' AND statut_paiement = 'payee'");
    $stmt->execute([$etudiantId]);
    if ((int)$stmt->fetchColumn() <= 0) return false;
    etudiant_set_categorie($pdo, $etudiantId, 'etudiant');
    return true;
}

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
                 numero_etudiant, statut, categorie, cree_par_admin)
             VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, 'actif', 'candidat', ?)"
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
        if (function_exists('etu_notify_send_welcome')) {
            $firstLoginUrl = 'https://lms.ipec.school/etudiant/login?next=/etudiant/profil';
            try { etu_notify_send_welcome($pdo, $etuId, ETU_DEFAULT_PASSWORD, $firstLoginUrl); }
            catch (\Throwable $e) { error_log('[etudiant_create_from_candidature] welcome: ' . $e->getMessage()); }
        }
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
        // Échéance : 14 jours après l'émission (frais de dossier non remboursables).
        $echeance = date('Y-m-d', strtotime($emis . ' +14 days'));
        $pdo->prepare(
            "INSERT INTO factures
                (numero, etudiant_id, candidature_id, type, libelle, annee_academique, etape_cursus, description,
                 montant_ht_cents, tva_taux, montant_ttc_cents, devise,
                 date_emission, date_echeance,
                 statut_paiement, paye_at, paye_par_admin, moyen_paiement,
                 visible_etudiant, cree_par_admin)
             VALUES (?, ?, ?, 'frais_dossier', ?, ?, ?, ?,
                     40000, 0.00, 40000, 'EUR',
                     ?, ?,
                     ?, ?, ?, ?,
                     1, ?)"
        )->execute([
            $numero, $etudiantId, $candId,
            'Frais de dossier IPEC',
            $candidature['annee_academique'] ?? null,
            etudiant_step_from_programme_annee($candidature['programme'] ?? null, $candidature['annee'] ?? null),
            'Traitement de la candidature ' . ($candidature['reference'] ?? ''),
            $emis, $echeance,
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
                 titre, annee_academique, etape_cursus, description, data_json, statut, visible_etudiant,
                 date_emission, cree_par_admin)
             VALUES (?, ?, ?, 'autre', 'recap_candidature',
                     ?, ?, ?, ?, ?, 'publie', 1,
                     ?, ?)"
        )->execute([
            $ref, $etudiantId, $candId,
            'Récapitulatif de candidature',
            $candidature['annee_academique'] ?? null,
            etudiant_step_from_programme_annee($candidature['programme'] ?? null, $candidature['annee'] ?? null),
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
function etudiant_create_factures_scolarite(PDO $pdo, array $candidature, string $adminUser, array $opts = []): array {
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

    // --- Cible : étape + année académique ----------------------------
    // Par défaut : on lit la candidature (flux d'admission initiale).
    // En cas de progression, $opts permet d'écraser ces valeurs sans avoir à
    // dupliquer la candidature (one-candidature-per-student).
    $stepKey = (string)($opts['step_key'] ?? '');
    if ($stepKey === '') {
        $stepKey = (string)(etudiant_step_from_programme_annee($candidature['programme'] ?? null, $candidature['annee'] ?? null) ?? '');
    }
    if ($stepKey === '') {
        return ['created' => false, 'count' => 0, 'reason' => 'programme inconnu (ni PAA ni PEA)'];
    }
    [$progBase, $yearNum] = explode('-', $stepKey, 2);
    $progLabel = $progBase . $yearNum;
    $isPEA = ($progBase === 'PEA');

    $anneeAca   = (string)($opts['annee_academique'] ?? $candidature['annee_academique'] ?? '') ?: null;
    $rentreeStr = (string)($opts['rentree']          ?? $candidature['rentree']          ?? '');

    // --- Idempotence : initiale = par candidature, progression = par (étudiant, année académique).
    $scope = $opts['idempotency_scope'] ?? 'candidature';
    if ($scope === 'etudiant_year') {
        if (!$anneeAca) {
            return ['created' => false, 'count' => 0, 'reason' => 'annee_academique requise pour la progression'];
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM factures
                                WHERE etudiant_id = ? AND type = 'scolarite' AND annee_academique = ?");
        $stmt->execute([$etuId, $anneeAca]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM factures
                                WHERE candidature_id = ? AND type = 'scolarite'");
        $stmt->execute([$candId]);
    }
    if ((int)$stmt->fetchColumn() > 0) {
        return ['created' => false, 'count' => 0, 'reason' => 'déjà générées'];
    }

    // Montants en centimes (TTC, exonérés TVA enseignement)
    $t1Cents = 300000; // 3 000 €
    $soldeCents = $isPEA ? 415000 : 295000;     // 4 150 € (PEA) / 2 950 € (PAA)
    $trancheSolde = (int)round($soldeCents / 2); // 2 075 € (PEA) / 1 475 € (PAA)

    // --- Échéances ---
    $today = new DateTimeImmutable('today');
    $emission = $today->format('Y-m-d');

    // T1 : à la confirmation d'inscription → 30 jours
    $t1Echeance = $today->modify('+30 days')->format('Y-m-d');

    // Le libellé "rentree" est maintenant symbolique ("Rentrée principale" / "Rentrée décalée").
    // La date réelle est résolue via ipec_rentree_date_for() (codée en dur côté serveur).
    $rentreeDateStr = ipec_rentree_date_for($rentreeStr); // jj/mm/aaaa
    $isFebruary = ipec_rentree_is_decalee($rentreeStr);
    $rentreeDate = DateTimeImmutable::createFromFormat('!d/m/Y', $rentreeDateStr) ?: null;

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

    $rentreeLabel = ipec_rentree_label_normalized($rentreeStr);
    $anneeSuffix  = $anneeAca ? " ({$anneeAca})" : '';

    $tranches = [
        [
            'libelle'     => "Frais de scolarité {$progLabel} — 1ʳᵉ tranche{$anneeSuffix}",
            'description' => "Première tranche due à la confirmation d'inscription ({$rentreeLabel}).",
            'montant'     => $t1Cents,
            'echeance'    => $t1Echeance,
        ],
        [
            'libelle'     => "Frais de scolarité {$progLabel} — 2ᵉ tranche{$anneeSuffix}",
            'description' => "Deuxième tranche exigible avant le début du programme ({$rentreeLabel}).",
            'montant'     => $trancheSolde,
            'echeance'    => $t2Echeance,
        ],
        [
            'libelle'     => "Frais de scolarité {$progLabel} — Solde{$anneeSuffix}",
            'description' => $isFebruary
                ? "Solde des droits de scolarité — exigible 6 mois après le début du programme."
                : "Solde des droits de scolarité — exigible avant le 31 janvier de l'année académique.",
            'montant'     => $trancheSolde,
            'echeance'    => $t3Echeance,
        ],
    ];

    $pdo->beginTransaction();
    $factureT1Numero = '';
    try {
        $insert = $pdo->prepare(
            "INSERT INTO factures
                (numero, etudiant_id, candidature_id, type, libelle, annee_academique, etape_cursus, description,
                 montant_ht_cents, tva_taux, montant_ttc_cents, devise,
                 date_emission, date_echeance,
                 statut_paiement, visible_etudiant, cree_par_admin)
             VALUES (?, ?, ?, 'scolarite', ?, ?, ?, ?,
                     ?, 0.00, ?, 'EUR',
                     ?, ?,
                     'en_attente', 1, ?)"
        );
        foreach ($tranches as $idx => $t) {
            $numero = etudiant_generate_ref($pdo, 'FACT');
            if ($idx === 0) $factureT1Numero = $numero;
            $insert->execute([
                $numero, $etuId, $candId,
                $t['libelle'], $anneeAca, $stepKey, $t['description'],
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

    // Document "Lettre de préadmission" — uniquement à l'admission initiale
    // (pas aux passages d'année : l'étudiant n'est plus préadmis).
    if (empty($opts['skip_preadmission_doc'])) {
        try {
            etudiant_create_document_preadmission($pdo, $candidature, $adminUser, [
                'facture_t1_numero'   => $factureT1Numero,
                'facture_t1_echeance' => $tranches[0]['echeance'],
            ]);
        } catch (\Throwable $e) {
            error_log('[etudiant_create_factures_scolarite] preadmission doc failed: ' . $e->getMessage());
        }
    }

    // Promotion catégorie : candidat → preadmis (uniquement à l'admission initiale).
    // Lors d'un passage d'année l'étudiant reste 'etudiant' (règle métier).
    if (empty($opts['skip_set_categorie'])) {
        try { etudiant_set_categorie($pdo, $etuId, 'preadmis'); }
        catch (\Throwable $e) { error_log('[etudiant_create_factures_scolarite] set_categorie failed: ' . $e->getMessage()); }
    }

    // Notification étudiant : nouveaux documents/factures disponibles
    if (function_exists('etu_notify_send_documents')) {
        try {
            $items = [];
            if (empty($opts['skip_preadmission_doc'])) $items[] = ['titre' => 'Lettre de préadmission IPEC', 'kind' => 'document'];
            foreach ($tranches as $t) $items[] = ['titre' => $t['libelle'], 'kind' => 'facture'];
            etu_notify_send_documents($pdo, $etuId, $items);
        } catch (\Throwable $e) { error_log('[etudiant_create_factures_scolarite] notify: ' . $e->getMessage()); }
    }

    return ['created' => true, 'count' => count($tranches)];
}

/**
 * Crée (idempotent) le document "Lettre de préadmission" dans l'espace
 * étudiant. Le PDF est régénéré à la volée par buildPreadmissionPdf()
 * depuis data_json (cf. public/etudiant/telecharger.php).
 *
 * Ne fait rien si :
 *   - aucun etudiant_id rattaché
 *   - un document de template 'preadmission' existe déjà pour cette candidature
 */
function etudiant_create_document_preadmission(PDO $pdo, array $candidature, string $adminUser, array $extra = []): void {
    if (empty($candidature['etudiant_id'])) return;
    $etuId  = (int)$candidature['etudiant_id'];
    $candId = (int)$candidature['id'];

    $stmt = $pdo->prepare("SELECT id FROM documents
                            WHERE candidature_id = ? AND template = 'preadmission' LIMIT 1");
    $stmt->execute([$candId]);
    if ($stmt->fetchColumn()) return;

    $ref = etudiant_generate_ref($pdo, 'DOC');
    $emis = date('Y-m-d');

    $data = [
        'reference_doc'         => $ref,
        'date_emission'         => $emis,
        'civilite'              => $candidature['civilite']        ?? null,
        'prenom'                => $candidature['prenom']          ?? null,
        'nom'                   => $candidature['nom']             ?? null,
        'email'                 => $candidature['email']           ?? null,
        'programme'             => $candidature['programme']       ?? null,
        'annee'                 => $candidature['annee']           ?? null,
        'specialisation'        => $candidature['specialisation']  ?? null,
        'rentree'               => $candidature['rentree']         ?? null,
        'candidature_reference' => $candidature['reference']       ?? null,
        'facture_t1_numero'     => (string)($extra['facture_t1_numero'] ?? ''),
        'facture_t1_echeance'   => (string)($extra['facture_t1_echeance'] ?? ''),
    ];

    $pdo->prepare(
        "INSERT INTO documents
            (reference, etudiant_id, candidature_id, type, template,
             titre, annee_academique, etape_cursus, description, data_json, statut, visible_etudiant,
             date_emission, cree_par_admin)
         VALUES (?, ?, ?, 'autre', 'preadmission',
                 ?, ?, ?, ?, ?, 'publie', 1,
                 ?, ?)"
    )->execute([
        $ref, $etuId, $candId,
        'Lettre de préadmission IPEC',
        $candidature['annee_academique'] ?? null,
        etudiant_step_from_programme_annee($candidature['programme'] ?? null, $candidature['annee'] ?? null),
        "Avis favorable — sous réserve du paiement de la 1ʳᵉ tranche.",
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        $emis, $adminUser,
    ]);
}

/* =====================================================================
 * Documents post-paiement T1 — Attestation d'inscription définitive
 * + Formulaire standard d'inscription. Idempotent.
 * ===================================================================== */
function etudiant_create_documents_inscription_definitive(PDO $pdo, int $etudiantId, int $candidatureId, string $adminUser): array {
    if ($etudiantId <= 0 || $candidatureId <= 0) {
        return ['created' => false, 'count' => 0, 'reason' => 'identifiants invalides'];
    }
    $cs = $pdo->prepare("SELECT c.*, e.numero_etudiant
                         FROM candidatures c
                         INNER JOIN etudiants e ON e.id = c.etudiant_id
                         WHERE c.id = ? AND c.etudiant_id = ? LIMIT 1");
    $cs->execute([$candidatureId, $etudiantId]);
    $cand = $cs->fetch();
    if (!$cand) return ['created' => false, 'count' => 0, 'reason' => 'candidature introuvable'];

    $fs = $pdo->prepare("SELECT numero, paye_at FROM factures
                         WHERE candidature_id = ? AND type = 'scolarite' AND statut_paiement = 'payee'
                         ORDER BY date_emission ASC, id ASC LIMIT 1");
    $fs->execute([$candidatureId]);
    $factT1 = $fs->fetch();
    if (!$factT1) return ['created' => false, 'count' => 0, 'reason' => 'aucune facture scolarité payée'];

    $baseData = [
        'date_emission'         => date('Y-m-d'),
        'civilite'              => $cand['civilite']        ?? null,
        'prenom'                => $cand['prenom']          ?? null,
        'nom'                   => $cand['nom']             ?? null,
        'email'                 => $cand['email']           ?? null,
        'date_naissance'        => $cand['date_naissance']  ?? null,
        'nationalite'           => $cand['nationalite']     ?? null,
        'telephone'             => $cand['telephone']       ?? null,
        'rue'                   => $cand['rue']             ?? null,
        'numero_rue'            => $cand['numero']          ?? null,
        'code_postal'           => $cand['code_postal']     ?? null,
        'ville'                 => $cand['ville']           ?? null,
        'pays_residence'        => $cand['pays_residence']  ?? null,
        'programme'             => $cand['programme']       ?? null,
        'annee'                 => $cand['annee']           ?? null,
        'specialisation'        => $cand['specialisation']  ?? null,
        'rentree'               => $cand['rentree']         ?? null,
        'annee_academique'      => $cand['annee_academique']?? null,
        'numero_etudiant'       => $cand['numero_etudiant'] ?? null,
        'candidature_reference' => $cand['reference']       ?? null,
        'facture_t1_numero'     => $factT1['numero']        ?? null,
        'facture_t1_paye_at'    => $factT1['paye_at']       ?? null,
    ];

    // Templates à émettre. Snapshot étape + année académique courants pour
    // que le doc soit relié à la bonne année (cf. progression in-place).
    $templates = [
        'attestation_inscription_definitive' => [
            'titre' => "Attestation d'inscription définitive",
            'desc'  => "Inscription confirmée après paiement de la 1ʳᵉ tranche de scolarité.",
        ],
        'formulaire_standard_inscription' => [
            'titre' => "Formulaire standard d'inscription",
            'desc'  => "Document officiel d'inscription à l'IPEC.",
        ],
    ];
    $emis = date('Y-m-d');
    // Snapshot état courant (priorité etudiants.etape_courante > candidature)
    $eRow = $pdo->prepare("SELECT etape_courante, annee_academique_courante, rentree_courante FROM etudiants WHERE id = ?");
    $eRow->execute([$etudiantId]);
    $cur = etudiant_current_cursus($eRow->fetch() ?: null, $cand);

    $created = 0;
    $createdTitles = [];

    $insert = $pdo->prepare(
        "INSERT INTO documents
            (reference, etudiant_id, candidature_id, type, template,
             titre, annee_academique, etape_cursus, description, data_json, statut, visible_etudiant,
             date_emission, cree_par_admin)
         VALUES (?, ?, ?, 'autre', ?,
                 ?, ?, ?, ?, ?, 'publie', 1,
                 ?, ?)"
    );
    foreach ($templates as $template => $meta) {
        $st = $pdo->prepare("SELECT id FROM documents
                              WHERE etudiant_id = ? AND template = ? AND COALESCE(annee_academique,'') = COALESCE(?, '')
                              LIMIT 1");
        $st->execute([$etudiantId, $template, $cur['annee_academique']]);
        if ($st->fetchColumn()) continue;
        $ref = etudiant_generate_ref($pdo, 'DOC');
        $data = array_merge($baseData, [
            'reference_doc'    => $ref,
            'annee_academique' => $cur['annee_academique'],
            'rentree'          => $cur['rentree'],
        ]);
        $insert->execute([
            $ref, $etudiantId, $candidatureId, $template,
            $meta['titre'], $cur['annee_academique'], $cur['etape'], $meta['desc'],
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            $emis, $adminUser,
        ]);
        $created++;
        $createdTitles[] = $meta['titre'];
    }
    if ($created > 0 && function_exists('etu_notify_send_documents')) {
        try {
            $items = array_map(fn($t) => ['titre' => $t, 'kind' => 'document'], $createdTitles);
            etu_notify_send_documents($pdo, $etudiantId, $items);
        } catch (\Throwable $e) { error_log('[inscription_definitive] notify: ' . $e->getMessage()); }
    }
    return ['created' => $created > 0, 'count' => $created];
}

