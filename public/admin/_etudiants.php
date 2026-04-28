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
