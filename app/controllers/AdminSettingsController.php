<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AuditLog.php';

class AdminSettingsController
{
    private $conn;
    private $auditLog;

    public function __construct()
    {
        Auth::checkAuthentication();
        // Apenas Admin Geral (role_id = 1) pode acessar
        if ($_SESSION['user_role_id'] != 1) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auditLog = new AuditLog($this->conn);
    }

    public function index()
    {
        $stmt = $this->conn->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $data = ['settings' => $settings];
        extract($data);
        require_once __DIR__ . '/../../templates/pages/admin/settings.php';
    }

    public function update()
    {
        try {
            $this->conn->beginTransaction();
            
            $stmt_old = $this->conn->query("SELECT setting_key, setting_value FROM system_settings");
            $oldSettings = $stmt_old->fetchAll(PDO::FETCH_KEY_PAIR);

            $stmt = $this->conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");

            foreach ($_POST['settings'] as $key => $value) {
                // Sanitiza para garantir que apenas chaves permitidas sejam salvas
                if (str_starts_with($key, 'oil_')) {
                    $stmt->execute([trim($value), $key]);
                }
            }
            
            $this->auditLog->log($_SESSION['user_id'], 'update_system_settings', 'system_settings', 1, $oldSettings, $_POST['settings']);
            $this->conn->commit();
            
            $_SESSION['success_message'] = "Configurações salvas com sucesso!";
        } catch (Exception $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Erro ao salvar configurações: " . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/admin/settings');
        exit();
    }
}