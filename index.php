<?php
include "includes/header.php";
require_once 'includes/functions.php';

// mês selecionado para dashboard (GET ?month=YYYY-MM)
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// dados do dashboard
$totalMes = somaDespesasPorMes($selectedMonth);
$totalPendente = somaDespesasPorMes($selectedMonth, 'Aguardando Pagamento');
$maiorDesp = maioresDespesasMes($selectedMonth, 1);
$maiorDesp = !empty($maiorDesp) ? $maiorDesp[0] : null;
$porTipo = totaisPorTipoMes($selectedMonth);
$proxVenc = proxVencimentos(5);
$ultimas = ultimasDespesasMes($selectedMonth, 5);
?>

<link rel="stylesheet" href="css/index.css">

<body>
    <main>
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Dashboard</h2>
                <form method="get" class="d-flex align-items-center">
                    <label for="month" class="me-2 visually-hidden">Mês</label>
                    <input type="month" id="month" name="month" class="form-control form-control-sm me-2" value="<?= htmlspecialchars($selectedMonth) ?>">
                    <button class="btn btn-sm btn-primary">Aplicar</button>
                </form>
            </div>

            <div class="row g-3 mb-4">
                <!-- Total Mês -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm card-dashboard border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3 icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">R$</div>
                                <div>
                                    <h6 class="mb-1">Despesas do Mês</h6>
                                    <h4 class="mb-0 text-primary">R$ <?= number_format($totalMes, 2, ',', '.') ?></h4>
                                    <small class="text-muted"><?= date('F Y', strtotime($selectedMonth . '-01')) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pendente -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm card-dashboard border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3 icon bg-warning text-white rounded-circle d-flex align-items-center justify-content-center">!</div>
                                <div>
                                    <h6 class="mb-1">Pendente</h6>
                                    <h4 class="mb-0 text-warning">R$ <?= number_format($totalPendente, 2, ',', '.') ?></h4>
                                    <small class="text-muted">Em aberto no mês</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maior Despesa -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm card-dashboard border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3 icon bg-danger text-white rounded-circle d-flex align-items-center justify-content-center">$</div>
                                <div>
                                    <h6 class="mb-1">Maior Despesa</h6>
                                    <?php if ($maiorDesp): ?>
                                        <h5 class="mb-0 text-danger">R$ <?= number_format($maiorDesp['valor'], 2, ',', '.') ?></h5>
                                        <small class="text-muted"><?= htmlspecialchars($maiorDesp['descricao']) ?></small>
                                    <?php else: ?>
                                        <h5 class="mb-0 text-muted">Nenhuma</h5>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Despesas por Tipo</h6>
                            <small class="text-muted">Total do mês</small>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTipos" height="160"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Próximos Vencimentos</h6>
                            <small class="text-muted">Próximos 5</small>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Descrição</th>
                                            <th>Valor</th>
                                            <th>Venc.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($proxVenc)): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-3">Sem próximos vencimentos</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($proxVenc as $p): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($p['descricao']) ?></td>
                                                    <td>R$ <?= number_format($p['valor'],2,',','.') ?></td>
                                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['data_vencimento']))) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Últimas Despesas</h6>
                            <small class="text-muted">Últimas 5</small>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Descrição</th>
                                            <th>Valor</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($ultimas)): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-3">Sem registros</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($ultimas as $u): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($u['descricao']) ?></td>
                                                    <td>R$ <?= number_format($u['valor'],2,',','.') ?></td>
                                                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="editarDespesa.php?id=<?= urlencode($u['id']) ?>">Editar</a></td>
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

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // preparar dados para o gráfico
        const tipos = <?= json_encode(array_keys($porTipo), JSON_HEX_TAG) ?>;
        const valores = <?= json_encode(array_values($porTipo), JSON_HEX_TAG) ?>;

        const ctx = document.getElementById('chartTipos');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: tipos,
                    datasets: [{
                        data: valores,
                        backgroundColor: ['#0d6efd','#198754','#dc3545','#fd7e14','#6f42c1','#0dcaf0','#adb5bd','#ffc107'],
                        borderWidth: 0
                    }]
                },
                options: {
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    </script>
</body>

</html>