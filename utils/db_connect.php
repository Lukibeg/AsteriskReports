<?php
$servername = "linepbx.inglinesystems.com.br";
$username = "luki";
$password = "88964302lL@";
$dbname = "asteriskcdrdb";

$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8mb4");


// Verifique a conexão
if ($conn->connect_error) {
    die("Erro ao conectar ao banco de dados: " . $conn->connect_error);
}
?>