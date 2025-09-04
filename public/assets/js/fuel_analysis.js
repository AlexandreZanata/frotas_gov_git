document.addEventListener('DOMContentLoaded', function () {
    Chart.register(ChartDataLabels);

    const powerBiPalette = ['#01B8AA', '#374649', '#FD625E', '#F2C80F', '#5F6B6D', '#8AD4EB', '#FE9666', '#A66999'];

    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.plugins.tooltip.backgroundColor = '#333';
    Chart.defaults.plugins.datalabels.color = '#fff';
    Chart.defaults.plugins.datalabels.font.weight = 'bold';

    const formatCurrency = (value) => 'R$ ' + parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // 1. Gráfico de Tendência e Projeção
    const trendCtx = document.getElementById('monthlyTrendChart')?.getContext('2d');
    if (trendCtx) {
        const labels = fuelData.map(d => new Date(d.month + '-02').toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' }));
        const forecastLabels = forecastData.map(d => new Date(d.month + '-02').toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' }));
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [...labels, ...forecastLabels],
                datasets: [{
                    label: 'Gasto Real',
                    data: fuelData.map(d => d.total_value),
                    borderColor: powerBiPalette[0],
                    backgroundColor: powerBiPalette[0],
                    tension: 0.3,
                    borderWidth: 3
                }, {
                    label: 'Projeção Futura',
                    data: [...Array(fuelData.length).fill(null), ...forecastData.map(d => d.value)],
                    borderColor: powerBiPalette[2],
                    backgroundColor: powerBiPalette[2],
                    borderDash: [5, 5], // Linha tracejada
                    tension: 0.3,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: { display: false },
                    tooltip: {
                        callbacks: { label: (context) => formatCurrency(context.raw) }
                    }
                },
                scales: {
                    y: {
                        ticks: { callback: (value) => `R$ ${value / 1000}k` }
                    }
                }
            }
        });
    }

    // 2. Gráfico de Pizza (Donut)
    const fuelTypeCtx = document.getElementById('fuelTypeChart')?.getContext('2d');
    if (fuelTypeCtx) {
        new Chart(fuelTypeCtx, {
            type: 'doughnut',
            data: {
                labels: fuelTypeDistribution.map(d => d.name),
                datasets: [{
                    data: fuelTypeDistribution.map(d => d.total_value),
                    backgroundColor: powerBiPalette,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        formatter: (value, ctx) => {
                            const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = (value / total * 100).toFixed(1) + '%';
                            return percentage;
                        }
                    },
                    tooltip: {
                        callbacks: { label: (context) => `${context.label}: ${formatCurrency(context.raw)}` }
                    }
                }
            }
        });
    }

    // 3. Gráfico de Barras Horizontais
    const vehicleCtx = document.getElementById('vehicleSpendingChart')?.getContext('2d');
    if (vehicleCtx) {
        new Chart(vehicleCtx, {
            type: 'bar',
            data: {
                labels: spendingByVehicle.map(d => d.prefix),
                datasets: [{
                    label: 'Gasto por Veículo',
                    data: spendingByVehicle.map(d => d.total_value),
                    backgroundColor: powerBiPalette[1],
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#333',
                        formatter: (value) => formatCurrency(value)
                    },
                    tooltip: {
                        callbacks: { label: (context) => formatCurrency(context.raw) }
                    }
                },
                scales: {
                    x: {
                        ticks: { display: false }
                    }
                }
            }
        });
    }
});