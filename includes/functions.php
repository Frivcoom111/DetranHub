<?php

require_once __DIR__ . '/../config.php';

/**
 * Converte YYYY-MM para YYYY-MM-01; aceita YYYY-MM-DD também.
 */
function normalizeDateRef(string $d): ?string {
    if (preg_match('/^\d{4}-\d{2}$/', $d)) {
        return $d . '-01';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        return $d;
    }
    return null;
}
/**
 * Busca todos os credenciamentos das UFs
 * Retorna array associativo uf => [renovacao, vencimento, ...]
 */
function buscarCredenciamentosUF(): array {
    global $conn;
    $sql = "SELECT uf, renovacao, vencimento, criado_em, atualizado_em FROM credenciamento_uf ORDER BY uf ASC";
    $res = mysqli_query($conn, $sql);
    $out = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $out[$row['uf']] = $row;
        }
    }
    return $out;
}

/**
 * Busca credenciamento de uma UF específica
 * Retorna array associativo ou null
 */
function buscarCredenciamentoPorUF(string $uf): ?array {
    global $conn;
    $sql = "SELECT uf, renovacao, vencimento, criado_em, atualizado_em FROM credenciamento_uf WHERE uf = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, "s", $uf);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row;
}

/**
 * Atualiza datas de renovação e vencimento de uma UF
 * Retorna true em sucesso, false em erro
 */
function atualizarCredenciamentoUF(string $uf, string $renovacao, string $vencimento): bool {
    global $conn;
    $sql = "UPDATE credenciamento_uf SET renovacao = ?, vencimento = ?, atualizado_em = CURRENT_TIMESTAMP WHERE uf = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "sss", $renovacao, $vencimento, $uf);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

function inserirDespesa($descricao, $valor, $data_referencia, $data_vencimento, $tipo, $situacao, $observacao) {
    global $conn;

    $data_ref = normalizeDateRef($data_referencia);
    if (!$data_ref) return false;

    // garante que valor seja float
    $valorFloat = is_numeric($valor) ? (float)$valor : 0.0;

    $sql = "INSERT INTO despesas (descricao, valor, data_referencia, data_vencimento, tipo, situacao, observacao)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("[inserirDespesa] Prepare failed: " . mysqli_error($conn));
        return false;
    }

    // tipos: s = string, d = double
    mysqli_stmt_bind_param($stmt, "sdsssss",
        $descricao,
        $valorFloat,
        $data_ref,
        $data_vencimento,
        $tipo,
        $situacao,
        $observacao
    );

    if (!mysqli_stmt_execute($stmt)) {
        error_log("[inserirDespesa] Execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $insertId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $insertId ?: true;
}

/**
 * Busca despesas por mês (YYYY-MM) e tipo (string). Se tipo for null, busca todos os tipos.
 * Retorna array de rows (assoc) ou array vazio.
 */
function buscarDespesasPorMesETipo(string $month, ?string $tipo = null): array {
    global $conn;
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) return [];

    list($y, $m) = explode('-', $month);
    $y = (int)$y; $m = (int)$m;

    if ($tipo) {
        // ordenar por valor da maior para a menor
        $sql = "SELECT * FROM despesas WHERE YEAR(data_referencia)=? AND MONTH(data_referencia)=? AND tipo = ? ORDER BY valor DESC, data_vencimento ASC, id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "iis", $y, $m, $tipo);
    } else {
        // ordenar por valor da maior para a menor para exibição geral também
        $sql = "SELECT * FROM despesas WHERE YEAR(data_referencia)=? AND MONTH(data_referencia)=? ORDER BY valor DESC, tipo ASC, data_vencimento ASC, id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "ii", $y, $m);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

/**
 * Conta despesas por mês e tipo
 */
function contarDespesasPorMesETipo(string $month, string $tipo): int {
    global $conn;
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) return 0;
    list($y, $m) = explode('-', $month);
    $y = (int)$y; $m = (int)$m;

    $sql = "SELECT COUNT(*) AS total FROM despesas WHERE YEAR(data_referencia)=? AND MONTH(data_referencia)=? AND tipo = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;
    mysqli_stmt_bind_param($stmt, "iis", $y, $m, $tipo);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return (int)($row['total'] ?? 0);
}

/**
 * Busca uma despesa pelo id
 */
function buscarDespesaPorId(int $id) {
    global $conn;
    $sql = "SELECT * FROM despesas WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row;
}

/**
 * Exclui uma despesa pelo id
 * Retorna true em sucesso, false em erro
 */
function deletarDespesa(int $id): bool {
    global $conn;
    $sql = "DELETE FROM despesas WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "i", $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

/**
 * Soma valores das despesas de um mês (YYYY-MM). Se $situacao informado, filtra também por situação.
 * Retorna float (0.0 se nenhum registro)
 */
function somaDespesasPorMes(string $month, ?string $situacao = null): float {
    global $conn;
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) return 0.0;
    list($y, $m) = explode('-', $month);
    $y = (int)$y; $m = (int)$m;

    if ($situacao) {
        $sql = "SELECT SUM(valor) AS total FROM despesas WHERE YEAR(data_referencia)=? AND MONTH(data_referencia)=? AND situacao = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return 0.0;
        mysqli_stmt_bind_param($stmt, "iis", $y, $m, $situacao);
    } else {
        $sql = "SELECT SUM(valor) AS total FROM despesas WHERE YEAR(data_referencia)=? AND MONTH(data_referencia)=?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return 0.0;
        mysqli_stmt_bind_param($stmt, "ii", $y, $m);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return (float)($row['total'] ?? 0.0);
}

/**
 * Retorna totais por tipo no mês fornecido. Retorna array associativo tipo => total
 */
function totaisPorTipoMes(string $month): array {
    global $conn;
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) return [];
    list($y, $m) = explode('-', $month);
    $y = (int)$y; $m = (int)$m;

    $sql = "SELECT tipo, SUM(valor) AS total FROM despesas WHERE YEAR(data_referencia)=? AND MONTH(data_referencia)=? GROUP BY tipo ORDER BY total DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, "ii", $y, $m);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);

    $out = [];
    foreach ($rows as $r) {
        $out[$r['tipo']] = (float)$r['total'];
    }
    return $out;
}

/**
 * Retorna próximas despesas por data de vencimento (>= hoje), ordenadas pela mais próxima.
 */
function proxVencimentos(int $limit = 5): array {
    global $conn;
    $sql = "SELECT id, descricao, valor, data_vencimento, tipo, situacao FROM despesas WHERE data_vencimento >= CURDATE() ORDER BY data_vencimento ASC LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

/**
 * Retorna as maiores despesas do mês (ordenado por valor desc)
 */
function maioresDespesasMes(string $month, int $limit = 5): array {
    global $conn;
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) return [];
    list($y, $m) = explode('-', $month);
    $y = (int)$y; $m = (int)$m;

    $sql = "SELECT * FROM despesas WHERE YEAR(data_referencia)=? AND MONTH(data_referencia)=? ORDER BY valor DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, "iii", $y, $m, $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

/**
 * Retorna últimas despesas adicionadas no mês (ordenado por criado_em desc)
 */
function ultimasDespesasMes(string $month, int $limit = 5): array {
    global $conn;
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) return [];
    list($y, $m) = explode('-', $month);
    $y = (int)$y; $m = (int)$m;

    $sql = "SELECT * FROM despesas WHERE YEAR(data_referencia)=? AND MONTH(data_referencia)=? ORDER BY criado_em DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];
    mysqli_stmt_bind_param($stmt, "iii", $y, $m, $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

/**
 * Atualiza uma despesa pelo id
 * Retorna true em sucesso, false em erro
 */
function atualizarDespesa(int $id, string $descricao, float $valor, string $data_referencia_month, string $data_vencimento, string $tipo, string $situacao, ?string $observacao = null): bool {
    global $conn;
    $data_ref = normalizeDateRef($data_referencia_month);
    if (!$data_ref) return false;

    $valorFloat = is_numeric($valor) ? (float)$valor : 0.0;

    $sql = "UPDATE despesas SET descricao = ?, valor = ?, data_referencia = ?, data_vencimento = ?, tipo = ?, situacao = ?, observacao = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, "sdsssssi",
        $descricao,
        $valorFloat,
        $data_ref,
        $data_vencimento,
        $tipo,
        $situacao,
        $observacao,
        $id
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

/**
 * Normaliza valores monetários recebidos em string (R$, pontos, vírgulas) para float.
 */
function normalizeMoneyValue($value): float {
    if (is_null($value)) return 0.0;
    if (is_numeric($value)) return (float)$value;
    $clean = str_replace(['R$', ' '], '', (string)$value);
    // Remove separadores de milhar e converte vírgula decimal para ponto
    $clean = str_replace('.', '', $clean);
    $clean = str_replace(',', '.', $clean);
    return is_numeric($clean) ? (float)$clean : 0.0;
}

/**
 * Retorna todos os processos administrativos cadastrados.
 */
function listarProcessosAdministrativos(): array {
    global $conn;
    $sql = "SELECT id, descricao, grupo, cota, data_processo, situacao, valor_custas, status, criado_em, atualizado_em FROM processos_administrativos ORDER BY data_processo DESC, id DESC";
    $res = mysqli_query($conn, $sql);
    if (!$res) return [];
    return mysqli_fetch_all($res, MYSQLI_ASSOC) ?: [];
}

/**
 * Retorna um processo administrativo específico.
 */
function buscarProcessoAdministrativoPorId(int $id): ?array {
    global $conn;
    $sql = "SELECT id, descricao, grupo, cota, data_processo, situacao, valor_custas, status, criado_em, atualizado_em FROM processos_administrativos WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/**
 * Insere um novo processo administrativo.
 */
function inserirProcessoAdministrativo(string $descricao, string $grupo, string $cota, string $dataProcesso, string $situacao, $valorCustas, string $status): bool {
    global $conn;

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataProcesso)) {
        return false;
    }

    $status = strtoupper($status);
    $statusPermitidos = ['EM_ABERTO', 'FINALIZADO'];
    if (!in_array($status, $statusPermitidos, true)) {
        $status = 'EM_ABERTO';
    }

    $valor = normalizeMoneyValue($valorCustas);

    $sql = "INSERT INTO processos_administrativos (descricao, grupo, cota, data_processo, situacao, valor_custas, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, "sssssds",
        $descricao,
        $grupo,
        $cota,
        $dataProcesso,
        $situacao,
        $valor,
        $status
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

/**
 * Atualiza todos os campos principais de um processo administrativo.
 */
function atualizarProcessoAdministrativo(int $id, string $descricao, string $grupo, string $cota, string $dataProcesso, string $situacao, $valorCustas, string $status): bool {
    global $conn;
    if ($id <= 0) return false;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataProcesso)) return false;

    $status = strtoupper($status);
    $statusPermitidos = ['EM_ABERTO', 'FINALIZADO'];
    if (!in_array($status, $statusPermitidos, true)) {
        return false;
    }

    $valor = normalizeMoneyValue($valorCustas);

    $sql = "UPDATE processos_administrativos SET descricao = ?, grupo = ?, cota = ?, data_processo = ?, situacao = ?, valor_custas = ?, status = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, "sssssdsi",
        $descricao,
        $grupo,
        $cota,
        $dataProcesso,
        $situacao,
        $valor,
        $status,
        $id
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

/**
 * Atualiza o status (em aberto/finalizado) de um processo administrativo.
 */
function atualizarStatusProcessoAdministrativo(int $id, string $status): bool {
    global $conn;
    $status = strtoupper($status);
    $statusPermitidos = ['EM_ABERTO', 'FINALIZADO'];
    if (!in_array($status, $statusPermitidos, true)) {
        return false;
    }

    $sql = "UPDATE processos_administrativos SET status = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

/**
 * Exclui um processo administrativo pelo id.
 */
function deletarProcessoAdministrativo(int $id): bool {
    global $conn;
    if ($id <= 0) {
        return false;
    }

    $sql = "DELETE FROM processos_administrativos WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "i", $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

/**
 * Retorna última mensagem de erro do DB (útil para debug)
 */
function db_last_error(): ?string {
    global $conn;
    if (!$conn) return null;
    $err = mysqli_error($conn);
    return $err ? $err : null;
}
