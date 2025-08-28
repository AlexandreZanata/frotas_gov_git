document.addEventListener('DOMContentLoaded', () => {

    // --- FUNÇÕES DE MÁSCARA ---

    // Função para aplicar máscara de CPF: XXX.XXX.XXX-XX
    const applyCpfMask = (inputField) => {
        if (!inputField) return;
        let value = inputField.value.replace(/\D/g, ''); // Remove tudo que não for dígito
        value = value.slice(0, 11); // Limita a 11 dígitos
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        inputField.value = value;
    };

    // Função para aplicar máscara de Telefone: (XX) XXXXX-XXXX
    const applyPhoneMask = (inputField) => {
        if (!inputField) return;
        let value = inputField.value.replace(/\D/g, '');
        value = value.slice(0, 11);
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        inputField.value = value;
    };

    // Função para aplicar máscara de CNH (apenas números)
    const applyCnhMask = (inputField) => {
        if (!inputField) return;
        let value = inputField.value.replace(/\D/g, ''); // Remove tudo que não for dígito
        value = value.slice(0, 11); // Limita a 11 dígitos (padrão brasileiro)
        inputField.value = value;
    };

    // --- FORMATAÇÃO DE NOME COMPLETO (CAPITALIZAÇÃO) ---
    const nameFields = document.querySelectorAll('input[name="name"]');
    nameFields.forEach(field => {
        field.addEventListener('input', () => {
            field.value = field.value.toLowerCase().replace(/(?:^|\s)\S/g, char => char.toUpperCase());
        });
    });

    // --- VALIDAÇÃO DE NOME E SOBRENOME (MÍNIMO DE 2 PALAVRAS) ---
    const userForms = document.querySelectorAll('form[action*="/store"]'); 
    userForms.forEach(form => {
        form.addEventListener('submit', (event) => {
            const currentNameField = form.querySelector('input[name="name"]');
            if (currentNameField) {
                const words = currentNameField.value.trim().split(/\s+/);
                if (words.length < 2) {
                    event.preventDefault();
                    alert('Por favor, insira o nome completo (nome e sobrenome).');
                    currentNameField.focus();
                }
            }
        });
    });

    // --- APLICAÇÃO DAS MÁSCARAS NOS CAMPOS ---

    // 1. LÓGICA INTELIGENTE PARA O CAMPO DE LOGIN (Email ou CPF)
    const loginField = document.querySelector('input[name="login"]');
    if (loginField) {
        loginField.addEventListener('input', () => {
            if (!loginField.value.includes('@') && !/[a-zA-Z]/.test(loginField.value)) {
                applyCpfMask(loginField);
            }
        });
    }

    // 2. MÁSCARA DEDICADA PARA CAMPOS DE CPF
    const cpfFields = document.querySelectorAll('input[name="cpf"]');
    cpfFields.forEach(field => {
        field.addEventListener('input', () => applyCpfMask(field));
    });

    // 3. MÁSCARA DEDICADA PARA CAMPOS DE TELEFONE (CORRIGIDO)
    const phoneFields = document.querySelectorAll('input[name="phone"]');
    phoneFields.forEach(field => {
        field.addEventListener('input', () => applyPhoneMask(field));
    });
    
    // 4. MÁSCARA DEDICADA PARA CAMPOS DE CNH (CORRIGIDO)
    const cnhFields = document.querySelectorAll('input[name="cnh_number"]');
    cnhFields.forEach(field => {
        field.addEventListener('input', () => applyCnhMask(field));
    });

    // 5. VISIBILIDADE DA SENHA ("OLHO")
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const passwordField = toggle.closest('.password-wrapper').querySelector('input');
            const use = toggle.querySelector('use');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                use.setAttribute('xlink:href', '#icon-eye-off');
            } else {
                passwordField.type = 'password';
                use.setAttribute('xlink:href', '#icon-eye');
            }
        });
    });
});


