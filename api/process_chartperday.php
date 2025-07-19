<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Verifica se os dados estão disponíveis na sessão
if (!isset($_SESSION['dadosPorDia'])) {
    echo json_encode(['error' => 'Nenhum dado encontrado.']);
    exit;
}

// Recupera os dados processados por dia
$dadosPorDia = $_SESSION['dadosPorDia'];

// Prepara os dados para o gráfico
$labelsDias = array_keys($dadosPorDia);

// Converte os valores para arrays indexados para compatibilidade com Chart.js
$atendidasDias = [];
$abandonadasDias = [];

foreach ($dadosPorDia as $dia => $dados) {
    $atendidasDias[] = $dados['atendidas'] ?? 0;
    $abandonadasDias[] = $dados['abandonadas'] ?? 0;
}

// Cria um array com os dados formatados para o gráfico
$response = [
    'labels' => $labelsDias,
    'atendidas' => $atendidasDias,
    'abandonadas' => $abandonadasDias
];

// Retorna os dados em formato JSON
echo json_encode($response);
exit;
