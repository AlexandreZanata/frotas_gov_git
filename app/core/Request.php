<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class Request
{
    public function getUri()
    {
        // 1. Pega a URL da requisição (ex: /frotas-gov/public/login?id=1)
        $uri = $_SERVER['REQUEST_URI'];

        // 2. Remove a query string, se houver (ex: /frotas-gov/public/login)
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        // 3. Define o caminho base da sua aplicação no XAMPP
        $basePath = '/frotas-gov/public';

        // 4. Remove o caminho base da URL, deixando apenas a rota (ex: /login)
        if (substr($uri, 0, strlen($basePath)) == $basePath) {
            $uri = substr($uri, strlen($basePath));
        }

        // 5. Remove as barras do início e do fim para ter uma rota limpa (ex: login)
        // Se a URI for vazia (página inicial), retorna '/' para corresponder à rota raiz.
        return trim($uri, '/') ?: '/';
    }

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
}