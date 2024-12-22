// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const inputs = form.querySelectorAll('input[required]');

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            showError(input, 'Field ini harus diisi');
        } else {
            clearError(input);
        }

        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                isValid = false;
                showError(input, 'Email tidak valid');
            }
        }

        // Password validation
        if (input.type === 'password' && input.value) {
            if (input.value.length < 8) {
                isValid = false;
                showError(input, 'Password minimal 8 karakter');
            }
        }
    });

    return isValid;
}

function showError(input, message) {
    const formGroup = input.closest('.form-group');
    const error = formGroup.querySelector('.error-message') || document.createElement('div');
    error.className = 'error-message text-danger';
    error.textContent = message;
    
    if (!formGroup.querySelector('.error-message')) {
        formGroup.appendChild(error);
    }
    
    input.classList.add('is-invalid');
}

function clearError(input) {
    const formGroup = input.closest('.form-group');
    const error = formGroup.querySelector('.error-message');
    if (error) {
        error.remove();
    }
    input.classList.remove('is-invalid');
}

// OTP Input Handler
function setupOTPInput() {
    const otpInput = document.getElementById('otp-input');
    if (!otpInput) return;

    otpInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 6) {
            this.value = this.value.slice(0, 6);
        }
    });
}

// Copy to Clipboard
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    // Show feedback
    const feedback = document.createElement('div');
    feedback.className = 'copy-feedback';
    feedback.textContent = 'Berhasil disalin!';
    document.body.appendChild(feedback);
    
    setTimeout(() => {
        feedback.remove();
    }, 2000);
}

// Dashboard Charts (jika diperlukan)
function initDashboardCharts() {
    // Implementasi chart jika diperlukan
}

// Auto Logout Timer
let logoutTimer;

function resetLogoutTimer() {
    clearTimeout(logoutTimer);
    logoutTimer = setTimeout(() => {
        window.location.href = '/auth/logout.php';
    }, 30 * 60 * 1000); // 30 menit
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Setup OTP input if exists
    setupOTPInput();
    
    // Setup logout timer
    if (document.querySelector('.dashboard-container')) {
        resetLogoutTimer();
        document.addEventListener('mousemove', resetLogoutTimer);
        document.addEventListener('keypress', resetLogoutTimer);
    }
    
    // Setup form validation
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this.id)) {
                e.preventDefault();
            }
        });
    });
});