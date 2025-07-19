<?php
require 'db_connect.php';

$query = 'SELECT server, name, user, secret from ami_credentials';
$result = mysqli_query($conn, $query) or die('Query Falhou: ' . mysqli_error($conn));

$row = mysqli_fetch_assoc($result);

$total = mysqli_num_rows($result);

$AMISERVER = $row['server'];
$AMINAME = $row['name'];
$AMIUSER = $row['user'];
$AMISECRET = $row['secret'];

// Configurações de conexão AMI
define('AMI_HOST', $AMISERVER);
define('AMI_PORT', 5038);
define('AMI_USER', $AMIUSER);
define('AMI_PASS', $AMISECRET);

echo "Conectando ao AMI...\n";
$socket = fsockopen($host, $port, $errno, $errstr, 30);
if (!$socket) {
    die("Erro ao conectar ao AMI: $errstr ($errno)\n");
}
echo "Conectado ao AMI com sucesso.\n";

fwrite($socket, "Action: Login\r\n");
fwrite($socket, "Username: $username\r\n");
fwrite($socket, "Secret: $password\r\n\r\n");

fwrite($socket, "Action: Events\r\n");
fwrite($socket, "EventMask: on\r\n\r\n");

echo "Escutando eventos relevantes...\n";

$currentEvent = "";
$pauses = [];

while (!feof($socket)) {
    $line = fgets($socket, 8192);
    $currentEvent .= $line;

    if (trim($line) === "") {
        $eventData = [];
        $lines = array_map('trim', preg_split('/\n/', $currentEvent));

        foreach ($lines as $entry) {
            if (strpos($entry, ':') !== false) {
                list($key, $value) = array_map('trim', explode(':', $entry, 2));
                $eventData[$key] = $value;
            }
        }

        if (isset($eventData['Event']) && $eventData['Event'] === 'QueueMemberPause') {
            $agent = $eventData['MemberName'];
            $queue = $eventData['Queue'];
            $paused = $eventData['Paused'];
            $reason = isset($eventData['PausedReason']) && $eventData['PausedReason'] !== ''
                ? $eventData['PausedReason']
                : 'Não especificado';
            $currentTime = date('Y-m-d H:i:s');

            if ($paused == "1") {
                // O agente entrou em pausa - Armazena o motivo corretamente
                $pauses["$agent|$queue"] = [
                    'start_time' => time(),
                    'reason' => $reason,  // Armazena o motivo corretamente
                    'start_datetime' => $currentTime
                ];
                echo "Pausa iniciada: Agente = $agent, Fila = $queue, Motivo = $reason\n";

            } elseif ($paused == "0" && isset($pauses["$agent|$queue"])) {
                // O agente saiu da pausa - Recupera o motivo corretamente
                $start = $pauses["$agent|$queue"]['start_time'];
                $startDatetime = $pauses["$agent|$queue"]['start_datetime'];
                $reason = $pauses["$agent|$queue"]['reason'];  // Recupera o motivo correto
                $duration = time() - $start;

                // Inserir o registro no banco de dados
                $stmt = $db->prepare("INSERT INTO agent_pause_logs (agent_name, queue, paused_reason, start_time, end_time, duration_seconds) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $agent, $queue, $reason, $startDatetime, $currentTime, $duration);

                if ($stmt->execute()) {
                    echo "Pausa registrada no banco: Agente = $agent, Razão da Pausa = $reason, Fila = $queue, Duração = $duration segundos\n";
                } else {
                    echo "Erro ao inserir pausa: " . $stmt->error . "\n";
                }

                $stmt->close();
                unset($pauses["$agent|$queue"]);  // Remove a pausa registrada
            }
        }

        $currentEvent = "";  // Limpa o evento
    }
}

fclose($socket);
echo "Conexão encerrada.\n";
$db->close();
?>