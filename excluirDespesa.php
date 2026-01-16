<?php
// excluirDespesa.php - recebe POST { id, month } e exclui a despesa chamando includes/functions.php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: despesas.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$month = isset($_POST['month']) ? trim($_POST['month']) : '';

if ($id <= 0) {
    header('Location: despesas.php' . ($month ? '?month=' . urlencode($month) : ''));
    exit;
}

$ok = deletarDespesa($id);
// opcional: você pode passar um flag de sucesso via query string
$qs = $month ? '?month=' . urlencode($month) : '';
if ($ok) {
    header('Location: despesas.php' . $qs);
    exit;
} else {
    // em caso de falha, redireciona com erro simples (pode melhorar)
    header('Location: despesas.php' . $qs . ($qs ? '&' : '?') . 'error=delete');
    exit;
}
