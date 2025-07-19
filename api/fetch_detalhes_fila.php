<?php
require_once __DIR__ . '/../utils/db_connect.php';

// Verifica se o nome da fila foi enviado
if (isset($_POST['fila'])) {
    $filaName = $_POST['fila'];

    // Consulta os detalhes da fila
    $query = "
        SELECT 
            agent, combined_time, queuename, callerid, event, ringtime, wait_time, call_time, recordingfile, callid
        FROM combined_queue_log
        WHERE queuename = ?
        ORDER BY combined_time DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $filaName);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Fila não especificada.']);
}
