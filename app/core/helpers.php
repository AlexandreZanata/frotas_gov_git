<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

/**
 * Exibe a página de erro padronizada.
 * Inclui o controller de erro e chama o método show.
 * @param string $title Título do erro.
 * @param string $message Mensagem detalhada.
 * @param int $httpCode Código de status HTTP.
 */
function show_error_page($title, $message, $httpCode = 400)
{
    require_once __DIR__ . '/../controllers/ErrorController.php';
    $errorController = new ErrorController();
    $errorController->show($title, $message, $httpCode);
    exit(); // Encerra o script após mostrar o erro
}