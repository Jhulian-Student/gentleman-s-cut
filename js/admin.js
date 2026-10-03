document.addEventListener('DOMContentLoaded', async () => {
    const adminAccessNotice = document.getElementById('adminAccessNotice');
    const appointmentTable = document.getElementById('adminAppointmentTable');
    const serviceTable = document.getElementById('serviceTable');
    const addServiceBtn = document.getElementById('addServiceBtn');

    // 1. Verify admin authorization
    try {
        const sessionRes = await fetch('api/session.php');
        const sessionData = await sessionRes.json();

        if (!sessionData.authenticated || !sessionData.user) {
            window.location.href = 'login.html';
            return;
        }

        if (sessionData.user.role !== 'admin') {
            localStorage.setItem('gentlemanscutRole', sessionData.user.role);
            window.location.href = 'dashboard.html';
            return;
        }

        if (adminAccessNotice) {
            adminAccessNotice.textContent = `Welcome, Administrator (${sessionData.user.fullName || sessionData.user.email}).`;
        }
    } catch (err) {
        console.error('Session verification error:', err);
    }

    // 2. Load Appointments from Database
    async function loadAppointments() {
        if (!appointmentTable) return;
        appointmentTable.innerHTML = '<tr><td colspan="8" style="text-align:center;">Loading appointments...</td></tr>';

        try {
            const res = await fetch('api/booking.php');
            const data = await res.json();

            if (data.success && data.appointments && data.appointments.length > 0) {
                appointmentTable.innerHTML = '';
                data.appointments.forEach((apt) => {
                    const tr = document.createElement('tr');
                    const statusColor = apt.status === 'Confirmed' ? '#2ecc71' : (apt.status === 'Cancelled' ? '#e74c3c' : '#f39c12');

                    tr.innerHTML = `
                        <td>${apt.id}</td>
                        <td>
                            <strong class="customer-name">${escapeHtml(apt.customer_name)}</strong><br>
                            <small style="color:#888;">${escapeHtml(apt.email)} | ${escapeHtml(apt.phone)}</small>
                        </td>
                        <td>${escapeHtml(apt.service)}</td>
                        <td><span class="barber-name">Marco</span></td>
                        <td>${escapeHtml(apt.appointment_date)}</td>
                        <td>${escapeHtml(apt.appointment_time)}</td>
                        <td>
                            <select class="status-select" data-id="${apt.id}" style="padding:4px 8px; border-radius:4px; border:1px solid #444; background:#222; color:${statusColor}; font-weight:600;">
                                <option value="Pending" ${apt.status === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Confirmed" ${apt.status === 'Confirmed' ? 'selected' : ''}>Confirmed</option>
                                <option value="Cancelled" ${apt.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </td>
                        <td>
                            <button class="delete-btn btn-delete-apt" data-id="${apt.id}">Delete</button>
                        </td>
                    `;
                    appointmentTable.appendChild(tr);
                });

                // Attach status change listeners
                document.querySelectorAll('.status-select').forEach((select) => {
                    select.addEventListener('change', async (e) => {
                        const aptId = e.target.getAttribute('data-id');
                        const newStatus = e.target.value;
                        await updateAppointmentStatus(aptId, newStatus, e.target);
                    });
                });

                // Attach delete listeners
                document.querySelectorAll('.btn-delete-apt').forEach((btn) => {
                    btn.addEventListener('click', async (e) => {
                        const aptId = e.target.getAttribute('data-id');
                        if (confirm(`Are you sure you want to delete appointment #${aptId}?`)) {
                            await deleteAppointment(aptId);
                        }
                    });
                });
            } else {
                appointmentTable.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px; color:#888;">No appointments found in database.</td></tr>';
            }
        } catch (err) {
            console.error('Failed to load appointments:', err);
            appointmentTable.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#e74c3c;">Failed to load appointments from server.</td></tr>';
        }
    }

    async function updateAppointmentStatus(id, status, selectElement) {
        try {
            const res = await fetch('api/admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_status', id: parseInt(id), status })
            });
            const result = await res.json();
            if (result.success) {
                const color = status === 'Confirmed' ? '#2ecc71' : (status === 'Cancelled' ? '#e74c3c' : '#f39c12');
                selectElement.style.color = color;
            } else {
                alert(result.message || 'Status update failed.');
            }
        } catch (err) {
            console.error('Status update failed:', err);
            alert('Failed to connect to server.');
        }
    }

    async function deleteAppointment(id) {
        try {
            const res = await fetch('api/admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_appointment', id: parseInt(id) })
            });
            const result = await res.json();
            if (result.success) {
                loadAppointments();
            } else {
                alert(result.message || 'Failed to delete appointment.');
            }
        } catch (err) {
            console.error('Delete error:', err);
            alert('Network error while deleting appointment.');
        }
    }

    // 3. Load Services from Database
    async function loadServices() {
        if (!serviceTable) return;
        serviceTable.innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading services...</td></tr>';

        try {
            const res = await fetch('api/services.php');
            const data = await res.json();

            if (data.success && data.services && data.services.length > 0) {
                serviceTable.innerHTML = '';
                data.services.forEach((srv) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${srv.id}</td>
                        <td>${escapeHtml(srv.name)}</td>
                        <td>₱${parseFloat(srv.price).toFixed(2)}</td>
                        <td>${escapeHtml(srv.duration)}</td>
                        <td>
                            <button class="delete-btn btn-delete-srv" data-id="${srv.id}">Delete</button>
                        </td>
                    `;
                    serviceTable.appendChild(tr);
                });

                document.querySelectorAll('.btn-delete-srv').forEach((btn) => {
                    btn.addEventListener('click', async (e) => {
                        const srvId = e.target.getAttribute('data-id');
                        if (confirm('Delete this service from the database?')) {
                            await deleteService(srvId);
                        }
                    });
                });
            } else {
                serviceTable.innerHTML = '<tr><td colspan="5" style="text-align:center;">No services found.</td></tr>';
            }
        } catch (err) {
            console.error('Failed to load services:', err);
            serviceTable.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#e74c3c;">Failed to load services from server.</td></tr>';
        }
    }

    async function deleteService(id) {
        try {
            const res = await fetch('api/services.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(id) })
            });
            const result = await res.json();
            if (result.success) {
                loadServices();
            } else {
                alert(result.message || 'Failed to delete service.');
            }
        } catch (err) {
            console.error('Error deleting service:', err);
            alert('Server error while deleting service.');
        }
    }

    // 4. Add Service Button Handler
    if (addServiceBtn) {
        addServiceBtn.addEventListener('click', async () => {
            const name = prompt('Enter service name (e.g., Hot Towel Shave):');
            if (!name) return;

            const price = prompt('Enter service price in PHP (e.g., 200):');
            if (!price || isNaN(price)) {
                alert('Please enter a valid numeric price.');
                return;
            }

            const duration = prompt('Enter duration (e.g., 25 minutes):') || '30 minutes';

            try {
                const res = await fetch('api/services.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, price: parseFloat(price), duration })
                });
                const result = await res.json();
                if (result.success) {
                    alert(result.message);
                    loadServices();
                } else {
                    alert(result.message || 'Failed to add service.');
                }
            } catch (err) {
                console.error('Add service error:', err);
                alert('Failed to connect to server.');
            }
        });
    }

    // 5. Logout links
    document.querySelectorAll('.logout-link').forEach((link) => {
        link.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await fetch('api/logout.php', { method: 'POST' });
            } catch (err) {}
            localStorage.clear();
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

    // Initial load
    loadAppointments();
    loadServices();
});
