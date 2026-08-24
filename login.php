<?php
session_start();
require 'functions.php';

// Kalau sudah login, redirect sesuai role
if (isset($_SESSION['login'])) {

    if ($_SESSION['role'] === 'admin') {

        header("Location: Admin/dashboard.php");
        exit;

    } elseif (
        isset($_SESSION['division_name']) &&
        strpos($_SESSION['division_name'], 'Leader') !== false
    ) {

        header("Location: Leader/dashboard.php");
        exit;

    } elseif ($_SESSION['role'] === 'pengguna') {

        header("Location: Pengguna/dashboard.php");
        exit;
    }
}


// PROSES LOGIN SAAT SUBMIT
if (isset($_POST['login'])) {

    if (login($_POST)) {

        if ($_SESSION['role'] === 'admin') {

            header("Location: Admin/dashboard.php");

        } elseif (
            isset($_SESSION['division_name']) &&
            strpos($_SESSION['division_name'], 'Leader') !== false
        ) {

            header("Location: Leader/dashboard.php");

        } else {

            header("Location: Pengguna/dashboard.php");
        }

        exit;

    } else {

        header("Location: login.php?error=login");
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
<link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png">
<link rel="icon" href="favicon_io/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-papom..." crossorigin="anonymous" referrerpolicy="no-referrer" />

    <meta charset="UTF-8">
    <title>Car Rental Radius - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="login-container">
    <div class="login-card">

        <div class="login-image">
            <h2>Car Rental Radius</h2>
            <p>Sistem Peminjaman Mobil Operasional</p>
        </div>

        <div class="login-form">
            <h1>Login</h1>

            <form method="POST">
    <div class="form-group">
        <input type="email" name="email" placeholder="Email" required>
    </div>

    <div class="form-group" style="position: relative;">
    <input type="password" id="password" name="password" placeholder="Password" required style="padding-right: 40px;">
    <!-- Ikon toggle -->
    <i id="togglePassword" class="fa-regular fa-eye" 
       style="position: absolute; right: 20px; top: 40%; transform: translateY(-50%); cursor: pointer; color: #555;"></i>
</div>

    <button type="submit" name="login" class="btn-login">Login</button>
</form>



            <div class="links">
                <a href="forgot_password.php">Forgot Password?</a>
                <!-- <a href="register.php">Belum punya akun? Daftar Akun</a> -->
            </div>
        </div>

    </div>
</div>

<script>
const passwordInput = document.getElementById('password');
const toggleIcon = document.getElementById('togglePassword');

toggleIcon.addEventListener('click', () => {
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
    // Ganti ikon
    toggleIcon.classList.toggle('fa-eye');
    toggleIcon.classList.toggle('fa-eye-slash');
});
</script>
</body>
</html>
