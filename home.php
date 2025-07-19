<?php
session_start();
require 'utils/db_connect.php';
require_once 'utils/listqueues.php';

// //Toda vez que home.php for acessado, irá mesclar/combinar todos os dados da data atual.
// include_once 'utils/combined_queue_log.php';
// include_once 'utils/agent_events.php';


// Consulta para obter os nomes personalizados das filas
$sql = "
    SELECT q.queue_name AS original_queue_name, 
           IFNULL(qn.queue_name, q.queue_name) AS display_queue_name 
    FROM queues q
    LEFT JOIN qnames qn ON q.queue_name = qn.queue_number
";
$result = $conn->query($sql);


// Array para armazenar os nomes das filas
$filas = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $filas[] = array(
            "original" => $row["original_queue_name"],
            "display" => $row["display_queue_name"]
        );
    }
}

// Consulta para obter os nomes únicos dos agentes
$sql = "SELECT DISTINCT agent_name FROM agents";
$result = $conn->query($sql);


// Array para armazenar os nomes dos agentes
$agentes = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $agentes[] = $row["agent_name"];
    }
}

// 🔹 Configurar o charset da conexão para UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Função para buscar os eventos
function fetch_events($conn, $startDate, $endDate, $agents = [], $queues = [], $startTime, $endTime)
{
    $sql = "
    SELECT 
        callid, 
        queuename, 
        serverid, 
        agent, 
        event, 
        callerid, 
        diallerid, 
        combined_time, 
        wait_time, 
        call_time,
        recordingfile,
        ringtime
    FROM combined_queue_log 
    WHERE combined_time BETWEEN CONCAT(?, ' ', ?) AND CONCAT(?, ' ', ?)"; // 🔹 Sempre aplicando o filtro de tempo

    $params = [$startDate, $startTime, $endDate, $endTime];
    $types = 'ssss';

    // 🔹 Verificar se "Ligações Efetuadas" foi selecionada
    $temLigacoesEfetuadas = in_array('Ligações Efetuadas', $queues);
    $outrasFilas = array_diff($queues, ['Ligações Efetuadas']);

    // 🔹 Tratamento adequado para filas
    if (!empty($outrasFilas)) {
        $placeholdersQueues = implode(',', array_fill(0, count($outrasFilas), '?'));
        $sql .= " AND queuename IN ($placeholdersQueues)";
        $params = array_merge($params, $outrasFilas);
        $types .= str_repeat('s', count($outrasFilas));
    }

    if ($temLigacoesEfetuadas) {
        // 🔹 Se houver outras filas selecionadas, a condição OR precisa manter o filtro de tempo
        if (!empty($outrasFilas)) {
            $sql .= " OR (queuename = 'Ligações Efetuadas' AND combined_time BETWEEN CONCAT(?, ' ', ?) AND CONCAT(?, ' ', ?))";
            $params = array_merge($params, [$startDate, $startTime, $endDate, $endTime]);
            $types .= 'ssss';
        } else {
            // 🔹 Se apenas "Ligações Efetuadas" for selecionada, o filtro de tempo deve ser aplicado corretamente
            $sql .= " AND queuename = 'Ligações Efetuadas'";
        }
    }

    // 🔹 Filtro de EVENTOS
    $agentEvents = ['COMPLETECALLER', 'COMPLETEAGENT', 'CANCEL', 'PAUSEALL', 'UNPAUSEALL'];
    $nonAgentEvents = ['ABANDON', 'EXITWITHTIMEOUT', 'EXITWITHKEY', 'CHANUNAVAIL'];

    $sql .= " AND (event IN ('" . implode("','", $nonAgentEvents) . "')";

    if (!empty($agents)) {
        $placeholdersAgents = implode(',', array_fill(0, count($agents), '?'));
        $sql .= " OR (event IN ('" . implode("','", $agentEvents) . "') AND agent IN ($placeholdersAgents))";
        $params = array_merge($params, $agents);
        $types .= str_repeat('s', count($agents));
    }

    $sql .= ")";

    // 🔹 Preparação da consulta SQL
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . $conn->error);
    }

    // 🔹 Verificar inconsistência entre placeholders e parâmetros
    if (count($params) !== strlen($types)) {
        die("⚠️ ERRO: Número de parâmetros (" . count($params) . ") não corresponde ao número de placeholders (" . strlen($types) . ").");
    }

    // 🔹 Executa a consulta
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // 🔹 Armazenando os resultados
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}


// Processamento do formulário ao submeter
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $agents = !empty($_POST['List_Agent']) ? $_POST['List_Agent'] : header('Location: home.php');
    $queues = !empty($_POST['List_Queue']) ? $_POST['List_Queue'] : header('Location: home.php');
    $startDate = !empty($_POST['startDate']) ? $_POST['startDate'] : header('Location: home.php');
    $endDate = !empty($_POST['endDate']) ? $_POST['endDate'] : header('Location: home.php');
    $startTime = !empty($_POST['startTime']) ? $_POST['startTime'] : header('Location: home.php');
    $endTime = !empty($_POST['endTime']) ? $_POST['endTime'] : header('Location: home.php');


    if (!empty($agents) && !empty($queues)) {
        $data = fetch_events($conn, $startDate, $endDate, $agents, $queues, $startTime, $endTime);

        if (isset($_SESSION['fetch_data']) || isset($_SESSION['dadosPorFila'])) {
            unset($_SESSION['fetch_data']);
            unset($_SESSION['dadosPorFila']);
        }

        $_SESSION['fetch_data'] = $data;
        $_SESSION['filas'] = $queues;
        $_SESSION['startDate'] = $startDate;
        $_SESSION['endDate'] = $endDate;
        $_SESSION['startTime'] = $startTime;
        $_SESSION['endTime'] = $endTime;

        include_once 'api/process_queuedata.php';
        include_once 'api/process_monthdata.php'; 
        include_once 'api/process_hourdata.php';
        include_once 'api/process_daydata.php';
        include_once 'api/process_weekdata.php';
        include_once 'api/process_outcallsdata.php';
        include_once 'api/process_perdayofweek.php';
        !empty($_SESSION['filas']) ? $queues = $_SESSION['filas'] : '';
        $friendlyNames = [];

        // Associa o número da fila ao nome amigável, se disponível
        foreach ($queues as $queueNumber) {
            $friendlyNames[$queueNumber] = isset($qnames[$queueNumber]) ? $qnames[$queueNumber] : $queueNumber;
        }
        $_SESSION['friendlyNames'] = $friendlyNames;
        header('Location: index.php');

    } else {
        echo "Por favor, informe um período.";
    }

}
?>


<?php include_once('header.php'); ?>

<body>
    <form method="post" action="home.php" class="form-horizontal" onsubmit="selectAllOptions()">
        <div class="row">
            <!-- Seção de Fila e Data -->
            <div class="column">
                <div class="selection-group">
                    <h2>Selecione a Fila</h2>
                    <div class="dual-select-container">
                        <select id="queueAvailable" multiple="multiple" size="9" class="form-control">
                            <?php foreach ($filas as $fila): ?>
                                <option value="<?= htmlspecialchars($fila['original']); ?>">
                                    <?= htmlspecialchars($fila['display']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="transfer-buttons">
                            <button type="button" onclick="moveSelected('queueAvailable', 'queueSelected')">></button>
                            <button type="button" onclick="moveSelected('queueSelected', 'queueAvailable')"><</button>
                        </div>

                        <select name="List_Queue[]" id="queueSelected" multiple="multiple" size="9"
                            class="form-control"></select>
                    </div>
                </div>

                <div class="selection-group">
                    <h2>Selecione a Data</h2>
                    <label for="startDate">Data Inicial:</label>
                    <input type="date" name="startDate" id="startDate" class="form-control"
                        value="<?php echo date('Y-m-d'); ?>">

                    <label for="endDate">Data Final:</label>
                    <input type="date" name="endDate" id="endDate" class="form-control"
                        value="<?php echo date('Y-m-d'); ?>">

                    <div class="last-month">
                        <input type="button" id="thisMonth" class="btn btn-primary" value="Este mês">
                        <input type="button" id="thisWeek" class="btn btn-primary" value="Esta semana">
                        <input type="button" id="today" class="btn btn-primary" value="Hoje">
                        <input type="button" id="yesterday" class="btn btn-primary" value="Ontem">
                        <input type="button" id="lastThreeMonths" class="btn btn-primary" value="Últimos 3 meses">
                    </div>
                </div>
            </div>

            <!-- Seção de Agentes e Horário -->
            <div class="column">
                <div class="selection-group">
                    <h2>Selecione os Agentes</h2>
                    <div class="dual-select-container">
                        <select id="agentAvailable" multiple="multiple" size="9" class="form-control">
                            <?php foreach ($agentes as $agente): ?>
                                <option value="<?= htmlspecialchars($agente); ?>">
                                    <?= htmlspecialchars($agente); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="transfer-buttons">
                            <button type="button" onclick="moveSelected('agentAvailable', 'agentSelected')">></button>
                            <button type="button" onclick="moveSelected('agentSelected', 'agentAvailable')"><</button>
                        </div>

                        <select name="List_Agent[]" id="agentSelected" multiple="multiple" size="9"
                            class="form-control"></select>
                    </div>
                </div>

                <div class="selection-group">
                    <h2>Selecione o Horário</h2>
                    <label for="startTime">Horário Inicial:</label>
                    <input type="time" name="startTime" id="startTime" class="form-control" value="00:00">

                    <label for="endTime">Horário Final:</label>
                    <input type="time" name="endTime" id="endTime" class="form-control" value="23:59">
                </div>
            </div>
        </div>

        <div class="button-container">
            <input type="submit" id="showReport" class="btn btn-primary" value="Mostrar Relatório">
        </div>
    </form>

</body>

<script>
    // Move os itens selecionados entre os selects
    function moveSelected(sourceId, targetId) {
        const sourceSelect = document.getElementById(sourceId);
        const targetSelect = document.getElementById(targetId);

        Array.from(sourceSelect.selectedOptions).forEach(option => {
            targetSelect.appendChild(option);
        });
    }

    // Antes de enviar o formulário, garante que todos os itens movidos serão enviados
    function selectAllOptions() {
        document.querySelectorAll('#queueSelected option, #agentSelected option').forEach(option => {
            option.selected = true;
        });
    }

    // Adiciona a funcionalidade de duplo clique para mover os itens
    function enableDoubleClickMove(sourceId, targetId) {
        const sourceSelect = document.getElementById(sourceId);
        sourceSelect.addEventListener('dblclick', function () {
            moveSelected(sourceId, targetId);
        });
    }

    // Configuração de datas rápidas
    document.addEventListener('DOMContentLoaded', function () {
        enableDoubleClickMove('queueAvailable', 'queueSelected');  // Move fila disponível → selecionada
        enableDoubleClickMove('queueSelected', 'queueAvailable');  // Move fila selecionada → disponível
        enableDoubleClickMove('agentAvailable', 'agentSelected');  // Move agente disponível → selecionado
        enableDoubleClickMove('agentSelected', 'agentAvailable');  // Move agente selecionado → disponível

        function formatDate(date) {
            var d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;

            return [year, month, day].join('-');
        }

        function setDateRange(startDate, endDate) {
            document.getElementById('startDate').value = formatDate(startDate);
            document.getElementById('endDate').value = formatDate(endDate);
            document.querySelector('form').submit();
        }

        document.getElementById('thisWeek').addEventListener('click', function () {
            var now = new Date();
            var firstDayOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
            var lastDayOfWeek = new Date(now.setDate(firstDayOfWeek.getDate() + 6));
            setDateRange(firstDayOfWeek, lastDayOfWeek);
        });

        document.getElementById('today').addEventListener('click', function () {
            var today = new Date();
            setDateRange(today, today);
        });

        document.getElementById('yesterday').addEventListener('click', function () {
            var yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            setDateRange(yesterday, yesterday);
        });

        document.getElementById('lastThreeMonths').addEventListener('click', function () {
            var now = new Date();
            var firstDayOfThreeMonthsAgo = new Date(now.setMonth(now.getMonth() - 3));
            var lastDayOfMonth = new Date();
            setDateRange(firstDayOfThreeMonthsAgo, lastDayOfMonth);
        });

        document.getElementById('thisMonth').addEventListener('click', function () {
            var now = new Date();
            var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            var lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            setDateRange(firstDay, lastDay);
        });

    });

</script>

<?php include_once('footer.php'); ?>

</html>