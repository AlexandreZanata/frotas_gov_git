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
            const vehicleName = vehicleRow.cells[0].textContent;
            
            modalVehicleName.textContent = `Gerenciar Pneus: ${vehicleName}`;
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
                renderTireDiagram(data.vehicleType || 'truck_4x2', data.tires || []);
            } else {
                diagramContainer.innerHTML = `<p class="error">${data.message || 'Erro ao carregar.'}</p>`;
            }
        } catch (error) {
            diagramContainer.innerHTML = '<p class="error">Erro de comunicação.</p>';
        }
    }

    function renderTireDiagram(type, tires) {
        diagramContainer.innerHTML = '';
        const diagram = document.createElement('div');
        diagram.className = `tire-diagram ${type}`;
        const tireMap = new Map(tires.map(t => [t.position, t]));
        const positions = {
            car: ['front_left', 'front_right', 'rear_left', 'rear_right'],
            truck_4x2: ['front_left', 'front_right', 'rear_left_outer', 'rear_left_inner', 'rear_right_outer', 'rear_right_inner']
        };

        (positions[type] || []).forEach(pos => {
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

        // Não permite selecionar posições vazias
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
        rotateBtn.disabled = selectedTires.length !== 2;
        // Lógica para outros botões aqui
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
                    openVehicleModal(currentVehicleId); // Recarrega o diagrama
                } else {
                    alert(`Erro: ${result.message}`);
                }
            } catch (error) {
                alert('Erro de comunicação ao executar a ação.');
            }
        });
    });
});