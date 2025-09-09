document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const sidebar = document.querySelector('.sidebar');
    const sidebarNav = document.querySelector('.sidebar-nav');
    const overlay = document.querySelector('.overlay');
    const menuToggle = document.getElementById('menu-toggle');
    const desktopMenuToggle = document.getElementById('desktop-menu-toggle');
    const mql = window.matchMedia('(max-width: 768px)');

    // Impedir o efeito de "piscar" na sidebar ao carregar a página
    // Aplicamos classe no body que será removida somente após posicionar o scroll
    body.classList.add('sidebar-loading');
    
    // --- FUNÇÕES PARA CONTROLE DA SIDEBAR ---
    
    // Função para fechar o sidebar no mobile
    const closeSidebar = () => {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
    };

    // Função para abrir o sidebar no mobile
    const openSidebar = () => {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
    };
    
    // Função para alternar o estado da sidebar no desktop
    const toggleDesktopSidebar = () => {
        body.classList.toggle('sidebar-collapsed');
        const isCollapsed = body.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', String(isCollapsed));
    };

    // --- RESTAURAR ESTADOS SALVOS ---
    
    // Restaurar estado de colapso da sidebar no desktop
    const savedCollapseState = localStorage.getItem('sidebarCollapsed');
    if (savedCollapseState === 'true' && !mql.matches) {
        body.classList.add('sidebar-collapsed');
    }
    
    // Restaurar a posição de scroll imediatamente (antes mesmo do DOMContentLoaded completo)
    if (sidebarNav) {
        // Usando requestAnimationFrame para garantir que a restauração ocorra no próximo frame de pintura
        requestAnimationFrame(() => {
            const savedScrollPosition = localStorage.getItem('sidebarScrollPosition');
            if (savedScrollPosition) {
                sidebarNav.scrollTop = parseInt(savedScrollPosition, 10);
            }
            // Removemos a classe de carregamento somente após posicionar o scroll
            setTimeout(() => body.classList.remove('sidebar-loading'), 50);
        });
        
        // Salvar a posição de scroll quando o usuário rolar a sidebar
        sidebarNav.addEventListener('scroll', () => {
            localStorage.setItem('sidebarScrollPosition', sidebarNav.scrollTop.toString());
        });
    } else {
        // Se não encontrou o sidebarNav, remova a classe de carregamento de qualquer maneira
        body.classList.remove('sidebar-loading');
    }

    // --- EVENT LISTENERS ---
    
    // Evento para o botão de menu no mobile
    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }
    
    // Evento para o botão de menu no desktop
    if (desktopMenuToggle) {
        desktopMenuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDesktopSidebar();
        });
    }
    
    // Evento para o overlay (fechar ao clicar fora)
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Fechar sidebar ao pressionar ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
    
    // Adaptar para mudanças de tamanho da tela
    mql.addEventListener('change', (e) => {
        if (!e.matches && body.classList.contains('sidebar-open')) {
            body.classList.remove('sidebar-open');
        }
    });
});