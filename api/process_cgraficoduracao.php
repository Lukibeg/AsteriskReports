<?php
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['fetch_data'])) {
    echo json_encode(['error' => 'Nenhum dado encontrado na sessão.']);
    exit;
}

// Recupera os dados
$data = $_SESSION['fetch_data'];

// Definição dos intervalos de tempo
$intervalos = [15, 30, 45, 60, 75, 90, 105, 120, 135, '150+'];
$agrupadas = [];

// Inicializa o array com valores padrão
foreach ($intervalos as $limite) {
    $agrupadas[$limite] = [
        'recebidas' => 0,
        'completadas' => 0,
        'tempo_conversando' => 0,
        'wait_time' => []
    ];
}

$totalChamadas = 0;

// Processa os dados e agrupa conforme os intervalos
foreach ($data as $chamada) {
    if (isset($chamada['call_time']) && in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
        $duracao = $chamada['call_time'];
        $intervaloSelecionado = '150+';

        // Identifica o intervalo correto
        foreach ($intervalos as $limite) {
            if ($limite !== '150+' && $duracao <= $limite) {
                $intervaloSelecionado = $limite;
                break;
            }
        }

        // Atualiza métricas
        $agrupadas[$intervaloSelecionado]['recebidas']++;
        $agrupadas[$intervaloSelecionado]['completadas']++;
        $agrupadas[$intervaloSelecionado]['tempo_conversando'] += $duracao;
        $agrupadas[$intervaloSelecionado]['wait_time'][] = $chamada['wait_time'] ?? 0;
        $totalChamadas++;
    }
}

// Retorna os dados como JSON
echo json_encode($agrupadas);
exit;
