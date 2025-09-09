<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Meu Perfil - Frotas Gov</title>

    <!-- CSS específicos da página -->
         <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/profile.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css" />



    <!-- Ícones e libs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" />
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <div class="overlay"></div>


    <main class="main-content">




        <div class="content-body">
            <?php
            if (isset($_SESSION['success_message'])) {
                echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                unset($_SESSION['success_message']);
            }
            if (isset($_SESSION['error_message'])) {
                echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>

            <div class="profile-section">
                <h2 class="section-title">Informações do Perfil</h2>
                <form action="<?php echo BASE_URL; ?>/profile/update" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <!-- Campos ocultos para armazenar imagens recortadas -->
                    <input type="hidden" id="cropped_profile_data" name="cropped_profile_data">
                    <input type="hidden" id="cropped_cnh_data" name="cropped_cnh_data">

                    <div class="profile-grid">
                        <div class="photo-upload-area">
                            <div class="photo-upload-group">
                                <label>Foto de Perfil</label>
                                <div class="image-preview" id="profile_photo_preview">
                                    <img src="<?php echo $user['profile_photo_path'] ? BASE_URL . '/' . htmlspecialchars($user['profile_photo_path']) : 'https://via.placeholder.com/180'; ?>" alt="Foto de Perfil">
                                </div>
                                <input type="file" name="profile_photo" id="profile_photo" class="file-input" accept="image/*">
                                <label for="profile_photo" class="btn-upload"><i class="fas fa-upload"></i> Alterar Foto</label>
                                <button type="button" id="edit_profile_photo" class="btn-upload" style="margin-top: 0.5rem;"><i class="fas fa-crop"></i> Editar Foto</button>
                            </div>
                            <div class="photo-upload-group">
                                <label>Foto da CNH</label>
                                <div class="image-preview" id="cnh_photo_preview">
                                    <img src="<?php echo $user['cnh_photo_path'] ? BASE_URL . '/' . htmlspecialchars($user['cnh_photo_path']) : 'https://via.placeholder.com/240x150'; ?>" alt="Foto da CNH">
                                </div>
                                <input type="file" name="cnh_photo" id="cnh_photo" class="file-input" accept="image/*">
                                <label for="cnh_photo" class="btn-upload"><i class="fas fa-upload"></i> Alterar CNH</label>
                                <button type="button" id="edit_cnh_photo" class="btn-upload" style="margin-top: 0.5rem;"><i class="fas fa-crop"></i> Editar Foto</button>
                            </div>
                        </div>

                        <div class="info-fields-area">
                            <div class="form-group">
                                <label for="name">Nome Completo</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">E-mail</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Telefone / Celular</label>
                                <input type="tel" id="phone" name="phone" placeholder="(XX) XXXXX-XXXX" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cnh_number">Nº da CNH</label>
                                <input type="text" id="cnh_number" name="cnh_number" value="<?php echo htmlspecialchars($user['cnh_number'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cnh_expiry_date">Data de Validade da CNH</label>
                                <input type="date" id="cnh_expiry_date" name="cnh_expiry_date" value="<?php echo htmlspecialchars($user['cnh_expiry_date'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Salvar Alterações</button>
                    </div>
                </form>
            </div>

            <div class="profile-section">
                <h2 class="section-title">Alterar Senha</h2>
                <form action="<?php echo BASE_URL; ?>/profile/change-password" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="form-group">
                        <label for="current_password">Senha Atual</label>
                        <div class="password-wrapper">
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nova Senha (mínimo 8 caracteres)</label>
                        <div class="password-wrapper">
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                            <button type="button" class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Alterar Senha</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Modal de Edição de Imagem -->
    <div id="cropperModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 id="modal-title">Editar Imagem</h2>
            <div>
                <div id="cropper-container">
                    <img id="cropper-image" src="" alt="Imagem para recortar">
                </div>
                <div class="crop-controls">
                    <button id="rotate-left" class="crop-btn"><i class="fas fa-rotate-left"></i> Girar</button>
                    <button id="flip-horizontal" class="crop-btn"><i class="fas fa-arrows-alt-h"></i> Espelhar</button>
                    <button id="reset-crop" class="crop-btn"><i class="fas fa-sync"></i> Resetar</button>
                    <button id="save-crop" class="crop-btn"><i class="fas fa-check"></i> Aplicar</button>
                </div>
            </div>
        </div>
    </div>

    <script>const BASE_URL = "<?php echo BASE_URL; ?>";</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/form-masks-edit.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/profile.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

    <!-- Se necessários para outras funcionalidades globais -->
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js" defer></script>
    
</body>
</html>