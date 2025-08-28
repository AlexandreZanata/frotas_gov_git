document.addEventListener('DOMContentLoaded', function () {
    // Registrar o plugin de rótulos de dados globalmente
    Chart.register(ChartDataLabels);

    // Paleta de cores inspirada no Power BI
    const powerBiPalette = [
        '#01B8AA', '#374649', '#FD625E', '#F2C80F', '#5F6B6D',
        '#8AD4EB', '#FE9666', '#A66999', '#3599B8', '#DFBFBF'
    ];

    // Configurações globais para fontes e estilo (para parecer com Power BI)
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.plugins.tooltip.backgroundColor = '#333';
    Chart.defaults.plugins.tooltip.titleFont.size = 14;
    Chart.defaults.plugins.tooltip.bodyFont.size = 12;
    Chart.defaults.plugins.tooltip.padding = 10;

    // --- Gráfico 1: Corridas por Veículo (Estilo Power BI) ---
    const runsCtx = document.getElementById('runsByVehicleChart')?.getContext('2d');
    if (runsCtx && typeof runsByVehicleData !== 'undefined' && runsByVehicleData.length > 0) {
        // REMOÇÃO: A linha que definia a altura do canvas foi removida para corrigir o bug.
        // O CSS agora controla o tamanho do contêiner.

        new Chart(runsCtx, {
            type: 'bar',
            data: {
                labels: runsByVehicleData.map(item => item.name),
                datasets: [{
                    label: 'Total de Corridas',
                    data: runsByVehicleData.map(item => item.run_count),
                    backgroundColor: powerBiPalette, // Usando a nova paleta
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y', // Mantém o gráfico na horizontal
                responsive: true,
                maintainAspectRatio: false, // Essencial para o contêiner controlar o tamanho
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        displayColors: false,
                    },
                    datalabels: { // Configuração dos rótulos de dados
                        anchor: 'end',
                        align: 'end',
                        color: '#374649',
                        font: {
                            weight: 'bold',
                            size: 12
                        },
                        formatter: (value) => value > 0 ? value : '' // Não mostra '0'
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false // Remove linhas de grade para um visual mais limpo
                        },
                        ticks: {
                           display: false // Oculta os números no eixo X, pois já temos os rótulos
                        },
                        border: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // --- Gráfico 2: Gastos Mensais (Gráfico de Linha Estilo Power BI) ---
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
                    borderColor: powerBiPalette[0], // Cor principal da paleta
                    backgroundColor: powerBiPalette[0], // Usado pelos rótulos e pontos
                    tension: 0.3,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    fill: false // Gráfico de linha sem preenchimento é mais comum no Power BI
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#374649',
                        font: {
                            weight: 'bold'
                        },
                        formatter: (value) => 'R$ ' + parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#eef2f6' // Linhas de grade sutis
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            callback: (value) => 'R$ ' + value.toLocaleString('pt-BR')
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});