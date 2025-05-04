<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verify CSRF token if using POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash_message("Invalid request", "danger");
        redirect('/dashboard');
    }
}

// Perform logout
logout_user();

// Redirect to login page with success message
flash_message("You have been successfully logged out", "success");
redirect('/auth/login.php');
