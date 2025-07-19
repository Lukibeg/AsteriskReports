<?php
require 'db_connect.php';
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

$startDate = $_SESSION['startDate'];
$endDate = $_SESSION['endDate'];
$startTime = $_SESSION['startTime'];
$endTime = $_SESSION['endTime'];

if (!$startDate || !$endDate || !$startTime || !$endTime) {
    exit();
}

$query = "SELECT * FROM ringnoanswer_log WHERE combined_time BETWEEN '$startDate $startTime' AND '$endDate $endTime' ORDER BY combined_time DESC";
$result = mysqli_query($conn, $query);
$noAnswerData = [];

if (!$result) {
    die("Erro na consulta: " . mysqli_error($conn));
}

if ($result->num_rows > 0) {

    while ($row = mysqli_fetch_assoc($result)) {
        $noAnswerData[] = $row;
    }
}

return $noAnswerData;
