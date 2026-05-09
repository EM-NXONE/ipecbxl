<?php
/**
 * IPEC — Inspecteur BDD temporaire (à supprimer après usage).
 *
 * Usage :
 *   GET /_inspect.php?token=IPEC_INSPECT_2026&action=tables
 *   GET /_inspect.php?token=...&action=schema&table=etudiants
 *   GET /_inspect.php?token=...&action=count&table=candidatures
 *   GET /_inspect.php?token=...&action=sample&table=etudiants&limit=5
 *   GET /_inspect.php?token=...&action=query&sql=SELECT...   (SELECT only)
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const INSPECT_TOKEN = 'IPEC_INSPECT_2026_ChangeMe';

if (($_GET['token'] ?? '') !== INSPECT_TOKEN) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

require __DIR__ . '/db_config.php';

try {
    $pdo = db();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_connect_failed', 'message' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? 'tables';
$table  = $_GET['table'] ?? '';
$limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));

function safeTable(string $t): string {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $t)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_table']);
        exit;
    }
    return $t;
}

try {
    switch ($action) {
        case 'tables': {
            $rows = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['tables' => $rows], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        }
        case 'schema': {
            $t = safeTable($table);
            $cols = $pdo->query("SHOW FULL COLUMNS FROM `$t`")->fetchAll();
            $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch();
            echo json_encode(['columns' => $cols, 'create' => $create], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        }
        case 'count': {
            $t = safeTable($table);
            $n = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo json_encode(['table' => $t, 'count' => $n]);
            break;
        }
        case 'sample': {
            $t = safeTable($table);
            $rows = $pdo->query("SELECT * FROM `$t` ORDER BY 1 DESC LIMIT $limit")->fetchAll();
            echo json_encode(['table' => $t, 'rows' => $rows], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        }
        case 'query': {
            $sql = trim((string)($_GET['sql'] ?? ''));
            if (!preg_match('/^\s*SELECT\s/i', $sql)) {
                http_response_code(400);
                echo json_encode(['error' => 'only_SELECT_allowed']);
                exit;
            }
            if (preg_match('/;\s*\S/', $sql)) {
                http_response_code(400);
                echo json_encode(['error' => 'no_multi_statement']);
                exit;
            }
            $rows = $pdo->query($sql)->fetchAll();
            echo json_encode(['rows' => $rows, 'count' => count($rows)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        }
        default:
            http_response_code(400);
            echo json_encode(['error' => 'unknown_action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'query_failed', 'message' => $e->getMessage()]);
}
