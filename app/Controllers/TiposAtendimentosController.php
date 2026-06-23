<?php

class TiposAtendimentosController
{
    private PDO $pdo;

    private const STATUS_VALIDOS = ['ativo', 'inativo'];

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
                'SELECT id, nome, descricao, status
                 FROM tipos_atendimentos
                 ORDER BY nome'
            );
            $stmt->execute();
            $this->json(['tipos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
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
                'SELECT id, nome, descricao, status
                 FROM tipos_atendimentos
                 WHERE id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $tipo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tipo) {
                $this->json(['erro' => 'Tipo não encontrado.'], 404);
                return;
            }

            $this->json(['tipo' => $tipo]);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    public function criar(): void
    {
        $dados = $this->lerDadosPost(['nome']);
        if (isset($dados['erro'])) {
            $this->json(['erro' => $dados['erro']], 400);
            return;
        }

        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $descricao = $descricao === '' ? null : $descricao;

        $status = trim((string) ($_POST['status'] ?? ''));
        if ($status === '') {
            $status = 'ativo';
        } elseif (!in_array($status, self::STATUS_VALIDOS, true)) {
            $this->json(['erro' => 'Status inválido. Valores aceitos: ativo, inativo.'], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO tipos_atendimentos (nome, descricao, status)
                 VALUES (:nome, :descricao, :status)'
            );
            $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $descricao, $descricao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->execute();

            $this->json(['id' => (int) $this->pdo->lastInsertId(), 'mensagem' => 'Tipo de atendimento cadastrado com sucesso.'], 201);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    public function atualizar(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id === false || $id === null) {
            $this->json(['erro' => 'ID inválido ou não informado.'], 400);
            return;
        }

        $dados = $this->lerDadosPost(['nome']);
        if (isset($dados['erro'])) {
            $this->json(['erro' => $dados['erro']], 400);
            return;
        }

        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $descricao = $descricao === '' ? null : $descricao;

        $status = trim((string) ($_POST['status'] ?? ''));
        if ($status === '') {
            $status = 'ativo';
        } elseif (!in_array($status, self::STATUS_VALIDOS, true)) {
            $this->json(['erro' => 'Status inválido. Valores aceitos: ativo, inativo.'], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id FROM tipos_atendimentos WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->json(['erro' => 'Tipo não encontrado.'], 404);
                return;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE tipos_atendimentos
                 SET nome = :nome, descricao = :descricao, status = :status
                 WHERE id = :id'
            );
            $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $descricao, $descricao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->json(['mensagem' => 'Tipo de atendimento atualizado com sucesso.']);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    public function inativar(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id === false || $id === null) {
            $this->json(['erro' => 'ID inválido ou não informado.'], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id FROM tipos_atendimentos WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->json(['erro' => 'Tipo não encontrado.'], 404);
                return;
            }

            $stmt = $this->pdo->prepare("UPDATE tipos_atendimentos SET status = 'inativo' WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->json(['mensagem' => 'Tipo de atendimento inativado com sucesso.']);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    private function lerDadosPost(array $campos): array
    {
        $dados = [];
        foreach ($campos as $campo) {
            $valor = $_POST[$campo] ?? null;
            if ($valor === null || trim((string) $valor) === '') {
                return ['erro' => "O campo '{$campo}' é obrigatório."];
            }
            $dados[$campo] = trim((string) $valor);
        }
        return $dados;
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
