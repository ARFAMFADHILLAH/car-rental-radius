<?php
session_start();
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: approval_list.php");
    exit;
}

$id = (int) $_GET['id'];

/* =========================
   AMBIL DETAIL PENGAJUAN
========================= */
$query = "
    SELECT 
        cr.*,
        u.username AS user_name,
        u.no_telp AS phone,
        d.name AS division,
        c.car_name,
        c.plate_number
    FROM car_requests cr
    JOIN cars c ON cr.car_id = c.id
    JOIN users u ON cr.users_id = u.id
    LEFT JOIN divisions d ON u.division_id = d.id
    WHERE cr.id = $id
";

$result = mysqli_query($conn, $query);
$request = mysqli_fetch_assoc($result);

if (!$request) {
    echo "Data tidak ditemukan";
    exit;
}

/* =========================
   CEK BENTROK JADWAL
========================= */
$bentrok_query = "
    SELECT 
        user_name,
        start_datetime,
        end_datetime
    FROM car_requests
    WHERE car_id = {$request['car_id']}
    AND id != {$request['id']}
    AND status IN ('approved', 'Menunggu Persetujuan Leader')
    AND (
        start_datetime < '{$request['end_datetime']}'
        AND end_datetime > '{$request['start_datetime']}'
    )
";

$bentrok_result = mysqli_query($conn, $bentrok_query);
$bentrok = mysqli_num_rows($bentrok_result) > 0;

// Cek siapa yang login
$login_user_id = $_SESSION['user_id'];
$login_user_division = $_SESSION['division_id']; // misal 6 = Leader HRGA & SCM

// =========================
// PROSES APPROVE LANGSUNG JIKA FORM DIKIRIM
// =========================
if (isset($_GET['action']) && $_GET['action'] === 'approve') {
    $request_id = $id; // gunakan dari GET id

    if ($login_user_division == 6) {
        // Leader HRGA & SCM: approve langsung selesai
        $update_query = "
            UPDATE car_requests
            SET leader_division_approve = 1,
                status = 'approved',
                approved_by = '$login_user_id',
                approved_at = NOW()
            WHERE id = '$request_id'
        ";
    } else {
        // Leader biasa: hanya update leader_division_approve
        $update_query = "
            UPDATE car_requests
            SET leader_division_approve = 1
            WHERE id = '$request_id'
        ";
    }

    mysqli_query($conn, $update_query);

    // Refresh halaman agar status terbaru tampil
    header("Location: approval_detail.php?id=$request_id");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Detail Approval</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon_io/favicon-32x32.png">
    <link rel="icon" href="../favicon_io/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/approvalpeminjaman.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include "../layout/sidebarleader.php"; ?>

<section class="container">
    <h3>Detail Pengajuan Peminjaman</h3>

    <!-- ROW -->
    <div class="detail-row">

        <!-- DATA PEMINJAM -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-user"></i> Data Peminjam
            </div>
             <p><b>Tanggal Pengajuan:<br></b> <?= date('d M Y H:i', strtotime($request['created_at'])) ?></p>
            <p><b>Nama:</b> <?= htmlspecialchars($request['user_name']) ?></p>
            <p><b>Divisi:</b> <?= htmlspecialchars($request['division']) ?></p>
            <p><b>No HP:</b> <?= htmlspecialchars($request['phone'] ?? '-') ?></p>
            <p><b>Keperluan:</b> <?= htmlspecialchars($request['purpose']) ?></p>
            <p><b>Lokasi Tujuan:</b> <?= htmlspecialchars($request['destination']) ?></p>
            <p><b>Nama Customer:</b> <?= htmlspecialchars($request['customer_name']) ?></p>
        </div>

        <!-- DATA MOBIL -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-car"></i> Data Mobil Yang Di Pinjam
            </div>
            <p><b>Mobil:</b> <?= htmlspecialchars($request['car_name']) ?></p>
            <p><b>Plat:</b> <?= htmlspecialchars($request['plate_number']) ?></p>
        </div>

        <!-- TANGGAL & STATUS -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-calendar"></i> Jadwal Peminjaman & Status
            </div>
            <p><b>Tanggal Peminjaman(Dari):<br></b> <?= date('d M Y H:i', strtotime($request['start_datetime'])) ?></p>
            <p><b>Tanggal Peminjaman(Sampai):<br></b> <?= date('d M Y H:i', strtotime($request['end_datetime'])) ?></p>
            <p>
            <b>Status Peminjaman:<br></b> <p><?= $request['status'] ?></p>
            </p>
        </div>

    </div>

    <!-- ALERT BENTROK -->
    <?php if ($bentrok): ?>
        <div class="alert alert-danger">
            <strong><i class="fas fa-triangle-exclamation"></i> BENTROK JADWAL</strong>
            <ul>
                <?php while ($b = mysqli_fetch_assoc($bentrok_result)): ?>
                    <li>
                        <?= htmlspecialchars($b['user_name']) ?> :
                        <?= date('d M Y H:i', strtotime($b['start_datetime'])) ?>
                        – <?= date('d M Y H:i', strtotime($b['end_datetime'])) ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- AKSI -->
<div class="action-btn">

<?php 
// Kondisi 1: Menunggu Persetujuan Awal (Leader Divisi)
if ($request['status'] === 'Menunggu Persetujuan Leader'): ?>
    
    <a href="approval_action.php?action=approve&id=<?= $request['id'] ?>" class="btn btn-approve">
        <i class="fas fa-check"></i> Setujui
    </a>
    <button type="button" class="btn btn-reject" onclick="openRejectModal()">
        <i class="fas fa-times"></i> Tolak
    </button>

<?php 
// Kondisi 2: Menunggu Persetujuan Leader GA
elseif ($request['status'] === 'Menunggu Persetujuan Leader GA'): ?>

    <?php if ($login_user_division == 5 || $login_user_division == 6): ?>
        <a href="approval_action.php?action=approve&id=<?= $request['id'] ?>" class="btn btn-approve">
            <i class="fas fa-check"></i> Setujui
        </a>
        <button type="button" class="btn btn-reject" onclick="openRejectModal()">
            <i class="fas fa-times"></i> Tolak
        </button>
    <?php endif; ?>

<?php 
// Kondisi 3: Sudah Approved atau Lainnya
elseif ($request['status'] === 'canceled'): ?>
    <a href="approval_action.php?action=cancel&id=<?= $request['id'] ?>"
       class="btn btn-cancel" onclick="return confirm('Yakin ingin membatalkan?')">
        <i class="fas fa-ban"></i> Batalkan Peminjaman
    </a>
<?php endif; ?>

    <a href="approval_list.php" class="btn btn-detail" style="margin-left: 10px;">
        <i class="fas fa-arrow-left" style="margin-top: 5px;"></i> Kembali
    </a>
</div>



    <?php include "../layout/footer.php"; ?>
</section>

<!-- MODAL REJECT -->
<div id="rejectModal" class="modal-overlay">
    <div class="modal-box">
        <h3>Alasan Penolakan</h3>

        <form action="approval_action.php" method="POST">
            <input type="hidden" name="id" value="<?= $request['id'] ?>">
            <input type="hidden" name="action" value="reject">

            <textarea name="notes" rows="5" required
                      placeholder="Masukkan alasan penolakan..."
                      class="modal-textarea"></textarea>

            <div class="modal-action">
                <button type="submit" class="btn btn-reject">
                    <i class="fas fa-times"></i> Tolak
                </button>
                <button type="button" class="btn btn-detail" onclick="closeRejectModal()">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal() {
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>

</body>
</html>
