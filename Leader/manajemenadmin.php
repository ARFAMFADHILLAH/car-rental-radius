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

    // TAMBAH ADMIN
    if ($_POST['action'] == 'add') {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, $_POST['role']);

        mysqli_query($conn, "INSERT INTO users (email, password, role, created_at)
                             VALUES ('$email', '$password', '$role', NOW())");

        header("Location: manajemenadmin.php");
        exit;
    }

    // EDIT ADMIN
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

        header("Location: manajemenadmin.php");
        exit;
    }

    // HAPUS ADMIN
    if ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
        header("Location: manajemenadmin.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/manajemenadmin.css">
<link rel="icon" href="../favicon_io/favicon.ico">

<title>Manajemen Admin</title>
</head>
<body>

<?php include "../layout/sidebarleader.php"; ?>

<div class="content">
<main>

<div class="head-title">
    <h1>Daftar Admin</h1>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-plus"></i> Tambah Admin
    </button>
</div>

<table class="table">
<thead>
<tr>
    <th>No</th>
    <th>Email</th>
    <th>Role</th>
    <th>Dibuat</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>

<?php
$no = 1;
$admins = mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin' ORDER BY id DESC");
while ($admin = mysqli_fetch_assoc($admins)) {
?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($admin['email']) ?></td>
    <td><?= htmlspecialchars($admin['role']) ?></td>
    <td><?= date('d M Y', strtotime($admin['created_at'])) ?></td>
    <td>
        <div class="action-buttons">
            <button class="btn btn-warning btn-sm btn-edit"
    data-id="<?= $admin['id'] ?>"
    data-email="<?= htmlspecialchars($admin['email'], ENT_QUOTES) ?>"
    data-role="<?= $admin['role'] ?>">
    <i class="fas fa-edit"></i>
</button>

            <button class="btn btn-danger btn-sm"
                onclick="deleteAdmin(<?= $admin['id'] ?>)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </td>
</tr>
<?php } ?>

</tbody>
</table>


</main>
<!-- MODAL ADMIN -->
<div class="custom-modal" id="AdminModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>

<h2 id="modalTitle">Tambah Admin</h2>

<form method="POST">
<input type="hidden" name="action" id="formAction" value="add">
<input type="hidden" name="id" id="adminId">

<label>Email</label>
<input type="email" name="email" id="adminEmail" required>

<label id="passwordLabel">Password</label>
<input type="password" name="password" id="adminPassword">


<label>Role</label>
<select name="role" id="adminRole" required>
    <option value="admin">Admin</option>
</select>

<button type="submit" class="btn btn-primary" id="modalBtn">Simpan</button>
</form>
</div>
</div>

<?php include "../layout/footer.php"; ?>
</div>


<script>
const modal = document.getElementById('AdminModal');
const title = document.getElementById('modalTitle');
const action = document.getElementById('formAction');
const adminId = document.getElementById('adminId');
const email = document.getElementById('adminEmail');
const password = document.getElementById('adminPassword');
const role = document.getElementById('adminRole');

/* ================= TAMBAH ADMIN ================= */
function openAddModal() {
    title.innerText = "Tambah Admin";
    action.value = "add";
    adminId.value = "";
    email.value = "";
    password.value = "";
    password.required = true;
    role.value = "admin";

    modal.style.display = "flex";
    email.focus();
}

/* ================= EDIT ADMIN ================= */
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        title.innerText = "Edit Admin";
        action.value = "edit";
        adminId.value = this.dataset.id;
        email.value = this.dataset.email;
        role.value = this.dataset.role;

        password.value = "";
        password.required = false;

        modal.style.display = "flex";
        email.focus();
    });
});


function deleteAdmin(id) {
    if (confirm("Yakin ingin menghapus admin ini?")) {
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
    const modal = document.getElementById('AdminModal');
    modal.style.display = "none";
});
</script>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>



</body>
</html>
