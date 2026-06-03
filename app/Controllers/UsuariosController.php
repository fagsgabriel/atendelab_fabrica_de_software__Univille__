<?php

class UsuariosController
{
    private PDO $pdo;

    private const PERFIS_VALIDOS = ['admin', 'aluno', 'atendente'];
    private const STATUS_VALIDOS = ['ativo', 'inativo'];

    public function __construct()
    {
        require_once __DIR__ . '/../../config/database.php';
        $this->pdo = $pdo;
    }

    public function listar(): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, nome, email, perfil, status, criado_em
                 FROM usuarios
                 ORDER BY id DESC'
            );
            $stmt->execute();
            $this->json(['usuarios' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
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
                'SELECT id, nome, email, perfil, status, criado_em
                 FROM usuarios
                 WHERE id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $this->json(['erro' => 'Usuário não encontrado.'], 404);
                return;
            }

            $this->json(['usuario' => $usuario]);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    public function criar(): void
    {
        $dados = $this->lerDadosPost(['nome', 'email', 'senha', 'perfil', 'status']);
        if (isset($dados['erro'])) {
            $this->json(['erro' => $dados['erro']], 400);
            return;
        }

        $validacao = $this->validarDadosUsuario(
            $dados['nome'],
            $dados['email'],
            $dados['perfil'],
            $dados['status']
        );
        if (isset($validacao['erro'])) {
            $this->json(['erro' => $validacao['erro']], 400);
            return;
        }

        $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha, perfil, status)
                 VALUES (:nome, :email, :senha, :perfil, :status)'
            );
            $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $dados['email'], PDO::PARAM_STR);
            $stmt->bindValue(':senha', $senhaHash, PDO::PARAM_STR);
            $stmt->bindValue(':perfil', $dados['perfil'], PDO::PARAM_STR);
            $stmt->bindValue(':status', $dados['status'], PDO::PARAM_STR);
            $stmt->execute();

            $this->json(['id' => (int) $this->pdo->lastInsertId(), 'mensagem' => 'Usuário criado com sucesso.'], 201);
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

        $dados = $this->lerDadosPost(['nome', 'email', 'perfil', 'status']);
        if (isset($dados['erro'])) {
            $this->json(['erro' => $dados['erro']], 400);
            return;
        }

        $validacao = $this->validarDadosUsuario(
            $dados['nome'],
            $dados['email'],
            $dados['perfil'],
            $dados['status']
        );
        if (isset($validacao['erro'])) {
            $this->json(['erro' => $validacao['erro']], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id FROM usuarios WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->json(['erro' => 'Usuário não encontrado.'], 404);
                return;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE usuarios
                 SET nome = :nome, email = :email, perfil = :perfil, status = :status
                 WHERE id = :id'
            );
            $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $dados['email'], PDO::PARAM_STR);
            $stmt->bindValue(':perfil', $dados['perfil'], PDO::PARAM_STR);
            $stmt->bindValue(':status', $dados['status'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->json(['mensagem' => 'Usuário atualizado com sucesso.']);
        } catch (PDOException $e) {
            $this->erroInterno($e);
        }
    }

    public function excluir(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id === false || $id === null) {
            $this->json(['erro' => 'ID inválido ou não informado.'], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->json(['erro' => 'Usuário não encontrado.'], 404);
                return;
            }

            $this->json(['mensagem' => 'Usuário excluído com sucesso.']);
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

    private function validarDadosUsuario(string $nome, string $email, string $perfil, string $status): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['erro' => 'E-mail inválido.'];
        }

        if (!in_array($perfil, self::PERFIS_VALIDOS, true)) {
            return ['erro' => 'Perfil inválido. Valores aceitos: admin, aluno, atendente.'];
        }

        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            return ['erro' => 'Status inválido. Valores aceitos: ativo, inativo.'];
        }

        return [];
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
