document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('vehicleTableBody');
    if (!tableBody) return;

    const modal = document.getElementById('vehicleTireModal');
    if (!modal) return;
    
    const modalClose = modal.querySelector('.modal-close');
    const diagramContainer = document.getElementById('tire-diagram-container');
    const modalVehicleName = document.getElementById('modalVehicleName');
    const actionButtons = document.querySelectorAll('#tire-actions button');

    let currentVehicleId = null;
    let selectedTires = [];

    tableBody.addEventListener('click', (e) => {
        const manageButton = e.target.closest('a.manage-tires');
        if (manageButton) {
            e.preventDefault();
            const vehicleRow = manageButton.closest('tr');
            currentVehicleId = vehicleRow.dataset.vehicleId;
            const vehicleName = vehicleRow.cells[2].textContent; // Coluna Nome/Modelo
            const vehiclePrefix = vehicleRow.cells[0].textContent; // Coluna Prefixo
            
            modalVehicleName.textContent = `Gerenciar Pneus: ${vehiclePrefix} - ${vehicleName}`;
            openVehicleModal(currentVehicleId);
        }
    });

    modalClose.addEventListener('click', closeModal);
    window.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    async function openVehicleModal(vehicleId) {
        diagramContainer.innerHTML = '<p>Carregando diagrama...</p>';
        modal.style.display = 'flex';
        resetSelection();
        try {
            const response = await fetch(`${BASE_URL}/tires/ajax/get-vehicle-layout`, {
                method: 'POST',
                body: new URLSearchParams({ 'vehicle_id': vehicleId }),
            });
            const data = await response.json();
            if (data.success) {
                renderTireDiagram(data.layoutConfig, data.tires || []);
            } else {
                diagramContainer.innerHTML = `<p class="error">${data.message || 'Erro ao carregar.'}</p>`;
            }
        } catch (error) {
            diagramContainer.innerHTML = '<p class="error">Erro de comunicação.</p>';
        }
    }

// Substitua a função renderTireDiagram existente por esta
    function renderTireDiagram(layoutConfig, tires) {
        diagramContainer.innerHTML = ''; // Limpa o conteúdo anterior
        if (!layoutConfig || !layoutConfig.positions || layoutConfig.positions.length === 0) {
            diagramContainer.innerHTML = '<p class="error">Configuração de layout inválida ou não encontrada para este veículo.</p>';
            return;
        }

        const diagram = document.createElement('div');
        diagram.className = 'tire-diagram';

        // Aplica estilos CSS customizados vindos do banco de dados, se existirem
        if (layoutConfig.css) {
            Object.assign(diagram.style, layoutConfig.css);
        }

        const tireMap = new Map(tires.map(t => [t.position, t]));
        
        // Cria um elemento de pneu para cada posição definida no layout
        layoutConfig.positions.forEach(pos => {
            const tireData = tireMap.get(pos);
            const tireEl = document.createElement('div');
            tireEl.className = 'tire';
            tireEl.dataset.position = pos;
            if (tireData) {
                tireEl.dataset.tireId = tireData.tire_id;
            }

            let lifespanClass = 'good';
            if (tireData) {
                if (tireData.lifespan <= 20) lifespanClass = 'critical';
                else if (tireData.lifespan <= 40) lifespanClass = 'attention';
            }

            tireEl.innerHTML = `
                <span class="position-label">${pos.replace(/_/g, ' ').toUpperCase()}</span>
                <strong>${tireData ? tireData.dot : 'VAZIO'}</strong>
                ${tireData ? `<div class="lifespan-bar"><div class="lifespan-fill ${lifespanClass}" style="width: ${tireData.lifespan}%;"></div></div>` : ''}
            `;
            diagram.appendChild(tireEl);
            tireEl.addEventListener('click', handleTireClick);
        });

        diagramContainer.appendChild(diagram);
    }

    function handleTireClick(e) {
        const tireEl = e.currentTarget;
        const position = tireEl.dataset.position;
        if (!tireEl.dataset.tireId) return;
        tireEl.classList.toggle('selected');
        
        const index = selectedTires.indexOf(position);
        if (index > -1) {
            selectedTires.splice(index, 1);
        } else {
            selectedTires.push(position);
        }
        updateActionButtons();
    }
    
    function updateActionButtons() {
        const rotateBtn = document.querySelector('[data-action="rotate_internal"]');
        if(rotateBtn) rotateBtn.disabled = selectedTires.length !== 2;
    }

    function resetSelection() {
        selectedTires = [];
        diagramContainer.querySelectorAll('.tire.selected').forEach(el => el.classList.remove('selected'));
        updateActionButtons();
    }
    
    function closeModal() {
        modal.style.display = 'none';
        resetSelection();
    }
    
    actionButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
            const action = btn.dataset.action;
            if (btn.disabled) return;

            const formData = new URLSearchParams();
            formData.append('action', action);
            formData.append('vehicle_id', currentVehicleId);
            selectedTires.forEach(pos => formData.append('tires[]', pos));

            try {
                const response = await fetch(`${BASE_URL}/tires/ajax/perform-action`, {
                    method: 'POST',
                    body: formData,
                });
                const result = await response.json();
                if(result.success) {
                    alert(result.message);
                    openVehicleModal(currentVehicleId);
                } else {
                    alert(`Erro: ${result.message}`);
                }
            } catch (error) {
                alert('Erro de comunicação ao executar a ação.');
            }
        });
    });
});
