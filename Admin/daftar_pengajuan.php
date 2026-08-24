<?php
session_start();
require '../functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$users_id = $_SESSION['user_id'];
$user_division_id = $_SESSION['division_id'];

// Ambil filter dari GET
$statusFilter   = isset($_GET['status']) ? $_GET['status'] : '';
$divisionFilter = isset($_GET['division']) ? $_GET['division'] : '';

// Query dasar
$query = "
SELECT 
    u.username AS user_name,
    d.name AS division,
    u.no_telp,
    cr.start_datetime,
    cr.end_datetime,
    cr.purpose,
    cr.status,
    cr.notes,
    cr.created_at,
    cr.leader_division_approve,
    cr.leader_ga_approve,
    c.car_name
FROM car_requests cr
JOIN cars c ON cr.car_id = c.id
JOIN users u ON cr.users_id = u.id
LEFT JOIN divisions d ON u.division_id = d.id
WHERE cr.users_id = '$users_id'
";

// Tambahkan filter jika dipilih
if($statusFilter != ''){
    $query .= " AND cr.status = '$statusFilter'";
}

if($divisionFilter != ''){
    $query .= " AND u.division_id = '$divisionFilter'";
}

$query .= " ORDER BY cr.created_at DESC";

$result = mysqli_query($conn, $query);

// Ambil data divisi untuk dropdown
$divisions = mysqli_query($conn, "SELECT id, name FROM divisions ORDER BY name ASC");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Daftar Pengajuan</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/daftar_pengajuan.css">
<link rel="icon" type="image/png" sizes="32x32" href="../favicon_io/favicon-32x32.png">
<link rel="icon" href="../favicon_io/favicon.ico" type="image/x-icon">
</head>
<body>

<?php include "../layout/sidebar.php"; ?>

<div class="content">
    <br>
<h2>Daftar Pengajuan</h2>

<!-- ================= FILTER ================= -->
<form method="GET" style="margin-bottom:20px;">

    <label>Status:</label>
    <select name="status">
        <option value="">Semua</option>
        <option value="approved" <?= $statusFilter=='approved'?'selected':'' ?>>Disetujui</option>
        <option value="rejected" <?= $statusFilter=='rejected'?'selected':'' ?>>Ditolak</option>
        <option value="Canceled" <?= $statusFilter=='Canceled'?'selected':'' ?>>Dibatalkan</option>
        <option value="returned" <?= $statusFilter=='returned'?'selected':'' ?>>Dikembalikan</option>
    </select>

    <label>Divisi:</label>
    <select name="division">
        <option value="">Semua</option>
        <?php while($div = mysqli_fetch_assoc($divisions)): ?>
            <option value="<?= $div['id'] ?>" <?= $divisionFilter==$div['id']?'selected':'' ?>>
                <?= $div['name'] ?>
            </option>
        <?php endwhile; ?>
    </select>
       

    <button type="submit"
        style="background-color:#b81414; color:white; padding:8px 15px; border:none; border-radius:5px;">
        Filter
    </button>

        </br>

        </br>
    <!-- Tombol Bersihkan -->
    <a href="daftar_pengajuan.php"
        style="background-color:#b81414; color:white; padding:8px 15px; border-radius:5px; text-decoration:none;">
        Bersihkan Semua
    </a>

</form>
<!-- ================= END FILTER ================= -->


<table>
<thead>
<tr>
<th>No</th>
<th>Tanggal Pengajuan</th>
<th>Nama</th>
<th>Divisi</th>
<th>Mobil Yang Di Pinjam</th>
<th>Tanggal Peminjaman (Dari)</th>
<th>Tanggal Peminjaman (Sampai)</th>
<th>Status Persetujuan</th>
<th>Pesan</th>
</tr>
</thead>
<tbody>
<?php $no=1; while($row=mysqli_fetch_assoc($result)): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= date('d M Y H:i',strtotime($row['created_at'])) ?></td>
<td><?= $row['user_name'] ?></td>
<td><?= $row['division'] ?></td>
<td><?= $row['car_name'] ?></td>
<td><?= date('d M Y H:i',strtotime($row['start_datetime'])) ?></td>
<td><?= date('d M Y H:i',strtotime($row['end_datetime'])) ?></td>
<td>
<?php
$statusText = "Menunggu Persetujuan Leader GA";

if ($row['status'] == 'rejected') {
    $statusText = "Ditolak";

} elseif ($row['status'] == 'approved' || $row['status'] == 'returned') {
    $statusText = "Disetujui";

} elseif ($row['leader_division_approve'] == 1 && $row['leader_ga_approve'] == 0) {
    $statusText = "Menunggu Persetujuan Leader GA";

} elseif ($row['leader_division_approve'] == 1 && $row['leader_ga_approve'] == 1) {
    $statusText = "Disetujui";
}

echo $statusText;
?>
</td>
<td><?= $row['notes'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>


<script>
function openModal(status, divisiApprove, gaApprove, userDivision, notes){
    let step = 1;

    if(status === 'rejected'){
        step = 2;
        document.getElementById("rejectedMessage").innerHTML =
            "Pengajuan ditolak: " + notes;
    } else {

        if(divisiApprove == 1){
            step = 2;

            if(userDivision == 6){
                step = 4;
            }
        }

        if(gaApprove == 1){
            step = 3;
        }

        if(status === 'approved' || status === 'returned'){
            step = 4;
        }
    }

    for(let i=1;i<=4;i++){
        document.getElementById("c"+i).classList.remove("active");
    }

    for(let i=1;i<=step;i++){
        document.getElementById("c"+i).classList.add("active");
    }

    document.getElementById("line").style.width = ((step-1)/3)*100 + "%";
    document.getElementById("statusModal").style.display="block";
}

function closeModal(){
    document.getElementById("statusModal").style.display="none";
    document.getElementById("rejectedMessage").innerHTML = "";
}
</script>

</body>
</html>