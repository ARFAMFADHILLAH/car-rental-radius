<?php
require '../functions.php';

// Ambil filter dari URL agar data yang diekspor sama dengan yang di filter user
$division = $_GET['division'] ?? '';
$status = $_GET['status'] ?? '';
$periode = $_GET['periode'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where = "WHERE 1=1";
if ($division) $where .= " AND cr.division='$division'";
if ($status) $where .= " AND cr.status='$status'";

if($start_date && $end_date){
    $where .= " AND cr.start_datetime BETWEEN '$start_date' AND '$end_date'";
} elseif ($periode == '7_hari') {
    $where .= " AND cr.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($periode == '30_hari') {
    $where .= " AND cr.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

// Set Header Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Peminjaman_Mobil.xls");
?>

<h2>Laporan Peminjaman Mobil</h2>

<table border="1">
    <tr>
        <th>Tanggal Pengajuan</th>
        <th>Nama Pengguna</th>
        <th>Divisi</th>
        <th>Mobil Yang Di Pinjam</th>
        <th>Total Peminjaman</th>
        <th>Status</th>
    </tr>
    <?php
    $q = mysqli_query($conn, "
        SELECT cr.created_at, cr.user_name, cr.division, c.car_name, cr.status, COUNT(*) total
        FROM car_requests cr
        JOIN cars c ON cr.car_id = c.id
        $where AND cr.status='approved'
        GROUP BY cr.created_at, cr.user_name, cr.division, c.car_name, cr.status
        ORDER BY total DESC
    ");
    while($r = mysqli_fetch_assoc($q)):
    ?>
    <tr>
        <td><?= $r['created_at'] ?></td>
        <td><?= $r['user_name'] ?></td>
        <td><?= $r['division'] ?></td>
        <td><?= $r['car_name'] ?></td>
        <td><?= $r['total'] ?> Kali</td>
        <td><?= $r['status'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>