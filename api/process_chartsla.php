<?php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['fetch_data'])) {
    echo json_encode(['error' => 'Nenhum dado encontrado na sessão.']);
    exit;
}

// Recupera os dados
$data = $_SESSION['fetch_data'];

// Define os intervalos de tempo
$intervalos = [15, 30, 45, 60, 75, 90, 105, 120, 135, 150, '150+'];
$resultados = [];
$deltas = [];

// Inicializa os contadores para cada intervalo
foreach ($intervalos as $limite) {
    $resultados[$limite] = 0;
    $deltas[$limite] = 0; // Inicializamos os deltas com 0
}

// Agrupamento das chamadas por intervalo
foreach ($data as $chamada) {
    if (isset($chamada['wait_time']) && in_array($chamada['event'], ['COMPLETEAGENT', 'COMPLETECALLER'])) {
        $tempoEspera = $chamada['wait_time'];
        $classificado = false;

        foreach ($intervalos as $limite) {
            if ($limite !== '150+' && $tempoEspera <= $limite) {
                $resultados[$limite]++;
                $classificado = true;
                break;
            }
        }

        // Caso o tempo de espera exceda todos os intervalos
        if (!$classificado) {
            $resultados['150+']++;
        }
    }
}

// Calcula os deltas
foreach ($intervalos as $limite) {
    $deltas[$limite] = $resultados[$limite];
}

// Prepara os dados para os gráficos
$labels = [];
$values = [];

// Formata os resultados para o gráfico
foreach ($deltas as $limite => $delta) {
    $labels[] = $limite === '150+' ? '150+ segundos' : $limite . ' segundos';
    $values[] = $delta; // Adiciona os deltas para o gráfico
}

// Verifica erros de codificação no JSON
$jsonOutput = json_encode([
    'labels' => $labels,
    'values' => $values
]);

if ($jsonOutput === false) {
    echo json_encode(['error' => json_last_error_msg()]);
    exit;
}

echo $jsonOutput;
exit;
