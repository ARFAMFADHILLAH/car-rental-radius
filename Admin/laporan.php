<?php
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

/* ================= FILTER ================= */
$division = $_GET['division'] ?? '';
$periode  = $_GET['periode'] ?? '';

$where = "WHERE 1=1";

if ($division) {
    $where .= " AND cr.division='$division'";
}

if ($periode == 'mingguan') {
    $where .= " AND YEARWEEK(cr.start_datetime,1)=YEARWEEK(CURDATE(),1)";
} elseif ($periode == 'bulanan') {
    $where .= " AND MONTH(cr.start_datetime)=MONTH(CURDATE())
                AND YEAR(cr.start_datetime)=YEAR(CURDATE())";
} elseif ($periode == 'tahunan') {
    $where .= " AND YEAR(cr.start_datetime)=YEAR(CURDATE())";
}

/* ================= FULLCALENDAR ================= */
$peminjaman_query = mysqli_query($conn, "
    SELECT cr.*, c.car_name
    FROM car_requests cr
    JOIN cars c ON cr.car_id = c.id
    $where AND cr.status!='rejected'
");

$events = [];
while($row = mysqli_fetch_assoc($peminjaman_query)) {
    $events[] = [
        'title' => $row['car_name']." - ".$row['user_name'],
        'start' => $row['start_datetime'],
        'end'   => $row['end_datetime'],
        'color' => ($row['status']=='approved') ? '#27ae60' :
                   (($row['status']=='returned') ? '#95a5a6' : '#f39c12')
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan & Monitoring</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/laporan.css">
<link rel="icon" href="../favicon_io/favicon.ico">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<style>
.box{background:#fff;padding:20px;border-radius:8px;margin-bottom:30px}
.export-btn a{margin-right:5px}
</style>
</head>

<body>
<?php include "../layout/sidebar.php"; ?>

<div class="content">
<main>

<h1>Laporan & Monitoring Sistem</h1>

<!-- ================= FILTER ================= -->
<div class="box">
<form method="GET">
<select name="division">
<option value="">Semua Divisi</option>
<?php
$div=mysqli_query($conn,"SELECT DISTINCT division FROM car_requests WHERE division!=''");
while($d=mysqli_fetch_assoc($div)){
    $s=$division==$d['division']?'selected':'';
    echo "<option $s>{$d['division']}</option>";
}
?>
</select>

<select name="periode">
<option value="">Semua Waktu</option>
<option value="mingguan" <?= $periode=='mingguan'?'selected':'' ?>>Mingguan</option>
<option value="bulanan" <?= $periode=='bulanan'?'selected':'' ?>>Bulanan</option>
<option value="tahunan" <?= $periode=='tahunan'?'selected':'' ?>>Tahunan</option>
</select>

<button style="background-color:red; color:white;" type="submit">Filter</button>
</form>
</div>

<!-- ================= DIVISI DITOLAK ================= -->
<div class="box">
<h2>Divisi Yang Sering Ditolak</h2>
<table class="table">
<tr><th>Divisi</th><th>Total Ditolak</th></tr>
<?php
$q=mysqli_query($conn,"
    SELECT division,COUNT(*) total
    FROM car_requests
    WHERE status='rejected'
    GROUP BY division
    ORDER BY total DESC
");
while($r=mysqli_fetch_assoc($q)):
?>
<tr>
<td><?= htmlspecialchars($r['division']) ?></td>
<td><?= $r['total'] ?> kali</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- ================= PEMINJAMAN PER DIVISI ================= -->
<div class="box">
<h2>Peminjaman Mobil Per Divisi</h2>
<table class="table">
<tr><th>Divisi</th><th>Total</th></tr>
<?php
$q=mysqli_query($conn,"
    SELECT cr.division,COUNT(*) total
    FROM car_requests cr
    $where AND cr.status='approved'
    GROUP BY cr.division
");
while($r=mysqli_fetch_assoc($q)):
?>
<tr>
<td><?= htmlspecialchars($r['division']) ?></td>
<td><?= $r['total'] ?> kali</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- ================= MOBIL & USER ================= -->
<div class="box">
<h2>Mobil & User Peminjaman</h2>
<table class="table">
<tr>
<th>User</th><th>Divisi</th><th>Mobil</th><th>Total</th>
</tr>
<?php
$q=mysqli_query($conn,"
    SELECT cr.user_name,cr.division,c.car_name,COUNT(*) total
    FROM car_requests cr
    JOIN cars c ON cr.car_id=c.id
    $where AND cr.status='approved'
    GROUP BY cr.user_name,cr.division,c.car_name
    ORDER BY total DESC
");
while($r=mysqli_fetch_assoc($q)):
?>
<tr>
<td><?= htmlspecialchars($r['user_name']) ?></td>
<td><?= htmlspecialchars($r['division']) ?></td>
<td><?= htmlspecialchars($r['car_name']) ?></td>
<td><?= $r['total'] ?> kali</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- ================= DATA USER ================= -->
<div class="box">
<h2>Data User</h2>
<div class="export-btn">
<a href="../export/user_pdf.php" target="_blank" class="btn btn-danger btn-sm">PDF</a>
<a href="../export/user_excel.php" class="btn btn-success btn-sm">Excel</a>
</div>

<table class="table">
<tr><th>No</th><th>Email</th><th>Role</th><th>Dibuat</th></tr>
<?php
$no=1;
$u=mysqli_query($conn,"SELECT * FROM users WHERE role='pengguna'");
while($r=mysqli_fetch_assoc($u)):
?>
<tr>
<td><?= $no++ ?></td>
<td><?= htmlspecialchars($r['email']) ?></td>
<td><?= $r['role'] ?></td>
<td><?= date('d M Y',strtotime($r['created_at'])) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- ================= DATA ADMIN ================= -->
<div class="box">
<h2>Data Admin</h2>
<div class="export-btn">
<a href="../export/admin_pdf.php" target="_blank" class="btn btn-danger btn-sm">PDF</a>
<a href="../export/admin_excel.php" class="btn btn-success btn-sm">Excel</a>
</div>

<table class="table">
<tr><th>No</th><th>Email</th><th>Role</th><th>Dibuat</th></tr>
<?php
$no=1;
$a=mysqli_query($conn,"SELECT * FROM users WHERE role='admin'");
while($r=mysqli_fetch_assoc($a)):
?>
<tr>
<td><?= $no++ ?></td>
<td><?= htmlspecialchars($r['email']) ?></td>
<td><?= $r['role'] ?></td>
<td><?= date('d M Y',strtotime($r['created_at'])) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- ================= DATA MOBIL ================= -->
<div class="box">
<h2>Data Mobil</h2>
<div class="export-btn">
<a href="../export/mobil_pdf.php" target="_blank" class="btn btn-danger btn-sm">PDF</a>
<a href="../export/mobil_excel.php" class="btn btn-success btn-sm">Excel</a>
</div>

<table class="table">
<tr><th>No</th><th>Nama Mobil</th><th>Plat</th><th>Status</th><th>Keterangan</th></tr>
<?php
$no=1;
$m=mysqli_query($conn,"SELECT * FROM cars");
while($r=mysqli_fetch_assoc($m)):
$status = $r['active'] ? 'Tersedia' : 'Tidak Tersedia';
?>
<tr>
<td><?= $no++ ?></td>
<td><?= htmlspecialchars($r['car_name']) ?></td>
<td><?= htmlspecialchars($r['plate_number']) ?></td>
<td><?= $status ?></td>
<td><?= htmlspecialchars($r['Keterangan']) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- ================= CALENDAR ================= -->
<h2>Jadwal Peminjaman Mobil</h2>
<div id="calendar"></div>

<script>
document.addEventListener('DOMContentLoaded',function(){
 new FullCalendar.Calendar(document.getElementById('calendar'),{
  initialView:'dayGridMonth',
  locale:'id',
  events:<?= json_encode($events); ?>
 }).render();
});
</script>

</main>
<footer>&copy; 2026 Car Rental Radius</footer>
</div>

</body>
</html>
