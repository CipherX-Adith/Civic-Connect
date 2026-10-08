<?php
// citizen/my_complaints.php - Citizen Complaints List with Search & Filtering
$pageTitle = "My Complaints";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('citizen');
$user = currentUser();
$db = getDB();

// Filters
$statusFilter = $_GET['status'] ?? '';
$searchQuery = trim($_GET['q'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);

$query = "
    SELECT c.*, cat.name as category_name, d.name as department_name, d.code as department_code
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN departments d ON c.department_id = d.id
    WHERE c.citizen_id = ?
";
$params = [$user['id']];

if (!empty($statusFilter)) {
    $query .= " AND c.status = ?";
    $params[] = $statusFilter;
}

if (!empty($categoryFilter)) {
    $query .= " AND c.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($searchQuery)) {
    $query .= " AND (c.complaint_uid LIKE ? OR c.title LIKE ? OR c.location LIKE ?)";
    $term = "%{$searchQuery}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$query .= " ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Fetch all categories for filter dropdown
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div class="portal-header-banner">
        <div>
            <h1 class="portal-header-title">
                <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> My Complaints History
            </h1>
            <p class="portal-header-subtitle">View status, real-time updates, and official resolution logs for all your reported issues.</p>
        </div>
        <div class="portal-header-actions">
            <a href="<?= APP_URL ?>/citizen/report_issue.php" class="btn btn-primary">
                <i class="fa-solid fa-plus-circle"></i> Report Another Issue
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body" style="padding: 1.25rem;">
            <form method="GET" action="<?= APP_URL ?>/citizen/my_complaints.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <div style="flex: 1; min-width: 240px;">
                    <input type="text" name="q" class="form-control" placeholder="Search by ID, keyword, or location..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>

                <div style="min-width: 170px;">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Resolved" <?= $statusFilter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div style="min-width: 200px;">
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
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <!-- Complaints Listing -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <?php if (empty($complaints)): ?>
                <div style="padding: 4rem 2rem; text-align: center; color: var(--text-muted);">
                    <i class="fa-regular fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; color: var(--text-subtle);"></i>
                    <h3>No complaints found matching your criteria</h3>
                    <p style="margin-top: 0.5rem;">Try adjusting filters or submit a new grievance report.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Grievance ID</th>
                                <th>Photo</th>
                                <th>Subject & Category</th>
                                <th>Department</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td>
                                        <a href="<?= APP_URL ?>/citizen/complaint_details.php?id=<?= $c['id'] ?>" style="font-weight: 700;">
                                            <?= htmlspecialchars($c['complaint_uid']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['image']) && file_exists(UPLOAD_DIR . $c['image'])): ?>
                                            <img src="<?= UPLOAD_URL . htmlspecialchars($c['image']) ?>" alt="Photo" style="width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                        <?php else: ?>
                                            <div style="width: 48px; height: 48px; background: var(--bg-surface); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; color: var(--text-subtle); font-size: 1.1rem;">
                                                <i class="fa-regular fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--secondary); max-width: 260px;">
                                            <?= htmlspecialchars($c['title']) ?>
                                        </div>
                                        <small style="color: var(--text-muted);"><?= htmlspecialchars($c['category_name']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary" style="font-size: 0.725rem;">
                                            <?= htmlspecialchars($c['department_code']) ?>
                                        </span>
                                    </td>
                                    <td style="max-width: 180px; font-size: 0.85rem; color: var(--text-muted);">
                                        <i class="fa-solid fa-location-dot" style="color: var(--accent-rose);"></i> <?= htmlspecialchars($c['location']) ?>
                                    </td>
                                    <td><?= getPriorityBadge($c['priority']) ?></td>
                                    <td><?= getStatusBadge($c['status']) ?></td>
                                    <td><small style="color: var(--text-muted);"><?= timeAgo($c['created_at']) ?></small></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/citizen/complaint_details.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">
                                            <i class="fa-solid fa-circle-info"></i> Track
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
