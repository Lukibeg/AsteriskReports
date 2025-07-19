<?php
require_once __DIR__ . '/../utils/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se os dados estão disponíveis na sessão
if (!isset($_SESSION['fetch_data'])) {
    echo json_encode(['error' => 'Nenhum dado encontrado.']);
    exit;
}

$data = $_SESSION['fetch_data'];

// Função para processar as métricas para a fila "Ligações Efetuadas"
function calculate_outbound_metrics($data)
{
    $outboundMetrics = [
        'total_ligacoes_recebidas' => 0,
        'total_ligacoes_atendidas' => 0,
        'total_ligacoes_nao_atendidas' => 0,
        'total_ligacoes_abandonadas' => 0,
        'total_ligacoes_transferidas' => 0,
        'soma_tempo_espera' => 0,
        'soma_tempo_conversa' => 0,
        'tme' => 0,
        'tmc' => 0,
        'percentual_atendidas' => 0,
        'percentual_nao_atendidas' => 0,
        'sla' => 0,
        'chamadas' => []
    ];

    foreach ($data as $entry) {
        // Filtrar apenas chamadas da fila "Ligações Efetuadas"
        if ($entry['queuename'] === 'Ligações Efetuadas') {
            $outboundMetrics['total_ligacoes_recebidas']++;

            // Classificar os eventos
            if (in_array($entry['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
                $outboundMetrics['total_ligacoes_atendidas']++;
                $outboundMetrics['soma_tempo_conversa'] += $entry['call_time'] ?? 0;
                $outboundMetrics['soma_tempo_espera'] += $entry['wait_time'] ?? 0;
            } elseif ($entry['event'] === 'CANCEL') {
                $outboundMetrics['total_ligacoes_nao_atendidas']++;
            } elseif ($entry['event'] === 'ABANDON' || $entry['event'] === 'CHANUNAVALIABLE') {
                $outboundMetrics['total_ligacoes_abandonadas']++;
            } elseif ($entry['event'] === 'TRANSFER') {
                $outboundMetrics['total_ligacoes_transferidas']++;
            }

            // Adicionar detalhes da chamada
            $outboundMetrics['chamadas'][] = [
                'callid' => $entry['callid'],
                'agente' => $entry['agent'],
                'data' => $entry['combined_time'],
                'fila' => $entry['queuename'],
                'numero' => $entry['callerid'],
                'evento' => $entry['event'],
                'ringtime' => $entry['ringtime'],
                'wait_time' => $entry['wait_time'],
                'talk_time' => $entry['call_time'],
                'recordingfile' => $entry['recordingfile'] ?? null
            ];
        }
    }

    // Calcula os tempos médios e percentuais
    $total_atendidas = $outboundMetrics['total_ligacoes_atendidas'];
    $total_recebidas = $outboundMetrics['total_ligacoes_recebidas'];

    // Calcula TME e TMC e formata no formato 00:00:00
    $tme_seconds = $total_recebidas > 0 ? intval(round($outboundMetrics['soma_tempo_espera'] / $total_recebidas)) : 0;
    $tmc_seconds = $total_atendidas > 0 ? intval(round($outboundMetrics['soma_tempo_conversa'] / $total_atendidas)) : 0;

    $outboundMetrics['tme'] = gmdate('H:i:s', $tme_seconds);
    $outboundMetrics['tmc'] = gmdate('H:i:s', $tmc_seconds);

    $outboundMetrics['percentual_atendidas'] = $total_recebidas > 0 ? ($total_atendidas / $total_recebidas) * 100 : 0;
    $outboundMetrics['percentual_nao_atendidas'] = $total_recebidas > 0 ? 100 - $outboundMetrics['percentual_atendidas'] : 0;
    $outboundMetrics['sla'] = $outboundMetrics['percentual_atendidas']; // SLA assumido como % atendidas

    return $outboundMetrics;
}

// Processa as métricas se os dados estiverem disponíveis
if (!empty($data)) {
    $outboundMetrics = calculate_outbound_metrics($data);
    $_SESSION['outboundMetrics'] = $outboundMetrics; // Armazena as métricas na sessão
} else {
    $_SESSION['outboundMetrics'] = []; // Define como vazio se não houver dados
}

// Retorna os dados detalhados para a tabela de detalhes
if (isset($_GET['detalhes']) && $_GET['detalhes'] === 'true') {
    if (!empty($_SESSION['outboundMetrics']['chamadas'])) {
        header('Content-Type: application/json');
        echo json_encode($_SESSION['outboundMetrics']['chamadas']);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Nenhum registro encontrado para exibição de detalhes.']);
        exit;
    }
}

