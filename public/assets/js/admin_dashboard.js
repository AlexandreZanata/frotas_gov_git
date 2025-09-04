document.addEventListener('DOMContentLoaded', function () {
    // --- LÓGICA DO MENU SIDEBAR (NOVO E MELHORADO) ---
    const body = document.body;
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    // Seleciona tanto o botão mobile quanto o novo botão desktop
    const menuToggleButtons = document.querySelectorAll('#menu-toggle, #desktop-menu-toggle');

    // Função unificada para alternar o estado da sidebar
    const toggleSidebar = () => {
        body.classList.toggle('sidebar-collapsed');
        
        // Em telas mobile, o overlay também precisa ser controlado
        if (window.innerWidth <= 768) {
            // Verifica se a sidebar está sendo aberta ou fechada para controlar o overlay
            const isSidebarOpen = !body.classList.contains('sidebar-collapsed');
            if(overlay) {
                overlay.classList.toggle('active', isSidebarOpen);
            }
        }
    };

    // Adiciona o evento de clique a todos os botões de toggle
    menuToggleButtons.forEach(button => {
        button.addEventListener('click', toggleSidebar);
    });

    // Fecha a sidebar ao clicar no overlay (apenas em modo mobile)
    if (overlay) {
        overlay.addEventListener('click', () => {
            // Só fecha se a tela for pequena e a sidebar estiver aberta
            if (window.innerWidth <= 768 && !body.classList.contains('sidebar-collapsed')) {
                toggleSidebar();
            }
        });
    }


    // --- CÓDIGO EXISTENTE DOS GRÁFICOS (MANTIDO) ---
    
    // Registrar o plugin de rótulos de dados globalmente
    if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    // Paleta de cores inspirada no Power BI
    const powerBiPalette = [
        '#01B8AA', '#374649', '#FD625E', '#F2C80F', '#5F6B6D',
        '#8AD4EB', '#FE9666', '#A66999', '#3599B8', '#DFBFBF'
    ];

    // Configurações globais para fontes e estilo
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.plugins.tooltip.backgroundColor = '#333';
        Chart.defaults.plugins.tooltip.titleFont.size = 14;
        Chart.defaults.plugins.tooltip.bodyFont.size = 12;
        Chart.defaults.plugins.tooltip.padding = 10;
    }

    // Gráfico 1: Corridas por Veículo
    const runsCtx = document.getElementById('runsByVehicleChart')?.getContext('2d');
    if (runsCtx && typeof runsByVehicleData !== 'undefined' && runsByVehicleData.length > 0) {
        new Chart(runsCtx, {
            type: 'bar',
            data: {
                labels: runsByVehicleData.map(item => item.name),
                datasets: [{
                    label: 'Total de Corridas',
                    data: runsByVehicleData.map(item => item.run_count),
                    backgroundColor: powerBiPalette,
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { displayColors: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#374649',
                        font: { weight: 'bold', size: 12 },
                        formatter: (value) => value > 0 ? value : ''
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { display: false },
                        border: { display: false }
                    },
                    y: {
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    }

    // Gráfico 2: Gastos Mensais com Combustível
    const fuelCtx = document.getElementById('fuelExpensesChart')?.getContext('2d');
    if (fuelCtx && typeof monthlyFuelData !== 'undefined' && monthlyFuelData.length > 0) {
        new Chart(fuelCtx, {
            type: 'line',
            data: {
                labels: monthlyFuelData.map(item => {
                    const [year, month] = item.month.split('-');
                    return `${month}/${year}`;
                }),
                datasets: [{
                    label: 'Gasto Total (R$)',
                    data: monthlyFuelData.map(item => item.total_value),
                    borderColor: powerBiPalette[0],
                    backgroundColor: powerBiPalette[0],
                    tension: 0.3,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#374649',
                        font: { weight: 'bold' },
                        formatter: (value) => 'R$ ' + parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#eef2f6' },
                        border: { display: false },
                        ticks: {
                            callback: (value) => 'R$ ' + value.toLocaleString('pt-BR')
                        }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    }
});