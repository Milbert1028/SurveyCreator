<?php
require_once 'includes/config.php';

// Get error code
$error_code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_NUMBER_INT) ?: 404;

// Set error details
switch ($error_code) {
    case 403:
        $title = 'Access Denied';
        $message = 'You do not have permission to access this resource.';
        break;
    case 500:
        $title = 'Server Error';
        $message = 'The server encountered an internal error and was unable to complete your request.';
        break;
    case 404:
    default:
        $title = 'Page Not Found';
        $message = 'The page you are looking for does not exist or has been moved.';
        $error_code = 404; // Default to 404
        break;
}

// Set the HTTP status code
http_response_code($error_code);

require_once 'templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-template">
                <h1 class="display-1"><?php echo $error_code; ?></h1>
                <h2 class="mb-4"><?php echo $title; ?></h2>
                <div class="error-details mb-4">
                    <?php echo $message; ?>
                </div>
                <div class="error-actions">
                    <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Return Home
                    </a>
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo SITE_URL; ?>/dashboard" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-tachometer-alt me-2"></i>Back to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 