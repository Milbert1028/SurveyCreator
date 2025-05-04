<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// If user is already logged in and verified, redirect to dashboard
if (is_logged_in()) {
    redirect('/dashboard');
}

$messages = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verify_csrf_token($csrf_token)) {
        $messages[] = [
            'type' => 'danger',
            'text' => 'Invalid request. Please try again.'
        ];
    }
    // Validate email
    elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messages[] = [
            'type' => 'danger',
            'text' => 'Please enter a valid email address.'
        ];
    }
    else {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Find the user
        $email_escaped = $db->escape($email);
        $sql = "SELECT id, email_verified FROM users WHERE email = '$email_escaped' LIMIT 1";
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if email is already verified
            if ($user['email_verified'] == 1) {
                $messages[] = [
                    'type' => 'info',
                    'text' => 'Your email is already verified. You can now <a href="' . SITE_URL . '/auth/login.php">log in</a>.'
                ];
            } else {
                // Resend verification email
                if (create_email_verification($user['id'], $email)) {
                    $messages[] = [
                        'type' => 'success',
                        'text' => 'Verification email has been sent. Please check your inbox and click on the verification link.'
                    ];
                } else {
                    $messages[] = [
                        'type' => 'danger',
                        'text' => 'Failed to send verification email. Please try again later.'
                    ];
                }
            }
        } else {
            // Don't reveal if the email exists or not for security
            $messages[] = [
                'type' => 'success',
                'text' => 'If the email address is registered, a verification email has been sent. Please check your inbox.'
            ];
        }
    }
}

$page_title = 'Resend Verification Email';
require_once '../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Resend Verification Email</h2>
                    
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?php echo $message['type']; ?> mb-4">
                            <?php echo $message['text']; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                            <div class="form-text">Enter the email you used to register.</div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">Resend Verification Email</button>
                        </div>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 