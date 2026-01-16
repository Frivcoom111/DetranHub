<?php
// editarDespesa.php - editar uma despesa existente
require_once __DIR__ . '/includes/functions.php';
include __DIR__ . '/includes/header.php';

$topics = [
    'Detran',
    'Registradora',
    'B3',
    'FENASEG',
    'Credenciamento',
    'Correios',
    'Custas ADM',
    'Outros'
];

$alert = '';

// obter id (GET para abrir form, POST para salvar)
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($id <= 0) {
    header('Location: despesas.php');
    exit;
}

// buscar registro
$desp = buscarDespesaPorId($id);
if (!$desp) {
    echo "<div class=\"container py-4\"><div class=\"alert alert-danger\">Despesa não encontrada.</div></div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // receber dados
    $descricao = trim(filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING));
    $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
    $data_referencia = trim($_POST['data_referencia'] ?? ''); // expect YYYY-MM from input type=month
    $data_vencimento = trim($_POST['data_vencimento'] ?? '');
    $tipo = trim(filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING));
    $situacao = trim(filter_input(INPUT_POST, 'situacao', FILTER_SANITIZE_STRING));
    $observacao = trim(filter_input(INPUT_POST, 'observacao', FILTER_SANITIZE_STRING));

    $ok = atualizarDespesa($id, $descricao, $valor, $data_referencia, $data_vencimento, $tipo, $situacao, $observacao);
    if ($ok) {
        // redirecionar para despesas no mês selecionado
        $qs = $data_referencia ? '?month=' . urlencode($data_referencia) : '';
        header('Location: despesas.php' . $qs);
        exit;
    } else {
        $alert = '<div class="alert alert-danger">Erro ao atualizar a despesa. Verifique os dados e tente novamente.</div>';
        // re-fetch to show current values in form in case of failure
        $desp = buscarDespesaPorId($id);
    }
}

// preparar valores para o form
$form_descricao = htmlspecialchars($desp['descricao'] ?? '');
$form_valor = isset($desp['valor']) ? number_format((float)$desp['valor'], 2, '.', '') : '';
$form_data_referencia = '';
if (!empty($desp['data_referencia'])) {
    // convert YYYY-MM-DD to YYYY-MM (month input value)
    $form_data_referencia = date('Y-m', strtotime($desp['data_referencia']));
}
$form_data_vencimento = !empty($desp['data_vencimento']) ? date('Y-m-d', strtotime($desp['data_vencimento'])) : '';
$form_tipo = $desp['tipo'] ?? '';
$form_situacao = $desp['situacao'] ?? '';
$form_observacao = htmlspecialchars($desp['observacao'] ?? '');

?>

<link rel="stylesheet" href="css/adicionarDespesa.css">

<main>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Editar Despesa</h2>
            <a href="despesas.php?month=<?= urlencode($form_data_referencia) ?>" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>

        <?= $alert ?>

        <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="col-12">
                <label for="descricao" class="form-label">Descrição</label>
                <input type="text" name="descricao" class="form-control" id="descricao" required value="<?= $form_descricao ?>">
            </div>

            <div class="col-12 col-md-4">
                <label for="valor" class="form-label">Valor</label>
                <input type="number" name="valor" step="0.01" class="form-control" id="valor" required value="<?= $form_valor ?>">
            </div>

            <div class="col-12 col-md-4">
                <label for="data_referencia" class="form-label">Data de Referência</label>
                <input type="month" name="data_referencia" class="form-control" id="data_referencia" required value="<?= $form_data_referencia ?>">
            </div>

            <div class="col-12 col-md-4">
                <label for="data_vencimento" class="form-label">Data de Vencimento</label>
                <input type="date" name="data_vencimento" class="form-control" id="data_vencimento" required value="<?= $form_data_vencimento ?>">
            </div>

            <div class="col-12 col-md-6">
                <label for="tipo" class="form-label">Tipo de Despesa</label>
                <select name="tipo" id="tipo" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($topics as $t) : ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $t === $form_tipo ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label for="situacao" class="form-label">Situação</label>
                <select name="situacao" id="situacao" class="form-select" required>
                    <option value="Aguardando Pagamento" <?= $form_situacao === 'Aguardando Pagamento' ? 'selected' : '' ?>>Aguardando Pagamento</option>
                    <option value="Pago" <?= $form_situacao === 'Pago' ? 'selected' : '' ?>>Pago</option>
                </select>
            </div>

            <div class="col-12">
                <label for="observacao" class="form-label">Observação</label>
                <textarea name="observacao" id="observacao" class="form-control" rows="3"><?= $form_observacao ?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                <a href="despesas.php?month=<?= urlencode($form_data_referencia) ?>" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </div>
        </form>
    </div>
</main>

</html>
