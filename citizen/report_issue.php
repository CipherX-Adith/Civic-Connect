<?php
// citizen/report_issue.php - Grievance Reporting with GPS and Image Upload
$pageTitle = "Report Public Issue";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('citizen');
$user = currentUser();
$db = getDB();

// Ensure "Others (Miscellaneous)" category exists in DB
$checkOther = $db->query("SELECT id FROM categories WHERE name LIKE 'Other%' LIMIT 1")->fetch();
if (!$checkOther) {
    $defaultDept = $db->query("SELECT id FROM departments ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1;
    $db->prepare("INSERT INTO categories (name, department_id, sla_days, description, icon) VALUES (?, ?, 3, 'Any other public infrastructure or municipal issue not listed above.', 'ellipsis')")
       ->execute(['Others (Miscellaneous)', $defaultDept]);
}

// Fetch active categories with department info
$categories = $db->query("
    SELECT c.*, d.name as department_name, d.code as department_code 
    FROM categories c
    JOIN departments d ON c.department_id = d.id
    ORDER BY CASE WHEN c.name LIKE 'Other%' THEN 1 ELSE 0 END ASC, c.name ASC
")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $priority = in_array($_POST['priority'] ?? '', ['Low', 'Medium', 'High', 'Urgent']) ? $_POST['priority'] : 'Medium';
    $token = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = "Form security verification failed. Please try again.";
    } elseif (empty($categoryId) || empty($title) || empty($description) || empty($location)) {
        $error = "Please fill in all mandatory fields (Category, Title, Description, Location).";
    } else {
        // Fallback: If departmentId wasn't passed directly, get it from category
        if (empty($departmentId)) {
            $catStmt = $db->prepare("SELECT department_id FROM categories WHERE id = ?");
            $catStmt->execute([$categoryId]);
            $departmentId = (int)($catStmt->fetch()['department_id'] ?? 1);
        }

        try {
            $imageFilename = null;
            if (isset($_FILES['complaint_image']) && $_FILES['complaint_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $imageFilename = handleImageUpload($_FILES['complaint_image']);
            }

            $complaintUid = generateComplaintUid();

            $stmt = $db->prepare("
                INSERT INTO complaints (
                    complaint_uid, citizen_id, category_id, department_id, 
                    title, description, image, location, latitude, longitude, 
                    status, priority, created_at
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, 
                    'Pending', ?, NOW()
                )
            ");
            $stmt->execute([
                $complaintUid, $user['id'], $categoryId, $departmentId,
                $title, $description, $imageFilename, $location, $latitude, $longitude,
                $priority
            ]);

            $complaintId = (int)$db->lastInsertId();

            // Insert initial timeline audit log
            addComplaintAudit($complaintId, $user['id'], null, 'Pending', 'Complaint registered by citizen with GPS location details.');

            // Add notification
            addNotification($user['id'], $complaintId, 'Issue Reported Successfully', "Your complaint {$complaintUid} has been registered and forwarded to the concerned department.");

            setFlash('success', "Grievance {$complaintUid} registered successfully! You can track real-time progress below.");
            header("Location: " . APP_URL . "/citizen/complaint_details.php?id=" . $complaintId);
            exit;

        } catch (Exception $e) {
            $error = "Failed to submit grievance: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">
    <?= renderFlash() ?>

    <div style="max-width: 900px; margin: 0 auto;">
        <div style="margin-bottom: 1.5rem;">
            <a href="<?= APP_URL ?>/citizen/dashboard.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--secondary); margin-top: 0.75rem;">
                <i class="fa-solid fa-bullhorn" style="color: var(--primary);"></i> Report a Public Issue
            </h1>
            <p style="color: var(--text-muted);">Provide issue details, upload photographs, and pin the exact location for swift municipal action.</p>
        </div>

        <div class="issue-stepper" aria-label="Issue reporting progress">
            <span class="active"><b>1</b> Issue Details</span>
            <i></i>
            <span><b>2</b> Location</span>
            <i></i>
            <span><b>3</b> Photo</span>
            <i></i>
            <span><b>4</b> Review</span>
        </div>

        <div class="issue-category-tiles" aria-label="Choose an issue category">
            <?php
            $tileIcons = [
                'Potholes & Broken Roads' => 'fa-road',
                'Damaged Footpaths & Curbs' => 'fa-person-walking',
                'Garbage Dump Accumulation' => 'fa-trash-can',
                'Dead Animal Removal' => 'fa-triangle-exclamation',
                'Main Water Pipeline Leakage' => 'fa-droplet',
                'Drainage Blockage & Sewage Overflow' => 'fa-water',
                'Streetlight Not Working' => 'fa-lightbulb',
                'Exposed Live Wires / Transformer Sparks' => 'fa-bolt',
                'Mosquito Infestation & Stagnant Water' => 'fa-bug',
                'Others (Miscellaneous)' => 'fa-layer-group',
                'Others' => 'fa-layer-group'
            ];
            foreach ($categories as $tileCategory):
                $tileIcon = $tileIcons[$tileCategory['name']] ?? 'fa-shapes';
            ?>
                <button type="button" class="issue-category-tile" data-category-id="<?= $tileCategory['id'] ?>">
                    <i class="fa-solid <?= $tileIcon ?>"></i>
                    <span><?= htmlspecialchars($tileCategory['name']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="alert-content"><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card" style="box-shadow: var(--shadow-lg);">
            <div class="card-body" style="padding: 2rem;">
                <form method="POST" action="<?= APP_URL ?>/citizen/report_issue.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="department_id" id="department_id" value="">

                    <!-- Step 1: Category & Auto-Department -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="category_id">
                                <i class="fa-solid fa-list"></i> Select Issue Category *
                            </label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" 
                                            data-dept-id="<?= $cat['department_id'] ?>" 
                                            data-dept-name="<?= htmlspecialchars($cat['department_name']) ?>"
                                            data-sla="<?= $cat['sla_days'] ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-building-columns"></i> Auto-Routed Department
                            </label>
                            <input type="text" id="department_display" class="form-control" readonly placeholder="Will auto-assign based on category" style="background: var(--bg-surface); font-weight: 600; color: var(--primary-dark);">
                            <div class="form-help" id="sla_display" style="color: var(--accent-teal); font-weight: 600;"></div>
                        </div>
                    </div>

                    <!-- Step 2: Title and Priority -->
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label" for="title">
                                <i class="fa-solid fa-heading"></i> Issue Title / Subject *
                            </label>
                            <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Deep pothole causing skidding near Metro Pillar 14" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="priority">
                                <i class="fa-solid fa-flag"></i> Priority Level
                            </label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="Low">Low (General Maintenance)</option>
                                <option value="Medium" selected>Medium (Standard)</option>
                                <option value="High">High (Serious Hazard)</option>
                                <option value="Urgent">Urgent (Immediate Danger)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Step 3: Description -->
                    <div class="form-group">
                        <label class="form-label" for="description">
                            <i class="fa-solid fa-align-left"></i> Detailed Description *
                        </label>
                        <textarea name="description" id="description" rows="4" class="form-control" required placeholder="Describe the problem, since when it has occurred, any landmarks, and safety hazards..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Step 4: Photo Upload with Live Preview -->
                    <div class="form-group">
                        <label class="form-label" for="complaint_image">
                            <i class="fa-solid fa-camera"></i> Supporting Photograph (JPG, PNG, WebP)
                        </label>
                        <input type="file" name="complaint_image" id="complaint_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-help">Upload clear ground photo of the issue (Max size: 6MB).</div>

                        <div id="image_preview_container" style="display: none; margin-top: 1rem;">
                            <img id="image_preview" src="#" alt="Preview" style="max-height: 220px; border-radius: var(--radius-md); border: 1px solid var(--border-color); object-fit: cover;">
                        </div>
                    </div>

                    <!-- Step 5: Geolocation & Map Picker -->
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                            <label class="form-label" style="margin-bottom: 0;">
                                <i class="fa-solid fa-map-location-dot"></i> Location & GPS Coordinates *
                            </label>
                            <button type="button" id="btn_get_location" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-location-crosshairs"></i> Detect My Current Location
                            </button>
                        </div>

                        <input type="text" name="location" id="location" class="form-control" required placeholder="Street address, landmark, or area name" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">

                        <div class="form-row" style="margin-top: 0.75rem;">
                            <div>
                                <input type="text" name="latitude" id="latitude" class="form-control" placeholder="Latitude (e.g. 12.971598)" value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>" readonly>
                            </div>
                            <div>
                                <input type="text" name="longitude" id="longitude" class="form-control" placeholder="Longitude (e.g. 77.594562)" value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>" readonly>
                            </div>
                        </div>

                        <!-- Leaflet Map Container -->
                        <div id="map" style="height: 280px; width: 100%; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-top: 0.75rem;"></div>
                        <div class="form-help"><i class="fa-solid fa-circle-info"></i> Click or drag the marker on the map to accurately pinpoint the issue location.</div>
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                        <a href="<?= APP_URL ?>/citizen/dashboard.php" class="btn btn-secondary btn-lg">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-paper-plane"></i> Submit Grievance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const categorySelect = document.getElementById('category_id');
    const tiles = document.querySelectorAll('.issue-category-tile');

    function syncSelectedTile() {
        const selectedId = categorySelect.value;
        tiles.forEach(tile => {
            if (tile.dataset.categoryId === selectedId) {
                tile.classList.add('selected');
            } else {
                tile.classList.remove('selected');
            }
        });
    }

    tiles.forEach(tile => {
        tile.addEventListener('click', () => {
            categorySelect.value = tile.dataset.categoryId;
            categorySelect.dispatchEvent(new Event('change'));
            syncSelectedTile();
        });
    });

    if (categorySelect) {
        categorySelect.addEventListener('change', syncSelectedTile);
        // Initial sync if category already chosen or defaults
        syncSelectedTile();
    }

    initLocationPicker(12.9716, 77.5946);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
