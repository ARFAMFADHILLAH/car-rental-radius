<?php
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

/* ================= FILTER ================= */
$division = $_GET['division'] ?? '';
$status = $_GET['status'] ?? '';
$periode  = $_GET['periode'] ?? '';
$start_date = $_GET['start_date'] ??'';
$end_date = $_GET['end_date'] ??'';

$where = "WHERE 1=1";

if ($division) {
    $where .= " AND cr.division='$division'";
}

if($status){
    $where .= " AND cr.status='$status'";
}

// Logika Filter Waktu
if($start_date && $end_date){
    // Jika user memilih range tanggal di kalender
    $where .= " AND cr.start_datetime BETWEEN '$start_date' AND '$end_date'";
} elseif ($periode == '7_hari') {
    // Jika user memilih 7 hari terakhir
    $where .= " AND cr.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($periode == '30_hari') {
    // Jika user memilih 30 hari terakhir
    $where .= " AND cr.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($periode == '1_tahun') {
    // Jika user memilih 1 tahun terakhir
    $where .= " AND cr.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
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
                   (($row['status']=='rejected') ? '#95a5a6' : '#f39c12')
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
<?php include "../layout/sidebarleader.php"; ?>

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

/* =================================
   LOGIKA TAMPIL DATA ANGGOTA DIVISI
====================== */

$no = 1;
$users = null;

$divisionLogin = $_SESSION['division_id'] ?? null;

if ($divisionLogin) {

    $getDivision = mysqli_query($conn, "SELECT name FROM divisions WHERE id = '$divisionLogin'");
    $divisionData = mysqli_fetch_assoc($getDivision);
    $divisionName = $divisionData['name'] ?? null; // gunakan null jika tidak ada

    // pastikan $divisionName ada sebelum diproses
    if ($divisionName && strpos($divisionName, 'Leader ') === 0) {

        // Hapus kata "Leader "
        $anggotaDivisi = str_replace('Leader ', '', $divisionName);

        // Pisah divisi jika ada "&" atau koma
        $divisiArray = array_map('trim', preg_split('/[,&]/', $anggotaDivisi));

        // Ambil semua ID divisi
        $ids = [];
        foreach ($divisiArray as $div) {
            $getDiv = mysqli_query($conn, "SELECT id FROM divisions WHERE name = '$div'");
            $divData = mysqli_fetch_assoc($getDiv);
            if ($divData) {
                $ids[] = $divData['id'];
            }
        }

        // Jika ada ID divisi, ambil user yang termasuk divisi-divisi tersebut
        if (!empty($ids)) {
            $idsStr = implode(',', $ids);
            $users = mysqli_query($conn, "
                SELECT users.*, divisions.name AS division_name
                FROM users
                LEFT JOIN divisions ON users.division_id = divisions.id
                WHERE users.division_id IN ($idsStr)
                ORDER BY users.id DESC
            ");
        } else {
            $users = mysqli_query($conn, "SELECT * FROM users WHERE 1=0"); // tidak ada user
        }

    } else {
        // Jika bukan leader, tampil semua user
        $users = mysqli_query($conn, "
            SELECT users.*, divisions.name AS division_name
            FROM users
            LEFT JOIN divisions ON users.division_id = divisions.id
            ORDER BY users.id DESC
        ");
    }

} else {
    // jika tidak login divisi
    $users = mysqli_query($conn, "SELECT * FROM users WHERE 1=0");
}

// Jika query gagal
if (!$users) {
    $users = mysqli_query($conn, "SELECT * FROM users WHERE 1=0");
}
?>
</select>

<select name="periode">
            <option value="">Pilih Periode</option>
            <option value="7_hari" <?= $periode=='7_hari'?'selected':'' ?>>7 Hari Terakhir</option>
            <option value="30_hari" <?= $periode=='30_hari'?'selected':'' ?>>30 Hari Terakhir</option>
            <option value="1_tahun" <?= $periode=='1_tahun'?'selected':'' ?>>1 Tahun Terakhir</option>
</select>


<input type="date" name="start_date" value="<?= $start_date ?>" placeholder="Tanggal Peminjaman (Dari)">
<input type="date" name="end_date" value="<?= $end_date ?>" placeholder="Tanggal Peminjaman (Sampai)">

<select name="status">
            <option value="">Semua Status</option>
            <option value="approved" <?= $status=='approved'?'selected':'' ?>>Approved</option>
            <option value="rejected" <?= $status=='rejected'?'selected':'' ?>>Rejected</option>
</select>

<button style="background-color: #2ecc71; color: white; border:none; padding: 7px 15px; border-radius:4px; cursor:pointer;" type="submit">Filter</button>
<a href="?" style="text-decoration:none; font-size:12px; color:#666; margin-left:10px;">Reset</a>
</form>
</div>

<div class="export-btn" style="margin-bottom: 20px;">
    <a href="../export/laporan_mobildanuserpdf.php?division=<?= $division ?>&status=<?= $status ?>&periode=<?= $periode ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
       target="_blank" class="btn btn-danger">PDF</a>
    
    <a href="../export/laporan_mobildanuserexcel.php?division=<?= $division ?>&status=<?= $status ?>&periode=<?= $periode ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
       class="btn btn-success">Excel</a>
</div>

<!-- ================= MOBIL & USER ================= -->
<div class="box">
<h2>Mobil & User Peminjaman</h2>
<table class="table">
<tr>
<th>Tanggal Pengajuan</th><th>Nama Peminjam</th><th>Divisi</th><th>Mobil Yang Di Pinjam</th><th>Total Peminjaman</th><th>Status</th>
</tr>
<?php

$q=mysqli_query($conn,"
    SELECT cr.created_at,cr.user_name,cr.division,c.car_name, cr.status,COUNT(*) total
    FROM car_requests cr
    JOIN cars c ON cr.car_id=c.id
    $where AND cr.status='approved'
    GROUP BY cr.created_at,cr.user_name,cr.division,c.car_name, cr.status
    ORDER BY total DESC
");
while($r=mysqli_fetch_assoc($q)):
?>
<tr>
<td><?= htmlspecialchars($r['created_at']) ?></td>
<td><?= htmlspecialchars($r['user_name']) ?></td>
<td><?= htmlspecialchars($r['division']) ?></td>
<td><?= htmlspecialchars($r['car_name']) ?></td>
<td><?= $r['total'] ?> kali</td>
<td><?= $r['status'] ?></td>

</tr>
<?php endwhile; ?>
</table>
</div>

<div class="export-btn" style="margin-bottom: 20px;">
    <a href="../export/laporan_peminjamandivisipdf.php?division=<?= $division ?>&status=<?= $status ?>&periode=<?= $periode ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
       target="_blank" class="btn btn-danger">PDF</a>
    
    <a href="../export/laporan_peminjamandivisiexcel.php?division=<?= $division ?>&status=<?= $status ?>&periode=<?= $periode ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
       class="btn btn-success">Excel</a>
</div>

<!-- ================= PEMINJAMAN PER DIVISI ================= -->
<div class="box">
<h2>Divisi Yang Paling Sering Meminjam Mobil</h2>
<table class="table">
<tr><th>Tanggal Pengajuan</th><th>Divisi</th><th>Total Peminjaman</th></tr>
<?php
$q = mysqli_query($conn, "
    SELECT cr.division, COUNT(*) AS total, MIN(cr.created_at) AS first_created
    FROM car_requests cr
    $where AND cr.status='approved'
    GROUP BY cr.division
    ORDER BY total DESC
");
while($r = mysqli_fetch_assoc($q)):
?>
<tr>
<td><?= htmlspecialchars($r['first_created']) ?></td>
<td><?= htmlspecialchars($r['division']) ?></td>
<td><?= $r['total'] ?> kali</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<div class="export-btn" style="margin-bottom: 20px;">
    <a href="../export/laporan_divisiditolakpdf.php?division=<?= $division ?>&status=<?= $status ?>&periode=<?= $periode ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
       target="_blank" class="btn btn-danger">PDF</a>
    
    <a href="../export/laporan_divisiditolakexcel.php?division=<?= $division ?>&status=<?= $status ?>&periode=<?= $periode ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
       class="btn btn-success">Excel</a>
</div>


<!-- ================= DIVISI DITOLAK ================= -->
<div class="box">
<h2>Divisi Yang Ditolak</h2>
<table class="table">
<tr><th>Tanggal Pengajuan</th><th>Divisi</th><th>Total Ditolak</th><th>Status</th></tr>
<?php
$q = mysqli_query($conn, "
    SELECT cr.division, cr.status, COUNT(*) AS total, MIN(cr.created_at) AS first_created
    FROM car_requests cr
    $where AND cr.status='rejected'
    GROUP BY cr.division, cr.status
    ORDER BY total DESC
");
while($r = mysqli_fetch_assoc($q)):
?>
<tr>
<td><?= $r['first_created'] ?></td>
<td><?= htmlspecialchars($r['division']) ?></td>
<td><?= $r['total'] ?> kali</td>
<td><?= $r['status'] ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- ================= DATA ANGGOTA DIVISI ================= -->
<div class="box">
<h2>Data Anggota Divisi</h2>
<div class="export-btn">
    <a href="../export/anggotaperdivisi_pdf.php" target="_blank" class="btn btn-danger btn-sm">PDF</a>
    <a href="../export/anggotaperdivisi_excel.php" class="btn btn-success btn-sm">Excel</a>
</div>
<br>
<table class="table">
<tr>
    <th>No</th>
    <th>Email</th>
    <th>Role</th>
    <th>Divisi</th>
    <th>Dibuat</th>
</tr>

<?php
$no = 1;

// Ambil divisi login leader
$divisionLogin = $_SESSION['division_id'] ?? null;

if ($divisionLogin) {
    $getDivision = mysqli_query($conn, "SELECT name FROM divisions WHERE id = '$divisionLogin'");
    $divisionData = mysqli_fetch_assoc($getDivision);
    $divisionName = $divisionData['name'] ?? '';

    // Jika user adalah Leader
    if (strpos($divisionName, 'Leader ') === 0) {

        // Ambil nama divisi anggota tanpa kata "Leader "
        $anggotaDivisi = str_replace('Leader ', '', $divisionName);

        // Ambil id divisi anggota
        $getAnggotaDiv = mysqli_query($conn, "SELECT id FROM divisions WHERE name = '$anggotaDivisi'");
        $anggotaDivData = mysqli_fetch_assoc($getAnggotaDiv);
        $anggotaDivisionId = $anggotaDivData['id'] ?? 0;

        // Ambil user hanya dari divisi anggota
        $u = mysqli_query($conn, "
            SELECT users.*, divisions.name AS division_name
            FROM users
            LEFT JOIN divisions ON users.division_id = divisions.id
            WHERE users.division_id = '$anggotaDivisionId'
            ORDER BY users.id DESC
        ");
    } else {
        // Jika bukan leader, tampilkan kosong atau semua user (opsional)
        $u = mysqli_query($conn, "
            SELECT users.*, divisions.name AS division_name
            FROM users
            LEFT JOIN divisions ON users.division_id = divisions.id
            WHERE 1=0
        ");
    }
} else {
    // Default: kosong jika session tidak ada
    $u = mysqli_query($conn, "
        SELECT users.*, divisions.name AS division_name
        FROM users
        LEFT JOIN divisions ON users.division_id = divisions.id
        WHERE 1=0
    ");
}

// LOOP DATA
while ($r = mysqli_fetch_assoc($users)):
?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td><?= htmlspecialchars($r['role']) ?></td>
    <td><?= htmlspecialchars($r['division_name'] ?? '-') ?></td>
    <td><?= !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '-' ?></td>
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
<br>
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
