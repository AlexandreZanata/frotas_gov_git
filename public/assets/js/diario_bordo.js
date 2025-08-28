document.addEventListener('DOMContentLoaded', () => {

    // --- LÓGICA DA PÁGINA 1: ESCOLHER VEÍCULO ---
    const prefixInput = document.getElementById('prefix');
    if (prefixInput) {
        const vehicleIdInput = document.getElementById('vehicle_id');
        const plateInput = document.getElementById('plate');
        const nameInput = document.getElementById('name');
        const secretariatInput = document.getElementById('secretariat');
        const submitButton = document.getElementById('submit-btn');
        const errorMessageDiv = document.getElementById('vehicle-error');
        let debounceTimer;

        prefixInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            resetFields();
            const prefix = prefixInput.value.trim();
            if (prefix.length > 0) {
                debounceTimer = setTimeout(() => fetchVehicleData(prefix), 300);
            }
        });

        async function fetchVehicleData(prefix) {
            const baseUrl = document.querySelector('link[href*="assets"]').href.split('/assets/')[0];
            try {
                const response = await fetch(`${baseUrl}/runs/ajax-get-vehicle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prefix: prefix })
                });
                const data = await response.json();
                if (data.success) {
                    vehicleIdInput.value = data.vehicle.id;
                    plateInput.value = data.vehicle.plate;
                    nameInput.value = data.vehicle.name;
                    secretariatInput.value = data.vehicle.secretariat_name;
                    submitButton.disabled = false;
                    errorMessageDiv.style.display = 'none';
                } else {
                    errorMessageDiv.textContent = data.message || 'Veículo não encontrado.';
                    errorMessageDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Erro ao buscar veículo:', error);
                errorMessageDiv.textContent = 'Erro de comunicação com o servidor.';
                errorMessageDiv.style.display = 'block';
            }
        }

        function resetFields() {
            vehicleIdInput.value = '';
            plateInput.value = '';
            nameInput.value = '';
            secretariatInput.value = '';
            submitButton.disabled = true;
            errorMessageDiv.style.display = 'none';
        }
    }

    // --- LÓGICA DA PÁGINA 2: CHECKLIST ---
    const checklistForm = document.getElementById('checklist-form');
    if (checklistForm) {
        const handleProblemVisibility = (radioInput) => {
            const itemContainer = radioInput.closest('.checklist-item');
            if (!itemContainer) return;
            const problemDetails = itemContainer.querySelector('.problem-details');
            const notesTextarea = itemContainer.querySelector('textarea');
            if (radioInput.value === 'problem' && radioInput.checked) {
                problemDetails.style.display = 'block';
                notesTextarea.required = true;
            } else {
                problemDetails.style.display = 'none';
                notesTextarea.required = false;
                notesTextarea.value = '';
            }
        };

        checklistForm.addEventListener('change', (event) => {
            if (event.target.type === 'radio' && event.target.name.includes('[status]')) {
                handleProblemVisibility(event.target);
            }
        });

        const initiallyCheckedProblems = checklistForm.querySelectorAll('input[value="problem"]:checked');
        initiallyCheckedProblems.forEach(radio => handleProblemVisibility(radio));
    }

    // --- LÓGICA DA PÁGINA 4: FINALIZAR CORRIDA ---
    const finishRunForm = document.getElementById('finish-run-form');
    if (finishRunForm) {
        const fuelingToggle = document.querySelector('.fueling-toggle');
        const fuelingForm = document.getElementById('fueling-form');
        const icon = fuelingToggle.querySelector('i');
        const baseUrl = document.querySelector('link[href*="assets"]').href.split('/assets/')[0];

        fuelingToggle.addEventListener('click', () => {
            const isHidden = fuelingForm.style.display === 'none' || fuelingForm.style.display === '';
            fuelingForm.style.display = isHidden ? 'block' : 'none';
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        });

        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.fueling-tab-content');
        const calculatedValueWrapper = document.getElementById('calculated-value-wrapper');
        const manualValueWrapper = document.getElementById('manual-value-wrapper');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                button.classList.add('active');
                document.getElementById(`tab-${button.dataset.tab}`).classList.add('active');
                calculatedValueWrapper.style.display = (button.dataset.tab === 'credenciado') ? 'block' : 'none';
                manualValueWrapper.style.display = (button.dataset.tab === 'manual') ? 'block' : 'none';
            });
        });

        const stationSelect = document.getElementById('gas_station_id');
        const fuelSelect = document.getElementById('fuel_type_select');
        const litersInput = document.getElementById('liters');
        const calculatedValueInput = document.getElementById('calculated_value');
        const hiddenCalculatedValueInput = document.getElementById('hidden_calculated_value');
        const maxLiters = parseFloat(litersInput.dataset.maxLiters);

        function calculateTotal() {
            const selectedFuelOption = fuelSelect.options[fuelSelect.selectedIndex];
            if (!selectedFuelOption || !selectedFuelOption.dataset.price) {
                calculatedValueInput.value = '';
                hiddenCalculatedValueInput.value = '';
                return;
            }
            const price = parseFloat(selectedFuelOption.dataset.price);
            const liters = parseFloat(litersInput.value.replace(',', '.'));
            if (!isNaN(price) && !isNaN(liters) && liters > 0) {
                const total = (price * liters).toFixed(2);
                calculatedValueInput.value = `R$ ${total.replace('.', ',')}`;
                hiddenCalculatedValueInput.value = total;
            } else {
                calculatedValueInput.value = '';
                hiddenCalculatedValueInput.value = '';
            }
        }

        stationSelect.addEventListener('change', async () => {
            const stationId = stationSelect.value;
            
            if (!stationId) {
                fuelSelect.innerHTML = '<option value="">-- Escolha um posto primeiro --</option>';
                fuelSelect.disabled = true;
                calculateTotal();
                return;
            }
            
            fuelSelect.innerHTML = '<option value="">Carregando...</option>';
            fuelSelect.disabled = true;
            
            try {
                const response = await fetch(`${baseUrl}/runs/ajax-get-fuels`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ station_id: stationId })
                });
                const data = await response.json();
                if (data.success && data.fuels.length > 0) {
                    fuelSelect.innerHTML = '<option value="">-- Selecione o combustível --</option>';
                    data.fuels.forEach(fuel => {
                        const option = document.createElement('option');
                        option.value = fuel.id; // Envia o ID do combustível
                        option.dataset.price = fuel.price;
                        option.textContent = fuel.name; // Mostra apenas o nome
                        fuelSelect.appendChild(option);
                    });
                    fuelSelect.disabled = false;
                } else {
                    fuelSelect.innerHTML = '<option value="">-- Nenhum combustível encontrado --</option>';
                }
            } catch (error) {
                console.error("Erro ao buscar combustíveis:", error);
                fuelSelect.innerHTML = '<option value="">-- Erro ao carregar --</option>';
            }
        });

        fuelSelect.addEventListener('change', calculateTotal);

        litersInput.addEventListener('input', () => {
            const currentLiters = parseFloat(litersInput.value.replace(',', '.'));
            if (!isNaN(maxLiters) && maxLiters > 0 && currentLiters > maxLiters) {
                litersInput.style.borderColor = 'red';
                litersInput.setCustomValidity(`Valor máximo permitido: ${maxLiters} L`);
            } else {
                litersInput.style.borderColor = '';
                litersInput.setCustomValidity('');
            }
            litersInput.reportValidity();
            calculateTotal();
        });

        const uploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('invoice');
        const previewContainer = document.getElementById('file-preview');
        const imagePreview = document.getElementById('image-preview');
        const fileNamePreview = document.getElementById('file-name-preview');
        const removeBtn = document.getElementById('file-remove');

        uploadArea.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (file) {
                uploadArea.style.display = 'none';
                previewContainer.style.display = 'block';
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        fileNamePreview.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.style.display = 'none';
                    fileNamePreview.textContent = file.name;
                    fileNamePreview.style.display = 'block';
                }
            }
        });

        removeBtn.addEventListener('click', () => {
            fileInput.value = '';
            uploadArea.style.display = 'block';
            previewContainer.style.display = 'none';
        });

        fuelingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const mainFeedbackDiv = document.getElementById('fueling-feedback-main');
            mainFeedbackDiv.innerHTML = 'Salvando...';
            mainFeedbackDiv.className = '';

            const formData = new FormData(fuelingForm);

            try {
                const response = await fetch(`${baseUrl}/runs/fueling/store`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (response.ok) {
                    mainFeedbackDiv.className = 'success';
                    mainFeedbackDiv.textContent = result.message;
                    fuelingForm.reset();
                    removeBtn.click();
                    setTimeout(() => {
                        fuelingForm.style.display = 'none';
                        icon.className = 'fas fa-chevron-down';
                        mainFeedbackDiv.textContent = '';
                        mainFeedbackDiv.className = '';
                    }, 3000);
                } else {
                    mainFeedbackDiv.className = 'error';
                    mainFeedbackDiv.textContent = result.message || 'Ocorreu um erro.';
                }
            } catch (error) {
                mainFeedbackDiv.className = 'error';
                mainFeedbackDiv.textContent = 'Erro de comunicação com o servidor.';
            }
        });
    }
});