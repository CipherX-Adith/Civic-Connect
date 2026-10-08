<?php
// citizen/profile.php - Citizen Profile & Account Settings
$pageTitle = "My Profile";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('citizen');
$user = currentUser();
$db = getDB();

$error = '';
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = "Security token verification failed.";
    } elseif ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            $error = "Name cannot be empty.";
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $user['id']]);
            
            $_SESSION['user_name'] = $name;
            $_SESSION['user_phone'] = $phone;
            setFlash('success', 'Profile updated successfully.');
            header("Location: " . APP_URL . "/citizen/profile.php");
            exit;
        }
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if (!password_verify($currentPass, $row['password'])) {
            $error = "Current password is incorrect.";
        } elseif (strlen($newPass) < 6) {
            $error = "New password must be at least 6 characters long.";
        } elseif ($newPass !== $confirmPass) {
            $error = "New password and confirmation do not match.";
        } else {
            $newHashed = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHashed, $user['id']]);
            setFlash('success', 'Password changed successfully.');
            header("Location: " . APP_URL . "/citizen/profile.php");
            exit;
        }
    }
}

// Fetch fresh details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content" style="max-width: 800px;">
    <?= renderFlash() ?>

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--secondary);">
            <i class="fa-solid fa-user-gear" style="color: var(--primary);"></i> Account Settings
        </h1>
        <p style="color: var(--text-muted);">Manage your personal details and password security.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="alert-content"><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <!-- Profile Info Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-regular fa-id-card"></i> Personal Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/citizen/profile.php">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="name">Full Name</label>
                            <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars($userData['name']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address (Read-only)</label>
                            <input type="email" class="form-control" readonly value="<?= htmlspecialchars($userData['email']) ?>" style="background: var(--bg-surface);">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
                    </button>
                </form>
            </div>
        </div>

        <!-- Password Change Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-key"></i> Update Security Password</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/citizen/profile.php">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-lock"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
