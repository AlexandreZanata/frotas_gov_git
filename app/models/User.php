<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../core/Database.php';

class User
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Encontra um usuário pelo seu ID.
     * @param int $id O ID do usuário.
     * @return mixed Retorna os dados do usuário se encontrado, caso contrário, false.
     */
    public function findById($id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Encontra um usuário pelo seu e-mail.
     * @param string $email O e-mail a ser pesquisado.
     * @return mixed Retorna os dados do usuário se encontrado, caso contrário, false.
     */
    public function findByEmail($email)
    {
        try {
            $stmt = $this->conn->prepare("SELECT id, email FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Encontra um usuário pelo seu CPF.
     * @param string $cpf O CPF a ser pesquisado.
     * @return mixed Retorna os dados do usuário se encontrado, caso contrário, false.
     */
    public function findByCpf($cpf)
    {
        try {
            $stmt = $this->conn->prepare("SELECT id, cpf FROM users WHERE cpf = :cpf");
            $stmt->execute(['cpf' => $cpf]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna todos os usuários de uma secretaria específica, com o nome do cargo.
     * @param int $secretariatId O ID da secretaria.
     * @return array Uma lista de usuários.
     */
    public function getUsersBySecretariat($secretariatId)
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT u.*, r.name as role_name 
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 WHERE u.secretariat_id = :secretariat_id
                 ORDER BY u.name ASC"
            );
            $stmt->execute(['secretariat_id' => $secretariatId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Retorna todos os cargos (roles).
     * @return array Uma lista de cargos.
     */
    public function getRoles()
    {
        try {
            $stmt = $this->conn->query("SELECT id, name, description FROM roles ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Cria um novo usuário no banco de dados.
     * @param array $data Um array associativo com os dados do usuário.
     * @return bool Retorna true em caso de sucesso, false em caso de falha.
     */
    public function create($data)
    {
        $sql = "INSERT INTO users (
                    name, cpf, email, password, role_id, secretariat_id, 
                    department_id, cnh_number, cnh_expiry_date, phone, 
                    profile_photo_path, status
                ) VALUES (
                    :name, :cpf, :email, :password, :role_id, :secretariat_id, 
                    :department_id, :cnh_number, :cnh_expiry_date, :phone, 
                    :profile_photo_path, :status
                )";

        try {
            $stmt = $this->conn->prepare($sql);
            
            // Bind dos parâmetros
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':cpf', $data['cpf']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':password', $data['password']);
            $stmt->bindValue(':role_id', $data['role_id'], PDO::PARAM_INT);
            $stmt->bindValue(':secretariat_id', $data['secretariat_id'], PDO::PARAM_INT);
            $stmt->bindValue(':department_id', $data['department_id'], PDO::PARAM_INT);
            $stmt->bindValue(':cnh_number', $data['cnh_number']);
            $stmt->bindValue(':cnh_expiry_date', $data['cnh_expiry_date']);
            $stmt->bindValue(':phone', $data['phone']);
            $stmt->bindValue(':profile_photo_path', $data['profile_photo_path']);
            $stmt->bindValue(':status', $data['status']);

            return $stmt->execute();

        } catch (PDOException $e) {
            // Em um sistema de produção, você deve logar este erro.
            // error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza os dados de um usuário.
     * @param int $id O ID do usuário a ser atualizado.
     * @param array $data Os dados a serem atualizados.
     * @return bool Retorna true em caso de sucesso, false em caso de falha.
     */
    public function update($id, $data)
    {
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "$key = :$key";
        }
        $fieldString = implode(', ', $fields);

        $sql = "UPDATE users SET $fieldString WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($sql);
            $data['id'] = $id;
            return $stmt->execute($data);
        } catch (PDOException $e) {
            // error_log($e->getMessage()); // Descomente para depuração
            return false;
        }
    }

    /**
     * Exclui um usuário do banco de dados.
     * @param int $id O ID do usuário a ser excluído.
     * @return bool Retorna true em caso de sucesso, false em caso de falha.
     */
    public function delete($id)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}