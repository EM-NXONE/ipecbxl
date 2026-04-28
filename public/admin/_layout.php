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
<html lang="fr" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $h($title) ?> — Admin IPEC</title>
<script>
(function(){try{var t=localStorage.getItem('ipec-admin-theme');if(!t){t=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/site-style.css?v=2">
<style>
/* Overrides spécifiques admin (responsive detail-grid) */
@media (max-width: 720px) {
    .detail-grid { grid-template-columns: 1fr; gap: 16px; }
}
</style>
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">
            <?= admin_logo_svg('logo-mark') ?>
            <div class="brand-text">
                <div class="brand-name">IPEC</div>
                <div class="brand-sub">
                    Institut Privé des Études Commerciales
                    <span class="sep">·</span>Admin
                </div>
            </div>
        </a>
        <nav>
            <?php
            $cur = basename($_SERVER['PHP_SELF'] ?? '');
            $navItems = [
                'index.php'  => 'Candidatures',
                'logout.php' => 'Déconnexion',
            ];
            foreach ($navItems as $href => $label) {
                $isActive = ($cur === $href) || ($href === 'index.php' && $cur === 'detail.php');
                echo '<a href="' . $h($href) . '"' . ($isActive ? ' class="active"' : '') . '>' . $h($label) . '</a>';
            }
            ?>
            <button type="button" class="theme-toggle" id="ipecThemeToggle" aria-label="Basculer le thème" title="Basculer clair / sombre">
                <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </button>
            <?php $u = admin_current_user(); if ($u !== ''): ?>
                <span class="user-pill">
                    <span class="avatar"><?= $h(mb_substr($u, 0, 1)) ?></span>
                    <?= $h($u) ?>
                </span>
            <?php endif; ?>
        </nav>
    </div>
</header>
<script>
(function(){var b=document.getElementById('ipecThemeToggle');if(!b)return;b.addEventListener('click',function(){var c=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',c);try{localStorage.setItem('ipec-admin-theme',c);}catch(e){}});})();
</script>
<main>
<?php
}

function admin_layout_end(): void {
    ?>
</main>
<footer class="site-footer">
    <div class="footer-inner">
        <span class="footer-brand">
            <?= admin_logo_svg('', 18) ?> IPEC
        </span>
        <span>© <?= date('Y') ?> IPEC — Institut Privé des Études Commerciales ASBL · Espace Admin</span>
    </div>
</footer>
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
