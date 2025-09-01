document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA DOS ATALHOS DE DATA ---
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput && endDateInput) {
        endDateInput.value = new Date().toISOString().split('T')[0];
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        startDateInput.value = thirtyDaysAgo.toISOString().split('T')[0];

        document.querySelectorAll('.btn-shortcut').forEach(button => {
            button.addEventListener('click', function() {
                const days = parseInt(this.dataset.days, 10);
                const today = new Date();
                const pastDate = new Date();
                pastDate.setDate(today.getDate() - (days - 1));
                
                endDateInput.value = today.toISOString().split('T')[0];
                startDateInput.value = pastDate.toISOString().split('T')[0];
            });
        });
    }

    // --- LÓGICA DO AUTOCOMPLETAR ---

    /**
     * Função genérica para configurar um campo de busca com autocompletar.
     * @param {string} inputId - ID do campo de texto para busca.
     * @param {string} resultsId - ID do container para exibir os resultados.
     * @param {string} hiddenId - ID do campo hidden que armazenará o ID do item selecionado.
     * @param {string} url - URL do endpoint AJAX para a busca.
     * @param {function} displayFormatter - Função que formata como cada item será exibido na lista.
     */
    const setupSearchInput = (inputId, resultsId, hiddenId, url, displayFormatter) => {
        const searchInput = document.getElementById(inputId);
        const resultsDiv = document.getElementById(resultsId);
        const hiddenInput = document.getElementById(hiddenId);
        if (!searchInput) return;

        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const term = searchInput.value.trim();
            
            // Limpa o valor selecionado se o usuário apagar o texto
            if (term === '') {
                hiddenInput.value = '';
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
                return;
            }

            if (term.length < 2) return;

            debounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(`${url}?term=${encodeURIComponent(term)}`);
                    const result = await response.json();
                    
                    if (result && result.success && Array.isArray(result.data)) {
                        if (result.data.length === 0) {
                            resultsDiv.innerHTML = '<div>Nenhum resultado encontrado</div>';
                        } else {
                            resultsDiv.innerHTML = result.data.map(item => {
                                const displayText = displayFormatter(item);
                                return `<div data-id="${item.id}" data-text="${displayText}">${displayText}</div>`;
                            }).join('');
                        }
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div>Erro na busca</div>';
                        resultsDiv.style.display = 'block';
                    }
                } catch (error) {
                    console.error(`Erro na busca (${url}):`, error);
                    resultsDiv.innerHTML = '<div>Erro de comunicação</div>';
                    resultsDiv.style.display = 'block';
                }
            }, 300);
        });

        resultsDiv.addEventListener('click', (e) => {
            const itemDiv = e.target.closest('div[data-id]');
            if (!itemDiv) return;
            
            hiddenInput.value = itemDiv.dataset.id;
            searchInput.value = itemDiv.dataset.text;
            resultsDiv.style.display = 'none';
        });

        // Oculta os resultados ao clicar fora
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });
    };

    // Configura o campo de busca de usuários
    // Reutiliza o endpoint 'ajax/search-drivers' que busca usuários por nome/CPF na secretaria
    setupSearchInput(
        'user_search',
        'user_search_results',
        'user_id',
        `${BASE_URL}/sector-manager/ajax/search-drivers`,
        (item) => `${item.name} - ${item.cpf}`
    );

    // Configura o campo de busca de veículos
    // Reutiliza o endpoint 'ajax/search-vehicles-for-run' que busca veículos por prefixo/placa
    setupSearchInput(
        'vehicle_search',
        'vehicle_search_results',
        'vehicle_id',
        `${BASE_URL}/sector-manager/ajax/search-vehicles-for-run`,
        (item) => `${item.prefix} - ${item.plate}`
    );
});