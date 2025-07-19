<?php
header('Content-Type: application/json');
session_start();

// Verifica se os dados estão disponíveis na sessão
if (empty($_SESSION['fetch_data'])) {
    echo json_encode(['error' => 'Nenhum dado encontrado na sessão.']);
    exit;
}

// Recupera os dados
$data = $_SESSION['fetch_data'];

// Parâmetro do tempo limite vindo da requisição
$tempoLimite = isset($_GET['tempo_limite']) ? $_GET['tempo_limite'] : null;
if ($tempoLimite === null) {
    echo json_encode(['error' => 'Tempo limite não especificado.']);
    exit;
}

// Define os intervalos de tempo
$intervalos = [15, 30, 45, 60, 75, 90, 105, 120, 135, 150];
$limiteAnterior = 0;

// Determina o limite anterior com base no intervalo atual
foreach ($intervalos as $limite) {
    if ($limite == $tempoLimite) {
        break;
    }
    $limiteAnterior = $limite;
}

// Ajusta a filtragem para o intervalo 150+ (caso especial)
$chamadasFiltradas = array_filter($data, function ($chamada) use ($tempoLimite, $limiteAnterior) {
    if ($tempoLimite == '150+') {
        // Para 150+, filtra registros com wait_time > 150
        return isset($chamada['wait_time']) &&
               $chamada['wait_time'] > 150 &&
               in_array($chamada['event'], ['COMPLETEAGENT', 'COMPLETECALLER']);
    } else {
        // Filtragem normal para intervalos regulares
        return isset($chamada['wait_time']) &&
               $chamada['wait_time'] > $limiteAnterior &&
               $chamada['wait_time'] <= $tempoLimite &&
               in_array($chamada['event'], ['COMPLETEAGENT', 'COMPLETECALLER']);
    }
});

// Monta os registros somente com os campos necessários
$resultado = array_map(function ($chamada) {
    return [
        'agente' => $chamada['agent'] ?? '-',
        'data' => $chamada['combined_time'] ?? '-',
        'numero' => $chamada['callerid'] ?? '-',
        'tme' => $chamada['wait_time'] ?? '-',
        'tma' => $chamada['call_time'] ?? '-',
        'evento' => $chamada['event'] ?? '-'
    ];
}, $chamadasFiltradas);

// Retorna os dados filtrados como JSON
if (empty($resultado)) {
    echo json_encode(['error' => 'Nenhuma chamada encontrada para o período especificado.']);
} else {
    echo json_encode(array_values($resultado)); // Garante que o array é indexado
}
exit;
