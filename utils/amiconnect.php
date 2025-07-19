<?php

include_once 'utils/db_connect.php';

$isIniciated = false;
$inProgress = false;
$connectDb = false;

function agentExists($agentName, $conn)
{
    $agentName = $conn->real_escape_string($agentName);
    $sql = "SELECT COUNT(*) AS count FROM agents WHERE agent_name = '$agentName'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

function insertAgent($agentName, $conn)
{
    if (!agentExists($agentName, $conn)) {
        $agentName = $conn->real_escape_string($agentName);
        $sql = "INSERT INTO agents (agent_name) VALUES ('$agentName')";
        if ($conn->query($sql) === false) {
            echo "Erro ao inserir agente: " . $conn->error;
        }
    }
}

function queueExists($queueName, $conn)
{
    $queueName = $conn->real_escape_string($queueName);
    $sql = "SELECT COUNT(*) AS count FROM queues WHERE queue_name = '$queueName'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

function insertQueue($queueName, $conn)
{
    if (!queueExists($queueName, $conn)) {
        $queueName = $conn->real_escape_string($queueName);
        $sql = "INSERT INTO queues (queue_name) VALUES ('$queueName')";
        if ($conn->query($sql) === false) {
            echo "Erro ao inserir fila: " . $conn->error;
        }
    }
}

echo "Entrando no loop... \n";

// Loop infinito para executar o script continuamente
while (true) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    mysqli_set_charset($conn, "utf8mb4");

    if ($conn->connect_error) {
        die("Erro ao conectar ao banco de dados: " . $conn->connect_error);
    }

    if (!$connectDb && $conn) {
        echo "Conectado com sucesso ao banco de dados \n";
        $connectDb = true;
        sleep(5);
    }

    $filas = array();
    $agentes = array();

    $logFile = '/var/log/asterisk/queue_log';
    if (file_exists($logFile) && is_readable($logFile)) {
        $fileHandle = fopen($logFile, 'r');
        if (!$inProgress) {
            echo "Preparando para iniciar a leitura... \n";
            $inProgress = true;
            sleep(3);
        }

        if ($fileHandle) {
            if (!$isIniciated) {
                echo "Leitura iniciada com sucesso! \n";
                $isIniciated = true;
            }
            while (($line = fgets($fileHandle)) !== false) {
                $fields = explode('|', $line);

                if (count($fields) >= 5) {
                    $event = $fields[4];

                    if (in_array($event, ['CONNECT', 'COMPLETEAGENT', 'COMPLETECALLER'])) {
                        $agentName = $fields[3];

                        if (!in_array($agentName, $agentes)) {
                            $agentes[] = $agentName;
                            insertAgent($agentName, $conn);
                        }
                    }
                }
                if (count($fields) >= 5 && $fields[4] === 'ENTERQUEUE' && !empty($fields[2]) && $fields[2] !== 'Liga��es Efetuadas' && $fields[2] !== "LigaÃ§Ãµes Efetuadas") {
                    $queueName = $fields[2];
                    echo "Fila que será registrada no banco: $queueName";

                    if (!in_array($queueName, $filas)) {
                        $filas[] = $queueName;
                        insertQueue($queueName, $conn);
                    }
                }
            }
            fclose($fileHandle);
        } else {
            echo "Erro ao abrir o arquivo de log.";
        }
    } else {
        echo "Arquivo de log não encontrado ou não legível.";
    }
    $conn->close();
}
