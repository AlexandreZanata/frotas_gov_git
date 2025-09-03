<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

require_once __DIR__ . '/../security/Auth.php';
require_once __DIR__ . '/../security/Hash.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/User.php';

class ProfileController
{
    private $conn;
    private $auditLog;
    private $userModel;

    public function __construct()
    {
        Auth::checkAuthentication();
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auditLog = new AuditLog($this->conn);
        $this->userModel = new User();
    }

    /**
     * Exibe a página de perfil do usuário logado.
     */
    public function index()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            show_error_page('Erro', 'Usuário não encontrado.', 404);
        }

        $data = [
            'user' => $user,
            'csrf_token' => $_SESSION['csrf_token']
        ];
        
        extract($data);
        $view_path = __DIR__ . '/../../templates/pages/profile/index.php';
        require_once __DIR__ . '/../../templates/layouts/internal_layout.php';
    }

    /**
     * Atualiza as informações do perfil do usuário.
     */
    public function update()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
        }

        $userId = $_SESSION['user_id'];
        $currentUser = $this->userModel->findById($userId);

        // --- VALIDAÇÃO DOS NOVOS CAMPOS EDITÁVEIS ---
        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if (empty($name) || !$email) {
            $_SESSION['error_message'] = 'O Nome Completo e o E-mail são obrigatórios e devem ser válidos.';
            header('Location: ' . BASE_URL . '/profile');
            exit();
        }

        // Verifica se o e-mail foi alterado e se o novo e-mail já está em uso
        if ($email !== $currentUser['email']) {
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser && $existingUser['id'] != $userId) {
                $_SESSION['error_message'] = 'O novo e-mail informado já está cadastrado em outra conta.';
                header('Location: ' . BASE_URL . '/profile');
                exit();
            }
        }

        // Prepara os dados para atualização no banco
        $dataToUpdate = [
            'name'            => $name,
            'email'           => $email,
            'cnh_number'      => preg_replace('/[^0-9]/', '', $_POST['cnh_number'] ?? ''),
            'cnh_expiry_date' => !empty($_POST['cnh_expiry_date']) ? $_POST['cnh_expiry_date'] : null,
            'phone'           => preg_replace('/[^0-9]/', '', $_POST['phone'] ?? ''),
        ];

        try {
            // Processa imagens recortadas se enviadas
            if (!empty($_POST['cropped_profile_data'])) {
                $newProfilePhotoPath = $this->handleCroppedImage($_POST['cropped_profile_data'], 'profile', $userId);
                if ($newProfilePhotoPath) {
                    $this->deleteOldFile($currentUser['profile_photo_path']);
                    $dataToUpdate['profile_photo_path'] = $newProfilePhotoPath;
                }
            } 
            // Se não houver imagem recortada, processa o upload normal
            else {
                $newProfilePhotoPath = $this->handleFileUpload('profile_photo', 'profile', $userId, $currentUser['profile_photo_path']);
                if ($newProfilePhotoPath) {
                    $dataToUpdate['profile_photo_path'] = $newProfilePhotoPath;
                }
            }

            // Processa a imagem recortada da CNH se enviada
            if (!empty($_POST['cropped_cnh_data'])) {
                $newCnhPhotoPath = $this->handleCroppedImage($_POST['cropped_cnh_data'], 'cnh', $userId);
                if ($newCnhPhotoPath) {
                    $this->deleteOldFile($currentUser['cnh_photo_path']);
                    $dataToUpdate['cnh_photo_path'] = $newCnhPhotoPath;
                }
            } 
            // Se não houver imagem recortada, processa o upload normal
            else {
                $newCnhPhotoPath = $this->handleFileUpload('cnh_photo', 'cnh', $userId, $currentUser['cnh_photo_path']);
                if ($newCnhPhotoPath) {
                    $dataToUpdate['cnh_photo_path'] = $newCnhPhotoPath;
                }
            }

            if ($this->userModel->update($userId, $dataToUpdate)) {
                $_SESSION['success_message'] = 'Perfil atualizado com sucesso!';
                $_SESSION['user_name'] = $name; // Atualiza o nome na sessão para refletir na interface
                $this->auditLog->log($userId, 'update_profile', 'users', $userId, $currentUser, $dataToUpdate);
            } else {
                $_SESSION['error_message'] = 'Nenhuma alteração foi feita ou ocorreu um erro.';
            }

        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Erro ao atualizar perfil: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/profile');
        exit();
    }

    /**
     * Altera a senha do usuário logado.
     */
    public function changePassword()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            show_error_page('Acesso Inválido', 'Erro de validação de segurança.', 403);
        }

        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['error_message'] = 'Todos os campos de senha são obrigatórios.';
            header('Location: ' . BASE_URL . '/profile');
            exit();
        }
        
        if (strlen($newPassword) < 8) {
            $_SESSION['error_message'] = 'A nova senha deve ter no mínimo 8 caracteres.';
            header('Location: ' . BASE_URL . '/profile');
            exit();
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error_message'] = 'A nova senha e a confirmação não correspondem.';
            header('Location: ' . BASE_URL . '/profile');
            exit();
        }

        $user = $this->userModel->findById($userId);

        if (!Hash::verify($currentPassword, $user['password'])) {
            $_SESSION['error_message'] = 'A senha atual está incorreta.';
            header('Location: ' . BASE_URL . '/profile');
            exit();
        }

        $hashedPassword = Hash::make($newPassword);
        
        if ($this->userModel->update($userId, ['password' => $hashedPassword])) {
            $_SESSION['success_message'] = 'Senha alterada com sucesso!';
            $this->auditLog->log($userId, 'change_password', 'users', $userId, null, ['password' => '******']);
        } else {
            $_SESSION['error_message'] = 'Ocorreu um erro ao alterar a senha.';
        }
        
        header('Location: ' . BASE_URL . '/profile');
        exit();
    }
    
    /**
     * Processa e salva uma imagem recortada a partir de dados base64
     */
    private function handleCroppedImage($base64Image, $uploadSubDir, $userId) 
    {
        // Remove a parte "data:image/png;base64," da string
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        
        if (!$imageData) {
            throw new Exception("Dados da imagem inválidos.");
        }
        
        $targetDir = __DIR__ . "/../../public/uploads/{$uploadSubDir}/";
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        
        // Gera um nome único para o arquivo
        $fileName = "user_{$userId}_" . uniqid() . ".png";
        $targetFile = $targetDir . $fileName;
        
        if (file_put_contents($targetFile, $imageData)) {
            return "uploads/{$uploadSubDir}/" . $fileName;
        } else {
            throw new Exception("Falha ao salvar a imagem recortada.");
        }
    }
    
    /**
     * Função auxiliar para gerenciar o upload de arquivos de forma segura.
     */
    private function handleFileUpload($fileInputName, $uploadSubDir, $userId, $oldFilePath = null)
    {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileInputName];
            $targetDir = __DIR__ . "/../../public/uploads/{$uploadSubDir}/";

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }

            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fileName = "user_{$userId}_" . uniqid() . ".{$fileExtension}";
            $targetFile = $targetDir . $fileName;

            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($fileExtension, $allowedTypes)) {
                throw new Exception("Tipo de arquivo inválido. Apenas JPG, PNG, GIF são permitidos.");
            }
            
            if ($file['size'] > 2 * 1024 * 1024) { // 2MB
                 throw new Exception("O arquivo é muito grande. O tamanho máximo é 2MB.");
            }

            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                if ($oldFilePath) {
                    $this->deleteOldFile($oldFilePath);
                }
                return "uploads/{$uploadSubDir}/" . $fileName;
            } else {
                throw new Exception("Falha ao mover o arquivo enviado.");
            }
        }
        return null;
    }
    
    /**
     * Método para excluir arquivos antigos
     */
    private function deleteOldFile($filePath)
    {
        if ($filePath) {
            $oldFullPath = __DIR__ . "/../../public/" . $filePath;
            if (file_exists($oldFullPath)) {
                @unlink($oldFullPath);
                return true;
            }
        }
        return false;
    }
}