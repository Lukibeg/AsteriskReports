<?php
require 'header.php'; // Cabeçalho da página
require 'listqueues.php'; // Busca o nome das filas


// Definir data atual como padrão se nenhuma data for passada
$currentDate = date('Y-m-d');
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : $currentDate;
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : $currentDate;

// Filtros opcionais
$queueFilter = isset($_POST['queue']) ? $_POST['queue'] : '';
$agentFilter = isset($_POST['agent']) ? $_POST['agent'] : '';
$eventFilter = isset($_POST['event']) ? $_POST['event'] : '';
$numberFilter = isset($_POST['callerid']) ? $_POST['callerid'] : '';
$uniqueIdFilter = isset($_POST['callid']) ? $_POST['callid'] : '';

// Montando a consulta SQL com base nos filtros aplicados
$query = "SELECT combined_time, queuename, agent, event, wait_time, call_time, callerid, callid, recordingfile
          FROM combined_queue_log 
          WHERE DATE(combined_time) BETWEEN '$startDate' AND '$endDate'";

// Adicionando os filtros dinamicamente
if (!empty($queueFilter)) {
    $query .= " AND queuename LIKE '%$queueFilter%'";
}
if (!empty($agentFilter)) {
    $query .= " AND agent LIKE '%$agentFilter%'";
}
if (!empty($eventFilter)) {
    $query .= " AND event LIKE '%$eventFilter%'";
}
if (!empty($uniqueIdFilter)) {
    $query .= " AND callid LIKE '%$uniqueIdFilter%'";
}
if (!empty($numberFilter)) {
    $query .= " AND callerid LIKE '%$numberFilter%'";
}

$result = mysqli_query($conn, $query);

// Verificar se a consulta foi executada corretamente
if (!$result) {
    die('Erro na consulta: ' . mysqli_error($conn));
}

// Função para formatar tempo
function format_time($seconds)
{
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}

// Função para formatar data
function format_date($date)
{
    $date = date_create($date);
    return date_format($date, "d/m/Y H:i:s");
}

// Criar array para Friendly Names
$friendlyNames = [];

// Percorrer os resultados da consulta para preencher os Friendly Names
while ($row = mysqli_fetch_assoc($result)) {
    // Verifica se já temos um nome amigável para essa fila, senão, tenta mapear pelo $qnames
    if (!isset($friendlyNames[$row['queuename']])) {
        // Tenta obter o nome amigável do array $qnames
        $friendlyNames[$row['queuename']] = isset($qnames[$row['queuename']]) ? $qnames[$row['queuename']] : $row['queuename'];
    }
}

// Reinicializa a consulta para exibir os resultados
mysqli_data_seek($result, 0);
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Pesquisar Registros</title>
    <link rel="stylesheet" type="text/css" href="css/tables.css"> <!-- Linkando o CSS de tabelas -->
    <link rel="stylesheet" href="css/index.css"> <!-- Linkando o CSS geral do site -->


    <!-- DataTables JS com configuração personalizada (seu arquivo datatables.js) -->
    <script src="js/datatables.js"></script>

    <!-- Seu CSS de tabelas e estilo -->
    <link rel="stylesheet" type="text/css" href="css/form.css">
</head>

<body>
    <div class="container">
        <h3 class="tituloprincipal">Pesquisar Registros</h3>
        <!-- Formulário de filtros -->
        <form method="POST" action="pesquisar.php" class="form-inline d-flex flex-wrap align-items-center gap-3" style="margin-bottom: 50px;">
            <div class="form-group mb-2 d-flex align-items-center">
                <label for="startDate" class="mr-2">Data Inicial:</label>
                <input type="date" class="form-control form-control-sm" name="startDate"
                    value="<?= htmlspecialchars($startDate); ?>">
            </div>

            <div class="form-group mb-2 d-flex align-items-center">
                <label for="endDate" class="mr-2">Data Final:</label>
                <input type="date" class="form-control form-control-sm" name="endDate"
                    value="<?= htmlspecialchars($endDate); ?>">
            </div>

            <div class="form-group mb-2 d-flex align-items-center">
                <label for="queue" class="mr-2">Fila:</label>
                <input type="text" class="form-control form-control-sm" name="queue"
                    value="<?= htmlspecialchars($queueFilter); ?>" placeholder="Ex: 500">
            </div>

            <div class="form-group mb-2 d-flex align-items-center">
                <label for="agent" class="mr-2">Agente:</label>
                <input type="text" class="form-control form-control-sm" name="agent"
                    value="<?= htmlspecialchars($agentFilter); ?>" placeholder="Ex: João">
            </div>

            <div class="form-group mb-2 d-flex align-items-center">
                <label for="event" class="mr-2">Evento:</label>
                <input type="text" class="form-control form-control-sm" name="event"
                    value="<?= htmlspecialchars($eventFilter); ?>" placeholder="Ex: COMPLETECALLER">
            </div>

            <div class="form-group mb-2 d-flex align-items-center">
                <label for="callerid" class="mr-2">Número:</label>
                <input type="text" class="form-control form-control-sm" name="callerid"
                    value="<?= htmlspecialchars($numberFilter); ?>" placeholder="Ex: 5571987654321">
            </div>

            <div class="form-group mb-2 d-flex align-items-center">
                <label for="uniqueid" class="mr-2">Call ID:</label>
                <input type="text" class="form-control form-control-sm" name="uniqueid"
                    value="<?= htmlspecialchars($uniqueIdFilter); ?>" placeholder="Ex: 1601234567.123">
            </div>

            <button type="submit" class="btn btn-sm btn-primary">Pesquisar</button>
        </form>




        <!-- Exibição dos registros -->
        <div class="tabela-container">
        <table class="table detailsTable">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Fila</th>
                    <th>Agente</th>
                    <th>Evento</th>
                    <th>Tempo de Espera</th>
                    <th>Tempo de Conversa</th>
                    <th>Número</th>
                    <th>Call ID</th>
                    <th>Gravação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars(format_date($row['combined_time'])); ?></td>
                            <td><?= htmlspecialchars($friendlyNames[$row['queuename']]); ?></td>
                            <td><?= htmlspecialchars($row['agent']); ?></td>
                            <td
                                style="color: <?= $row['event'] === 'COMPLETECALLER' || $row['event'] === 'COMPLETEAGENT' ? 'green' : 'red' ?>">
                                <?= htmlspecialchars($row['event']); ?>
                            </td>
                            <td><?= htmlspecialchars(format_time($row['wait_time'])); ?></td>
                            <td><?= htmlspecialchars(format_time($row['call_time'])); ?></td>
                            <td><?= htmlspecialchars($row['callerid']); ?></td>
                            <td><?= htmlspecialchars($row['callid']); ?></td>
                            <td>
                                <?php if (!empty($row['recordingfile'])): ?>
                                    <audio controls>
                                        <source src="<?= htmlspecialchars($row['recordingfile']); ?>" type="audio/wav">
                                        Seu navegador não suporta o elemento de áudio.
                                    </audio>
                                <?php else: ?>
                                    Sem gravação
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">Nenhum registro encontrado</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Inclua o DataTables JS -->
    <script src="js/datatables.js"></script> <!-- Seu arquivo já existente -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

</body>

<?php
include 'footer.php'; // Rodapé da página
?>