<?php
// admin/departments.php - Municipal Departments Administration
$pageTitle = "Department Management";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');
$db = getDB();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!validateCsrfToken($token)) {
        setFlash('danger', 'Security verification failed.');
        header("Location: " . APP_URL . "/admin/departments.php");
        exit;
    }

    if ($action === 'create_department') {
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $headName = trim($_POST['head_name'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');

        if (empty($name) || empty($code)) {
            setFlash('danger', 'Department name and code are required.');
        } else {
            $stmt = $db->prepare("INSERT INTO departments (name, code, description, head_name, contact_email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $description, $headName, $contactEmail]);
            setFlash('success', "Department '{$name}' created.");
        }
        header("Location: " . APP_URL . "/admin/departments.php");
        exit;
    }

    if ($action === 'edit_department') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $headName = trim($_POST['head_name'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');

        $stmt = $db->prepare("UPDATE departments SET name = ?, code = ?, description = ?, head_name = ?, contact_email = ? WHERE id = ?");
        $stmt->execute([$name, $code, $description, $headName, $contactEmail, $id]);
        setFlash('success', "Department details updated.");
        header("Location: " . APP_URL . "/admin/departments.php");
        exit;
    }

    if ($action === 'delete_department') {
        $id = (int)($_POST['id'] ?? 0);
        // Check if complaints are associated
        $cStmt = $db->prepare("SELECT COUNT(*) FROM complaints WHERE department_id = ?");
        $cStmt->execute([$id]);
        if ($cStmt->fetchColumn() > 0) {
            setFlash('danger', 'Cannot delete department with active or historical complaints.');
        } else {
            $stmt = $db->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Department deleted.');
        }
        header("Location: " . APP_URL . "/admin/departments.php");
        exit;
    }
}

// Fetch departments with statistics
$departments = $db->query("
    SELECT d.*, 
           COUNT(DISTINCT cat.id) as category_count,
           COUNT(DISTINCT c.id) as complaint_count,
           COUNT(DISTINCT u.id) as staff_count
    FROM departments d
    LEFT JOIN categories cat ON d.id = cat.department_id
    LEFT JOIN complaints c ON d.id = c.department_id
    LEFT JOIN users u ON d.id = u.department_id AND u.role = 'official'
    GROUP BY d.id
    ORDER BY d.id ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div class="portal-header-banner">
        <div>
            <h1 class="portal-header-title">
                <i class="fa-solid fa-building-columns" style="color: var(--accent-teal);"></i> Department Management
            </h1>
            <p class="portal-header-subtitle">Configure municipal departments, designated nodal officers, and official routing codes.</p>
        </div>
        <div class="portal-header-actions">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('createDeptModal').style.display='flex'">
                <i class="fa-solid fa-plus-circle"></i> Add Department
            </button>
        </div>
    </div>

    <!-- Departments Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
        <?php foreach ($departments as $d): ?>
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="card-header">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span class="badge" style="background: var(--primary-light); color: var(--primary); font-size: 0.85rem; font-weight: 800;">
                                <?= htmlspecialchars($d['code']) ?>
                            </span>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--secondary);">
                                <?= htmlspecialchars($d['name']) ?>
                            </h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <p style="color: var(--text-muted); font-size: 0.875rem; min-height: 42px; margin-bottom: 1.25rem;">
                            <?= htmlspecialchars($d['description'] ?? 'No description provided.') ?>
                        </p>

                        <div style="background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-md); font-size: 0.825rem; display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1rem;">
                            <div>
                                <span style="color: var(--text-muted);">Nodal Officer / Head:</span> 
                                <strong><?= htmlspecialchars($d['head_name'] ?? 'Not Assigned') ?></strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted);">Contact Email:</span> 
                                <strong><?= htmlspecialchars($d['contact_email'] ?? 'N/A') ?></strong>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; text-align: center; gap: 0.5rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                            <div>
                                <div style="font-size: 1.2rem; font-weight: 800; color: var(--primary);"><?= $d['category_count'] ?></div>
                                <small style="color: var(--text-muted); font-size: 0.75rem;">Categories</small>
                            </div>
                            <div>
                                <div style="font-size: 1.2rem; font-weight: 800; color: var(--accent-amber);"><?= $d['complaint_count'] ?></div>
                                <small style="color: var(--text-muted); font-size: 0.75rem;">Grievances</small>
                            </div>
                            <div>
                                <div style="font-size: 1.2rem; font-weight: 800; color: var(--accent-teal);"><?= $d['staff_count'] ?></div>
                                <small style="color: var(--text-muted); font-size: 0.75rem;">Staff</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick='openEditDeptModal(<?= json_encode($d) ?>)'>
                        <i class="fa-solid fa-pencil"></i> Edit
                    </button>
                    <form method="POST" action="<?= APP_URL ?>/admin/departments.php" onsubmit="return confirm('Delete this department?');" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="delete_department">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: Create Department -->
<div id="createDeptModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="width: 100%; max-width: 520px; box-shadow: var(--shadow-xl); background: white;">
        <div class="card-header">
            <h3><i class="fa-solid fa-plus-circle"></i> Add New Department</h3>
            <button type="button" onclick="document.getElementById('createDeptModal').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/admin/departments.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="create_department">

                <div class="form-row">
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Department Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Public Works Department">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" class="form-control" required placeholder="PWD" style="text-transform:uppercase;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Scope</label>
                    <textarea name="description" rows="2" class="form-control" placeholder="Roads, bridges, drainage maintenance..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Head / Nodal Officer</label>
                        <input type="text" name="head_name" class="form-control" placeholder="Er. John Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control" placeholder="dept@civicconnect.gov">
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createDeptModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Department -->
<div id="editDeptModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="width: 100%; max-width: 520px; box-shadow: var(--shadow-xl); background: white;">
        <div class="card-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Department</h3>
            <button type="button" onclick="document.getElementById('editDeptModal').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/admin/departments.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="edit_department">
                <input type="hidden" name="id" id="edit_dept_id">

                <div class="form-row">
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="name" id="edit_dept_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" id="edit_dept_code" class="form-control" required style="text-transform:uppercase;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_dept_desc" rows="2" class="form-control"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Head Officer</label>
                        <input type="text" name="head_name" id="edit_dept_head" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" id="edit_dept_email" class="form-control">
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editDeptModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditDeptModal(d) {
    document.getElementById('edit_dept_id').value = d.id;
    document.getElementById('edit_dept_name').value = d.name;
    document.getElementById('edit_dept_code').value = d.code;
    document.getElementById('edit_dept_desc').value = d.description || '';
    document.getElementById('edit_dept_head').value = d.head_name || '';
    document.getElementById('edit_dept_email').value = d.contact_email || '';
    document.getElementById('editDeptModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
