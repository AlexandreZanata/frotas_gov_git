<?php
// Define a constante para permitir acesso direto ao sistema
define('SYSTEM_LOADED', true);

// Inclui os arquivos de configuração
require_once __DIR__ . '/config/database.php';

// Inclui as dependências necessárias
require_once __DIR__ . '/app/core/Database.php';

echo "Iniciando limpeza de bloqueios de login...\n";

try {
    // Cria uma conexão com o banco de dados
    $db = new Database();
    $conn = $db->getConnection();
    
    // 1. Limpa a tabela login_lockouts (remove todos os bloqueios ativos)
    $stmt = $conn->prepare("TRUNCATE TABLE login_lockouts");
    $stmt->execute();
    echo "✓ Bloqueios ativos removidos com sucesso!\n";
    
    // 2. Limpa a tabela login_attempts (opcional, mas ajuda a começar do zero)
    $stmt = $conn->prepare("TRUNCATE TABLE login_attempts");
    $stmt->execute();
    echo "✓ Histórico de tentativas de login limpo!\n";
    
    // 3. Limpa a tabela failed_logins (opcional, mas ajuda a começar do zero)
    $stmt = $conn->prepare("TRUNCATE TABLE failed_logins");
    $stmt->execute();
    echo "✓ Histórico de falhas de login limpo!\n";
    
    echo "\nTodos os bloqueios foram removidos. Agora você pode tentar fazer login novamente.\n";
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
