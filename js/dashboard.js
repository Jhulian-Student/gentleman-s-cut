document.addEventListener('DOMContentLoaded', async () => {
    let currentUser = null;

    // 1. Fetch current active session from server
    try {
        const sessionRes = await fetch('api/session.php');
        const sessionData = await sessionRes.json();

        if (!sessionData.authenticated || !sessionData.user) {
            // Not logged in -> redirect to login
            window.location.href = 'login.html';
            return;
        }

        currentUser = sessionData.user;
        localStorage.setItem('gentlemanscutRole', currentUser.role);
        localStorage.setItem('gentlemanscutUser', currentUser.email);
        localStorage.setItem('gentlemanscutName', currentUser.fullName);

        // If user was assigned Admin in database, automatically move to admin panel
        if (currentUser.role === 'admin') {
            window.location.href = 'admin.html';
            return;
        }
    } catch (err) {
        console.error('Session check failed:', err);
        // Fallback to local storage if API unreachable
        const storedRole = localStorage.getItem('gentlemanscutRole');
        const storedUser = localStorage.getItem('gentlemanscutUser');
        if (!storedUser) {
            window.location.href = 'login.html';
            return;
        }
        currentUser = {
            role: storedRole || 'customer',
            email: storedUser,
            fullName: localStorage.getItem('gentlemanscutName') || storedUser
        };
    }

    const role = currentUser.role || 'customer';
    const roleLabelMap = {
        customer: 'CUSTOMER DASHBOARD',
        barber: 'BARBER DASHBOARD',
        admin: 'ADMIN DASHBOARD'
    };

    const dashboardLabel = document.getElementById('dashboardRoleLabel');
    const dashboardGreeting = document.getElementById('dashboardGreeting');
    const dashboardSectionTitle = document.getElementById('dashboardSectionTitle');
    const dashboardActionBtn = document.getElementById('dashboardActionBtn');
    const appointmentTable = document.getElementById('appointmentTable');

    if (dashboardLabel) {
        dashboardLabel.textContent = roleLabelMap[role] || 'CUSTOMER DASHBOARD';
    }

    if (dashboardGreeting) {
        const displayName = currentUser.fullName || currentUser.email;
        const nameClass = role === 'barber' ? 'barber-name' : 'customer-name';
        dashboardGreeting.innerHTML = `Welcome, <span class="${nameClass}">${escapeHtml(displayName)}</span>`;
    }

    if (dashboardSectionTitle) {
        dashboardSectionTitle.textContent = role === 'barber' ? "Barber's Schedule" : 'My Appointments';
    }

    if (dashboardActionBtn) {
        if (role === 'barber') {
            dashboardActionBtn.style.display = 'none';
        } else {
            dashboardActionBtn.textContent = 'New Appointment';
            dashboardActionBtn.href = 'booking.html';
        }
    }

    // Update sidebar elements if present
    const sidebarUserName = document.getElementById('sidebarUserName');
    const sidebarRoleBadge = document.getElementById('sidebarRoleBadge');
    const sidebarActionLink = document.getElementById('sidebarActionLink');

    if (sidebarUserName) {
        sidebarUserName.textContent = currentUser.fullName || currentUser.email;
    }

    if (sidebarRoleBadge) {
        sidebarRoleBadge.textContent = role.toUpperCase();
        sidebarRoleBadge.className = `sidebar-role-badge badge-${role}`;
    }

    if (sidebarActionLink && role === 'barber') {
        sidebarActionLink.style.display = 'none';
    }

    // Update table header column for Barber vs Customer view
    const partyHeader = document.getElementById('dashboardColParty');
    if (partyHeader) {
        partyHeader.textContent = role === 'barber' ? 'Customer' : 'Barber';
    }

    // 2. Load live appointments from database
    if (appointmentTable) {
        appointmentTable.innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading appointments...</td></tr>';
        try {
            const res = await fetch(`api/booking.php?role=${encodeURIComponent(role)}&email=${encodeURIComponent(currentUser.email)}`);
            const data = await res.json();

            if (data.success && data.appointments && data.appointments.length > 0) {
                appointmentTable.innerHTML = '';
                data.appointments.forEach((apt) => {
                    const tr = document.createElement('tr');
                    const secondaryName = role === 'barber' ? apt.customer_name : (apt.customer_name || 'Marco');
                    const statusColor = apt.status === 'Confirmed' ? '#2ecc71' : (apt.status === 'Cancelled' ? '#e74c3c' : '#f39c12');
                    const statusCell = `<td style="color:${statusColor}; font-weight:600;">${escapeHtml(apt.status)}</td>`;

                    if (role === 'barber') {
                        tr.innerHTML = `
                            <td><span class="barber-service">${escapeHtml(apt.service)}</span></td>
                            <td><span class="customer-name">${escapeHtml(secondaryName)}</span></td>
                            <td><span class="barber-date">${escapeHtml(apt.appointment_date)}</span></td>
                            <td><span class="barber-time">${escapeHtml(apt.appointment_time)}</span></td>
                            ${statusCell}
                        `;
                    } else {
                        tr.innerHTML = `
                            <td>${escapeHtml(apt.service)}</td>
                            <td><span class="barber-name">${escapeHtml(secondaryName)}</span></td>
                            <td>${escapeHtml(apt.appointment_date)}</td>
                            <td>${escapeHtml(apt.appointment_time)}</td>
                            ${statusCell}
                        `;
                    }
                    appointmentTable.appendChild(tr);
                });
            } else {
                appointmentTable.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 20px; color: #888;">
                            No appointments found. <a href="booking.html" style="color:#d4af37;">Book your first appointment here</a>.
                        </td>
                    </tr>
                `;
            }
        } catch (err) {
            console.error('Failed to load appointments:', err);
            appointmentTable.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#e74c3c;">Failed to load appointments from server.</td></tr>';
        }
    }

    // 3. Logout handling
    const logoutLinks = document.querySelectorAll('.logout-link');
    logoutLinks.forEach((link) => {
        link.addEventListener('click', async (event) => {
            event.preventDefault();
            try {
                await fetch('api/logout.php', { method: 'POST' });
            } catch (e) {
                console.warn('Logout request failed:', e);
            }
            localStorage.removeItem('gentlemanscutUser');
            localStorage.removeItem('gentlemanscutRole');
            localStorage.removeItem('gentlemanscutName');
            window.location.href = 'login.html';
        });
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
