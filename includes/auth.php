<?php
// includes/auth.php - Session and Role-based Access Control

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'citizen',
        'department_id' => $_SESSION['user_department_id'] ?? null,
        'department_name' => $_SESSION['user_department_name'] ?? null,
        'phone' => $_SESSION['user_phone'] ?? '',
    ];
}

function userRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

function loginUser(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';
    $_SESSION['user_department_id'] = $user['department_id'] ?? null;
    $_SESSION['user_department_name'] = $user['department_name'] ?? null;
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        header("Location: " . APP_URL . "/login.php");
        exit;
    }
}

function requireRole($allowedRoles): void {
    requireLogin();
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $current = userRole();
    
    if (!in_array($current, $roles, true)) {
        $_SESSION['flash_error'] = "Access denied: You do not have permission to view that resource.";
        // Redirect to their appropriate home
        if ($current === 'admin') {
            header("Location: " . APP_URL . "/admin/dashboard.php");
        } elseif ($current === 'official') {
            header("Location: " . APP_URL . "/official/dashboard.php");
        } else {
            header("Location: " . APP_URL . "/citizen/dashboard.php");
        }
        exit;
    }
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
