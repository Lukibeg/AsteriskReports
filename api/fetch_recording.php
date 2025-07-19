<?php
require_once __DIR__ . '/../utils/db_connect.php';
header('Content-Type: application/json');

// Verificar se o parâmetro 'callid' foi enviado
if (isset($_POST['callid']) && !empty($_POST['callid'])) {
    $callid = $_POST['callid'];

    // Log do valor recebido
    error_log("CallID recebido: $callid");

    // Consultar o diretório da gravação na tabela combined_queue_log
    $stmt = $conn->prepare("SELECT recordingfile FROM combined_queue_log WHERE callid = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $callid);
        $stmt->execute();
        $stmt->bind_result($recordingFile);
        $stmt->fetch();
        $stmt->close();

        // Verificar se a gravação foi encontrada
        if (!empty($recordingFile)) {
            error_log("Gravação encontrada: $recordingFile");
            echo json_encode(['status' => 'success', 'path' => $recordingFile]);
        } else {
            error_log("Nenhuma gravação encontrada para CallID: $callid");
            echo json_encode(['status' => 'error', 'message' => 'Gravação não encontrada.']);
        }
    } else {
        error_log("Erro ao preparar a consulta SQL: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Erro interno no servidor.']);
    }
} else {
    error_log("Parâmetro 'callid' ausente ou inválido.");
    echo json_encode(['status' => 'error', 'message' => 'Parâmetro inválido ou ausente.']);
}

$conn->close();
