/**
 * Jewellery Shop — Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // ---------- Mobile Menu Toggle ----------
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function () {
            navLinks.classList.toggle('open');
        });

        // Close menu when a link is clicked (mobile)
        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navLinks.classList.remove('open');
            });
        });
    }

    // ---------- Client-side Form Validation ----------
    const forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');

            // Clear previous error styles
            form.querySelectorAll('.input-error').forEach(function (el) {
                el.classList.remove('input-error');
            });

            requiredFields.forEach(function (field) {
                const value = field.value.trim();

                if (!value) {
                    isValid = false;
                    field.classList.add('input-error');
                }

                // Email validation
                if (field.type === 'email' && value) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(value)) {
                        isValid = false;
                        field.classList.add('input-error');
                    }
                }

                // Minimum length for password
                if (field.type === 'password' && value && value.length < 6) {
                    isValid = false;
                    field.classList.add('input-error');
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });
    });

    // ---------- Remove error style on input ----------
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('input-error')) {
            e.target.classList.remove('input-error');
        }
    });

    // ---------- Smooth scroll for anchor links ----------
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

});