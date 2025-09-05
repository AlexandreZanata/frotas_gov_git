document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    const mobileToggle = document.getElementById('menu-toggle');
    const desktopToggle = document.getElementById('desktop-menu-toggle');
    const mql = window.matchMedia('(max-width: 768px)');

    const setAriaExpanded = (expanded) => {
        if (mobileToggle) mobileToggle.setAttribute('aria-expanded', String(expanded));
        if (desktopToggle) desktopToggle.setAttribute('aria-expanded', String(expanded));
    };

    const openMobileSidebar = () => {
        body.classList.add('sidebar-open');
        if (overlay) overlay.classList.add('active');
        setAriaExpanded(true);
    };

    const closeMobileSidebar = () => {
        body.classList.remove('sidebar-open');
        if (overlay) overlay.classList.remove('active');
        setAriaExpanded(false);
    };

    const toggleDesktopSidebar = () => {
        body.classList.toggle('sidebar-collapsed');
        setAriaExpanded(!body.classList.contains('sidebar-collapsed'));
    };

    const handleToggleClick = (e) => {
        e.stopPropagation();
        if (mql.matches) {
            if (body.classList.contains('sidebar-open')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        } else {
            toggleDesktopSidebar();
        }
    };

    // Botões (mobile e desktop)
    if (mobileToggle) {
        mobileToggle.setAttribute('aria-controls', 'sidebar');
        mobileToggle.addEventListener('click', handleToggleClick);
    }
    if (desktopToggle) {
        desktopToggle.setAttribute('aria-controls', 'sidebar');
        desktopToggle.addEventListener('click', handleToggleClick);
    }

    // Fecha ao clicar no overlay (mobile)
    if (overlay) {
        overlay.addEventListener('click', () => {
            if (mql.matches) closeMobileSidebar();
        });
    }

    // Fecha com ESC (mobile)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && body.classList.contains('sidebar-open')) {
            closeMobileSidebar();
        }
    });

    // ---------- Gráficos: omitir no celular, inicializar no desktop ----------
    let chartsInitialized = false;
    let runsChartInstance = null;
    let fuelChartInstance = null;

    function initChartsOnce() {
        if (chartsInitialized) return;
        chartsInitialized = true;

        if (typeof Chart === 'undefined') return;

        // Registrar plugin se disponível
        if (typeof ChartDataLabels !== 'undefined') {
            Chart.register(ChartDataLabels);
        }

        // Paleta e defaults
        const powerBiPalette = [
            '#01B8AA', '#374649', '#FD625E', '#F2C80F', '#5F6B6D',
            '#8AD4EB', '#FE9666', '#A66999', '#3599B8', '#DFBFBF'
        ];

        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.plugins.tooltip.backgroundColor = '#333';
        Chart.defaults.plugins.tooltip.titleFont.size = 14;
        Chart.defaults.plugins.tooltip.bodyFont.size = 12;
        Chart.defaults.plugins.tooltip.padding = 10;

        // Gráfico 1: Corridas por Veículo
        const runsCtx = document.getElementById('runsByVehicleChart')?.getContext('2d');
        if (runsCtx && typeof runsByVehicleData !== 'undefined' && Array.isArray(runsByVehicleData) && runsByVehicleData.length > 0) {
            runsChartInstance = new Chart(runsCtx, {
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
        if (fuelCtx && typeof monthlyFuelData !== 'undefined' && Array.isArray(monthlyFuelData) && monthlyFuelData.length > 0) {
            fuelChartInstance = new Chart(fuelCtx, {
                type: 'line',
                data: {
                    labels: monthlyFuelData.map(item => {
                        const [year, month] = String(item.month).split('-');
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
                                callback: (value) => 'R$ ' + Number(value).toLocaleString('pt-BR')
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
    }

    // Inicializa gráficos somente se NÃO for mobile
    if (!mql.matches) {
        initChartsOnce();
    }

    // Ao mudar para desktop (deixar de ser mobile), inicializa os gráficos
    const onViewportChange = (e) => {
        if (!e.matches) { // matches == true => mobile; false => desktop
            initChartsOnce();
        }
    };
    if (mql.addEventListener) {
        mql.addEventListener('change', onViewportChange);
    } else if (mql.addListener) {
        mql.addListener(onViewportChange);
    }
});