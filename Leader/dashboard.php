<?php
session_start();
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$user_email = $_SESSION['email'];

date_default_timezone_set('Asia/Jakarta');

/* ==========================
   SAPAAN
========================== */
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

/* ==========================
   STATISTIK DASHBOARD
========================== */

// 1. Data untuk Diagram Batang (Peminjaman per Divisi - Top 5)
$q_bar = mysqli_query($conn, "
    SELECT division, COUNT(*) as total 
    FROM car_requests 
    WHERE status='approved' 
    GROUP BY division 
    ORDER BY total DESC LIMIT 5
");
$bar_labels = [];
$bar_data = [];
while($row = mysqli_fetch_assoc($q_bar)){
    $bar_labels[] = $row['division'];
    $bar_data[] = $row['total'];
}

// 2. Data untuk Diagram Garis (Tren Peminjaman 7 Hari Terakhir)
$q_line = mysqli_query($conn, "
    SELECT DATE(created_at) as tgl, COUNT(*) as total 
    FROM car_requests 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY tgl ASC
");
$line_labels = [];
$line_data = [];
while($row = mysqli_fetch_assoc($q_line)){
    $line_labels[] = date('d M', strtotime($row['tgl']));
    $line_data[] = $row['total'];
}

// Mobil sedang dipakai
$q_used = mysqli_query($conn, "
    SELECT COUNT(DISTINCT car_id) AS total
    FROM car_requests
    WHERE status = 'approved'
");
$mobil_dipakai = mysqli_fetch_assoc($q_used)['total'];

// Mobil sedang diservice
$q_mobil_service = mysqli_query($conn, "SELECT COUNT(*) AS total_mobil FROM cars WHERE active = 0");
$mobil_diservice = mysqli_fetch_assoc($q_mobil_service)['total_mobil'];

// Total mobil
$q_mobil = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cars");
$total_mobil = mysqli_fetch_assoc($q_mobil)['total'];
$available = $total_mobil - $mobil_dipakai;
$available = $available - $mobil_diservice;


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

// Pending terbaru
$q_latest = mysqli_query($conn, "
    SELECT cr.id, u.username AS user_name, c.car_name, cr.created_at
    FROM car_requests cr
    JOIN cars c ON cr.car_id = c.id
    JOIN users u ON cr.users_id = u.id
    WHERE cr.status = 'Menunggu Persetujuan Leader'
    ORDER BY cr.created_at DESC
    LIMIT 5
");

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

// Ambil data user
$user_email = $_SESSION['email']; // sesuai session login
$query_user = mysqli_query($conn, "SELECT photo,username FROM users WHERE email = '$user_email'");
$user_data = mysqli_fetch_assoc($query_user);

$username1 = $user_data['username'];
$foto_profile = $user_data['photo'] 
    ? "../uploads/profile/" . $user_data['photo'] 
    : "../uploads/profile-default.png";
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon_io/favicon-32x32.png">
<link rel="icon" href="../favicon_io/favicon.ico" type="image/x-icon">

    <title>Dashboard Leader</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard_leader.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<body>

<?php include "../layout/sidebarleader.php"; ?>

<div class="content">

<div>

</div>

    <!-- TOPBAR -->
    <header class="topbar">
        <h1>Dashboard Leader</h1>
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
    <span><?= $greeting ?>, <?= htmlspecialchars($username1) ?></span>
</div>

    </header>

    <!-- STAT CARD -->
<section class="cards">
    <div class="card blue">
        <i class="fa-solid fa-car"></i>
        <div><p>Total Mobil</p><h2><?= $available ?></h2></div>
    </div>
    <div class="card red">
        <i class="fa-solid fa-route"></i>
        <div><p>Mobil Yang Dipakai</p><h2><?= $mobil_dipakai ?></h2></div>
    </div>
    <div class="card grey">
        <i class="fa-solid fa-tools"></i>
        <div><p>Mobil Sedang Service</p><h2><?= $mobil_diservice ?></h2></div>
    </div>
    <div class="card yellow">
        <i class="fa-solid fa-clock"></i>
        <div><p>Menunggu Persetujuan</p><h2><?= $pending ?></h2></div>
    </div>
</section>

<section class="charts-grid">
<div class="box-chart">
    <h3>Tren Peminjaman (7 Hari Terakhir)</h3>
    <div class="canvas-container">
        <canvas id="lineChart"></canvas>
    </div>
</div>

<br>

    <div class="box-chart">
        <h3>Top 5 Divisi Peminjam</h3>
        <div class="canvas-container">
            <canvas id="barChart"></canvas>
        </div>
    </div>
</section>


</div>

<?php include "../layout/footer.php"; ?>

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
                            <?php elseif ($row['status'] == 'Canceled') : ?>
    <i class="fa-solid fa-circle-xmark" style="color: #e74c3c;"></i>
    <span><strong><?= $row['user_name'] ?></strong> Membatalkan Peminjaman Mobil <strong><?= $row['mobil'] ?></strong></span>
                            <?php elseif ($row['status'] == 'rejected') : ?>
                                <i class="fa-solid fa-circle-xmark" style="color: #e74c3c;"></i>
                                <span><strong><?= $row['approved_by'] ?></strong> Menolak Peminjaman Mobil <strong><?= $row['mobil'] ?></strong> Dari <strong><?= $row['user_name'] ?></strong></span>
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

// Konfigurasi Diagram Batang
const ctxBar = document.getElementById('barChart').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: <?= json_encode($bar_labels) ?>,
        datasets: [{
            label: 'Total Peminjaman',
            data: <?= json_encode($bar_data) ?>,
            backgroundColor: '#3498db',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// Konfigurasi Diagram Garis
const ctxLine = document.getElementById('lineChart').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: <?= json_encode($line_labels) ?>,
        datasets: [{
            label: 'Jumlah Pengajuan',
            data: <?= json_encode($line_data) ?>,
            borderColor: '#e74c3c',
            backgroundColor: 'rgba(231, 76, 60, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});


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
