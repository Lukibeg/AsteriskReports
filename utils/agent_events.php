<?php
require 'db_connect.php';

// Dados de in�cio e fim
$startDate = date('Y-m-d') . ' 00:00:00';
$endDate = date('Y-m-d') . ' 23:59:59';

// Chama a procedure no banco de dados
$sql = "CALL CombineQueueLogRingNoAnswer(?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$stmt->close();
?>
