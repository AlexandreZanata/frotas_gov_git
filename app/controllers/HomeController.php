<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class HomeController
{
    public function index()
    {
        // Carrega a página de dashboard
        // Por enquanto, vamos apenas exibir uma mensagem
        echo "<h1>Bem-vindo ao Frotas Gov!</h1><p>Página principal funcionando.</p>";
    }
}