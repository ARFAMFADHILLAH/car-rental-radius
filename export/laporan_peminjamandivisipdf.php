<?php
require '../functions.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();

$division = $_GET['division'] ?? '';
$created_at = $_GET['created_at'] ?? '';



$where = "WHERE 1=1";
if ($division) $where .= " AND cr.division='$division'";
if ($created_at) $where .= " AND cr.created_at='$created_at'";


// Ambil Data
$q = mysqli_query($conn, "SELECT cr.created_at, cr.division, COUNT(*) total FROM car_requests cr JOIN cars c ON cr.car_id = c.id $where AND cr.status='approved' GROUP BY cr.created_at, cr.division");

// Generate HTML
$html = '
<style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 12px; }
    h2 { text-align: center; }
</style>
<h2>Laporan Divisi Yang Paling Sering Meminjam Mobil</h2>';

$html .= '<table>
            <thead>
                <tr>
                    <th>Tanggal Pengajuan</th><th>Divisi</th><th>Total Peminjaman</th>
                </tr>
            </thead>
            <tbody>';

while($r = mysqli_fetch_assoc($q)) {
    $html .= "<tr>
                <td>{$r['created_at']}</td>
                <td>{$r['division']}</td>
                <td>{$r['total']} kali</td>
              </tr>";
}
$html .= '</tbody></table>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Laporan_Divisi_Yang_Paling_Sering_Meminjam_Mobil.pdf", ["Attachment" => false]);