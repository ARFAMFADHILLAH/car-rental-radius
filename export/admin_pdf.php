<?php
require '../functions.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();

$html = '
<h3>Laporan Data Admin</h3>
<table border="1" width="100%" cellspacing="0" cellpadding="6">
<tr>
    <th>No</th>
    <th>Email</th>
    <th>Role</th>
    <th>Dibuat</th>
</tr>';

$no = 1;
$admin = mysqli_query($conn, "SELECT * FROM users WHERE role='admin' ORDER BY id DESC");
while ($a = mysqli_fetch_assoc($admin)) {
    $html .= "
    <tr>
    <td> $no </td>
    <td> {$a['email']}</td>
    <td> {$a['role']} </td>
    <td> {$a['created_at']} </td>
    </tr>";
    $no++;
}

$html .= '</table>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("laporan_admin.pdf", ["Attachment" => false]);
