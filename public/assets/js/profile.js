document.addEventListener('DOMContentLoaded', () => {
    // Variáveis globais
    let currentCropper = null;
    let currentEditField = null;
    const modal = document.getElementById('cropperModal');
    const modalImage = document.getElementById('cropper-image');
    const modalTitle = document.getElementById('modal-title');
    
    // Função para formatar números de telefone já existentes ao carregar a página
    function formatExistingPhone() {
        const phoneField = document.getElementById('phone');
        if (phoneField && phoneField.value) {
            const phone = phoneField.value.replace(/\D/g, '');
            if (phone.length > 0) {
                let formatted = phone;
                if (phone.length <= 11) {  // Formatação brasileira
                    formatted = phone.replace(/^(\d{2})(\d)/g, '($1) $2');
                    formatted = formatted.replace(/(\d{5})(\d)/, '$1-$2');
                }
                phoneField.value = formatted;
            }
        }
    }
    
    // Função para formatar números de CNH já existentes ao carregar a página
    function formatExistingCNH() {
        const cnhField = document.getElementById('cnh_number');
        if (cnhField && cnhField.value) {
            // Mantém apenas os dígitos para CNH
            const cnh = cnhField.value.replace(/\D/g, '');
            // Insere espaços a cada grupo de 3 dígitos para melhor legibilidade
            cnhField.value = cnh.replace(/(\d{3})(?=\d)/g, '$1 ');
        }
    }
    
    // Aplica as formatações existentes ao carregar a página
    formatExistingPhone();
    formatExistingCNH();
    
    // Monitora os campos para aplicar formatação enquanto digita
    if (document.getElementById('phone')) {
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.substring(0, 11); // Limita a 11 dígitos
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                this.value = value;
            }
        });
    }
    
    if (document.getElementById('cnh_number')) {
        document.getElementById('cnh_number').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            value = value.substring(0, 11); // CNH tem 11 dígitos no Brasil
            // Insere espaços a cada grupo de 3 dígitos para melhor legibilidade
            this.value = value.replace(/(\d{3})(?=\d)/g, '$1 ');
        });
    }
    
    // Função para configurar a pré-visualização de imagem
    function setupImagePreview(inputId, previewId) {
        const fileInput = document.getElementById(inputId);
        const previewContainer = document.getElementById(previewId);

        if (fileInput && previewContainer) {
            const previewImage = previewContainer.querySelector('img');
            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    // Configuração do Editor de Imagem (Cropper.js)
    function setupImageCropper() {
        // Botões de edição de imagem
        const editProfileBtn = document.getElementById('edit_profile_photo');
        const editCnhBtn = document.getElementById('edit_cnh_photo');
        const closeModalBtn = document.querySelector('.close-modal');
        const rotateLeftBtn = document.getElementById('rotate-left');
        const flipHorizontalBtn = document.getElementById('flip-horizontal');
        const resetCropBtn = document.getElementById('reset-crop');
        const saveCropBtn = document.getElementById('save-crop');
        
        // Função para abrir o modal com a imagem para edição
        function openCropperModal(imageSource, fieldId) {
            // Definir a imagem no modal e o campo atual sendo editado
            modalImage.src = imageSource;
            currentEditField = fieldId;
            
            // Ajustar o título do modal
            modalTitle.textContent = fieldId === 'profile_photo' ? 
                'Editar Foto de Perfil' : 'Editar Foto da CNH';
                
            // Mostrar o modal
            modal.style.display = 'block';
            
            // Inicializar o Cropper.js após um pequeno delay para garantir que a imagem esteja carregada
            setTimeout(() => {
                if (currentCropper) {
                    currentCropper.destroy();
                }
                
                // Opções do Cropper diferentes para cada tipo de imagem
                const options = fieldId === 'profile_photo' ? 
                    { aspectRatio: 1, viewMode: 1 } : // Proporção quadrada para foto de perfil
                    { aspectRatio: 16/9, viewMode: 1 }; // Proporção retangular para CNH
                    
                currentCropper = new Cropper(modalImage, options);
            }, 100);
        }
        
        // Configurar os botões de edição
        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', () => {
                const previewImg = document.querySelector('#profile_photo_preview img');
                openCropperModal(previewImg.src, 'profile_photo');
            });
        }
        
        if (editCnhBtn) {
            editCnhBtn.addEventListener('click', () => {
                const previewImg = document.querySelector('#cnh_photo_preview img');
                openCropperModal(previewImg.src, 'cnh_photo');
            });
        }
        
        // Fechar o modal
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                if (currentCropper) {
                    currentCropper.destroy();
                    currentCropper = null;
                }
                modal.style.display = 'none';
            });
        }
        
        // Também fecha ao clicar fora do modal
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                if (currentCropper) {
                    currentCropper.destroy();
                    currentCropper = null;
                }
                modal.style.display = 'none';
            }
        });
        
        // Botões de manipulação da imagem
        if (rotateLeftBtn) {
            rotateLeftBtn.addEventListener('click', () => {
                if (currentCropper) {
                    currentCropper.rotate(-90);
                }
            });
        }
        
        if (flipHorizontalBtn) {
            flipHorizontalBtn.addEventListener('click', () => {
                if (currentCropper) {
                    currentCropper.scaleX(currentCropper.getData().scaleX * -1);
                }
            });
        }
        
        if (resetCropBtn) {
            resetCropBtn.addEventListener('click', () => {
                if (currentCropper) {
                    currentCropper.reset();
                }
            });
        }
        
        if (saveCropBtn) {
            saveCropBtn.addEventListener('click', () => {
                if (currentCropper) {
                    // Obter a imagem recortada como data URL (base64)
                    const croppedCanvas = currentCropper.getCroppedCanvas({
                        width: currentEditField === 'profile_photo' ? 180 : 240,
                        height: currentEditField === 'profile_photo' ? 180 : 150
                    });
                    
                    const croppedImage = croppedCanvas.toDataURL('image/png');
                    
                    // Atualizar a pré-visualização
                    const previewImg = document.querySelector(`#${currentEditField}_preview img`);
                    previewImg.src = croppedImage;
                    
                    // Armazenar a imagem recortada no campo oculto apropriado
                    const hiddenField = document.getElementById(currentEditField === 'profile_photo' ? 
                        'cropped_profile_data' : 'cropped_cnh_data');
                        
                    hiddenField.value = croppedImage;
                    
                    // Fechar o modal
                    currentCropper.destroy();
                    currentCropper = null;
                    modal.style.display = 'none';
                }
            });
        }
    }

    // Aplica as funções para ambos os campos de upload
    setupImagePreview('profile_photo', 'profile_photo_preview');
    setupImagePreview('cnh_photo', 'cnh_photo_preview');
    
    // Inicializa o editor de imagem
    setupImageCropper();

    // Validação de confirmação de senha no lado do cliente
    const passwordForm = document.querySelector('form[action*="change-password"]');
    if(passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                alert('A nova senha e a confirmação não correspondem.');
                e.preventDefault(); // Impede o envio do formulário
            }
        });
    }
    
    // Configuração dos botões de visibilidade de senha
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const passwordField = toggle.closest('.password-wrapper').querySelector('input');
            const icon = toggle.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});