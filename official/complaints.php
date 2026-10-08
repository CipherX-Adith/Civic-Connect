<?php
// official/complaints.php - Department Complaint Management Queue
$pageTitle = "Department Complaints Queue";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('official');
$user = currentUser();
$db = getDB();

$deptId = $user['department_id'];

// Filter inputs
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$categoryFilter = (int)($_GET['category'] ?? 0);
$searchQuery = trim($_GET['q'] ?? '');

$query = "
    SELECT c.*, 
           cat.name as category_name, 
           u.name as citizen_name, u.phone as citizen_phone,
           d.name as department_name, d.code as department_code,
           officer.name as assigned_officer_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.citizen_id = u.id
    JOIN departments d ON c.department_id = d.id
    LEFT JOIN users officer ON c.assigned_to = officer.id
    WHERE 1=1
";
$params = [];

if ($deptId) {
    $query .= " AND c.department_id = ?";
    $params[] = $deptId;
}

if (!empty($statusFilter)) {
    $query .= " AND c.status = ?";
    $params[] = $statusFilter;
}

if (!empty($priorityFilter)) {
    $query .= " AND c.priority = ?";
    $params[] = $priorityFilter;
}

if (!empty($categoryFilter)) {
    $query .= " AND c.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($searchQuery)) {
    $query .= " AND (c.complaint_uid LIKE ? OR c.title LIKE ? OR c.location LIKE ? OR u.name LIKE ?)";
    $term = "%{$searchQuery}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$query .= " ORDER BY FIELD(c.status, 'Pending', 'In Progress', 'Resolved', 'Rejected'), c.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Categories for filter
$catQuery = $deptId ? "SELECT id, name FROM categories WHERE department_id = ?" : "SELECT id, name FROM categories";
$catStmt = $db->prepare($catQuery);
$catStmt->execute($deptId ? [$deptId] : []);
$categories = $catStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div class="portal-header-banner">
        <div>
            <h1 class="portal-header-title">
                <i class="fa-solid fa-inbox" style="color: var(--primary);"></i> Department Grievance Queue
            </h1>
            <p class="portal-header-subtitle">Review filed grievances, re-assign staff, update progress statuses, and submit proof of resolution.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body" style="padding: 1.25rem;">
            <form method="GET" action="<?= APP_URL ?>/official/complaints.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <div style="flex: 1; min-width: 220px;">
                    <input type="text" name="q" class="form-control" placeholder="Search by ID, keyword, citizen, location..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>

                <div style="min-width: 150px;">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Resolved" <?= $statusFilter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div style="min-width: 150px;">
                    <select name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <option value="Urgent" <?= $priorityFilter === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                        <option value="High" <?= $priorityFilter === 'High' ? 'selected' : '' ?>>High</option>
                        <option value="Medium" <?= $priorityFilter === 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="Low" <?= $priorityFilter === 'Low' ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>

                <div style="min-width: 180px;">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryFilter === $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter"></i> Apply Filter
                </button>
                <a href="<?= APP_URL ?>/official/complaints.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <!-- Complaints Data Table -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <?php if (empty($complaints)): ?>
                <div style="padding: 4rem 2rem; text-align: center; color: var(--text-muted);">
                    <i class="fa-regular fa-clipboard" style="font-size: 3rem; margin-bottom: 1rem; color: var(--text-subtle);"></i>
                    <h3>No complaints found matching this criteria</h3>
                    <p style="margin-top: 0.5rem;">There are currently no active grievances waiting in this queue view.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Grievance ID</th>
                                <th>Priority</th>
                                <th>Subject & Category</th>
                                <th>Citizen Name</th>
                                <th>Location</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Reported</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td>
                                        <a href="<?= APP_URL ?>/official/complaint_details.php?id=<?= $c['id'] ?>" style="font-weight: 700;">
                                            <?= htmlspecialchars($c['complaint_uid']) ?>
                                        </a>
                                    </td>
                                    <td><?= getPriorityBadge($c['priority']) ?></td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--secondary); max-width: 250px;">
                                            <?= htmlspecialchars($c['title']) ?>
                                        </div>
                                        <small style="color: var(--text-muted);"><?= htmlspecialchars($c['category_name']) ?></small>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($c['citizen_name']) ?></div>
                                        <small style="color: var(--text-muted);"><?= htmlspecialchars($c['citizen_phone'] ?? 'N/A') ?></small>
                                    </td>
                                    <td style="max-width: 170px; font-size: 0.85rem; color: var(--text-muted);">
                                        <i class="fa-solid fa-location-dot" style="color: var(--accent-rose);"></i> <?= htmlspecialchars($c['location']) ?>
                                    </td>
                                    <td>
                                        <small style="font-weight: 600; color: var(--primary-dark);">
                                            <?= htmlspecialchars($c['assigned_officer_name'] ?? 'Unassigned') ?>
                                        </small>
                                    </td>
                                    <td><?= getStatusBadge($c['status']) ?></td>
                                    <td><small style="color: var(--text-muted);"><?= timeAgo($c['created_at']) ?></small></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/official/complaint_details.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-pen-to-square"></i> Review
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
