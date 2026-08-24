<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../functions.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

$dompdf = new Dompdf();

$html = '<h3>Laporan Anggota Per Divisi</h3>';
$html .= '<table border="1" width="100%" cellspacing="0" cellpadding="6">';
$html .= '<tr>
<th>No</th>
<th>Email</th>
<th>Role</th>
<th>Divisi</th>
<th>Dibuat</th>
</tr>';

$no = 1;
$division_login = $_SESSION['division_id'] ?? null;

if ($division_login) {

    // Ambil nama divisi login
    $getDivision = mysqli_query($conn, "
        SELECT name FROM divisions WHERE id = '$division_login'
    ");

    $divisionData = mysqli_fetch_assoc($getDivision);
    $divisionName = $divisionData['name'] ?? '';

    // Jika dia Leader
    if (strpos($divisionName, 'Leader ') === 0) {

        // Hapus kata Leader
        $anggotaDivisi = trim(str_replace('Leader ', '', $divisionName));

        
        $divisiArray = array_map('trim', explode('&', $anggotaDivisi));

        $ids = [];

        foreach ($divisiArray as $div) {

            $getDiv = mysqli_query($conn, "
                SELECT id FROM divisions WHERE name = '$div'
            ");

            $divData = mysqli_fetch_assoc($getDiv);

            if ($divData) {
                $ids[] = $divData['id'];
            }
        }

        if (!empty($ids)) {

            $idsStr = implode(',', $ids);

            $users = mysqli_query($conn, "
                SELECT users.*, divisions.name AS division_name
                FROM users
                LEFT JOIN divisions ON users.division_id = divisions.id
                WHERE users.division_id IN ($idsStr)
                ORDER BY users.id DESC
            ");

            if (!$users) {
                die(mysqli_error($conn));
            }

            while ($l = mysqli_fetch_assoc($users)) {

                $tanggal = !empty($l['created_at'])
                    ? date('d M Y', strtotime($l['created_at']))
                    : '-';

                $html .= "
                <tr>
                    <td>$no</td>
                    <td>{$l['email']}</td>
                    <td>{$l['role']}</td>
                    <td>{$l['division_name']}</td>
                    <td>$tanggal</td>
                </tr>";

                $no++;
            }
        }
    }
}

$html .= '</table>';

ob_end_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("laporan_anggota_per_divisi.pdf", ["Attachment" => false]);