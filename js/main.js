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
        }
    });

    // 3. Auto-connect to localhost server if opened directly as a file
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

    // 4. Dynamic Authentication in Navbar (Show "Sign Out" when logged in)
    await updateNavbarAuth();

    // 4. Scroll Reveal Animations
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
 * Checks authentication status and toggles Login vs Sign Out in navbar
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
            user = { email: storedUser, role: storedRole || 'customer' };
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
            loginLi.innerHTML = `<a href="${dashboardUrl}">${dashboardLabel}</a>`;

            // Create Sign Out link right after Dashboard
            const signOutLi = document.createElement('li');
            signOutLi.innerHTML = `<a href="#" class="logout-link signout-nav-btn" style="color: #e74c3c; font-weight: 600;">Sign Out</a>`;
            loginLi.parentNode.insertBefore(signOutLi, loginLi.nextSibling);
        }

        // Attach logout event listeners to all logout/signout buttons
        document.querySelectorAll('.logout-link, .signout-nav-btn').forEach((link) => {
            link.textContent = 'Sign Out';
            link.addEventListener('click', handleSignOut);
        });
    } else {
        // When not logged in, ensure any logout link is handled
        document.querySelectorAll('.logout-link').forEach((link) => {
            link.textContent = 'Sign Out';
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
