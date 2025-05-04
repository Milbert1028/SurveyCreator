/**
 * Enhanced UI JavaScript
 * Adds modern interactions, animations and improved usability to the Survey System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all enhanced UI components
    initAnimations();
    initTooltips();
    initToasts();
    initCopyButtons();
    initDropdownAnimations();
    initCardHoverEffects();
    initFormEnhancements();
    initNavigationHighlight();
    initSmoothScrolling();
    initBackToTop();
    initCustomAlerts();
    
    // Add .fade-in class to the main content container
    const mainContent = document.querySelector('.container');
    if (mainContent) {
        mainContent.classList.add('fade-in');
    }
});

/**
 * Initialize entrance animations for elements
 */
function initAnimations() {
    // Animate elements as they enter the viewport
    const animatedElements = document.querySelectorAll('.card, .stat-card, .chart-container');
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        animatedElements.forEach(el => {
            observer.observe(el);
        });
    } else {
        // Fallback for browsers that don't support IntersectionObserver
        animatedElements.forEach(el => {
            el.classList.add('fade-in');
        });
    }
}

/**
 * Initialize Bootstrap tooltips with enhanced styling
 */
function initTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 300, hide: 100 }
        });
    });
}

/**
 * Initialize enhanced toast notifications
 */
function initToasts() {
    // Create toast container if it doesn't exist
    if (!document.querySelector('.toast-container')) {
        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
}

/**
 * Show a custom toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type of toast (success, error, info, warning)
 * @param {number} duration - Duration in milliseconds
 */
function showToast(message, type = 'info', duration = 3000) {
    const toastContainer = document.querySelector('.toast-container');
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'custom-toast';
    
    // Choose icon based on type
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    // Set toast content
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-${icon}"></i>
            <span class="toast-title">${type.charAt(0).toUpperCase() + type.slice(1)}</span>
            <button type="button" class="btn-close btn-sm"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    // Append and animate
    toastContainer.appendChild(toast);
    
    // Animate entrance
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Close button functionality
    toast.querySelector('.btn-close').addEventListener('click', () => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    });
    
    // Auto close after duration
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, duration);
    
    return toast;
}

/**
 * Initialize copy buttons with animation
 */
function initCopyButtons() {
    const copyButtons = document.querySelectorAll('.copy-btn');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-copy-target');
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                // Select and copy text
                targetElement.select();
                document.execCommand('copy');
                
                // Show animation
                this.classList.add('copied');
                
                // Show toast notification
                showToast('Copied to clipboard', 'success');
                
                // Reset button after animation
                setTimeout(() => {
                    this.classList.remove('copied');
                }, 1500);
            }
        });
    });
}

/**
 * Add smooth animations to dropdown menus
 */
function initDropdownAnimations() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (menu) {
            dropdown.addEventListener('show.bs.dropdown', function() {
                menu.classList.add('fade-in');
            });
        }
    });
}

/**
 * Initialize enhanced card hover effects
 */
function initCardHoverEffects() {
    const cards = document.querySelectorAll('.card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = 'var(--shadow-lg)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
}

/**
 * Add enhancements to form elements
 */
function initFormEnhancements() {
    // Add floating label effect
    const formControls = document.querySelectorAll('.form-control');
    
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            
            // Keep the focused class if the input has a value
            if (this.value.trim() !== '') {
                this.parentElement.classList.add('has-value');
            } else {
                this.parentElement.classList.remove('has-value');
            }
        });
        
        // Initialize with has-value class if needed
        if (control.value.trim() !== '') {
            control.parentElement.classList.add('has-value');
        }
    });
}

/**
 * Highlight active navigation items
 */
function initNavigationHighlight() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        
        // Check if this link corresponds to the current page
        if (href && currentPath.includes(href) && href !== '/') {
            link.classList.add('active');
        } else if (href === '/' && currentPath === '/') {
            link.classList.add('active');
        }
    });
}

/**
 * Initialize smooth scrolling
 */
function initSmoothScrolling() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 70, // Adjust for fixed header
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Add a back-to-top button
 */
function initBackToTop() {
    // Create the back-to-top button
    const backToTopButton = document.createElement('button');
    backToTopButton.className = 'back-to-top';
    backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
    document.body.appendChild(backToTopButton);
    
    // Show/hide based on scroll position
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTopButton.classList.add('show');
        } else {
            backToTopButton.classList.remove('show');
        }
    });
    
    // Scroll to top when clicked
    backToTopButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Add styles
    const style = document.createElement('style');
    style.textContent = `
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .back-to-top.show {
            opacity: 1;
            transform: translateY(0);
        }
        .back-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        @media (max-width: 576px) {
            .back-to-top {
                bottom: 20px;
                right: 20px;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Replace browser alerts with custom styled alerts
 */
function initCustomAlerts() {
    // Override the default alert function
    const originalAlert = window.alert;
    window.alert = function(message) {
        showCustomAlert(message);
    };
    
    /**
     * Display a custom alert dialog
     * @param {string} message - Message to display
     */
    function showCustomAlert(message) {
        // Check if there's already an alert
        if (document.querySelector('.custom-alert-overlay')) {
            return;
        }
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'custom-alert-overlay';
        
        // Create alert box
        const alertBox = document.createElement('div');
        alertBox.className = 'custom-alert-box';
        
        // Set content
        alertBox.innerHTML = `
            <div class="custom-alert-content">
                <p>${message}</p>
                <button class="btn btn-primary">OK</button>
            </div>
        `;
        
        // Add to DOM
        overlay.appendChild(alertBox);
        document.body.appendChild(overlay);
        
        // Add focus to the button
        const okButton = alertBox.querySelector('button');
        okButton.focus();
        
        // Handle close
        okButton.addEventListener('click', function() {
            overlay.classList.add('closing');
            setTimeout(() => {
                overlay.remove();
            }, 300);
        });
        
        // Close on escape key
        document.addEventListener('keydown', function closeOnEscape(e) {
            if (e.key === 'Escape') {
                overlay.classList.add('closing');
                setTimeout(() => {
                    overlay.remove();
                }, 300);
                document.removeEventListener('keydown', closeOnEscape);
            }
        });
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .custom-alert-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                animation: fadeIn 0.3s ease-out forwards;
            }
            .custom-alert-overlay.closing {
                animation: fadeOut 0.3s ease-out forwards;
            }
            .custom-alert-box {
                background-color: white;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-lg);
                width: 90%;
                max-width: 400px;
                padding: 1.5rem;
                animation: slideUp 0.3s ease-out forwards;
            }
            .custom-alert-overlay.closing .custom-alert-box {
                animation: slideDown 0.3s ease-out forwards;
            }
            .custom-alert-content p {
                margin-bottom: 1.25rem;
                font-size: 1rem;
                line-height: 1.5;
                color: var(--gray-800);
            }
            .custom-alert-content button {
                display: block;
                margin: 0 auto;
                min-width: 100px;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
            @keyframes slideUp {
                from { 
                    opacity: 0;
                    transform: translateY(20px);
                }
                to { 
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            @keyframes slideDown {
                from { 
                    opacity: 1;
                    transform: translateY(0);
                }
                to { 
                    opacity: 0;
                    transform: translateY(20px);
                }
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Initialize form validation with enhanced UI feedback
 * @param {string} formSelector - CSS selector for the form
 */
function initFormValidation(formSelector) {
    const form = document.querySelector(formSelector);
    
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        this.classList.add('was-validated');
        
        // Add custom error messages
        const invalidInputs = this.querySelectorAll(':invalid');
        invalidInputs.forEach(input => {
            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                input.classList.add('highlight-error');
                
                // Shake effect
                input.classList.add('shake');
                setTimeout(() => {
                    input.classList.remove('shake');
                }, 600);
            }
        });
    }, false);
    
    // Add styles for enhanced validation feedback
    const style = document.createElement('style');
    style.textContent = `
        .highlight-error {
            border-color: var(--danger) !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        .shake {
            animation: shake 0.6s ease-in-out;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-5px); }
            80% { transform: translateX(5px); }
            100% { transform: translateX(0); }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Add a dynamic loading spinner
 * @param {HTMLElement} container - Container to center the spinner in
 * @param {string} message - Optional loading message
 * @returns {HTMLElement} - The spinner element
 */
function showLoadingSpinner(container, message = 'Loading...') {
    // Create spinner
    const spinnerContainer = document.createElement('div');
    spinnerContainer.className = 'spinner-container';
    
    spinnerContainer.innerHTML = `
        <div class="spinner"></div>
        <p class="spinner-message">${message}</p>
    `;
    
    // Add to container
    container.appendChild(spinnerContainer);
    
    // Add styles
    const style = document.createElement('style');
    style.textContent = `
        .spinner-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            width: 100%;
        }
        .spinner-message {
            margin-top: 1rem;
            color: var(--gray-600);
        }
    `;
    document.head.appendChild(style);
    
    // Return the spinner element so it can be removed later
    return spinnerContainer;
}

/**
 * Remove the loading spinner
 * @param {HTMLElement} spinner - The spinner element to remove
 */
function removeLoadingSpinner(spinner) {
    if (spinner && spinner.parentNode) {
        spinner.classList.add('fade-out');
        
        setTimeout(() => {
            spinner.remove();
        }, 300);
    }
}

// Export functions for global use
window.showToast = showToast;
window.initFormValidation = initFormValidation;
window.showLoadingSpinner = showLoadingSpinner;
window.removeLoadingSpinner = removeLoadingSpinner; 