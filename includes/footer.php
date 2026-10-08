<?php
// includes/footer.php - Global Footer for CivicConnect
?>
<footer class="footer">
    <div class="container">
        <div class="footer-top">
            <div>
                <div class="brand-logo" style="display: flex; align-items: center; gap: 0.65rem; color:#ffffff; margin-bottom: 0.75rem;">
                    <img src="<?= APP_URL ?>/assets/images/logo.png" alt="CivicConnect Logo" style="width: 36px; height: 36px; object-fit: cover; border-radius: 8px;">
                    <div>
                        <span style="font-size: 1.2rem; font-weight: 800; color: #ffffff;">Civic<span style="color:#10b981;">Connect</span></span>
                        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.65); letter-spacing: 0.04em;">CLEANER COMMUNITIES. STRONGER TOMORROW.</div>
                    </div>
                </div>
                <p style="font-size: 0.9rem; max-width: 480px; line-height: 1.6;">
                    CivicConnect is a smart, transparent public issue reporting portal enabling citizens to bridge the gap with municipal authorities for rapid grievance redressal, real-time tracking, and sustainable urban infrastructure.
                </p>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <span class="sdg-tag sdg-11"><i class="fa-solid fa-tree-city"></i> SDG 11: Sustainable Cities</span>
                    <span class="sdg-tag sdg-16"><i class="fa-solid fa-scale-balanced"></i> SDG 16: Strong Institutions</span>
                </div>
            </div>

            <div>
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?= APP_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= APP_URL ?>/login.php">Portal Login</a></li>
                    <li><a href="<?= APP_URL ?>/register.php">Citizen Sign Up</a></li>
                    <li><a href="<?= APP_URL ?>/citizen/report_issue.php">Report Grievance</a></li>
                </ul>
            </div>

            <div>
                <h5>Project Credits</h5>
                <p style="font-size: 0.825rem; margin-bottom: 0.5rem; color: #cbd5e1;"><strong>Group No: 01</strong></p>
                <ul class="footer-links" style="font-size: 0.85rem;">
                    <li><i class="fa-solid fa-user-check" style="color:#60a5fa;"></i> 1. ABHIRAM RAJESH (1)</li>
                    <li><i class="fa-solid fa-user-check" style="color:#60a5fa;"></i> 2. ABHISHEK S (2)</li>
                    <li><i class="fa-solid fa-user-check" style="color:#60a5fa;"></i> 3. ADITH S KUMAR (3)</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?= date('Y') ?> CivicConnect Portal. All rights reserved.
            </div>
            <div>
                <span style="display:inline-flex; align-items:center; gap:0.5rem;">
                    <i class="fa-solid fa-shield-halved" style="color:#10b981;"></i> Secure Role-Based Government Interface
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Leaflet Map JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- Chart.js for analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Main App Script -->
<script src="<?= APP_URL ?>/assets/js/script.js?v=<?= filemtime(__DIR__ . '/../assets/js/script.js') ?>"></script>
</body>
</html>
