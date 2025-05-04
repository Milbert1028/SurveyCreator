<?php
// WARNING: This file is for development/diagnostic purposes only
// REMOVE FROM PRODUCTION SERVER AFTER DEBUGGING

// Security check - comment this out when you need to use the file
$allowed_ips = ['127.0.0.1', '::1']; // localhost
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!in_array($client_ip, $allowed_ips)) {
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied. This file should only be accessed locally.";
    exit;
}

// Output PHP information
phpinfo(); 