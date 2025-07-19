<?php
header('Content-Type: application/json');
session_start();

// Verifica se os dados estão disponíveis e se a fila foi especificada
if (empty($_SESSION['fetch_data']) || empty($_GET['evento'])) {
    echo json_encode(['error' => 'Dados não disponíveis ou evento não especificado.']);
    exit;
}


$eventoSelecionado = htmlspecialchars($_GET['evento']); // Sanitiza o parâmetro
$data = $_SESSION['fetch_data'];


// Filtra as chamadas pelo evento selecionado (por exemplo, ABANDON)
$chamadasFiltradas = array_filter($data, function ($chamada) use ($eventoSelecionado) {
    return isset($chamada['event']) && $chamada['event'] === $eventoSelecionado;
});

// Reorganiza os dados para um array indexado
$resultado = array_values(array_map(function ($chamada) {
    return [
        'callid' => htmlspecialchars($chamada['callid'] ?? '??'),
        'fila' => htmlspecialchars($chamada['queuename'] ?? '-'),
        'agent' => htmlspecialchars($chamada['agent'] ?? '-'),
        'data' => htmlspecialchars($chamada['combined_time'] ?? '-'),
        'numero' => htmlspecialchars($chamada['callerid'] ?? '-'),
        'evento' => htmlspecialchars($chamada['event'] ?? '-'),
        'wait_time' => $chamada['wait_time'] ?? 0,
        'call_time' => $chamada['call_time'] ?? 0,
    ];
}, $chamadasFiltradas));

// Retorna os dados como JSON
if (empty($resultado)) {
    echo json_encode(['error' => 'Nenhum registro encontrado para o evento especificado.']);
} else {
    echo json_encode($resultado);
}
exit;
