<?php
// login.php - Secure Multi-Role Portal Authentication
$pageTitle = "Portal Login";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Handle explicit switch or logout requests
if (isset($_GET['switch']) || isset($_GET['logout'])) {
    logoutUser();
}

$alreadyLoggedInUser = currentUser();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($token)) {
        $error = "Security token validation failed. Please try again.";
    } elseif (empty($email) || empty($password)) {
        $error = "Please provide both email address and password.";
    } else {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.*, d.name as department_name 
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.email = ? AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Log in the new user (overwriting any previous session)
            loginUser($user);
            setFlash('success', "Welcome back, " . htmlspecialchars($user['name']) . "!");
            
            if ($user['role'] === 'admin') {
                header("Location: " . APP_URL . "/admin/dashboard.php");
            } elseif ($user['role'] === 'official') {
                header("Location: " . APP_URL . "/official/dashboard.php");
            } else {
                header("Location: " . APP_URL . "/citizen/dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid email address or password. Please verify your credentials.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page"><div class="container" style="max-width: 480px; padding: 3rem 1.5rem;">
    <?= renderFlash() ?>
    
    <div class="card auth-card" style="box-shadow: var(--shadow-xl); border-radius: var(--radius-xl);">
        <div class="card-header" style="text-align: center; display: block; padding: 2rem 1.5rem 1rem;">
            <div style="display: flex; justify-content: center; margin-bottom: 0.85rem;">
                <img src="<?= APP_URL ?>/assets/images/logo.png" alt="CivicConnect Logo" style="width: 76px; height: 76px; object-fit: cover; border-radius: 18px; box-shadow: var(--shadow-md);">
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--secondary);">CivicConnect Login</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.25rem;">Access citizen portal, department queue, or admin console</p>
        </div>

        <div class="card-body" style="padding: 1.5rem 2rem 2rem;">
            <?php if ($alreadyLoggedInUser): ?>
                <div style="background: var(--primary-light); border: 1px solid var(--border-focus); padding: 0.65rem 0.85rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-size: 0.825rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                    <div>
                        <i class="fa-solid fa-user-check" style="color: var(--primary);"></i> Active: <strong><?= htmlspecialchars($alreadyLoggedInUser['name']) ?></strong> (<span style="text-transform: capitalize;"><?= htmlspecialchars($alreadyLoggedInUser['role']) ?></span>)
                    </div>
                    <a href="<?= APP_URL ?>/logout.php" class="btn btn-sm" style="background:#fee2e2; color:#b91c1c; padding:0.2rem 0.5rem; font-size:0.75rem;">Sign Out</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                    <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="alert-content"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= APP_URL ?>/login.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="email"><i class="fa-regular fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" required placeholder="name@domain.gov" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password"><i class="fa-solid fa-key"></i> Password</label>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.25rem;">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
                </button>
            </form>

            <!-- One-Click Demo Logins -->
            <div style="margin-top: 1.5rem; border-top: 1px dashed var(--border-color); padding-top: 1.25rem;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 0.5rem; text-align: center;">
                    ⚡ Quick Demo Fill (Password: <code>Password@123</code>)
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('citizen@civicconnect.gov')">👤 Citizen</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('pwd.officer@civicconnect.gov')">👷 Official</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('admin@civicconnect.gov')">👑 Admin</button>
                </div>
            </div>
        </div>

        <div class="card-footer" style="text-align: center; font-size: 0.875rem;">
            New Citizen? <a href="<?= APP_URL ?>/register.php" style="font-weight: 700;">Create an Account</a>
        </div>
    </div>
</div></main>

<script>
function fillCreds(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'Password@123';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
