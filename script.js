/**
 * Common JavaScript Utilities & Interactivity
 * Project: Student Feedback and Course Evaluation System
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Auto-dismiss flash alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // 2. Generic delete/action confirmation prompt
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
            if (!confirm(msg)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // 3. Password visibility toggle buttons
    const togglePasswordBtns = document.querySelectorAll('.toggle-password-visibility');
    togglePasswordBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetInputId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetInputId);
            const icon = this.querySelector('i');
            
            if (targetInput) {
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                } else {
                    targetInput.type = 'password';
                    if (icon) {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                }
            }
        });
    });

    // 4. Client-side Form Validation using HTML5 Bootstrap classes
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 5. Comment Character Counters
    const commentInputs = document.querySelectorAll('textarea[data-counter]');
    commentInputs.forEach(function (textarea) {
        const counterId = textarea.getAttribute('data-counter');
        const counterEl = document.getElementById(counterId);
        const maxLen = textarea.getAttribute('maxlength') || 500;
        
        if (counterEl) {
            const updateCount = () => {
                const remaining = maxLen - textarea.value.length;
                counterEl.textContent = `${textarea.value.length} / ${maxLen} characters`;
                if (remaining < 20) {
                    counterEl.classList.add('text-danger');
                } else {
                    counterEl.classList.remove('text-danger');
                }
            };
            textarea.addEventListener('input', updateCount);
            updateCount();
        }
    });
});

