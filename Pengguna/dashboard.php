<?php
session_start();
require '../functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// ambil data dari session
$users_id = $_SESSION['user_id'];


$query_user = mysqli_query($conn, "SELECT username,email FROM users WHERE id = '$users_id'");
$user_data = mysqli_fetch_assoc($query_user);

$username = $user_data['username'];
$user_email = $user_data['email'];

date_default_timezone_set('Asia/Jakarta');

// Sapaan
$hour = date("H");
if ($hour >= 5 && $hour < 12) {
    $greeting = "Selamat pagi";
} elseif ($hour >= 12 && $hour < 15) {
    $greeting = "Selamat siang";
} elseif ($hour >= 15 && $hour < 18) {
    $greeting = "Selamat sore";
} else {
    $greeting = "Selamat malam";
}

/* ===============================
   DATA KETERSEDIAAN MOBIL
================================ */

// Total mobil
$q_mobil = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cars");
$total_mobil = mysqli_fetch_assoc($q_mobil)['total'];

// Mobil sedang dipakai
$q_used = mysqli_query($conn, "
    SELECT COUNT(DISTINCT car_id) AS total
    FROM car_requests
    WHERE status = 'approved'
");
$mobil_dipakai = mysqli_fetch_assoc($q_used)['total'];


// Mobil sedang diservice
$q_mobil_service = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cars WHERE Keterangan = 'Service'");
$mobil_diservice = mysqli_fetch_assoc($q_mobil_service)['total'];


// Mobil yang tersedia
$available = max(0, $total_mobil - $mobil_dipakai - $mobil_diservice);

/* ===============================
   RIWAYAT PENGAJUAN USER
================================ */

$q_status = mysqli_query($conn, "
    SELECT status, COUNT(*) AS total
    FROM car_requests
    WHERE users_id = '$users_id'
    GROUP BY status
");

$stat = [
    'Menunggu Persetujuan Leader' => 0,
    'approved' => 0,
    'rejected' => 0
];

while ($row = mysqli_fetch_assoc($q_status)) {
    $stat[$row['status']] = $row['total'];
}


// foto profil 

// Ambil data user
$user_email = $_SESSION['email']; // sesuai session login
$query_user = mysqli_query($conn, "SELECT photo FROM users WHERE email = '$user_email'");
$user_data = mysqli_fetch_assoc($query_user);

$foto_profile = $user_data['photo'] 
    ? "../uploads/profile/" . $user_data['photo'] 
    : "../uploads/profile-default.png";


// Ambil aktivitas gabungan: Persetujuan, Penolakan, dan Pengajuan Baru
$q_notif_gabungan = mysqli_query($conn, "
    (
    SELECT 'pengajuan' AS tipe, id, user_name, NULL AS approved_by, created_at AS waktu, status, 
    (SELECT car_name FROM cars WHERE id = car_requests.car_id) as mobil, is_read_notif
    FROM car_requests 
    WHERE status = 'Menunggu Persetujuan Leader' AND is_deleted_notif = 0
)
UNION ALL
(
    SELECT 'proses' AS tipe, id, user_name, approved_by, approved_at AS waktu, status, 
    (SELECT car_name FROM cars WHERE id = car_requests.car_id) as mobil, is_read_notif
    FROM car_requests 
    WHERE status IN ('approved', 'rejected') AND approved_by IS NOT NULL AND is_deleted_notif = 0
)
UNION ALL
(
    SELECT 'canceled' AS tipe, id, user_name, NULL AS approved_by, created_at AS waktu, status, 
    (SELECT car_name FROM cars WHERE id = car_requests.car_id) as mobil, is_read_notif
    FROM car_requests 
    WHERE status = 'Canceled' AND is_deleted_notif = 0
)
ORDER BY waktu DESC LIMIT 20
");

// Pengajuan pending yang BELUM dihapus dari notifikasi
$q_pending = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM car_requests 
    WHERE status = 'Menunggu Persetujuan Leader' 
    AND is_deleted_notif = 0 AND is_read_notif = 0
");
$pending = mysqli_fetch_assoc($q_pending)['total'];

// Hitung semua notifikasi yang belum dibaca (Unread)
$q_unread = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM car_requests 
    WHERE is_read_notif = 0 
    AND is_deleted_notif = 0
");
$notif_count = mysqli_fetch_assoc($q_unread)['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Pengguna</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon_io/favicon-32x32.png">
    <link rel="icon" href="../favicon_io/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard_user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>


<?php include "../layout/sidebaruser.php"; ?>

<div class="content">

    <header class="topbar">
    <h1>Dashboard Pengguna</h1>

    <div class="user">
        <div class="notif-wrapper" onclick="toggleModal()" style="position: relative; cursor: pointer; margin-right: 15px;">
        <i class="fa-solid fa-bell" style="font-size: 20px; color: #333;"></i>
        <?php if ($notif_count > 0): ?>
            <span style="position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px;">
                <?= $notif_count ?>
            </span>
        <?php endif; ?>
    </div>
        <img src="<?= $foto_profile ?>" alt="Foto Profil" class="topbar-profile">
        <span><?= $greeting ?>, <?= htmlspecialchars($username) ?></span>
    </div>
</header>



    <div class="info-grid">

        <!-- KETERSEDIAAN MOBIL -->
        <div class="info-box">
            <h4><i class="fa-solid fa-car"></i> Ketersediaan Mobil</h4>
            <ul>
                <li><i class="fa-solid fa-car-tunnel"></i> Total Mobil : <b><?= $total_mobil ?></b></li>
                <li><i class="fa-solid fa-route"></i> Jumlah Mobil Yang Sedang Dipakai : <b><?= $mobil_dipakai ?></b></li>
                <li><i class="fa-solid fa-car-on"></i> Jumlah Mobil Yang Tidak Di Pakai : <b><?= $available ?></b></li>
                <li><i class="fa-solid fa-tools"></i> Jumlah Mobil Yang Di Service / Tidak Tersedia : <b><?= $mobil_diservice ?></b></li>
            </ul>
        </div>

        <!-- RIWAYAT PENGAJUAN -->
        <div class="info-box">
            <h4><i class="fa-solid fa-file-lines"></i> Status Pengajuan Anda</h4>
            <ul>
                <li><i class="fa-solid fa-clock"></i> Menunggu Persetujuan : <b><?= $stat['Menunggu Persetujuan Leader'] ?></b></li>
                <li><i class="fa-solid fa-circle-check"></i> Disetujui : <b><?= $stat['approved'] ?></b></li>
                <li><i class="fa-solid fa-circle-xmark"></i> Ditolak : <b><?= $stat['rejected'] ?></b></li>
            </ul>
        </div>

    </div>

<?php include "../layout/footer.php"; ?>
</div>

<div id="notifModal" class="modal-notif">
    <div class="modal-content-notif">
        <div class="modal-header">
            <h3><i class="fa-solid fa-bell"></i> Notifikasi</h3>
            <div>
                <button onclick="notifAction('markAll')" class="btn-sm">Mark All as Read</button>
                <button onclick="notifAction('deleteAll')" class="btn-sm btn-red">Delete All</button>
                <span class="close-btn" onclick="toggleModal()">&times;</span>
            </div>
        </div>
        
        <form id="notifForm">
            <div class="notif-list-container">
                <?php mysqli_data_seek($q_notif_gabungan, 0); 
                while ($row = mysqli_fetch_assoc($q_notif_gabungan)) : ?>
                    <div class="notif-item <?= $row['is_read_notif'] ? 'read' : 'unread' ?>" id="item-<?= $row['id'] ?>">
                        <input type="checkbox" name="notif_ids[]" value="<?= $row['id'] ?>" class="notif-checkbox">
                        <div class="notif-content">
                            <?php if ($row['status'] == 'approved') : ?>
                                <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i>
                                <span><strong><?= $row['approved_by'] ?></strong> Menyetujui Peminjaman Mobil <strong><?= $row['mobil'] ?></strong> Dari <strong><?= $row['user_name'] ?></strong></span>
                            <?php elseif ($row['status'] == 'rejected') : ?>
                                <i class="fa-solid fa-circle-xmark" style="color: #e74c3c;"></i>
                                <span><strong><?= $row['approved_by'] ?></strong> Menolak Peminjaman Mobil <strong><?= $row['mobil'] ?></strong> Dari <strong><?= $row['user_name'] ?></strong></span>
                            <?php elseif ($row['status'] == 'Canceled') : ?>
    <i class="fa-solid fa-circle-xmark" style="color: #e74c3c;"></i>
    <span><strong><?= $row['user_name'] ?></strong> Membatalkan Peminjaman Mobil <strong><?= $row['mobil'] ?></strong></span>
                            <?php else : ?>
                                <i class="fa-solid fa-envelope" style="color: #f1c40f;"></i>
                                <span>Pengajuan Peminjaman Mobil Dari <strong><?= $row['user_name'] ?></strong></span>
                            <?php endif; ?>
                            <br><small><?= date('d M, H:i', strtotime($row['waktu'])) ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="modal-footer-action">
                <button type="button" onclick="notifAction('markSelected')">Read Selected</button>
                <button type="button" onclick="notifAction('deleteSelected')" class="btn-red">Delete Selected</button>
            </div>
        </form>
    </div>
</div>


</body>
</html>
<script>
      function notifAction(actionType) {
    let form = document.getElementById('notifForm');
    let formData = new FormData(form);
    
    // Ambil semua ID yang dicentang
    let params = new URLSearchParams();
    params.append('action', actionType);
    
    // Tambahkan checkbox yang dipilih ke parameter
    form.querySelectorAll('input[name="notif_ids[]"]:checked').forEach(cb => {
        params.append('ids[]', cb.value);
    });

    fetch('action_notif.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params.toString()
    })
    .then(res => res.text())
    .then(data => {
        if(data.trim() === 'success') {
            location.reload(); // Refresh halaman untuk melihat hasil
        } else {
            alert("Terjadi kesalahan: " + data);
        }
    })
    .catch(err => console.error("Error:", err));
}
    function toggleModal() {
    var modal = document.getElementById("notifModal");
    if (modal.style.display === "block") {
        modal.style.display = "none";
    } else {
        modal.style.display = "block";
    }
}

// Menutup modal jika user klik di luar kotak modal
window.onclick = function(event) {
    var modal = document.getElementById("notifModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>
