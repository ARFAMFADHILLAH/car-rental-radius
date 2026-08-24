<?php
require '../functions.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->fromArray([
    ['No', 'Email', 'Role', 'Dibuat']
]);

$no = 1;
$row = 2;

$q = mysqli_query($conn, "SELECT * FROM users WHERE role='pengguna' ORDER BY id DESC");
while ($m = mysqli_fetch_assoc($q)) {
    $sheet->setCellValue("A$row", $no++);
    $sheet->setCellValue("B$row", $m['email']);
    $sheet->setCellValue("C$row", $m['role']);
    $sheet->setCellValue("D$row", $m['created_at']);
    $row++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="laporan_user.xlsx"');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
