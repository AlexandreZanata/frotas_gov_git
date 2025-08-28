<?php

// Define uma constante para prevenir acesso direto a arquivos
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

// Define a URL base completa do projeto para links e redirecionamentos
define('BASE_URL', 'http://172.19.2.140/frotas-gov/public');

// Você pode adicionar outras configurações globais aqui no futuro
// Ex: define('SITE_NAME', 'Frotas Gov');