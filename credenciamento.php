<?php
include 'includes/header.php';
include 'includes/functions.php';

// Tratamento de submissão de atualização (vindo do modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uf'], $_POST['renovacao'], $_POST['vencimento'])) {
	$uf = trim($_POST['uf']);
	$renovacao = trim($_POST['renovacao']); // espera YYYY-MM-DD
	$vencimento = trim($_POST['vencimento']); // espera YYYY-MM-DD

	// validar formato básico
	$ok = preg_match('/^[A-Z]{2}$/', $uf) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $renovacao) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento);
	if ($ok) {
		if (atualizarCredenciamentoUF($uf, $renovacao, $vencimento)) {
			// redireciona para evitar reenvio do form
			header('Location: credenciamento.php?updated=1');
			exit;
		} else {
			$error = 'Erro ao salvar no banco.';
		}
	} else {
		$error = 'Dados inválidos.';
	}
}

// Buscar dados do banco
$ufs = buscarCredenciamentosUF();

function statusCredenciamento($vencimento) {
	$hoje = strtotime(date('Y-m-d'));
	$venc = strtotime($vencimento);
	$dias = ($venc - $hoje) / 86400;
	if ($dias < 0) return 'vencido';
	if ($dias <= 60) return 'alerta';
	return 'ok';
}

?>

<link rel="stylesheet" href="css/credenciamento.css">

<main class="container py-4">
	<h2 class="mb-4">Credenciamento por UF</h2>

	<?php if (!empty($_GET['updated'])): ?>
		<div class="alert alert-success">Dados atualizados com sucesso.</div>
	<?php elseif (!empty($error)): ?>
		<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
	<?php endif; ?>

	<div class="card card-surface shadow-sm mb-4">
		<div class="card-body">
			<h5 class="card-title mb-3">Lista de UFs</h5>
			<div class="table-responsive">
				<table class="table table-bordered align-middle">
					<thead class="table-light">
						<tr>
							<th>UF</th>
							<th>Renovação</th>
							<th>Vencimento</th>
							<th>Status</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($ufs as $uf => $dados):
							$status = statusCredenciamento($dados['vencimento']);
						?>
						<tr class="uf-status status-<?= $status ?>" data-uf="<?= htmlspecialchars($uf) ?>">
							<td><strong><?= htmlspecialchars($uf) ?></strong></td>
							<td class="cell-renovacao"><?= htmlspecialchars(date('d/m/Y', strtotime($dados['renovacao']))) ?></td>
							<td class="cell-vencimento"><?= htmlspecialchars(date('d/m/Y', strtotime($dados['vencimento']))) ?></td>
							<td class="cell-status">
								<?php if ($status == 'ok'): ?><span class="badge bg-success">Credenciado</span><?php endif; ?>
								<?php if ($status == 'alerta'): ?><span class="badge bg-warning text-dark">Renovar em breve</span><?php endif; ?>
								<?php if ($status == 'vencido'): ?><span class="badge bg-danger">Vencido</span><?php endif; ?>
							</td>
							<td>
								<button class="btn btn-sm btn-outline-primary" onclick="editarUF('<?= htmlspecialchars($uf) ?>')">Editar</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Modal edição -->
	<div class="modal fade" id="modalEditarUF" tabindex="-1" aria-labelledby="modalEditarUFLabel" aria-hidden="true">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <form id="formEditarUF" method="post">
			<div class="modal-header">
			  <h5 class="modal-title" id="modalEditarUFLabel">Editar Credenciamento UF</h5>
			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
			</div>
			<div class="modal-body">
			  <input type="hidden" name="uf" id="editUf">
			  <div class="mb-3">
				<label class="form-label">Data Renovação</label>
				<input type="date" name="renovacao" id="editRenovacao" class="form-control" required>
			  </div>
			  <div class="mb-3">
				<label class="form-label">Data Vencimento</label>
				<input type="date" name="vencimento" id="editVencimento" class="form-control" required>
			  </div>
			</div>
			<div class="modal-footer">
			  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
			  <button type="submit" class="btn btn-primary">Salvar</button>
			</div>
		  </form>
		</div>
	  </div>
	</div>

</main>

<script>
const dadosUF = <?= json_encode($ufs) ?>;

function calcularStatus(vencimentoISO) {
	var venc = new Date(vencimentoISO);
	var hoje = new Date();
	var diff = (venc - hoje) / (1000 * 60 * 60 * 24);
	if (diff < 0) return 'vencido';
	if (diff <= 60) return 'alerta';
	return 'ok';
}

function gerarBadge(status) {
	if (status === 'ok') return '<span class="badge bg-success">Credenciado</span>';
	if (status === 'alerta') return '<span class="badge bg-warning text-dark">Renovar em breve</span>';
	return '<span class="badge bg-danger">Vencido</span>';
}

function atualizarLinhaUF(uf, rowData) {
	var row = document.querySelector('tr[data-uf="' + uf + '"]');
	if (!row) return;
	var status = calcularStatus(rowData.vencimento);
	row.querySelector('.cell-renovacao').textContent = (new Date(rowData.renovacao)).toLocaleDateString('pt-BR');
	row.querySelector('.cell-vencimento').textContent = (new Date(rowData.vencimento)).toLocaleDateString('pt-BR');
	var cellStatus = row.querySelector('.cell-status');
	if (cellStatus) {
		cellStatus.innerHTML = gerarBadge(status);
	}
	row.classList.remove('status-ok','status-alerta','status-vencido');
	row.classList.add('status-' + status);
}

function mostrarAlerta(mensagem, tipo) {
	var main = document.querySelector('main.container');
	if (!main) return;
	var alertEl = document.createElement('div');
	alertEl.className = 'alert alert-' + (tipo || 'success');
	alertEl.textContent = mensagem;
	var titulo = main.querySelector('h2');
	if (titulo) {
		titulo.insertAdjacentElement('afterend', alertEl);
	} else {
		main.insertBefore(alertEl, main.firstChild);
	}
	setTimeout(function(){ alertEl.remove(); }, 3000);
}

function editarUF(uf) {
	if (!dadosUF[uf]) return;
	document.getElementById('editUf').value = uf;
	document.getElementById('editRenovacao').value = dadosUF[uf]['renovacao'];
	document.getElementById('editVencimento').value = dadosUF[uf]['vencimento'];
	var modal = new bootstrap.Modal(document.getElementById('modalEditarUF'));
	modal.show();
}

var formEditar = document.getElementById('formEditarUF');
if (formEditar) {
	formEditar.addEventListener('submit', async function(e){
		e.preventDefault();
		var fd = new FormData(formEditar);
		var uf = fd.get('uf');

		try {
			var resp = await fetch('credenciamento_api.php?action=update', { method: 'POST', body: fd });
			var json = await resp.json();
			if (resp.ok && json.ok && json.row) {
				dadosUF[uf] = json.row;
				atualizarLinhaUF(uf, json.row);
				var bmodal = bootstrap.Modal.getInstance(document.getElementById('modalEditarUF'));
				if (bmodal) bmodal.hide();
				mostrarAlerta('Atualizado com sucesso.', 'success');
			} else {
				alert('Erro ao salvar: ' + (json && (json.error || resp.status)));
			}
		} catch (err) {
			alert('Erro de rede: ' + err.message);
		}
	});
}
</script>
</body>
</html>