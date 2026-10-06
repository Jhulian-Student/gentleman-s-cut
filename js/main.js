document.addEventListener('DOMContentLoaded', async () => {
    // 1. Dynamic Footer Year
    const yearNodes = document.querySelectorAll('[data-current-year]');
    yearNodes.forEach((node) => {
        node.textContent = new Date().getFullYear();
    });

    // 2. Active Navbar Link Highlighting
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.nav-links a');
    navLinks.forEach((link) => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
            // If inside a dropdown, also style parent dropdown toggle
            const parentDropdown = link.closest('.nav-item-dropdown');
            if (parentDropdown && href !== 'index.html') {
                const toggle = parentDropdown.querySelector('.nav-dropdown-toggle');
                if (toggle) toggle.classList.add('child-active');
            }
        }
    });

    // 3. Navbar "Home" Dropdown Interactivity (Click / Tap / Escape)
    initNavbarDropdowns();

    // 4. Dashboard Sidebar Mobile Toggle
    initDashboardSidebar();

    // 5. Auto-connect to localhost server if opened directly as a file
    if (window.location.protocol === 'file:') {
        const page = window.location.pathname.split('/').pop() || 'index.html';
        try {
            const test = await fetch('http://localhost:8000/api/session.php', { method: 'GET', mode: 'cors' });
            if (test.ok) {
                window.location.href = `http://localhost:8000/${page}`;
                return;
            }
        } catch (err) {
            const banner = document.createElement('div');
            banner.style.cssText = 'background:#856404; color:#fff3cd; padding:10px 15px; text-align:center; font-weight:600; font-size:14px; position:sticky; top:0; z-index:9999;';
            banner.innerHTML = '⚠️ Note: To enable login, bookings, and database features, double-click <strong>Start_Server.command</strong> in your project folder, or run <code>php -S 0.0.0.0:8000</code>.';
            document.body.insertBefore(banner, document.body.firstChild);
        }
    }

    // 6. Dynamic Authentication in Navbar (Show "Dashboard" separated to side and "Sign Out")
    await updateNavbarAuth();

    // 7. Scroll Reveal Animations
    const revealItems = document.querySelectorAll(
        '.service-card, .card, .section, .booking-form-wrap, .auth-box, .table-container, .page-header'
    );

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealItems.forEach((item) => item.classList.add('reveal'));
        revealItems.forEach((item) => observer.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('reveal', 'visible'));
    }
});

/**
 * Initializes dropdown menus (for Home dropdown) with click, touch, and outside-click support
 */
function initNavbarDropdowns() {
    const dropdowns = document.querySelectorAll('.nav-item-dropdown');

    dropdowns.forEach((dropdown) => {
        const toggle = dropdown.querySelector('.nav-dropdown-toggle');
        if (!toggle) return;

        // Toggle dropdown on click/tap (works on desktop, laptop, mobile, and tablets)
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = dropdown.classList.contains('open');
            // Close other dropdowns
            dropdowns.forEach(d => d.classList.remove('open'));
            if (!isOpen) {
                dropdown.classList.add('open');
            }
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-item-dropdown')) {
            dropdowns.forEach(d => d.classList.remove('open'));
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dropdowns.forEach(d => d.classList.remove('open'));
            closeSidebarDrawer();
        }
    });
}

/**
 * Mobile drawer support for dashboard sidebar
 */
function initDashboardSidebar() {
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('dashboardSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('sidebar-open');
            if (backdrop) backdrop.classList.toggle('active');
        });
    }

    if (backdrop && sidebar) {
        backdrop.addEventListener('click', () => {
            closeSidebarDrawer();
        });
    }
}

function closeSidebarDrawer() {
    const sidebar = document.getElementById('dashboardSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) sidebar.classList.remove('sidebar-open');
    if (backdrop) backdrop.classList.remove('active');
}

/**
 * Checks authentication status and formats navbar auth elements
 */
async function updateNavbarAuth() {
    let user = null;

    try {
        const res = await fetch('api/session.php');
        const data = await res.json();
        if (data.authenticated && data.user) {
            user = data.user;
            localStorage.setItem('gentlemanscutRole', user.role);
            localStorage.setItem('gentlemanscutUser', user.email);
            localStorage.setItem('gentlemanscutName', user.fullName);
        }
    } catch (e) {
        // Fallback to localStorage if offline/API unreachable
        const storedUser = localStorage.getItem('gentlemanscutUser');
        const storedRole = localStorage.getItem('gentlemanscutRole');
        if (storedUser) {
            user = { 
                email: storedUser, 
                role: storedRole || 'customer',
                fullName: localStorage.getItem('gentlemanscutName') || storedUser
            };
        }
    }

    // Update user profile info on dashboard/admin sidebar if present
    if (user) {
        const role = user.role || 'customer';
        const displayName = user.fullName || user.email;

        const sidebarUserName = document.getElementById('sidebarUserName') || document.getElementById('adminSidebarUserName');
        if (sidebarUserName) {
            sidebarUserName.textContent = displayName;
        }

        const sidebarRoleBadge = document.getElementById('sidebarRoleBadge');
        if (sidebarRoleBadge) {
            sidebarRoleBadge.textContent = role.toUpperCase();
            sidebarRoleBadge.className = `sidebar-role-badge badge-${role}`;
        }
    }

    const navLinksList = document.querySelector('.nav-links');
    if (!navLinksList) return;

    if (user) {
        const role = user.role || 'customer';
        const dashboardUrl = role === 'admin' ? 'admin.html' : 'dashboard.html';
        const dashboardLabel = role === 'admin' ? 'Admin Panel' : 'Dashboard';

        // Find existing login link in the navbar
        const loginLink = Array.from(navLinksList.querySelectorAll('a')).find((a) => {
            const href = a.getAttribute('href') || '';
            const text = a.textContent.trim().toLowerCase();
            return href === 'login.html' || text === 'login';
        });

        if (loginLink) {
            const loginLi = loginLink.parentElement;
            loginLi.className = 'nav-auth-item nav-auth-side';
            loginLi.innerHTML = `
                <a href="${dashboardUrl}" class="nav-dashboard-btn">
                    <span class="dash-badge-icon">📊</span>
                    <span>${dashboardLabel}</span>
                </a>
            `;

            // Create Sign Out link right after Dashboard
            const signOutLi = document.createElement('li');
            signOutLi.className = 'nav-auth-item';
            signOutLi.innerHTML = `<a href="#" class="logout-link signout-nav-btn" style="color: #e74c3c; font-weight: 600;">Sign Out</a>`;
            loginLi.parentNode.insertBefore(signOutLi, loginLi.nextSibling);
        }

        // Attach logout event listeners to all logout/signout buttons
        document.querySelectorAll('.logout-link, .signout-nav-btn, .sidebar-logout').forEach((link) => {
            link.addEventListener('click', handleSignOut);
        });
    } else {
        // When not logged in, ensure any logout link is handled
        document.querySelectorAll('.logout-link, .sidebar-logout').forEach((link) => {
            link.addEventListener('click', handleSignOut);
        });
    }
}

/**
 * Handle Sign Out: call backend logout API and clear local cache
 */
async function handleSignOut(event) {
    if (event) event.preventDefault();

    try {
        await fetch('api/logout.php', { method: 'POST' });
    } catch (err) {
        console.error('Logout error:', err);
    }

    localStorage.removeItem('gentlemanscutUser');
    localStorage.removeItem('gentlemanscutRole');
    localStorage.removeItem('gentlemanscutName');
    window.location.href = 'login.html';
}
