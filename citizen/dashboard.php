<?php
// citizen/dashboard.php - Citizen Portal Overview
$pageTitle = "Citizen Dashboard";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('citizen');
$user = currentUser();
$db = getDB();

// Fetch citizen specific statistics
$stmt = $db->prepare("SELECT COUNT(*) as total FROM complaints WHERE citizen_id = ?");
$stmt->execute([$user['id']]);
$totalComplaints = (int)$stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as pending FROM complaints WHERE citizen_id = ? AND status = 'Pending'");
$stmt->execute([$user['id']]);
$pendingCount = (int)$stmt->fetch()['pending'];

$stmt = $db->prepare("SELECT COUNT(*) as in_progress FROM complaints WHERE citizen_id = ? AND status = 'In Progress'");
$stmt->execute([$user['id']]);
$inProgressCount = (int)$stmt->fetch()['in_progress'];

$stmt = $db->prepare("SELECT COUNT(*) as resolved FROM complaints WHERE citizen_id = ? AND status = 'Resolved'");
$stmt->execute([$user['id']]);
$resolvedCount = (int)$stmt->fetch()['resolved'];

// Fetch latest complaints
$stmt = $db->prepare("
    SELECT c.*, cat.name as category_name, d.name as department_name 
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN departments d ON c.department_id = d.id
    WHERE c.citizen_id = ?
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$recentComplaints = $stmt->fetchAll();

// Fetch recent notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <!-- Welcome Banner -->
    <div class="portal-header-banner">
        <div>
            <h1 class="portal-header-title">Hello, <?= htmlspecialchars($user['name']) ?> 👋</h1>
            <p class="portal-header-subtitle">Track your municipal complaints and report new community issues.</p>
        </div>
        <div class="portal-header-actions">
            <a href="<?= APP_URL ?>/citizen/report_issue.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-plus-circle"></i> Report New Issue
            </a>
        </div>
    </div>

    <section class="citizen-quick-actions" aria-label="Quick actions">
        <a href="<?= APP_URL ?>/citizen/report_issue.php" class="quick-action-card report">
            <span><i class="fa-regular fa-file-lines"></i></span>
            <div><strong>Report New Issue</strong><small>Submit a public issue</small></div>
        </a>
        <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="quick-action-card complaints">
            <span><i class="fa-regular fa-clipboard"></i></span>
            <div><strong>My Complaints</strong><small>View &amp; track status</small></div>
        </a>
        <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="quick-action-card track">
            <span><i class="fa-solid fa-magnifying-glass"></i></span>
            <div><strong>Track Complaint</strong><small>Check using ID</small></div>
        </a>
        <a href="<?= APP_URL ?>/citizen/profile.php" class="quick-action-card profile">
            <span><i class="fa-regular fa-user"></i></span>
            <div><strong>Profile</strong><small>Manage your account</small></div>
        </a>
    </section>

    <!-- Stat Grid -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fa-solid fa-bullhorn"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Reported</div>
                <div class="stat-value"><?= $totalComplaints ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-label">Pending Review</div>
                <div class="stat-value"><?= $pendingCount ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon teal"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            <div class="stat-info">
                <div class="stat-label">In Progress</div>
                <div class="stat-value"><?= $inProgressCount ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon emerald"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-info">
                <div class="stat-label">Resolved</div>
                <div class="stat-value"><?= $resolvedCount ?></div>
            </div>
        </div>
    </div>

    <div class="portal-grid-2-1">
        <!-- Recent Complaints -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-list-check" style="color: var(--primary);"></i> My Recent Complaints</h3>
                <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($recentComplaints)): ?>
                    <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-inbox" style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--text-subtle);"></i>
                        <p style="font-weight: 600;">You have not reported any issues yet.</p>
                        <a href="<?= APP_URL ?>/citizen/report_issue.php" class="btn btn-primary btn-sm" style="margin-top: 1rem;">
                            Report Your First Grievance
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Grievance ID</th>
                                    <th>Category / Title</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Reported</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentComplaints as $c): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($c['complaint_uid']) ?></strong></td>
                                        <td>
                                            <div style="font-weight: 600; color: var(--secondary);"><?= htmlspecialchars($c['title']) ?></div>
                                            <small style="color: var(--text-muted);"><?= htmlspecialchars($c['category_name']) ?></small>
                                        </td>
                                        <td><small><?= htmlspecialchars($c['department_name']) ?></small></td>
                                        <td><?= getStatusBadge($c['status']) ?></td>
                                        <td><small><?= timeAgo($c['created_at']) ?></small></td>
                                        <td>
                                            <a href="<?= APP_URL ?>/citizen/complaint_details.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">
                                                <i class="fa-solid fa-eye"></i> Track
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

        <!-- Notification / Activity Stream -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-bell" style="color: var(--accent-amber);"></i> Recent Updates</h3>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 1.5rem 0;">No new notifications.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($notifications as $n): ?>
                            <div style="padding: 0.85rem; background: var(--bg-surface); border-radius: var(--radius-md); border-left: 3px solid var(--primary);">
                                <div style="font-weight: 700; font-size: 0.875rem; color: var(--secondary); margin-bottom: 0.2rem;">
                                    <?= htmlspecialchars($n['title']) ?>
                                </div>
                                <div style="font-size: 0.825rem; color: var(--text-muted); line-height: 1.4;">
                                    <?= htmlspecialchars($n['message']) ?>
                                </div>
                                <div style="font-size: 0.725rem; color: var(--text-subtle); margin-top: 0.35rem;">
                                    <i class="fa-regular fa-clock"></i> <?= timeAgo($n['created_at']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
