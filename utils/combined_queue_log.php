<?php
require 'db_connect.php';
date_default_timezone_set('America/Bahia');

while (true) {
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $startYesterday = $yesterday . ' 00:00:00';
    $endYesterday = $yesterday . ' 23:59:59';

    $today = date('Y-m-d');
    $startToday = $today . ' 00:00:00';
    $endToday = $today . ' 23:59:59';

    // Executa para ontem
    $stmt1 = $conn->prepare("CALL CombineQueueLog(?, ?)");
    $stmt1->bind_param('ss', $startYesterday, $endYesterday);
    $stmt1->execute();
    $stmt1->close();

    // Executa para hoje
    $stmt2 = $conn->prepare("CALL CombineQueueLog(?, ?)");
    $stmt2->bind_param('ss', $startToday, $endToday);
    $stmt2->execute();
    $stmt2->close();

    // Aguarda 1 hora até a próxima execução
    sleep(3600);
}
