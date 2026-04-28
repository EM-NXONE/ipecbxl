<?php
/**
 * IPEC Admin — Liste des candidatures (page d'accueil)
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';
admin_require_login();

// Filtres
$q       = trim((string)($_GET['q'] ?? ''));
$statut  = (string)($_GET['statut'] ?? '');
$payee   = (string)($_GET['payee'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(prenom LIKE :q OR nom LIKE :q OR email LIKE :q OR reference LIKE :q OR facture_numero LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($statut !== '' && isset(ADMIN_STATUTS[$statut])) {
    $where[] = 'statut = :statut';
    $params[':statut'] = $statut;
}
if ($payee === '1') {
    $where[] = 'facture_payee = 1';
} elseif ($payee === '0') {
    $where[] = 'facture_payee = 0';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$pdo = db();

// Total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM candidatures $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

// Page
$sql = "SELECT id, reference, statut, prenom, nom, email, programme, annee,
               annee_academique, facture_numero, facture_payee, facture_payee_at,
               created_at
        FROM candidatures
        $whereSql
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

admin_layout_start('Candidatures');
admin_flash();
?>

<h1>Candidatures <span class="muted" style="font-size:14px;font-weight:400;">— <?= number_format($total, 0, ',', ' ') ?> au total</span></h1>

<form method="GET" class="card">
    <div class="filters">
        <div class="form-row" style="flex:2;min-width:240px;">
            <label for="q">Recherche (nom, prénom, email, référence)</label>
            <input type="search" id="q" name="q" value="<?= admin_h($q) ?>" placeholder="Dupont, IPEC-CAND-2026-...">
        </div>
        <div class="form-row">
            <label for="statut">Statut</label>
            <select id="statut" name="statut">
                <option value="">Tous</option>
                <?php foreach (ADMIN_STATUTS as $k => $label): ?>
                    <option value="<?= admin_h($k) ?>" <?= $statut === $k ? 'selected' : '' ?>><?= admin_h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="payee">Facture</label>
            <select id="payee" name="payee">
                <option value="">Toutes</option>
                <option value="1" <?= $payee === '1' ? 'selected' : '' ?>>Payées</option>
                <option value="0" <?= $payee === '0' ? 'selected' : '' ?>>En attente</option>
            </select>
        </div>
        <div class="form-row">
            <button type="submit">Filtrer</button>
            <?php if ($q || $statut || $payee !== ''): ?>
                <a href="index.php" class="btn btn-secondary">Réinitialiser</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<div class="card" style="padding:0;overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Candidat</th>
                <th>Programme</th>
                <th>Référence</th>
                <th>Facture</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;" class="muted">Aucune candidature trouvée.</td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr>
                <td class="mono"><?= admin_format_date($r['created_at']) ?></td>
                <td>
                    <strong><?= admin_h($r['prenom']) ?> <?= admin_h($r['nom']) ?></strong><br>
                    <span class="muted mono" style="font-size:11px;"><?= admin_h($r['email']) ?></span>
                </td>
                <td>
                    <?= admin_h($r['programme'] ?: '—') ?>
                    <?php if ($r['annee']): ?>
                        <br><span class="muted" style="font-size:11px;"><?= admin_h($r['annee']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="mono" style="font-size:11px;"><?= admin_h($r['reference']) ?></td>
                <td>
                    <?php if ((int)$r['facture_payee'] === 1): ?>
                        <span class="badge badge-paid">Payée</span>
                    <?php else: ?>
                        <span class="badge badge-unpaid">En attente</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?= admin_h($r['statut']) ?>"><?= admin_h(ADMIN_STATUTS[$r['statut']] ?? $r['statut']) ?></span>
                </td>
                <td><a href="detail.php?id=<?= (int)$r['id'] ?>" class="btn btn-secondary" style="padding:4px 10px;font-size:12px;">Ouvrir</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
<div class="pagination">
    <?php
    $qsBase = $_GET; unset($qsBase['page']);
    $base = 'index.php?' . http_build_query($qsBase);
    $sep = $qsBase ? '&' : '';
    for ($p = 1; $p <= $pages; $p++):
        if ($p === $page): ?>
            <span class="current"><?= $p ?></span>
        <?php else: ?>
            <a href="<?= admin_h($base . $sep . 'page=' . $p) ?>"><?= $p ?></a>
        <?php endif;
    endfor; ?>
</div>
<?php endif; ?>

<?php admin_layout_end();
