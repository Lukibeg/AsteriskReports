<?php
header('Content-Type: application/json');
session_start();

include_once __DIR__ . '/../utils/db_connect.php';

$response = ['success' => false, 'message' => 'Erro desconhecido'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido. Use POST.');
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    error_log("Dados recebidos: " . json_encode($data));

    $userId = $data['user_id'] ?? null;
    $username = $data['username'] ?? null;
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;
    $permission = $data['permission'] ?? null;
    $active = $data['active'] ?? null;

    if (is_null($userId) || is_null($username) || is_null($email) || is_null($permission) || is_null($active)) {
        throw new Exception('Campos obrigatórios ausentes.');
    }

    // Atualiza os dados do usuário
    $query = "UPDATE usuarios SET username = ?, email = ?, permission = ?, active = ?";
    $params = [$username, $email, $permission, $active];

    if (!empty($password)) {
        $query .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_BCRYPT);
    }

    $query .= " WHERE id = ?";
    $params[] = $userId;

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta: ' . $conn->error);
    }

    if ($stmt->bind_param(str_repeat('s', count($params)), ...$params) === false) {
        throw new Exception('Erro ao vincular parâmetros: ' . $stmt->error);
    }

    if ($stmt->execute()) {
        // Se o usuário foi atualizado, atualize também valid_session
        $stmt = $conn->prepare("UPDATE user_sessions SET valid_session = 0 WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception('Erro ao preparar atualização da sessão: ' . $conn->error);
        }

        if ($stmt->bind_param("i", $userId) === false) {
            throw new Exception('Erro ao vincular parâmetros para atualizar sessão: ' . $stmt->error);
        }

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Usuário e sessão atualizados com sucesso.';
        } else {
            throw new Exception('Erro ao atualizar sessão: ' . $stmt->error);
        }
    } else {
        throw new Exception('Erro ao executar consulta: ' . $stmt->error);
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
