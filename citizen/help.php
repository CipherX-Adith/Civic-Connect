<?php
// citizen/help.php - In-app guidance
$pageTitle = "Help & Support";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('citizen');
require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="page-intro">
        <div><span class="eyebrow">Need a hand?</span><h1>Help &amp; support</h1><p>Everything you need to file an effective civic report and follow it through to resolution.</p></div>
    </div>
    <section class="support-grid">
        <article class="card"><div class="card-body"><i class="fa-solid fa-camera"></i><h3>Make your report actionable</h3><p>Use a clear title, add a sharp photo, and place the map pin as close to the issue as possible.</p><a href="<?= APP_URL ?>/citizen/report_issue.php">Report an issue <i class="fa-solid fa-arrow-right"></i></a></div></article>
        <article class="card"><div class="card-body"><i class="fa-solid fa-clock-rotate-left"></i><h3>Track real progress</h3><p>Every official update appears in your complaint timeline, including department assignment and final remarks.</p><a href="<?= APP_URL ?>/citizen/track_complaint.php">Track a complaint <i class="fa-solid fa-arrow-right"></i></a></div></article>
        <article class="card"><div class="card-body"><i class="fa-solid fa-shield-heart"></i><h3>Stay safe</h3><p>For immediate danger, contact your local emergency services first. CivicConnect is for municipal issue reporting.</p><a href="mailto:support@civicconnect.gov">Contact support <i class="fa-solid fa-arrow-right"></i></a></div></article>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
