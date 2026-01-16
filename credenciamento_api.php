<?php
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

// Checar autenticação simples
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($method === 'GET' && ($action === null || $action === 'list')) {
    $rows = buscarCredenciamentosUF(); // assoc uf => row
    echo json_encode($rows);
    exit;
}

if ($method === 'POST' && ($action === 'update' || $action === null)) {
    // aceitar application/x-www-form-urlencoded or form-data
    $uf = strtoupper(trim($_POST['uf'] ?? ''));
    $renov = trim($_POST['renovacao'] ?? '');
    $venc = trim($_POST['vencimento'] ?? '');

    if (!preg_match('/^[A-Z]{2}$/', $uf) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $renov) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $venc)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_input']);
        exit;
    }

    $ok = atualizarCredenciamentoUF($uf, $renov, $venc);
    if ($ok) {
        $row = buscarCredenciamentoPorUF($uf);
        echo json_encode(['ok' => true, 'row' => $row]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'update_failed']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'bad_request']);
