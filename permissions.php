<?php
session_start();
$isAdmin = ($_SESSION['permission'] === 'admin');
?>