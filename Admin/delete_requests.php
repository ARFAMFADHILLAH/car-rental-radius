<?php
session_start();
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// Tidak ada checkbox dicentang
if (!isset($_POST['ids']) || empty($_POST['ids'])) {
    header("Location: approval_list.php");
    exit;
}

// Amankan input
$ids = array_map('intval', $_POST['ids']);
$idList = implode(',', $ids);

// DELETE semua row yang dicentang, termasuk approved
$query = "DELETE FROM car_requests WHERE id IN ($idList)";
mysqli_query($conn, $query);

// Redirect kembali
header("Location: approval_list.php");
exit;
