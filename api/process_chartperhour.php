<?php
session_start();

// Verifica se os dados existem
if (!isset($_SESSION['dadosPorHora'])) {
    echo json_encode(['error' => 'Nenhum dado encontrado para o gráfico.']);
    exit;
}

// Recupera os dados processados
$dadosPorHora = $_SESSION['dadosPorHora'];

// Inicializa os arrays de dados
$labels = [];
$atendidas = [];
$abandonadas = [];
$tme = [];
$tma = [];

// Processa os dados para o gráfico
foreach ($dadosPorHora as $hora => $metrics) {
    $labels[] = $hora;
    $atendidas[] = $metrics['total_ligacoes_atendidas'];
    $abandonadas[] = $metrics['total_ligacoes_abandonadas'];
    $tme[] = round($metrics['total_tme'], 2); // Arredonda o TME
    $tma[] = round($metrics['total_tma'], 2); // Arredonda o TMA
}

// Retorna os dados em formato JSON
echo json_encode([
    'labels' => $labels,
    'atendidas' => $atendidas,
    'abandonadas' => $abandonadas,
    'tme' => $tme,
    'tma' => $tma,
]);
