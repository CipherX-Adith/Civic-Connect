<?php
// official/assigned.php - Personal official worklist
$pageTitle = "Assigned to Me";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('official');
$user = currentUser();
$db = getDB();
$stmt = $db->prepare("
    SELECT c.*, cat.name AS category_name, u.name AS citizen_name
    FROM complaints c
    JOIN categories cat ON cat.id = c.category_id
    JOIN users u ON u.id = c.citizen_id
    WHERE c.assigned_to = ?
    ORDER BY FIELD(c.status, 'Pending', 'In Progress', 'Resolved', 'Rejected'), c.updated_at DESC
");
$stmt->execute([$user['id']]);
$complaints = $stmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<main class="main-content">
    <div class="page-intro"><div><span class="eyebrow">Personal work queue</span><h1>Assigned to me</h1><p>Work items assigned directly to you, ordered by their active status.</p></div></div>
    <section class="card">
        <div class="card-body" style="padding:0;">
        <?php if (!$complaints): ?>
            <div class="empty-state"><i class="fa-regular fa-circle-check"></i><h3>Your work queue is clear</h3><p>New assignments will appear here when a department lead assigns them to you.</p></div>
        <?php else: ?>
            <div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Issue</th><th>Citizen</th><th>Priority</th><th>Status</th><th>Updated</th><th></th></tr></thead><tbody>
            <?php foreach ($complaints as $complaint): ?><tr>
                <td><strong><?= htmlspecialchars($complaint['complaint_uid']) ?></strong></td>
                <td><strong><?= htmlspecialchars($complaint['title']) ?></strong><small><?= htmlspecialchars($complaint['category_name']) ?> · <?= htmlspecialchars($complaint['location']) ?></small></td>
                <td><?= htmlspecialchars($complaint['citizen_name']) ?></td><td><?= getPriorityBadge($complaint['priority']) ?></td><td><?= getStatusBadge($complaint['status']) ?></td>
                <td><?= timeAgo($complaint['updated_at']) ?></td><td><a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/official/complaint_details.php?id=<?= $complaint['id'] ?>">Open</a></td>
            </tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
