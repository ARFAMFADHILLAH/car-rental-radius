<?php
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

/* ======================
   PROSES TAMBAH / EDIT / HAPUS
====================== */
if (isset($_POST['action'])) {

    // TAMBAH USER
    if ($_POST['action'] == 'add') {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, $_POST['role']);

        mysqli_query($conn, "INSERT INTO users (email, password, role, created_at)
                             VALUES ('$email', '$password', '$role', NOW())");

        header("Location: manajemenuser.php");
        exit;
    }

    // EDIT USER
    if ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET email='$email', password='$password', role='$role' WHERE id='$id'");
        } else {
            mysqli_query($conn, "UPDATE users SET email='$email', role='$role' WHERE id='$id'");
        }

        header("Location: manajemenuser.php");
        exit;
    }

    // HAPUS USER
    if ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
        header("Location: manajemenuser.php");
        exit;
    }
}

/* ======================
   LOGIKA TAMPIL DATA
====================== */

$no = 1;
$users = null;

$divisionLogin = $_SESSION['division_id'] ?? null;

if ($divisionLogin) {

    $getDivision = mysqli_query($conn, "SELECT name FROM divisions WHERE id = '$divisionLogin'");
    $divisionData = mysqli_fetch_assoc($getDivision);
    $divisionName = $divisionData['name'] ?? null; // gunakan null jika tidak ada

    // pastikan $divisionName ada sebelum diproses
    if ($divisionName && strpos($divisionName, 'Leader ') === 0) {

        // Hapus kata "Leader "
        $anggotaDivisi = str_replace('Leader ', '', $divisionName);

        // Pisah divisi jika ada "&" atau koma
        $divisiArray = array_map('trim', preg_split('/[,&]/', $anggotaDivisi));

        // Ambil semua ID divisi
        $ids = [];
        foreach ($divisiArray as $div) {
            $getDiv = mysqli_query($conn, "SELECT id FROM divisions WHERE name = '$div'");
            $divData = mysqli_fetch_assoc($getDiv);
            if ($divData) {
                $ids[] = $divData['id'];
            }
        }

        // Jika ada ID divisi, ambil user yang termasuk divisi-divisi tersebut
        if (!empty($ids)) {
            $idsStr = implode(',', $ids);
            $users = mysqli_query($conn, "
                SELECT users.*, divisions.name AS division_name
                FROM users
                LEFT JOIN divisions ON users.division_id = divisions.id
                WHERE users.division_id IN ($idsStr)
                ORDER BY users.id DESC
            ");
        } else {
            $users = mysqli_query($conn, "SELECT * FROM users WHERE 1=0"); // tidak ada user
        }

    } else {
        // Jika bukan leader, tampil semua user
        $users = mysqli_query($conn, "
            SELECT users.*, divisions.name AS division_name
            FROM users
            LEFT JOIN divisions ON users.division_id = divisions.id
            ORDER BY users.id DESC
        ");
    }

} else {
    // jika tidak login divisi
    $users = mysqli_query($conn, "SELECT * FROM users WHERE 1=0");
}

// Jika query gagal
if (!$users) {
    $users = mysqli_query($conn, "SELECT * FROM users WHERE 1=0");
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/manajemenuser.css">
<link rel="icon" href="../favicon_io/favicon.ico">

<title>Manajemen Anggota</title>
</head>
<body>

<?php include "../layout/sidebarleader.php"; ?>

<div class="content">
<main>

<div class="head-title">
    <h1>Daftar Anggota</h1>
    <!-- <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-plus"></i> Tambah User
    </button> -->
</div>

<table class="table">
<thead>
<tr>
    <th>No</th>
    <th>Username</th>
    <th>Email</th>
    <th>Divisi</th>
    <th>Dibuat</th>
    <!-- <th>Aksi</th> -->
</tr>
</thead>
<tbody>

<?php while ($user = mysqli_fetch_assoc($users)) { ?>

<tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($user['username'] ?? '') ?></td>
    <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
    <td><?= htmlspecialchars($user['division_name'] ?? '-') ?></td>
    <td>
        <?= !empty($user['created_at']) 
            ? date('d M Y', strtotime($user['created_at'])) 
            : '-' ?>
    </td>
    <!-- <td>
        <div class="action-buttons">
            <button class="btn btn-warning btn-sm btn-edit"
                data-id="<?= $user['id'] ?>"
                data-email="<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>"
                data-role="<?= $user['role'] ?>">
                <i class="fas fa-edit"></i>
            </button>

            <button class="btn btn-danger btn-sm"
                onclick="deleteUser(<?= $user['id'] ?>)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </td> -->
</tr>

<?php } ?>

</tbody>

</table>


</main>
<!-- MODAL USER -->
<!-- <div class="custom-modal" id="userModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>

<h2 id="modalTitle">Tambah User</h2>

<form method="POST">
<input type="hidden" name="action" id="formAction" value="add">
<input type="hidden" name="id" id="userId">

<label>Email</label>
<input type="email" name="email" id="userEmail" required>

<label id="passwordLabel">Password</label>
<input type="password" name="password" id="userPassword">


<label>Role</label>
<select name="role" id="userRole" required>
    <option value="pengguna">Pengguna</option>
</select>

<button type="submit" class="btn btn-primary" id="modalBtn">Simpan</button>
</form>
</div>
</div> -->

<?php include "../layout/footer.php"; ?>
</div>


<script>
const modal = document.getElementById('userModal');
const title = document.getElementById('modalTitle');
const action = document.getElementById('formAction');
const userId = document.getElementById('userId');
const email = document.getElementById('userEmail');
const password = document.getElementById('userPassword');
const role = document.getElementById('userRole');

/* ================= TAMBAH USER ================= */
function openAddModal() {
    title.innerText = "Tambah User";
    action.value = "add";
    userId.value = "";
    email.value = "";
    password.value = "";
    password.required = true;
    role.value = "pengguna";

    modal.style.display = "flex";
    email.focus();
}

/* ================= EDIT USER ================= */
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        title.innerText = "Edit User";
        action.value = "edit";
        userId.value = this.dataset.id;
        email.value = this.dataset.email;
        role.value = this.dataset.role;

        password.value = "";
        password.required = false;

        modal.style.display = "flex";
        email.focus();
    });
});


function deleteUser(id) {
    if (confirm("Yakin ingin menghapus user ini?")) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}



/* ================= CLOSE ================= */
function closeModal() {
    modal.style.display = "none";
}

window.onclick = function(e) {
    if (e.target === modal) closeModal();
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById('userModal');
    modal.style.display = "none";
});
</script>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>



</body>
</html>
