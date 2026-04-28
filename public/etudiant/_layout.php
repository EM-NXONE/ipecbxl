<?php
/**
 * IPEC — Espace étudiant : layout commun (header / sidebar / footer)
 * Charte alignée sur le site (paper warm, deep ink, blue accent).
 */

if (!function_exists('etu_layout_start')) {

function etu_logo_svg(string $class = '', int $size = 36): string {
    // Réutilise le helper admin si dispo, sinon SVG inline minimal (monogramme IPEC)
    if (function_exists('admin_logo_svg')) {
        return admin_logo_svg($class, $size);
    }
    $cls = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '';
    $s = (int)$size;
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="' . $s . '" height="' . $s . '"' . $cls . ' aria-hidden="true">'
         . '<rect width="64" height="64" rx="8" fill="currentColor" fill-opacity="0.08"/>'
         . '<text x="32" y="40" text-anchor="middle" font-family="Georgia,serif" font-weight="600" font-size="22" fill="currentColor">IPEC</text>'
         . '</svg>';
}

function etu_layout_start(string $title, ?array $user = null): void {
    $h = 'etu_h';
    $current = $_SERVER['REQUEST_URI'] ?? '';
    $isActive = function (string $href) use ($current) {
        $path = parse_url($current, PHP_URL_PATH) ?? '';
        return str_ends_with($path, $href) ? ' aria-current="page"' : '';
    };
    ?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $h($title) ?> — Espace étudiant IPEC</title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<script>
(function(){try{var t=localStorage.getItem('ipec-etu-theme');if(!t){t=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root, html[data-theme="light"] {
    --bg:        #FBFAF7;
    --surface:   #F4F2EC;
    --card:      #FFFFFF;
    --ink:       #1B1F2A;
    --muted:     #5C6373;
    --hairline:  rgba(27, 31, 42, 0.10);
    --hairline-strong: rgba(27, 31, 42, 0.16);
    --primary:   #1F3D8A;
    --primary-hover: #16306E;
    --primary-soft:  rgba(31, 61, 138, 0.08);
    --primary-on:    #ffffff;
    --success:   #2F8F5E;
    --success-soft: rgba(47, 143, 94, 0.10);
    --amber:     #B07B0A;
    --amber-soft: rgba(176, 123, 10, 0.12);
    --danger:    #B0332B;
    --danger-soft: rgba(176, 51, 43, 0.10);
    --font-display: 'Fraunces', Georgia, serif;
    --font-body: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
}
html[data-theme="dark"] {
    --bg:        #0E1117;
    --surface:   #161B24;
    --card:      #1A2030;
    --ink:       #ECEEF3;
    --muted:     #9099AC;
    --hairline:  rgba(236, 238, 243, 0.10);
    --hairline-strong: rgba(236, 238, 243, 0.18);
    --primary:   #6E8FD9;
    --primary-hover: #88A4E2;
    --primary-soft:  rgba(110, 143, 217, 0.14);
    --primary-on:    #0E1117;
    --success:   #4FBF87;
    --success-soft: rgba(79, 191, 135, 0.14);
    --amber:     #E0B458;
    --amber-soft: rgba(224, 180, 88, 0.16);
    --danger:    #E07268;
    --danger-soft: rgba(224, 114, 104, 0.14);
}

* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    font-family: var(--font-body);
    color: var(--ink);
    background: var(--bg);
    font-size: 15px;
    line-height: 1.55;
    -webkit-font-smoothing: antialiased;
}
h1, h2, h3 { font-family: var(--font-display); font-weight: 500; letter-spacing: -0.01em; color: var(--ink); }
h1 { font-size: 28px; margin: 0 0 4px; }
h2 { font-size: 19px; margin: 0 0 12px; }
h3 { font-size: 16px; margin: 0 0 8px; font-family: var(--font-body); font-weight: 600; }
a { color: var(--primary); text-decoration: none; }
a:hover { text-decoration: underline; }
p { margin: 0 0 12px; }
.muted { color: var(--muted); }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; }

/* Topbar */
.topbar {
    position: sticky; top: 0; z-index: 50;
    background: color-mix(in srgb, var(--bg) 88%, transparent);
    backdrop-filter: saturate(180%) blur(12px);
    -webkit-backdrop-filter: saturate(180%) blur(12px);
    border-bottom: 1px solid var(--hairline);
}
.topbar-inner {
    max-width: 1280px; margin: 0 auto; padding: 0 24px;
    height: 72px; display: flex; align-items: center; gap: 24px;
}
.brand { display: flex; align-items: center; gap: 12px; color: var(--ink); }
.brand:hover { text-decoration: none; }
.brand .logo-mark { color: var(--primary); }
.brand-text { line-height: 1.1; }
.brand-name { font-family: var(--font-display); font-size: 18px; font-weight: 500; }
.brand-sub { font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--muted); margin-top: 2px; }
.brand-sub .sep { margin: 0 6px; opacity: 0.5; }

.topbar-spacer { flex: 1; }
.topbar-actions { display: flex; align-items: center; gap: 12px; }
.theme-toggle, .icon-btn {
    background: transparent; border: 1px solid var(--hairline-strong);
    color: var(--ink); width: 36px; height: 36px; border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .15s, border-color .15s;
}
.theme-toggle:hover, .icon-btn:hover { background: var(--primary-soft); border-color: var(--primary); }
.user-pill {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 4px 14px 4px 4px; border-radius: 999px; border: 1px solid var(--hairline-strong);
    color: var(--ink); font-weight: 500; font-size: 13px;
}
.user-pill:hover { text-decoration: none; background: var(--primary-soft); border-color: var(--primary); }
.user-avatar {
    width: 28px; height: 28px; border-radius: 999px;
    background: var(--primary); color: var(--primary-on);
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 600; font-size: 12px;
}

/* Layout grille */
.shell { max-width: 1280px; margin: 0 auto; padding: 32px 24px 64px; display: grid; grid-template-columns: 240px 1fr; gap: 32px; }
@media (max-width: 880px) { .shell { grid-template-columns: 1fr; gap: 16px; } }

aside.sidebar { position: sticky; top: 96px; align-self: start; }
.side-nav { display: flex; flex-direction: column; gap: 4px; }
.side-nav a {
    display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    border-radius: 10px; color: var(--ink); font-weight: 500; font-size: 14px;
    border: 1px solid transparent;
}
.side-nav a:hover { background: var(--primary-soft); text-decoration: none; }
.side-nav a[aria-current="page"] {
    background: var(--primary-soft); color: var(--primary);
    border-color: color-mix(in srgb, var(--primary) 25%, transparent);
}
.side-nav .ico { width: 18px; opacity: 0.8; display: inline-flex; }

/* Cards */
.card {
    background: var(--card); border: 1px solid var(--hairline);
    border-radius: 14px; padding: 24px; margin-bottom: 20px;
    box-shadow: 0 1px 0 rgba(0,0,0,0.02);
}
.card h2 { margin-top: 0; }

.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.kpi {
    background: var(--card); border: 1px solid var(--hairline);
    border-radius: 14px; padding: 20px;
}
.kpi-label { color: var(--muted); font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 8px; }
.kpi-value { font-family: var(--font-display); font-size: 28px; font-weight: 500; }
.kpi-sub { font-size: 12px; color: var(--muted); margin-top: 4px; }

/* Tables */
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--hairline); font-size: 14px; vertical-align: middle; }
th { font-weight: 600; color: var(--muted); font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; background: var(--surface); }
tbody tr:hover { background: color-mix(in srgb, var(--surface) 60%, transparent); }
.empty { padding: 32px; text-align: center; color: var(--muted); }

/* Badges */
.badge {
    display: inline-block; padding: 3px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 600; letter-spacing: 0.04em;
    background: var(--primary-soft); color: var(--primary);
    border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
}
.badge-success { background: var(--success-soft); color: var(--success); border-color: color-mix(in srgb, var(--success) 25%, transparent); }
.badge-warning { background: var(--amber-soft); color: var(--amber); border-color: color-mix(in srgb, var(--amber) 25%, transparent); }
.badge-danger  { background: var(--danger-soft); color: var(--danger); border-color: color-mix(in srgb, var(--danger) 25%, transparent); }
.badge-muted   { background: var(--surface); color: var(--muted); border-color: var(--hairline-strong); }

/* Forms */
.form-row { margin-bottom: 16px; }
.form-row label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--ink); }
input[type=text], input[type=email], input[type=password], textarea, select {
    width: 100%; padding: 10px 12px; font-size: 14px; font-family: inherit;
    background: var(--bg); color: var(--ink);
    border: 1px solid var(--hairline-strong); border-radius: 10px;
    transition: border-color .15s, box-shadow .15s;
}
input:focus, textarea:focus, select:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-soft);
}
.help { font-size: 12px; color: var(--muted); margin-top: 6px; }

/* Buttons */
button, .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 18px; font-size: 14px; font-weight: 600; font-family: inherit;
    border: 1px solid var(--primary); background: var(--primary); color: var(--primary-on);
    border-radius: 10px; cursor: pointer; transition: background .15s, transform .05s;
    text-decoration: none;
}
button:hover, .btn:hover { background: var(--primary-hover); border-color: var(--primary-hover); text-decoration: none; }
button:active, .btn:active { transform: translateY(1px); }
.btn-secondary {
    background: transparent; color: var(--ink); border-color: var(--hairline-strong);
}
.btn-secondary:hover { background: var(--primary-soft); color: var(--primary); border-color: var(--primary); }
.btn-ghost { background: transparent; color: var(--primary); border-color: transparent; padding: 6px 10px; }
.btn-ghost:hover { background: var(--primary-soft); }

/* Flash */
.flash {
    padding: 14px 18px; border-radius: 10px; margin-bottom: 20px;
    border: 1px solid; font-size: 14px;
}
.flash-success { background: var(--success-soft); color: var(--success); border-color: color-mix(in srgb, var(--success) 25%, transparent); }
.flash-error   { background: var(--danger-soft);  color: var(--danger);  border-color: color-mix(in srgb, var(--danger)  25%, transparent); }
.flash-info    { background: var(--primary-soft); color: var(--primary); border-color: color-mix(in srgb, var(--primary) 25%, transparent); }

/* Auth (centered) */
.auth-shell {
    min-height: calc(100vh - 72px);
    display: flex; align-items: center; justify-content: center;
    padding: 32px 16px;
}
.auth-card {
    background: var(--card); border: 1px solid var(--hairline);
    border-radius: 16px; padding: 36px; width: 100%; max-width: 440px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.04);
}
.auth-card h1 { font-size: 26px; margin-bottom: 4px; }
.auth-card .lede { color: var(--muted); margin-bottom: 24px; font-size: 14px; }

footer.etu-footer {
    border-top: 1px solid var(--hairline); margin-top: 64px; padding: 24px;
    text-align: center; color: var(--muted); font-size: 12px;
}
</style>
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a href="<?= etu_url('/index.php') ?>" class="brand">
            <?= etu_logo_svg('logo-mark') ?>
            <div class="brand-text">
                <div class="brand-name">IPEC</div>
                <div class="brand-sub">Espace étudiant <span class="sep">·</span> Mon dossier</div>
            </div>
        </a>
        <div class="topbar-spacer"></div>
        <div class="topbar-actions">
            <button type="button" class="theme-toggle" id="etuThemeToggle" aria-label="Changer de thème" title="Changer de thème">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>
            <?php if ($user): ?>
                <a class="user-pill" href="<?= etu_url('/profil.php') ?>" title="Mon profil">
                    <span class="user-avatar"><?= $h(mb_strtoupper(mb_substr($user['prenom'] ?: $user['email'], 0, 1))) ?></span>
                    <span><?= $h($user['prenom']) ?></span>
                </a>
                <a class="btn-secondary btn" href="<?= etu_url('/logout.php') ?>">Déconnexion</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($user): ?>
<div class="shell">
    <aside class="sidebar">
        <nav class="side-nav">
            <a href="<?= etu_url('/index.php') ?>"<?= $isActive('/index.php') ?>>
                <span class="ico">▤</span> Tableau de bord
            </a>
            <a href="<?= etu_url('/factures.php') ?>"<?= $isActive('/factures.php') ?>>
                <span class="ico">€</span> Factures
            </a>
            <a href="<?= etu_url('/documents.php') ?>"<?= $isActive('/documents.php') ?>>
                <span class="ico">▣</span> Documents
            </a>
            <a href="<?= etu_url('/profil.php') ?>"<?= $isActive('/profil.php') ?>>
                <span class="ico">●</span> Mon profil
            </a>
        </nav>
    </aside>
    <main>
<?php else: ?>
<main class="auth-shell">
<?php endif;

    // Affiche le flash s'il existe
    $f = etu_take_flash();
    if ($f) {
        echo '<div class="flash flash-' . etu_h($f['type']) . '">' . etu_h($f['msg']) . '</div>';
    }
}

function etu_layout_end(?array $user = null): void {
    if ($user): ?>
    </main>
</div>
    <?php else: ?>
</main>
    <?php endif; ?>
<footer class="etu-footer">
    © <?= date('Y') ?> IPEC — Institut Privé des Études Commerciales · Espace étudiant sécurisé
</footer>
<script>
(function(){
    var btn = document.getElementById('etuThemeToggle');
    if (!btn) return;
    btn.addEventListener('click', function(){
        var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        var next = cur === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        try { localStorage.setItem('ipec-etu-theme', next); } catch(e){}
    });
})();
</script>
</body>
</html>
<?php
}

} // !function_exists
