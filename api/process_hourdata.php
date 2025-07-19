<?php

// Inicia a sessão, se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se os dados estão disponíveis na sessão
if (empty($_SESSION['fetch_data']) || !is_array($_SESSION['fetch_data'])) {
    if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
        echo json_encode(['error' => 'Nenhum dado válido encontrado.']);
    }
    return; // Retorna sem fazer nada
}

// Recupera os dados armazenados na sessão
$data = $_SESSION['fetch_data'];
$dadosPorHora = [];

// Inicializa as métricas por hora
$horas = range(0, 23);
foreach ($horas as $hora) {
    $horaInicio = str_pad($hora, 2, '0', STR_PAD_LEFT) . ":00";
    $horaFim = str_pad($hora, 2, '0', STR_PAD_LEFT) . ":59";
    $dadosPorHora["$horaInicio - $horaFim"] = [
        'total_ligacoes_recebidas' => 0,
        'total_ligacoes_atendidas' => 0,
        'total_ligacoes_nao_atendidas' => 0,
        'total_ligacoes_abandonadas' => 0,
        'total_ligacoes_transferidas' => 0,
        'soma_duracao_atendimento' => 0,
        'soma_duracao_espera' => 0,
        'total_tme' => 0,
        'total_tma' => 0,
        'registros' => []
    ];
}

// Processa os dados de ligações
foreach ($data as $row) {
    $timestamp = isset($row['combined_time']) ? strtotime($row['combined_time']) : null;
    if ($timestamp === null) {
        continue; // Ignora registros inválidos
    }

    $hora = date('H', $timestamp);
    $horaInicio = str_pad($hora, 2, '0', STR_PAD_LEFT) . ":00";
    $horaFim = str_pad($hora, 2, '0', STR_PAD_LEFT) . ":59";
    $intervaloHora = "$horaInicio - $horaFim";

    // Atualiza as métricas para o intervalo de hora
    $dadosPorHora[$intervaloHora]['total_ligacoes_recebidas']++;

    if (in_array($row['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
        $dadosPorHora[$intervaloHora]['total_ligacoes_atendidas']++;
        $dadosPorHora[$intervaloHora]['soma_duracao_atendimento'] += $row['call_time'] ?? 0;
    } elseif ($row['event'] == 'ABANDON' || $row['event'] == 'CANCEL' || $row['event'] == 'CHANUNAVAIL') {
        $dadosPorHora[$intervaloHora]['total_ligacoes_abandonadas']++;
    } elseif ($row['event'] == 'TRANSFER') {
        $dadosPorHora[$intervaloHora]['total_ligacoes_transferidas']++;
    }

    $dadosPorHora[$intervaloHora]['soma_duracao_espera'] += $row['wait_time'] ?? 0;
    $dadosPorHora[$intervaloHora]['registros'][] = $row;
}

// Calcula as métricas finais (TME, TMA, SLA)
foreach ($dadosPorHora as $hora => &$metrics) {
    $totalLigacoesAtendidas = $metrics['total_ligacoes_atendidas'];
    $totalLigacoesRecebidas = $metrics['total_ligacoes_recebidas'];

    // TME: Tempo Médio de Espera (wait_time)
    $metrics['total_tme'] = $totalLigacoesRecebidas > 0 ? $metrics['soma_duracao_espera'] / $totalLigacoesRecebidas : 0;

    // TMA: Tempo Médio de Atendimento (call_time)
    $metrics['total_tma'] = $totalLigacoesAtendidas > 0 ? $metrics['soma_duracao_atendimento'] / $totalLigacoesAtendidas : 0;

    // Calculando percentuais
    $metrics['percentual_atendidas'] = $totalLigacoesRecebidas > 0 ? ($totalLigacoesAtendidas / $totalLigacoesRecebidas) * 100 : 0;
    $metrics['percentual_nao_atendidas'] = $totalLigacoesRecebidas ? 100 - $metrics['percentual_atendidas'] : 0;
    $metrics['sla'] = $metrics['percentual_atendidas'];
}

// Salva os dados no contexto para serem usados em outro local
$_SESSION['dadosPorHora'] = $dadosPorHora;

// Verifica se um intervalo de hora foi solicitado
if (isset($_GET['intervaloHora'])) {
    $intervaloHora = $_GET['intervaloHora']; // Captura o intervalo de hora enviado

    // Verifica se o intervalo existe nos dados processados
    if (isset($dadosPorHora[$intervaloHora])) {
        header('Content-Type: application/json');
        echo json_encode(['registros' => $dadosPorHora[$intervaloHora]['registros']]);
        exit;
    } else {
        // Retorna um erro caso o intervalo não seja encontrado
        header('Content-Type: application/json');
        echo json_encode(['error' => "Nenhum registro encontrado para o intervalo de hora $intervaloHora."]);
        exit;
    }
}


// Retorna o JSON apenas se o script for acessado diretamente
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    echo json_encode($dadosPorHora);
    exit; // Encerra a execução
}


