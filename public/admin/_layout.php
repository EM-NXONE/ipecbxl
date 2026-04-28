<?php
/**
 * IPEC Admin — Layout commun (header / footer)
 * Aligné sur le thème éditorial du site (paper warm, deep ink, blue accent).
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root, html[data-theme="light"] {
    /* Site palette — light editorial */
    --bg:        #FBFAF7;          /* paper */
    --surface:   #F4F2EC;          /* tinted band */
    --card:      #FFFFFF;
    --ink:       #1B1F2A;          /* deep ink text */
    --muted:     #5C6373;
    --hairline:  rgba(27, 31, 42, 0.10);
    --hairline-strong: rgba(27, 31, 42, 0.16);
    --primary:   #1F3D8A;          /* deep editorial blue */
    --primary-hover: #16306E;
    --primary-soft:  rgba(31, 61, 138, 0.08);
    --primary-on:    #ffffff;
    --success:   #2F8F5E;
    --success-soft: rgba(47, 143, 94, 0.10);
    --amber:     #B07B0A;
    --amber-soft: rgba(176, 123, 10, 0.12);
    --danger:    #B0332B;
    --danger-soft: rgba(176, 51, 43, 0.10);
    --radial-tint: rgba(31, 61, 138, 0.05);

    --font-display: 'Fraunces', Georgia, serif;
    --font-body: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;

    --shadow-sm: 0 1px 2px rgba(27,31,42,0.04);
    --shadow-md: 0 1px 2px rgba(27,31,42,0.04), 0 8px 24px -10px rgba(27,31,42,0.10);
}

html[data-theme="dark"] {
    /* Deep midnight palette aligned with site dark mode */
    --bg:        #0F1320;
    --surface:   #161B2C;
    --card:      #1B2236;
    --ink:       #ECEEF5;
    --muted:     #98A0B5;
    --hairline:  rgba(236, 238, 245, 0.10);
    --hairline-strong: rgba(236, 238, 245, 0.18);
    --primary:   #6B9BFF;
    --primary-hover: #88B0FF;
    --primary-soft:  rgba(107, 155, 255, 0.14);
    --primary-on:    #0F1320;
    --success:   #4FD18A;
    --success-soft: rgba(79, 209, 138, 0.14);
    --amber:     #F5B948;
    --amber-soft: rgba(245, 185, 72, 0.14);
    --danger:    #F26B63;
    --danger-soft: rgba(242, 107, 99, 0.14);
    --radial-tint: rgba(107, 155, 255, 0.10);

    --shadow-sm: 0 1px 2px rgba(0,0,0,0.30);
    --shadow-md: 0 1px 2px rgba(0,0,0,0.30), 0 12px 32px -10px rgba(0,0,0,0.55);
}
html[data-theme="dark"] header.topbar {
    background: rgba(15, 19, 32, 0.78) !important;
}
html[data-theme="dark"] .form-row input,
html[data-theme="dark"] .form-row select,
html[data-theme="dark"] .form-row textarea {
    background: var(--bg);
}
* { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body {
    margin: 0; padding: 0;
    font-family: var(--font-body);
    background-color: var(--bg);
    background-image: radial-gradient(ellipse at top, rgba(31,61,138,0.05), transparent 65%);
    background-attachment: fixed;
    color: var(--ink);
    font-size: 14px; line-height: 1.5;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
a { color: var(--primary); text-decoration: none; }
a:hover { color: var(--primary-hover); text-decoration: underline; }

/* ---------- Topbar ---------- */
header.topbar {
    background: rgba(251, 250, 247, 0.85);
    backdrop-filter: saturate(180%) blur(12px);
    -webkit-backdrop-filter: saturate(180%) blur(12px);
    border-bottom: 1px solid var(--hairline);
    padding: 14px 28px;
    display: flex; align-items: center;
    justify-content: space-between; gap: 16px;
    position: sticky; top: 0; z-index: 50;
}
header.topbar .brand {
    font-family: var(--font-display);
    font-weight: 500;
    font-size: 19px;
    letter-spacing: -0.01em;
    color: var(--ink);
}
header.topbar .brand .dot {
    display: inline-block; width: 6px; height: 6px; border-radius: 50%;
    background: var(--primary); margin: 0 10px 2px; vertical-align: middle;
}
header.topbar .brand em {
    font-style: italic; font-weight: 400; color: var(--muted);
}
header.topbar nav { display: flex; gap: 24px; align-items: center; }
header.topbar nav a {
    color: var(--muted); font-size: 13px; font-weight: 500;
    letter-spacing: 0.01em;
}
header.topbar nav a:hover { color: var(--ink); text-decoration: none; }
header.topbar .user {
    font-size: 12px; color: var(--muted);
    padding-left: 18px; border-left: 1px solid var(--hairline);
}

/* ---------- Layout ---------- */
main { max-width: 1320px; margin: 0 auto; padding: 32px 28px 64px; }

h1 {
    font-family: var(--font-display);
    font-size: 30px; font-weight: 400; letter-spacing: -0.02em;
    margin: 0 0 24px; color: var(--ink); line-height: 1.15;
}
h2 {
    font-family: var(--font-display);
    font-size: 18px; font-weight: 500; letter-spacing: -0.01em;
    margin: 28px 0 14px; color: var(--ink);
}

/* ---------- Card ---------- */
.card {
    background: var(--card);
    border: 1px solid var(--hairline);
    border-radius: 10px;
    padding: 24px;
    margin-bottom: 18px;
    box-shadow: var(--shadow-sm);
}

/* ---------- Table ---------- */
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--hairline); }
th {
    background: var(--surface); font-weight: 600; color: var(--muted);
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em;
}
tbody tr { transition: background 0.12s ease; }
tbody tr:hover { background: var(--primary-soft); }
tbody tr:last-child td { border-bottom: none; }

/* ---------- Badges ---------- */
.badge {
    display: inline-block; padding: 3px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 600; letter-spacing: 0.02em;
    border: 1px solid transparent;
}
.badge-recue    { background: var(--primary-soft); color: var(--primary); border-color: rgba(31,61,138,0.18); }
.badge-en_cours { background: var(--amber-soft);   color: var(--amber);   border-color: rgba(176,123,10,0.22); }
.badge-validee  { background: var(--success-soft); color: var(--success); border-color: rgba(47,143,94,0.22); }
.badge-refusee  { background: var(--danger-soft);  color: var(--danger);  border-color: rgba(176,51,43,0.22); }
.badge-annulee  { background: rgba(92,99,115,0.10);color: var(--muted);   border-color: rgba(92,99,115,0.20); }
.badge-paid     { background: var(--success-soft); color: var(--success); border-color: rgba(47,143,94,0.22); }
.badge-unpaid   { background: var(--amber-soft);   color: var(--amber);   border-color: rgba(176,123,10,0.22); }

/* ---------- Buttons ---------- */
button, .btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 16px; border-radius: 6px; border: 1px solid var(--primary);
    background: var(--primary); color: #ffffff;
    font-family: var(--font-body); font-size: 13px; font-weight: 500;
    letter-spacing: 0.01em;
    cursor: pointer; text-decoration: none;
    transition: all 0.15s ease;
    line-height: 1.3;
}
button:hover, .btn:hover {
    background: var(--primary-hover); border-color: var(--primary-hover);
    text-decoration: none; color: #ffffff;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}
button.btn-secondary, .btn-secondary {
    background: var(--card); color: var(--ink);
    border: 1px solid var(--hairline-strong);
}
button.btn-secondary:hover, .btn-secondary:hover {
    background: var(--surface); color: var(--ink);
    border-color: var(--ink);
}
button.btn-danger, .btn-danger {
    background: var(--danger); border-color: var(--danger);
}
button.btn-danger:hover { background: #8E2620; border-color: #8E2620; }
button.btn-success, .btn-success {
    background: var(--success); border-color: var(--success); color: #ffffff;
}
button.btn-success:hover { background: #246E48; border-color: #246E48; color: #ffffff; }

/* ---------- Forms ---------- */
.form-row { margin-bottom: 14px; }
.form-row label {
    display: block; margin-bottom: 6px;
    font-size: 11px; color: var(--muted); font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.06em;
}
.form-row input, .form-row select, .form-row textarea {
    width: 100%; padding: 10px 12px;
    background: var(--card); color: var(--ink);
    border: 1px solid var(--hairline-strong); border-radius: 6px;
    font-size: 14px; font-family: inherit;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.form-row input:focus, .form-row select:focus, .form-row textarea:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-soft);
}

.filters { display: flex; gap: 14px; flex-wrap: wrap; align-items: end; margin-bottom: 0; }
.filters .form-row { margin-bottom: 0; min-width: 160px; }

/* ---------- Detail grid ---------- */
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
.detail-grid dt {
    color: var(--muted); font-size: 11px; text-transform: uppercase;
    letter-spacing: 0.06em; font-weight: 600;
    margin-bottom: 4px; margin-top: 14px;
}
.detail-grid dt:first-child { margin-top: 0; }
.detail-grid dd { margin: 0; font-size: 14px; color: var(--ink); }

.actions-bar { display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0; }

/* ---------- Flash ---------- */
.flash {
    padding: 14px 18px; border-radius: 8px; margin-bottom: 18px;
    font-size: 13px; border: 1px solid transparent;
}
.flash-success {
    background: var(--success-soft); color: var(--success);
    border-color: rgba(47,143,94,0.25);
}
.flash-error {
    background: var(--danger-soft); color: var(--danger);
    border-color: rgba(176,51,43,0.25);
}

/* ---------- Misc ---------- */
.muted { color: var(--muted); }
.mono {
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
    font-size: 12px;
}

.pagination { display: flex; gap: 6px; margin-top: 24px; justify-content: center; }
.pagination a, .pagination span {
    padding: 7px 12px; border-radius: 6px;
    background: var(--card); border: 1px solid var(--hairline);
    color: var(--ink); font-size: 12px; min-width: 36px; text-align: center;
    text-decoration: none; transition: all 0.15s;
}
.pagination a:hover { border-color: var(--primary); color: var(--primary); text-decoration: none; }
.pagination .current {
    background: var(--primary); color: #ffffff; border-color: var(--primary);
}

@media (max-width: 720px) {
    .detail-grid { grid-template-columns: 1fr; gap: 16px; }
    main { padding: 20px 16px 48px; }
    header.topbar { padding: 12px 16px; flex-wrap: wrap; }
    header.topbar .user { display: none; }
    table { font-size: 12px; }
    th, td { padding: 10px 8px; }
    h1 { font-size: 24px; }
}
</style>
</head>
<body>
<header class="topbar">
    <div class="brand">IPEC<span class="dot"></span><em>Admin</em></div>
    <nav>
        <a href="index.php">Candidatures</a>
        <a href="logout.php">Déconnexion</a>
        <span class="user"><?= $h(admin_current_user()) ?></span>
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
