<?php

require_once 'utils/db_connect.php';

function destroySessionAndRedirect($message = null)
{
    session_destroy();
    $url = 'login.php';
    if ($message) {
        $url .= '?message=' . urlencode($message);
    }
    header('Location: ' . $url);
    exit();
}

// Ignorar validação na página de login
if (basename($_SERVER['PHP_SELF']) === 'login.php') {
    return;
}

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    destroySessionAndRedirect();
}
// Validação da sessão
if (!isset($_SESSION['usuario']) || !isset($_SESSION['session_id'])) {
    destroySessionAndRedirect('sessão inválida ou expirada.');
}

$sessionId = $_SESSION['session_id'];
$stmt = $conn->prepare("SELECT valid_session FROM user_sessions WHERE session_id = ?");
$stmt->bind_param("s", $sessionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $sessionData = $result->fetch_assoc();
    if ($sessionData['valid_session'] == 0) {
        destroySessionAndRedirect('sessão inválida ou encerrada.');
    }
} else {
    destroySessionAndRedirect('sessão não encontrada.');
}
?>