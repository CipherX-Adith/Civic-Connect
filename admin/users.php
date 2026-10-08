<?php
// admin/users.php - User Management (Citizens, Department Officials, Administrators)
$pageTitle = "User Management";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');
$db = getDB();
$currentUser = currentUser();

$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!validateCsrfToken($token)) {
        setFlash('danger', 'Invalid security token.');
        header("Location: " . APP_URL . "/admin/users.php");
        exit;
    }

    if ($action === 'create_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'citizen';
        $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $password = $_POST['password'] ?? 'Password@123';

        if (empty($name) || empty($email)) {
            setFlash('danger', 'Name and Email are required.');
        } else {
            $check = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                setFlash('danger', 'A user with this email address already exists.');
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, role, department_id, password, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$name, $email, $phone, $role, $departmentId, $hashed]);
                setFlash('success', "User '{$name}' created successfully.");
            }
        }
        header("Location: " . APP_URL . "/admin/users.php");
        exit;
    }

    if ($action === 'edit_user') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'citizen';
        $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $status = $_POST['status'] ?? 'active';

        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, role = ?, department_id = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $role, $departmentId, $status, $id]);
        setFlash('success', "User details updated.");
        header("Location: " . APP_URL . "/admin/users.php");
        exit;
    }

    if ($action === 'delete_user') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$currentUser['id']) {
            setFlash('danger', 'You cannot delete your own administrative account.');
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'User deleted successfully.');
        }
        header("Location: " . APP_URL . "/admin/users.php");
        exit;
    }
}

// Fetch departments for dropdown
$departments = $db->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll();

// Filter & Search
$roleFilter = $_GET['role'] ?? '';
$search = trim($_GET['q'] ?? '');

$query = "
    SELECT u.*, d.name as department_name,
           (SELECT COUNT(*) FROM complaints WHERE citizen_id = u.id) as complaints_count
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE 1=1
";
$params = [];

if (!empty($roleFilter)) {
    $query .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$query .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div class="portal-header-banner">
        <div>
            <h1 class="portal-header-title">
                <i class="fa-solid fa-users" style="color: var(--primary);"></i> User Management
            </h1>
            <p class="portal-header-subtitle">Manage citizen profiles, create official department staff accounts, and assign permissions.</p>
        </div>
        <div class="portal-header-actions">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('createUserModal').style.display='flex'">
                <i class="fa-solid fa-user-plus"></i> Add New User / Official
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body" style="padding: 1.25rem;">
            <form method="GET" action="<?= APP_URL ?>/admin/users.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <div style="flex: 1; min-width: 240px;">
                    <input type="text" name="q" class="form-control" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div style="min-width: 170px;">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="citizen" <?= $roleFilter === 'citizen' ? 'selected' : '' ?>>Citizens</option>
                        <option value="official" <?= $roleFilter === 'official' ? 'selected' : '' ?>>Officials</option>
                        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admins</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Department Assignment</th>
                            <th>Complaints Filed</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersList as $u): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--secondary);"><?= htmlspecialchars($u['name']) ?></div>
                                    <small style="color: var(--text-muted);"><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($u['email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge" style="background:#e0e7ff; color:#4338ca;"><i class="fa-solid fa-crown"></i> Admin</span>
                                    <?php elseif ($u['role'] === 'official'): ?>
                                        <span class="badge" style="background:#dbeafe; color:#1e40af;"><i class="fa-solid fa-building-user"></i> Official</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><i class="fa-regular fa-user"></i> Citizen</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($u['department_name'] ?? '—') ?>
                                </td>
                                <td><strong><?= $u['complaints_count'] ?></strong></td>
                                <td>
                                    <span class="badge <?= $u['status'] === 'active' ? 'badge-resolved' : 'badge-rejected' ?>">
                                        <?= htmlspecialchars($u['status']) ?>
                                    </span>
                                </td>
                                <td><small><?= formatDate($u['created_at'], 'M d, Y') ?></small></td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem;">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($u) ?>)'>
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <?php if ($u['id'] !== $currentUser['id']): ?>
                                            <form method="POST" action="<?= APP_URL ?>/admin/users.php" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create User -->
<div id="createUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="width: 100%; max-width: 520px; box-shadow: var(--shadow-xl); background: white;">
        <div class="card-header">
            <h3><i class="fa-solid fa-user-plus"></i> Create New Account</h3>
            <button type="button" onclick="document.getElementById('createUserModal').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/admin/users.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="create_user">

                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="Er. John Doe">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" required placeholder="john@civicconnect.gov">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+91 9876543210">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" id="modal_role" class="form-select" onchange="toggleDeptSelect(this.value)">
                            <option value="citizen">Citizen</option>
                            <option value="official" selected>Department Official</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="form-group" id="dept_group">
                        <label class="form-label">Department Assignment</label>
                        <select name="department_id" class="form-select">
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Password</label>
                    <input type="password" name="password" class="form-control" value="Password@123" required>
                    <small class="form-help">Default password is <code>Password@123</code></small>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createUserModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div id="editUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="width: 100%; max-width: 520px; box-shadow: var(--shadow-xl); background: white;">
        <div class="card-header">
            <h3><i class="fa-solid fa-user-pen"></i> Edit User Account</h3>
            <button type="button" onclick="document.getElementById('editUserModal').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/admin/users.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" id="edit_phone" class="form-control">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" id="edit_role" class="form-select">
                            <option value="citizen">Citizen</option>
                            <option value="official">Official</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department_id" id="edit_dept" class="form-select">
                        <option value="">-- No Department / Citizen / Admin --</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editUserModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDeptSelect(role) {
    const deptGrp = document.getElementById('dept_group');
    if (deptGrp) {
        deptGrp.style.opacity = (role === 'official') ? '1' : '0.5';
    }
}

function openEditModal(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_status').value = user.status;
    document.getElementById('edit_dept').value = user.department_id || '';
    document.getElementById('editUserModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
