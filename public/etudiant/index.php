<?php
/**
 * IPEC — Espace étudiant : tableau de bord
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';
$user = etu_require_login();
$pdo  = db();

// Candidatures rattachées (en pratique 1, mais on prévoit le cas multi)
$stmt = $pdo->prepare("SELECT id, reference, statut, programme, annee, specialisation,
                              annee_academique, rentree, created_at,
                              facture_numero, facture_payee
                       FROM candidatures WHERE etudiant_id = ?
                       ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$candidatures = $stmt->fetchAll();

// Factures à payer
$stmt = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(montant_ttc_cents),0) AS s
                       FROM factures
                       WHERE etudiant_id = ? AND visible_etudiant=1
                         AND statut_paiement IN ('en_attente','partiellement_payee')");
$stmt->execute([$user['id']]);
$ouvertes = $stmt->fetch();

// Compteurs
$nbDocs = (int)$pdo->query("SELECT COUNT(*) FROM documents
                            WHERE etudiant_id=" . (int)$user['id'] . "
                              AND visible_etudiant=1 AND statut='publie'")->fetchColumn();

// Dernières factures
$stmt = $pdo->prepare("SELECT * FROM factures
                       WHERE etudiant_id=? AND visible_etudiant=1
                       ORDER BY date_emission DESC, id DESC LIMIT 5");
$stmt->execute([$user['id']]);
$lastFact = $stmt->fetchAll();

// Derniers documents
$stmt = $pdo->prepare("SELECT * FROM documents
                       WHERE etudiant_id=? AND visible_etudiant=1 AND statut='publie'
                       ORDER BY date_emission DESC, id DESC LIMIT 5");
$stmt->execute([$user['id']]);
$lastDocs = $stmt->fetchAll();

const STATUT_LABELS = [
    'recue'    => ['Reçue', 'badge'],
    'en_cours' => ['En cours d\'étude', 'badge-warning'],
    'validee'  => ['Validée', 'badge-success'],
    'refusee'  => ['Refusée', 'badge-danger'],
    'annulee'  => ['Annulée', 'badge-muted'],
];
const FACT_STATUTS = [
    'en_attente'         => ['En attente', 'badge-warning'],
    'partiellement_payee'=> ['Partiel',    'badge-warning'],
    'payee'              => ['Payée',      'badge-success'],
    'annulee'            => ['Annulée',    'badge-muted'],
    'remboursee'         => ['Remboursée', 'badge-muted'],
];

etu_layout_start('Tableau de bord', $user);
?>

<h1>Bonjour <?= etu_h($user['prenom']) ?>.</h1>
<p class="muted" style="margin-bottom:24px;">Voici l'état de ton dossier IPEC.</p>

<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-label">Numéro étudiant</div>
        <div class="kpi-value mono" style="font-size:18px;"><?= etu_h($user['numero_etudiant'] ?: '—') ?></div>
        <div class="kpi-sub">Identifiant interne IPEC</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Dossier</div>
        <div class="kpi-value" style="font-size:18px;">
            <?php if ($candidatures): $c0 = $candidatures[0]; $s = STATUT_LABELS[$c0['statut']] ?? [$c0['statut'],'badge']; ?>
                <span class="badge <?= etu_h($s[1]) ?>"><?= etu_h($s[0]) ?></span>
            <?php else: ?>
                <span class="muted">—</span>
            <?php endif; ?>
        </div>
        <div class="kpi-sub"><?= count($candidatures) ?> candidature(s) rattachée(s)</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Solde dû</div>
        <div class="kpi-value"><?= etu_money_cents((int)$ouvertes['s']) ?></div>
        <div class="kpi-sub"><?= (int)$ouvertes['n'] ?> facture(s) à régler</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Documents</div>
        <div class="kpi-value"><?= $nbDocs ?></div>
        <div class="kpi-sub">Disponibles dans ton espace</div>
    </div>
</div>

<div class="card">
    <h2>Mon dossier de candidature</h2>
    <?php if (!$candidatures): ?>
        <p class="muted">Aucune candidature n'est rattachée à ton compte pour le moment.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Référence</th><th>Programme</th><th>Année académique</th><th>Statut</th><th>Soumis le</th></tr>
            </thead>
            <tbody>
            <?php foreach ($candidatures as $c):
                $s = STATUT_LABELS[$c['statut']] ?? [$c['statut'],'badge'];
            ?>
                <tr>
                    <td class="mono"><?= etu_h($c['reference']) ?></td>
                    <td>
                        <strong><?= etu_h($c['programme'] ?: '—') ?></strong>
                        <?php if ($c['annee']): ?> · <?= etu_h($c['annee']) ?><?php endif; ?>
                        <?php if ($c['specialisation']): ?>
                            <div class="muted" style="font-size:12px;"><?= etu_h($c['specialisation']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= etu_h($c['annee_academique'] ?: '—') ?></td>
                    <td><span class="badge <?= etu_h($s[1]) ?>"><?= etu_h($s[0]) ?></span></td>
                    <td><?= etu_format_date($c['created_at'], true) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2 style="display:flex; align-items:center; justify-content:space-between;">
        Dernières factures <a class="btn btn-ghost" href="/etudiant/factures.php">Tout voir →</a>
    </h2>
    <?php if (!$lastFact): ?>
        <p class="muted">Aucune facture pour l'instant.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Numéro</th><th>Libellé</th><th>Émise le</th><th>Montant</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($lastFact as $f):
                $s = FACT_STATUTS[$f['statut_paiement']] ?? [$f['statut_paiement'],'badge'];
            ?>
                <tr>
                    <td class="mono"><?= etu_h($f['numero']) ?></td>
                    <td><?= etu_h($f['libelle']) ?></td>
                    <td><?= etu_format_date($f['date_emission']) ?></td>
                    <td><strong><?= etu_money_cents((int)$f['montant_ttc_cents'], $f['devise']) ?></strong></td>
                    <td><span class="badge <?= etu_h($s[1]) ?>"><?= etu_h($s[0]) ?></span></td>
                    <td><a class="btn btn-ghost" href="/etudiant/telecharger.php?type=facture&amp;id=<?= (int)$f['id'] ?>">PDF ↓</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2 style="display:flex; align-items:center; justify-content:space-between;">
        Derniers documents <a class="btn btn-ghost" href="/etudiant/documents.php">Tout voir →</a>
    </h2>
    <?php if (!$lastDocs): ?>
        <p class="muted">Aucun document pour l'instant.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Référence</th><th>Type</th><th>Titre</th><th>Émis le</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($lastDocs as $d): ?>
                <tr>
                    <td class="mono"><?= etu_h($d['reference']) ?></td>
                    <td><span class="badge badge-muted"><?= etu_h(str_replace('_',' ',$d['type'])) ?></span></td>
                    <td><?= etu_h($d['titre']) ?></td>
                    <td><?= etu_format_date($d['date_emission']) ?></td>
                    <td><a class="btn btn-ghost" href="/etudiant/telecharger.php?type=document&amp;id=<?= (int)$d['id'] ?>">PDF ↓</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php etu_layout_end($user);
