<?php
session_start();

if (!isset($_SESSION['fetch_data'])) {
    echo json_encode(['error' => 'Nenhum dado disponível']);
    exit;
}

$data = $_SESSION['fetch_data'];
$agentStats = [];
$totalChamadas = 0;

// Processa os dados
foreach ($data as $chamada) {
    if (isset($chamada['agent']) && in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
        $agent = $chamada['agent'];
        if (!isset($agentStats[$agent])) {
            $agentStats[$agent] = [
                'recebidas' => 0,
                'tempo_conversando' => 0,
                'tempo_espera' => 0
            ];
        }

        $agentStats[$agent]['recebidas']++;
        $agentStats[$agent]['tempo_conversando'] += $chamada['call_time'] ?? 0;
        $agentStats[$agent]['tempo_espera'] += $chamada['wait_time'] ?? 0;
        $totalChamadas++;
    }
}

// Calcula os percentuais e formata os dados
foreach ($agentStats as $agent => &$stats) {
    $stats['percentual'] = $totalChamadas > 0 ? round(($stats['recebidas'] / $totalChamadas) * 100, 2) : 0;
    $stats['tma'] = $stats['recebidas'] > 0 ? gmdate("H:i:s", $stats['tempo_conversando'] / $stats['recebidas']) : '00:00:00';
    $stats['tme'] = $stats['recebidas'] > 0 ? gmdate("H:i:s", $stats['tempo_espera'] / $stats['recebidas']) : '00:00:00';
}

// Retorna os dados em JSON
echo json_encode($agentStats);
exit;
