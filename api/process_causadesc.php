<?php
header('Content-Type: application/json');
session_start();

// Verifica se os dados estão disponíveis na sessão
if (empty($_SESSION['fetch_data'])) {
    echo json_encode(['error' => 'Nenhum dado encontrado na sessão.']);
    exit;
}

$data = $_SESSION['fetch_data'];
$causa = isset($_GET['causa']) ? $_GET['causa'] : null;

// Valida a causa
if (!$causa || !in_array($causa, ['COMPLETEAGENT', 'COMPLETECALLER'])) {
    echo json_encode(['error' => 'Causa de desconexão inválida ou não especificada.']);
    exit;
}

// Filtra os registros pela causa
$chamadasFiltradas = array_filter($data, function ($chamada) use ($causa) {
    return isset($chamada['event']) && $chamada['event'] === $causa;
});

// Mapeia os registros para os campos necessários
$resultado = array_map(function ($chamada) {
    return [
        'callid' => $chamada['callid'] ?? '??',
        'agente' => $chamada['agent'] ?? '-',
        'data' => $chamada['combined_time'] ?? '-',
        'numero' => $chamada['callerid'] ?? '-',
        'evento' => $chamada['event'] ?? '-',
        'wait_time' => $chamada['wait_time'] ?? '0',
        'call_time' => $chamada['call_time'] ?? '0',
    ];
}, $chamadasFiltradas);

// Retorna os dados filtrados
if (empty($resultado)) {
    echo json_encode(['error' => 'Nenhuma chamada encontrada para a causa selecionada.']);
} else {
    echo json_encode(array_values($resultado));
}
exit;
