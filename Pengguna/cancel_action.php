<?php
session_start();
require '../functions.php';

if(isset($_GET['action']) && $_GET['action'] == 'cancel'){
    
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Pastikan request milik user yang login
    $query = "UPDATE car_requests 
              SET status='canceled' 
              WHERE id='$id' AND users_id='$user_id'";

    mysqli_query($conn, $query);

    header("Location: daftar_pengajuan.php");
    exit;
}