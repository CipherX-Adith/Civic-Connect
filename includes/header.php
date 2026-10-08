<?php
// includes/header.php - Global Header for CivicConnect
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user = currentUser();
$unreadCount = $user ? getUnreadNotificationsCount($user['id']) : 0;
$currentPage = basename($_SERVER['PHP_SELF']);
$isHomePage = ($currentPage === 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | CivicConnect' : 'CivicConnect - Smart Public Issue Reporting Portal' ?></title>
    <meta name="description" content="Report civic issues, track progress, and build cleaner, safer, and smarter cities together with CivicConnect.">
    
    <!-- Favicon & Icons -->
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/images/logo.png">
    
    <!-- Google Fonts: Plus Jakarta Sans, Outfit, and Caveat (Handwritten) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Leaflet Maps CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    
    <!-- App Theme Styles -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    
    <!-- Theme & SFW Preload Script (prevents theme flicker) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('civic_theme') || 'system';
            let effectiveTheme = savedTheme;
            if (savedTheme === 'system') {
                effectiveTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', effectiveTheme);
            document.documentElement.setAttribute('data-theme-setting', savedTheme);
            if (localStorage.getItem('civic_sfw') === 'true') {
                document.documentElement.classList.add('sfw-mode-enabled');
            }
        })();
    </script>
</head>
<body class="<?= $isHomePage ? 'landing-page' : 'portal-page' ?><?= $user ? ' is-authenticated' : ' is-guest' ?>">

<?php if (!$isHomePage): ?>
<!-- Navigation Bar for internal / authenticated pages -->
<header class="navbar">
    <div class="nav-inner">
        <a href="<?= APP_URL ?>/index.php" class="brand-logo">
            <img src="<?= APP_URL ?>/assets/images/logo.png" alt="CivicConnect Logo" class="brand-logo-img">
            <div class="brand-text">
                <span class="brand-civic">Civic<span class="brand-connect">Connect</span></span>
                <span class="brand-tagline">CLEANER COMMUNITIES. STRONGER TOMORROW.</span>
            </div>
        </a>

        <nav>
            <ul class="nav-links">
                <li><a href="<?= APP_URL ?>/index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'citizen'): ?>
                        <li><a href="<?= APP_URL ?>/citizen/report_issue.php" class="nav-link <?= $currentPage === 'report_issue.php' ? 'active' : '' ?>">Report</a></li>
                        <li><a href="<?= APP_URL ?>/citizen/my_complaints.php" class="nav-link <?= $currentPage === 'my_complaints.php' ? 'active' : '' ?>">Track</a></li>
                        <li><a href="<?= APP_URL ?>/citizen/dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Insights</a></li>
                    <?php elseif ($user['role'] === 'official'): ?>
                        <li><a href="<?= APP_URL ?>/official/dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
                        <li><a href="<?= APP_URL ?>/official/complaints.php" class="nav-link <?= $currentPage === 'complaints.php' ? 'active' : '' ?>">Queue</a></li>
                    <?php elseif ($user['role'] === 'admin'): ?>
                        <li><a href="<?= APP_URL ?>/admin/dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Console</a></li>
                        <li><a href="<?= APP_URL ?>/admin/users.php" class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">Users</a></li>
                        <li><a href="<?= APP_URL ?>/admin/departments.php" class="nav-link <?= $currentPage === 'departments.php' ? 'active' : '' ?>">Departments</a></li>
                        <li><a href="<?= APP_URL ?>/admin/reports.php" class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">Reports</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="<?= APP_URL ?>/citizen/report_issue.php" class="nav-link">Report</a></li>
                    <li><a href="<?= APP_URL ?>/citizen/my_complaints.php" class="nav-link">Track</a></li>
                    <li><a href="<?= APP_URL ?>/index.php#insights" class="nav-link">Insights</a></li>
                    <li><a href="<?= APP_URL ?>/index.php#about" class="nav-link">About</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="nav-actions">
            <?php if ($user): ?>
                <label class="top-search" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" placeholder="Search...">
                </label>
                <span class="top-notification" title="<?= $unreadCount ?> unread notifications">
                    <i class="fa-regular fa-bell"></i>
                    <?php if ($unreadCount > 0): ?><b></b><?php endif; ?>
                </span>
                <?php if ($user['role'] === 'citizen'): ?>
                    <a href="<?= APP_URL ?>/citizen/report_issue.php" class="btn btn-emerald btn-sm">
                        <i class="fa-solid fa-plus-circle"></i> Report Issue
                    </a>
                <?php endif; ?>

                <!-- User Profile & Dashboard Navigation Toggle -->
                <div class="user-profile-menu-container">
                    <button type="button" class="user-pill" id="userProfileToggle" aria-haspopup="true" aria-expanded="false" title="Click to reveal dashboard navigation">
                        <i class="fa-solid fa-user-circle" style="font-size: 1.15rem; color: #10b981;"></i>
                        <span><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
                        <span class="user-role-tag"><?= htmlspecialchars($user['role']) ?></span>
                        <i class="fa-solid fa-chevron-down profile-chevron" style="font-size: 0.65rem; margin-left: 0.2rem; opacity: 0.75;"></i>
                    </button>

                    <!-- Dropdown Dashboard Navigation Menu -->
                    <div class="user-nav-dropdown" id="userNavDropdown" aria-hidden="true">
                        <div class="user-nav-header">
                            <div class="user-nav-avatar">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="user-nav-info">
                                <strong><?= htmlspecialchars($user['name']) ?></strong>
                                <small><?= htmlspecialchars($user['email']) ?></small>
                                <span class="badge badge-resolved" style="margin-top: 0.25rem;"><?= ucfirst(htmlspecialchars($user['role'])) ?> Portal</span>
                            </div>
                        </div>
                        <div class="user-nav-divider"></div>
                        <div class="user-nav-links">
                            <div class="user-nav-heading">Dashboard Navigation</div>
                            <?php if ($user['role'] === 'citizen'): ?>
                                <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/dashboard.php">
                                    <i class="fa-solid fa-house"></i> Dashboard
                                </a>
                                <a class="<?= $currentPage === 'report_issue.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/report_issue.php">
                                    <i class="fa-regular fa-file-lines"></i> Report Issue
                                </a>
                                <a class="<?= $currentPage === 'my_complaints.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/my_complaints.php">
                                    <i class="fa-regular fa-clipboard"></i> My Complaints
                                </a>
                                <a class="<?= $currentPage === 'track_complaint.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/track_complaint.php">
                                    <i class="fa-solid fa-location-dot"></i> Track Complaint
                                </a>
                                <a class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/profile.php">
                                    <i class="fa-regular fa-user"></i> Profile Settings
                                </a>
                                <a class="<?= $currentPage === 'help.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/help.php">
                                    <i class="fa-regular fa-circle-question"></i> Help & Support
                                </a>
                            <?php elseif ($user['role'] === 'official'): ?>
                                <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/official/dashboard.php">
                                    <i class="fa-solid fa-house"></i> Department Dashboard
                                </a>
                                <a class="<?= $currentPage === 'complaints.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/official/complaints.php">
                                    <i class="fa-regular fa-clipboard"></i> Complaints Queue
                                </a>
                                <a class="<?= $currentPage === 'assigned.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/official/assigned.php">
                                    <i class="fa-solid fa-user-check"></i> Assigned to Me
                                </a>
                                <a class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/official/reports.php">
                                    <i class="fa-regular fa-file-lines"></i> Analytics & Reports
                                </a>
                            <?php else: ?>
                                <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/dashboard.php">
                                    <i class="fa-solid fa-house"></i> Admin Console
                                </a>
                                <a class="<?= $currentPage === 'users.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/users.php">
                                    <i class="fa-solid fa-users"></i> User Accounts
                                </a>
                                <a class="<?= $currentPage === 'departments.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/departments.php">
                                    <i class="fa-regular fa-building"></i> Departments
                                </a>
                                <a class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/categories.php">
                                    <i class="fa-solid fa-tags"></i> Categories & SLAs
                                </a>
                                <a class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/reports.php">
                                    <i class="fa-solid fa-chart-pie"></i> System Analytics
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="user-nav-divider"></div>
                        <div class="user-nav-footer">
                            <div class="dropdown-theme-row">
                                <button type="button" class="sfw-toggle-btn sfw-dropdown-btn" title="Toggle SFW Mode">
                                    <span class="sfw-dot"></span>
                                    <span class="sfw-label">SFW</span>
                                </button>
                                <div class="theme-switch-pill" role="group" aria-label="Theme mode switcher">
                                    <button type="button" class="theme-mode-btn" data-theme="dark" title="Dark Mode" aria-label="Dark Mode">
                                        <i class="fa-solid fa-moon"></i>
                                    </button>
                                    <button type="button" class="theme-mode-btn" data-theme="light" title="Light Mode" aria-label="Light Mode">
                                        <i class="fa-solid fa-sun"></i>
                                    </button>
                                    <button type="button" class="theme-mode-btn" data-theme="system" title="System Theme" aria-label="System Mode">
                                        <i class="fa-solid fa-mobile-screen-button"></i>
                                    </button>
                                </div>
                            </div>
                            <a href="<?= APP_URL ?>/logout.php" class="user-nav-logout">
                                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                            </a>
                        </div>
                    </div>
                </div>

                <a href="<?= APP_URL ?>/logout.php" class="btn btn-glass btn-sm" title="Log Out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/login.php" class="btn btn-glass btn-sm">Login</a>
                <a href="<?= APP_URL ?>/register.php" class="btn btn-emerald btn-sm">Sign Up</a>
                <div class="location-pill">
                    <i class="fa-solid fa-location-dot" style="color: #10b981;"></i>
                    <span>Kochi, Kerala</span>
                    <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; opacity: 0.7;"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php endif; ?>

<?php if ($user && !$isHomePage): ?>
<!-- Backdrop for mobile/responsive dashboard navigation drawer -->
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

<aside class="app-sidebar" id="appSidebar" aria-label="Portal navigation">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding: 0 0.35rem;">
        <a href="<?= APP_URL ?>/index.php" class="app-sidebar-brand" style="padding: 0;">
            <span class="sidebar-mark"><img src="<?= APP_URL ?>/assets/images/logo.png" alt="CivicConnect Logo" class="sidebar-logo-img"></span>
            <span><strong>Civic<span>Connect</span></strong><small><?= ucfirst(htmlspecialchars($user['role'])) ?> Portal</small></span>
        </a>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close navigation">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <nav class="app-sidebar-links">
        <?php if ($user['role'] === 'citizen'): ?>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a class="<?= $currentPage === 'report_issue.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/report_issue.php"><i class="fa-regular fa-file-lines"></i> Report Issue</a>
            <a class="<?= $currentPage === 'my_complaints.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/my_complaints.php"><i class="fa-regular fa-clipboard"></i> My Complaints</a>
            <a class="<?= $currentPage === 'track_complaint.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/track_complaint.php"><i class="fa-solid fa-location-dot"></i> Track Complaint</a>
            <a class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/profile.php"><i class="fa-regular fa-user"></i> Profile</a>
            <a class="<?= $currentPage === 'help.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/citizen/help.php"><i class="fa-regular fa-circle-question"></i> Help & Support</a>
        <?php elseif ($user['role'] === 'official'): ?>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/official/dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a class="<?= $currentPage === 'complaints.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/official/complaints.php"><i class="fa-regular fa-clipboard"></i> Complaints</a>
            <a class="<?= $currentPage === 'assigned.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/official/assigned.php"><i class="fa-solid fa-user-check"></i> Assigned to Me</a>
            <a href="<?= APP_URL ?>/official/complaints.php"><i class="fa-solid fa-pen-to-square"></i> Update Status</a>
            <a class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/official/reports.php"><i class="fa-regular fa-file-lines"></i> Reports</a>
        <?php else: ?>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a class="<?= $currentPage === 'users.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/users.php"><i class="fa-solid fa-users"></i> Users</a>
            <a class="<?= $currentPage === 'departments.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/departments.php"><i class="fa-regular fa-building"></i> Departments</a>
            <a class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/categories.php"><i class="fa-solid fa-tags"></i> Categories</a>
            <a class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/reports.php"><i class="fa-solid fa-chart-pie"></i> Reports</a>
        <?php endif; ?>
    </nav>
    <div class="app-sidebar-footer">
        <div class="sidebar-theme-row">
            <button type="button" class="sfw-toggle-btn" id="sfwToggleBtn" title="Toggle SFW Mode (Blur sensitive complaint media)">
                <span class="sfw-dot"></span>
                <span class="sfw-label">SFW</span>
            </button>
            <div class="theme-switch-pill" role="group" aria-label="Theme mode switcher">
                <button type="button" class="theme-mode-btn" data-theme="dark" title="Dark Mode" aria-label="Dark Mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <button type="button" class="theme-mode-btn" data-theme="light" title="Light Mode" aria-label="Light Mode">
                    <i class="fa-solid fa-sun"></i>
                </button>
                <button type="button" class="theme-mode-btn" data-theme="system" title="System Theme" aria-label="System Mode">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </button>
            </div>
        </div>
        <a href="<?= APP_URL ?>/logout.php" class="app-sidebar-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
    </div>
</aside>
<?php endif; ?>
