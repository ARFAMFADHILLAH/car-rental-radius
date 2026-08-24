<?php
require '../functions.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();

// Ambil file logo dan ubah jadi base64
$logoPath = '../uploads/logo_radius/logo-radius.webp';
$logoData = file_get_contents($logoPath);
$logoBase64 = 'data:image/webp;base64,' . base64_encode($logoData);

$html = '
<style>
    body { font-family: Arial, sans-serif; }
    .header { text-align: center; margin-bottom: 20px; }
    .header img { width: 120px; height: auto; margin-bottom: 10px; }
    .header h1 { margin: 0; font-size: 24px; }
    .header p { margin: 0; font-size: 14px; color: #555; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 6px; text-align: center; font-size: 12px; }
    th { background-color: #f2f2f2; }
</style>

<div class="header">
    <img src="'.$logoBase64.'" alt="Logo Perusahaan">
    <h1>PT Radius Allkindo Electric</h1>
    <p>Partner for Electrical and Safety Equipments</p>
</div>

<h3 style="text-align:center;">Laporan Peminjaman Mobil</h3>

<table>
<tr>
    <th>No</th>
    <th>Tanggal Pengajuan</th>
    <th>Nama</th>
    <th>Mobil yang di pinjam</th>
    <th>Waktu dan tanggal Peminjaman(Dari)</th>
    <th>Waktu dan tanggal Peminjaman(Sampai)</th>
    <th>Status</th>
</tr>';

// Ambil data dari database
$no = 1;
$query = mysqli_query($conn, "
    SELECT u.username AS user_name, c.car_name, cr.start_datetime, cr.end_datetime, cr.status, cr.created_at
    FROM car_requests cr
    JOIN users u ON cr.users_id = u.id
    JOIN cars c ON cr.car_id = c.id
    ORDER BY cr.created_at DESC
");

while ($row = mysqli_fetch_assoc($query)) {
    $html .= "
    <tr>
        <td>{$no}</td>
        <td>{$row['created_at']}</td>
        <td>{$row['user_name']}</td>
        <td>{$row['car_name']}</td>
        <td>{$row['start_datetime']}</td>
        <td>{$row['end_datetime']}</td>
        <td>".ucfirst($row['status'])."</td>
    </tr>";
    $no++;
}

$html .= '</table>';

// Load dan render PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("laporan_peminjaman_mobil.pdf", ["Attachment" => false]);
?>
