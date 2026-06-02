<?php

$controller = $_GET['controller'] ?? '';
$action = $_GET['action'] ?? '';

if ($controller === 'usuarios') {
    require_once __DIR__ . '/app/Controllers/UsuariosController.php';

    $ctrl = new UsuariosController();
    $acoes = ['listar', 'buscarPorId', 'criar', 'atualizar', 'excluir'];

    if (in_array($action, $acoes, true) && method_exists($ctrl, $action)) {
        $ctrl->$action();
    } else {
        http_response_code(404);
        echo '<h1>Ação não encontrada</h1>';
        echo '<p>Use uma das ações: ' . implode(', ', $acoes) . '.</p>';
    }
} else {
    echo '<h1>AtendeLab</h1>';
    echo '<p>Sistema de Controle de Atendimentos Acadêmicos.</p>';
    echo '<p><strong>Como usar:</strong> informe <code>controller</code> e <code>action</code> na URL.</p>';
    echo '<p>Exemplo: <code>?controller=usuarios&amp;action=listar</code></p>';
    echo '<p>Ações disponíveis para <code>usuarios</code>: listar, buscarPorId, criar, atualizar, excluir.</p>';
}
