<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'functions.php'; // koneksi $conn
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // cek email di table users
    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($result) > 0) {
        // hapus token lama
        mysqli_query($conn, "DELETE FROM password_resets WHERE email='$email'");

        // buat token baru
        $token   = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        mysqli_query($conn, "INSERT INTO password_resets (email, token, expires_at)
                             VALUES ('$email', '$token', '$expires')");

        $resetLink = "http://localhost/car-rental-radius/reset_password.php?token=$token";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.zoho.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'car.rental.test@zohomail.com'; // Ganti dengan akun Zoho kamu
            $mail->Password   = 'radius.co.id12';          // Ganti dengan password Zoho
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // pengirim harus sama dengan username SMTP
            $mail->setFrom('car.rental.test@zohomail.com', 'Car Rental Radius');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Akun Anda';
            $mail->Body    = "
                <h3>Reset Password</h3>
                <p>Klik tombol di bawah untuk reset password:</p>
                <p>
                    <a href='$resetLink' 
                       style='padding:10px 15px;background:#007bff;color:#fff;text-decoration:none;border-radius:5px;'>
                       Reset Password
                    </a>
                </p>
                <p><b>Link berlaku 1 jam</b></p>
            ";

            $mail->send();

            echo "<script>
                alert('Link reset password sudah dikirim ke email');
                window.location='login.php';
            </script>";

        } catch (Exception $e) {
            echo "Email gagal dikirim: {$mail->ErrorInfo}";
        }

    } else {
        echo "<script>alert('Email tidak terdaftar');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
</head>
<body>

<h2>Forgot Password</h2>
<form method="POST">
    <input type="email" name="email" placeholder="Masukkan email" required>
    <button type="submit" name="submit">Kirim Link</button>
</form>

</body>
</html>
