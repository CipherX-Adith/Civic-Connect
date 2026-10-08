<?php
// register.php - Citizen Registration Portal
$pageTitle = "Citizen Registration";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . APP_URL . "/citizen/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = "Invalid form submission security token.";
    } elseif (empty($name) || empty($email) || empty($password)) {
        $error = "Name, email and password are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Password and confirm password do not match.";
    } else {
        $db = getDB();
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "An account with this email address already exists. Please log in.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'citizen', 'active')");
            $stmt->execute([$name, $email, $phone, $hashedPassword]);
            
            $userId = (int)$db->lastInsertId();
            addNotification($userId, null, 'Welcome to CivicConnect!', 'Thank you for registering. You can now report public infrastructure issues and track them in real-time.');

            setFlash('success', "Account registered successfully! You can now log in.");
            header("Location: " . APP_URL . "/login.php");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page"><div class="container" style="max-width: 520px; padding: 3rem 1.5rem;">
    <?= renderFlash() ?>

    <div class="card auth-card" style="box-shadow: var(--shadow-xl); border-radius: var(--radius-xl);">
        <div class="card-header" style="text-align: center; display: block; padding: 2rem 1.5rem 1rem;">
            <div style="display: flex; justify-content: center; margin-bottom: 0.85rem;">
                <img src="<?= APP_URL ?>/assets/images/logo.png" alt="CivicConnect Logo" style="width: 76px; height: 76px; object-fit: cover; border-radius: 18px; box-shadow: var(--shadow-md);">
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--secondary);">Citizen Registration</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.25rem;">Join CivicConnect to report municipal issues and track solutions</p>
        </div>

        <div class="card-body" style="padding: 1.5rem 2rem 2rem;">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="alert-content"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= APP_URL ?>/register.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="name"><i class="fa-regular fa-user"></i> Full Name *</label>
                    <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. John Doe" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email"><i class="fa-regular fa-envelope"></i> Email Address *</label>
                    <input type="email" name="email" id="email" class="form-control" required placeholder="john@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone"><i class="fa-solid fa-phone"></i> Contact Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="+91 9876543210" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password"><i class="fa-solid fa-key"></i> Password *</label>
                        <input type="password" name="password" id="password" class="form-control" required minlength="6" placeholder="Min. 6 chars">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password"><i class="fa-solid fa-check-double"></i> Confirm Password *</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6" placeholder="Repeat password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1rem;">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Complete Registration
                </button>
            </form>
        </div>

        <div class="card-footer" style="text-align: center; font-size: 0.875rem;">
            Already have an account? <a href="<?= APP_URL ?>/login.php" style="font-weight: 700;">Sign In</a>
        </div>
    </div>
</div></main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
