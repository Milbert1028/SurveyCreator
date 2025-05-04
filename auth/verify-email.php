<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// If user is already logged in and verified, redirect to dashboard
if (is_logged_in()) {
    redirect('/dashboard');
}

$token = $_GET['token'] ?? '';
$verified = false;
$message = '';

if ($token) {
    $result = verify_email($token);
    $verified = $result['success'];
    $message = $result['message'];
}

$page_title = 'Email Verification';
require_once '../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <?php if ($verified): ?>
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success display-1"></i>
                        </div>
                        <h2 class="mb-4">Email Verified!</h2>
                        <p class="mb-4"><?php echo $message; ?></p>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-primary btn-lg px-5">
                            Log In
                        </a>
                    <?php elseif ($token): ?>
                        <div class="mb-4">
                            <i class="fas fa-times-circle text-danger display-1"></i>
                        </div>
                        <h2 class="mb-4">Verification Failed</h2>
                        <p class="mb-4"><?php echo $message; ?></p>
                        <div class="d-grid gap-3">
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-primary">
                                Go to Login
                            </a>
                            <a href="<?php echo SITE_URL; ?>/auth/resend-verification.php" class="btn btn-outline-primary">
                                Resend Verification Email
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <i class="fas fa-envelope text-primary display-1"></i>
                        </div>
                        <h2 class="mb-4">Email Verification Required</h2>
                        <p class="mb-4">Please check your email for a verification link to activate your account.</p>
                        <p class="text-muted mb-4">If you didn't receive the email, check your spam folder or try resending the verification email.</p>
                        <div class="d-grid gap-3">
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-primary">
                                Go to Login
                            </a>
                            <a href="<?php echo SITE_URL; ?>/auth/resend-verification.php" class="btn btn-outline-primary">
                                Resend Verification Email
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 