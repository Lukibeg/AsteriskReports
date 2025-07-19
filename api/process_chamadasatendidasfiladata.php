<?php
header('Content-Type: application/json');
session_start();

// Verifica os dados
if (empty($_SESSION['fetch_data']) || empty($_GET['fila'])) {
    echo json_encode(['error' => 'Dados não disponíveis ou fila não especificada.']);
    exit;
}

$filaSelecionada = $_GET['fila'];
$data = $_SESSION['fetch_data'];

// Filtra as chamadas pela fila selecionada
$chamadasFiltradas = array_filter($data, function ($chamada) use ($filaSelecionada) {
    return isset($chamada['queuename']) &&
           $chamada['queuename'] === $filaSelecionada &&
           in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT']);
});

// Reorganiza os dados para um array indexado
$resultado = array_values(array_map(function ($chamada) {
    return [
        'callid' => $chamada['callid'] ?? '??',
        'agent' => $chamada['agent'] ?? '-',
        'data' => $chamada['combined_time'] ?? '-',
        'numero' => $chamada['callerid'] ?? '-',
        'evento' => $chamada['event'] ?? '-',
        'wait_time' => $chamada['wait_time'] ?? 0,
        'call_time' => $chamada['call_time'] ?? 0
    ];
}, $chamadasFiltradas));

// Retorna os dados como JSON
if (empty($resultado)) {
    echo json_encode([]); // Retorna array vazio se não houver registros
} else {
    echo json_encode($resultado);
}
exit;
