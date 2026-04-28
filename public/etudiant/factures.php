<?php
/**
 * IPEC — Espace étudiant : factures (liste complète)
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';
$user = etu_require_login();
$pdo  = db();

$stmt = $pdo->prepare("SELECT * FROM factures
                       WHERE etudiant_id=? AND visible_etudiant=1
                       ORDER BY date_emission DESC, id DESC");
$stmt->execute([$user['id']]);
$factures = $stmt->fetchAll();

$totalDu = 0; $totalPaye = 0;
foreach ($factures as $f) {
    if (in_array($f['statut_paiement'], ['en_attente','partiellement_payee'], true)) {
        $totalDu += (int)$f['montant_ttc_cents'];
    } elseif ($f['statut_paiement'] === 'payee') {
        $totalPaye += (int)$f['montant_ttc_cents'];
    }
}

$STATUTS = [
    'en_attente'          => ['En attente', 'badge-warning'],
    'partiellement_payee' => ['Partiel',    'badge-warning'],
    'payee'               => ['Payée',      'badge-success'],
    'annulee'             => ['Annulée',    'badge-muted'],
    'remboursee'          => ['Remboursée', 'badge-muted'],
];

etu_layout_start('Mes factures', $user);
?>
<h1>Mes factures</h1>
<p class="muted" style="margin-bottom:24px;">Toutes les factures émises par l'IPEC à ton nom.</p>

<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-label">Total dû</div>
        <div class="kpi-value"><?= etu_money_cents($totalDu) ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Total payé</div>
        <div class="kpi-value"><?= etu_money_cents($totalPaye) ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Nombre de factures</div>
        <div class="kpi-value"><?= count($factures) ?></div>
    </div>
</div>

<div class="card">
    <?php if (!$factures): ?>
        <div class="empty">Aucune facture pour l'instant.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Numéro</th><th>Type</th><th>Libellé</th>
                    <th>Émise le</th><th>Échéance</th>
                    <th>Montant</th><th>Statut</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($factures as $f):
                $s = $STATUTS[$f['statut_paiement']] ?? [$f['statut_paiement'],'badge'];
            ?>
                <tr>
                    <td class="mono"><?= etu_h($f['numero']) ?></td>
                    <td><span class="badge badge-muted"><?= etu_h(str_replace('_',' ',$f['type'])) ?></span></td>
                    <td><?= etu_h($f['libelle']) ?></td>
                    <td><?= etu_format_date($f['date_emission']) ?></td>
                    <td><?= etu_format_date($f['date_echeance']) ?></td>
                    <td><strong><?= etu_money_cents((int)$f['montant_ttc_cents'], $f['devise']) ?></strong></td>
                    <td><span class="badge <?= etu_h($s[1]) ?>"><?= etu_h($s[0]) ?></span></td>
                    <td><a class="btn btn-ghost" href="<?= etu_url('/telecharger.php') ?>?type=facture&amp;id=<?= (int)$f['id'] ?>">PDF ↓</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php etu_layout_end($user);
