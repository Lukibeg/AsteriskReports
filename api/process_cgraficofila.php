<?php
session_start();

if (!isset($_SESSION['fetch_data']) || !isset($_SESSION['friendlyNames'])) {
    echo json_encode(['error' => 'Nenhum dado disponível']);
    exit;
}

$friendlyNames = $_SESSION['friendlyNames'];
$data = $_SESSION['fetch_data'];
$filaStats = [];
$totalChamadas = 0;

// Processa os dados
foreach ($data as $chamada) {
    if (
        isset($chamada['queuename'], $chamada['event']) &&
        in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])
    ) {
        $fila = $chamada['queuename'];
        $filaAmigavel = isset($friendlyNames[$fila]) ? $friendlyNames[$fila] : $fila;

        if (!isset($filaStats[$filaAmigavel])) {
            $filaStats[$filaAmigavel] = ['recebidas' => 0];
        }
        $filaStats[$filaAmigavel]['recebidas']++;
        $totalChamadas++;
    }
}

// Calcula o percentual para cada fila
foreach ($filaStats as $filaAmigavel => &$stats) {
    $stats['percentual'] = $totalChamadas > 0 ? round(($stats['recebidas'] / $totalChamadas) * 100, 2) : 0;
}

// Retorna o JSON no formato esperado
echo json_encode($filaStats);
exit;
