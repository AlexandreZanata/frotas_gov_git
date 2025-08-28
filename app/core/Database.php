<?php

// Previne o acesso direto ao arquivo
if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class Database
{
    // Parâmetros de conexão com o banco de dados
    // Eles são obtidos do arquivo de configuração
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        // Carrega as credenciais do arquivo de configuração
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }

    /**
     * Cria e retorna uma conexão PDO com o banco de dados.
     * @return PDO|null Retorna o objeto PDO em caso de sucesso ou null em caso de falha.
     */
    public function getConnection()
    {
        // Limpa qualquer conexão anterior
        $this->conn = null;

        // DSN (Data Source Name) - String de conexão para o PDO
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';

        // Opções para a conexão PDO
        $options = [
            // Lança exceções em caso de erro, o que é mais fácil de tratar
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Retorna os resultados como arrays associativos (ex: $row['name'])
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Desativa a emulação de prepared statements, usando o modo nativo do MySQL
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            // Tenta criar uma nova instância da classe PDO para conectar
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Se a conexão falhar, exibe uma mensagem de erro genérica
            // Em um ambiente de produção, você deveria registrar esse erro em um log
            echo 'Erro de Conexão: ' . $e->getMessage();
            return null;
        }

        // Retorna o objeto de conexão
        return $this->conn;
    }
}