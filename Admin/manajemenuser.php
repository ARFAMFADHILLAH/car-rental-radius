<?php
require '../functions.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// Ambil daftar divisi
$divisions = mysqli_query($conn, "SELECT * FROM divisions ORDER BY name ASC");

/* ======================
   PROSES TAMBAH / EDIT / HAPUS
====================== */
if (isset($_POST['action'])) {

    // TAMBAH USER
    if ($_POST['action'] == 'add') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $divisi = mysqli_real_escape_string($conn, $_POST['divisi']);

        mysqli_query($conn, "INSERT INTO users (username, email, password, role, division_id, created_at)
                             VALUES ('$username', '$email', '$password', '$role', '$divisi', NOW())");

        header("Location: manajemenuser.php");
        exit;
    }

    // EDIT USER
    if ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $divisi = mysqli_real_escape_string($conn, $_POST['divisi']);

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET username='$username', email='$email', password='$password', role='$role', division_id='$divisi' WHERE id='$id'");
        } else {
            mysqli_query($conn, "UPDATE users SET username='$username', email='$email', role='$role', division_id='$divisi' WHERE id='$id'");
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/manajemenuser.css">
<link rel="icon" href="../favicon_io/favicon.ico">
<title>Manajemen User</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.custom-modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
.modal-content { background:#fff; padding:20px; border-radius:5px; width:400px; max-width:90%; position:relative; }
.close-btn { position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px; }
</style>
</head>
<body>

<?php include "../layout/sidebar.php"; ?>

<div class="content">
<main>

<div class="head-title">
    <h1>Daftar User</h1>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-plus"></i> Tambah User
    </button>
</div>

<table class="table">
<thead>
<tr>
    <th>No</th>
    <th>Username</th>
    <th>Role</th>
    <th>Divisi</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>

<?php
$no = 1;
$users = mysqli_query($conn, "
    SELECT u.*, d.name 
    FROM users u
    LEFT JOIN divisions d ON u.division_id = d.id
    WHERE u.role='pengguna'
    ORDER BY u.id DESC
");

while ($user = mysqli_fetch_assoc($users)) {
?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($user['username'] ?? '-') ?></td>
    <td><?= htmlspecialchars($user['role'] ?? '-') ?></td>
    <td><?= htmlspecialchars($user['name'] ?? '-') ?></td>
    <td>
        <div class="action-buttons">
            <button class="btn btn-info btn-sm btn-view"
                data-username="<?= htmlspecialchars($user['username'] ?? '-', ENT_QUOTES) ?>"
                data-email="<?= htmlspecialchars($user['email'] ?? '-', ENT_QUOTES) ?>"
                data-role="<?= htmlspecialchars($user['role'] ?? '-', ENT_QUOTES) ?>"
                data-divisi="<?= htmlspecialchars($user['name'] ?? '-', ENT_QUOTES) ?>"
                data-created="<?= date('d M Y', strtotime($user['created_at'] ?? date('Y-m-d'))) ?>">
                <i class="fas fa-eye"></i>
            </button>

            <button class="btn btn-warning btn-sm btn-edit"
                data-id="<?= $user['id'] ?>"
                data-username="<?= htmlspecialchars($user['username'] ?? '-', ENT_QUOTES) ?>"
                data-email="<?= htmlspecialchars($user['email'] ?? '-', ENT_QUOTES) ?>"
                data-role="<?= htmlspecialchars($user['role'] ?? '-', ENT_QUOTES) ?>"
                data-divisi_id="<?= $user['division_id'] ?>">
                <i class="fas fa-edit"></i>
            </button>

            <button class="btn btn-danger btn-sm" onclick="deleteUser(<?= $user['id'] ?>)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </td>
</tr>
<?php } ?>

</tbody>
</table>

</main>

<!-- MODAL USER (Add/Edit) -->
<div class="custom-modal" id="userModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>

<h2 id="modalTitle">Tambah User</h2>

<form method="POST" id="userForm">
<input type="hidden" name="action" id="formAction" value="add">
<input type="hidden" name="id" id="userId">

<label>Username</label>
<input type="text" name="username" id="userName" required>

<label>Email</label>
<input type="email" name="email" id="userEmail" required>

<label id="passwordLabel">Password</label>
<input type="password" name="password" id="userPassword">

<label>Role</label>
<select name="role" id="userRole" required>
    <option value="pengguna">Pengguna</option>
</select>

<label>Divisi</label>
<select name="divisi" id="userDivisi" required>
    <option value="">-- Pilih Divisi --</option>
    <?php
    // Reset pointer karena query $divisions sudah dipakai di atas
    mysqli_data_seek($divisions, 0);
    while($d = mysqli_fetch_assoc($divisions)): ?>
        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
    <?php endwhile; ?>
</select>

<button type="submit" class="btn btn-primary" id="modalBtn">Simpan</button>
</form>
</div>
</div>

<!-- MODAL VIEW -->
<div class="custom-modal" id="viewModal">
<div class="modal-content">
<span class="close-btn" onclick="closeViewModal()">&times;</span>
<h2>Detail User</h2>
<p><strong>Username:</strong> <span id="viewUsername"></span></p>
<p><strong>Email:</strong> <span id="viewEmail"></span></p>
<p><strong>Role:</strong> <span id="viewRole"></span></p>
<p><strong>Divisi:</strong> <span id="viewDivisi"></span></p>
<p><strong>Dibuat:</strong> <span id="viewCreated"></span></p>
</div>
</div>

<?php include "../layout/footer.php"; ?>
</div>

<script>
const modal = document.getElementById('userModal');
const viewModal = document.getElementById('viewModal');
const title = document.getElementById('modalTitle');
const action = document.getElementById('formAction');
const userId = document.getElementById('userId');
const username = document.getElementById('userName');
const email = document.getElementById('userEmail');
const password = document.getElementById('userPassword');
const role = document.getElementById('userRole');
const divisi = document.getElementById('userDivisi');

/* ================= TAMBAH USER ================= */
function openAddModal() {
    title.innerText = "Tambah User";
    action.value = "add";
    userId.value = "";
    username.value = "";
    email.value = "";
    password.value = "";
    password.required = true;
    role.value = "pengguna";
    divisi.value = "";

    modal.style.display = "flex";
    username.focus();
}

/* ================= EDIT USER ================= */
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        title.innerText = "Edit User";
        action.value = "edit";
        userId.value = this.dataset.id;
        username.value = this.dataset.username;
        email.value = this.dataset.email;
        role.value = this.dataset.role;
        divisi.value = this.dataset.divisi_id;

        password.value = "";
        password.required = false;

        modal.style.display = "flex";
        username.focus();
    });
});

/* ================= VIEW USER ================= */
document.querySelectorAll('.btn-view').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('viewUsername').innerText = this.dataset.username;
        document.getElementById('viewEmail').innerText = this.dataset.email;
        document.getElementById('viewRole').innerText = this.dataset.role;
        document.getElementById('viewDivisi').innerText = this.dataset.divisi;
        document.getElementById('viewCreated').innerText = this.dataset.created;

        viewModal.style.display = "flex";
    });
});

function closeViewModal() {
    viewModal.style.display = "none";
}

/* ================= HAPUS USER ================= */
function deleteUser(id) {
    if (confirm("Yakin ingin menghapus user ini?")) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

/* ================= CLOSE MODAL ================= */
function closeModal() {
    modal.style.display = "none";
}

window.onclick = function(e) {
    if (e.target === modal) closeModal();
    if (e.target === viewModal) closeViewModal();
}

document.addEventListener("DOMContentLoaded", function () {
    modal.style.display = "none";
    viewModal.style.display = "none";
});
</script>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

</body>
</html>
