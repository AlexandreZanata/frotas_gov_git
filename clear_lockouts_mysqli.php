<?php
// Configurações do banco de dados
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'frotas_gov';

echo "Iniciando limpeza de bloqueios de login...\n";

// Criar conexão
$conn = new mysqli($host, $user, $pass, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error . "\n");
}

try {
    // 1. Limpa a tabela login_lockouts (remove todos os bloqueios ativos)
    $result = $conn->query("TRUNCATE TABLE login_lockouts");
    if ($result) {
        echo "✓ Bloqueios ativos removidos com sucesso!\n";
    } else {
        echo "Erro ao limpar bloqueios: " . $conn->error . "\n";
    }
    
    // 2. Limpa a tabela login_attempts
    $result = $conn->query("TRUNCATE TABLE login_attempts");
    if ($result) {
        echo "✓ Histórico de tentativas de login limpo!\n";
    } else {
        echo "Erro ao limpar tentativas de login: " . $conn->error . "\n";
    }
    
    // 3. Limpa a tabela failed_logins
    $result = $conn->query("TRUNCATE TABLE failed_logins");
    if ($result) {
        echo "✓ Histórico de falhas de login limpo!\n";
    } else {
        echo "Erro ao limpar falhas de login: " . $conn->error . "\n";
    }
    
    echo "\nTodos os bloqueios foram removidos. Agora você pode tentar fazer login novamente.\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
} finally {
    // Fecha a conexão
    $conn->close();
}
