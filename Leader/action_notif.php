<?php
session_start();
require '../functions.php';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];

    if ($action == 'markAll') {
        mysqli_query($conn, "UPDATE car_requests SET is_read_notif = 1 WHERE is_deleted_notif = 0");
    } elseif ($action == 'deleteAll') {
        mysqli_query($conn, "UPDATE car_requests SET is_deleted_notif = 1");
    } elseif (!empty($ids)) {
        $id_list = implode(",", array_map('intval', $ids));
        if ($action == 'markSelected') {
            mysqli_query($conn, "UPDATE car_requests SET is_read_notif = 1 WHERE id IN ($id_list)");
        } elseif ($action == 'deleteSelected') {
            mysqli_query($conn, "UPDATE car_requests SET is_deleted_notif = 1 WHERE id IN ($id_list)");
        }
    }
    echo "success";
    exit;
}