<?php
// admin/categories.php - Complaint Categories & SLA Management
$pageTitle = "Category Management";
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
        header("Location: " . APP_URL . "/admin/categories.php");
        exit;
    }

    if ($action === 'create_category') {
        $name = trim($_POST['name'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $slaDays = (int)($_POST['sla_days'] ?? 3);
        $description = trim($_POST['description'] ?? '');

        if (empty($name) || empty($departmentId)) {
            setFlash('danger', 'Category name and linked department are required.');
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, department_id, sla_days, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $departmentId, $slaDays, $description]);
            setFlash('success', "Category '{$name}' created.");
        }
        header("Location: " . APP_URL . "/admin/categories.php");
        exit;
    }

    if ($action === 'edit_category') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $slaDays = (int)($_POST['sla_days'] ?? 3);
        $description = trim($_POST['description'] ?? '');

        $stmt = $db->prepare("UPDATE categories SET name = ?, department_id = ?, sla_days = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $departmentId, $slaDays, $description, $id]);
        setFlash('success', "Category updated.");
        header("Location: " . APP_URL . "/admin/categories.php");
        exit;
    }

    if ($action === 'delete_category') {
        $id = (int)($_POST['id'] ?? 0);
        $cStmt = $db->prepare("SELECT COUNT(*) FROM complaints WHERE category_id = ?");
        $cStmt->execute([$id]);
        if ($cStmt->fetchColumn() > 0) {
            setFlash('danger', 'Cannot delete category with associated complaints.');
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Category deleted.');
        }
        header("Location: " . APP_URL . "/admin/categories.php");
        exit;
    }
}

// Fetch categories with department info & complaints count
$categories = $db->query("
    SELECT c.*, d.name as department_name, d.code as department_code,
           COUNT(comp.id) as complaint_count
    FROM categories c
    JOIN departments d ON c.department_id = d.id
    LEFT JOIN complaints comp ON c.id = comp.category_id
    GROUP BY c.id
    ORDER BY d.name ASC, c.name ASC
")->fetchAll();

// Fetch departments for dropdown
$departments = $db->query("SELECT id, name, code FROM departments ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div class="portal-header-banner">
        <div>
            <h1 class="portal-header-title">
                <i class="fa-solid fa-tags" style="color: var(--accent-amber);"></i> Category & SLA Rules
            </h1>
            <p class="portal-header-subtitle">Configure citizen issue taxonomy, automated department linkages, and resolution turnaround targets.</p>
        </div>
        <div class="portal-header-actions">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('createCatModal').style.display='flex'">
                <i class="fa-solid fa-plus-circle"></i> Add New Category
            </button>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Auto-Routed Department</th>
                            <th>SLA Turnaround</th>
                            <th>Description</th>
                            <th>Reported Issues</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--secondary);">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background: var(--primary-light); color: var(--primary-dark); font-weight: 700;">
                                        <?= htmlspecialchars($cat['department_code']) ?> &bull; <?= htmlspecialchars($cat['department_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: var(--accent-teal);">
                                        <i class="fa-regular fa-clock"></i> <?= $cat['sla_days'] ?> Days
                                    </span>
                                </td>
                                <td style="max-width: 280px; font-size: 0.85rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($cat['description'] ?? '—') ?>
                                </td>
                                <td>
                                    <strong><?= $cat['complaint_count'] ?></strong>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem;">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick='openEditCatModal(<?= json_encode($cat) ?>)'>
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <form method="POST" action="<?= APP_URL ?>/admin/categories.php" onsubmit="return confirm('Delete this category?');" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                                        </form>
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

<!-- Modal: Create Category -->
<div id="createCatModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="width: 100%; max-width: 520px; box-shadow: var(--shadow-xl); background: white;">
        <div class="card-header">
            <h3><i class="fa-solid fa-plus-circle"></i> Create Complaint Category</h3>
            <button type="button" onclick="document.getElementById('createCatModal').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/admin/categories.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="create_category">

                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Illegal Sand Mining">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Assigned Department *</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Target SLA (Days) *</label>
                        <input type="number" name="sla_days" class="form-control" value="3" min="1" max="30" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Guidelines for Citizens</label>
                    <textarea name="description" rows="2" class="form-control" placeholder="When citizens should select this issue type..."></textarea>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createCatModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Category -->
<div id="editCatModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="width: 100%; max-width: 520px; box-shadow: var(--shadow-xl); background: white;">
        <div class="card-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Category</h3>
            <button type="button" onclick="document.getElementById('editCatModal').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/admin/categories.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="id" id="edit_cat_id">

                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" id="edit_cat_name" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department_id" id="edit_cat_dept" class="form-select" required>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">SLA (Days)</label>
                        <input type="number" name="sla_days" id="edit_cat_sla" class="form-control" min="1" max="30" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_cat_desc" rows="2" class="form-control"></textarea>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editCatModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditCatModal(cat) {
    document.getElementById('edit_cat_id').value = cat.id;
    document.getElementById('edit_cat_name').value = cat.name;
    document.getElementById('edit_cat_dept').value = cat.department_id;
    document.getElementById('edit_cat_sla').value = cat.sla_days;
    document.getElementById('edit_cat_desc').value = cat.description || '';
    document.getElementById('editCatModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
