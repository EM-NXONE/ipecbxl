<?php
/**
 * IPEC Admin — Layout commun (header / footer)
 * Inclus depuis chaque page protégée APRÈS admin_require_login().
 */

if (!function_exists('admin_layout_start')) {

function admin_layout_start(string $title): void {
    $h = 'admin_h';
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $h($title) ?> — Admin IPEC</title>
<style>
:root {
    --bg: #0f1419;
    --surface: #1a1f26;
    --surface-2: #232932;
    --border: #2d3540;
    --text: #e8eaed;
    --muted: #8b95a5;
    --blue: #4a9eff;
    --blue-hover: #66b0ff;
    --green: #34d399;
    --red: #ef4444;
    --amber: #fbbf24;
}
* { box-sizing: border-box; }
body {
    margin: 0; padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
    background: var(--bg); color: var(--text);
    font-size: 14px; line-height: 1.5;
}
a { color: var(--blue); text-decoration: none; }
a:hover { color: var(--blue-hover); text-decoration: underline; }
header.topbar {
    background: var(--surface); border-bottom: 1px solid var(--border);
    padding: 12px 24px; display: flex; align-items: center;
    justify-content: space-between; gap: 16px;
}
header.topbar .brand {
    font-weight: 700; font-size: 16px; letter-spacing: 0.5px;
}
header.topbar .brand span { color: var(--blue); }
header.topbar nav { display: flex; gap: 20px; align-items: center; }
header.topbar nav a { color: var(--muted); font-size: 13px; }
header.topbar nav a:hover { color: var(--text); text-decoration: none; }
header.topbar .user { font-size: 12px; color: var(--muted); }
main { max-width: 1400px; margin: 0 auto; padding: 24px; }
h1 { font-size: 22px; margin: 0 0 20px; font-weight: 600; }
h2 { font-size: 16px; margin: 24px 0 12px; font-weight: 600; }
.card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 6px; padding: 20px; margin-bottom: 16px;
}
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); }
th { background: var(--surface-2); font-weight: 600; color: var(--muted);
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
tbody tr:hover { background: var(--surface-2); }
.badge {
    display: inline-block; padding: 2px 8px; border-radius: 10px;
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;
}
.badge-recue { background: rgba(74,158,255,0.15); color: var(--blue); }
.badge-en_cours { background: rgba(251,191,36,0.15); color: var(--amber); }
.badge-validee { background: rgba(52,211,153,0.15); color: var(--green); }
.badge-refusee { background: rgba(239,68,68,0.15); color: var(--red); }
.badge-annulee { background: rgba(139,149,165,0.15); color: var(--muted); }
.badge-paid { background: rgba(52,211,153,0.15); color: var(--green); }
.badge-unpaid { background: rgba(251,191,36,0.15); color: var(--amber); }
button, .btn {
    display: inline-block; padding: 8px 14px; border-radius: 4px; border: none;
    background: var(--blue); color: white; font-size: 13px; font-weight: 500;
    cursor: pointer; text-decoration: none; transition: background 0.15s;
}
button:hover, .btn:hover { background: var(--blue-hover); text-decoration: none; color: white; }
button.btn-secondary, .btn-secondary {
    background: var(--surface-2); color: var(--text); border: 1px solid var(--border);
}
button.btn-secondary:hover, .btn-secondary:hover { background: var(--border); color: var(--text); }
button.btn-danger, .btn-danger { background: var(--red); }
button.btn-danger:hover { background: #dc2626; }
button.btn-success, .btn-success { background: var(--green); color: var(--bg); }
button.btn-success:hover { background: #10b981; }
.form-row { margin-bottom: 12px; }
.form-row label { display: block; margin-bottom: 4px; font-size: 12px; color: var(--muted); }
.form-row input, .form-row select, .form-row textarea {
    width: 100%; padding: 8px 10px; background: var(--bg); color: var(--text);
    border: 1px solid var(--border); border-radius: 4px; font-size: 14px;
    font-family: inherit;
}
.form-row input:focus, .form-row select:focus { outline: none; border-color: var(--blue); }
.filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: end; margin-bottom: 16px; }
.filters .form-row { margin-bottom: 0; min-width: 160px; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.detail-grid dt { color: var(--muted); font-size: 11px; text-transform: uppercase;
    letter-spacing: 0.4px; margin-bottom: 2px; margin-top: 10px; }
.detail-grid dt:first-child { margin-top: 0; }
.detail-grid dd { margin: 0; font-size: 14px; }
.actions-bar { display: flex; gap: 8px; flex-wrap: wrap; margin: 16px 0; }
.flash {
    padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; font-size: 13px;
}
.flash-success { background: rgba(52,211,153,0.1); color: var(--green); border: 1px solid rgba(52,211,153,0.3); }
.flash-error { background: rgba(239,68,68,0.1); color: var(--red); border: 1px solid rgba(239,68,68,0.3); }
.muted { color: var(--muted); }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
.pagination { display: flex; gap: 4px; margin-top: 16px; justify-content: center; }
.pagination a, .pagination span {
    padding: 6px 10px; border-radius: 4px; background: var(--surface-2);
    color: var(--text); font-size: 12px; min-width: 32px; text-align: center;
}
.pagination .current { background: var(--blue); color: white; }
@media (max-width: 720px) {
    .detail-grid { grid-template-columns: 1fr; }
    main { padding: 12px; }
    table { font-size: 12px; }
    th, td { padding: 8px 6px; }
}
</style>
</head>
<body>
<header class="topbar">
    <div class="brand">IPEC <span>Admin</span></div>
    <nav>
        <a href="index.php">Candidatures</a>
        <a href="logout.php">Déconnexion</a>
        <span class="user">— <?= $h(admin_current_user()) ?></span>
    </nav>
</header>
<main>
<?php
}

function admin_layout_end(): void {
    ?>
</main>
</body>
</html>
<?php
}

function admin_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'] ?? 'success';
        $msg = $_SESSION['flash']['msg'] ?? '';
        echo '<div class="flash flash-' . admin_h($type) . '">' . admin_h($msg) . '</div>';
        unset($_SESSION['flash']);
    }
}

function admin_set_flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

}
