<?php
// official/dashboard.php - Municipal Department Officer Console
$pageTitle = "Official Dashboard";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('official');
$user = currentUser();
$db = getDB();

$deptId = $user['department_id'];

// Department name
$deptName = "All Departments";
if ($deptId) {
    $dStmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
    $dStmt->execute([$deptId]);
    $deptName = $dStmt->fetch()['name'] ?? 'Assigned Department';
}

// Stats for this department
$baseCondition = $deptId ? "WHERE department_id = ?" : "WHERE 1=1";
$baseParams = $deptId ? [$deptId] : [];

// Total
$stmt = $db->prepare("SELECT COUNT(*) as c FROM complaints $baseCondition");
$stmt->execute($baseParams);
$totalDeptComplaints = (int)$stmt->fetch()['c'];

// Pending
$stmt = $db->prepare("SELECT COUNT(*) as c FROM complaints $baseCondition AND status = 'Pending'");
$stmt->execute($baseParams);
$pendingCount = (int)$stmt->fetch()['c'];

// In Progress
$stmt = $db->prepare("SELECT COUNT(*) as c FROM complaints $baseCondition AND status = 'In Progress'");
$stmt->execute($baseParams);
$inProgressCount = (int)$stmt->fetch()['c'];

// Resolved
$stmt = $db->prepare("SELECT COUNT(*) as c FROM complaints $baseCondition AND status = 'Resolved'");
$stmt->execute($baseParams);
$resolvedCount = (int)$stmt->fetch()['c'];

// Joined query condition with table alias
$joinedCondition = $deptId ? "WHERE c.department_id = ?" : "WHERE 1=1";

// Urgent / High priority queue
$urgentQuery = "
    SELECT c.*, cat.name as category_name, u.name as citizen_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.citizen_id = u.id
    $joinedCondition AND c.status IN ('Pending', 'In Progress') AND c.priority IN ('Urgent', 'High')
    ORDER BY FIELD(c.priority, 'Urgent', 'High'), c.created_at ASC
    LIMIT 6
";
$stmt = $db->prepare($urgentQuery);
$stmt->execute($baseParams);
$urgentComplaints = $stmt->fetchAll();

// Recent 5 complaints
$recentQuery = "
    SELECT c.*, cat.name as category_name, u.name as citizen_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.citizen_id = u.id
    $joinedCondition
    ORDER BY c.created_at DESC
    LIMIT 5
";
$stmt = $db->prepare($recentQuery);
$stmt->execute($baseParams);
$recentComplaints = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div class="portal-header-banner">
        <div>
            <div class="portal-header-badge">
                <span class="badge badge-official">
                    <i class="fa-solid fa-building-user"></i> Department Officer Portal
                </span>
                <span class="portal-header-dept">
                    <i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i> <?= htmlspecialchars($deptName) ?>
                </span>
            </div>
            <h1 class="portal-header-title">Officer Workspace</h1>
            <p class="portal-header-subtitle">Manage incoming public reports, assign field crews, and record verified resolutions.</p>
        </div>
        <div class="portal-header-actions">
            <a href="<?= APP_URL ?>/official/complaints.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-list-check"></i> Open Department Queue
            </a>
        </div>
    </div>

    <!-- Stat Grid -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fa-solid fa-inbox"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Assigned</div>
                <div class="stat-value"><?= $totalDeptComplaints ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber"><i class="fa-solid fa-hourglass-start"></i></div>
            <div class="stat-info">
                <div class="stat-label">Pending Action</div>
                <div class="stat-value"><?= $pendingCount ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon teal"><i class="fa-solid fa-person-digging"></i></div>
            <div class="stat-info">
                <div class="stat-label">In Progress</div>
                <div class="stat-value"><?= $inProgressCount ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon emerald"><i class="fa-solid fa-check-double"></i></div>
            <div class="stat-info">
                <div class="stat-label">Resolved Redressals</div>
                <div class="stat-value"><?= $resolvedCount ?></div>
            </div>
        </div>
    </div>

    <!-- Urgent Priority Alert List -->
    <?php if (!empty($urgentComplaints)): ?>
        <div class="card" style="border-left: 5px solid var(--accent-rose); margin-bottom: 2rem;">
            <div class="card-header" style="background: var(--accent-rose-light);">
                <h3 style="color: #9f1239;">
                    <i class="fa-solid fa-triangle-exclamation"></i> High & Urgent Priority Grievances Requiring Action
                </h3>
                <span class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:700;"><?= count($urgentComplaints) ?> Urgent Issues</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Grievance ID</th>
                                <th>Priority</th>
                                <th>Subject & Location</th>
                                <th>Citizen</th>
                                <th>Status</th>
                                <th>Reported</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($urgentComplaints as $uc): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($uc['complaint_uid']) ?></strong></td>
                                    <td><?= getPriorityBadge($uc['priority']) ?></td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--secondary);"><?= htmlspecialchars($uc['title']) ?></div>
                                        <small style="color: var(--text-muted);"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($uc['location']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($uc['citizen_name']) ?></td>
                                    <td><?= getStatusBadge($uc['status']) ?></td>
                                    <td><small><?= timeAgo($uc['created_at']) ?></small></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/official/complaint_details.php?id=<?= $uc['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-bolt"></i> Process Now
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Grievances and Resolution Ratio -->
    <div class="portal-grid-2-1">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-list" style="color: var(--primary);"></i> Recent Department Complaints</h3>
                <a href="<?= APP_URL ?>/official/complaints.php" class="btn btn-secondary btn-sm">View All Queue</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentComplaints as $c): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['complaint_uid']) ?></strong></td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--secondary);"><?= htmlspecialchars($c['title']) ?></div>
                                        <small style="color: var(--text-muted);"><?= htmlspecialchars($c['location']) ?></small>
                                    </td>
                                    <td><small><?= htmlspecialchars($c['category_name']) ?></small></td>
                                    <td><?= getStatusBadge($c['status']) ?></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/official/complaint_details.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-pie" style="color: var(--accent-teal);"></i> Department Status Share</h3>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 260px;">
                <canvas id="deptStatusChart" style="max-height: 220px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let deptStatusChart = null;

    function renderOfficialCharts() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const labelColor = isDark ? '#cbd5e1' : '#475569';
        const donutBorder = isDark ? '#111c2a' : '#ffffff';

        const ctx = document.getElementById('deptStatusChart');
        if (ctx) {
            if (deptStatusChart) deptStatusChart.destroy();
            deptStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'In Progress', 'Resolved'],
                    datasets: [{
                        data: [<?= $pendingCount ?>, <?= $inProgressCount ?>, <?= $resolvedCount ?>],
                        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981'],
                        borderWidth: 3,
                        borderColor: donutBorder
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { 
                                color: labelColor,
                                font: { family: 'Plus Jakarta Sans', size: 12, weight: '600' },
                                padding: 15
                            }
                        }
                    },
                    cutout: '68%'
                }
            });
        }
    }

    renderOfficialCharts();
    window.addEventListener('civicthemechange', renderOfficialCharts);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
