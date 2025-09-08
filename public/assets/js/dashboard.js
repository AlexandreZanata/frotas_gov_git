// public/assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    const sidebarNav = document.querySelector('.sidebar-nav');

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

    // NOVA FUNCIONALIDADE: Persistência da posição de scroll da sidebar
    if (sidebarNav) {
        // Restaurar a posição do scroll quando a página carrega
        const savedScrollPosition = localStorage.getItem('sidebarScrollPosition');
        if (savedScrollPosition) {
            sidebarNav.scrollTop = parseInt(savedScrollPosition);
        }

        // Salvar a posição do scroll quando o usuário rolar a sidebar
        sidebarNav.addEventListener('scroll', () => {
            localStorage.setItem('sidebarScrollPosition', sidebarNav.scrollTop.toString());
        });
    }

    // Adicionar funcionalidade para o botão de toggle do desktop (se existir)
    const desktopMenuToggle = document.getElementById('desktop-menu-toggle');
    if (desktopMenuToggle) {
        desktopMenuToggle.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
            
            // Salvar o estado de colapso da sidebar
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed.toString());
        });
        
        // Restaurar o estado de colapso da sidebar
        const savedCollapseState = localStorage.getItem('sidebarCollapsed');
        if (savedCollapseState === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
    }
});