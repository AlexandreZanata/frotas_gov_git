<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class ErrorController
{
    /**
     * Exibe a página de erro personalizada.
     * @param string $title O título do erro (ex: Acesso Negado)
     * @param string $message A mensagem detalhada para o usuário.
     * @param int $httpCode O código de status HTTP (ex: 403, 404, 500)
     */
    public function show($title = 'Ocorreu um Erro', $message = 'Houve um problema inesperado.', $httpCode = 500)
    {
        // Define o código de resposta HTTP
        http_response_code($httpCode);

        // Disponibiliza as variáveis para a view
        $errorTitle = $title;
        $errorMessage = $message;

        // Carrega a view de erro
        require_once __DIR__ . '/../../templates/pages/error.php';
    }
}