<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';

class VehicleCategoryController
{
    private $conn;

    public function __construct()
    {
        Auth::checkAuthentication();
        if ($_SESSION['user_role_id'] != 1) { // Apenas Admin (role 1) pode gerenciar
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Exibe o painel de gerenciamento de categorias.
     */
    public function index()
    {
        $stmt = $this->conn->query("SELECT * FROM vehicle_categories ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = ['categories' => $categories];
        
        extract($data);
        require_once __DIR__ . '/../../templates/pages/sector_manager/manage_categories.php';
    }

    /**
     * Salva ou atualiza uma categoria.
     */
    public function store()
    {
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name']);
        $oil_change_km = filter_input(INPUT_POST, 'oil_change_km', FILTER_VALIDATE_INT);
        $oil_change_days = filter_input(INPUT_POST, 'oil_change_days', FILTER_VALIDATE_INT);

        if (empty($name) || !$oil_change_km || !$oil_change_days) {
            $_SESSION['error_message'] = "Todos os campos são obrigatórios.";
            header('Location: ' . BASE_URL . '/sector-manager/categories');
            exit();
        }

        try {
            if ($categoryId) {
                // Atualiza
                $stmt = $this->conn->prepare(
                    "UPDATE vehicle_categories SET name = ?, oil_change_km = ?, oil_change_days = ? WHERE id = ?"
                );
                $stmt->execute([$name, $oil_change_km, $oil_change_days, $categoryId]);
                $_SESSION['success_message'] = "Categoria atualizada com sucesso!";
            } else {
                // Insere
                $stmt = $this->conn->prepare(
                    "INSERT INTO vehicle_categories (name, oil_change_km, oil_change_days) VALUES (?, ?, ?)"
                );
                $stmt->execute([$name, $oil_change_km, $oil_change_days]);
                $_SESSION['success_message'] = "Nova categoria criada com sucesso!";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erro ao salvar a categoria: " . $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . '/sector-manager/categories');
        exit();
    }

    /**
     * Exclui uma categoria.
     */
    public function delete()
    {
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        if (!$categoryId) {
            $_SESSION['error_message'] = "ID da categoria inválido.";
            header('Location: ' . BASE_URL . '/sector-manager/categories');
            exit();
        }

        try {
            // Verifica se a categoria está em uso antes de excluir
            $stmt_check = $this->conn->prepare("SELECT COUNT(*) FROM vehicles WHERE category_id = ?");
            $stmt_check->execute([$categoryId]);
            if ($stmt_check->fetchColumn() > 0) {
                 $_SESSION['error_message'] = "Não é possível excluir. Esta categoria está associada a um ou mais veículos.";
                 header('Location: ' . BASE_URL . '/sector-manager/categories');
                 exit();
            }

            $stmt = $this->conn->prepare("DELETE FROM vehicle_categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $_SESSION['success_message'] = "Categoria excluída com sucesso!";

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erro ao excluir a categoria: " . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/sector-manager/categories');
        exit();
    }
}