<?php
include 'includes/header.php';
require_once 'includes/functions.php';
?>

<link rel="stylesheet" href="css/despesas.css">

<main>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Despesas</h2>
        </div>

        <!-- Formulário: seleção de mês -->
        <?php
        // mês selecionado via GET (ex: 2025-11)
        $selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

        // gerar últimos 12 meses
        $months = [];
        $now = new DateTime();
        for ($i = 0; $i < 12; $i++) {
            $dt = (clone $now)->modify("-$i month");
            $value = $dt->format('Y-m');
            $months[] = [
                'value' => $value,
                'label' => $dt->format('m') . '/' . $dt->format('Y') // ex: 11/2025
            ];
        }

        // tópicos fixos (na ordem solicitada)
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
        ?>

        <form method="get" class="row g-2 align-items-center mb-4">
            <div class="col-auto">
                <label for="month" class="form-label visually-hidden">Mês</label>
                <select id="month" name="month" class="form-select form-select-sm">
                    <?php foreach ($months as $m) : ?>
                        <option value="<?= $m['value'] ?>" <?= $m['value'] === $selectedMonth ? 'selected' : '' ?>>
                            <?= formatarMesLabel($m['value']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
            </div>
            <div class="col-auto ms-auto">
                <small class="text-muted">Filtrando: <?= formatarMesLabel($selectedMonth) ?></small>
            </div>
        </form>

        <?php
        // função auxiliar para mostrar mês por extenso (pt-BR)
        function formatarMesLabel($ym)
        {
            $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            list($y, $m) = explode('-', $ym);
            $mIndex = (int)$m - 1;
            return $meses[$mIndex] . " $y";
        }
        ?>

        <!-- Seções por tópico -->
        <div class="row g-3">
            <?php foreach ($topics as $topic) : ?>
                <div class="col-12">
                    <div class="card shadow-sm despesas-card border-0">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <h6 class="mb-0"><?= htmlspecialchars($topic) ?></h6>
                                <?php $count = contarDespesasPorMesETipo($selectedMonth, $topic); ?>
                                <span class="badge bg-secondary"><?= $count ?></span>
                            </div>
                            <small class="text-muted">Mês: <?= formatarMesLabel($selectedMonth) ?></small>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Descrição</th>
                                            <th scope="col">Valor</th>
                                            <th scope="col">Data Vencimento</th>
                                            <th scope="col">Tipo</th>
                                            <th scope="col">Situação</th>
                                            <th scope="col">Observação</th>
                                            <th scope="col" class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // buscar do DB por mês e tópico
                                        $rows = buscarDespesasPorMesETipo($selectedMonth, $topic);
                                        if (empty($rows)) :
                                        ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-3">
                                                    Nenhuma despesa para "<?= htmlspecialchars($topic) ?>" em <?= formatarMesLabel($selectedMonth) ?>.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($rows as $r) : ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r['descricao']) ?></td>
                                                    <td>R$ <?= number_format($r['valor'], 2, ',', '.') ?></td>
                                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['data_vencimento']))) ?></td>
                                                    <td><?= htmlspecialchars($r['tipo']) ?></td>
                                                    <td><?= htmlspecialchars($r['situacao']) ?></td>
                                                    <td><?= htmlspecialchars($r['observacao']) ?></td>
                                                    <td class="text-end">
                                                        <a class="btn btn-sm btn-outline-secondary" href="editarDespesa.php?id=<?= urlencode($r['id']) ?>">Editar</a>
                                                        <form method="post" action="excluirDespesa.php" class="d-inline ms-2" onsubmit="return confirm('Tem certeza que deseja excluir esta despesa?');">
                                                            <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                                                            <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth) ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                                        </form>
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
            <?php endforeach; ?>
        </div>

    </div>
</main>

<?php
// Nota: substitua o bloco de "Nenhuma despesa..." pelo loop que puxa resultados do DB.
// Sugestão: retornar um array $rows por tópico e, no lugar do bloco de vazio, fazer foreach($rows as $r) { echo linhas }
?>
</main>

</html>