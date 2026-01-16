<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'config.php';

if (!empty($_SESSION['user'])) {
	header('Location: index.php');
	exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = isset($_POST['username']) ? trim($_POST['username']) : '';
	$password = isset($_POST['password']) ? $_POST['password'] : '';

	// Credenciais configuráveis em config.php (opcionais)
	$cfgUser = isset($AUTH_USER) ? $AUTH_USER : null;
	$cfgPassPlain = isset($AUTH_PASS) ? $AUTH_PASS : null;
	$cfgPassHash = isset($AUTH_PASS_HASH) ? $AUTH_PASS_HASH : null;

	$valid = false;
	if ($cfgUser) {
		if ($username === $cfgUser) {
			if ($cfgPassHash && password_verify($password, $cfgPassHash)) {
				$valid = true;
			} elseif ($cfgPassPlain !== null && hash_equals($cfgPassPlain, $password)) {
				$valid = true;
			}
		}
	} else {
		if ($username === 'carlos.faustino' && $password === 'CA1348@gazin') {
			$valid = true;
		}
	}

	if ($valid) {
		// Armazenar usuário na sessão
		$_SESSION['user'] = $username;
		header('Location: index.php');
		exit;
	} else {
		$error = 'Usuário ou senha inválidos.';
	}
}
?>

<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Login - DetranHub</title>
	<link rel="stylesheet" href="css/theme.css">
	<link rel="stylesheet" href="css/login.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>
	<main class="login-page">
		<div class="login-hero">
			<img src="img/login-hero.svg" alt="Hero" class="img-fluid">
		</div>

		<div class="login-form-wrap">
			<div class="card card-surface shadow-sm">
				<div class="card-body">
					<h3 class="card-title mb-3">Entrar</h3>
					<?php if ($error): ?>
						<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
					<?php endif; ?>

					<form method="post" novalidate>
						<div class="mb-3">
							<label class="form-label">Usuário</label>
							<input name="username" required class="form-control" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
						</div>
						<div class="mb-3">
							<label class="form-label">Senha</label>
							<input name="password" type="password" required class="form-control">
						</div>
						<div class="d-flex justify-content-between align-items-center">
							<button class="btn btn-primary">Entrar</button>
							<a href="index.php" class="small-muted">Voltar</a>
						</div>
					</form>
				</div>
			</div>
		</div>
	</main>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
