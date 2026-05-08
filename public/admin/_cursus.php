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
 * Action : passer à l'année suivante (PAA1→PAA2 etc.) ou redoubler.
 *
 * MODÈLE (depuis migration v7) : un étudiant n'a qu'UNE SEULE candidature
 * (le dossier d'admission initial). La progression NE crée plus de
 * nouvelle ligne dans `candidatures` — on met à jour l'état courant
 * sur `etudiants` (etape_courante / annee_academique_courante / rentree_courante)
 * puis on génère les 3 factures de scolarité de la NOUVELLE année académique
 * rattachées à la candidature initiale, mais portant elles-mêmes leur propre
 * `annee_academique` + `etape_cursus`.
 *
 * L'étudiant reste « etudiant » (jamais de retour en preadmis). Une nouvelle
 * attestation d'inscription définitive sera générée seulement après paiement
 * de la 1ʳᵉ tranche de la nouvelle année (cf. facture-action.php).
 *
 * @param string $mode 'passage' ou 'redoublement'
 * @param string|null $anneeAcademique label "AAAA-AAAA" (obligatoire en pratique)
 * @param string|null $rentree libellé "Rentrée principale" / "Rentrée décalée"
 * @return array{candidature:array, factures_count:int, documents_count:int}
 */
function cursus_evoluer(
    PDO $pdo, int $candidatureId, string $mode,
    ?string $anneeAcademique, ?string $rentree, string $adminUser
): array {
    if (!in_array($mode, ['passage','redoublement'], true)) {
        throw new InvalidArgumentException('mode invalide.');
    }
    $stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
    $stmt->execute([$candidatureId]);
    $cand = $stmt->fetch();
    if (!$cand) throw new RuntimeException('Candidature introuvable.');
    if (empty($cand['etudiant_id'])) {
        throw new RuntimeException('Aucun compte étudiant rattaché à cette candidature.');
    }
    $etuId = (int)$cand['etudiant_id'];

    // Lit l'étudiant pour récupérer l'étape courante (sinon on retombe sur la candidature).
    $eStmt = $pdo->prepare("SELECT id, etape_courante, annee_academique_courante, rentree_courante, categorie
                            FROM etudiants WHERE id = ?");
    $eStmt->execute([$etuId]);
    $etu = $eStmt->fetch() ?: [];
    $cur = etudiant_current_cursus($etu, $cand);
    $curStep = $cur['etape'];
    if (!$curStep || !in_array($curStep, CURSUS_STEPS, true)) {
        throw new RuntimeException("Étape de cursus indéterminée pour cet étudiant.");
    }

    if ($mode === 'passage') {
        $nextStep = cursus_next_step($curStep);
        if (!$nextStep) {
            throw new RuntimeException("Cet étudiant est déjà en dernière année (PEA2). Utilisez « Diplômer » à la place.");
        }
    } else {
        $nextStep = $curStep;
    }

    if (!$anneeAcademique) {
        throw new RuntimeException("Année académique cible requise.");
    }
    $rentreeNorm = $rentree ? ipec_rentree_label_normalized($rentree) : ipec_rentree_label_normalized($cur['rentree']);

    // Garde-fou : pas deux jeux de factures pour la même (étudiant, année académique).
    $check = $pdo->prepare("SELECT COUNT(*) FROM factures
                             WHERE etudiant_id = ? AND type='scolarite' AND annee_academique = ?");
    $check->execute([$etuId, $anneeAcademique]);
    if ((int)$check->fetchColumn() > 0) {
        throw new RuntimeException("Des factures de scolarité existent déjà pour cet étudiant en {$anneeAcademique}.");
    }

    // Met à jour l'état courant sur l'étudiant. Catégorie : reste 'etudiant'
    // (s'il l'était déjà). Si pour une raison X il n'a jamais payé sa T1
    // initiale, on ne le rétrograde pas non plus — règle métier explicite.
    $pdo->prepare("UPDATE etudiants
                   SET etape_courante = ?,
                       annee_academique_courante = ?,
                       rentree_courante = ?,
                       motif_inactif = NULL,
                       date_fin_cursus = NULL
                   WHERE id = ?")
        ->execute([$nextStep, $anneeAcademique, $rentreeNorm, $etuId]);

    // Génère les 3 factures de scolarité de la NOUVELLE année académique,
    // rattachées à la candidature initiale (un étudiant = une candidature).
    // On force statut='validee' + facture_payee=1 dans le tableau passé pour
    // satisfaire les garde-fous internes (la candidature initiale est déjà validée).
    $candForFact = $cand;
    $candForFact['statut']         = 'validee';
    $candForFact['facture_payee']  = 1;
    $res = etudiant_create_factures_scolarite($pdo, $candForFact, $adminUser, [
        'step_key'              => $nextStep,
        'annee_academique'      => $anneeAcademique,
        'rentree'               => $rentreeNorm,
        'idempotency_scope'     => 'etudiant_year',
        'skip_preadmission_doc' => true,   // pas de "lettre de préadmission" sur progression
        'skip_set_categorie'    => true,   // l'étudiant reste 'etudiant'
    ]);

    // Documents pédagogiques liés au passage (idempotent).
    $docsCreated = 0;
    $extraNotifyTitles = [];
    if ($mode === 'passage') {
        if (cursus_create_attestation_reussite_annee($pdo, $cand, $curStep, $nextStep, $cur['annee_academique'] ?? null, $adminUser)) {
            $docsCreated++;
            $extraNotifyTitles[] = "Attestation de réussite — " . CURSUS_LABELS[$curStep]['programme'] . ' ' . CURSUS_LABELS[$curStep]['annee_label'];
        }
        if ($curStep === 'PAA-3') {
            if (cursus_create_diplome_bachelier($pdo, $cand, $cur['annee_academique'] ?? null, $adminUser)) {
                $docsCreated++;
                $extraNotifyTitles[] = "Diplôme de Bachelier (PAA — BAC+3)";
            }
        }
    }
    if (!empty($extraNotifyTitles) && function_exists('etu_notify_send_documents')) {
        try {
            $items = array_map(fn($t) => ['titre' => $t, 'kind' => 'document'], $extraNotifyTitles);
            etu_notify_send_documents($pdo, $etuId, $items);
        } catch (\Throwable $e) { error_log('[cursus_evoluer] notify: ' . $e->getMessage()); }
    }

    // Recharge la candidature pour la cohérence du retour.
    $stmt2 = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
    $stmt2->execute([$candidatureId]);
    $candReloaded = $stmt2->fetch() ?: $cand;

    return [
        'candidature'    => $candReloaded,
        'factures_count' => (int)($res['count'] ?? 0),
        'documents_count'=> $docsCreated,
        'next_step'      => $nextStep,
        'next_label'     => CURSUS_LABELS[$nextStep]['programme'] . ' ' . CURSUS_LABELS[$nextStep]['annee_label'],
    ];
}

/**
 * Crée (idempotent) l'attestation de réussite de l'année que l'étudiant
 * vient de valider. Idempotence : (etudiant_id, template, prevStep, anneeValidee).
 *
 * @param array  $cand          La candidature initiale (porte l'identité).
 * @param string $prevStep      Étape validée (ex 'PAA-1').
 * @param string $nextStep      Étape autorisée pour l'année suivante (ex 'PAA-2').
 * @param string|null $anneeValidee  Année académique qui vient d'être validée.
 */
function cursus_create_attestation_reussite_annee(
    PDO $pdo, array $cand, string $prevStep, string $nextStep, ?string $anneeValidee, string $adminUser
): bool {
    $cid   = (int)$cand['id'];
    $etuId = (int)$cand['etudiant_id'];
    // Idempotence par (étudiant, étape validée, année académique)
    $exists = $pdo->prepare("SELECT id FROM documents
                             WHERE etudiant_id = ? AND template = 'attestation_reussite_annee'
                               AND COALESCE(etape_cursus,'') = ? AND COALESCE(annee_academique,'') = COALESCE(?, '')
                             LIMIT 1");
    $exists->execute([$etuId, $prevStep, $anneeValidee]);
    if ($exists->fetchColumn()) return false;

    $prevMeta = CURSUS_LABELS[$prevStep] ?? null;
    $nextMeta = CURSUS_LABELS[$nextStep] ?? null;
    $prevLabelProg = $prevMeta ? $prevMeta['programme'] : '';
    $prevLabelAnn  = $prevMeta ? $prevMeta['annee_label'] : '';
    $nextLabel     = $nextMeta ? ($nextMeta['programme'] . ' ' . $nextMeta['annee_label']) : '';

    $ref = etudiant_generate_ref($pdo, 'DOC');
    $emis = date('Y-m-d');
    $data = [
        'reference_doc'         => $ref,
        'date_emission'         => $emis,
        'civilite'              => $cand['civilite']        ?? null,
        'prenom'                => $cand['prenom']          ?? null,
        'nom'                   => $cand['nom']             ?? null,
        'date_naissance'        => $cand['date_naissance']  ?? null,
        'programme'             => $prevLabelProg,
        'annee'                 => $prevLabelAnn,
        'specialisation'        => $cand['specialisation']  ?? null,
        'annee_academique'      => $anneeValidee,
        'annee_suivante'        => $nextLabel,
        'candidature_reference' => $cand['reference']       ?? null,
    ];
    $pdo->prepare(
        "INSERT INTO documents
            (reference, etudiant_id, candidature_id, type, template,
             titre, annee_academique, etape_cursus, description, data_json, statut, visible_etudiant,
             date_emission, cree_par_admin)
         VALUES (?, ?, ?, 'attestation', 'attestation_reussite_annee',
                 ?, ?, ?, ?, ?, 'publie', 1,
                 ?, ?)"
    )->execute([
        $ref, $etuId, $cid,
        "Attestation de réussite — {$prevLabelProg} {$prevLabelAnn}",
        $anneeValidee, $prevStep,
        "Année académique " . ($anneeValidee ?? '') . " validée. Document officiel signé par la direction de l'IPEC.",
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        $emis, $adminUser,
    ]);
    return true;
}

/**
 * Crée (idempotent) le diplôme de Bachelier en fin de PAA-3.
 */
function cursus_create_diplome_bachelier(PDO $pdo, array $cand, ?string $anneeValidee, string $adminUser): bool {
    $cid   = (int)$cand['id'];
    $etuId = (int)$cand['etudiant_id'];
    $exists = $pdo->prepare("SELECT id FROM documents
                             WHERE etudiant_id = ? AND template = 'diplome_bachelier' LIMIT 1");
    $exists->execute([$etuId]);
    if ($exists->fetchColumn()) return false;

    $ref = etudiant_generate_ref($pdo, 'DOC');
    $emis = date('Y-m-d');
    $data = [
        'reference_doc'         => $ref,
        'date_emission'         => $emis,
        'civilite'              => $cand['civilite']         ?? null,
        'prenom'                => $cand['prenom']           ?? null,
        'nom'                   => $cand['nom']              ?? null,
        'date_naissance'        => $cand['date_naissance']   ?? null,
        'specialisation'        => $cand['specialisation']   ?? null,
        'annee_academique'      => $anneeValidee,
        'candidature_reference' => $cand['reference']        ?? null,
    ];
    $pdo->prepare(
        "INSERT INTO documents
            (reference, etudiant_id, candidature_id, type, template,
             titre, annee_academique, etape_cursus, description, data_json, statut, visible_etudiant,
             date_emission, cree_par_admin)
         VALUES (?, ?, ?, 'diplome', 'diplome_bachelier',
                 ?, ?, ?, ?, ?, 'publie', 1,
                 ?, ?)"
    )->execute([
        $ref, $etuId, $cid,
        "Diplôme de Bachelier (PAA — BAC+3)",
        $anneeValidee, 'PAA-3',
        "Diplôme officiel délivré au terme du cycle PAA. Signé par la direction de l'IPEC.",
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        $emis, $adminUser,
    ]);
    return true;
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
