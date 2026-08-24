<?php
session_start();
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');


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

// Hitung semua notifikasi yang belum dibaca (Unread)
$q_unread = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM car_requests 
    WHERE is_read_notif = 0 
    AND is_deleted_notif = 0
");
$notif_count = mysqli_fetch_assoc($q_unread)['total'];

/* ==========================
   AMBIL DATA USER LOGIN
========================== */
$user_email = $_SESSION['email'];
// Ambil data user
$user_email = $_SESSION['email']; // sesuai session login
$query_user = mysqli_query($conn, "SELECT photo,username FROM users WHERE email = '$user_email'");
$user_data = mysqli_fetch_assoc($query_user);

$qUser = mysqli_query($conn, "
    SELECT id, email, role, division_id, photo 
    FROM users 
    WHERE email = '$user_email'
");

$dataUser = mysqli_fetch_assoc($qUser);

$login_user_id     = $dataUser['id'];
$login_role        = $dataUser['role'];
$login_division_id = $dataUser['division_id'];
$username          = $dataUser['email'];

$foto_profile = $dataUser['photo'] 
    ? "../uploads/profile/" . $dataUser['photo'] 
    : "../uploads/profile-default.png";

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
   FILTER DIVISI
========================== */

if ($login_role == 'admin') {
    $filterDivision = "";
} else {
    $filterDivision = "AND u.division_id = '$login_division_id'";
}

/* ==========================
   STATISTIK
========================== */

// Mobil sedang dipakai
$q_used = mysqli_query($conn, "
    SELECT COUNT(DISTINCT car_id) AS total
    FROM car_requests
    WHERE status = 'approved'
");
$mobil_dipakai = mysqli_fetch_assoc($q_used)['total'];

// Mobil sedang diservice
$q_mobil_service = mysqli_query($conn, "
    SELECT COUNT(*) AS total_mobil 
    FROM cars 
    WHERE active = 0
");
$mobil_diservice = mysqli_fetch_assoc($q_mobil_service)['total_mobil'];

// Total mobil
$q_mobil = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cars");
$total_mobil = mysqli_fetch_assoc($q_mobil)['total'];

$available = $total_mobil - $mobil_dipakai - $mobil_diservice;

// Pending sesuai divisi
$q_pending = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM car_requests cr
    JOIN users u ON cr.users_id = u.id
    WHERE cr.status = 'pending'
    $filterDivision
");
$pending = mysqli_fetch_assoc($q_pending)['total'];

// Total user
$q_user = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'pengguna'
");
$total_user = mysqli_fetch_assoc($q_user)['total'];

// Total user
$q_admin = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'admin'
");
$total_admin = mysqli_fetch_assoc($q_admin)['total'];

/* ==========================
   PENDING TERBARU SESUAI DIVISI
========================== */

$q_latest = mysqli_query($conn, "
    SELECT cr.id, u.username AS user_name, c.car_name, cr.start_datetime
    FROM car_requests cr
    JOIN cars c ON cr.car_id = c.id
    JOIN users u ON cr.users_id = u.id
    WHERE cr.status = 'pending'
    $filterDivision
    ORDER BY cr.created_at DESC
    LIMIT 5
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard_admin.css">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon_io/favicon-32x32.png">
<link rel="icon" href="../favicon_io/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php include "../layout/sidebar.php"; ?>

<div class="content">

<header class="topbar">
        <h1>Dashboard Admin</h1>
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
    <span><?= $greeting ?>, <?= htmlspecialchars($user_data['username']) ?></span>
</div>

    </header>

<section class="cards">

    <div class="card blue">
        <i class="fa-solid fa-car"></i>
        <div>
            <p>Mobil Tersedia</p>
            <h2><?= $available ?></h2>
        </div>
    </div>

    <div class="card red">
        <i class="fa-solid fa-route"></i>
        <div>
            <p>Sedang Dipakai</p>
            <h2><?= $mobil_dipakai ?></h2>
        </div>
    </div>

    <div class="card grey">
        <i class="fa-solid fa-tools"></i>
        <div>
            <p>Mobil di Service / Tidak Tersedia</p>
            <h2><?= $mobil_diservice ?></h2>
        </div>
    </div>

    <div class="card yellow">
        <i class="fa-solid fa-clock"></i>
        <div>
            <p>Menunggu Persetujuan</p>
            <h2><?= $pending ?></h2>
        </div>
    </div>

    <div class="card green">
        <i class="fa-solid fa-users"></i>
        <div>
            <p>Total User</p>
            <h2><?= $total_user ?></h2>
        </div>
    </div>

    <div class="card blue">
        <i class="fa-solid fa-user-shield"></i>
        <div>
            <p>Total Admin</p>
            <h2><?= $total_admin ?></h2>
        </div>
    </div>

</section>

<section class="box">
    <h3>Pengajuan Pending Terbaru</h3>

    <?php if (mysqli_num_rows($q_latest) == 0): ?>
        <p>Tidak ada pengajuan pending.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Mobil</th>
                    <th>Tanggal Mulai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($q_latest)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['car_name']) ?></td>
                        <td><?= date('d M Y H:i', strtotime($row['start_datetime'])) ?></td>
                        <td>
                            <a href="approval_detail.php?id=<?= $row['id'] ?>" class="btn-detail">
                                Detail
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
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