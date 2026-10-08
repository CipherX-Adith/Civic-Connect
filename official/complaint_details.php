<?php
// official/complaint_details.php - Official Complaint Review & Status Management
$pageTitle = "Official Complaint Review";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('official');
$user = currentUser();
$db = getDB();

$complaintId = (int)($_GET['id'] ?? 0);

// Fetch complaint details
$stmt = $db->prepare("
    SELECT c.*, 
           cat.name as category_name, cat.sla_days,
           d.name as department_name, d.code as department_code,
           citizen.name as citizen_name, citizen.email as citizen_email, citizen.phone as citizen_phone,
           officer.name as assigned_officer_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN departments d ON c.department_id = d.id
    JOIN users citizen ON c.citizen_id = citizen.id
    LEFT JOIN users officer ON c.assigned_to = officer.id
    WHERE c.id = ? " . ($user['department_id'] ? "AND c.department_id = ?" : "") . "
");

$params = [$complaintId];
if ($user['department_id']) $params[] = $user['department_id'];
$stmt->execute($params);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint not found or not assigned to your department.');
    header("Location: " . APP_URL . "/official/complaints.php");
    exit;
}

// Fetch list of staff in this department for assignment dropdown
$staffStmt = $db->prepare("SELECT id, name FROM users WHERE (role = 'official' OR role = 'admin') AND (department_id = ? OR department_id IS NULL) AND status = 'active' ORDER BY name ASC");
$staffStmt->execute([$complaint['department_id']]);
$deptStaff = $staffStmt->fetchAll();

// Fetch audit trail
$stmt = $db->prepare("
    SELECT u.*, usr.name as updater_name, usr.role as updater_role
    FROM complaint_updates u
    JOIN users usr ON u.updated_by = usr.id
    WHERE u.complaint_id = ?
    ORDER BY u.created_at ASC
");
$stmt->execute([$complaintId]);
$updates = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="<?= APP_URL ?>/official/complaints.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Queue
            </a>
            <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.75rem;">
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--secondary);">
                    <?= htmlspecialchars($complaint['complaint_uid']) ?>
                </h1>
                <?= getStatusBadge($complaint['status']) ?>
                <?= getPriorityBadge($complaint['priority']) ?>
            </div>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Filed by <strong><?= htmlspecialchars($complaint['citizen_name']) ?></strong> (<?= htmlspecialchars($complaint['citizen_phone'] ?? 'No phone') ?>) &bull; <?= timeAgo($complaint['created_at']) ?>
            </p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.25fr 0.95fr; gap: 1.5rem;">
        <!-- Left Column: Details, Photos, Map -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Complaint Summary Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-file-lines" style="color: var(--primary);"></i> Grievance Information</h3>
                </div>
                <div class="card-body">
                    <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.75rem;">
                        <?= htmlspecialchars($complaint['title']) ?>
                    </h2>
                    <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.7; white-space: pre-line;">
                        <?= htmlspecialchars($complaint['description']) ?>
                    </p>

                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.875rem;">
                        <div>
                            <span style="color: var(--text-muted); display: block;">Citizen Contact</span>
                            <strong><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($complaint['citizen_email']) ?></strong><br>
                            <strong><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($complaint['citizen_phone'] ?? 'N/A') ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--text-muted); display: block;">Department & Category</span>
                            <strong><?= htmlspecialchars($complaint['department_name']) ?></strong><br>
                            <small style="color: var(--primary);"><?= htmlspecialchars($complaint['category_name']) ?> (SLA: <?= $complaint['sla_days'] ?> Days)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Location -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-location-dot" style="color: var(--accent-rose);"></i> Incident Location & Pin</h3>
                </div>
                <div class="card-body">
                    <p style="font-weight: 600; color: var(--secondary); margin-bottom: 0.75rem;">
                        <i class="fa-solid fa-map-pin" style="color: var(--accent-rose);"></i> <?= htmlspecialchars($complaint['location']) ?>
                    </p>
                    
                    <?php if (!empty($complaint['latitude']) && !empty($complaint['longitude'])): ?>
                        <div id="official_map" style="height: 250px; border-radius: var(--radius-md); border: 1px solid var(--border-color);"></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Photos (Citizen Evidence + Official Resolution Photo) -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-camera" style="color: var(--primary);"></i> Photographs & Evidence</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label">Citizen Submitted Photo:</label>
                            <?php if (!empty($complaint['image']) && file_exists(UPLOAD_DIR . $complaint['image'])): ?>
                                <a href="<?= UPLOAD_URL . htmlspecialchars($complaint['image']) ?>" target="_blank">
                                    <img src="<?= UPLOAD_URL . htmlspecialchars($complaint['image']) ?>" alt="Evidence" style="width:100%; height: 180px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                </a>
                            <?php else: ?>
                                <div style="height: 180px; background: var(--bg-surface); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.85rem;">
                                    No photo uploaded
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="form-label">Official Resolution Proof:</label>
                            <?php if (!empty($complaint['resolution_image']) && file_exists(UPLOAD_DIR . $complaint['resolution_image'])): ?>
                                <a href="<?= UPLOAD_URL . htmlspecialchars($complaint['resolution_image']) ?>" target="_blank">
                                    <img src="<?= UPLOAD_URL . htmlspecialchars($complaint['resolution_image']) ?>" alt="Resolution Photo" style="width:100%; height: 180px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                </a>
                            <?php else: ?>
                                <div style="height: 180px; background: var(--bg-surface); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.85rem;">
                                    Pending resolution proof
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Status Update Form & Audit Log -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Take Action Form -->
            <div class="card" style="border: 2px solid var(--primary-light); box-shadow: var(--shadow-md);">
                <div class="card-header" style="background: var(--primary-light);">
                    <h3 style="color: var(--primary-dark);"><i class="fa-solid fa-pen-to-square"></i> Department Action & Status Update</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= APP_URL ?>/official/update_status.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">

                        <div class="form-group">
                            <label class="form-label" for="status">Update Grievance Status *</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="Pending" <?= $complaint['status'] === 'Pending' ? 'selected' : '' ?>>Pending Review</option>
                                <option value="In Progress" <?= $complaint['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress (Crew Dispatched)</option>
                                <option value="Resolved" <?= $complaint['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved (Completed & Verified)</option>
                                <option value="Rejected" <?= $complaint['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected (Duplicate / Invalid)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="assigned_to">Assign Personnel / Officer</label>
                            <select name="assigned_to" id="assigned_to" class="form-select">
                                <option value="">-- Keep Unassigned / Ward Pool --</option>
                                <?php foreach ($deptStaff as $staff): ?>
                                    <option value="<?= $staff['id'] ?>" <?= $complaint['assigned_to'] == $staff['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($staff['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="priority">Adjust Priority</label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="Low" <?= $complaint['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                                <option value="Medium" <?= $complaint['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="High" <?= $complaint['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                                <option value="Urgent" <?= $complaint['priority'] === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="comment">Official Remarks / Action Taken *</label>
                            <textarea name="comment" id="comment" rows="3" class="form-control" required placeholder="Explain action taken, field crew findings, or reason for resolution/rejection..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="resolution_image">Resolution Photograph Proof (Optional)</label>
                            <input type="file" name="resolution_image" id="resolution_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fa-solid fa-check"></i> Commit Status Update
                        </button>
                    </form>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail & Updates Log</h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($updates as $up): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker <?= strtolower($up['new_status']) === 'resolved' ? 'resolved' : (strtolower($up['new_status']) === 'in progress' ? 'progress' : 'pending') ?>"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-author"><?= htmlspecialchars($up['updater_name']) ?></span>
                                        <span class="timeline-time"><?= timeAgo($up['created_at']) ?></span>
                                    </div>
                                    <div style="margin-bottom: 0.35rem;">
                                        <?= getStatusBadge($up['new_status']) ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-main);">
                                        <?= htmlspecialchars($up['comment']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($complaint['latitude']) && !empty($complaint['longitude'])): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const lat = <?= (float)$complaint['latitude'] ?>;
    const lng = <?= (float)$complaint['longitude'] ?>;
    const map = L.map('official_map').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup("<b><?= htmlspecialchars(addslashes($complaint['complaint_uid'])) ?></b>").openPopup();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
