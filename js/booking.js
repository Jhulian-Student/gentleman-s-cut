document.addEventListener('DOMContentLoaded', async () => {
    const bookingForm = document.getElementById('bookingForm');
    const bookingMessage = document.getElementById('bookingMessage');

    if (!bookingForm || !bookingMessage) {
        return;
    }

    // Auto-fill logged in user info if available
    try {
        const sessionRes = await fetch('api/session.php');
        const sessionData = await sessionRes.json();
        if (sessionData.authenticated && sessionData.user) {
            const nameInput = document.getElementById('customerName');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');

            if (nameInput && !nameInput.value) nameInput.value = sessionData.user.fullName;
            if (emailInput && !emailInput.value) emailInput.value = sessionData.user.email;
            if (phoneInput && !phoneInput.value) phoneInput.value = sessionData.user.phone;
        }
    } catch (err) {
        // Fallback to localStorage if offline
        const localUser = localStorage.getItem('gentlemanscutUser');
        const localName = localStorage.getItem('gentlemanscutName');
        if (localName) document.getElementById('customerName').value = localName;
        if (localUser) document.getElementById('email').value = localUser;
    }

    bookingForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const customerName = document.getElementById('customerName')?.value.trim();
        const email = document.getElementById('email')?.value.trim();
        const phone = document.getElementById('phone')?.value.trim();
        const service = document.getElementById('service')?.value;
        const appointmentDate = document.getElementById('appointmentDate')?.value;
        const appointmentTime = document.getElementById('appointmentTime')?.value;
        const notes = document.getElementById('notes')?.value.trim() || '';
        const submitBtn = bookingForm.querySelector('button[type="submit"]');

        if (!customerName || !email || !phone || !service || !appointmentDate || !appointmentTime) {
            bookingMessage.textContent = 'Please fill out all required fields.';
            bookingMessage.className = 'booking-error';
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Booking...';
        }

        try {
            const response = await fetch('api/booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customerName,
                    email,
                    phone,
                    service,
                    appointmentDate,
                    appointmentTime,
                    notes
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                bookingMessage.textContent = result.message;
                bookingMessage.className = 'booking-success';
                bookingForm.reset();
            } else {
                bookingMessage.textContent = result.message || 'Booking failed. Please try again.';
                bookingMessage.className = 'booking-error';
            }
        } catch (error) {
            console.error('Booking error:', error);
            bookingMessage.textContent = 'Could not connect to booking server. Please try again later.';
            bookingMessage.className = 'booking-error';
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirm Appointment';
            }
        }
    });
});
