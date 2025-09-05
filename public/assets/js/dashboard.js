// public/assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');

    // Função para fechar o sidebar
    const closeSidebar = () => {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active'); // Mudei para 'active' para consistência
    };

    // Função para abrir o sidebar
    const openSidebar = () => {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
    };

    // Evento para o botão de menu
    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation(); // Impede que o evento de clique se propague para o window
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }
    
    // Evento para o overlay (fechar ao clicar fora)
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
});