<?php
session_start();
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : (int)($_GET['id'] ?? 0);
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($id === 0 || $action === '') {
    header("Location: approval_list.php");
    exit;
}

$admin_name = $_SESSION['username'];

// Ambil data pengajuan
$result = mysqli_query($conn, "SELECT * FROM car_requests WHERE id = $id");
$request = mysqli_fetch_assoc($result);

if (!$request) {
    header("Location: approval_list.php");
    exit;
}

$status = trim($request['status']);

$waiting_status = [
    'Menunggu Persetujuan Leader',
    'Menunggu Persetujuan Leader GA'
];

if ($action === 'approve') {

    if (!in_array($status, $waiting_status)) {
        header("Location: approval_list.php?msg=invalid");
        exit;
    }

    // ==============================
    // START TRANSACTION (PENTING)
    // ==============================
    mysqli_begin_transaction($conn);

    try {

        // 1️⃣ APPROVE request yang dipilih
        mysqli_query($conn, "
            UPDATE car_requests
            SET 
                status = 'approved',
                approved_by = '$admin_name',
                approved_at = NOW()
            WHERE id = $id
        ");

        // 2️⃣ AUTO REJECT request lain yang bentrok
        mysqli_query($conn, "
            UPDATE car_requests
            SET 
                status = 'rejected',
                notes = 'Otomatis ditolak karena jadwal bentrok dengan request lain yang sudah disetujui'
            WHERE car_id = {$request['car_id']}
            AND id != {$request['id']}
            AND status IN ('Menunggu Persetujuan Leader','Menunggu Persetujuan Leader GA')
            AND (
                start_datetime < '{$request['end_datetime']}'
                AND end_datetime > '{$request['start_datetime']}'
            )
        ");

        // Commit kalau semua berhasil
        mysqli_commit($conn);

        header("Location: approval_list.php?msg=approved");
        exit;

    } catch (Exception $e) {

        mysqli_rollback($conn);
        header("Location: approval_list.php?msg=error");
        exit;
    }
}

/* =====================================================
   REJECT
===================================================== */
if ($action === 'reject') {

    if (!in_array($status, $waiting_status)) {
        header("Location: approval_list.php?msg=invalid");
        exit;
    }

    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    mysqli_query($conn, "
        UPDATE car_requests
        SET 
            status = 'rejected',
            notes = '$notes',
            approved_by = '$admin_name',
            approved_at = NOW()
        WHERE id = $id
    ");

    header("Location: approval_list.php?msg=rejected");
    exit;
}

/* =====================================================
   CANCEL
===================================================== */
if ($action === 'cancel') {

    if ($status !== 'approved') {
        header("Location: approval_list.php?msg=invalid");
        exit;
    }

    mysqli_query($conn, "
        UPDATE car_requests
        SET status = 'Canceled'
        WHERE id = $id
    ");

    header("Location: approval_list.php?msg=canceled");
    exit;
}

header("Location: approval_list.php");
exit;