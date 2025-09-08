<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../core/Database.php';

class OilStockController
{
    private $conn;

    public function __construct()
    {
        Auth::checkAuthentication();
        // Apenas Admin (role 1) pode gerenciar o estoque
        if ($_SESSION['user_role_id'] != 1) {
            show_error_page('Acesso Negado', 'Você não tem permissão para acessar esta página.', 403);
        }
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Exibe o painel de gerenciamento de estoque de óleo.
     */
    public function index()
    {
        $stmt = $this->conn->prepare(
            "SELECT op.*, s.name as secretariat_name 
             FROM oil_products op
             LEFT JOIN secretariats s ON op.secretariat_id = s.id
             ORDER BY op.name ASC"
        );
        $stmt->execute();
        $oil_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $secretariats = $this->conn->query("SELECT id, name FROM secretariats ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'oil_products' => $oil_products,
            'secretariats' => $secretariats
        ];
        
        extract($data);
        require_once __DIR__ . '/../../templates/pages/sector_manager/oil_stock_panel.php';
    }

    /**
     * Salva um novo produto de óleo ou atualiza um existente.
     */
    public function store()
    {
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name']);
        $brand = trim($_POST['brand']);
        $stock = filter_var($_POST['stock_liters'], FILTER_VALIDATE_FLOAT);
        $cost = filter_var($_POST['cost_per_liter'], FILTER_VALIDATE_FLOAT);
        // Se 'secretariat_id' for vazio, define como NULL para ser um produto global
        $secretariatId = !empty($_POST['secretariat_id']) ? filter_var($_POST['secretariat_id'], FILTER_VALIDATE_INT) : null;

        if (empty($name) || $stock === false || $cost === false) {
             $_SESSION['error_message'] = "Nome, estoque e custo são obrigatórios.";
             header('Location: ' . BASE_URL . '/sector-manager/oil-stock');
             exit();
        }

        try {
            if ($productId) {
                // Atualiza
                $stmt = $this->conn->prepare(
                    "UPDATE oil_products 
                     SET name = ?, brand = ?, stock_liters = ?, cost_per_liter = ?, secretariat_id = ?
                     WHERE id = ?"
                );
                $stmt->execute([$name, $brand, $stock, $cost, $secretariatId, $productId]);
                $_SESSION['success_message'] = "Produto atualizado com sucesso!";
            } else {
                // Insere
                $stmt = $this->conn->prepare(
                    "INSERT INTO oil_products (name, brand, stock_liters, cost_per_liter, secretariat_id) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$name, $brand, $stock, $cost, $secretariatId]);
                 $_SESSION['success_message'] = "Novo produto de óleo cadastrado com sucesso!";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erro ao salvar o produto: " . $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . '/sector-manager/oil-stock');
        exit();
    }

    /**
     * Exclui um produto de óleo do banco de dados.
     */
    public function delete()
    {
        // Apenas Admin (role 1) pode excluir
        if ($_SESSION['user_role_id'] != 1) {
            $_SESSION['error_message'] = "Acesso negado.";
            header('Location: ' . BASE_URL . '/sector-manager/oil-stock');
            exit();
        }

        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

        if (!$productId) {
            $_SESSION['error_message'] = "ID do produto inválido.";
            header('Location: ' . BASE_URL . '/sector-manager/oil-stock');
            exit();
        }

        try {
            // Verifica se o produto não está sendo utilizado em algum registro de troca de óleo
            $stmt_check = $this->conn->prepare("SELECT COUNT(*) FROM oil_change_logs WHERE oil_product_id = ?");
            $stmt_check->execute([$productId]);
            if ($stmt_check->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Não é possível excluir este produto, pois ele já está associado a registros de troca de óleo. Considere zerar o estoque.";
                header('Location: ' . BASE_URL . '/sector-manager/oil-stock');
                exit();
            }

            $stmt = $this->conn->prepare("DELETE FROM oil_products WHERE id = ?");
            $stmt->execute([$productId]);
            
            $_SESSION['success_message'] = "Produto excluído com sucesso!";

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erro ao excluir o produto: " . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/sector-manager/oil-stock');
        exit();
    }
}