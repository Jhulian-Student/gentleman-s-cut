document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    // ==========================================
    // 1. LOGIN FORM HANDLER
    // ==========================================
    if (loginForm) {
        const message = document.getElementById('loginMessage');

        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const email = document.getElementById('email')?.value.trim();
            const password = document.getElementById('password')?.value.trim();
            const submitBtn = loginForm.querySelector('button[type="submit"]');

            if (!email || !password) {
                if (message) {
                    message.textContent = 'Please enter both email and password.';
                    message.className = 'booking-error';
                }
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Logging in...';
            }

            try {
                const apiUrl = window.location.protocol === 'file:' ? 'http://localhost:8000/api/login.php' : 'api/login.php';
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    localStorage.setItem('gentlemanscutUser', result.user.email);
                    localStorage.setItem('gentlemanscutName', result.user.fullName);
                    localStorage.setItem('gentlemanscutRole', result.user.role);

                    if (message) {
                        message.textContent = `Login successful! Redirecting...`;
                        message.className = 'booking-success';
                    }

                    setTimeout(() => {
                        window.location.href = result.redirect || 'dashboard.html';
                    }, 800);
                } else {
                    if (message) {
                        message.textContent = result.message || 'Login failed. Please check your credentials.';
                        message.className = 'booking-error';
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Login';
                    }
                }
            } catch (error) {
                console.error('Login error:', error);
                if (message) {
                    message.textContent = 'Unable to connect to server. Please try again.';
                    message.className = 'booking-error';
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Login';
                }
            }
        });
    }

    // ==========================================
    // 2. REGISTER FORM HANDLER
    // ==========================================
    if (registerForm) {
        const message = document.getElementById('registerMessage');

        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const fullName = document.getElementById('fullName')?.value.trim();
            const email = document.getElementById('email')?.value.trim();
            const phone = document.getElementById('phone')?.value.trim();
            const password = document.getElementById('password')?.value;
            const confirmPassword = document.getElementById('confirmPassword')?.value;
            const role = document.getElementById('registerRole')?.value || 'customer';
            const submitBtn = registerForm.querySelector('button[type="submit"]');

            if (password !== confirmPassword) {
                if (message) {
                    message.textContent = 'Passwords do not match. Please try again.';
                    message.className = 'booking-error';
                }
                return;
            }

            if (password.length < 6) {
                if (message) {
                    message.textContent = 'Password must be at least 6 characters.';
                    message.className = 'booking-error';
                }
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creating Account...';
            }

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fullName, email, phone, password, confirmPassword, registerRole: role })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    localStorage.setItem('gentlemanscutUser', result.user.email);
                    localStorage.setItem('gentlemanscutName', result.user.fullName);
                    localStorage.setItem('gentlemanscutRole', result.user.role);

                    if (message) {
                        message.textContent = `${result.message} Redirecting to login...`;
                        message.className = 'booking-success';
                    }

                    registerForm.reset();
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 1200);
                } else {
                    if (message) {
                        message.textContent = result.message || 'Registration failed.';
                        message.className = 'booking-error';
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Create Account';
                    }
                }
            } catch (error) {
                console.error('Registration error:', error);
                if (message) {
                    message.textContent = 'Unable to connect to server. Please try again.';
                    message.className = 'booking-error';
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Create Account';
                }
            }
        });
    }
});
