<?php 
// Array para armazenar o mapeamento de nomes amigáveis
$qnames = [];

// Busque o mapeamento de nomes amigáveis do banco de dados
$sql = "SELECT queue_number, queue_name FROM qnames";
$result = $conn->query($sql);

// Armazene o mapeamento no array $qnames
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $qnames[$row['queue_number']] = $row['queue_name'];
    }
}

?>