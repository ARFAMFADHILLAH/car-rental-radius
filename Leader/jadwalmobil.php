<?php
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// 1. QUERY UNTUK FULLCALENDAR (Menggunakan tabel car_requests)
$peminjaman_query = mysqli_query($conn, "SELECT cr.*, c.car_name ,cr.start_datetime, cr.end_datetime
                                        FROM car_requests cr
                                        JOIN cars c ON cr.car_id = c.id 
                                        WHERE cr.status != 'rejected'"); // Hanya tampilkan yang tidak ditolak

$events = [];
while($row = mysqli_fetch_assoc($peminjaman_query)) {
    $events[] = [
        'title' => $row['car_name'] . " - " . $row['user_name'],
        'start' => $row['start_datetime'],
        'end'   => $row['end_datetime'],
        // Warna berdasarkan status
        'color' => ($row['status'] == 'approved') ? '#27ae60' : (($row['status'] == 'returned') ? '#95a5a6' : '#ffe600ff')
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Mobil</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/jadwalmobil.css">
    <link rel="icon" href="../favicon_io/favicon.ico">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.1.0/css/all.min.css" />
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

    <style>
        .table { margin-bottom:40px; width: 100%; }
        h2 { margin:30px 0 10px; display: flex; align-items: center; gap: 10px; }
        img { border-radius:6px; }
        
        /* Styling tambahan untuk Monitoring */
        #calendar { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 40px; }
        .monitoring-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .card-box { background: #fff; padding: 20px; border-radius: 8px; border-left: 5px solid #2ecc71; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card-box h3 { margin: 5px 0; color: #333; }
        .card-box small { color: #888; text-transform: uppercase; font-weight: bold; }
    </style>
</head>

<body>

<?php include "../layout/sidebarleader.php"; ?>

<div class="content">
<main>

<h1>Jadwal Mobil</h1>

<h2><i class="fas fa-calendar-check"></i> Jadwal Peminjaman Mobil</h2>
<b>Note : Status warna kuning = Menunggu Persetujuan, Warna Hijau = Disetujui</b>
<div id='calendar'></div>

</main>

<footer>
    &copy; 2026 Car Rental Radius
</footer>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'timeGridDay',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'timeGridDay,dayGridMonth,timeGridWeek'
      },
      locale: 'id',
      events: <?php echo json_encode($events); ?>, // Mengirim data PHP ke FullCalendar
      eventClick: function(info) {

  let start = info.event.start;
  let end   = info.event.end;

  // Format tanggal ke Indonesia
  let options = { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
  };

  let startFormatted = start.toLocaleString('id-ID', options);
  let endFormatted   = end.toLocaleString('id-ID', options);

  alert(
      "📌 Info Peminjaman\n\n" +
      "Mobil Yang Di Pinjam : " + info.event.title.split(" - ")[0] + "\n" +
      "Nama Peminjam : " + info.event.title.split(" - ")[1] + "\n" +
      "Dari : " + startFormatted + "\n" +
      "Sampai : " + endFormatted
  );
}
    });
    calendar.render();
  });
</script>

</body>
</html>