<?php
require '../functions.php';
require '../vendor/autoload.php'; // Path ke dompdf

use Dompdf\Dompdf;

$dompdf = new Dompdf();

// --- LOGIKA FILTER (Sama seperti di atas) ---
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

// Ambil Data
$q = mysqli_query($conn, "SELECT cr.created_at, cr.user_name, cr.division, c.car_name, cr.status, COUNT(*) total FROM car_requests cr JOIN cars c ON cr.car_id = c.id $where AND cr.status='approved' GROUP BY cr.created_at, cr.user_name, cr.division, c.car_name, cr.status");

// Generate HTML
$html = '
<style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 12px; }
    h2 { text-align: center; }
</style>
<h2>Laporan Peminjaman Mobil</h2>';

$html .= '<table>
            <thead>
                <tr>
                    <th>Tanggal Pengajuan</th><th>Nama Peminjam</th><th>Divisi</th><th>Mobil Yang Dipinjam</th><th>Total Peminjaman</th><th>Status</th>
                </tr>
            </thead>
            <tbody>';

while($r = mysqli_fetch_assoc($q)) {
    $html .= "<tr>
                <td>{$r['created_at']}</td>
                <td>{$r['user_name']}</td>
                <td>{$r['division']}</td>
                <td>{$r['car_name']}</td>
                <td>{$r['total']} kali</td>
                <td>{$r['status']}</td>
              </tr>";
}
$html .= '</tbody></table>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Laporan_Peminjaman_Mobil.pdf", ["Attachment" => false]);