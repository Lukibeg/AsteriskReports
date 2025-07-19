<?php
require_once __DIR__ . '/../utils/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['fetch_data'])) {
    die('Nenhum dado encontrado.');
}

$data = $_SESSION['fetch_data'];
$dadosPorSemana = [];

// Função para calcular a semana e o ano
function getWeekYear($date)
{
    return date('o-W', strtotime($date)); // o = ano ISO, W = número da semana
}

foreach ($data as $entry) {
    $weekYear = getWeekYear($entry['combined_time']); // Exemplo: "2024-01" (ano-semana)

    if (!isset($dadosPorSemana[$weekYear])) {
        $dadosPorSemana[$weekYear] = [
            'recebidas' => 0,
            'atendidas' => 0,
            'nao_atendidas' => 0,
            'abandonadas' => 0,
            'transferidas' => 0,
            'wait_time' => 0,
            'call_time' => 0,
            'max_calls' => 0,
            'registros' => [], // Detalhes das chamadas
        ];
    }

    $dadosPorSemana[$weekYear]['recebidas']++;
    $dadosPorSemana[$weekYear]['registros'][] = $entry; // Armazena os detalhes das chamadas

    // Classifica os eventos
    switch ($entry['event']) {
        case 'COMPLETECALLER':
        case 'COMPLETEAGENT':
            $dadosPorSemana[$weekYear]['atendidas']++;
            $dadosPorSemana[$weekYear]['wait_time'] += $entry['wait_time'] ?? 0;
            $dadosPorSemana[$weekYear]['call_time'] += $entry['call_time'] ?? 0;
            break;
        case 'ABANDON':
            $dadosPorSemana[$weekYear]['abandonadas']++;
            break;
        case 'TRANSFER':
            $dadosPorSemana[$weekYear]['transferidas']++;
            break;
        case 'CANCEL':
            $dadosPorSemana[$weekYear]['abandonadas']++;

            break;
        case 'CHANUNAVAIL':
            $dadosPorSemana[$weekYear]['abandonadas']++;
            break;
    }

    $dadosPorSemana[$weekYear]['max_calls'] = max($dadosPorSemana[$weekYear]['max_calls'], $entry['wait_time'] ?? 0);
}
// Calcula métricas adicionais
foreach ($dadosPorSemana as $weekYear => &$metrics) {
    $metrics['tme'] = $metrics['atendidas'] > 0 ? $metrics['wait_time'] / $metrics['atendidas'] : 0;
    $metrics['tma'] = $metrics['atendidas'] > 0 ? $metrics['call_time'] / $metrics['atendidas'] : 0;

    $metrics['percent_atendidas'] = $metrics['recebidas'] > 0 ? ($metrics['atendidas'] / $metrics['recebidas']) * 100 : 0;

    // Calcula % Não Atendidas como complemento de % Atendidas
    $metrics['percent_nao_atendidas'] = 100 - $metrics['percent_atendidas'];

    // SLA é igual ao percentual de chamadas atendidas
    $metrics['sla'] = $metrics['percent_atendidas'];
}
// Apenas salva os dados na sessão sem exibir nada
$_SESSION['dadosPorSemana'] = $dadosPorSemana;

// Certifique-se de que nada é impresso, a menos que uma requisição AJAX específica seja feita
if (isset($_GET['week'])) {
    $weekYear = $_GET['week'];
    if (isset($dadosPorSemana[$weekYear])) {
        echo json_encode($dadosPorSemana[$weekYear]['registros']);
    } else {
        echo json_encode(['error' => 'Nenhum registro encontrado para a semana solicitada.']);
    }
    exit;
}
