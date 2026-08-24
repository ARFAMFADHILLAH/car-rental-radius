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

$admin_name = $_SESSION['username']; // Leader yang approve

// Ambil info user
$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
$admin_name = $user['username'];
$role = $user['role']; // bisa 'admin', 'pengguna', dsb
$division = $user['division_id']; // bisa cek divisi leader



// Validasi data pengajuan
$cek = mysqli_query($conn, "SELECT * FROM car_requests WHERE id = $id");
if (mysqli_num_rows($cek) == 0) {
    header("Location: approval_list.php");
    exit;
}

// ===============================
// ACTION APPROVE
// ===============================
if ($action === 'approve') {

    // ==============================
    // LEADER HRGA & SCM (DIVISI 6)
    // APPROVE LANGSUNG FINAL
    // ==============================
    if ($division == 6) {

        mysqli_query($conn, "
            UPDATE car_requests
            SET leader_division_approve = 1,
                leader_ga_approve = 1,
                status = 'approved',
                approved_by = '$admin_name',
                approved_at = NOW()
            WHERE id = $id
        ");

    }

    // ==============================
    // LEADER DIVISI BIASA
    // ==============================
    else if (in_array($division, [7,8,12])) {

        mysqli_query($conn, "
            UPDATE car_requests
            SET leader_division_approve = 1,
                status = 'Menunggu Persetujuan Leader GA',
                approved_by = '$admin_name',
                approved_at = NOW()
            WHERE id = $id
        ");

    }

    // ==============================
    // LEADER GA (DIVISI 5)
    // ==============================
    // Di dalam if ($action === 'approve') bagian Leader GA (Divisi 5)
else if ($division == 5) {
    // Update leader_ga_approve dan langsung set status ke 'approved' 
    mysqli_query($conn, "
        UPDATE car_requests
        SET leader_ga_approve = 1,
            status = 'approved',
            approved_by = '$admin_name',
            approved_at = NOW()
        WHERE id = $id
    ");


        // cek apakah sudah disetujui leader divisi
        $cek = mysqli_query($conn, "
            SELECT leader_division_approve 
            FROM car_requests 
            WHERE id = $id
        ");

        $row = mysqli_fetch_assoc($cek);

        if ($row['leader_division_approve'] == 1) {

            mysqli_query($conn, "
                UPDATE car_requests 
                SET status = 'approved'
                WHERE id = $id
            ");

        }
    }

    // ===============================
// AUTO REJECT REQUEST YANG BENTROK
// ===============================

// Ambil data request yang baru saja di-approve
$getApproved = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT car_id, start_datetime, end_datetime 
    FROM car_requests 
    WHERE id = $id
"));

$car_id = $getApproved['car_id'];
$start  = $getApproved['start_datetime'];
$end    = $getApproved['end_datetime'];

// Cari request lain yang bentrok (berdasarkan tanggal peminjaman)
$conflictQuery = mysqli_query($conn, "
    SELECT id FROM car_requests
    WHERE car_id = '$car_id'
    AND id != '$id'
    AND status IN ('Menunggu Persetujuan Leader', 'Menunggu Persetujuan Leader GA')
    AND (
        start_datetime < '$end'
        AND end_datetime > '$start'
    )
");

// Jika ada yang bentrok → auto reject
while ($conflict = mysqli_fetch_assoc($conflictQuery)) {

    $conflict_id = $conflict['id'];

    mysqli_query($conn, "
        UPDATE car_requests
        SET status = 'rejected',
            notes = 'Otomatis ditolak karena jadwal bentrok dengan pengajuan yang sudah disetujui.',
            approved_by = '$admin_name',
            approved_at = NOW()
        WHERE id = '$conflict_id'
    ");
}

    header("Location: approval_list.php?msg=approved");
    exit;
}



// ===============================
// ACTION REJECT
// ===============================
if ($action === 'reject') {

    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    $query = "
        UPDATE car_requests
        SET 
            status = 'rejected',
            notes = '$notes',
            approved_by = '$admin_name',
            approved_at = NOW()
        WHERE id = $id
    ";

    mysqli_query($conn, $query);

    header("Location: approval_list.php?msg=rejected");
    exit;
}


// ===============================
// ACTION CANCEL
// ===============================
if ($action === 'cancel') {

    $query = "
        UPDATE car_requests
        SET 
            status = 'Canceled',
            approved_by = '$admin_name',
            approved_at = NOW()
        WHERE id = $id
    ";

    mysqli_query($conn, $query);

    header("Location: approval_list.php?msg=canceled");
    exit;
}

// Kalau action tidak valid
header("Location: approval_list.php");
exit;
