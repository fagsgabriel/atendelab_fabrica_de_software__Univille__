<?php

class AtendimentosController
{
    private PDO $pdo;

    private const STATUS_VALIDOS = ['aberto', 'em_andamento', 'concluido'];

    public function __construct()
    {
        require_once __DIR__ . '/../../config/database.php';
        global $pdo;
        $this->pdo = $pdo;
    }

    public function listar(): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT a.id, p.nome AS pessoa_nome, t.nome AS tipo_nome,
                        u.nome AS responsavel_nome, a.descricao, a.observacao,
                        a.status, a.data_atendimento, a.hora_atendimento, a.criado_em
                 FROM atendimentos a
                 INNER JOIN pessoas p ON p.id = a.pessoa_id
                 INNER JOIN tipos_atendimentos t ON t.id = a.tipo_atendimento_id
                 INNER JOIN usuarios u ON u.id = a.usuario_id
                 ORDER BY a.id DESC'
            );
            $stmt->execute();
            $this->json(['atendimentos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    public function buscarPorId(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id === false || $id === null) {
            $this->json(['erro' => 'ID inválido ou não informado.'], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT a.id, a.pessoa_id, a.tipo_atendimento_id, a.usuario_id,
                        p.nome AS pessoa_nome, t.nome AS tipo_nome,
                        u.nome AS responsavel_nome, a.descricao, a.observacao,
                        a.status, a.data_atendimento, a.hora_atendimento, a.criado_em
                 FROM atendimentos a
                 INNER JOIN pessoas p ON p.id = a.pessoa_id
                 INNER JOIN tipos_atendimentos t ON t.id = a.tipo_atendimento_id
                 INNER JOIN usuarios u ON u.id = a.usuario_id
                 WHERE a.id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$atendimento) {
                $this->json(['erro' => 'Atendimento não encontrado.'], 404);
                return;
            }

            $this->json(['atendimento' => $atendimento]);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    public function criar(): void
    {
        $pessoaId = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
        $tipoId = filter_input(INPUT_POST, 'tipo_atendimento_id', FILTER_VALIDATE_INT);
        $usuarioId = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);

        if (
            $pessoaId === false || $pessoaId === null
            || $tipoId === false || $tipoId === null
            || $usuarioId === false || $usuarioId === null
        ) {
            $this->json(['erro' => 'Os campos pessoa_id, tipo_atendimento_id e usuario_id são obrigatórios e devem ser numéricos.'], 400);
            return;
        }

        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $dataAtendimento = trim((string) ($_POST['data_atendimento'] ?? ''));
        $horaAtendimento = trim((string) ($_POST['hora_atendimento'] ?? ''));
        $descricao = $descricao === '' ? null : $descricao;
        $dataAtendimento = $dataAtendimento === '' ? null : $dataAtendimento;
        $horaAtendimento = $horaAtendimento === '' ? null : $horaAtendimento;

        try {
            $stmt = $this->pdo->prepare('SELECT id FROM pessoas WHERE id = :id');
            $stmt->bindValue(':id', $pessoaId, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->json(['erro' => 'Pessoa não encontrada.'], 404);
                return;
            }

            $stmt = $this->pdo->prepare('SELECT id FROM tipos_atendimentos WHERE id = :id');
            $stmt->bindValue(':id', $tipoId, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->json(['erro' => 'Tipo de atendimento não encontrado.'], 404);
                return;
            }

            $stmt = $this->pdo->prepare('SELECT id, status FROM usuarios WHERE id = :id');
            $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) {
                $this->json(['erro' => 'Usuário responsável não encontrado.'], 404);
                return;
            }
            if ($usuario['status'] === 'inativo') {
                $this->json(['erro' => 'Usuário responsável está inativo.'], 400);
                return;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO atendimentos (pessoa_id, tipo_atendimento_id, usuario_id,
                                           descricao, data_atendimento, hora_atendimento, status)
                 VALUES (:pessoa_id, :tipo_atendimento_id, :usuario_id,
                         :descricao, :data_atendimento, :hora_atendimento, :status)'
            );
            $stmt->bindValue(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_atendimento_id', $tipoId, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':descricao', $descricao, $descricao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':data_atendimento', $dataAtendimento, $dataAtendimento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':hora_atendimento', $horaAtendimento, $horaAtendimento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':status', 'aberto', PDO::PARAM_STR);
            $stmt->execute();

            $this->json(['id' => (int) $this->pdo->lastInsertId(), 'mensagem' => 'Atendimento registrado com sucesso.'], 201);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    public function alterarStatus(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id === false || $id === null) {
            $this->json(['erro' => 'ID inválido ou não informado.'], 400);
            return;
        }

        $status = trim((string) ($_POST['status'] ?? ''));
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            $this->json(['erro' => 'Status inválido. Valores aceitos: aberto, em_andamento, concluido.'], 400);
            return;
        }

        $observacao = trim((string) ($_POST['observacao'] ?? ''));

        if ($status === 'concluido' && $observacao === '') {
            $this->json(['erro' => 'Informe a observação para concluir o atendimento.'], 400);
            return;
        }

        $observacao = $observacao === '' ? null : $observacao;

        try {
            $stmt = $this->pdo->prepare('SELECT id FROM atendimentos WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->json(['erro' => 'Atendimento não encontrado.'], 404);
                return;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE atendimentos
                 SET status = :status, observacao = :observacao
                 WHERE id = :id'
            );
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':observacao', $observacao, $observacao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->json(['mensagem' => 'Status do atendimento atualizado com sucesso.']);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    private function json(array $dados, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    }

    private function erroInterno(PDOException $e): void
    {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['erro' => 'Erro interno no servidor.', 'detalhe' => $e->getMessage()],
            JSON_UNESCAPED_UNICODE
        );
    }
}
