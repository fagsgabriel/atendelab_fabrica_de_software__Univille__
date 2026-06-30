<?php

require_once __DIR__ . '/../Middleware/auth.php';

class FrontendController
{
    public function pessoas(): void
    {
        exigirAutenticacao();
        require __DIR__ . '/../Views/pessoas/index.php';
    }

    public function tipos(): void
    {
        exigirAutenticacao();
        require __DIR__ . '/../Views/tipos-atendimentos/index.php';
    }

    public function atendimentos(): void
    {
        exigirAutenticacao();
        require __DIR__ . '/../Views/atendimentos/index.php';
    }
}
