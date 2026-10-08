// assets/js/script.js - CivicConnect Frontend Interactions

document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 6000);
    });

    // 2. Image Upload Preview
    const imageInput = document.getElementById('complaint_image');
    const imagePreviewContainer = document.getElementById('image_preview_container');
    const imagePreview = document.getElementById('image_preview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    imagePreview.src = event.target.result;
                    if (imagePreviewContainer) {
                        imagePreviewContainer.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 3. Category & Department dynamic linkage
    const categorySelect = document.getElementById('category_id');
    const departmentDisplay = document.getElementById('department_display');
    const departmentInput = document.getElementById('department_id');
    const slaDisplay = document.getElementById('sla_display');

    if (categorySelect) {
        const updateDept = () => {
            const selectedOpt = categorySelect.options[categorySelect.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.deptName) {
                if (departmentDisplay) departmentDisplay.value = selectedOpt.dataset.deptName;
                if (departmentInput) departmentInput.value = selectedOpt.dataset.deptId;
                if (slaDisplay) slaDisplay.textContent = `Estimated Resolution SLA: ${selectedOpt.dataset.sla || 3} Days`;
            }
        };
        categorySelect.addEventListener('change', updateDept);
        if (categorySelect.value) updateDept();
    }

    // 4. User Profile Toggle & Dashboard Navigation Drawer
    const userProfileToggle = document.getElementById('userProfileToggle');
    const userNavDropdown = document.getElementById('userNavDropdown');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

    function closeAllNav() {
        if (userNavDropdown) {
            userNavDropdown.classList.remove('show');
            userNavDropdown.setAttribute('aria-hidden', 'true');
        }
        if (userProfileToggle) {
            userProfileToggle.classList.remove('active');
            userProfileToggle.setAttribute('aria-expanded', 'false');
        }
        if (appSidebar) {
            appSidebar.classList.remove('sidebar-open');
        }
        if (sidebarBackdrop) {
            sidebarBackdrop.classList.remove('active');
            sidebarBackdrop.setAttribute('aria-hidden', 'true');
        }
    }

    if (userProfileToggle) {
        userProfileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isSmallScreen = window.innerWidth <= 900;
            
            if (isSmallScreen && appSidebar) {
                // On small/mobile screens, toggle the slide-over navigation sidebar with backdrop
                const isOpen = appSidebar.classList.contains('sidebar-open');
                if (isOpen) {
                    closeAllNav();
                } else {
                    appSidebar.classList.add('sidebar-open');
                    if (sidebarBackdrop) {
                        sidebarBackdrop.classList.add('active');
                        sidebarBackdrop.setAttribute('aria-hidden', 'false');
                    }
                    userProfileToggle.classList.add('active');
                }
            } else if (userNavDropdown) {
                // On desktop screens, toggle the profile dropdown
                const isShown = userNavDropdown.classList.contains('show');
                if (isShown) {
                    closeAllNav();
                } else {
                    userNavDropdown.classList.add('show');
                    userNavDropdown.setAttribute('aria-hidden', 'false');
                    userProfileToggle.classList.add('active');
                    userProfileToggle.setAttribute('aria-expanded', 'true');
                }
            }
        });
    }

    // Close drawer on backdrop click
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeAllNav);
    }

    // Close drawer on close button click
    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', closeAllNav);
    }

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-profile-menu-container') && !e.target.closest('.app-sidebar')) {
            closeAllNav();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllNav();
        }
    });

    // 5. Theme & SFW Mode Controller
    function initThemeAndSFW() {
        const themeButtons = document.querySelectorAll('.theme-mode-btn');
        const sfwButtons = document.querySelectorAll('.sfw-toggle-btn');
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        function applyTheme(theme, save = true) {
            if (save) {
                localStorage.setItem('civic_theme', theme);
            }
            document.documentElement.setAttribute('data-theme-setting', theme);

            let effective = theme;
            if (theme === 'system') {
                effective = mediaQuery.matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', effective);
            if (document.body) {
                document.body.setAttribute('data-theme', effective);
            }

            // Update UI state for all theme switchers on page
            document.querySelectorAll('.theme-mode-btn').forEach(btn => {
                if (btn.getAttribute('data-theme') === theme) {
                    btn.classList.add('active');
                    btn.setAttribute('aria-pressed', 'true');
                } else {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-pressed', 'false');
                }
            });

            // Dispatch global event for Chart.js and live-listening widgets
            window.dispatchEvent(new CustomEvent('civicthemechange', { 
                detail: { theme: effective, isDark: effective === 'dark' } 
            }));
        }

        // Initialize active button state from stored or default theme
        const currentSetting = localStorage.getItem('civic_theme') || 'system';
        applyTheme(currentSetting, false);

        // Theme button click events via robust delegation
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.theme-mode-btn');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                const selectedTheme = btn.getAttribute('data-theme');
                if (selectedTheme) {
                    applyTheme(selectedTheme, true);
                }
            }
        });

        // Listen for OS scheme changes if system mode is active
        mediaQuery.addEventListener('change', () => {
            const current = localStorage.getItem('civic_theme') || 'system';
            if (current === 'system') {
                applyTheme('system', false);
            }
        });

        // SFW mode handler
        function updateSFWState(isEnabled, save = true) {
            if (save) {
                localStorage.setItem('civic_sfw', isEnabled ? 'true' : 'false');
            }
            if (isEnabled) {
                document.documentElement.classList.add('sfw-mode-enabled');
            } else {
                document.documentElement.classList.remove('sfw-mode-enabled');
            }
            document.querySelectorAll('.sfw-toggle-btn').forEach(btn => {
                if (isEnabled) {
                    btn.classList.add('active');
                    btn.setAttribute('title', 'SFW Mode: Enabled (Sensitive media blurred)');
                    btn.setAttribute('aria-pressed', 'true');
                } else {
                    btn.classList.remove('active');
                    btn.setAttribute('title', 'SFW Mode: Disabled (Click to enable)');
                    btn.setAttribute('aria-pressed', 'false');
                }
            });
        }

        const isSfwInitial = localStorage.getItem('civic_sfw') === 'true';
        updateSFWState(isSfwInitial, false);

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.sfw-toggle-btn');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                const isCurrentlyEnabled = document.documentElement.classList.contains('sfw-mode-enabled');
                updateSFWState(!isCurrentlyEnabled, true);
            }
        });
    }

    initThemeAndSFW();
});

// Helper for GPS Geolocation & Leaflet map picker
function initLocationPicker(defaultLat = 12.9716, defaultLng = 77.5946) {
    const mapElement = document.getElementById('map');
    if (!mapElement || typeof L === 'undefined') return;

    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const locInput = document.getElementById('location');

    let initialLat = parseFloat(latInput?.value) || defaultLat;
    let initialLng = parseFloat(lngInput?.value) || defaultLng;

    const map = L.map('map').setView([initialLat, initialLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

    function updateCoords(lat, lng) {
        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
        
        // Reverse Geocoding via Nominatim
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.display_name && locInput && (!locInput.value || locInput.dataset.autoFilled === "true")) {
                    locInput.value = data.display_name;
                    locInput.dataset.autoFilled = "true";
                }
            })
            .catch(() => {});
    }

    marker.on('dragend', function (e) {
        const coord = marker.getLatLng();
        updateCoords(coord.lat, coord.lng);
    });

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        updateCoords(e.latlng.lat, e.latlng.lng);
    });

    // Auto-detect location button
    const btnGetLocation = document.getElementById('btn_get_location');
    if (btnGetLocation) {
        btnGetLocation.addEventListener('click', () => {
            if (navigator.geolocation) {
                btnGetLocation.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        map.setView([latitude, longitude], 16);
                        marker.setLatLng([latitude, longitude]);
                        updateCoords(latitude, longitude);
                        btnGetLocation.innerHTML = '<i class="fa-solid fa-crosshairs"></i> Location Found!';
                        setTimeout(() => {
                            btnGetLocation.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Detect My Current Location';
                        }, 2500);
                    },
                    (error) => {
                        alert('Could not retrieve your location. Please drop the pin on the map manually.');
                        btnGetLocation.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Detect My Current Location';
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        });
    }
}
