<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Bloquear acesso a páginas internas se não estiver autenticado.
// Exceções: tela de login e logout (e quaisquer páginas públicas explicitamente listadas abaixo).
$public_pages = ['login.php', 'logout.php'];
$current_script = basename($_SERVER['PHP_SELF']);
if (empty($_SESSION['user']) && !in_array($current_script, $public_pages)) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DetranHub</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/header.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>
</head>

<body>
    <header class="sticky-top">
        <nav class="navbar navbar-expand-lg bg-white shadow-sm border-bottom">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                    <span class="brand-mark rounded bg-primary text-white d-inline-flex align-items-center justify-content-center">DH</span>
                    <span class="brand-text">DetranHub</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="nav align-items-lg-center">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Despesas</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="despesas.php">Despesas Mês</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="adicionarDespesa.php">Adicionar Despesa</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="credenciamento.php">Credenciamento</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="processoAdministrativo.php">Processo Administrativo</a>
                        </li>
                        <?php if (!empty($_SESSION['user'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"><?= htmlspecialchars($_SESSION['user']) ?></a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Entrar</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Conteúdo da página começa após o header; o fechamento de </body> e </html> deve ficar nos templates/footers finais -->