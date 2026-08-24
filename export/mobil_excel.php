<?php
require '../functions.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->fromArray([
    ['No', 'Nama Mobil', 'Plat', 'Status', 'Keterangan']
]);

$no = 1;
$row = 2;

$q = mysqli_query($conn, "SELECT * FROM cars ORDER BY id DESC");
while ($m = mysqli_fetch_assoc($q)) {
    $sheet->setCellValue("A$row", $no++);
    $sheet->setCellValue("B$row", $m['car_name']);
    $sheet->setCellValue("C$row", $m['plate_number']);
    $sheet->setCellValue("D$row", $m['active'] ? 'Tersedia' : 'Tidak Tersedia');
    $sheet->setCellValue("E$row", $m['Keterangan']);
    $row++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="laporan_mobil.xlsx"');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
