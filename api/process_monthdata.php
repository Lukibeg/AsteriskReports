<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!function_exists('format_time')) {
    function format_time($seconds)
    {
        return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
    }
}


!empty($_SESSION['fetch_data']) ? $data = $_SESSION['fetch_data'] : '';

$dadosPorMes = [];

// Agrupando os dados por mês e ano
if (!empty($data)) {
    foreach ($data as $registro) {
        $combined_time = $registro['combined_time'];
        $timestamp = strtotime($combined_time);
        $mes = date('m', $timestamp);
        $ano = date('Y', $timestamp);
        $chaveMes = $mes . '-' . $ano;

        if (!isset($dadosPorMes[$chaveMes])) {
            $dadosPorMes[$chaveMes] = [];
        }
        $dadosPorMes[$chaveMes][] = $registro;
    }

    // Métricas por mês
    $metricasPorMes = [];
    foreach ($dadosPorMes as $mesAno => $registros) {
        $metricasPorMes[$mesAno] = [
            'total_ligacoes_recebidas' => count($registros),
            'total_ligacoes_atendidas' => 0,
            'total_ligacoes_nao_atendidas' => 0,
            'total_ligacoes_abandonadas' => 0,
            'total_ligacoes_transferidas' => 0,
            'soma_duracao_atendimento' => 0,
            'soma_tempo_espera' => 0,
            'soma_tempo_conversa' => 0,
            'sla' => 0,
        ];

        foreach ($registros as $registro) {
            // Se a ligação foi atendida
            if (in_array($registro['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
                $metricasPorMes[$mesAno]['total_ligacoes_atendidas']++;
                $metricasPorMes[$mesAno]['soma_duracao_atendimento'] += $registro['call_time'];
                $metricasPorMes[$mesAno]['soma_tempo_espera'] += $registro['wait_time'];
                $metricasPorMes[$mesAno]['soma_tempo_conversa'] += $registro['call_time'];
            } elseif ($registro['event'] == 'ABANDON' || $registro['event'] == 'CHANUNAVAIL' || $registro['event'] == 'CANCEL') {
                $metricasPorMes[$mesAno]['total_ligacoes_abandonadas']++;
            } elseif ($registro['event'] == 'TRANSFER') {
                $metricasPorMes[$mesAno]['total_ligacoes_transferidas']++;
            }
        }

        // Calcula percentuais
        $total_ligacoes = $metricasPorMes[$mesAno]['total_ligacoes_recebidas'];
        $metricasPorMes[$mesAno]['percentual_atendidas'] = ($metricasPorMes[$mesAno]['total_ligacoes_atendidas'] / $total_ligacoes) * 100;
        $metricasPorMes[$mesAno]['percentual_nao_atendidas'] = 100 - $metricasPorMes[$mesAno]['percentual_atendidas'];

        // Cálculo de TME e TMC
        $total_atendidas = $metricasPorMes[$mesAno]['total_ligacoes_atendidas'];
        $metricasPorMes[$mesAno]['tme'] = $total_atendidas > 0 ? format_time($metricasPorMes[$mesAno]['soma_tempo_espera'] / $total_atendidas) : 0;
        $metricasPorMes[$mesAno]['tmc'] = $total_atendidas > 0 ? format_time($metricasPorMes[$mesAno]['soma_tempo_conversa'] / $total_atendidas) : 0;

        // SLA
        $metricasPorMes[$mesAno]['sla'] = $metricasPorMes[$mesAno]['percentual_atendidas'];
    }

    $_SESSION['dadosPorMes'] = $metricasPorMes;
}

// Responder a requisições AJAX
if (isset($_GET['mesAno'])) {
    $mesAno = $_GET['mesAno'];
    if (isset($dadosPorMes[$mesAno])) {
        echo json_encode($dadosPorMes[$mesAno]);
    } else {
        echo json_encode(['error' => 'Nenhum registro encontrado para o mês solicitado.']);
    }
    exit;
}
?>