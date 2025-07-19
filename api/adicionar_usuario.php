<?php
header('Content-Type: application/json');
session_start();

// Inclui a conexão com o banco de dados
include_once __DIR__ . '/../utils/db_connect.php';

// Inicializa a resposta padrão
$response = ['success' => false, 'message' => 'Erro desconhecido.'];

try {
    // Verifica se a requisição é POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Captura os dados enviados
        $username = htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
        $password = htmlspecialchars($_POST['password'], ENT_QUOTES, 'UTF-8');
        $permission = htmlspecialchars($_POST['permission'], ENT_QUOTES, 'UTF-8');        

        // Valida os dados
        if (empty($username) || empty($email) || empty($password) || empty($permission)) {
            throw new Exception('Todos os campos devem ser preenchidos.');
        }

        // Insere o novo usuário no banco de dados
        $query = "INSERT INTO usuarios (username, email, password, permission, active) VALUES (?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($query);

        // Use password_hash para proteger a senha
        $passwordHashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bind_param('ssss', $username, $email, $passwordHashed, $permission);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Usuário adicionado com sucesso.';
        } else {
            throw new Exception('Erro ao adicionar usuário: ' . $conn->error);
        }
    } else {
        throw new Exception('Método inválido. Use POST.');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Retorna a resposta em JSON
echo json_encode($response);
exit;
