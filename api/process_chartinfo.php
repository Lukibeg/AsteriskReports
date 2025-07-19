<?php

// Inicia a sessão, se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se os dados necessários estão na sessão
if (!isset($_SESSION['dadosPorFila']) || !isset($_SESSION['friendlyNames'])) {
    if (isset($_GET['output']) && $_GET['output'] === 'json') {
        echo json_encode(['error' => 'Nenhum dado encontrado nas métricas ou nos nomes amigáveis.']);
    }
    return; // Interrompe a execução se o script está sendo incluído
}

// Recupera os dados por fila e os nomes amigáveis da sessão
$dadosPorFila = $_SESSION['dadosPorFila'];
$friendlyNames = $_SESSION['friendlyNames'];

foreach ($queues as $queueNumber) {
    $friendlyNames[$queueNumber] = isset($qnames[$queueNumber]) ? $qnames[$queueNumber] : $queueNumber;
}

// Calcula as métricas para o gráfico
$metricasPorFila = [];

foreach ($dadosPorFila as $fila => $metrics) {
    // Substitui o número da fila pelo nome amigável, se existir
    $filaAmigavel = isset($friendlyNames[$fila]) ? $friendlyNames[$fila] : $fila;

    // Calcula as métricas
    $totalAtendidas = $metrics['total_ligacoes_atendidas'] ?? 0;
    $totalAbandonadas = $metrics['total_ligacoes_abandonadas'] ?? 0;
    $totalWaitTime = $metrics['soma_tempo_espera'] ?? 0;
    $totalTalkTime = $metrics['soma_tempo_conversa'] ?? 0;

    // Verifica se há chamadas para calcular TME e TMA
    $tme = $totalAtendidas > 0 ? round($totalWaitTime / $totalAtendidas, 2) : 0;
    $tma = $totalAtendidas > 0 ? round($totalTalkTime / $totalAtendidas, 2) : 0;


    $metricasPorFila[$filaAmigavel] = [
        'total_ligacoes_atendidas' => $totalAtendidas,
        'total_ligacoes_abandonadas' => $totalAbandonadas,
        'tme' => $totalAtendidas > 0 ? round($totalWaitTime / $totalAtendidas, 2) : 0,
        'tma' => $totalAtendidas > 0 ? round($totalTalkTime / $totalAtendidas, 2) : 0,
    ];
}

// Se for uma requisição para JSON, retorna os dados processados
if (isset($_GET['output']) && $_GET['output'] === 'json') {
    echo json_encode($metricasPorFila);
    exit;
}
