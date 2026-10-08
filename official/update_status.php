<?php
// official/update_status.php - Backend Controller for Status Transitions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['official', 'admin']);
$user = currentUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . APP_URL . "/official/dashboard.php");
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($token)) {
    setFlash('danger', 'Security verification failed.');
    header("Location: " . APP_URL . "/official/complaints.php");
    exit;
}

$complaintId = (int)($_POST['complaint_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$priority = trim($_POST['priority'] ?? 'Medium');
$assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
$comment = trim($_POST['comment'] ?? '');

if (empty($complaintId) || empty($newStatus) || empty($comment)) {
    setFlash('danger', 'Please provide a valid status and comment/remarks.');
    header("Location: " . APP_URL . "/official/complaint_details.php?id=" . $complaintId);
    exit;
}

// Fetch current complaint state
$stmt = $db->prepare("SELECT * FROM complaints WHERE id = ?");
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint not found.');
    header("Location: " . APP_URL . "/official/complaints.php");
    exit;
}

$oldStatus = $complaint['status'];

try {
    // Handle optional resolution image upload
    $resolutionImage = $complaint['resolution_image'];
    if (isset($_FILES['resolution_image']) && $_FILES['resolution_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaded = handleImageUpload($_FILES['resolution_image']);
        if ($uploaded) {
            $resolutionImage = $uploaded;
        }
    }

    $resolutionRemarks = ($newStatus === 'Resolved') ? $comment : $complaint['resolution_remarks'];
    $resolvedAt = ($newStatus === 'Resolved') ? date('Y-m-d H:i:s') : ($newStatus !== 'Resolved' ? null : $complaint['resolved_at']);

    // Update complaint record
    $updateStmt = $db->prepare("
        UPDATE complaints 
        SET status = ?, priority = ?, assigned_to = ?, resolution_remarks = ?, resolution_image = ?, resolved_at = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([
        $newStatus, $priority, $assignedTo, $resolutionRemarks, $resolutionImage, $resolvedAt, $complaintId
    ]);

    // Insert timeline update log
    addComplaintAudit($complaintId, $user['id'], $oldStatus, $newStatus, $comment);

    // Notify the citizen
    $notifTitle = "Complaint {$complaint['complaint_uid']} Updated to {$newStatus}";
    $notifMsg = "Department official updated status: {$comment}";
    addNotification($complaint['citizen_id'], $complaintId, $notifTitle, $notifMsg);

    setFlash('success', "Grievance status updated to '{$newStatus}' successfully.");
} catch (Exception $e) {
    setFlash('danger', "Failed to update grievance: " . $e->getMessage());
}

header("Location: " . APP_URL . "/official/complaint_details.php?id=" . $complaintId);
exit;
