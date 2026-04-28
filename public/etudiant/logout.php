<?php
/**
 * IPEC — Espace étudiant : déconnexion
 */
require_once __DIR__ . '/_bootstrap.php';

if ($u = etu_current()) {
    etu_log_action($u['id'], 'logout');
}
etu_session_destroy();
$_SESSION = [];
session_destroy();

header('Location: /etudiant/login.php');
exit;
