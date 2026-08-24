<?php
require 'functions.php';

// ======================
// CEK TOKEN
// ======================
if (!isset($_GET['token'])) {
    die("Token tidak ditemukan");
}

$token = mysqli_real_escape_string($conn, $_GET['token']);

// ambil data token
$query = mysqli_query($conn, "
    SELECT * FROM password_resets 
    WHERE token = '$token'
");

if (mysqli_num_rows($query) === 0) {
    die("Token tidak valid");
}

$data = mysqli_fetch_assoc($query);

// cek expired
if (strtotime($data['expires_at']) < time()) {
    mysqli_query($conn, "DELETE FROM password_resets WHERE token='$token'");
    die("Token sudah kedaluwarsa");
}

// ======================
// PROSES RESET PASSWORD
// ======================
if (isset($_POST['reset'])) {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if ($password !== $confirm) {
        $error = "Password tidak sama";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter";
    } else {
        $email = $data['email'];
        $hash  = password_hash($password, PASSWORD_DEFAULT);

        // update password user
        mysqli_query($conn, "
            UPDATE users 
            SET password='$hash' 
            WHERE email='$email'
        ");

        // hapus token
        mysqli_query($conn, "
            DELETE FROM password_resets 
            WHERE email='$email'
        ");

        echo "<script>
            alert('Password berhasil direset');
            window.location='login.php';
        </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body>

<h2>Reset Password</h2>

<?php if (isset($error)) : ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST">
    <input type="password" name="password" placeholder="Password baru" required><br><br>
    <input type="password" name="confirm" placeholder="Ulangi password" required><br><br>
    <button type="submit" name="reset">Reset Password</button>
</form>

</body>
</html>
