// Main JavaScript file for common functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Handle flash message dismissal
    var flashMessages = document.querySelectorAll('.alert-dismissible');
    flashMessages.forEach(function(flash) {
        var closeButton = flash.querySelector('.btn-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                flash.remove();
            });
        }
        // Auto-hide after 5 seconds
        setTimeout(function() {
            if (flash && flash.parentNode) {
                flash.remove();
            }
        }, 5000);
    });
});
