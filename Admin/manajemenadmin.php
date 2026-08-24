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
        $username     = mysqli_real_escape_string($conn, $_POST['username']);
        $email        = mysqli_real_escape_string($conn, $_POST['email']);
        $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $division_id  = mysqli_real_escape_string($conn, $_POST['division_id']);
        $role         = 'admin';

        mysqli_query($conn, "INSERT INTO users (username, email, password, role, division_id, created_at)
                             VALUES ('$username', '$email', '$password', '$role', '$division_id', NOW())");

        header("Location: manajemenadmin.php");
        exit;
    }

    // EDIT ADMIN
    if ($_POST['action'] == 'edit') {
        $id           = $_POST['id'];
        $username     = mysqli_real_escape_string($conn, $_POST['username']);
        $email        = mysqli_real_escape_string($conn, $_POST['email']);
        $division_id  = mysqli_real_escape_string($conn, $_POST['division_id']);

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users 
                                 SET username='$username',
                                     email='$email',
                                     password='$password',
                                     division_id='$division_id'
                                 WHERE id='$id'");
        } else {
            mysqli_query($conn, "UPDATE users 
                                 SET username='$username',
                                     email='$email',
                                     division_id='$division_id'
                                 WHERE id='$id'");
        }

        header("Location: manajemenadmin.php");
        exit;
    }

    // HAPUS
    if ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
        header("Location: manajemenadmin.php");
        exit;
    }
}

// Ambil data divisi
$divisions = mysqli_query($conn, "SELECT * FROM divisions ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/manajemenadmin.css">
<link rel="icon" href="../favicon_io/favicon.ico">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<title>Manajemen Admin</title>

<style>
.custom-modal {
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.5);
    justify-content:center;
    align-items:center;
}
.modal-content {
    background:#fff;
    padding:20px;
    border-radius:6px;
    width:400px;
    max-width:95%;
    position:relative;
}
.close-btn {
    position:absolute;
    top:10px;
    right:15px;
    cursor:pointer;
    font-size:20px;
}
</style>
</head>
<body>

<?php include "../layout/sidebar.php"; ?>

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
    <th>Username</th>
    <th>Divisi</th>
    <th>Dibuat</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>

<?php
$no = 1;
$admins = mysqli_query($conn, "
    SELECT u.*, d.name AS division_name
    FROM users u
    LEFT JOIN divisions d ON u.division_id = d.id
    WHERE u.role='admin'
    ORDER BY u.id DESC
");

while ($admin = mysqli_fetch_assoc($admins)) {
?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($admin['username'] ?? '-') ?></td>
    <td><?= htmlspecialchars($admin['division_name'] ?? '-') ?></td>
    <td><?= date('d M Y', strtotime($admin['created_at'])) ?></td>
    <td>

        <!-- DETAIL -->
        <button class="btn btn-info btn-sm btn-view"
            data-username="<?= htmlspecialchars($admin['username'], ENT_QUOTES) ?>"
            data-email="<?= htmlspecialchars($admin['email'], ENT_QUOTES) ?>"
            data-division="<?= $admin['division_id'] ?>"
            data-divisiname="<?= htmlspecialchars($admin['division_name'] ?? '-', ENT_QUOTES) ?>"
            data-created="<?= date('d M Y', strtotime($admin['created_at'])) ?>">
            <i class="fas fa-eye"></i>
        </button>

        <!-- EDIT -->
        <button class="btn btn-warning btn-sm btn-edit"
            data-id="<?= $admin['id'] ?>"
            data-username="<?= htmlspecialchars($admin['username'], ENT_QUOTES) ?>"
            data-email="<?= htmlspecialchars($admin['email'], ENT_QUOTES) ?>"
            data-division="<?= $admin['division_id'] ?>">
            <i class="fas fa-edit"></i>
        </button>

        <!-- DELETE -->
        <button class="btn btn-danger btn-sm"
            onclick="deleteAdmin(<?= $admin['id'] ?>)">
            <i class="fas fa-trash"></i>
        </button>

    </td>
</tr>
<?php } ?>

</tbody>
</table>

</main>

<!-- MODAL TAMBAH / EDIT -->
<div class="custom-modal" id="adminModal">
<div class="modal-content">
<span class="close-btn" onclick="closeModal()">&times;</span>

<h2 id="modalTitle">Tambah Admin</h2>

<form method="POST">
<input type="hidden" name="action" id="formAction" value="add">
<input type="hidden" name="id" id="adminId">

<label>Username</label>
<input type="text" name="username" id="adminUsername" required>

<label>Email</label>
<input type="email" name="email" id="adminEmail" required>

<label>Password</label>
<input type="password" name="password" id="adminPassword">

<label>Divisi</label>
<select name="division_id" id="adminDivision" required>
<option value="">-- Pilih Divisi --</option>
<?php
mysqli_data_seek($divisions, 0);
while($d = mysqli_fetch_assoc($divisions)):
?>
<option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
<?php endwhile; ?>
</select>

<br><br>
<button type="submit" class="btn btn-primary">Simpan</button>
</form>
</div>
</div>

<!-- MODAL DETAIL -->
<div class="custom-modal" id="viewModal">
<div class="modal-content">
<span class="close-btn" onclick="closeViewModal()">&times;</span>

<h2>Detail Admin</h2>
<p><strong>Username:</strong> <span id="viewUsername"></span></p>
<p><strong>Email:</strong> <span id="viewEmail"></span></p>
<p><strong>Divisi:</strong> <span id="viewDivisi"></span></p>
<p><strong>Dibuat:</strong> <span id="viewCreated"></span></p>

</div>
</div>

<?php include "../layout/footer.php"; ?>
</div>

<script>
const modal = document.getElementById('adminModal');
const viewModal = document.getElementById('viewModal');

function openAddModal() {
    document.getElementById('modalTitle').innerText = "Tambah Admin";
    document.getElementById('formAction').value = "add";
    document.getElementById('adminId').value = "";
    document.getElementById('adminUsername').value = "";
    document.getElementById('adminEmail').value = "";
    document.getElementById('adminPassword').value = "";
    document.getElementById('adminPassword').required = true;
    document.getElementById('adminDivision').value = "";
    modal.style.display = "flex";
}

document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('modalTitle').innerText = "Edit Admin";
        document.getElementById('formAction').value = "edit";
        document.getElementById('adminId').value = this.dataset.id;
        document.getElementById('adminUsername').value = this.dataset.username;
        document.getElementById('adminEmail').value = this.dataset.email;
        document.getElementById('adminDivision').value = this.dataset.division;
        document.getElementById('adminPassword').value = "";
        document.getElementById('adminPassword').required = false;
        modal.style.display = "flex";
    });
});

document.querySelectorAll('.btn-view').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('viewUsername').innerText = this.dataset.username;
        document.getElementById('viewEmail').innerText = this.dataset.email;
        document.getElementById('viewDivisi').innerText = this.dataset.divisiname;
        document.getElementById('viewCreated').innerText = this.dataset.created;
        viewModal.style.display = "flex";
    });
});

function deleteAdmin(id) {
    if (confirm("Yakin ingin menghapus admin ini?")) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() { modal.style.display = "none"; }
function closeViewModal() { viewModal.style.display = "none"; }

window.onclick = function(e) {
    if (e.target === modal) closeModal();
    if (e.target === viewModal) closeViewModal();
};

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
