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
    AND status = 'approved'
    AND (
        start_datetime < '{$request['end_datetime']}'
        AND end_datetime > '{$request['start_datetime']}'
    )
";

$bentrok_result = mysqli_query($conn, $bentrok_query);
$bentrok = mysqli_num_rows($bentrok_result) > 0;

/* =========================
   STATUS HANDLING
========================= */
$status = trim($request['status']);

$waiting_status = [
    'Menunggu Persetujuan Leader',
    'Menunggu Persetujuan Leader GA'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Detail Approval</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/approvalpeminjaman.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include "../layout/sidebar.php"; ?>

<section class="container">
    <h3>Detail Pengajuan Peminjaman</h3>

    <div class="detail-row">

        <!-- DATA PEMINJAM -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-user"></i> Data Peminjam
            </div>
            <p><b>Tanggal Pengajuan:</b><br>
                <?= date('d M Y H:i', strtotime($request['created_at'])) ?>
            </p>
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
                <i class="fas fa-car"></i> Data Mobil
            </div>
            <p><b>Mobil:</b> <?= htmlspecialchars($request['car_name']) ?></p>
            <p><b>Plat:</b> <?= htmlspecialchars($request['plate_number']) ?></p>
        </div>

        <!-- JADWAL -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-calendar"></i> Jadwal & Status
            </div>
            <p><b>Dari:</b><br>
                <?= date('d M Y H:i', strtotime($request['start_datetime'])) ?>
            </p>
            <p><b>Sampai:</b><br>
                <?= date('d M Y H:i', strtotime($request['end_datetime'])) ?>
            </p>
            <p><b>Status:</b><br>
                <?= htmlspecialchars($status) ?>
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
                        –
                        <?= date('d M Y H:i', strtotime($b['end_datetime'])) ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- AKSI -->
    <div class="action-btn">

    <?php if (in_array($status, $waiting_status)): ?>

        <a href="approval_action.php?action=approve&id=<?= $request['id'] ?>"
           class="btn btn-approve"
           onclick="return confirm('Setujui pengajuan ini?')">
            <i class="fas fa-check"></i> Setujui
        </a>

        <button type="button"
            class="btn btn-reject"
            onclick="openRejectModal()">
            <i class="fas fa-times"></i> Tolak
        </button>

    <?php elseif ($status === 'approved'): ?>

        <a href="approval_action.php?action=cancel&id=<?= $request['id'] ?>"
           class="btn btn-cancel"
           onclick="return confirm('Yakin ingin membatalkan peminjaman ini?')">
            <i class="fas fa-ban"></i> Batalkan Peminjaman
        </a>

    <?php endif; ?>

        <a href="approval_list.php"
           class="btn btn-detail"
           style="background-color: #6c757d;">
            <i class="fas fa-arrow-left"></i> Kembali
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

            <textarea name="notes"
                rows="5"
                required
                placeholder="Masukkan alasan penolakan..."
                class="modal-textarea"></textarea>

            <div class="modal-action">
                <button type="submit" class="btn btn-reject">
                    <i class="fas fa-times"></i> Tolak
                </button>

                <button type="button"
                    class="btn btn-detail"
                    onclick="closeRejectModal()">
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