<?php
// Pastikan koneksi ke database benar jalurnya
require '../functions.php';

// Menghilangkan pesan error yang tidak sengaja muncul agar tidak merusak format JSON
error_reporting(0);

$start = isset($_GET['start']) ? $_GET['start'] : '';
$end = isset($_GET['end']) ? $_GET['end'] : '';

$cars = [];

if (!empty($start) && !empty($end)) {
    // Bersihkan input untuk keamanan
    $start = mysqli_real_escape_string($conn, $start);
    $end = mysqli_real_escape_string($conn, $end);

    /**
     * LOGIKA FILTER:
     * Cari mobil yang ID-nya TIDAK ADA dalam daftar pinjaman (car_requests)
     * yang statusnya 'approved' DAN waktunya bertabrakan dengan input user.
     */
    $query = "
        SELECT id, car_name, plate_number 
        FROM cars 
        WHERE active = 1 
        AND id NOT IN (
            SELECT car_id 
            FROM car_requests 
            WHERE status = 'approved' 
            AND (
                ('$start' < end_datetime AND '$end' > start_datetime)
            )
        )
        ORDER BY car_name ASC
    ";

    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cars[] = $row;
        }
    }
}

// Beritahu browser bahwa ini adalah data JSON, bukan teks biasa
header('Content-Type: application/json');
echo json_encode($cars);
exit;