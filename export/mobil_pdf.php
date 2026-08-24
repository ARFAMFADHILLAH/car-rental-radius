<?php
require '../functions.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();

$html = '
<h3>Laporan Data Mobil</h3>
<table border="1" width="100%" cellspacing="0" cellpadding="6">
<tr>
    <th>No</th>
    <th>Nama Mobil</th>
    <th>Plat</th>
    <th>Status</th>
    <th>Keterangan</th>
</tr>';

$no = 1;
$q = mysqli_query($conn, "SELECT * FROM cars ORDER BY id DESC");
while ($m = mysqli_fetch_assoc($q)) {
    $status = $m['active'] ? 'Tersedia' : 'Tidak Tersedia';
    $html .= "
    <tr>
        <td>{$no}</td>
        <td>{$m['car_name']}</td>
        <td>{$m['plate_number']}</td>
        <td>{$status}</td>
        <td>{$m['Keterangan']}</td>
    </tr>";
    $no++;
}

$html .= '</table>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("laporan_mobil.pdf", ["Attachment" => false]);
