<?php
header('Content-Type: application/json');
session_start();

isset($_SESSION['fetch_data']) ? $data = $_SESSION['fetch_data'] : [];

$peerqueue = [];

foreach ($data as $valores) {
    $event = $valores['event'];
    $numberqueue = $valores['queuename'];
    $date = $valores['combined_time']; // Corrigido nome da variável
    $callerid = $valores['callerid'];

    // Inicializa a fila se não existir
    if (!isset($peerqueue[$numberqueue])) {
        $peerqueue[$numberqueue] = [];
    }

    if (in_array($valores['event'], ['ABANDON', 'CANCEL', 'CHANUNAVAIL'])) {
        // Adiciona os dados à fila correspondente
        $peerqueue[$numberqueue][] = [
            'data' => $date,
            'numero' => $callerid,
            'evento' => $event,
            'tef' => 0, // Não sei qual dado deveria ser atribuído aqui, corrija se necessário
        ];
    }

}

// Retorna JSON
echo json_encode($peerqueue);
exit;
