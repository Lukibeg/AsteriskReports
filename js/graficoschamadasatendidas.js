document.addEventListener('DOMContentLoaded', function () {
    // Variáveis globais para armazenar as instâncias dos gráficos
    let charts = {};

    // Função para destruir gráficos existentes antes de criar novos
    function destroyChart(chartId) {
        if (charts[chartId]) {
            charts[chartId].destroy();
            delete charts[chartId];
        }
    }

    // Função base para configurações dos gráficos
    function getBaseChartOptions(titleText) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            devicePixelRatio: 2,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                },
                title: {
                    display: true,
                    text: titleText,
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    intersect: false,
                    mode: 'index',
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#ccc',
                    borderWidth: 1,
                    callbacks: {
                        label: function (context) {
                            return `${context.dataset.label}: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    barPercentage: 0.7,
                    categoryPercentage: 0.8,
                    ticks: {
                        font: {
                            size: 12
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 10,
                        font: {
                            size: 12
                        }
                    }
                }
            }
        };
    }

    // Função para renderizar o gráfico de Delta
    function renderDeltaChart() {
        fetch('api/process_chartsla.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }

                const deltaCanvas = document.getElementById('deltaChart');
                if (!deltaCanvas) {
                    console.error('Elemento <canvas> com ID "deltaChart" não encontrado.');
                    return;
                }

                if (!data.labels || !data.values) {
                    console.error('Dados inválidos para o gráfico de Delta:', data);
                    return;
                }

                const deltaConfig = {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Delta (Chamadas por Intervalo)',
                                data: data.values,
                                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                                borderColor: 'rgba(0, 123, 255, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: getBaseChartOptions('Delta de Chamadas por Intervalo')
                };

                destroyChart('deltaChart');
                charts['deltaChart'] = new Chart(deltaCanvas, deltaConfig);
            })
            .catch(error =>
                console.error('Erro ao carregar os dados do gráfico de Delta:', error)
            );
    }

    // Função para renderizar o gráfico de Atendidas por Fila
    function renderAtendidasPorFilaChart() {
        fetch('api/process_cgraficofila.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Erro ao carregar os dados do gráfico de Atendidas por Fila:', data.error);
                    return;
                }

                const canvas = document.getElementById('atendidasPorFilaChart');
                if (!canvas) {
                    console.error('Elemento <canvas> com ID "atendidasPorFilaChart" não encontrado.');
                    return;
                }

                const labels = Object.keys(data);
                const values = labels.map(key => data[key].recebidas);
                const percentages = labels.map(key => data[key].percentual);

                const config = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Chamadas Recebidas',
                                data: values,
                                backgroundColor: 'rgba(153, 102, 255, 0.8)',
                                borderColor: 'rgba(153, 102, 255, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Percentual de Chamadas (%)',
                                data: percentages,
                                backgroundColor: 'rgba(255, 205, 86, 0.8)',
                                borderColor: 'rgba(255, 205, 86, 1)',
                                borderWidth: 1,
                                type: 'line',
                                yAxisID: 'percentAxis'
                            }
                        ]
                    },
                    options: {
                        ...getBaseChartOptions('Atendidas por Fila'),
                        scales: {
                            ...getBaseChartOptions().scales,
                            percentAxis: {
                                position: 'right',
                                beginAtZero: true,
                                ticks: {
                                    callback: value => `${value}%`,
                                    font: { size: 12 }
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                };

                destroyChart('atendidasPorFilaChart');
                charts['atendidasPorFilaChart'] = new Chart(canvas, config);
            })
            .catch(error => console.error('Erro ao carregar os dados do gráfico de Atendidas por Fila:', error));
    }

    function renderAtendidasPorAgenteChart() {
        fetch('api/process_cgraficoagente.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Erro ao carregar os dados do gráfico de Atendidas por Agente:', data.error);
                    return;
                }

                const canvas = document.getElementById('atendidasPorAgenteChart');
                if (!canvas) {
                    console.error('Elemento <canvas> com ID "atendidasPorAgenteChart" não encontrado.');
                    return;
                }

                // Verifica se os dados estão no formato esperado
                if (typeof data !== 'object' || Object.keys(data).length === 0) {
                    console.error('Nenhum dado encontrado para o gráfico de Atendidas por Agente.');
                    return;
                }

                // Extrai os labels (nomes dos agentes) e os valores (número de chamadas atendidas)
                const labels = Object.keys(data); // Nomes dos agentes
                const values = labels.map(key => data[key]?.recebidas || 0); // Chamadas atendidas
                const percentuais = labels.map(key => data[key]?.percentual || 0); // Percentual de chamadas

                const config = {
                    type: 'bar',
                    data: {
                        labels: labels, // Labels com os nomes dos agentes
                        datasets: [
                            {
                                label: 'Chamadas Recebidas',
                                data: values,
                                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Percentual (%)',
                                data: percentuais,
                                backgroundColor: 'rgba(255, 205, 86, 0.8)',
                                borderColor: 'rgba(255, 205, 86, 1)',
                                borderWidth: 1,
                                type: 'line', // Tipo de gráfico adicional
                                yAxisID: 'percentAxis' // Adiciona eixo Y secundário
                            }
                        ]
                    },
                    options: {
                        ...getBaseChartOptions('Atendidas por Agente'),
                        scales: {
                            ...getBaseChartOptions().scales,
                            percentAxis: {
                                position: 'right',
                                beginAtZero: true,
                                ticks: {
                                    callback: value => `${value}%`,
                                    font: { size: 12 }
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                };

                destroyChart('atendidasPorAgenteChart');
                charts['atendidasPorAgenteChart'] = new Chart(canvas, config);
            })
            .catch(error => console.error('Erro ao carregar os dados do gráfico de Atendidas por Agente:', error));
    }

    // Função para renderizar o gráfico de Causas
    // Função para renderizar o gráfico de Causas
    function renderCausaChart() {
        fetch('api/process_cgraficocausa.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Erro ao carregar os dados do gráfico de Causas:', data.error);
                    return;
                }

                const canvas = document.getElementById('causaChart');
                if (!canvas) {
                    console.error('Elemento <canvas> com ID "causaChart" não encontrado.');
                    return;
                }

                // Extrai os labels e os valores do JSON retornado
                const labels = Object.keys(data);
                const values = labels.map(key => data[key]?.completadas || 0);

                const config = {
                    type: 'pie', // Gráfico de Pizza
                    data: {
                        labels: labels, // Labels com os tipos de causas
                        datasets: [
                            {
                                label: 'Causas de Chamadas',
                                data: values,
                                backgroundColor: [
                                    'rgba(54, 162, 235, 0.8)',
                                    'rgba(255, 99, 132, 0.8)'
                                ],
                                borderColor: [
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 99, 132, 1)'
                                ],
                                borderWidth: 1
                            }
                        ]
                    },
                    options: getBaseChartOptions('Causas de Chamadas')
                };

                destroyChart('causaChart');
                charts['causaChart'] = new Chart(canvas, config);
            })
            .catch(error => console.error('Erro ao carregar os dados do gráfico de Causas:', error));
    }

    // Função para renderizar o gráfico de duração das chamadas
    function renderDuracaoChart() {
        fetch('api/process_cgraficoduracao.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Erro ao carregar os dados do gráfico de Duração:', data.error);
                    return;
                }

                const canvas = document.getElementById('duracaoChart');
                if (!canvas) {
                    console.error('Elemento <canvas> com ID "duracaoChart" não encontrado.');
                    return;
                }

                // Verifica se os dados estão no formato esperado
                if (typeof data !== 'object' || Object.keys(data).length === 0) {
                    console.error('Nenhum dado encontrado para o gráfico de Duração.');
                    return;
                }

                // Extrai os labels (intervalos) e os valores (número de chamadas completadas)
                const labels = Object.keys(data);
                const values = labels.map(key => data[key]?.completadas || 0);
                const tempoMedioConversando = labels.map(key => {
                    const completadas = data[key]?.completadas || 1; // Evita divisão por zero
                    return data[key]?.tempo_conversando / completadas;
                });

                const config = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Chamadas Completadas',
                                data: values,
                                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Tempo Médio de Conversa (s)',
                                data: tempoMedioConversando,
                                backgroundColor: 'rgba(153, 102, 255, 0.8)',
                                borderColor: 'rgba(153, 102, 255, 1)',
                                borderWidth: 1,
                                type: 'line',
                                yAxisID: 'tempoAxis'
                            }
                        ]
                    },
                    options: {
                        ...getBaseChartOptions('Duração das Chamadas por Intervalo'),
                        scales: {
                            ...getBaseChartOptions().scales,
                            tempoAxis: {
                                position: 'right',
                                beginAtZero: true,
                                ticks: {
                                    callback: value => `${value.toFixed(2)}s`,
                                    font: { size: 12 }
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                };

                destroyChart('duracaoChart');
                charts['duracaoChart'] = new Chart(canvas, config);
            })
            .catch(error => console.error('Erro ao carregar os dados do gráfico de Duração:', error));
    }

    // Renderiza o gráfico de causas
    renderDuracaoChart();
    renderCausaChart();
    renderDeltaChart();
    renderAtendidasPorFilaChart();
    renderAtendidasPorAgenteChart();
});
