<?php
// official/reports.php - Department performance summary
$pageTitle = "Department Reports";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('official');
$user = currentUser();
$db = getDB();
$deptId = (int)$user['department_id'];
$deptStmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
$deptStmt->execute([$deptId]);
$department = $deptStmt->fetch()['name'] ?? 'Department';
$summary = $db->prepare("SELECT COUNT(*) total, SUM(status='Pending') pending, SUM(status='In Progress') progress, SUM(status='Resolved') resolved, SUM(status='Rejected') rejected FROM complaints WHERE department_id=?");
$summary->execute([$deptId]);
$stats = $summary->fetch();
$categoryStmt = $db->prepare("SELECT cat.name, COUNT(c.id) total FROM categories cat LEFT JOIN complaints c ON c.category_id=cat.id WHERE cat.department_id=? GROUP BY cat.id ORDER BY total DESC");
$categoryStmt->execute([$deptId]);
$categories = $categoryStmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<main class="main-content">
    <div class="page-intro"><div><span class="eyebrow"><?= htmlspecialchars($department) ?></span><h1>Department reports</h1><p>Live workload and outcome data for your service area.</p></div></div>
    <section class="stat-grid">
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div><div class="stat-info"><span class="stat-label">Total issues</span><strong class="stat-value"><?= (int)$stats['total'] ?></strong></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fa-regular fa-clock"></i></div><div class="stat-info"><span class="stat-label">Pending</span><strong class="stat-value"><?= (int)$stats['pending'] ?></strong></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-person-digging"></i></div><div class="stat-info"><span class="stat-label">In progress</span><strong class="stat-value"><?= (int)$stats['progress'] ?></strong></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div><div class="stat-info"><span class="stat-label">Resolved</span><strong class="stat-value"><?= (int)$stats['resolved'] ?></strong></div></div>
    </section>
    <section class="card report-bars"><div class="card-header"><h3>Issues by category</h3></div><div class="card-body">
    <?php $max = max(array_column($categories, 'total') ?: [1]); foreach ($categories as $category): $width = ((int)$category['total'] / $max) * 100; ?>
        <div class="report-bar"><span><?= htmlspecialchars($category['name']) ?></span><div><b style="width:<?= $width ?>%"></b></div><strong><?= (int)$category['total'] ?></strong></div>
    <?php endforeach; ?>
    </div></section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
