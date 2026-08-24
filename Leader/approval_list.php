<?php
session_start();
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$query = "
    SELECT 
        cr.id,
        u.username AS user_name,
        u.email,
        c.car_name,
        cr.start_datetime,
        cr.end_datetime,
        cr.created_at,
        cr.status,
        cr.car_id
    FROM car_requests cr
    JOIN cars c ON cr.car_id = c.id
    JOIN users u ON cr.users_id = u.id
   ORDER BY 
    CASE 
        WHEN cr.status = 'Menunggu Persetujuan Leader' THEN 1
        WHEN cr.status = 'Menunggu Persetujuan Leader GA' THEN 2
        WHEN cr.status = 'approved' THEN 3
        WHEN cr.status = 'rejected' THEN 4
        ELSE 5
    END,
    cr.created_at ASC
";


$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Approval Peminjaman</title>
<link rel="icon" href="../favicon_io/favicon.ico">
<link rel="stylesheet" href="../css/approvalpeminjaman.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
}
td:first-child { padding: 5px 10px; }
</style>
</head>
<body>

<?php include "../layout/sidebarleader.php"; ?>

<div class="content">
<section class="container">
<h3>Approval Peminjaman Mobil</h3>
<div style="margin-bottom:15px;">
    <a href="../export/peminjaman_excel.php" class="btn btn-approve" style="margin-right: 10px;">
        <i class="fa fa-file-excel"></i> Export Excel
    </a>
    <a href="../export/peminjaman_pdf.php" class="btn btn-detail" target="_blank">
        <i class="fa fa-file-pdf"></i> Export PDF
    </a>
</div>

<form method="post" action="delete_requests.php"
      onsubmit="return confirm('Yakin menghapus data yang dipilih? Ini akan menghapus permanen!');">

<table border="1" cellpadding="6" cellspacing="0">
<thead>
<tr>
    <th><input type="checkbox" id="checkAll"></th>
    <th>No</th>
    <th>Tanggal Pengajuan</th>
    <th>Nama</th>
    <th>Mobil Yang Di Pinjam</th>
    <th>Tanggal Peminjaman (Dari)</th>
    <th>Tanggal Peminjaman (Sampai)</th>
    <th>Status</th>
    <th class="text-center">Aksi</th>
</tr>
</thead>

<tbody>
<?php $no = 1; while ($row = mysqli_fetch_assoc($result)) : ?>

<?php
// Cek bentrok
$bentrok = false;
if ($row['status'] === 'approved') {
    $cekBentrok = mysqli_query($conn, "
        SELECT id FROM car_requests
        WHERE car_id = '{$row['car_id']}'
        AND status = 'approved'
        AND start_datetime <= '{$row['end_datetime']}'
        AND end_datetime >= '{$row['start_datetime']}'
        AND id != '{$row['id']}'
    ");
    $bentrok = mysqli_num_rows($cekBentrok) > 0;
}
?>

<tr>
    <td class="td-checkbox" data-label="Pilih">
        <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="deleteCheckbox">
    </td>

    <td data-label="No"><?= $no++ ?></td>

    <td data-label="Tanggal Pengajuan">
        <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
    </td>

    <td data-label="Nama">
        <?= htmlspecialchars($row['user_name']) ?>
    </td>

    <td data-label="Mobil Yang Di Pinjam">
        <?= htmlspecialchars($row['car_name']) ?>
    </td>

    <td data-label="Tanggal Peminjaman (Dari)">
        <?= date('d M Y H:i', strtotime($row['start_datetime'])) ?>
    </td>
    <td data-label="Tanggal Peminjaman (Sampai)">
        <?= date('d M Y H:i', strtotime($row['end_datetime'])) ?>
    </td>

    <td data-label="Status">
        <span class="badge" style="color: black; font-size: 16px;">
            <?= ucfirst($row['status']) ?>
        </span>
    </td>

    <td data-label="Aksi" class="text-center">
        <a class="btn btn-detail" href="approval_detail.php?id=<?= $row['id'] ?>">
            Detail
        </a>
    </td>
</tr>


<?php endwhile; ?>
</tbody>
</table>

<br>
<button type="submit" class="btn btn-reject" style="margin-top:10px color">
    Hapus Data Terpilih 
</button>

</form>
</section>

<?php include "../layout/footer.php"; ?>
</div>

<script>
// Check All
document.getElementById('checkAll').addEventListener('change', function () {
    const checkboxes = document.querySelectorAll('input.deleteCheckbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

</body>
</html>
