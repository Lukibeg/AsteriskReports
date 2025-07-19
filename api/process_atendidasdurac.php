<?php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['fetch_data']) || !isset($_GET['intervalo'])) {
    echo json_encode(['error' => 'Dados inválidos ou inexistentes.']);
    exit;
}

$data = $_SESSION['fetch_data'];
$intervalo = (int)$_GET['intervalo'];

// Filtra as chamadas que se encaixam no intervalo
$detalhes = array_filter($data, function ($chamada) use ($intervalo) {
    if (!isset($chamada['call_time']) || !in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT']) || !isset($chamada['callid'])) {
        return false;
    }

    return $intervalo === 150 ? $chamada['call_time'] > 150 : $chamada['call_time'] <= $intervalo;    
});

// Retorna os dados
echo json_encode(array_values($detalhes));
exit;
