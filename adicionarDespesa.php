<?php
// carregar funções antes do header (evita problemas de include/redirect)
require_once __DIR__ . '/includes/functions.php';
include "includes/header.php";
?>

<link rel="stylesheet" href="css/adicionarDespesa.css">

<?php
// tópicos fixos (mesma ordem de despesas.php)
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe e sanitiza dados do formulário
    $descricao = trim(filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING));
    $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
    $data_referencia = trim($_POST['data_referencia'] ?? '');
    $data_vencimento = trim($_POST['data_vencimento'] ?? '');
    $tipo = trim(filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING));
    $situacao = trim(filter_input(INPUT_POST, 'situacao', FILTER_SANITIZE_STRING));
    $observacao = trim(filter_input(INPUT_POST, 'observacao', FILTER_SANITIZE_STRING));

    if (inserirDespesa($descricao, $valor, $data_referencia, $data_vencimento, $tipo, $situacao, $observacao)) {
        $alert = '<div class="alert alert-success" role="alert">
                    Despesa adicionada com sucesso!
                  </div>';
    } else {
        $alert = '<div class="alert alert-danger" role="alert">
                    Erro ao adicionar despesa. Tente novamente.
                  </div>';
    }
}

// Debug: checar se a função está disponível
if (!function_exists('inserirDespesa')) {
    $alert .= '<div class="alert alert-danger" role="alert">Função <strong>inserirDespesa</strong> não encontrada. Verifique <code>includes/functions.php</code>.</div>';
}
?>

<main>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Adicionar Despesa</h2>
            <a href="despesas.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>

        <?= $alert ?>

        <form method="post" class="row g-3">
            <div class="col-12">
                <label for="descricao" class="form-label">Descrição</label>
                <input type="text" name="descricao" class="form-control" id="descricao" required>
            </div>

            <div class="col-12 col-md-4">
                <label for="valor" class="form-label">Valor</label>
                <input type="number" name="valor" step="0.01" class="form-control" id="valor" required>
            </div>

            <div class="col-12 col-md-4">
                <label for="data_referencia" class="form-label">Data de Referência</label>
                <input type="month" name="data_referencia" class="form-control" id="data_referencia" required>
            </div>

            <div class="col-12 col-md-4">
                <label for="data_vencimento" class="form-label">Data de Vencimento</label>
                <input type="date" name="data_vencimento" class="form-control" id="data_vencimento" required>
            </div>

            <div class="col-12 col-md-6">
                <label for="tipo" class="form-label">Tipo de Despesa</label>
                <select name="tipo" id="tipo" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($topics as $t) : ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label for="situacao" class="form-label">Situação</label>
                <select name="situacao" id="situacao" class="form-select" required>
                    <option value="Aguardando Pagamento">Aguardando Pagamento</option>
                    <option value="Pago">Pago</option>
                </select>
            </div>

            <div class="col-12">
                <label for="observacao" class="form-label">Observação</label>
                <textarea name="observacao" id="observacao" class="form-control" rows="3"></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Adicionar Despesa</button>
                <a href="despesas.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </div>
        </form>
    </div>
</main>

</body>
</html>