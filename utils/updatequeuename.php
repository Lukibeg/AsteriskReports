<?php
// Arquivo: atualizar_nome_filas.php

header('Content-Type: application/json');

require 'db_connect.php'; // Inclui o arquivo de conexão com o banco de dados

if ($conn->connect_error) {
    echo json_encode(array('error' => 'Falha na conexão: ' . $conn->connect_error));
    exit();
}

// Decodifica os dados recebidos em JSON
$updates = json_decode($_POST['updates'], true);

if ($updates === null) {
    echo json_encode(array('error' => 'Dados recebidos são inválidos ou nulos.'));
    exit();
}

foreach ($updates as $update) {
    $queue_number = $conn->real_escape_string($update['queue_number']);
    $queue_name = $conn->real_escape_string($update['queue_name']);
    $created_at = date('Y-m-d H:i:s'); // Data e hora atual

    // Verifica se a fila já existe no banco de dados
    $sqlCheck = "SELECT id FROM qnames WHERE queue_number = '$queue_number'";
    $result = $conn->query($sqlCheck);

    if ($result && $result->num_rows > 0) {
        // A fila existe, então faz um update
        $sqlUpdate = "UPDATE qnames 
                      SET queue_name = '$queue_name', created_at = '$created_at' 
                      WHERE queue_number = '$queue_number'";

        if (!$conn->query($sqlUpdate)) {
            echo json_encode(array('error' => 'Erro ao atualizar: ' . $conn->error));
            exit();
        }
    } else {
        // A fila não existe, então faz um insert
        $sqlInsert = "INSERT INTO qnames (queue_number, queue_name, created_at) 
                      VALUES ('$queue_number', '$queue_name', '$created_at')";

        if (!$conn->query($sqlInsert)) {
            echo json_encode(array('error' => 'Erro ao inserir: ' . $conn->error));
            exit();
        }
    }
}

echo json_encode(array('status' => 'success'));

$conn->close(); // Fecha a conexão com o banco de dados
?>
