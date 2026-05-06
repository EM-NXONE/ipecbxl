<?php
/**
 * IPEC Admin — Helpers évolution du cursus étudiant.
 *
 * Suite logique IPEC (codée en DUR — modifier ici uniquement) :
 *
 *   PAA1 → PAA2 → PAA3 → PEA1 → PEA2 → diplôme
 *
 * Actions :
 *   - cursus_passer_annee_suivante : crée une nouvelle candidature liée à la
 *     précédente (parent_candidature_id), valide, type = 'passage', sur la
 *     prochaine étape du cursus, déclenche la génération des 3 factures de
 *     scolarité de la nouvelle année académique. La catégorie redescend à
 *     'preadmis' tant que la T1 de la nouvelle année n'est pas payée.
 *   - cursus_redoubler : idem mais même étape (PAA2 → PAA2), type = 'redoublement'.
 *   - cursus_diplomer  : uniquement depuis PEA2 → categorie='diplome',
 *     date_fin_cursus = aujourd'hui, génère un document "attestation_reussite".
 *   - cursus_set_inactif / cursus_set_actif : abandon/exclusion réversible.
 */

declare(strict_types=1);

/**
 * Étapes du cursus IPEC dans l'ordre (clé = "PROG-année").
 * MODIFIER ICI pour ajouter/retirer une étape.
 */
const CURSUS_STEPS = ['PAA-1', 'PAA-2', 'PAA-3', 'PEA-1', 'PEA-2'];

/** Libellés humains pour chaque étape (utilisés dans l'UI/PDF). */
const CURSUS_LABELS = [
    'PAA-1' => ['programme' => 'PAA', 'annee_num' => 1, 'annee_label' => "1ʳᵉ année (BAC+1)"],
    'PAA-2' => ['programme' => 'PAA', 'annee_num' => 2, 'annee_label' => "2ᵉ année (BAC+2)"],
    'PAA-3' => ['programme' => 'PAA', 'annee_num' => 3, 'annee_label' => "3ᵉ année (BAC+3)"],
    'PEA-1' => ['programme' => 'PEA', 'annee_num' => 1, 'annee_label' => "1ʳᵉ année (BAC+4)"],
    'PEA-2' => ['programme' => 'PEA', 'annee_num' => 2, 'annee_label' => "2ᵉ année (BAC+5)"],
];

/**
 * Détermine la clé d'étape ("PAA-2") à partir d'une candidature.
 * Renvoie null si non identifiable.
 */
function cursus_step_key(array $candidature): ?string {
    $prog = strtoupper(trim((string)($candidature['programme'] ?? '')));
    if ($prog !== 'PAA' && $prog !== 'PEA') return null;
    $anneeStr = (string)($candidature['annee'] ?? '');
    if (!preg_match('/(\d)/u', $anneeStr, $m)) return null;
    $key = $prog . '-' . $m[1];
    return in_array($key, CURSUS_STEPS, true) ? $key : null;
}

/** Étape suivante dans le cursus, ou null si déjà à la dernière (PEA-2). */
function cursus_next_step(string $key): ?string {
    $i = array_search($key, CURSUS_STEPS, true);
    if ($i === false || $i + 1 >= count(CURSUS_STEPS)) return null;
    return CURSUS_STEPS[$i + 1];
}

/**
 * Crée la nouvelle candidature pour l'année académique en cours, liée à la
 * précédente. Statut = 'validee' (l'étudiant n'a pas à recandidater),
 * facture_payee = 1 (les frais 400 € ne sont JAMAIS refacturés au passage).
 */
function cursus_create_next_candidature(
    PDO $pdo, array $previous, string $stepKey, string $type, string $adminUser
): array {
    if (!in_array($type, ['passage', 'redoublement'], true)) {
        throw new InvalidArgumentException('type_inscription invalide.');
    }
    $meta = CURSUS_LABELS[$stepKey] ?? null;
    if (!$meta) throw new RuntimeException("Étape cursus inconnue : $stepKey");

    // Référence officielle (IPEC-CAND-AAAA-XXXXXX), unique en base.
    // Mémoire : JAMAIS de fallback timestamp inventé.
    $reference = generateDocumentReference($pdo, 'CAND');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO candidatures
                (reference, statut,
                 civilite, prenom, nom, date_naissance, nationalite,
                 email, telephone,
                 rue, numero, code_postal, ville, pays_residence,
                 programme, annee, specialisation, rentree, annee_academique,
                 message, etudiant_id, parent_candidature_id, type_inscription,
                 facture_payee, facture_payee_at, facture_payee_par,
                 created_at)
             VALUES
                (?, 'validee',
                 ?, ?, ?, ?, ?,
                 ?, ?,
                 ?, ?, ?, ?, ?,
                 ?, ?, ?, ?, ?,
                 ?, ?, ?, ?,
                 1, NOW(), ?,
                 NOW())"
        );
        $stmt->execute([
            $reference,
            $previous['civilite'], $previous['prenom'], $previous['nom'],
            $previous['date_naissance'], $previous['nationalite'],
            $previous['email'], $previous['telephone'],
            $previous['rue'], $previous['numero'], $previous['code_postal'],
            $previous['ville'], $previous['pays_residence'],
            $meta['programme'], $meta['annee_label'], $previous['specialisation'],
            $previous['rentree'],            // calé sur la même rentrée
            $previous['annee_academique'],   // sera remplacé par UI/API si fournie
            null,
            (int)$previous['etudiant_id'],
            (int)$previous['id'],
            $type,
            $adminUser,
        ]);
        $newId = (int)$pdo->lastInsertId();
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Recharge la nouvelle ligne (avec les champs par défaut MySQL : created_at, etc.)
    $stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
    $stmt->execute([$newId]);
    return $stmt->fetch();
}

/**
 * Action : passer à l'année suivante (PAA1→PAA2 etc.) ou redoubler.
 * Génère automatiquement les 3 factures de scolarité de la nouvelle année,
 * crée la lettre de préadmission, et fait redescendre l'étudiant en
 * 'preadmis' tant que la T1 n'est pas payée.
 *
 * @param string $mode 'passage' ou 'redoublement'
 * @param string|null $anneeAcademique label "AAAA-AAAA" (optionnel — sinon repris du parent)
 * @param string|null $rentree libellé "Septembre — JJ/MM/AAAA" (optionnel)
 * @return array{candidature:array, factures_count:int}
 */
function cursus_evoluer(
    PDO $pdo, int $previousCandidatureId, string $mode,
    ?string $anneeAcademique, ?string $rentree, string $adminUser
): array {
    $stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
    $stmt->execute([$previousCandidatureId]);
    $prev = $stmt->fetch();
    if (!$prev) throw new RuntimeException('Candidature précédente introuvable.');
    if (empty($prev['etudiant_id'])) {
        throw new RuntimeException('Aucun compte étudiant rattaché à cette candidature.');
    }

    $curStep = cursus_step_key($prev);
    if (!$curStep) {
        throw new RuntimeException("Cursus indéterminé pour cette candidature (programme/année).");
    }

    if ($mode === 'passage') {
        $nextStep = cursus_next_step($curStep);
        if (!$nextStep) {
            throw new RuntimeException("Cet étudiant est déjà en dernière année (PEA2). Utilisez « Diplômer » à la place.");
        }
    } else { // redoublement
        $nextStep = $curStep;
    }

    // Garde-fou : pas deux candidatures pour la même étape sur la même année académique.
    if ($anneeAcademique) {
        $check = $pdo->prepare(
            "SELECT id FROM candidatures
              WHERE etudiant_id = ? AND annee_academique = ?
                AND programme = ? AND annee LIKE ?
              LIMIT 1"
        );
        $meta = CURSUS_LABELS[$nextStep];
        $check->execute([
            (int)$prev['etudiant_id'],
            $anneeAcademique,
            $meta['programme'],
            $meta['annee_label'],
        ]);
        if ($check->fetchColumn()) {
            throw new RuntimeException("Une candidature existe déjà pour {$meta['programme']} {$meta['annee_label']} en {$anneeAcademique}.");
        }
    }

    // Pré-remplit annee_academique / rentree si fournis (UI)
    if ($anneeAcademique) $prev['annee_academique'] = $anneeAcademique;
    if ($rentree)         $prev['rentree']          = $rentree;

    $newCand = cursus_create_next_candidature($pdo, $prev, $nextStep, $mode, $adminUser);

    // Génère les 3 factures de scolarité pour la nouvelle candidature.
    // etudiant_create_factures_scolarite() est idempotent + lettre de préadmission.
    // NB : la catégorie de l'étudiant n'est JAMAIS rétrogradée. Un étudiant qui
    // passe en année supérieure (ou redouble) reste 'etudiant'. La nouvelle
    // attestation d'inscription définitive sera générée à part, lorsque la
    // 1ʳᵉ tranche de la nouvelle année sera marquée payée
    // (cf. etudiant_create_documents_inscription_definitive, scoped par candidature_id).
    $res = etudiant_create_factures_scolarite($pdo, $newCand, $adminUser);

    return [
        'candidature'    => $newCand,
        'factures_count' => (int)($res['count'] ?? 0),
    ];
}

/**
 * Action : diplômer un étudiant (uniquement depuis la dernière étape).
 * Met categorie='diplome', date_fin_cursus, et crée le document
 * "attestation_reussite" dans son espace.
 */
function cursus_diplomer(PDO $pdo, int $candidatureId, string $adminUser): array {
    $stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
    $stmt->execute([$candidatureId]);
    $cand = $stmt->fetch();
    if (!$cand) throw new RuntimeException('Candidature introuvable.');
    if (empty($cand['etudiant_id'])) {
        throw new RuntimeException('Aucun compte étudiant rattaché.');
    }
    $step = cursus_step_key($cand);
    if ($step !== 'PEA-2') {
        throw new RuntimeException("La diplomation n'est possible qu'en dernière année (PEA2).");
    }

    $etuId = (int)$cand['etudiant_id'];

    // Crée (idempotent) le document attestation de réussite.
    $exists = $pdo->prepare("SELECT id FROM documents
                             WHERE candidature_id = ? AND template = 'attestation_reussite' LIMIT 1");
    $exists->execute([$candidatureId]);
    $created = false;
    if (!$exists->fetchColumn()) {
        $ref = etudiant_generate_ref($pdo, 'DOC');
        $emis = date('Y-m-d');
        $data = [
            'reference_doc'         => $ref,
            'date_emission'         => $emis,
            'civilite'              => $cand['civilite']        ?? null,
            'prenom'                => $cand['prenom']          ?? null,
            'nom'                   => $cand['nom']             ?? null,
            'date_naissance'        => $cand['date_naissance']  ?? null,
            'programme'             => $cand['programme']       ?? null,
            'annee'                 => $cand['annee']           ?? null,
            'specialisation'        => $cand['specialisation']  ?? null,
            'annee_academique'      => $cand['annee_academique']?? null,
            'candidature_reference' => $cand['reference']       ?? null,
        ];
        $pdo->prepare(
            "INSERT INTO documents
                (reference, etudiant_id, candidature_id, type, template,
                 titre, description, data_json, statut, visible_etudiant,
                 date_emission, cree_par_admin)
             VALUES (?, ?, ?, 'attestation', 'attestation_reussite',
                     ?, ?, ?, 'publie', 1,
                     ?, ?)"
        )->execute([
            $ref, $etuId, $candidatureId,
            "Attestation de réussite — {$cand['programme']} {$cand['annee']}",
            "Cycle complet validé. Document officiel signé par la direction de l'IPEC.",
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            $emis, $adminUser,
        ]);
        $created = true;
    }

    $pdo->prepare("UPDATE etudiants
                   SET categorie='diplome', date_fin_cursus=CURDATE(), motif_inactif=NULL
                   WHERE id=?")
        ->execute([$etuId]);

    return ['attestation_creee' => $created];
}

/**
 * Action : marquer un étudiant inactif (abandon, exclusion). Réversible.
 * Aucune nouvelle facture/document ne sera généré tant qu'inactif.
 * Les factures existantes restent (statut, paiement, historique préservés).
 */
function cursus_set_inactif(PDO $pdo, int $etudiantId, string $motif): void {
    $motif = trim($motif);
    if ($motif === '') throw new InvalidArgumentException('Motif requis.');
    if (mb_strlen($motif) > 255) $motif = mb_substr($motif, 0, 255);
    $pdo->prepare("UPDATE etudiants
                   SET categorie='inactif', motif_inactif=?, date_fin_cursus=CURDATE()
                   WHERE id=?")
        ->execute([$motif, $etudiantId]);
    // Coupe les sessions actives.
    $pdo->prepare("DELETE FROM etudiant_sessions WHERE etudiant_id=?")->execute([$etudiantId]);
}

/**
 * Action inverse : réactive un étudiant inactif.
 * Le remet en 'etudiant' s'il a au moins une T1 scolarité payée, sinon 'preadmis'.
 */
function cursus_set_actif(PDO $pdo, int $etudiantId): string {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM factures
                           WHERE etudiant_id = ? AND type = 'scolarite' AND statut_paiement = 'payee'");
    $stmt->execute([$etudiantId]);
    $cat = ((int)$stmt->fetchColumn() > 0) ? 'etudiant' : 'preadmis';
    $pdo->prepare("UPDATE etudiants
                   SET categorie=?, motif_inactif=NULL, date_fin_cursus=NULL
                   WHERE id=?")
        ->execute([$cat, $etudiantId]);
    return $cat;
}

/**
 * Renvoie un descripteur pour l'UI : étape courante + actions possibles
 * pour la candidature LA PLUS RÉCENTE de l'étudiant.
 *
 * @return array{
 *   current_step: string|null,
 *   current_label: string|null,
 *   next_step: string|null,
 *   next_label: string|null,
 *   can_promote: bool,
 *   can_redouble: bool,
 *   can_diplomer: bool
 * }
 */
function cursus_describe_for(array $latestCandidature): array {
    $step = cursus_step_key($latestCandidature);
    $next = $step ? cursus_next_step($step) : null;
    return [
        'current_step'  => $step,
        'current_label' => $step ? (CURSUS_LABELS[$step]['programme'] . ' ' . CURSUS_LABELS[$step]['annee_num']) : null,
        'next_step'     => $next,
        'next_label'    => $next ? (CURSUS_LABELS[$next]['programme'] . ' ' . CURSUS_LABELS[$next]['annee_num']) : null,
        'can_promote'   => $step !== null && $next !== null,
        'can_redouble'  => $step !== null,
        'can_diplomer'  => $step === 'PEA-2',
    ];
}
