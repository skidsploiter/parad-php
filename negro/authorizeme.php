<?php
session_start();
$_SESSION['is_admin'] = true;
header("Location: admin-stock.php");
?>