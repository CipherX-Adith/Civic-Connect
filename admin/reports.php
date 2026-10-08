<?php
// admin/reports.php - Analytical Governance Reports & CSV Export Engine
$pageTitle = "Analytics & Reports";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');
$db = getDB();

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=CivicConnect_Grievance_Report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Complaint UID', 'Citizen Name', 'Department', 'Category', 'Priority', 'Status', 'Location', 'Latitude', 'Longitude', 'Created At', 'Resolved At', 'Resolution Remarks']);
    
    $exportStmt = $db->query("
        SELECT c.complaint_uid, u.name as citizen_name, d.name as dept_name, cat.name as cat_name,
               c.priority, c.status, c.location, c.latitude, c.longitude, c.created_at, c.resolved_at, c.resolution_remarks
        FROM complaints c
        JOIN users u ON c.citizen_id = u.id
        JOIN departments d ON c.department_id = d.id
        JOIN categories cat ON c.category_id = cat.id
        ORDER BY c.created_at DESC
    ");
    
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Analytics Queries
$total = (int)$db->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$resolved = (int)$db->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();
$pending = (int)$db->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn();
$inProgress = (int)$db->query("SELECT COUNT(*) FROM complaints WHERE status = 'In Progress'")->fetchColumn();

// Department Scorecard
$deptScorecard = $db->query("
    SELECT d.id, d.name as dept_name, d.code as dept_code, d.head_name,
           COUNT(c.id) as total_received,
           SUM(CASE WHEN c.status = 'Resolved' THEN 1 ELSE 0 END) as total_resolved,
           SUM(CASE WHEN c.status = 'Pending' THEN 1 ELSE 0 END) as total_pending,
           SUM(CASE WHEN c.status = 'In Progress' THEN 1 ELSE 0 END) as total_in_progress,
           AVG(CASE WHEN c.status = 'Resolved' AND c.resolved_at IS NOT NULL 
               THEN TIMESTAMPDIFF(HOUR, c.created_at, c.resolved_at)/24.0 ELSE NULL END) as avg_resolution_days
    FROM departments d
    LEFT JOIN complaints c ON d.id = c.department_id
    GROUP BY d.id
    ORDER BY total_received DESC
")->fetchAll();

// Category Distribution for Radar/Pie chart
$categoryDistribution = $db->query("
    SELECT cat.name, COUNT(c.id) as count
    FROM categories cat
    LEFT JOIN complaints c ON cat.id = c.category_id
    GROUP BY cat.id
    ORDER BY count DESC
")->fetchAll();

$catLabels = [];
$catCounts = [];
foreach ($categoryDistribution as $cd) {
    $catLabels[] = $cd['name'];
    $catCounts[] = (int)$cd['count'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div class="portal-header-banner">
        <div>
            <h1 class="portal-header-title">
                <i class="fa-solid fa-chart-line" style="color: var(--accent-emerald);"></i> Municipal Analytics & Governance Reports
            </h1>
            <p class="portal-header-subtitle">SLA performance audits, department resolution scorecards, and public data exports.</p>
        </div>
        <div class="portal-header-actions">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fa-solid fa-print"></i> Print Report
            </button>
            <a href="<?= APP_URL ?>/admin/reports.php?export=csv" class="btn btn-primary">
                <i class="fa-solid fa-file-csv"></i> Export Full Data (CSV)
            </a>
        </div>
    </div>

    <!-- Executive Summary Cards -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fa-solid fa-folder-open"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Grievances Received</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon emerald"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Redressed</div>
                <div class="stat-value"><?= $resolved ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-label">Active Workload</div>
                <div class="stat-value"><?= ($pending + $inProgress) ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon teal"><i class="fa-solid fa-percent"></i></div>
            <div class="stat-info">
                <div class="stat-label">Redressal Efficiency</div>
                <div class="stat-value"><?= $total > 0 ? round(($resolved / $total) * 100, 1) : 0 ?>%</div>
            </div>
        </div>
    </div>

    <!-- Category Distribution Chart & SDG Alignment Box -->
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-pie" style="color: var(--primary);"></i> Grievances by Issue Category</h3>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" style="max-height: 280px;"></canvas>
            </div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: white;">
            <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h3 style="color: white;"><i class="fa-solid fa-award" style="color: #60a5fa;"></i> SDG Impact Index</h3>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="background: rgba(255,255,255,0.06); padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid #f99d26;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: #f99d26;">SDG 11: Sustainable Cities</div>
                    <p style="font-size: 0.825rem; color: #cbd5e1; margin-top: 0.35rem;">
                        Target 11.2 & 11.6: Enhancing urban road safety, municipal solid waste management, and basic public utility upkeep.
                    </p>
                </div>

                <div style="background: rgba(255,255,255,0.06); padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid #00689d;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: #60a5fa;">SDG 16: Strong Institutions</div>
                    <p style="font-size: 0.825rem; color: #cbd5e1; margin-top: 0.35rem;">
                        Target 16.6: Developing effective, accountable, and transparent institutions through digitized public audit logs.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Performance Scorecard Table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-table-list" style="color: var(--secondary);"></i> Municipal Department Scorecard</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Nodal Head</th>
                            <th>Total Received</th>
                            <th>Resolved</th>
                            <th>In Progress</th>
                            <th>Pending</th>
                            <th>Resolution Rate</th>
                            <th>Avg. Redressal Speed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deptScorecard as $sc): ?>
                            <?php 
                                $rate = $sc['total_received'] > 0 ? round(($sc['total_resolved'] / $sc['total_received']) * 100, 1) : 0;
                                $avgDays = !is_null($sc['avg_resolution_days']) ? round($sc['avg_resolution_days'], 1) . ' Days' : 'N/A';
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--secondary);"><?= htmlspecialchars($sc['dept_name']) ?></div>
                                    <small class="badge badge-secondary"><?= htmlspecialchars($sc['dept_code']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($sc['head_name'] ?? 'Assigned') ?></td>
                                <td><strong><?= $sc['total_received'] ?></strong></td>
                                <td><span style="color: var(--accent-emerald); font-weight: 700;"><?= $sc['total_resolved'] ?></span></td>
                                <td><span style="color: var(--primary); font-weight: 700;"><?= $sc['total_in_progress'] ?></span></td>
                                <td><span style="color: var(--accent-amber); font-weight: 700;"><?= $sc['total_pending'] ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="flex: 1; height: 6px; background: var(--bg-surface); border-radius: 3px; overflow: hidden; width: 60px;">
                                            <div style="width: <?= $rate ?>%; height: 100%; background: var(--accent-emerald);"></div>
                                        </div>
                                        <span style="font-weight: 700; font-size: 0.85rem;"><?= $rate ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?= $avgDays ?></span>
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
    let catChart = null;

    function renderCategoryChart() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const labelColor = isDark ? '#cbd5e1' : '#475569';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

        const catCtx = document.getElementById('categoryChart');
        if (catCtx) {
            if (catChart) catChart.destroy();
            catChart = new Chart(catCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($catLabels) ?>,
                    datasets: [{
                        label: 'Reported Grievances',
                        data: <?= json_encode($catCounts) ?>,
                        backgroundColor: ['#3b82f6', '#0d9488', '#f59e0b', '#10b981', '#6366f1', '#f43f5e', '#ec4899'],
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { 
                            beginAtZero: true, 
                            ticks: { color: labelColor, stepSize: 1, font: { family: 'Plus Jakarta Sans' } },
                            grid: { color: gridColor }
                        },
                        y: { 
                            ticks: { color: labelColor, font: { family: 'Plus Jakarta Sans', weight: '600' } },
                            grid: { color: gridColor }
                        }
                    }
                }
            });
        }
    }

    renderCategoryChart();
    window.addEventListener('civicthemechange', renderCategoryChart);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
