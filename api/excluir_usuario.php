<?php
// Inicia a sessão, se necessário
session_start();

// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/../utils/db_connect.php';

// Define o cabeçalho para JSON
header('Content-Type: application/json');

try {
    // Lê o conteúdo do corpo da solicitação
    $data = json_decode(file_get_contents('php://input'), true);

    // Verifica se o ID foi enviado
    if (!isset($data['id']) || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID do usuário não fornecido.']);
        exit;
    }

    // Filtra e sanitiza o ID
    $id = intval($data['id']);

    // Prepara a consulta SQL para excluir o usuário
    $query = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Erro ao preparar a consulta: ' . $conn->error);
    }

    // Vincula o parâmetro e executa a consulta
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nenhum usuário encontrado com este ID.']);
        }
    } else {
        throw new Exception('Erro ao executar a consulta: ' . $stmt->error);
    }

    // Fecha a declaração
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Fecha a conexão com o banco de dados
$conn->close();
