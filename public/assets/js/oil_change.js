document.addEventListener('DOMContentLoaded', () => {
    const vehicleGrid = document.getElementById('vehicleGrid');
    const vehicleSearch = document.getElementById('vehicleSearch');
    const statusFilter = document.getElementById('statusFilter');
    const openModalBtn = document.getElementById('openRegisterModalBtn');
    const modal = document.getElementById('registerOilChangeModal');
    const closeModalBtn = modal.querySelector('.modal-close');
    const oilChangeForm = document.getElementById('oilChangeForm');

    let allVehicles = []; // Cache para os dados dos veículos

    // --- FUNÇÕES DE RENDERIZAÇÃO ---
    const renderVehicleGrid = (vehicles) => {
        if (!vehicles || vehicles.length === 0) {
            vehicleGrid.innerHTML = '<p class="loading-message">Nenhum veículo encontrado.</p>';
            return;
        }
        vehicleGrid.innerHTML = vehicles.map(v => createVehicleCard(v)).join('');
    };

    const createVehicleCard = (v) => {
        const statusMap = {
            ok: 'Em Dia',
            attention: 'Atenção',
            critical: 'Crítico',
            overdue: 'Vencido'
        };
        const currentKm = v.current_km ? parseInt(v.current_km).toLocaleString('pt-BR') : 'N/A';
        const nextKm = v.next_oil_change_km ? parseInt(v.next_oil_change_km).toLocaleString('pt-BR') : 'N/A';
        const nextDate = v.next_oil_change_date ? new Date(v.next_oil_change_date + 'T00:00:00').toLocaleDateString('pt-BR') : 'N/A';

        return `
            <div class="vehicle-card status-${v.status}" data-status="${v.status}" data-name="${v.name.toLowerCase()}" data-plate="${v.plate.toLowerCase()}" data-prefix="${v.prefix.toLowerCase()}">
                <div class="card-header">
                    <h4>${v.name} (${v.prefix})</h4>
                    <span>Placa: ${v.plate} | KM Atual: ${currentKm}</span>
                </div>
                <div class="card-body">
                    <div class="progress-group">
                        <div class="progress-label">
                            <span>Próxima Troca (KM)</span>
                            <span>${nextKm} KM</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${v.km_progress}%;"></div>
                        </div>
                    </div>
                    <div class="progress-group">
                        <div class="progress-label">
                            <span>Próxima Troca (Data)</span>
                            <span>${nextDate}</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${v.days_progress}%;"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    ${statusMap[v.status] || 'Indefinido'}
                </div>
            </div>
        `;
    };

    const updateStats = (stats) => {
        document.getElementById('totalVehiclesStat').textContent = stats.total;
        document.getElementById('statusOkStat').textContent = stats.ok;
        document.getElementById('statusAttentionStat').textContent = stats.attention;
        document.getElementById('statusCriticalStat').textContent = stats.critical + stats.overdue;
    };
    
    const renderAlerts = (alerts) => {
        const alertsSection = document.getElementById('alerts-section');
        alertsSection.innerHTML = '';
        if (alerts && alerts.length > 0) {
            alerts.forEach(alert => {
                const alertCard = document.createElement('div');
                alertCard.className = 'alert-card';
                alertCard.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <strong>Estoque Baixo:</strong> ${alert.name} (${alert.brand}) - Restam apenas ${alert.stock_liters} litros.`;
                alertsSection.appendChild(alertCard);
            });
        }
    };

    // --- LÓGICA DE DADOS ---
    const fetchData = async () => {
        try {
            const response = await fetch(`${BASE_URL}/sector-manager/oil-change/ajax_get_vehicles`);
            if (!response.ok) throw new Error('Erro ao buscar dados.');
            const data = await response.json();
            if (data.success) {
                allVehicles = data.vehicles;
                renderVehicleGrid(allVehicles);
                updateStats(data.stats);
                renderAlerts(data.alerts);
                filterVehicles(); // Aplica filtros iniciais
            }
        } catch (error) {
            vehicleGrid.innerHTML = `<p class="loading-message" style="color: red;">${error.message}</p>`;
        }
    };

    // --- FILTROS ---
    const filterVehicles = () => {
        const searchTerm = vehicleSearch.value.toLowerCase();
        const selectedStatus = statusFilter.value;

        document.querySelectorAll('.vehicle-card').forEach(card => {
            const matchesSearch = card.dataset.name.includes(searchTerm) || card.dataset.plate.includes(searchTerm) || card.dataset.prefix.includes(searchTerm);
            const matchesStatus = selectedStatus === 'all' || card.dataset.status === selectedStatus;

            card.style.display = (matchesSearch && matchesStatus) ? 'flex' : 'none';
        });
    };

    vehicleSearch.addEventListener('input', filterVehicles);
    statusFilter.addEventListener('change', filterVehicles);

    // --- LÓGICA DO MODAL ---
    const modalVehicleSearch = document.getElementById('modalVehicleSearch');
    const modalVehicleId = document.getElementById('modalVehicleId');
    const modalVehicleResults = document.getElementById('modalVehicleResults');
    const oilProductSelect = document.getElementById('oilProductId');
    const litersUsedInput = document.getElementById('litersUsed');
    const totalCostInput = document.getElementById('totalCost');
    const stockInfo = document.getElementById('stockInfo');

    const setupAutocompleteModal = () => {
        let debounce;
        modalVehicleSearch.addEventListener('input', () => {
            clearTimeout(debounce);
            const term = modalVehicleSearch.value.trim().toLowerCase();
            modalVehicleId.value = '';
            if (term.length < 2) {
                modalVehicleResults.style.display = 'none';
                return;
            }
            debounce = setTimeout(() => {
                const filtered = allVehicles.filter(v => v.prefix.toLowerCase().includes(term) || v.plate.toLowerCase().includes(term));
                modalVehicleResults.innerHTML = filtered.map(v => `<div data-id="${v.id}" data-text="${v.prefix} - ${v.plate}">${v.name} (${v.prefix})</div>`).join('');
                modalVehicleResults.style.display = 'block';
            }, 300);
        });

        modalVehicleResults.addEventListener('click', (e) => {
            const target = e.target.closest('div[data-id]');
            if (target) {
                modalVehicleId.value = target.dataset.id;
                modalVehicleSearch.value = target.dataset.text;
                modalVehicleResults.style.display = 'none';
            }
        });
    };

    const calculateCost = () => {
        const selectedOption = oilProductSelect.options[oilProductSelect.selectedIndex];
        const costPerLiter = parseFloat(selectedOption.dataset.cost);
        const liters = parseFloat(litersUsedInput.value);

        if (costPerLiter && liters > 0) {
            totalCostInput.value = (costPerLiter * liters).toFixed(2).replace('.', ',');
        } else {
            totalCostInput.value = '';
        }
    };
    
    oilProductSelect.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const stock = selectedOption.dataset.stock;
        stockInfo.textContent = stock ? `Estoque: ${stock} L` : '';
        calculateCost();
    });
    litersUsedInput.addEventListener('input', calculateCost);

    openModalBtn.addEventListener('click', () => modal.style.display = 'flex');
    closeModalBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    oilChangeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(oilChangeForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(`${BASE_URL}/sector-manager/oil-change/store`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (!response.ok) throw new Error(result.message);
            
            alert(result.message);
            modal.style.display = 'none';
            oilChangeForm.reset();
            fetchData(); // Atualiza a dashboard

        } catch (error) {
            alert(`Erro ao registrar: ${error.message}`);
        }
    });

    // --- INICIALIZAÇÃO ---
    fetchData();
    setupAutocompleteModal();
});