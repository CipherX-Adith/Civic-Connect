<?php
// admin/dashboard.php - Master Administrative Governance Console
$pageTitle = "Admin Dashboard";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');
$user = currentUser();
$db = getDB();

// Aggregated Statistics
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCitizens = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'citizen'")->fetchColumn();
$totalOfficials = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'official'")->fetchColumn();
$totalDepartments = (int)$db->query("SELECT COUNT(*) FROM departments")->fetchColumn();

$totalComplaints = (int)$db->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$pendingComplaints = (int)$db->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn();
$inProgressComplaints = (int)$db->query("SELECT COUNT(*) FROM complaints WHERE status = 'In Progress'")->fetchColumn();
$resolvedComplaints = (int)$db->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();

$resolutionRate = $totalComplaints > 0 ? round(($resolvedComplaints / $totalComplaints) * 100, 1) : 0;

// Department performance aggregation for chart
$deptStats = $db->query("
    SELECT d.name as dept_name, d.code as dept_code,
           COUNT(c.id) as total,
           SUM(CASE WHEN c.status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
           SUM(CASE WHEN c.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
           SUM(CASE WHEN c.status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM departments d
    LEFT JOIN complaints c ON d.id = c.department_id
    GROUP BY d.id
")->fetchAll();

$deptLabels = [];
$deptResolved = [];
$deptPending = [];
$deptProgress = [];

foreach ($deptStats as $ds) {
    $deptLabels[] = $ds['dept_code'];
    $deptResolved[] = (int)$ds['resolved'];
    $deptPending[] = (int)$ds['pending'];
    $deptProgress[] = (int)$ds['in_progress'];
}

// Recent System-wide Complaints
$recentComplaints = $db->query("
    SELECT c.*, cat.name as category_name, d.name as department_name, u.name as citizen_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN departments d ON c.department_id = d.id
    JOIN users u ON c.citizen_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 6
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div class="portal-header-banner">
        <div>
            <div class="portal-header-badge">
                <span class="badge badge-admin">
                    <i class="fa-solid fa-crown"></i> Administrative Console
                </span>
            </div>
            <h1 class="portal-header-title">City Governance Command Center</h1>
            <p class="portal-header-subtitle">Real-time municipal performance metrics, department dispatching, and analytical reports.</p>
        </div>
        <div class="portal-header-actions">
            <a href="<?= APP_URL ?>/admin/reports.php" class="btn btn-secondary">
                <i class="fa-solid fa-file-invoice"></i> Reports & Analytics
            </a>
            <a href="<?= APP_URL ?>/admin/users.php?action=new" class="btn btn-primary">
                <i class="fa-solid fa-user-plus"></i> Add Official
            </a>
        </div>
    </div>

    <!-- Stat Grid -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fa-solid fa-layer-group"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Grievances</div>
                <div class="stat-value"><?= $totalComplaints ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon emerald"><i class="fa-solid fa-chart-line"></i></div>
            <div class="stat-info">
                <div class="stat-label">Resolution Rate</div>
                <div class="stat-value"><?= $resolutionRate ?>%</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon teal"><i class="fa-solid fa-building-columns"></i></div>
            <div class="stat-info">
                <div class="stat-label">Departments</div>
                <div class="stat-value"><?= $totalDepartments ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon indigo"><i class="fa-solid fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-label">Registered Citizens</div>
                <div class="stat-value"><?= $totalCitizens ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="portal-grid-2-1" style="margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-column" style="color: var(--primary);"></i> Department Performance & Resolution Load</h3>
            </div>
            <div class="card-body">
                <canvas id="deptBarChart" style="max-height: 280px; width: 100%;"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-pie-chart" style="color: var(--accent-amber);"></i> Grievance Status Split</h3>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 280px;">
                <canvas id="statusDonutChart" style="max-height: 220px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Sub-Modules -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <a href="<?= APP_URL ?>/admin/users.php" class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; text-decoration: none;">
            <div class="stat-icon primary"><i class="fa-solid fa-users-gear"></i></div>
            <div>
                <div style="font-weight: 700; color: var(--secondary);">User Management</div>
                <small style="color: var(--text-muted);">Manage <?= $totalUsers ?> accounts</small>
            </div>
        </a>

        <a href="<?= APP_URL ?>/admin/departments.php" class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; text-decoration: none;">
            <div class="stat-icon teal"><i class="fa-solid fa-building-shield"></i></div>
            <div>
                <div style="font-weight: 700; color: var(--secondary);">Departments</div>
                <small style="color: var(--text-muted);"><?= $totalDepartments ?> active units</small>
            </div>
        </a>

        <a href="<?= APP_URL ?>/admin/categories.php" class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; text-decoration: none;">
            <div class="stat-icon amber"><i class="fa-solid fa-tags"></i></div>
            <div>
                <div style="font-weight: 700; color: var(--secondary);">Categories & SLAs</div>
                <small style="color: var(--text-muted);">Configure issue rules</small>
            </div>
        </a>

        <a href="<?= APP_URL ?>/admin/reports.php" class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; text-decoration: none;">
            <div class="stat-icon emerald"><i class="fa-solid fa-chart-pie"></i></div>
            <div>
                <div style="font-weight: 700; color: var(--secondary);">Analytics & Export</div>
                <small style="color: var(--text-muted);">Download CSV & print</small>
            </div>
        </a>
    </div>

    <!-- Recent Complaints Table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i> Recent Complaints Across City</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject & Category</th>
                            <th>Citizen</th>
                            <th>Department</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Reported</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentComplaints as $rc): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($rc['complaint_uid']) ?></strong></td>
                                <td>
                                    <div style="font-weight: 600; color: var(--secondary);"><?= htmlspecialchars($rc['title']) ?></div>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars($rc['category_name']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($rc['citizen_name']) ?></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($rc['department_name']) ?></span></td>
                                <td><?= getPriorityBadge($rc['priority']) ?></td>
                                <td><?= getStatusBadge($rc['status']) ?></td>
                                <td><small><?= timeAgo($rc['created_at']) ?></small></td>
                                <td>
                                    <a href="<?= APP_URL ?>/official/complaint_details.php?id=<?= $rc['id'] ?>" class="btn btn-secondary btn-sm">
                                        Inspect
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let deptChart = null;
    let statusChart = null;

    function renderAdminCharts() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const labelColor = isDark ? '#cbd5e1' : '#475569';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
        const donutBorder = isDark ? '#111c2a' : '#ffffff';

        // Bar Chart
        const barCtx = document.getElementById('deptBarChart');
        if (barCtx) {
            if (deptChart) deptChart.destroy();
            deptChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($deptLabels) ?>,
                    datasets: [
                        { label: 'Resolved', data: <?= json_encode($deptResolved) ?>, backgroundColor: '#10b981', borderRadius: 4 },
                        { label: 'In Progress', data: <?= json_encode($deptProgress) ?>, backgroundColor: '#3b82f6', borderRadius: 4 },
                        { label: 'Pending', data: <?= json_encode($deptPending) ?>, backgroundColor: '#f59e0b', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { 
                            stacked: true,
                            ticks: { color: labelColor, font: { family: 'Plus Jakarta Sans', weight: '600' } },
                            grid: { color: gridColor }
                        },
                        y: { 
                            stacked: true, 
                            beginAtZero: true,
                            ticks: { color: labelColor, font: { family: 'Plus Jakarta Sans' } },
                            grid: { color: gridColor }
                        }
                    },
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: { color: labelColor, font: { family: 'Plus Jakarta Sans', weight: '600', size: 12 }, padding: 15 }
                        }
                    }
                }
            });
        }

        // Donut Chart
        const donutCtx = document.getElementById('statusDonutChart');
        if (donutCtx) {
            if (statusChart) statusChart.destroy();
            statusChart = new Chart(donutCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'In Progress', 'Resolved'],
                    datasets: [{
                        data: [<?= $pendingComplaints ?>, <?= $inProgressComplaints ?>, <?= $resolvedComplaints ?>],
                        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981'],
                        borderColor: donutBorder,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: { color: labelColor, font: { family: 'Plus Jakarta Sans', weight: '600', size: 12 }, padding: 15 }
                        }
                    },
                    cutout: '68%'
                }
            });
        }
    }

    renderAdminCharts();
    window.addEventListener('civicthemechange', renderAdminCharts);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
