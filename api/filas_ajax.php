<?php
// Arquivo: filas_ajax.php

header('Content-Type: application/json'); // Define o cabeçalho como JSON

require_once __DIR__ . '/../utils/db_connect.php'; // Inclui o arquivo de conexão com o banco de dados

// Verifique se a conexão foi bem-sucedida
if ($conn->connect_error) {
    echo json_encode(array('error' => 'Falha na conexão: ' . $conn->connect_error));
    exit();
}

$conn->set_charset("utf8");

// Consulta para buscar as filas na tabela `queues`
$sql = "SELECT id, queue_name, created_at FROM queues"; // Ajuste conforme necessário
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(array('error' => 'Erro na consulta: ' . $conn->error));
    exit();
}

$queues = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $queues[] = $row; // Adiciona cada fila ao array
    }
}

// Retorna o array como JSON
echo json_encode($queues, JSON_UNESCAPED_UNICODE);

$conn->close(); // Fecha a conexão com o banco de dados
?>
