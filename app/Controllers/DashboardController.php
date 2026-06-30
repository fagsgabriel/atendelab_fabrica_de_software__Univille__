<?php

class DashboardController
{
    private PDO $pdo;

    public function __construct()
    {
        require_once __DIR__ . '/../../config/database.php';
        global $pdo;
        $this->pdo = $pdo;
    }

    public function resumo(): void
    {
        try {
            $totalPessoas = (int) $this->pdo->query('SELECT COUNT(*) FROM pessoas')->fetchColumn();
            $totalTipos = (int) $this->pdo->query('SELECT COUNT(*) FROM tipos_atendimentos')->fetchColumn();
            $totalAtendimentos = (int) $this->pdo->query('SELECT COUNT(*) FROM atendimentos')->fetchColumn();

            $stmt = $this->pdo->query(
                'SELECT a.id, p.nome AS pessoa_nome, t.nome AS tipo_nome,
                        a.status, a.data_atendimento, a.hora_atendimento
                 FROM atendimentos a
                 INNER JOIN pessoas p ON p.id = a.pessoa_id
                 INNER JOIN tipos_atendimentos t ON t.id = a.tipo_atendimento_id
                 ORDER BY a.id DESC
                 LIMIT 5'
            );
            $recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'indicadores' => [
                    'total_pessoas' => $totalPessoas,
                    'total_tipos' => $totalTipos,
                    'total_atendimentos' => $totalAtendimentos,
                ],
                'atendimentos_recentes' => $recentes,
            ]);
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
