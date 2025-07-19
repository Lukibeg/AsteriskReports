<?php
// Caminho para o arquivo JSON com os nomes personalizados
define('AGENT_NAMES_FILE', __DIR__ . '/logs/agent_names.json');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$ramal = $data['ramal'] ?? null;
$name = $data['name'] ?? null;

if ($ramal && $name) {
    // Carregar nomes existentes
    $agentNames = [];
    if (file_exists(AGENT_NAMES_FILE)) {
        $agentNames = json_decode(file_get_contents(AGENT_NAMES_FILE), true);
    }

    // Atualizar ou adicionar o nome do ramal
    $agentNames[$ramal] = $name;

    // Salvar de volta no arquivo JSON
    if (file_put_contents(AGENT_NAMES_FILE, json_encode($agentNames, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar o arquivo JSON']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
}
?>
