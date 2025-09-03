<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class Structure
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    public function getAllSecretariatsWithDepartments()
    {
        $sql = "SELECT id, name FROM secretariats ORDER BY name ASC";
        $stmt = $this->conn->query($sql);
        $secretariats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $deptSql = "SELECT id, name FROM departments WHERE secretariat_id = :id ORDER BY name ASC";
        $deptStmt = $this->conn->prepare($deptSql);

        foreach ($secretariats as &$secretariat) {
            $deptStmt->execute(['id' => $secretariat['id']]);
            $secretariat['departments'] = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $secretariats;
    }

    public function getSecretariatById($id) {
        $stmt = $this->conn->prepare("SELECT name FROM secretariats WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getDepartmentById($id) {
        $stmt = $this->conn->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createSecretariat($name)
    {
        $stmt = $this->conn->prepare("INSERT INTO secretariats (name) VALUES (?)");
        $stmt->execute([$name]);
        return $this->conn->lastInsertId();
    }

    public function createDepartment($name, $secretariatId)
    {
        $stmt = $this->conn->prepare("INSERT INTO departments (name, secretariat_id) VALUES (?, ?)");
        $stmt->execute([$name, $secretariatId]);
        return $this->conn->lastInsertId();
    }

    public function updateSecretariat($id, $name)
    {
        $stmt = $this->conn->prepare("UPDATE secretariats SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    }

    public function updateDepartment($id, $name)
    {
        $stmt = $this->conn->prepare("UPDATE departments SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    }

    public function deleteSecretariat($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM secretariats WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteDepartment($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM departments WHERE id = ?");
        return $stmt->execute([$id]);
    }
}