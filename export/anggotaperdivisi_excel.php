<?php
require '../functions.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header
$sheet->fromArray([
    ['No', 'Email', 'Role','Divisi', 'Dibuat']
]);

$no = 1;
$row = 2;

$division_login = $_SESSION['division_id'] ?? null;

if ($division_login) {

    // Ambil nama divisi leader
    $getDivision = mysqli_query($conn, "SELECT name FROM divisions WHERE id = '$division_login'");
    $divisionData = mysqli_fetch_assoc($getDivision);
    $divisionName = $divisionData['name'] ?? '';

    if (strpos($divisionName, 'Leader ') === 0) {

        $anggotaDivisi = trim(str_replace('Leader ', '', $divisionName));

        $divisiArray = array_map('trim', explode('&', $anggotaDivisi));

        $ids = [];

        foreach ($divisiArray as $div) {
            $getDiv = mysqli_query($conn, "SELECT id FROM divisions WHERE name = '$div'");
            $divData = mysqli_fetch_assoc($getDiv);
            if ($divData) $ids[] = $divData['id'];
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

            while ($m = mysqli_fetch_assoc($users)) {
                $tanggal = !empty($m['created_at']) ? date('d M Y', strtotime($m['created_at'])) : '-';
                $sheet->setCellValue("A$row", $no++);
                $sheet->setCellValue("B$row", $m['email']);
                $sheet->setCellValue("C$row", $m['role']);
                $sheet->setCellValue("D$row", $m['division_name']);
                $sheet->setCellValue("E$row", $tanggal);
                $row++;
            }
        }

    }
}

// Export Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="laporan_anggota_per_divisi.xlsx"');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;