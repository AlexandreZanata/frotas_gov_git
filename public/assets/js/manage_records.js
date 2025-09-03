document.addEventListener('DOMContentLoaded', () => {
    // --- GERENCIAMENTO DE ABAS COM SESSIONSTORAGE ---
    const setActiveTabFromStorage = () => {
        const activeTabId = sessionStorage.getItem('activeRecordTab') || 'run';
        const tabLink = document.querySelector(`.tab-link[data-tab="${activeTabId}"]`);
        if (tabLink) tabLink.click();
    };
    const saveActiveTabToStorage = (tabId) => {
        sessionStorage.setItem('activeRecordTab', tabId);
    };
    const checkUrlForTab = () => {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab === 'fueling' || tab === 'run') {
            sessionStorage.setItem('activeRecordTab', tab);
        }
    };
    checkUrlForTab();

    // --- ELEMENTOS GERAIS ---
    const modal = document.getElementById('confirmationModal');
    const csrfToken = CSRF_TOKEN;
    const mainFormTitle = document.getElementById('mainFormTitle');
    const runsTableContainer = document.getElementById('runs-table-container');
    const fuelingsTableContainer = document.getElementById('fuelings-table-container');
    // NOVO: Referência ao select de filtro de secretaria (só existe para Admin)
    const filterSecretariatSelect = document.getElementById('filter_secretariat_id');

    // --- ELEMENTOS DAS ABAS E FORMULÁRIOS ---
    const tabLinks = document.querySelectorAll('.tab-link');
    const recordForms = document.querySelectorAll('.record-form');
    const runForm = document.getElementById('runForm');
    const fuelingForm = document.getElementById('fuelingForm');

    // --- ELEMENTOS DE CORRIDA ---
    const runIdInput = document.getElementById('run_id');
    const cancelRunEditBtn = document.getElementById('cancelRunEditBtn');
    const runsTableBody = document.getElementById('runsTableBody');
    const runsPaginationContainer = document.getElementById('runsPaginationContainer');
    const runSearchInput = document.getElementById('runSearchInput');

    // --- ELEMENTOS DE ABASTECIMENTO ---
    const fuelingIdInput = document.getElementById('fueling_id');
    const cancelFuelingEditBtn = document.getElementById('cancelFuelingEditBtn');
    const fuelingsTableBody = document.getElementById('fuelingsTableBody');
    const fuelingsPaginationContainer = document.getElementById('fuelingsPaginationContainer');
    const gasStationSelect = document.getElementById('gas_station_id');
    const fuelTypeSelect = document.getElementById('fuel_type_id');
    const litersInput = document.getElementById('liters');
    const totalValueInput = document.getElementById('total_value');
    const gasStationNameInput = document.getElementById('gas_station_name');
    const isManualStationCheckbox = document.getElementById('is_manual_station');
    const gasStationSelectContainer = document.getElementById('gas_station_select_container');
    const gasStationNameContainer = document.getElementById('gas_station_name_container');
    const fuelingSearchInput = document.getElementById('fuelingSearchInput');
    let isManuallyEdited = false;

    if (isManualStationCheckbox) {
        isManualStationCheckbox.addEventListener('change', () => {
            const isManual = isManualStationCheckbox.checked;
            if (isManual) {
                gasStationSelectContainer.style.display = 'none';
                gasStationSelect.required = false;
                gasStationSelect.value = '';
                gasStationNameContainer.style.display = 'block';
                gasStationNameInput.required = true;
            } else {
                gasStationSelectContainer.style.display = 'block';
                gasStationSelect.required = true;
                gasStationNameContainer.style.display = 'none';
                gasStationNameInput.required = false;
                gasStationNameInput.value = '';
            }
        });
    }

    // --- CONTROLE DAS ABAS E VISIBILIDADE DAS TABELAS ---
    tabLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const tabId = e.target.closest('.tab-link').dataset.tab;
            saveActiveTabToStorage(tabId);
            tabLinks.forEach(item => item.classList.remove('active'));
            recordForms.forEach(form => form.classList.remove('active'));
            e.target.closest('.tab-link').classList.add('active');
            document.getElementById(`${tabId}Form`).classList.add('active');
            if (tabId === 'run') {
                mainFormTitle.textContent = 'Registrar Corrida';
                runsTableContainer.style.display = 'block';
                fuelingsTableContainer.style.display = 'none';
            } else if (tabId === 'fueling') {
                mainFormTitle.textContent = 'Registrar Abastecimento';
                runsTableContainer.style.display = 'none';
                fuelingsTableContainer.style.display = 'block';
            }
        });
    });

    // --- FUNÇÕES DE BUSCA E AUTOCOMPLETE ---
    function parseDateSafe(dateStr) {
        if (!dateStr) return null;
        if (dateStr instanceof Date) {
            return isNaN(dateStr.getTime()) ? null : dateStr;
        }
        const tryIso = dateStr.replace(' ', 'T');
        const d = new Date(tryIso);
        if (!isNaN(d.getTime())) return d;
        const d2 = new Date(dateStr);
        if (!isNaN(d2.getTime())) return d2;
        return null;
    }

    const escapeHTML = (str) => {
        if (str === null || str === undefined) return '';
        return str.toString().replace(/[&<>"']/g, match => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[match]));
    };

    const setupSearchInput = (inputId, resultsId, hiddenId, url, displayFormatter, onSelectCallback) => {
        const searchInput = document.getElementById(inputId);
        const resultsDiv = document.getElementById(resultsId);
        const hiddenInput = document.getElementById(hiddenId);
        if (!searchInput) return;

        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const term = searchInput.value.trim();
            if (hiddenInput) hiddenInput.value = '';
            if (term.length < 2) {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
                return;
            }
            debounceTimer = setTimeout(async () => {
                try {
                    // MODIFICADO: Adiciona o parâmetro secretariat_id à URL se o filtro estiver presente
                    let requestUrl = `${url}?term=${encodeURIComponent(term)}`;
                    
                    // Verifica se é admin e se tem filtro de secretaria selecionado
                    if (IS_ADMIN && filterSecretariatSelect) {
                        const secretariatId = filterSecretariatSelect.value;
                        if (secretariatId) {
                            requestUrl += `&secretariat_id=${secretariatId}`;
                        }
                    }
                    
                    const response = await fetch(requestUrl);
                    const result = await response.json();
                    if (result && result.success && Array.isArray(result.data)) {
                        const data = result.data;
                        if (data.length === 0) {
                            resultsDiv.innerHTML = '<div class="no-results">Nenhum resultado</div>';
                            resultsDiv.style.display = 'block';
                            return;
                        }
                        resultsDiv.innerHTML = data.map(item => {
                            let displayText = '';
                            try {
                                displayText = displayFormatter(item) || '';
                            } catch (e) {
                                displayText = 'Erro ao formatar item';
                            }
                            const escaped = escapeHTML(displayText);
                            const idAttr = item.id || item.run_id || item.id;
                            const itemData = escapeHTML(JSON.stringify(item));
                            return `<div data-id="${idAttr}" data-text="${escaped}" data-item-data='${itemData}' class="search-result-item">${escaped}</div>`;
                        }).join('');
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = `<div class="no-results">${escapeHTML(result.message || 'Nenhum resultado')}</div>`;
                        resultsDiv.style.display = 'block';
                    }
                } catch (error) {
                    console.error(`Erro na busca (${url}):`, error);
                    resultsDiv.innerHTML = '<div class="no-results">Erro na busca</div>';
                    resultsDiv.style.display = 'block';
                }
            }, 300);
        });

        resultsDiv.addEventListener('click', (e) => {
            const itemDiv = e.target.closest('.search-result-item');
            if (!itemDiv) return;
            const id = itemDiv.dataset.id;
            const text = itemDiv.dataset.text;
            if (hiddenInput) hiddenInput.value = id;
            searchInput.value = text;
            resultsDiv.style.display = 'none';
            if (onSelectCallback && itemDiv.dataset.itemData) {
                const data = JSON.parse(itemDiv.dataset.itemData);
                onSelectCallback(data);
            }
        });

        document.addEventListener('click', (ev) => {
            if (!resultsDiv) return;
            if (!resultsDiv.contains(ev.target) && ev.target !== searchInput) {
                resultsDiv.style.display = 'none';
            }
        });
    };
    
    const onRunSelectForFueling = (runData) => {
        if (runData.vehicle_id) {
            document.getElementById('fueling_vehicle_id').value = runData.vehicle_id;
            document.getElementById('fueling_vehicle_search').value = `${runData.prefix} - ${runData.plate || runData.vehicle_name}`;
        }
        if (runData.driver_id) {
            document.getElementById('fueling_driver_id').value = runData.driver_id;
            document.getElementById('fueling_driver_search').value = runData.driver_name;
        }
    };

    // --- FUNÇÕES AUXILIARES ---
    const formatDateTimeForInput = (dateTimeStr) => {
        if (!dateTimeStr) return '';
        try {
            const date = new Date(dateTimeStr);
            if (isNaN(date.getTime())) return '';
            return date.toISOString().slice(0, 16);
        } catch (e) {
            return '';
        }
    };

    // --- LÓGICA DE BUSCA NAS TABELAS ---
    let runSearchTimeout;
    runSearchInput && runSearchInput.addEventListener('input', () => {
        clearTimeout(runSearchTimeout);
        runSearchTimeout = setTimeout(() => {
            fetchRuns(1);
        }, 300);
    });

    let fuelingSearchTimeout;
    fuelingSearchInput && fuelingSearchInput.addEventListener('input', () => {
        clearTimeout(fuelingSearchTimeout);
        fuelingSearchTimeout = setTimeout(() => {
            fetchFuelings(1);
        }, 300);
    });

    // NOVO: Adiciona event listener para o filtro de secretaria
    if (IS_ADMIN && filterSecretariatSelect) {
        filterSecretariatSelect.addEventListener('change', () => {
            fetchRuns(1);
            fetchFuelings(1);
        });
    }

    // --- LÓGICA DE FETCH GENÉRICA ---
    const fetchRuns = async (page = 1) => {
        if (!runsTableBody) return;
        // Calcula o número de colunas com base no tipo de usuário
        const colSpan = IS_ADMIN ? 7 : 6;
        runsTableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center;">Carregando...</td></tr>`;
        const searchTerm = runSearchInput ? runSearchInput.value.trim() : '';
        
        try {
            // MODIFICADO: Adiciona o parâmetro secretariat_id à URL se o filtro estiver presente
            let url = `${BASE_URL}/sector-manager/ajax/search-runs?page=${page}&term=${encodeURIComponent(searchTerm)}`;
            if (IS_ADMIN && filterSecretariatSelect && filterSecretariatSelect.value) {
                url += `&secretariat_id=${filterSecretariatSelect.value}`;
            }
            
            const response = await fetch(url);
            const result = await response.json();
            if (result.success && result.data) {
                renderRunsTable(result.data.runs);
                runsPaginationContainer.innerHTML = result.data.paginationHtml || '';
            } else {
                runsTableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center;">${result.message || 'Nenhuma corrida encontrada.'}</td></tr>`;
            }
        } catch (error) {
            console.error("Erro ao buscar corridas:", error);
            runsTableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; color: red;">Erro ao carregar dados.</td></tr>`;
        }
    };

    const renderRunsTable = (runs) => {
        // Calcula o número de colunas com base no tipo de usuário
        const colSpan = IS_ADMIN ? 7 : 6;
        if (!runs || runs.length === 0) {
            runsTableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center;">Nenhuma corrida encontrada.</td></tr>`;
            return;
        }
        
        runsTableBody.innerHTML = runs.map(run => {
            const kmRodado = (run.end_km && run.start_km) ? (run.end_km - run.start_km) : 'N/A';
            const startTime = new Date(run.start_time).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
            
            // Constrói a linha, incluindo a coluna de secretaria se for Admin
            return `
                <tr data-id="${run.id}" data-destination="${escapeHTML(run.destination)}">
                    <td>${startTime}</td>
                    ${IS_ADMIN ? `<td>${escapeHTML(run.secretariat_name || 'N/A')}</td>` : ''}
                    <td>${escapeHTML(run.vehicle_prefix)}</td>
                    <td>${escapeHTML(run.driver_name)}</td>
                    <td>${escapeHTML(run.destination)}</td>
                    <td>${kmRodado} km</td>
                    <td class="actions">
                        <a href="#" class="edit-run" title="Editar Corrida"><i class="fas fa-edit"></i></a>
                        <a href="#" class="delete-run" title="Excluir Corrida"><i class="fas fa-trash-alt"></i></a>
                    </td>
                </tr>`;
        }).join('');
    };

    const fetchFuelings = async (page = 1) => {
        if (!fuelingsTableBody) return;
        // Calcula o número de colunas com base no tipo de usuário
        const colSpan = IS_ADMIN ? 8 : 7;
        fuelingsTableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center;">Carregando...</td></tr>`;
        const searchTerm = fuelingSearchInput ? fuelingSearchInput.value.trim() : '';
        
        try {
            // MODIFICADO: Adiciona o parâmetro secretariat_id à URL se o filtro estiver presente
            let url = `${BASE_URL}/sector-manager/ajax/search-fuelings?page=${page}&term=${encodeURIComponent(searchTerm)}`;
            if (IS_ADMIN && filterSecretariatSelect && filterSecretariatSelect.value) {
                url += `&secretariat_id=${filterSecretariatSelect.value}`;
            }
            
            const response = await fetch(url);
            const result = await response.json();
            if (result.success && result.data) {
                renderFuelingsTable(result.data.fuelings);
                fuelingsPaginationContainer.innerHTML = result.data.paginationHtml || '';
            } else {
                fuelingsTableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align: center;">${result.message || 'Nenhum abastecimento encontrado.'}</td></tr>`;
            }
        } catch (error) {
            console.error('Erro ao buscar abastecimentos:', error);
            fuelingsTableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align: center; color: red;">Erro ao carregar dados.</td></tr>`;
        }
    };

    const renderFuelingsTable = (fuelings) => {
        // Calcula o número de colunas com base no tipo de usuário
        const colSpan = IS_ADMIN ? 8 : 7;
        if (!fuelings || fuelings.length === 0) {
            fuelingsTableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align: center;">Nenhum abastecimento encontrado.</td></tr>`;
            return;
        }
        
        fuelingsTableBody.innerHTML = fuelings.map(fueling => `
            <tr data-id="${fueling.id}" data-station-name="${escapeHTML(fueling.gas_station)}">
                <td>${new Date(fueling.created_at).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })}</td>
                ${IS_ADMIN ? `<td>${escapeHTML(fueling.secretariat_name || 'N/A')}</td>` : ''}
                <td>${escapeHTML(fueling.vehicle_prefix)}</td>
                <td>${escapeHTML(fueling.driver_name)}</td>
                <td>${escapeHTML(fueling.gas_station)}</td>
                <td>${parseFloat(fueling.liters).toLocaleString('pt-BR')} L</td>
                <td>R$ ${parseFloat(fueling.total_value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                <td class="actions">
                    <a href="#" class="edit-fueling" title="Editar Abastecimento"><i class="fas fa-edit"></i></a>
                    <a href="#" class="delete-fueling" title="Excluir Abastecimento"><i class="fas fa-trash-alt"></i></a>
                </td>
            </tr>
        `).join('');
    };

    // --- LÓGICA DE CÁLCULO DE PREÇO ---
    const calculateFuelingValue = async () => {
        if (isManuallyEdited) return;
        const stationId = gasStationSelect.value;
        const fuelTypeId = fuelTypeSelect.value;
        const liters = parseFloat(litersInput.value.replace(',', '.'));
        if (stationId && fuelTypeId && liters > 0) {
            try {
                const formData = new FormData();
                formData.append('station_id', stationId);
                formData.append('fuel_type_id', fuelTypeId);
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/get-fuel-price`, {
                    method: 'POST', body: new URLSearchParams(formData)
                });
                const result = await response.json();
                if (result.success && result.price) {
                    const total = (parseFloat(result.price) * liters).toFixed(2);
                    totalValueInput.value = total.replace('.', ',');
                } else {
                    totalValueInput.value = '';
                    totalValueInput.placeholder = result.message || 'Preço não encontrado';
                }
            } catch (error) {
                console.error('Erro ao buscar preço:', error);
                totalValueInput.value = '';
            }
        }
    };

    gasStationSelect && gasStationSelect.addEventListener('change', () => {
        isManuallyEdited = false; // Força o recálculo
        calculateFuelingValue();
    });
    fuelTypeSelect && fuelTypeSelect.addEventListener('change', () => {
        isManuallyEdited = false; // Força o recálculo
        calculateFuelingValue();
    });
    litersInput && litersInput.addEventListener('input', () => {
        isManuallyEdited = false;
        totalValueInput.placeholder = 'Calculado automaticamente...';
        calculateFuelingValue();
    });
    totalValueInput && totalValueInput.addEventListener('input', () => { isManuallyEdited = true; });


    // --- FUNÇÕES DE AÇÃO E MODAIS ---
    const closeModal = () => { if(modal) modal.style.display = 'none'; };
    const resetRunForm = () => {
        runForm.action = `${BASE_URL}/sector-manager/records/run/store`;
        runIdInput.value = '';
        runForm.reset();
        cancelRunEditBtn.style.display = 'none';
        
        // NOVO: Resetar o campo de secretaria para Admin
        if (IS_ADMIN && document.getElementById('run_secretariat_id')) {
            document.getElementById('run_secretariat_id').value = '';
        }
    };
    const resetFuelingForm = () => {
        fuelingForm.action = `${BASE_URL}/sector-manager/records/fueling/store`;
        fuelingIdInput.value = '';
        fuelingForm.reset();
        cancelFuelingEditBtn.style.display = 'none';
        isManuallyEdited = false;
        document.getElementById('fueling_run_id').value = '';
        document.getElementById('fueling_run_search').value = '';
        isManualStationCheckbox.checked = false;
        isManualStationCheckbox.dispatchEvent(new Event('change'));
        
        // NOVO: Resetar o campo de secretaria para Admin
        if (IS_ADMIN && document.getElementById('fueling_secretariat_id')) {
            document.getElementById('fueling_secretariat_id').value = '';
        }
    };

    // --- DELEGAÇÃO DE EVENTOS PRINCIPAL ---
    document.body.addEventListener('click', async (e) => {
        const target = e.target;
        const link = target.closest('a');
        if (target.classList.contains('modal-close') || target === modal) closeModal();

        if (link && link.dataset.page) {
            e.preventDefault();
            const paginationWrapper = link.closest('.pagination-wrapper');
            if (paginationWrapper && paginationWrapper.id === 'runsPaginationContainer') fetchRuns(link.dataset.page);
            else if (paginationWrapper && paginationWrapper.id === 'fuelingsPaginationContainer') fetchFuelings(link.dataset.page);
        }

        const actionLink = target.closest('.actions a');
        if (actionLink) {
            e.preventDefault();
            const row = actionLink.closest('tr');
            const id = row.dataset.id;

            if (actionLink.classList.contains('edit-run')) {
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/get-run`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `run_id=${id}&csrf_token=${csrfToken}`
                });
                const result = await response.json();
                if(result.success) {
                    mainFormTitle.textContent = `Editando Corrida`;
                    runForm.action = `${BASE_URL}/sector-manager/records/run/update`;
                    runIdInput.value = result.data.id;
                    document.getElementById('run_vehicle_id').value = result.data.vehicle_id;
                    document.getElementById('run_vehicle_search').value = `${result.data.vehicle_prefix} - ${result.data.plate || result.data.vehicle_name}`;
                    document.getElementById('run_driver_id').value = result.data.driver_id;
                    document.getElementById('run_driver_search').value = result.data.driver_name;
                    document.getElementById('start_km').value = result.data.start_km;
                    document.getElementById('end_km').value = result.data.end_km || '';
                    document.getElementById('start_time').value = formatDateTimeForInput(result.data.start_time);
                    document.getElementById('end_time').value = formatDateTimeForInput(result.data.end_time);
                    document.getElementById('destination').value = result.data.destination;
                    document.getElementById('stop_point').value = result.data.stop_point || '';
                    
                    // NOVO: Preenche o campo de secretaria para Admin
                    if (IS_ADMIN && document.getElementById('run_secretariat_id') && result.data.secretariat_id) {
                        document.getElementById('run_secretariat_id').value = result.data.secretariat_id;
                    }
                    
                    cancelRunEditBtn.style.display = 'inline-block';
                    runForm.scrollIntoView({ behavior: 'smooth' });
                } else {
                    alert('Erro ao carregar dados da corrida.');
                }
            } else if (actionLink.classList.contains('delete-run')) {
                const modalForm = document.getElementById('modalForm');
                modalForm.action = `${BASE_URL}/sector-manager/records/run/delete`;
                document.getElementById('modalTitle').textContent = 'Excluir Registro de Corrida';
                document.getElementById('modalBody').innerHTML = `
                    <p>Tem certeza que deseja excluir a corrida para <strong>${escapeHTML(row.dataset.destination)}</strong>?</p>
                    <p class="warning-text" style="color: #b91c1c;">Esta ação não pode ser desfeita.</p>
                    <div class="form-group"><label for="justificativa">Justificativa (Obrigatório):</label><textarea id="justificativa" name="justificativa" required style="width: 100%;"></textarea></div>
                    <input type="hidden" name="run_id" value="${id}"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">`;
                document.getElementById('modalSubmitBtn').textContent = 'Confirmar Exclusão';
                modal.style.display = 'flex';
            }
            if (actionLink.classList.contains('edit-fueling')) {
                const response = await fetch(`${BASE_URL}/sector-manager/ajax/get-fueling`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `fueling_id=${id}&csrf_token=${csrfToken}`
                });
                const result = await response.json();
                if(result.success) {
                    mainFormTitle.textContent = `Editando Abastecimento`;
                    fuelingForm.action = `${BASE_URL}/sector-manager/records/fueling/update`;
                    fuelingIdInput.value = result.data.id;
                    document.getElementById('fueling_run_id').value = result.data.run_id;
                    const runDate = new Date(result.data.run_start_time).toLocaleDateString('pt-BR');
                    document.getElementById('fueling_run_search').value = `${result.data.run_destination} (${result.data.vehicle_prefix}) - ${runDate}`;
                    document.getElementById('fueling_vehicle_id').value = result.data.vehicle_id;
                    document.getElementById('fueling_vehicle_search').value = `${result.data.vehicle_prefix} - ${result.data.vehicle_name}`;
                    document.getElementById('fueling_driver_id').value = result.data.user_id;
                    document.getElementById('fueling_driver_search').value = result.data.driver_name;
                    document.getElementById('fueling_created_at').value = formatDateTimeForInput(result.data.created_at);
                    if (result.data.gas_station_id) {
                        isManualStationCheckbox.checked = false;
                        gasStationSelect.value = result.data.gas_station_id;
                    } else {
                        isManualStationCheckbox.checked = true;
                        gasStationNameInput.value = result.data.gas_station_name;
                    }
                    isManualStationCheckbox.dispatchEvent(new Event('change'));
                    fuelingForm.querySelector('#km').value = result.data.km;
                    fuelingForm.querySelector('#liters').value = parseFloat(result.data.liters).toLocaleString('pt-BR');
                    fuelingForm.querySelector('#total_value').value = parseFloat(result.data.total_value).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    fuelingForm.querySelector('#fuel_type_id').value = result.data.fuel_type_id;
                    
                    // NOVO: Preenche o campo de secretaria para Admin
                    if (IS_ADMIN && document.getElementById('fueling_secretariat_id') && result.data.secretariat_id) {
                        document.getElementById('fueling_secretariat_id').value = result.data.secretariat_id;
                    }
                    
                    cancelFuelingEditBtn.style.display = 'inline-block';
                    fuelingForm.scrollIntoView({ behavior: 'smooth' });
                } else {
                    alert('Erro ao carregar dados do abastecimento.');
                }
            } else if (actionLink.classList.contains('delete-fueling')) {
                const modalForm = document.getElementById('modalForm');
                modalForm.action = `${BASE_URL}/sector-manager/records/fueling/delete`;
                document.getElementById('modalTitle').textContent = 'Excluir Registro de Abastecimento';
                document.getElementById('modalBody').innerHTML = `
                    <p>Tem certeza que deseja excluir o abastecimento no posto <strong>${escapeHTML(row.dataset.stationName)}</strong>?</p>
                    <p class="warning-text" style="color: #b91c1c;">Esta ação não pode ser desfeita.</p>
                    <div class="form-group"><label for="justificativa">Justificativa (Obrigatório):</label><textarea id="justificativa" name="justificativa" required style="width: 100%;"></textarea></div>
                    <input type="hidden" name="fueling_id" value="${id}"><input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">`;
                document.getElementById('modalSubmitBtn').textContent = 'Confirmar Exclusão';
                modal.style.display = 'flex';
            }
        }
    });

    // --- INICIALIZAÇÃO ---
    setupSearchInput('run_vehicle_search', 'run_vehicle_search_results', 'run_vehicle_id', `${BASE_URL}/sector-manager/ajax/search-vehicles-for-run`, item => `${escapeHTML(item.prefix)} - ${escapeHTML(item.plate)}`);
    setupSearchInput('run_driver_search', 'run_driver_search_results', 'run_driver_id', `${BASE_URL}/sector-manager/ajax/search-drivers`, item => escapeHTML(item.name));
    setupSearchInput('fueling_vehicle_search', 'fueling_vehicle_search_results', 'fueling_vehicle_id', `${BASE_URL}/sector-manager/ajax/search-vehicles-for-run`, item => `${escapeHTML(item.prefix)} - ${escapeHTML(item.plate)}`);
    setupSearchInput('fueling_driver_search', 'fueling_driver_search_results', 'fueling_driver_id', `${BASE_URL}/sector-manager/ajax/search-drivers`, item => escapeHTML(item.name));
    setupSearchInput(
        'fueling_run_search',
        'fueling_run_search_results',
        'fueling_run_id',
        `${BASE_URL}/sector-manager/ajax/search-runs-for-fueling`,
        item => {
            const destination = item.destination || item.run_destination || 'Sem destino';
            const prefix = item.prefix || item.vehicle_prefix || 'S/P';
            const vehicleName = item.vehicle_name || '';
            const rawDate = item.start_time || item.run_start_time || null;
            const parsed = parseDateSafe(rawDate);
            const dateText = parsed ? parsed.toLocaleDateString('pt-BR') : 'Data desconhecida';
            const vehiclePart = vehicleName ? `${prefix} - ${vehicleName}` : prefix;
            return `${destination} (${vehiclePart}) - ${dateText}`;
        },
        onRunSelectForFueling
    );

    cancelRunEditBtn && cancelRunEditBtn.addEventListener('click', (e) => { e.preventDefault(); resetRunForm(); });
    cancelFuelingEditBtn && cancelFuelingEditBtn.addEventListener('click', (e) => { e.preventDefault(); resetFuelingForm(); });

    setActiveTabFromStorage();
    fetchRuns();
    fetchFuelings();
});