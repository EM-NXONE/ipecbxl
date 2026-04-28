<?php
/**
 * IPEC Admin — Fiche détail d'une candidature
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/_etudiants.php';
admin_require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c) {
    admin_layout_start('Introuvable');
    echo '<div class="card"><p>Candidature introuvable.</p><a href="index.php" class="btn">Retour</a></div>';
    admin_layout_end();
    exit;
}

// Étudiant rattaché (si déjà créé) — sinon on tente une détection par e-mail
$etudiant = null;
if (!empty($c['etudiant_id'])) {
    $eStmt = $pdo->prepare("SELECT * FROM etudiants WHERE id = ?");
    $eStmt->execute([(int)$c['etudiant_id']]);
    $etudiant = $eStmt->fetch() ?: null;
}
$etudiantHomonyme = null;
if (!$etudiant) {
    $etudiantHomonyme = etudiant_find_by_email($pdo, (string)$c['email']);
}

// Historique des actions admin
$histStmt = $pdo->prepare("SELECT * FROM admin_actions WHERE candidature_id = ? ORDER BY created_at DESC LIMIT 50");
$histStmt->execute([$id]);
$historique = $histStmt->fetchAll();

$csrf = admin_csrf_token();

admin_layout_start('Candidature ' . $c['reference']);
admin_flash();
?>

<a href="index.php" class="muted" style="font-size:12px;">← Retour à la liste</a>

<h1 style="margin-top:8px;">
    <?= admin_h($c['prenom']) ?> <?= admin_h($c['nom']) ?>
    <span class="badge badge-<?= admin_h($c['statut']) ?>" style="vertical-align:middle;margin-left:8px;">
        <?= admin_h(ADMIN_STATUTS[$c['statut']] ?? $c['statut']) ?>
    </span>
</h1>

<div class="actions-bar">
    <a class="btn" href="action.php?do=download_candidature&id=<?= $id ?>">📄 Télécharger PDF candidature</a>
    <a class="btn" href="action.php?do=download_facture&id=<?= $id ?>">🧾 Télécharger PDF facture</a>

    <form method="POST" action="action.php" style="display:inline;"
          onsubmit="return confirm('Renvoyer l\'e-mail de confirmation au candidat (avec les 2 PDF) ?');">
        <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>">
        <input type="hidden" name="do" value="resend_email">
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="btn-secondary">✉️ Renvoyer l'e-mail</button>
    </form>

    <?php if ((int)$c['facture_payee'] === 1): ?>
        <form method="POST" action="action.php" style="display:inline;"
              onsubmit="return confirm('Annuler le marquage \'payée\' de cette facture ?');">
            <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>">
            <input type="hidden" name="do" value="mark_unpaid">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn-secondary">↶ Annuler le paiement</button>
        </form>
    <?php else: ?>
        <form method="POST" action="action.php" style="display:inline;">
            <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>">
            <input type="hidden" name="do" value="mark_paid">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn-success">💶 Marquer la facture comme payée</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2 style="margin-top:0;">Statut du dossier</h2>
    <form method="POST" action="action.php" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;">
        <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>">
        <input type="hidden" name="do" value="update_statut">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-row" style="margin:0;min-width:220px;">
            <label>Nouveau statut</label>
            <select name="statut">
                <?php foreach (ADMIN_STATUTS as $k => $label): ?>
                    <option value="<?= admin_h($k) ?>" <?= $c['statut'] === $k ? 'selected' : '' ?>>
                        <?= admin_h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Enregistrer</button>
    </form>
</div>

<div class="card">
    <h2 style="margin-top:0;">Identité &amp; contact</h2>
    <dl class="detail-grid">
        <div>
            <dt>Référence candidature</dt><dd class="mono"><?= admin_h($c['reference']) ?></dd>
            <dt>Civilité</dt><dd><?= admin_h($c['civilite'] ?: '—') ?></dd>
            <dt>Prénom · Nom</dt><dd><?= admin_h($c['prenom']) ?> <?= admin_h($c['nom']) ?></dd>
            <dt>Date de naissance</dt><dd><?= admin_h($c['date_naissance'] ?: '—') ?></dd>
            <dt>Nationalité</dt><dd><?= admin_h($c['nationalite'] ?: '—') ?></dd>
        </div>
        <div>
            <dt>E-mail</dt><dd><a href="mailto:<?= admin_h($c['email']) ?>"><?= admin_h($c['email']) ?></a></dd>
            <dt>Téléphone</dt><dd><?= admin_h($c['telephone'] ?: '—') ?></dd>
            <dt>Adresse</dt>
            <dd>
                <?php
                $adr = trim(($c['rue'] ?: '') . ' ' . ($c['numero'] ?: ''));
                $adr2 = trim(($c['code_postal'] ?: '') . ' ' . ($c['ville'] ?: ''));
                echo $adr ? admin_h($adr) : '—';
                if ($adr2) echo '<br>' . admin_h($adr2);
                if ($c['pays_residence']) echo '<br>' . admin_h($c['pays_residence']);
                ?>
            </dd>
            <dt>Date de soumission</dt><dd><?= admin_format_date($c['created_at']) ?></dd>
            <dt>IP soumission</dt><dd class="mono" style="font-size:11px;"><?= admin_h($c['ip'] ?: '—') ?></dd>
        </div>
    </dl>
</div>

<div class="card">
    <h2 style="margin-top:0;">Programme demandé</h2>
    <dl class="detail-grid">
        <div>
            <dt>Programme</dt><dd><?= admin_h($c['programme'] ?: '—') ?></dd>
            <dt>Année</dt><dd><?= admin_h($c['annee'] ?: '—') ?></dd>
            <dt>Spécialisation</dt><dd><?= admin_h($c['specialisation'] ?: '—') ?></dd>
        </div>
        <div>
            <dt>Année académique</dt><dd><?= admin_h($c['annee_academique'] ?: '—') ?></dd>
            <dt>Rentrée souhaitée</dt><dd><?= admin_h($c['rentree'] ?: '—') ?></dd>
        </div>
    </dl>
</div>

<div class="card">
    <h2 style="margin-top:0;">Facture frais de dossier (400 €)</h2>
    <dl class="detail-grid">
        <div>
            <dt>Numéro de facture</dt><dd class="mono"><?= admin_h($c['facture_numero'] ?: '—') ?></dd>
            <dt>Statut</dt>
            <dd>
                <?php if ((int)$c['facture_payee'] === 1): ?>
                    <span class="badge badge-paid">Payée</span>
                <?php else: ?>
                    <span class="badge badge-unpaid">En attente de paiement</span>
                <?php endif; ?>
            </dd>
        </div>
        <div>
            <dt>Date de paiement</dt><dd><?= admin_format_date($c['facture_payee_at']) ?></dd>
            <dt>Marqué payée par</dt><dd><?= admin_h($c['facture_payee_par'] ?: '—') ?></dd>
        </div>
    </dl>
</div>

<div class="card">
    <h2 style="margin-top:0;">Espace étudiant</h2>
    <?php if ($etudiant): ?>
        <dl class="detail-grid">
            <div>
                <dt>Numéro étudiant</dt><dd class="mono"><?= admin_h($etudiant['numero_etudiant'] ?: '—') ?></dd>
                <dt>E-mail du compte</dt><dd><?= admin_h($etudiant['email']) ?></dd>
                <dt>Statut compte</dt>
                <dd>
                    <?php if ($etudiant['password_hash']): ?>
                        <span class="badge badge-paid">Activé</span>
                    <?php else: ?>
                        <span class="badge badge-unpaid">En attente d'activation</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Créé le</dt><dd><?= admin_format_date($etudiant['created_at']) ?></dd>
                <dt>Créé par</dt><dd><?= admin_h($etudiant['cree_par_admin'] ?: '—') ?></dd>
                <dt>Dernière connexion</dt><dd><?= admin_format_date($etudiant['derniere_connexion']) ?></dd>
            </div>
        </dl>
    <?php elseif ($etudiantHomonyme): ?>
        <p>Un compte étudiant existe déjà pour <strong><?= admin_h($etudiantHomonyme['email']) ?></strong>
           (n° <span class="mono"><?= admin_h($etudiantHomonyme['numero_etudiant']) ?></span>),
           mais cette candidature n'y est pas rattachée.</p>
        <form method="POST" action="action.php" style="display:inline;"
              onsubmit="return confirm('Rattacher cette candidature au compte étudiant existant ?');">
            <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>">
            <input type="hidden" name="do" value="create_etudiant">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit">🔗 Rattacher au compte existant</button>
        </form>
    <?php else: ?>
        <p class="muted" style="margin-top:0;">
            Aucun compte étudiant n'est encore associé à cette candidature.
            La création génère un identifiant interne (IPEC-ETU-AAAA-XXXX) et un lien
            d'activation à transmettre au candidat pour qu'il définisse son mot de passe.
        </p>
        <form method="POST" action="action.php" style="display:inline;"
              onsubmit="return confirm('Créer un compte étudiant pour <?= admin_h($c['prenom'].' '.$c['nom']) ?> (<?= admin_h($c['email']) ?>) ?');">
            <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>">
            <input type="hidden" name="do" value="create_etudiant">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn-success">👤 Créer un compte étudiant</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($c['message']): ?>
<div class="card">
    <div style="white-space:pre-wrap;background:var(--bg);padding:12px;border-radius:4px;border:1px solid var(--border);">
        <?= admin_h($c['message']) ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h2 style="margin-top:0;">Historique des actions admin</h2>
    <?php if (!$historique): ?>
        <p class="muted">Aucune action enregistrée.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Date</th><th>Action</th><th>Détail</th><th>Par</th></tr></thead>
            <tbody>
            <?php foreach ($historique as $h): ?>
                <tr>
                    <td class="mono" style="font-size:11px;"><?= admin_format_date($h['created_at']) ?></td>
                    <td><strong><?= admin_h($h['action']) ?></strong></td>
                    <td class="muted"><?= admin_h($h['detail'] ?: '—') ?></td>
                    <td><?= admin_h($h['admin_user']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php admin_layout_end();
