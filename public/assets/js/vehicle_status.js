document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('vehicleSearchInput');
    const inUseGrid = document.getElementById('in-use-grid');
    const availableGrid = document.getElementById('available-grid');
    const inUseCounter = document.getElementById('in-use-counter');
    const availableCounter = document.getElementById('available-counter');
    let searchDebounce;

    // --- FUNÇÕES DE RENDERIZAÇÃO ---
    const escapeHTML = (str) => str ? String(str).replace(/[&<>"']/g, match => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[match]) : '';
    const formatDateTime = (dateString) => dateString ? new Date(dateString).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : 'N/A';
    const formatKm = (km) => km ? new Intl.NumberFormat('pt-BR').format(km) : '0';

    const createInUseCardHTML = (vehicle) => `
        <div class="vehicle-card status-in-use" data-vehicle-id="${vehicle.vehicle_id}">
            <div class="card-header"><h3>${escapeHTML(vehicle.vehicle_prefix)}</h3><span>${escapeHTML(vehicle.vehicle_plate)}</span></div>
            <div class="card-body">
                <p><strong>Modelo:</strong> ${escapeHTML(vehicle.vehicle_name)}</p>
                <p><strong>Motorista:</strong> ${escapeHTML(vehicle.driver_name || 'N/A')}</p>
                <p><strong>Destino:</strong> ${escapeHTML(vehicle.destination || 'N/A')}</p>
                <p><strong>Saída:</strong> ${formatDateTime(vehicle.start_time)}</p>
                <p class="card-km-info"><strong>KM Inicial:</strong> ${formatKm(vehicle.start_km)}</p>
            </div>
            <div class="card-footer">
                <button class="btn-force-end"><i class="fas fa-power-off"></i> Encerrar Corrida</button>
                <div class="justification-form-container">
                    <button class="close-form-btn">&times;</button>
                    <form class="justification-form" data-run-id="${vehicle.run_id}">
                        <input type="number" name="end_km" placeholder="KM Final (Opcional)" class="km-final-input" min="${vehicle.start_km || 0}">
                        <textarea name="justification" placeholder="Justificativa obrigatória..." required></textarea>
                        <button type="submit">Confirmar</button>
                    </form>
                </div>
            </div>
        </div>`;
    
    const createAvailableCardHTML = (vehicle) => `
        <div class="vehicle-card status-available" data-vehicle-id="${vehicle.vehicle_id}">
            <div class="card-header"><h3>${escapeHTML(vehicle.vehicle_prefix)}</h3><span>${escapeHTML(vehicle.vehicle_plate)}</span></div>
            <div class="card-body">
                <p><strong>Modelo:</strong> ${escapeHTML(vehicle.vehicle_name)}</p>
                <p><strong>Última Parada:</strong> ${escapeHTML(vehicle.stop_point || 'Sem registro')}</p>
                <p><strong>Disponível desde:</strong> ${formatDateTime(vehicle.end_time)}</p>
                <p class="card-km-info"><strong>Último KM:</strong> ${formatKm(vehicle.last_valid_km || vehicle.end_km)}</p>
            </div>
        </div>`;

    const renderGrids = (data) => {
        inUseGrid.innerHTML = data.inUseVehicles.length > 0 ? data.inUseVehicles.map(createInUseCardHTML).join('') : '<p class="no-vehicles-message">Nenhum veículo em uso encontrado.</p>';
        availableGrid.innerHTML = data.availableVehicles.length > 0 ? data.availableVehicles.map(createAvailableCardHTML).join('') : '<p class="no-vehicles-message">Nenhum veículo disponível encontrado.</p>';
        inUseCounter.innerHTML = `<i class="fas fa-road-circle-check icon-in-use"></i> Veículos em Uso (${data.inUseVehicles.length})`;
        availableCounter.innerHTML = `<i class="fas fa-check-circle icon-available"></i> Veículos Disponíveis (${data.availableVehicles.length})`;
    };

    // --- LÓGICA DE BUSCA (CORRIGIDA) ---
    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(async () => {
            const term = searchInput.value;
            try {
                // USA A NOVA ROTA CORRETA
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/search-vehicles-status?term=${encodeURIComponent(term)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!response.ok) { // Verifica se a resposta HTTP foi bem-sucedida
                    throw new Error(`Erro de rede: ${response.statusText}`);
                }
                const result = await response.json();
                if (result.success) {
                    renderGrids(result.data);
                } else {
                     throw new Error(result.message || "Falha ao obter dados.");
                }
            } catch (error) {
                console.error("Erro ao buscar veículos:", error);
                inUseGrid.innerHTML = `<p class="no-vehicles-message" style="color:red;">${error.message}</p>`;
            }
        }, 300);
    });

    // --- LÓGICA DE INTERAÇÃO DOS CARDS ---
    document.querySelector('.content-body').addEventListener('click', async (e) => {
        const target = e.target;
        
        // Esconde o formulário
        if (target.classList.contains('close-form-btn')) {
            target.closest('.justification-form-container').style.display = 'none';
        }
        
        // Mostra o formulário
        const endButton = target.closest('.btn-force-end');
        if (endButton) {
            e.preventDefault();
            const formContainer = endButton.nextElementSibling;
            const isVisible = formContainer.style.display === 'block';
            document.querySelectorAll('.justification-form-container').forEach(fc => fc.style.display = 'none');
            formContainer.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) formContainer.querySelector('textarea').focus();
        }

        // Submissão do formulário
        if (target.closest('.justification-form button[type="submit"]')) {
            e.preventDefault();
            const form = target.closest('.justification-form');
            const button = target.closest('button');
            button.disabled = true;
            button.textContent = '...';

            const formData = new URLSearchParams();
            formData.append('run_id', form.dataset.runId);
            formData.append('justification', form.querySelector('[name="justification"]').value);
            formData.append('end_km', form.querySelector('[name="end_km"]').value);
            formData.append('csrf_token', CSRF_TOKEN);

            try {
                const response = await fetch(`${BASE_URL}/sector-manager/vehicles/force-end-run`, { method: 'POST', body: formData });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || `Erro ${response.status}`);
                
                // Atualização em tempo real
                const cardToRemove = form.closest('.vehicle-card');
                cardToRemove.style.opacity = '0';
                setTimeout(() => {
                    cardToRemove.remove();
                     if (inUseGrid.children.length === 0) {
                        inUseGrid.innerHTML = '<p class="no-vehicles-message">Nenhum veículo em uso no momento.</p>';
                    }
                    inUseCounter.innerHTML = `<i class="fas fa-road-circle-check icon-in-use"></i> Veículos em Uso (${inUseGrid.children.length})`;
                }, 300);

                const newAvailableCardHTML = createAvailableCardHTML(result.updatedVehicle);
                if (availableGrid.querySelector('.no-vehicles-message')) {
                    availableGrid.innerHTML = newAvailableCardHTML;
                } else {
                    availableGrid.insertAdjacentHTML('afterbegin', newAvailableCardHTML);
                }
                availableCounter.innerHTML = `<i class="fas fa-check-circle icon-available"></i> Veículos Disponíveis (${availableGrid.querySelectorAll('.vehicle-card').length})`;
            } catch (error) {
                alert(`Erro: ${error.message}`);
                button.disabled = false;
                button.textContent = 'Confirmar';
            }
        }
    });
});