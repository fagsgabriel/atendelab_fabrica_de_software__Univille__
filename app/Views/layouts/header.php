<?php
declare(strict_types=1);
require_once __DIR__ . '/config-view.php';
$tituloPagina = $tituloPagina ?? 'AtendeLab';
$usuarioLogado = $_SESSION['usuario'] ?? [];
$nomeUsuario = htmlspecialchars((string) ($usuarioLogado['nome'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8');
$perfilUsuario = htmlspecialchars((string) ($usuarioLogado['perfil'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?> | AtendeLab</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
<script src="<?= $baseUrl ?>assets/js/api.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="<?= $baseUrl ?>?controller=auth&action=dashboard">AtendeLab</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menuPrincipal">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>?controller=auth&action=dashboard">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>?controller=frontend&action=pessoas">Pessoas</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>?controller=frontend&action=tipos">Tipos</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>?controller=frontend&action=atendimentos">Atendimentos</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3 text-white small">
        <span><?= $nomeUsuario ?><?= $perfilUsuario !== '' ? ' · ' . $perfilUsuario : '' ?></span>
        <a class="btn btn-outline-light btn-sm" href="<?= $baseUrl ?>?controller=auth&action=logout">Sair</a>
      </div>
    </div>
  </div>
</nav>
<main class="container py-4">
