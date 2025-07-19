<?php
include_once __DIR__ . '/../utils/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Busca apenas os dados necessários do banco
    $stmt = $conn->prepare("SELECT id, username, email, active, permission FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Usuário não encontrado.']);
    }
}
?>
