document.addEventListener('DOMContentLoaded', () => {

    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    if (passwordToggles.length > 0) {
        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault(); // Previne qualquer comportamento padrão
                
                const passwordField = this.closest('.password-wrapper').querySelector('input');
                const icon = this.querySelector('i');
                
                console.log('Toggle senha clicado:', passwordField.id); // Log para depuração
                
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
        
        console.log('Configurados', passwordToggles.length, 'botões de visibilidade de senha'); // Log para depuração
    } else {
        console.warn('Nenhum botão de visibilidade de senha encontrado na página'); // Log para depuração
    }
});