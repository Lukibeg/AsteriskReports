<?php
require_once __DIR__ . '/../utils/db_connect.php';

// Configuração de caracteres para garantir que o banco de dados lide com UTF-8
$conn->set_charset("utf8");

// Inicia a sessão, se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se os dados estão disponíveis na sessão
if (!isset($_SESSION['fetch_data'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Nenhum dado encontrado.']);
    exit;
}

$data = $_SESSION['fetch_data'];


// Função para calcular as métricas detalhadas por fila
function calculate_detailed_metrics($data)
{
    $dadosPorFila = [];

    foreach ($data as $entry) {
        $fila = $entry['queuename']; // Nome da fila
        $agent = $entry['agent'];   // Nome do agente (se aplicável)

        // Inicializa a estrutura para a fila, se não existir
        if (!isset($dadosPorFila[$fila])) {
            $dadosPorFila[$fila] = [
                'total_ligacoes_recebidas' => 0,
                'total_ligacoes_atendidas' => 0,
                'total_ligacoes_nao_atendidas' => 0,
                'total_ligacoes_abandonadas' => 0,
                'total_ligacoes_transferidas' => 0,
                'soma_tempo_espera' => 0.0,
                'soma_tempo_conversa' => 0.0,
                'tme' => '00:00:00', // Inicializa no formato correto
                'tmc' => '00:00:00', // Inicializa no formato correto
                'percentual_atendidas' => 0.0,
                'percentual_nao_atendidas' => 0.0,
                'sla' => 0.0,
                'registros' => [] // Detalhes das chamadas
            ];
        }

        // Incrementa os totais com base no evento
        $dadosPorFila[$fila]['total_ligacoes_recebidas']++;

        if (in_array($entry['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
            $dadosPorFila[$fila]['total_ligacoes_atendidas']++;
            $dadosPorFila[$fila]['soma_tempo_conversa'] += (float) ($entry['call_time'] ?? 0);
            $dadosPorFila[$fila]['soma_tempo_espera'] += (float) ($entry['wait_time'] ?? 0);
        } elseif ($entry['event'] === 'ABANDON') {
            $dadosPorFila[$fila]['total_ligacoes_abandonadas']++;
        } elseif ($entry['event'] === 'CHANUNAVAIL' || $entry['event'] === 'CANCEL') {
            $dadosPorFila[$fila]['total_ligacoes_abandonadas']++;
        } elseif ($entry['event'] === 'TRANSFER') {
            $dadosPorFila[$fila]['total_ligacoes_transferidas']++;
        }

        // Adiciona os detalhes da chamada
        $dadosPorFila[$fila]['registros'][] = [
            'callid' => $entry['callid'],
            'agente' => $agent,
            'data' => $entry['combined_time'],
            'fila' => $fila,
            'numero' => $entry['callerid'],
            'evento' => $entry['event'],
            'ringtime' => $entry['ringtime'] ?? 0, // Sem formatação
            'wait_time' => $entry['wait_time'] ?? 0, // Sem formatação
            'talk_time' => $entry['call_time'] ?? 0, // Sem formatação
            'recordingfile' => $entry['recordingfile'] ?? null
        ];
    }

    // Calcula tempos médios e percentuais
    foreach ($dadosPorFila as $fila => &$metrics) {
        $total_atendidas = $metrics['total_ligacoes_atendidas'];
        $total_recebidas = $metrics['total_ligacoes_recebidas'];

        // Calcula TME e TMC e formata no formato 00:00:00
        $tme_seconds = $total_recebidas > 0 ? intval(round($metrics['soma_tempo_espera'] / $total_recebidas)) : 0;
        $tmc_seconds = $total_atendidas > 0 ? intval(round($metrics['soma_tempo_conversa'] / $total_atendidas)) : 0;

        $metrics['tme'] = gmdate('H:i:s', $tme_seconds);
        $metrics['tmc'] = gmdate('H:i:s', $tmc_seconds);

        // Calcula percentuais
        $metrics['percentual_atendidas'] = $total_recebidas > 0 ? round(($total_atendidas / $total_recebidas) * 100, 2) : 0.0;
        $metrics['percentual_nao_atendidas'] = $total_recebidas > 0 ? round(100 - $metrics['percentual_atendidas'], 2) : 0.0;

        // SLA como % atendidas
        $metrics['sla'] = $metrics['percentual_atendidas'];
    }

    return $dadosPorFila;
}

// Processa os dados para a tabela principal
if (empty($_SESSION['dadosPorFila'])) {
    $dadosPorFila = calculate_detailed_metrics($data);
    $_SESSION['dadosPorFila'] = $dadosPorFila; // Salva na sessão para uso posterior
}

// Mapeamento de filas
$filasMapeadas = [
    'LigacoesEfetuadas' => 'Ligações Efetuadas',
    'LigaesEfetuadas' => 'Ligações Efetuadas', // Cobre diferentes casos
];

// Recebe a fila solicitada
if (isset($_GET['fila'])) {
    ob_clean(); // Limpa qualquer saída anterior
    $filaRecebida = $_GET['fila'];
    // Mapeia o nome recebido para o nome real, se existir
    $fila = $filasMapeadas[$filaRecebida] ?? $filaRecebida;

    // Busca os registros da fila solicitada
    if (isset($_SESSION['dadosPorFila'][$fila])) {
        header('Content-Type: application/json');
        echo json_encode($_SESSION['dadosPorFila'][$fila]['registros']);
        unset($_SESSION['dadosPorFila'][$fila]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Nenhum dado encontrado para a fila solicitada.']);
        unset($_SESSION['dadosPorFila'][$fila]);
    }
    exit;
}
