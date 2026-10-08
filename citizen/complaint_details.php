<?php
// citizen/complaint_details.php - Detailed Complaint Status and Timeline Tracker
$pageTitle = "Complaint Details";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('citizen');
$user = currentUser();
$db = getDB();

$complaintId = (int)($_GET['id'] ?? 0);

// Fetch complaint with department and category details
$stmt = $db->prepare("
    SELECT c.*, 
           cat.name as category_name, cat.sla_days,
           d.name as department_name, d.code as department_code, d.contact_email as dept_email,
           officer.name as assigned_officer_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN departments d ON c.department_id = d.id
    LEFT JOIN users officer ON c.assigned_to = officer.id
    WHERE c.id = ? AND c.citizen_id = ?
");
$stmt->execute([$complaintId, $user['id']]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint not found or you do not have permission to view it.');
    header("Location: " . APP_URL . "/citizen/my_complaints.php");
    exit;
}

// Fetch complaint updates timeline
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
            <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Complaints
            </a>
            <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.75rem;">
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--secondary);">
                    <?= htmlspecialchars($complaint['complaint_uid']) ?>
                </h1>
                <?= getStatusBadge($complaint['status']) ?>
                <?= getPriorityBadge($complaint['priority']) ?>
            </div>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Reported on <?= formatDate($complaint['created_at']) ?> (<?= timeAgo($complaint['created_at']) ?>)
            </p>
        </div>
    </div>

    <!-- Status Progress Steps -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.75rem 2rem;">
        <h4 style="font-size: 1rem; font-weight: 700; color: var(--secondary); margin-bottom: 1.5rem;">
            <i class="fa-solid fa-timeline" style="color: var(--primary);"></i> Resolution Lifecycle
        </h4>
        
        <div style="display: flex; justify-content: space-between; position: relative; max-width: 850px; margin: 0 auto;">
            <!-- Step 1: Registered -->
            <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--accent-emerald); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: 700;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div style="font-weight: 700; font-size: 0.875rem;">Registered</div>
                <small style="color: var(--text-muted); font-size: 0.75rem;"><?= formatDate($complaint['created_at'], 'M d, h:i A') ?></small>
            </div>

            <!-- Step 2: Department Assigned -->
            <?php 
                $isAssigned = ($complaint['status'] !== 'Pending');
                $isProgress = in_array($complaint['status'], ['In Progress', 'Resolved']);
                $isResolved = ($complaint['status'] === 'Resolved');
                $isRejected = ($complaint['status'] === 'Rejected');
            ?>
            <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: <?= $isAssigned ? 'var(--primary)' : 'var(--bg-surface)' ?>; color: <?= $isAssigned ? 'white' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: 700; border: 2px solid <?= $isAssigned ? 'var(--primary)' : 'var(--border-color)' ?>;">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div style="font-weight: 700; font-size: 0.875rem;"><?= htmlspecialchars($complaint['department_code']) ?> Assigned</div>
                <small style="color: var(--text-muted); font-size: 0.75rem;"><?= $isAssigned ? 'Assigned' : 'Awaiting Official' ?></small>
            </div>

            <!-- Step 3: In Progress / Work Underway -->
            <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: <?= $isProgress ? 'var(--accent-amber)' : 'var(--bg-surface)' ?>; color: <?= $isProgress ? 'white' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: 700; border: 2px solid <?= $isProgress ? 'var(--accent-amber)' : 'var(--border-color)' ?>;">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <div style="font-weight: 700; font-size: 0.875rem;">Work in Progress</div>
                <small style="color: var(--text-muted); font-size: 0.75rem;"><?= $isProgress ? 'Field Crew Active' : 'Queued' ?></small>
            </div>

            <!-- Step 4: Resolution -->
            <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: <?= $isResolved ? 'var(--accent-emerald)' : ($isRejected ? 'var(--accent-rose)' : 'var(--bg-surface)') ?>; color: <?= ($isResolved || $isRejected) ? 'white' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: 700; border: 2px solid <?= ($isResolved || $isRejected) ? 'transparent' : 'var(--border-color)' ?>;">
                    <i class="fa-solid <?= $isResolved ? 'fa-circle-check' : ($isRejected ? 'fa-circle-xmark' : 'fa-hourglass-end') ?>"></i>
                </div>
                <div style="font-weight: 700; font-size: 0.875rem;"><?= $isRejected ? 'Rejected' : 'Resolved' ?></div>
                <small style="color: var(--text-muted); font-size: 0.75rem;"><?= $isResolved ? formatDate($complaint['resolved_at'] ?? $complaint['updated_at'], 'M d, h:i A') : ($isRejected ? 'Declined' : 'Pending Final Signoff') ?></small>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.3fr 0.9fr; gap: 1.5rem;">
        <!-- Left: Complaint Info & Map -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-circle-info" style="color: var(--primary);"></i> Issue Description</h3>
                </div>
                <div class="card-body">
                    <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.75rem;">
                        <?= htmlspecialchars($complaint['title']) ?>
                    </h2>
                    <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.7; white-space: pre-line;">
                        <?= htmlspecialchars($complaint['description']) ?>
                    </p>

                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;">
                        <div>
                            <span style="color: var(--text-muted); display: block; font-size: 0.8rem; text-transform: uppercase;">Category</span>
                            <strong><?= htmlspecialchars($complaint['category_name']) ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--text-muted); display: block; font-size: 0.8rem; text-transform: uppercase;">Department</span>
                            <strong><?= htmlspecialchars($complaint['department_name']) ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--text-muted); display: block; font-size: 0.8rem; text-transform: uppercase;">Assigned Officer</span>
                            <strong><?= htmlspecialchars($complaint['assigned_officer_name'] ?? 'Assigned to Ward Duty Team') ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--text-muted); display: block; font-size: 0.8rem; text-transform: uppercase;">Target Resolution SLA</span>
                            <strong style="color: var(--accent-teal);"><?= $complaint['sla_days'] ?> Working Days</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resolution Remarks (If Resolved) -->
            <?php if (!empty($complaint['resolution_remarks'])): ?>
                <div class="card" style="border-left: 5px solid var(--accent-emerald);">
                    <div class="card-header" style="background: var(--accent-emerald-light);">
                        <h3 style="color: #065f46;"><i class="fa-solid fa-shield-check"></i> Official Resolution Remarks</h3>
                    </div>
                    <div class="card-body">
                        <p style="color: #065f46; font-size: 0.95rem; line-height: 1.6;">
                            <?= htmlspecialchars($complaint['resolution_remarks']) ?>
                        </p>
                        <small style="color: var(--text-muted); margin-top: 0.5rem; display: block;">
                            <i class="fa-regular fa-clock"></i> Redressal confirmed on <?= formatDate($complaint['resolved_at'] ?? $complaint['updated_at']) ?>
                        </small>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Location Map -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-map-location-dot" style="color: var(--accent-rose);"></i> Incident Location</h3>
                </div>
                <div class="card-body">
                    <p style="font-weight: 600; color: var(--secondary); margin-bottom: 0.75rem;">
                        <i class="fa-solid fa-location-dot" style="color: var(--accent-rose);"></i> <?= htmlspecialchars($complaint['location']) ?>
                    </p>
                    
                    <?php if (!empty($complaint['latitude']) && !empty($complaint['longitude'])): ?>
                        <div id="details_map" style="height: 240px; border-radius: var(--radius-md); border: 1px solid var(--border-color);"></div>
                        <small style="color: var(--text-muted); margin-top: 0.5rem; display: block;">
                            GPS Coordinates: <?= $complaint['latitude'] ?>, <?= $complaint['longitude'] ?>
                        </small>
                    <?php else: ?>
                        <div style="padding: 1.5rem; background: var(--bg-surface); border-radius: var(--radius-md); text-align: center; color: var(--text-muted);">
                            Location specified by address string.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Photo & Audit Timeline -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Photo Evidence -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-camera" style="color: var(--primary);"></i> Photo Evidence</h3>
                </div>
                <div class="card-body" style="text-align: center;">
                    <?php if (!empty($complaint['image']) && file_exists(UPLOAD_DIR . $complaint['image'])): ?>
                        <a href="<?= UPLOAD_URL . htmlspecialchars($complaint['image']) ?>" target="_blank" title="Click to view full image">
                            <img src="<?= UPLOAD_URL . htmlspecialchars($complaint['image']) ?>" alt="Evidence" style="width: 100%; max-height: 280px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        </a>
                        <small style="color: var(--text-muted); margin-top: 0.5rem; display: block;">Click photo to expand high-resolution view</small>
                    <?php else: ?>
                        <div style="padding: 3rem 1rem; background: var(--bg-surface); border-radius: var(--radius-md); color: var(--text-muted);">
                            <i class="fa-regular fa-image" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: var(--text-subtle);"></i>
                            <p style="font-size: 0.9rem;">No photographic evidence was attached.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity & Status Timeline -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-teal);"></i> Status History Trail</h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($updates as $up): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker <?= strtolower($up['new_status']) === 'resolved' ? 'resolved' : (strtolower($up['new_status']) === 'in progress' ? 'progress' : 'pending') ?>"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-author"><?= htmlspecialchars($up['updater_name']) ?> <small style="color:var(--text-muted);">(<?= htmlspecialchars($up['updater_role']) ?>)</small></span>
                                        <span class="timeline-time"><?= timeAgo($up['created_at']) ?></span>
                                    </div>
                                    <div style="margin-bottom: 0.35rem;">
                                        <?= getStatusBadge($up['new_status']) ?>
                                    </div>
                                    <div style="font-size: 0.875rem; color: var(--text-main);">
                                        <?= htmlspecialchars($up['comment']) ?>
                                    </div>
                                    <div style="font-size: 0.725rem; color: var(--text-subtle); margin-top: 0.25rem;">
                                        <?= formatDate($up['created_at']) ?>
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
    const map = L.map('details_map').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup("<b><?= htmlspecialchars(addslashes($complaint['complaint_uid'])) ?></b><br><?= htmlspecialchars(addslashes($complaint['title'])) ?>").openPopup();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
