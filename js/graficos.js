// Variáveis globais para armazenar instâncias dos gráficos
let charts = {};

document.addEventListener('DOMContentLoaded', function () {
    // Função para destruir gráficos existentes antes de criar novos
    function destroyChart(chartId) {
        if (charts[chartId]) {
            charts[chartId].destroy();
            delete charts[chartId];
        }
    }
// Configuração de "Ligações por Fila"
const chartContainer = document.getElementById('chartContainerPorFila');

const labels = JSON.parse(chartContainer.dataset.labels);
const atendidasData = JSON.parse(chartContainer.dataset.atendidas);
const abandonadasData = JSON.parse(chartContainer.dataset.abandonadas);
const tmeData = JSON.parse(chartContainer.dataset.tme);
const tmaData = JSON.parse(chartContainer.dataset.tma);

// Configuração para o gráfico de "Ligações Atendidas e Abandonadas"
const atendidasConfig = {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Ligações Atendidas',
                data: atendidasData,
                backgroundColor: 'rgba(255, 165, 0, 0.8)',
                borderColor: 'rgba(255, 165, 0, 1)',
                borderWidth: 2
            },
            {
                label: 'Ligações Abandonadas',
                data: abandonadasData,
                backgroundColor: 'rgba(0, 128, 0, 0.8)',
                borderColor: 'rgba(0, 128, 0, 1)',
                borderWidth: 2
            }
        ]
    },
    options: getBaseChartOptions('Ligações por Fila')
};

// Certifique-se de destruir qualquer gráfico anterior antes de criar um novo
destroyChart('ligacoesAtendidasChart');
charts['ligacoesAtendidasChart'] = new Chart(document.getElementById('ligacoesAtendidasChart'), atendidasConfig);


// Configuração para o gráfico de "Tempos Médios"
const temposConfig = {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'TME',
                data: tmeData,
                backgroundColor: 'rgba(255, 0, 0, 0.8)',
                borderColor: 'rgba(255, 0, 0, 1)',
                borderWidth: 1
            },
            {
                label: 'TMA',
                data: tmaData,
                backgroundColor: 'rgba(0, 0, 255, 0.8)',
                borderColor: 'rgba(0, 0, 255, 1)',
                borderWidth: 1
            }
        ]
    },
    options: getBaseChartOptions('Tempos Médios por Fila')
};

destroyChart('temposMediosChart');
charts['temposMediosChart'] = new Chart(document.getElementById('temposMediosChart'), temposConfig);
});


document.addEventListener('DOMContentLoaded', function () {
    // Faz a requisição para buscar os dados do gráfico
    fetch('api/process_chartperday.php')
        .then(response => response.json())
        .then(data => {
            // Verifica se há erro nos dados
            if (data.error) {
                console.error(data.error);
                return;
            }

            // Configuração dos gráficos
            const ligacoesPorDiaConfig = {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Ligações Atendidas',
                            data: data.atendidas,
                            backgroundColor: 'rgba(0, 123, 255, 0.8)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Ligações Abandonadas',
                            data: data.abandonadas,
                            backgroundColor: 'rgba(255, 99, 132, 0.8)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Permite redimensionamento adequado
                    devicePixelRatio: 2, // Aumenta a resolução para telas de alta densidade
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    size: 14 // Aumenta o tamanho da fonte da legenda
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Ligações por Dia',
                            font: {
                                size: 16 // Aumenta o tamanho da fonte do título
                            }
                        },
                        tooltip: {
                            intersect: false,
                            mode: 'index', // Exibe todos os valores no mesmo índice
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
                                    size: 12 // Aumenta o tamanho da fonte do eixo X
                                },
                                maxRotation: 45, // Controla o ângulo da rotação dos rótulos
                                minRotation: 45
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 10,
                                font: {
                                    size: 12 // Aumenta o tamanho da fonte do eixo Y
                                }
                            }
                        }
                    }
                }

            };

            // Inicializa o gráfico
            new Chart(document.getElementById('ligacoesPorDiaChart'), ligacoesPorDiaConfig);
        })
        .catch(error => console.error('Erro ao carregar os dados do gráfico:', error));
});



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


document.addEventListener('DOMContentLoaded', function () {
    // Faz a requisição para buscar os dados do gráfico
    fetch('api/process_chartperhour.php')
        .then(response => response.json())
        .then(data => {
            // Verifica se há erro nos dados
            if (data.error) {
                console.error(data.error);
                return;
            }

            // Configuração dos gráficos
            const ligacoesPorHoraConfig = {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Ligações Atendidas',
                            data: data.atendidas,
                            backgroundColor: 'rgba(0, 123, 255, 0.8)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Ligações Abandonadas',
                            data: data.abandonadas,
                            backgroundColor: 'rgba(255, 99, 132, 0.8)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: getBaseChartOptions('Ligações por Hora') // Reutiliza a configuração base
            };

            // Configuração dos Tempos Médios
            const temposPorHoraConfig = {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'TME (Tempo Médio de Espera)',
                            data: data.tme,
                            backgroundColor: 'rgba(255, 165, 0, 0.8)',
                            borderColor: 'rgba(255, 165, 0, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'TMA (Tempo Médio de Atendimento)',
                            data: data.tma,
                            backgroundColor: 'rgba(0, 128, 0, 0.8)',
                            borderColor: 'rgba(0, 128, 0, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: getBaseChartOptions('Tempos Médios por Hora') // Reutiliza a configuração base
            };

            // Inicializa os gráficos
            new Chart(document.getElementById('ligacoesPorHoraChart'), ligacoesPorHoraConfig);
            new Chart(document.getElementById('temposPorHoraChart'), temposPorHoraConfig);
        })
        .catch(error => console.error('Erro ao carregar os dados do gráfico:', error));
});

document.addEventListener('DOMContentLoaded', function () {
    fetch('api/process_chartperdayofweek.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }

            // Configuração para "Ligações Atendidas por Dia"
            const atendidasConfig = {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Ligações Atendidas',
                            data: data.atendidas,
                            backgroundColor: 'rgba(0, 123, 255, 0.8)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: getBaseChartOptions('Ligações Atendidas por Dia')
            };
            new Chart(document.getElementById('atendidasPorDiaChart'), atendidasConfig);

            // Configuração para "Ligações Não Atendidas por Dia"
            const naoAtendidasConfig = {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Ligações Não Atendidas',
                            data: data.abandonadas,
                            backgroundColor: 'rgba(255, 99, 132, 0.8)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: getBaseChartOptions('Ligações Não Atendidas por Dia')
            };
            new Chart(document.getElementById('naoAtendidasPorDiaChart'), naoAtendidasConfig);

            // Configuração para "Tempo Médio de Espera por Dia"
            const esperaMediaConfig = {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Tempo Médio de Espera (segundos)',
                            data: data.tme,
                            backgroundColor: 'rgba(255, 165, 0, 0.8)',
                            borderColor: 'rgba(255, 165, 0, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: getBaseChartOptions('Tempo Médio de Espera por Dia')
            };
            new Chart(document.getElementById('esperaMediaPorDiaChart'), esperaMediaConfig);

            // Configuração para "Duração Média por Dia"
            const duracaoMediaConfig = {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Duração Média (segundos)',
                            data: data.tma,
                            backgroundColor: 'rgba(0, 128, 0, 0.8)',
                            borderColor: 'rgba(0, 128, 0, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: getBaseChartOptions('Duração Média por Dia')
            };
            new Chart(document.getElementById('duracaoMediaPorDiaChart'), duracaoMediaConfig);
        })
        .catch(error => console.error('Erro ao carregar os dados do gráfico:', error));
});