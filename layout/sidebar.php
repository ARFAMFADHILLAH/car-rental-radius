<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['email'];

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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800" rel="stylesheet">

  <!-- Font Awesome -->
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.1.0/css/all.min.css" />

 <link rel="stylesheet" href="../css/style.css">

</head>
<body>

<div id="wrapper">

<button class="hamburger" id="hamburger">
    <i class="fas fa-bars"></i>
</button>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-car"></i>
        <span>Car Rental Radius</span>
    </div>

    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="jadwalmobil.php"><i class="fa-solid fa-calendar"></i> Jadwal Mobil</a>
    <a href="datamobil.php"><i class="fas fa-car"></i> Data Mobil</a>
    <a href="approval_list.php"><i class="fas fa-circle-check"></i> Approval Peminjaman</a>
    <a href="ajukan_peminjaman.php"><i class="fas fa-key"></i> Ajukan Peminjaman</a>
    <a href="daftar_pengajuan.php"><i class="fas fa-file-invoice"></i> Daftar Pengajuan Saya</a>
    <!-- <a href="approval_pengembalian.php"><i class="fas fa-car-tunnel"></i> Approval Pengembalian</a> -->
    <a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a>
    <a href="manajemenadmin.php"><i class="fa-solid fa-user-plus"></i> Manajemen Admin</a>
    <a href="manajemenuser.php"><i class="fas fa-users"></i> Manajemen User</a>
    <a href="profil.php"><i class="fas fa-user"></i> Profil</a>
    <a href="tentang.php"><i class="fa-solid fa-circle-info"></i> Tentang</a>
    <a href="../logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
</aside>
<script>
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');

    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
</script>


</body>
</html>

