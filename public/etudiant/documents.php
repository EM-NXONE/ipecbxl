<?php
/**
 * IPEC — Espace étudiant : documents administratifs
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';
$user = etu_require_login();
$pdo  = db();

$stmt = $pdo->prepare("SELECT * FROM documents
                       WHERE etudiant_id=? AND visible_etudiant=1 AND statut='publie'
                       ORDER BY date_emission DESC, id DESC");
$stmt->execute([$user['id']]);
$docs = $stmt->fetchAll();

etu_layout_start('Mes documents', $user);
?>
<h1>Mes documents</h1>
<p class="muted" style="margin-bottom:24px;">Attestations, conventions, courriers — régénérés à la demande au format PDF.</p>

<div class="card">
    <?php if (!$docs): ?>
        <div class="empty">Aucun document pour l'instant.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Référence</th><th>Type</th><th>Titre</th>
                    <th>Émis le</th><th>Valide jusqu'au</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($docs as $d): ?>
                <tr>
                    <td class="mono"><?= etu_h($d['reference']) ?></td>
                    <td><span class="badge badge-muted"><?= etu_h(str_replace('_',' ',$d['type'])) ?></span></td>
                    <td>
                        <strong><?= etu_h($d['titre']) ?></strong>
                        <?php if ($d['description']): ?>
                            <div class="muted" style="font-size:12px;"><?= etu_h($d['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= etu_format_date($d['date_emission']) ?></td>
                    <td><?= etu_format_date($d['valide_jusqu_au']) ?></td>
                    <td><a class="btn btn-ghost" href="<?= etu_url('/telecharger.php') ?>?type=document&amp;id=<?= (int)$d['id'] ?>">PDF ↓</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php etu_layout_end($user);
