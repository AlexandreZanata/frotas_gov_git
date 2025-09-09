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
            const vehicleName = vehicleRow.cells[2].textContent;
            const vehiclePrefix = vehicleRow.cells[0].textContent;
            
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
                // Passa a chave do layout para a função de renderização
                renderTireDiagram(data.layoutKey, data.layoutConfig, data.tires || []);
            } else {
                diagramContainer.innerHTML = `<p class="error">${data.message || 'Erro ao carregar.'}</p>`;
            }
        } catch (error) {
            diagramContainer.innerHTML = '<p class="error">Erro de comunicação.</p>';
        }
    }

// Dentro de public/assets/js/tire_management.js

function renderTireDiagram(layoutKey, layoutConfig, tires) {
    diagramContainer.innerHTML = '';
    if (!layoutConfig || !layoutConfig.axles) {
        diagramContainer.innerHTML = '<p class="error">Configuração de layout inválida.</p>';
        return;
    }

    const diagram = document.createElement('div');
    diagram.className = 'tire-diagram';
    diagram.dataset.layoutKey = layoutKey; // Para o CSS geral, se houver

    const maxColumns = layoutConfig.axles.reduce((max, axle) => Math.max(max, axle.tires.length), 0);
    const hasSpare = layoutConfig.spare && layoutConfig.spare.length > 0;

    // --- Lógica para gerar Grid CSS dinamicamente ---
    let gridTemplateAreas = '';
    const allPositions = [];

    // Eixos dianteiros
    layoutConfig.axles.filter(a => a.type === 'front').forEach(axle => {
        gridTemplateAreas += `"${axle.tires.join(' ')}"\n`;
        allPositions.push(...axle.tires);
    });

    // Estepe (se houver)
    if (hasSpare) {
        const sparePos = layoutConfig.spare[0];
        allPositions.push(sparePos);
        let spareRow = `"${'. '.repeat(Math.floor(maxColumns / 2))}${sparePos}${' .'.repeat(Math.ceil(maxColumns / 2) -1)}"`;
        gridTemplateAreas += `${spareRow}\n`;
    }

    // Eixos traseiros
    layoutConfig.axles.filter(a => a.type === 'rear').forEach(axle => {
        gridTemplateAreas += `"${axle.tires.join(' ')}"\n`;
        allPositions.push(...axle.tires);
    });

    diagram.style.gridTemplateAreas = gridTemplateAreas;
    diagram.style.gridTemplateColumns = `repeat(${maxColumns}, auto)`;

    // --- Renderiza os Pneus ---
    const tireMap = new Map(tires.map(t => [t.position, t]));
    allPositions.forEach(pos => {
        const tireData = tireMap.get(pos);
        const tireEl = document.createElement('div');
        tireEl.className = 'tire';
        tireEl.dataset.position = pos;
        if (tireData) {
            tireEl.dataset.tireId = tireData.tire_id;
        }

        let lifespanClass = 'good';
        if (tireData && tireData.lifespan) {
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
        // Só permite selecionar pneus que existem
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
                    // Recarrega o modal para mostrar o resultado da ação
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