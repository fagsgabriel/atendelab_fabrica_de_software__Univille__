<?php
$tituloPagina = 'Login';
$baseUrl = '/atendelab/public/';
$erro = $_GET['erro'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | AtendeLab</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
<div style="width:100%;max-width:430px;margin:0 1rem;">
  <div class="card border-0 shadow-sm p-4">
    <div class="text-center mb-4">
      <div class="brand-mark mx-auto mb-3">AL</div>
      <h1 class="h4 mb-0 fw-semibold">AtendeLab</h1>
      <p class="text-secondary small mt-1">Controle de atendimentos acadêmicos</p>
    </div>
    <?php if ($erro): ?>
      <div class="alert alert-danger py-2 small">E-mail ou senha inválidos.</div>
    <?php endif; ?>
    <form method="POST" action="<?= $baseUrl ?>?controller=auth&action=entrar">
      <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input class="form-control" type="email" name="email" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label">Senha</label>
        <input class="form-control" type="password" name="senha" required>
      </div>
      <button class="btn btn-success w-100" type="submit">Entrar</button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
