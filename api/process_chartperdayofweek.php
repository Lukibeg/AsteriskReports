<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Verifica se os dados por dia da semana estão disponíveis na sessão
if (empty($_SESSION['dadosPorDiaSemana'])) {
    echo json_encode(['error' => 'Nenhum dado encontrado para os dias da semana.']);
    exit;
}

$dadosPorDiaSemana = $_SESSION['dadosPorDiaSemana'];

// Lista fixa para garantir a ordem dos dias da semana em português
$diasSemana = [
    'segunda-feira',
    'terça-feira',
    'quarta-feira',
    'quinta-feira',
    'sexta-feira',
    'sábado',
    'domingo'
];

// Inicializa os arrays para os dados do gráfico
$labels = [];
$atendidas = [];
$abandonadas = [];
$tme = [];
$tma = [];

// Preenche os dados na ordem correta dos dias da semana
foreach ($diasSemana as $dia) {
    if (isset($dadosPorDiaSemana[$dia])) {
        $labels[] = ucfirst($dia); // Adiciona o nome do dia com a primeira letra maiúscula
        $atendidas[] = $dadosPorDiaSemana[$dia]['atendidas'] ?? 0;
        $abandonadas[] = $dadosPorDiaSemana[$dia]['abandonadas'] ?? 0;
        $tme[] = round($dadosPorDiaSemana[$dia]['tme'], 2); // Tempo médio de espera
        $tma[] = round($dadosPorDiaSemana[$dia]['tma'], 2); // Tempo médio de atendimento
    } else {
        // Garante que todos os dias estejam presentes mesmo que não haja dados
        $labels[] = ucfirst($dia);
        $atendidas[] = 0;
        $abandonadas[] = 0;
        $tme[] = 0;
        $tma[] = 0;
    }
}

// Cria o array de resposta com os dados formatados para o gráfico
$response = [
    'labels' => $labels,
    'atendidas' => $atendidas,
    'abandonadas' => $abandonadas,
    'tme' => $tme,
    'tma' => $tma
];

// Retorna os dados em formato JSON
echo json_encode($response);
exit;
