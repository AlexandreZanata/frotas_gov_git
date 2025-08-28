<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class Sanitize
{
    /**
     * Limpa uma string para ser exibida com segurança em HTML.
     * @param string $data O dado a ser limpo.
     * @return string O dado sanitizado.
     */
    public static function output($data)
    {
        return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
    }
}