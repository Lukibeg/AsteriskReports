<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../utils/db_connect.php';


$fetchData = $_SESSION['fetch_data'] ?? null;
$startTime = $_SESSION['startTime'] ?? null;
$endTime = $_SESSION['endTime'] ?? null;
$startDate = $_SESSION['startDate'] . ' ' . $startTime ?? null;
$endDate = $_SESSION['endDate'] . ' ' . $endTime ?? null;

if (!$fetchData) {
    echo json_encode(['error' => 'Nenhum dado encontrado na sessão.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $requestAgent = $_GET['agent'] ?? null;

    // if (!$requestAgent) {
    //     echo json_encode(['error' => 'Nenhum agente especificado.']);
    //     exit;
    // }

    $groupByAgent = [];

    foreach ($fetchData as $data) {
        $agent = $data['agent'] ?? '';

        if ($agent === '') {
            continue; // Ignora dados sem agente
        }

        if ($agent === $requestAgent) {
            $groupByAgent[] = $data;
        }
    }

    // Consulta de chamadas sem resposta /////////////////////////////////////////////////////////////////////////////
    $eventType = 'RINGNOANSWER';

    $queryNoAnswer = "
    SELECT agent, combined_time, queuename, callerid, event, ringtime, callid
    FROM ringnoanswer_log
    WHERE event = ? AND agent = ? AND combined_time BETWEEN ? AND ?
    ORDER BY combined_time DESC
";
    $stmtNoAnswer = $conn->prepare($queryNoAnswer);
    $stmtNoAnswer->bind_param('ssss', $eventType, $requestAgent, $startDate, $endDate);
    $stmtNoAnswer->execute();

    $resultNoAnswer = $stmtNoAnswer->get_result();


    while ($row = $resultNoAnswer->fetch_assoc()) {
        $ringTime = $row['ringtime'];
        if ($ringTime) {
            $row['ringtime'] /= 1000;
        }
        $groupByAgent[] = $row;
    }
    //Encerra aqui a consulta de chamadas sem resposta. /////////////////////////////////////////////////////////////////////////

    if (empty($groupByAgent)) {
        echo json_encode(['error' => 'Nenhum dado encontrado para o agente especificado.']);
        exit;
    }

    // Aqui você pode customizar o retorno para formatar melhor a visualização
    echo json_encode(['success' => 'Requisição processada com sucesso!', 'data' => $groupByAgent]);
    exit;

    if (!isset($groupByAgent[$agent][$data])) {
        $groupByAgent[$agent][] = $data;
    }
}
