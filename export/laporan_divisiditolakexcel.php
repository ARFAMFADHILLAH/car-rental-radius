<?php
require '../functions.php';

// Ambil filter dari URL agar data yang diekspor sama dengan yang di filter user
$division = $_GET['division'] ?? '';
$status = $_GET['status'] ?? '';
$created_at = $_GET['created_at'] ?? '';

$where = "WHERE 1=1";
if ($division) $where .= " AND cr.division='$division'";
if ($status) $where .= " AND cr.status='$status'";

// Set Header Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Divisi_Yang_Ditolak.xls");
?>

<h2>Laporan Divisi Yang Ditolak</h2>

<table border="1">
    <tr>
        <th>Tanggal Pengajuan</th>
        <th>Divisi</th>
        <th>Total Ditolak</th>
    </tr>
    <?php
    $q = mysqli_query($conn, "
        SELECT cr.created_at,cr.division, COUNT(*) total
        FROM car_requests cr
        JOIN cars c ON cr.car_id = c.id
        $where AND cr.status='rejected'
        GROUP BY cr.created_at, cr.division
        ORDER BY total DESC
    ");
    while($r = mysqli_fetch_assoc($q)):
    ?>
    <tr>
        <td><?= $r['created_at'] ?></td>
        <td><?= $r['division'] ?></td>
        <td><?= $r['total'] ?> Kali</td>
    </tr>
    <?php endwhile; ?>
</table>