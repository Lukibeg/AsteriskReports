<?php
require_once __DIR__ . '/../utils/db_connect.php'; // Conexão ao banco de dados
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se os dados estão disponíveis na sessão
if (empty($_SESSION['fetch_data'])) {
    echo ('Nenhum dado encontrado.');
}

$data = $_SESSION['fetch_data'];
$dadosPorDiaSemana = [];

// Função para obter o dia da semana em português
function getDiaSemana($date)
{
    $dias = [
        'Monday' => 'segunda-feira',
        'Tuesday' => 'terça-feira',
        'Wednesday' => 'quarta-feira',
        'Thursday' => 'quinta-feira',
        'Friday' => 'sexta-feira',
        'Saturday' => 'sábado',
        'Sunday' => 'domingo',
    ];
    $dayEnglish = date('l', strtotime($date));
    return $dias[$dayEnglish] ?? $dayEnglish;
}

// Processa os dados para calcular as métricas por dia da semana
foreach ($data as $entry) {
    $diaSemana = getDiaSemana($entry['combined_time']); // Nome do dia da semana em português

    if (!isset($dadosPorDiaSemana[$diaSemana])) {
        $dadosPorDiaSemana[$diaSemana] = [
            'recebidas' => 0,
            'atendidas' => 0,
            'nao_atendidas' => 0,
            'abandonadas' => 0,
            'transferidas' => 0,
            'tme' => 0,
            'tma' => 0,
            'sla' => 0,
            'registros' => [], // Detalhes das chamadas
        ];
    }

    // Incrementa chamadas recebidas
    $dadosPorDiaSemana[$diaSemana]['recebidas']++;
    $dadosPorDiaSemana[$diaSemana]['registros'][] = $entry;

    switch ($entry['event']) {
        case 'COMPLETECALLER':
        case 'COMPLETEAGENT':
            $dadosPorDiaSemana[$diaSemana]['atendidas']++;
            $dadosPorDiaSemana[$diaSemana]['tme'] += $entry['wait_time'] ?? 0;
            $dadosPorDiaSemana[$diaSemana]['tma'] += $entry['call_time'] ?? 0;
            break;
        case 'CANCEL':
            $dadosPorDiaSemana[$diaSemana]['abandonadas']++;
            break;
        case 'ABANDON':
            $dadosPorDiaSemana[$diaSemana]['abandonadas']++;
            break;
        case 'TRANSFER':
            $dadosPorDiaSemana[$diaSemana]['transferidas']++;
            break;

        case 'CHANUNAVAIL':
            $dadosPorDiaSemana[$diaSemana]['abandonadas']++;
            break;
    }
}

// Calcula métricas adicionais
foreach ($dadosPorDiaSemana as $diaSemana => &$metrics) {
    $metrics['tme'] = $metrics['atendidas'] > 0 ? $metrics['tme'] / $metrics['atendidas'] : 0;
    $metrics['tma'] = $metrics['atendidas'] > 0 ? $metrics['tma'] / $metrics['atendidas'] : 0;
    $metrics['percent_atendidas'] = $metrics['recebidas'] > 0
        ? ($metrics['atendidas'] / $metrics['recebidas']) * 100
        : 0;
    $metrics['percent_nao_atendidas'] = 100 - $metrics['percent_atendidas'];
    $metrics['sla'] = $metrics['percent_atendidas'];
}

// Salva os dados processados na sessão
$_SESSION['dadosPorDiaSemana'] = $dadosPorDiaSemana;
