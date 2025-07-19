<?php
include_once 'db_connect.php';


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

// Caminho para o arquivo de log de eventos
define('EVENTS_FILE', __DIR__ . '/logs/chat_events.json');

// Função para carregar o estado dos agentes ao iniciar
function loadAgentsStatus()
{
    if (file_exists(EVENTS_FILE)) {
        $agents_status = json_decode(file_get_contents(EVENTS_FILE), true);
        return is_array($agents_status) ? $agents_status : [];
    }
    return [];
}

// Função para salvar o estado dos agentes no JSON
function saveAgentsStatus($agents_status)
{
    file_put_contents(EVENTS_FILE, json_encode($agents_status, JSON_PRETTY_PRINT));
}


// Função principal para monitorar a conexão
function listenForRelevantEvents()
{
    while (true) { // Loop contínuo para manter o script ativo
        $socket = connectAMI();


        if ($socket) {
            echo "Conectado ao AMI com sucesso.\n";
            processEvents($socket);
        } else {
            echo "Erro ao conectar ao AMI. Tentando novamente em 5 segundos...\n";
            sleep(5); // Aguardar antes de tentar reconectar
        }
    }
}

// Função para conectar ao AMI
function connectAMI()
{
    $socket = @fsockopen(AMI_HOST, AMI_PORT, $errno, $errstr, 10);
    if (!$socket) {
        return false;
    }

    // Login no AMI
    fputs($socket, "Action: Login\r\n");
    fputs($socket, "Username: " . AMI_USER . "\r\n");
    fputs($socket, "Secret: " . AMI_PASS . "\r\n");
    fputs($socket, "Events: on\r\n\r\n");

    // Verificar se o login foi bem-sucedido
    while (!feof($socket)) {
        $line = fgets($socket, 4096);
        if (strpos($line, "Message: Authentication accepted") !== false) {
            return $socket;
        }
        if (strpos($line, "Message: Authentication failed") !== false) {
            fclose($socket);
            return false;
        }
    }
    return false;
}

// Função para processar eventos e atualizar o JSON
function processEvents($socket)
{
    while (!feof($socket)) {
        $line = fgets($socket, 4096);
        if ($line === false) {
            echo "Conexão perdida. Tentando reconectar...\n";
            fclose($socket);
            break; // Sair do loop para tentar reconectar
        }
        // Processar eventos relevantes
        if (
            strpos($line, "Event: QueueMemberPause") !== false ||
            strpos($line, "Event: DeviceStateChange") !== false ||
            strpos($line, "Event: ExtensionStatus") !== false
        ) {

            $event_data = parseEvent($socket);

            if (strpos($line, "Event: QueueMemberPause") !== false) {
                $interface = $event_data['Interface'] ?? '';
                $normalized_ramal = normalizeRamalName($interface);
                if ($normalized_ramal) {
                    $agents_status[$normalized_ramal] = [
                        'status' => $event_data['Paused'] == '1' ? 'paused' : 'available',
                        'reason' => $event_data['PausedReason'] ?? ''
                    ];
                }
            } elseif (strpos($line, "Event: DeviceStateChange") !== false) {
                $device = $event_data['Device'] ?? '';
                $normalized_ramal = normalizeRamalName($device);
                if ($normalized_ramal) {
                    $agents_status[$normalized_ramal] = [
                        'status' => $event_data['State'] === 'INUSE' ? 'in_call' : 'available',
                        'reason' => ''
                    ];
                }
            } elseif (strpos($line, "Event: ExtensionStatus") !== false) {
                $extension = $event_data['Exten'] ?? '';
                $normalized_ramal = normalizeRamalName("PJSIP/$extension");
                if ($normalized_ramal) {
                    $agents_status[$normalized_ramal] = [
                        'status' => $event_data['StatusText'] === 'Unavailable' ? 'no_register' : 'available',
                        'reason' => $event_data['StatusText'] === 'Unavailable' ? 'Sem Registro' : ''
                    ];
                }
            }

            // Salva o status atualizado dos agentes no arquivo JSON
            saveAgentsStatus($agents_status);
        }
    }

    fclose($socket); // Fechar o socket caso saia do loop
    echo "Conexão ao AMI encerrada.\n";
}

// Função para parsear dados dos eventos
function parseEvent($socket)
{
    $event_data = [];
    while ($line = trim(fgets($socket, 4096))) {
        if (strpos($line, ": ") !== false) {
            list($key, $value) = explode(": ", $line, 2);
            $event_data[$key] = $value;
        }
    }
    return $event_data;
}

// Função para normalizar o nome do ramal
function normalizeRamalName($interface)
{
    if (preg_match('/^(PJSIP|SIP)\/\d+$/', $interface)) {
        return $interface;
    } elseif (preg_match('/^Local\/(\d+)@from-queue\/n$/', $interface, $matches)) {
        return "PJSIP/" . $matches[1];
    }
    return null;
}

// Executar a função para capturar eventos
listenForRelevantEvents();