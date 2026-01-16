<?php
include 'includes/header.php';
require_once 'includes/functions.php';

$statusLabels = [
    'EM_ABERTO' => 'Em aberto',
    'FINALIZADO' => 'Finalizado'
];

$feedback = ['success' => null, 'error' => null];
$formData = [
    'descricao' => '',
    'grupo' => '',
    'cota' => '',
    'data_processo' => date('Y-m-d'),
    'situacao' => '',
    'valor_custas' => '',
    'status' => 'EM_ABERTO'
];

if (!function_exists('validarProcessoAdminInput')) {
    function validarProcessoAdminInput(array $dados, array $statusLabels): array {
        $erros = [];
        $obrigatorios = ['descricao', 'grupo', 'cota', 'data_processo', 'situacao'];
        foreach ($obrigatorios as $campo) {
            if (trim($dados[$campo] ?? '') === '') {
                $erros[] = 'O campo "' . ucfirst(str_replace('_', ' ', $campo)) . '" é obrigatório.';
            }
        }
        if (!empty($dados['data_processo']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data_processo'])) {
            $erros[] = 'Informe uma data de processo válida (YYYY-MM-DD).';
        }
        $status = strtoupper($dados['status'] ?? '');
        if (!array_key_exists($status, $statusLabels)) {
            $erros[] = 'Status informado é inválido.';
        }
        return $erros;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $formData = [
            'descricao' => trim($_POST['descricao'] ?? ''),
            'grupo' => trim($_POST['grupo'] ?? ''),
            'cota' => trim($_POST['cota'] ?? ''),
            'data_processo' => trim($_POST['data_processo'] ?? ''),
            'situacao' => trim($_POST['situacao'] ?? ''),
            'valor_custas' => trim($_POST['valor_custas'] ?? ''),
            'status' => strtoupper(trim($_POST['status'] ?? 'EM_ABERTO'))
        ];

        $erros = validarProcessoAdminInput($formData, $statusLabels);

        if (empty($erros)) {
            $ok = inserirProcessoAdministrativo(
                $formData['descricao'],
                $formData['grupo'],
                $formData['cota'],
                $formData['data_processo'],
                $formData['situacao'],
                $formData['valor_custas'],
                $formData['status']
            );
            if ($ok) {
                $feedback['success'] = 'Processo cadastrado com sucesso.';
                $formData = [
                    'descricao' => '',
                    'grupo' => '',
                    'cota' => '',
                    'data_processo' => date('Y-m-d'),
                    'situacao' => '',
                    'valor_custas' => '',
                    'status' => 'EM_ABERTO'
                ];
            } else {
                $feedback['error'] = 'Não foi possível salvar o processo. ' . (db_last_error() ?? '');
            }
        } else {
            $feedback['error'] = implode(' ', $erros);
        }
    } elseif ($action === 'update') {
        $processoId = isset($_POST['processo_id']) ? (int)$_POST['processo_id'] : 0;
        $dadosAtualizados = [
            'descricao' => trim($_POST['descricao'] ?? ''),
            'grupo' => trim($_POST['grupo'] ?? ''),
            'cota' => trim($_POST['cota'] ?? ''),
            'data_processo' => trim($_POST['data_processo'] ?? ''),
            'situacao' => trim($_POST['situacao'] ?? ''),
            'valor_custas' => trim($_POST['valor_custas'] ?? ''),
            'status' => strtoupper(trim($_POST['status'] ?? 'EM_ABERTO'))
        ];
        $erros = validarProcessoAdminInput($dadosAtualizados, $statusLabels);
        if ($processoId <= 0) {
            $erros[] = 'Processo selecionado é inválido.';
        }

        if (empty($erros)) {
            $ok = atualizarProcessoAdministrativo(
                $processoId,
                $dadosAtualizados['descricao'],
                $dadosAtualizados['grupo'],
                $dadosAtualizados['cota'],
                $dadosAtualizados['data_processo'],
                $dadosAtualizados['situacao'],
                $dadosAtualizados['valor_custas'],
                $dadosAtualizados['status']
            );
            if ($ok) {
                $feedback['success'] = 'Processo atualizado com sucesso.';
            } else {
                $feedback['error'] = 'Não foi possível atualizar o processo. ' . (db_last_error() ?? '');
            }
        } else {
            $feedback['error'] = implode(' ', $erros);
        }
    } elseif ($action === 'update-status') {
        $processoId = isset($_POST['processo_id']) ? (int)$_POST['processo_id'] : 0;
        $novoStatus = strtoupper(trim($_POST['novo_status'] ?? ''));
        if ($processoId > 0 && array_key_exists($novoStatus, $statusLabels)) {
            if (atualizarStatusProcessoAdministrativo($processoId, $novoStatus)) {
                $feedback['success'] = 'Status atualizado com sucesso.';
            } else {
                $feedback['error'] = 'Não foi possível atualizar o status. ' . (db_last_error() ?? '');
            }
        } else {
            $feedback['error'] = 'Dados inválidos para atualização de status.';
        }
    } elseif ($action === 'delete') {
        $processoId = isset($_POST['processo_id']) ? (int)$_POST['processo_id'] : 0;
        if ($processoId > 0) {
            if (deletarProcessoAdministrativo($processoId)) {
                $feedback['success'] = 'Processo removido com sucesso.';
            } else {
                $feedback['error'] = 'Não foi possível excluir o processo. ' . (db_last_error() ?? '');
            }
        } else {
            $feedback['error'] = 'Processo inválido para exclusão.';
        }
    }
}

$processos = listarProcessosAdministrativos();
?>

<link rel="stylesheet" href="css/processoAdministrativo.css">

<main>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Processo Administrativo</h2>
        </div>

        <?php if ($feedback['success']): ?>
            <div class="alert alert-success" role="alert"><?= htmlspecialchars($feedback['success']) ?></div>
        <?php endif; ?>
        <?php if ($feedback['error']): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($feedback['error']) ?></div>
        <?php endif; ?>

        <div class="processo-section mb-4">
            <div class="card shadow-sm processo-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Novo processo</h5>
                    <form method="post" class="processo-form">
                        <input type="hidden" name="action" value="create">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Descrição *</label>
                                <input type="text" name="descricao" class="form-control" value="<?= htmlspecialchars($formData['descricao']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Grupo *</label>
                                <input type="text" name="grupo" class="form-control" value="<?= htmlspecialchars($formData['grupo']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cota *</label>
                                <input type="text" name="cota" class="form-control" value="<?= htmlspecialchars($formData['cota']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data do processo *</label>
                                <input type="date" name="data_processo" class="form-control" value="<?= htmlspecialchars($formData['data_processo']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Situação *</label>
                                <input type="text" name="situacao" class="form-control" value="<?= htmlspecialchars($formData['situacao']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor das custas (R$)</label>
                                <input type="number" step="0.01" min="0" name="valor_custas" class="form-control" value="<?= htmlspecialchars($formData['valor_custas']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach ($statusLabels as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $formData['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">Salvar processo</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const modalElement = document.getElementById('modalEditarProcesso');
                    const formEditarProcesso = document.getElementById('formEditarProcesso');
                    const modalInstance = modalElement && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalElement) : null;

                    function preencherCampo(campoId, valor) {
                        const elemento = document.getElementById(campoId);
                        if (elemento) {
                            elemento.value = valor ?? '';
                        }
                    }

                    function normalizarData(valor) {
                        if (!valor) {
                            return '';
                        }
                        if (/^\d{4}-\d{2}-\d{2}$/.test(valor)) {
                            return valor;
                        }
                        const timestamp = Date.parse(valor);
                        if (Number.isNaN(timestamp)) {
                            return '';
                        }
                        return new Date(timestamp).toISOString().split('T')[0];
                    }

                    window.abrirModalEditarProcesso = function (button) {
                        if (!modalInstance || !formEditarProcesso || !button) {
                            return;
                        }
                        const rawData = button.getAttribute('data-processo');
                        if (!rawData) {
                            return;
                        }
                        try {
                            const processo = JSON.parse(rawData);
                            preencherCampo('editProcessoId', processo.id ?? '');
                            preencherCampo('editDescricao', processo.descricao ?? '');
                            preencherCampo('editGrupo', processo.grupo ?? '');
                            preencherCampo('editCota', processo.cota ?? '');
                            preencherCampo('editSituacao', processo.situacao ?? '');
                            preencherCampo('editValorCustas', processo.valor_custas === null || typeof processo.valor_custas === 'undefined' ? '' : processo.valor_custas);
                            preencherCampo('editStatus', (processo.status || 'EM_ABERTO').toUpperCase());
                            preencherCampo('editDataProcesso', normalizarData(processo.data_processo));
                            modalInstance.show();
                        } catch (error) {
                            console.error('Erro ao abrir modal de edição', error);
                        }
                    };

                    function confirmarExclusao(form) {
                        const descricao = form?.getAttribute('data-descricao') || 'este processo';
                        return window.confirm(`Tem certeza que deseja excluir "${descricao}"? Esta ação não pode ser desfeita.`);
                    }

                    document.querySelectorAll('.processo-delete-form').forEach(function (form) {
                        form.addEventListener('submit', function (event) {
                            if (!confirmarExclusao(form)) {
                                event.preventDefault();
                            }
                        });
                    });
                });
                </script>

                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const modalElement = document.getElementById('modalEditarProcesso');
                    const formEditarProcesso = document.getElementById('formEditarProcesso');
                    const modalInstance = modalElement && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalElement) : null;

                    function preencherCampo(campoId, valor) {
                        const elemento = document.getElementById(campoId);
                        if (elemento) {
                            elemento.value = valor ?? '';
                        }
                    }

                    function normalizarData(valor) {
                        if (!valor) {
                            return '';
                        }
                        if (/^\d{4}-\d{2}-\d{2}$/.test(valor)) {
                            return valor;
                        }
                        const timestamp = Date.parse(valor);
                        if (Number.isNaN(timestamp)) {
                            return '';
                        }
                        return new Date(timestamp).toISOString().split('T')[0];
                    }

                    window.abrirModalEditarProcesso = function (button) {
                        if (!modalInstance || !formEditarProcesso || !button) {
                            return;
                        }
                        const rawData = button.getAttribute('data-processo');
                        if (!rawData) {
                            return;
                        }
                        try {
                            const processo = JSON.parse(rawData);
                            preencherCampo('editProcessoId', processo.id ?? '');
                            preencherCampo('editDescricao', processo.descricao ?? '');
                            preencherCampo('editGrupo', processo.grupo ?? '');
                            preencherCampo('editCota', processo.cota ?? '');
                            preencherCampo('editSituacao', processo.situacao ?? '');
                            preencherCampo('editValorCustas', processo.valor_custas === null || typeof processo.valor_custas === 'undefined' ? '' : processo.valor_custas);
                            preencherCampo('editStatus', (processo.status || 'EM_ABERTO').toUpperCase());
                            preencherCampo('editDataProcesso', normalizarData(processo.data_processo));
                            modalInstance.show();
                        } catch (error) {
                            console.error('Erro ao abrir modal de edição', error);
                        }
                    };
                });
                </script>

        <div class="processo-section">
            <div class="card shadow-sm processo-card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                        <h5 class="card-title mb-0">Processos cadastrados</h5>
                        <span class="text-muted small">Total: <?= count($processos) ?></span>
                    </div>
                    <div class="table-responsive processo-table">
                        <table class="table table-hover align-middle processo-table__table">
                            <thead class="table-light">
                                <tr>
                                    <th>Descrição</th>
                                    <th>Grupo</th>
                                    <th>Cota</th>
                                    <th>Data</th>
                                    <th>Situação</th>
                                    <th>Custas</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($processos)): ?>
                                    <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Nenhum processo cadastrado no momento.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($processos as $proc): ?>
                                        <tr>
                                            <td data-label="Descrição"><?= htmlspecialchars($proc['descricao']) ?></td>
                                            <td data-label="Grupo"><?= htmlspecialchars($proc['grupo']) ?></td>
                                            <td data-label="Cota"><?= htmlspecialchars($proc['cota']) ?></td>
                                            <td data-label="Data"><?= date('d/m/Y', strtotime($proc['data_processo'])) ?></td>
                                            <td data-label="Situação"><?= htmlspecialchars($proc['situacao']) ?></td>
                                            <td data-label="Custas"><span class="fw-semibold">R$ <?= number_format((float)$proc['valor_custas'], 2, ',', '.') ?></span></td>
                                            <td data-label="Status">
                                                <?php if ($proc['status'] === 'FINALIZADO'): ?>
                                                    <span class="badge bg-success">Finalizado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Em aberto</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Ações">
                                                <div class="processo-actions">
                                                    <?php $procJson = htmlspecialchars(json_encode($proc, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                                                    <button type="button" class="btn btn-link p-0 processo-edit-btn" data-processo="<?= $procJson ?>" onclick="abrirModalEditarProcesso(this)">Editar dados</button>
                                                    <form method="post" class="d-flex align-items-center gap-2 processo-status-form">
                                                        <input type="hidden" name="action" value="update-status">
                                                        <input type="hidden" name="processo_id" value="<?= (int)$proc['id'] ?>">
                                                        <select name="novo_status" class="form-select form-select-sm">
                                                            <?php foreach ($statusLabels as $value => $label): ?>
                                                                <option value="<?= $value ?>" <?= $proc['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="btn btn-outline-primary btn-sm flex-shrink-0">Atualizar</button>
                                                    </form>
                                                    <form method="post" class="processo-delete-form" data-descricao="<?= htmlspecialchars($proc['descricao']) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="processo_id" value="<?= (int)$proc['id'] ?>">
                                                        <button type="submit" class="btn btn-link text-danger p-0">Excluir</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal edição completa -->
<div class="modal fade" id="modalEditarProcesso" tabindex="-1" aria-labelledby="modalEditarProcessoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" id="formEditarProcesso">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarProcessoLabel">Editar processo administrativo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="processo_id" id="editProcessoId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Descrição *</label>
                            <input type="text" name="descricao" id="editDescricao" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Grupo *</label>
                            <input type="text" name="grupo" id="editGrupo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cota *</label>
                            <input type="text" name="cota" id="editCota" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data do processo *</label>
                            <input type="date" name="data_processo" id="editDataProcesso" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Situação *</label>
                            <input type="text" name="situacao" id="editSituacao" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor das custas (R$)</label>
                            <input type="number" step="0.01" min="0" name="valor_custas" id="editValorCustas" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="editStatus" class="form-select">
                                <?php foreach ($statusLabels as $value => $label): ?>
                                    <option value="<?= $value ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>