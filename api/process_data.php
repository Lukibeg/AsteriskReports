<?php

session_start();

$data = $_SESSION['fetch_data'] ?? [];

// Função para formatar tempo em hh:mm:ss
function format_time($seconds)
{
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}

// Função para calcular métricas
function calculate_metrics($data)
{
    $metrics = [
        'total_calls' => 0,
        'total_connects' => 0,
        'total_abandons' => 0,
        'total_duration' => 0,
        'total_transfers' => 0,
        'total_timeouts' => 0,
        'total_noanswer' => 0,
        'total_chanunavail' => 0,
        'total_wait_time_abandons' => 0, // Soma total do tempo de espera de abandonos
    ];

    foreach ($data as $row) {
        $event = $row['event'] ?? '';
        $wait_time = $row['wait_time'] ?? 0;
        $call_time = $row['call_time'] ?? 0;

        switch ($event) {
            case 'COMPLETECALLER':
            case 'COMPLETEAGENT':
                $metrics['total_connects']++;
                $metrics['total_duration'] += $call_time;
                $metrics['total_calls']++;
                break;
            case 'ABANDON':
                $metrics['total_abandons']++;
                $metrics['total_calls']++;
                $metrics['total_wait_time_abandons'] += $wait_time;
                break;
            case 'TRANSFER':
                $metrics['total_transfers']++;
                break;
            case 'EXITWITHTIMEOUT':
                $metrics['total_timeouts']++;
                break;
            case 'CANCEL':
                $metrics['total_abandons']++;
                $metrics['total_calls']++;
                break;
            case 'CHANUNAVAIL':
                $metrics['total_chanunavail']++;
                $metrics['total_abandons']++;
                $metrics['total_calls']++;
                break;
        }
    }

    // Calcula a espera média antes de abandonar
    $metrics['average_wait_time_abandons'] = $metrics['total_abandons'] > 0 ? $metrics['total_wait_time_abandons'] / $metrics['total_abandons'] : 0;

    return $metrics;
}

// Calcula as métricas
$metrics = calculate_metrics($data);

// Formata o tempo médio de espera para exibição
$average_wait_time_formatted = format_time($metrics['average_wait_time_abandons']);

?>
