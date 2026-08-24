<?php
session_start();
require '../functions.php';

if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit;
}

$email = $_SESSION['email'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT u.*, d.name AS division
    FROM users u
    LEFT JOIN divisions d ON u.division_id = d.id
    WHERE u.email = '$email'
"));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajukan Peminjaman</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon_io/favicon-32x32.png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/ajukan_peminjaman.css">
</head>
<body>

<div class="wrapper">
    <?php include '../layout/sidebaruser.php'; ?>

    <div class="content">
        <h2 style="margin-left: 30px; padding-top: 20px; margin-bottom: 10px;">Ajukan Peminjaman Mobil</h2>

        <div class="form-box">
            <p class="info">
                Silakan pilih tanggal terlebih dahulu agar sistem bisa menampilkan mobil yang tersedia pada jam & tanggal peminjaman tersebut.
            </p>

            <form action="ajukan_peminjaman_proses.php" method="POST">

                <label>Nama</label>
                <input type="text" name="user_name" value="<?= $user['username'] ?>" readonly>

                <label>Email</label>
                <input type="text" name="email" value="<?= $user['email'] ?>" readonly>

                <label>Divisi</label>
                <input type="text" name="division" value="<?= $user['division'] ?>" readonly>

                <label>No HP</label>
                <input type="text" name="phone" value="<?= $user['no_telp'] ?>" readonly>

                <label>Tanggal & Jam Peminjaman (Dari)</label>
                <input type="datetime-local" name="start_datetime" id="start_datetime" required>

                <label>Tanggal & Jam Peminjaman (Sampai)</label>
                <input type="datetime-local" name="end_datetime" id="end_datetime" required>

                <label>Mobil</label>
                <select name="car_id" id="car_select" required>
                    <option value="">-- Pilih Tanggal Peminjaman Terlebih Dahulu --</option>
                </select>

                <label>Kegiatan</label>
                <input type="text" name="purpose" required placeholder="Contoh: Meeting Proyek">

                <label>Lokasi Tujuan</label>
                <textarea name="destination" placeholder="Alamat lengkap tujuan"></textarea>

                <label>Nama Customer</label>
                <input type="text" name="customer_name" placeholder="Nama customer yang dikunjungi">

                <label>Catatan</label>
                <textarea name="notes" placeholder="Catatan tambahan jika ada"></textarea>

                <button class="btn-submit" type="submit">Ajukan Peminjaman</button>
            </form>
        </div>
    </div>

    <footer>
      &copy; 2026 Car Rental Radius
    </footer>
</div>

<script>
// Ambil elemen input
const startInput = document.getElementById('start_datetime');
const endInput = document.getElementById('end_datetime');
const carSelect = document.getElementById('car_select');

function updateAvailableCars() {
    const startValue = startInput.value;
    const endValue = endInput.value;

    // Pastikan kedua tanggal sudah diisi sebelum cek ke database
    if (startValue && endValue) {
        carSelect.innerHTML = '<option value="">Mengecek ketersediaan mobil...</option>';

        // Memanggil file get_available_cars.php secara background (AJAX)
        fetch(`get_available_cars.php?start=${startValue}&end=${endValue}`)
            .then(response => response.json())
            .then(data => {
                carSelect.innerHTML = '<option value="">-- Pilih Mobil --</option>';
                
                if (data.length > 0) {
                    data.forEach(car => {
                        const option = document.createElement('option');
                        option.value = car.id;
                        option.text = `${car.car_name} (${car.plate_number})`;
                        carSelect.appendChild(option);
                    });
                } else {
                    carSelect.innerHTML = '<option value="">Tidak ada mobil tersedia di jam ini</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                carSelect.innerHTML = '<option value="">Gagal mengambil data mobil</option>';
            });
    }
}

// Jalankan fungsi setiap kali input tanggal berubah
startInput.addEventListener('change', updateAvailableCars);
endInput.addEventListener('change', updateAvailableCars);
</script>

</body>
</html>