// ===== Mobile Menu Toggle =====
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileNav = document.getElementById('mobileNav');
const closeMobileNav = document.getElementById('closeMobileNav');

if (mobileMenuBtn && mobileNav && closeMobileNav) {
    mobileMenuBtn.addEventListener('click', () => {
        mobileNav.classList.add('active');
    });

    closeMobileNav.addEventListener('click', () => {
        mobileNav.classList.remove('active');
    });

    // Close mobile nav when clicking outside
    mobileNav.addEventListener('click', (e) => {
        if (e.target === mobileNav) {
            mobileNav.classList.remove('active');
        }
    });
}

// ===== Password Visibility Toggle =====
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = event.currentTarget;
    const icon = button.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// ===== Form Validation =====
document.addEventListener('DOMContentLoaded', () => {
    // Clear previous error states
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const errorFields = this.querySelectorAll('.form-group.error');
            errorFields.forEach(field => field.classList.remove('error'));
        });
    });

    // Date and time validation
    const dateInput = document.getElementById('date');
    const startTimeInput = document.getElementById('startTime');
    const endTimeInput = document.getElementById('endTime');

    if (dateInput && startTimeInput && endTimeInput) {
        function validateTimeSelection() {
            const date = dateInput.value;
            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;

            if (date && startTime && endTime) {
                const startHour = parseInt(startTime.split(':')[0]);
                const endHour = parseInt(endTime.split(':')[0]);
                if (endHour <= startHour) {
                    endTimeInput.setCustomValidity('L\'heure de fin doit être après l\'heure de début');
                } else {
                    endTimeInput.setCustomValidity('');
                }
            }
        }

        dateInput.addEventListener('change', validateTimeSelection);
        startTimeInput.addEventListener('change', validateTimeSelection);
        endTimeInput.addEventListener('change', validateTimeSelection);
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Table row highlighting
    const tableRows = document.querySelectorAll('.reservations-table tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(74, 107, 255, 0.05)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Profile picture preview
    const profilePictureInput = document.getElementById('profilePicture');
    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePicturePreview');
                    if (preview) preview.innerHTML = `<img src="${e.target.result}" alt="Aperçu">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

// ===== Console Welcome =====
console.log('%c🚀 Plateforme de Réservation de Salles', 'color: #4a6bff; font-size: 16px; font-weight: bold;');
console.log('%c💡 Node.js + Express + SQLite', 'color: #6c757d; font-size: 12px;');