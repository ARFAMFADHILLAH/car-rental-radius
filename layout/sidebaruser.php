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

    <a href="../Pengguna/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="../Pengguna/jadwalmobil.php"><i class="fa-solid fa-calendar"></i> Jadwal Mobil</a>
    <a href="../Pengguna/ketersediaan_mobil.php"><i class="fas fa-car"></i> Ketersediaan Mobil</a>
    <a href="../Pengguna/ajukan_peminjaman.php"><i class="fas fa-key"></i> Ajukan Peminjaman</a>
    <a href="../Pengguna/daftar_pengajuan.php"><i class="fas fa-file-invoice"></i> Daftar Pengajuan Saya</a>
    <!-- <a href="../Pengguna/ajukan_pengembalian.php"><i class="fas fa-car-tunnel"></i> Ajukan Pengembalian</a> -->
    <a href="../Pengguna/profil.php"><i class="fas fa-user"></i> Profil</a>
    <a href="../Pengguna/tentang.php"><i class="fa-solid fa-circle-info"></i> Tentang</a>
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