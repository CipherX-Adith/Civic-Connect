<?php
// includes/functions.php - Global Utility Functions for CivicConnect

require_once __DIR__ . '/../config/database.php';

function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    if (isset($_SESSION['flash_error'])) {
        $msg = $_SESSION['flash_error'];
        unset($_SESSION['flash_error']);
        return ['type' => 'danger', 'message' => $msg];
    }
    if (isset($_SESSION['flash_success'])) {
        $msg = $_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
        return ['type' => 'success', 'message' => $msg];
    }
    return null;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) {
        return '';
    }
    $type = $flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'info'));
    $icons = [
        'success' => '<i class="fa-solid fa-circle-check"></i>',
        'danger' => '<i class="fa-solid fa-triangle-exclamation"></i>',
        'warning' => '<i class="fa-solid fa-circle-exclamation"></i>',
        'info' => '<i class="fa-solid fa-circle-info"></i>'
    ];
    $icon = $icons[$type] ?? $icons['info'];

    return "<div class='alert alert-{$type} animate-fade-in' role='alert'>
        <div class='alert-icon'>{$icon}</div>
        <div class='alert-content'>" . htmlspecialchars($flash['message']) . "</div>
        <button type='button' class='alert-close' onclick='this.parentElement.remove()'>&times;</button>
    </div>";
}

function getStatusBadge(string $status): string {
    $statusLower = strtolower($status);
    $map = [
        'pending' => ['bg' => 'badge-pending', 'icon' => 'fa-regular fa-clock', 'text' => 'Pending Review'],
        'in progress' => ['bg' => 'badge-progress', 'icon' => 'fa-solid fa-spinner fa-spin-pulse', 'text' => 'In Progress'],
        'resolved' => ['bg' => 'badge-resolved', 'icon' => 'fa-solid fa-circle-check', 'text' => 'Resolved'],
        'rejected' => ['bg' => 'badge-rejected', 'icon' => 'fa-solid fa-circle-xmark', 'text' => 'Rejected'],
    ];

    $cfg = $map[$statusLower] ?? ['bg' => 'badge-secondary', 'icon' => 'fa-solid fa-tag', 'text' => $status];
    return "<span class='badge {$cfg['bg']}'><i class='{$cfg['icon']}'></i> " . htmlspecialchars($cfg['text']) . "</span>";
}

function getPriorityBadge(string $priority): string {
    $pLower = strtolower($priority);
    $map = [
        'urgent' => ['class' => 'priority-urgent', 'icon' => 'fa-solid fa-bolt'],
        'high' => ['class' => 'priority-high', 'icon' => 'fa-solid fa-angles-up'],
        'medium' => ['class' => 'priority-medium', 'icon' => 'fa-solid fa-angle-up'],
        'low' => ['class' => 'priority-low', 'icon' => 'fa-solid fa-angle-down'],
    ];
    $cfg = $map[$pLower] ?? ['class' => 'priority-low', 'icon' => 'fa-solid fa-minus'];
    return "<span class='priority-tag {$cfg['class']}'><i class='{$cfg['icon']}'></i> " . htmlspecialchars($priority) . "</span>";
}

function formatDate(?string $datetime, string $format = 'M d, Y - h:i A'): string {
    if (!$datetime) return 'N/A';
    return date($format, strtotime($datetime));
}

function timeAgo(?string $datetime): string {
    if (!$datetime) return 'N/A';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

function generateComplaintUid(): string {
    $db = getDB();
    $year = date('Y');
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM complaints WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = (int)$stmt->fetch()['total'] + 1;
    return sprintf("CC-%s-%04d", $year, $count);
}

function handleImageUpload(array $file, string $targetDir = UPLOAD_DIR): ?string {
    if (!isset($file['error']) || is_array($file['error'])) {
        return null;
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // optional upload
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error during file upload code: " . $file['error']);
    }

    // Validate size (max 6MB)
    if ($file['size'] > 6 * 1024 * 1024) {
        throw new Exception("Uploaded file size exceeds maximum limit (6 MB).");
    }

    // Validate mime type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowed = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];

    $ext = array_search($mimeType, $allowed, true);
    if ($ext === false) {
        throw new Exception("Invalid image format. Only JPG, PNG, and WebP images are allowed.");
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = sprintf('complaint_%s_%s.%s', time(), bin2hex(random_bytes(6)), $ext);
    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to move uploaded file to target directory.");
    }

    return $fileName;
}

function addComplaintAudit(int $complaintId, int $userId, ?string $oldStatus, string $newStatus, string $comment): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO complaint_updates (complaint_id, updated_by, old_status, new_status, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$complaintId, $userId, $oldStatus, $newStatus, $comment]);
}

function addNotification(int $userId, ?int $complaintId, string $title, string $message): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, complaint_id, title, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $complaintId, $title, $message]);
}

function getUnreadNotificationsCount(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)($stmt->fetch()['unread'] ?? 0);
}
