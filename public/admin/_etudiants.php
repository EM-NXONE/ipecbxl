<?php
/**
 * IPEC Admin — Helpers gestion des comptes étudiants
 *
 * Régles :
 *  - Création MANUELLE par l'admin depuis une fiche candidature.
 *  - Auth = PHP natif bcrypt (pas de Supabase).
 *  - Le compte est créé sans mot de passe (password_hash NULL) et un token
 *    d'activation est généré ; l'étudiant définira son mdp via le lien reçu.
 *  - Numéro étudiant format IPEC-ETU-AAAA-XXXX (4 hex majuscules).
 */

declare(strict_types=1);

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
 * Cherche un étudiant par e-mail (insensible à la casse côté MySQL via collation).
 */
function etudiant_find_by_email(PDO $pdo, string $email): ?array {
    $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE email = ? LIMIT 1");
    $stmt->execute([trim(strtolower($email))]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Crée un token d'activation (ou reset) à usage unique.
 * Renvoie le token EN CLAIR (à envoyer par e-mail) ; en BDD on stocke le sha256.
 */
function etudiant_create_token(PDO $pdo, int $etudiantId, string $type = 'activation', int $ttlSeconds = 7 * 24 * 3600): string {
    if (!in_array($type, ['activation', 'reset_password'], true)) {
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
 * @return array{etudiant_id:int, numero:string, token:string, deja_existant:bool}
 */
function etudiant_create_from_candidature(PDO $pdo, array $candidature, string $adminUser): array {
    $email = trim(strtolower((string)$candidature['email']));
    if ($email === '') {
        throw new RuntimeException("La candidature n'a pas d'e-mail.");
    }

    $existing = etudiant_find_by_email($pdo, $email);
    if ($existing) {
        // Rattache la candidature s'il manque le lien
        if (empty($candidature['etudiant_id']) || (int)$candidature['etudiant_id'] !== (int)$existing['id']) {
            $pdo->prepare("UPDATE candidatures SET etudiant_id = ? WHERE id = ?")
                ->execute([(int)$existing['id'], (int)$candidature['id']]);
        }
        // (Re)synchronise les documents historiques pour cette candidature
        etudiant_sync_documents_historiques($pdo, (int)$existing['id'], $candidature, $adminUser);
        return [
            'etudiant_id'   => (int)$existing['id'],
            'numero'        => (string)$existing['numero_etudiant'],
            'token'         => '',
            'deja_existant' => true,
        ];
    }

    $pdo->beginTransaction();
    try {
        $numero = etudiant_generate_numero($pdo);
        $stmt = $pdo->prepare(
            "INSERT INTO etudiants
                (email, password_hash, email_verifie,
                 civilite, prenom, nom, date_naissance, nationalite, telephone,
                 numero_etudiant, statut, cree_par_admin)
             VALUES (?, NULL, 0, ?, ?, ?, ?, ?, ?, ?, 'actif', ?)"
        );
        $stmt->execute([
            $email,
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

        $token = etudiant_create_token($pdo, $etuId, 'activation', 14 * 24 * 3600);

        // Synchronise les "documents historiques" de la candidature dans les
        // nouvelles tables `factures` + `documents` pour qu'ils apparaissent
        // immédiatement dans l'espace étudiant. PDF jamais stockés : on n'écrit
        // que les métadonnées + data_json (régénération à la volée).
        etudiant_sync_documents_historiques($pdo, $etuId, $candidature, $adminUser);

        $pdo->commit();
        return [
            'etudiant_id'   => $etuId,
            'numero'        => $numero,
            'token'         => $token,
            'deja_existant' => false,
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
            'Frais de dossier de candidature IPEC',
            'Frais de dossier non remboursables — traitement de la candidature ' . ($candidature['reference'] ?? ''),
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
            'Récapitulatif de candidature ' . ($candidature['reference'] ?? ''),
            'Confirmation officielle de réception de votre dossier de candidature à l\'IPEC.',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            $emis, $adminUser,
        ]);
    }
}
