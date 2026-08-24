<?php
require '../functions.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (ob_get_length()) {
    ob_end_clean();
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();


// --- Tambahkan Nama & Tagline ---
$sheet->setCellValue('B1', 'PT Radius Allkindo Electric');
$sheet->setCellValue('B2', 'Partner for Electrical and Safety Equipments');

// Merge untuk teks agar rapi
$sheet->mergeCells('B1:G1');
$sheet->mergeCells('B2:G2');

// Format nama & tagline
$sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('B2')->getFont()->setItalic(true)->setSize(12);

// --- Header Tabel (di row ke 6, di bawah logo & nama) ---
$startRow = 6;
$headers = ['No', 'Tanggal Pengajuan', 'Nama', 'Divisi', 'Mobil yang dipinjam', 'Tanggal dan waktu peminjaman(Dari)', 'Tanggal dan waktu peminjaman(Sampai)', 'Status'];
$sheet->fromArray($headers, NULL, "A$startRow");

// --- Styling header tabel ---
$sheet->getStyle("A$startRow:H$startRow")->getFont()->setBold(true);
$sheet->getStyle("A$startRow:H$startRow")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCE5FF');
$sheet->getStyle("A$startRow:H$startRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// --- Ambil data dari database ---
$query = mysqli_query($conn, "
    SELECT 
        u.username AS user_name, 
        d.name AS division,
        c.car_name, 
        cr.start_datetime, 
        cr.end_datetime, 
        cr.status, cr.created_at
    FROM car_requests cr
    JOIN users u ON cr.users_id = u.id
    LEFT JOIN divisions d ON u.division_id = d.id
    JOIN cars c ON cr.car_id = c.id
    ORDER BY cr.created_at DESC
");

$no = 1;
$row = $startRow + 1;

while ($data = mysqli_fetch_assoc($query)) {
    $sheet->setCellValue("A$row", $no++);
    $sheet->setCellValue("B$row", $data['created_at']);
    $sheet->setCellValue("C$row", $data['user_name']);
    $sheet->setCellValue("D$row", $data['division']);
    $sheet->setCellValue("E$row", $data['car_name']);
    $sheet->setCellValue("F$row", $data['start_datetime']);
    $sheet->setCellValue("G$row", $data['end_datetime']);
    $sheet->setCellValue("H$row", ucfirst($data['status']));
    $row++;
}

// --- Styling semua data tabel ---
$sheet->getStyle("A" . ($startRow+1) . ":H" . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Auto size kolom
foreach (range('A','H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// --- Header untuk download Excel ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="laporan_peminjaman_mobil.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
