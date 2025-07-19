<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['fetch_data'])) {
    echo json_encode(['error' => 'Nenhum dado encontrado.']);
    exit;
}

$data = $_SESSION['fetch_data'];
$dadosPorDia = [];

// Agrupar as ligações por dia no formato correto
foreach ($data as $entry) {
    $date = date('Y-m-d', strtotime($entry['combined_time'])); // Use o formato ISO para evitar confusão

    if (!isset($dadosPorDia[$date])) {
        $dadosPorDia[$date] = [
            'recebidas' => 0,
            'atendidas' => 0,
            'nao_atendidas' => 0,
            'abandonadas' => 0,
            'transferidas' => 0,
            'wait_time' => 0,
            'call_time' => 0,
            'max_calls' => 0,
            'registros' => [] // Detalhes das ligações para esta data
        ];
    }

    $dadosPorDia[$date]['recebidas']++;

    switch ($entry['event']) {
        case 'COMPLETECALLER':
        case 'COMPLETEAGENT':
            $dadosPorDia[$date]['atendidas']++;
            $dadosPorDia[$date]['wait_time'] += $entry['wait_time'] ?? 0;
            $dadosPorDia[$date]['call_time'] += $entry['call_time'] ?? 0;
            break;
        case 'ABANDON':
            $dadosPorDia[$date]['abandonadas']++;
            break;
        case 'TRANSFER':
            $dadosPorDia[$date]['transferidas']++;
            break;
        case 'CANCEL':
            $dadosPorDia[$date]['abandonadas']++;
            break;
        case 'CHANUNAVAIL':
            $dadosPorDia[$date]['abandonadas']++;
            break;
    }

    // Adicionar os detalhes da chamada
    $dadosPorDia[$date]['registros'][] = [
        'agent' => $entry['agent'] ?? 'N/A',
        'data' => $entry['combined_time'] ?? '',
        'fila' => $entry['queuename'] ?? '',
        'numero' => $entry['callerid'] ?? '',
        'evento' => $entry['event'] ?? '',
        'ringtime' => gmdate('H:i:s', intval($entry['ringtime'] ?? 0)), // Conversão explícita para int
        'wait_time' => gmdate('H:i:s', intval($entry['wait_time'] ?? 0)), // Conversão explícita para int
        'call_time' => gmdate('H:i:s', intval($entry['call_time'] ?? 0)), // Conversão explícita para int
        'recordingfile' => $entry['recordingfile'] ?? null
    ];

    // Registrar a maior chamada (maior tempo de espera)
    $dadosPorDia[$date]['max_calls'] = max($dadosPorDia[$date]['max_calls'], intval($entry['wait_time'] ?? 0));
}

// Calcular métricas adicionais para cada dia
foreach ($dadosPorDia as $date => &$metrics) {
    $tmeSeconds = $metrics['atendidas'] > 0 ? $metrics['wait_time'] / $metrics['atendidas'] : 0;
    $tmaSeconds = $metrics['atendidas'] > 0 ? $metrics['call_time'] / $metrics['atendidas'] : 0;

    // Conversão explícita para int antes de passar para gmdate
    $metrics['tme'] = gmdate('H:i:s', intval($tmeSeconds));
    $metrics['tma'] = gmdate('H:i:s', intval($tmaSeconds));

    $metrics['percent_atendidas'] = $metrics['recebidas'] > 0 ? ($metrics['atendidas'] / $metrics['recebidas']) * 100 : 0;
    $metrics['percent_nao_atendidas'] = 100 - $metrics['percent_atendidas'];
    $metrics['sla'] = $metrics['percent_atendidas'];
}

// Processar a requisição para um dia específico
if (isset($_GET['date'])) {
    $date = $_GET['date'];

    // Verifica se a data existe nos dados agrupados
    if (isset($dadosPorDia[$date])) {
        echo json_encode($dadosPorDia[$date]['registros']);
    } else {
        echo json_encode(['error' => 'Nenhum dado encontrado para a data solicitada.']);
    }
    exit;
}

// Salvar os dados agregados na sessão
$_SESSION['dadosPorDia'] = $dadosPorDia;
