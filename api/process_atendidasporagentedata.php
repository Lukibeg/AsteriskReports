<?php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['fetch_data']) || empty($_GET['agente'])) {
    echo json_encode(['error' => 'Dados não disponíveis ou agente não especificado.']);
    exit;
}

$agenteSelecionado = $_GET['agente'];
$data = $_SESSION['fetch_data'];

// Filtra os registros pelo agente selecionado
$chamadasFiltradas = array_filter($data, function ($chamada) use ($agenteSelecionado) {
    return isset($chamada['agent']) && $chamada['agent'] === $agenteSelecionado;
});

// Formata os dados filtrados
$resultado = array_map(function ($chamada) {
    return [
        'callid' => $chamada['callid'] ?? '??',
        'data' => $chamada['combined_time'] ?? '-',
        'numero' => $chamada['callerid'] ?? '-',
        'evento' => $chamada['event'] ?? '-',
        'wait_time' => $chamada['wait_time'] ?? 0,
        'call_time' => $chamada['call_time'] ?? 0
    ];
}, $chamadasFiltradas);

echo empty($resultado) ? json_encode([]) : json_encode(array_values($resultado));
exit;
