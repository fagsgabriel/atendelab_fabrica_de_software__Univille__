<?php

class PessoasController
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
                'SELECT id, nome, documento, telefone, curso, periodo, status
                 FROM pessoas
                 ORDER BY nome'
            );
            $stmt->execute();
            $this->json(['pessoas' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
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
                'SELECT id, nome, documento, telefone, curso, periodo, status
                 FROM pessoas
                 WHERE id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pessoa) {
                $this->json(['erro' => 'Pessoa não encontrada.'], 404);
                return;
            }

            $this->json(['pessoa' => $pessoa]);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    public function criar(): void
    {
        $dados = $this->lerDadosPost(['nome', 'documento']);
        if (isset($dados['erro'])) {
            $this->json(['erro' => $dados['erro']], 400);
            return;
        }

        $telefone = trim((string) ($_POST['telefone'] ?? ''));
        $curso = trim((string) ($_POST['curso'] ?? ''));
        $periodo = trim((string) ($_POST['periodo'] ?? ''));
        $telefone = $telefone === '' ? null : $telefone;
        $curso = $curso === '' ? null : $curso;
        $periodo = $periodo === '' ? null : $periodo;

        $status = trim((string) ($_POST['status'] ?? ''));
        if ($status === '') {
            $status = 'ativo';
        } elseif (!in_array($status, self::STATUS_VALIDOS, true)) {
            $this->json(['erro' => 'Status inválido. Valores aceitos: ativo, inativo.'], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO pessoas (nome, documento, telefone, curso, periodo, status)
                 VALUES (:nome, :documento, :telefone, :curso, :periodo, :status)'
            );
            $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':documento', $dados['documento'], PDO::PARAM_STR);
            $stmt->bindValue(':telefone', $telefone, $telefone === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':curso', $curso, $curso === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':periodo', $periodo, $periodo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->execute();

            $this->json(['id' => (int) $this->pdo->lastInsertId(), 'mensagem' => 'Pessoa cadastrada com sucesso.'], 201);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json(['erro' => 'Documento já cadastrado para outra pessoa.'], 400);
                return;
            }
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

        $dados = $this->lerDadosPost(['nome', 'documento']);
        if (isset($dados['erro'])) {
            $this->json(['erro' => $dados['erro']], 400);
            return;
        }

        $telefone = trim((string) ($_POST['telefone'] ?? ''));
        $curso = trim((string) ($_POST['curso'] ?? ''));
        $periodo = trim((string) ($_POST['periodo'] ?? ''));
        $telefone = $telefone === '' ? null : $telefone;
        $curso = $curso === '' ? null : $curso;
        $periodo = $periodo === '' ? null : $periodo;

        $status = trim((string) ($_POST['status'] ?? ''));
        if ($status === '') {
            $status = 'ativo';
        } elseif (!in_array($status, self::STATUS_VALIDOS, true)) {
            $this->json(['erro' => 'Status inválido. Valores aceitos: ativo, inativo.'], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id FROM pessoas WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->json(['erro' => 'Pessoa não encontrada.'], 404);
                return;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE pessoas
                 SET nome = :nome, documento = :documento, telefone = :telefone,
                     curso = :curso, periodo = :periodo, status = :status
                 WHERE id = :id'
            );
            $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':documento', $dados['documento'], PDO::PARAM_STR);
            $stmt->bindValue(':telefone', $telefone, $telefone === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':curso', $curso, $curso === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':periodo', $periodo, $periodo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->json(['mensagem' => 'Pessoa atualizada com sucesso.']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json(['erro' => 'Documento já cadastrado para outra pessoa.'], 400);
                return;
            }
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
            $stmt = $this->pdo->prepare('SELECT id FROM pessoas WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->json(['erro' => 'Pessoa não encontrada.'], 404);
                return;
            }

            $stmt = $this->pdo->prepare("UPDATE pessoas SET status = 'inativo' WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->json(['mensagem' => 'Pessoa inativada com sucesso.']);
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
