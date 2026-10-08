<?php
// citizen/track_complaint.php - Quick tracking by a citizen-owned complaint ID
$pageTitle = "Track Complaint";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('citizen');
$user = currentUser();
$db = getDB();
$complaint = null;
$queryId = trim($_GET['complaint_id'] ?? '');
$message = '';

if ($queryId !== '') {
    $stmt = $db->prepare("
        SELECT c.*, cat.name AS category_name, d.name AS department_name
        FROM complaints c
        JOIN categories cat ON cat.id = c.category_id
        JOIN departments d ON d.id = c.department_id
        WHERE c.complaint_uid = ? AND c.citizen_id = ?
        LIMIT 1
    ");
    $stmt->execute([$queryId, $user['id']]);
    $complaint = $stmt->fetch();
    if (!$complaint) {
        $message = 'No complaint with that ID belongs to your account.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="page-intro">
        <div>
            <span class="eyebrow">Quick status check</span>
            <h1>Track a complaint</h1>
            <p>Enter a CivicConnect complaint ID to jump straight to its latest progress.</p>
        </div>
    </div>

    <section class="track-panel card">
        <form method="GET" action="<?= APP_URL ?>/citizen/track_complaint.php" class="track-form">
            <label class="form-label" for="complaint_id">Complaint ID</label>
            <div>
                <input class="form-control" id="complaint_id" name="complaint_id" value="<?= htmlspecialchars($queryId) ?>" placeholder="e.g. CC-2026-0001" required>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Track</button>
            </div>
        </form>
        <?php if ($message): ?><div class="alert alert-warning"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($complaint): ?>
            <div class="track-result">
                <div><span class="eyebrow"><?= htmlspecialchars($complaint['complaint_uid']) ?></span><h2><?= htmlspecialchars($complaint['title']) ?></h2><p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($complaint['location']) ?></p></div>
                <div><?= getStatusBadge($complaint['status']) ?><a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/citizen/complaint_details.php?id=<?= $complaint['id'] ?>">Open full timeline <i class="fa-solid fa-arrow-right"></i></a></div>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
