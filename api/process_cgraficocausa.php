<?php
session_start();
if (!isset($_SESSION['fetch_data'])) {
    echo json_encode(['error' => 'Nenhum dado disponível']);
    exit;
}

$data = $_SESSION['fetch_data'];
$causaStats = [];
$totalCaller = 0;
$totalAgent = 0;

foreach ($data as $chamada) {
    if (isset($chamada['event']) && in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
        $evento = $chamada['event'];

        if ($evento == 'COMPLETECALLER') {
            $causa = $chamada['event'];
            if (!isset($causaStats['Encerradas pelo cliente'])) {
                $causaStats['Encerradas pelo cliente'] = ['completadas' => 0];
            }
            $causaStats['Encerradas pelo cliente']['completadas']++;
        } else {
            $causa = $chamada['event'];
            if (!isset($causaStats['Encerradas pelo agente'])) {
                $causaStats['Encerradas pelo agente'] = ['completadas' => 0];
            }
            $causaStats['Encerradas pelo agente']['completadas']++; 
        }
    }
}
echo json_encode($causaStats);
?>