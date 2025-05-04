<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('/dashboard');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid request";
    }
    // Validate email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    // Validate password
    elseif (empty($password)) {
        $errors[] = "Password is required";
    }
    // Attempt login
    else {
        $login_result = login_user($email, $password);
        
        if ($login_result['success']) {
            flash_message("Welcome back!", "success");
            redirect('/dashboard');
        } else {
            if (isset($login_result['reason']) && $login_result['reason'] === 'unverified_email') {
                $resend_url = SITE_URL . '/auth/resend-verification.php?email=' . urlencode($email);
                $errors[] = $login_result['message'] . ' <div class="mt-2"><a href="' . $resend_url . '" class="btn btn-primary btn-sm">Resend Verification Email</a></div>';
            } else {
                $errors[] = $login_result['message'];
            }
        }
    }
}

require_once '../templates/header.php';
?>

<div class="auth-background position-fixed"></div>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg animate__animated animate__fadeInDown">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="app-icon-wrapper mb-3 animate__animated animate__pulse animate__infinite animate__slower">
                            <i class="fas fa-poll fa-3x text-primary"></i>
                        </div>
                        <h1 class="h3 fw-bold"><?php echo APP_NAME; ?></h1>
                        <p class="text-muted">Welcome back! Please sign in to your account</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger animate__animated animate__headShake">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <?php if (strpos($error, 'btn-primary') !== false): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php else: ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo SITE_URL; ?>/auth/login.php" class="login-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="name@example.com" autocomplete="email" required>
                            <label for="email">
                                <i class="fas fa-envelope me-2 text-muted"></i>Email address
                            </label>
                        </div>

                        <div class="form-floating mb-3 password-field">
                            <input type="password" class="form-control form-control-lg" id="password" name="password" 
                                   placeholder="Password" autocomplete="current-password" required>
                            <label for="password">
                                <i class="fas fa-lock me-2 text-muted"></i>Password
                            </label>
                            <span class="password-toggle" onclick="togglePasswordVisibility()">
                                <i class="fas fa-eye" id="password-toggle-icon"></i>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/auth/forgot-password.php" class="text-decoration-none">
                                <i class="fas fa-question-circle me-1"></i>Forgot password?
                            </a>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg signin-btn">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">Don't have an account? 
                            <a href="<?php echo SITE_URL; ?>/auth/register.php" class="text-decoration-none fw-bold">
                                Create one <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-background {
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -1;
    background: linear-gradient(135deg, rgba(78,115,223,0.05) 0%, rgba(34,74,190,0.1) 100%);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;
}

@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.app-icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 10px 20px rgba(78,115,223,0.3);
    color: white;
}

.card {
    border-radius: 15px;
    transition: all 0.3s ease;
    overflow: hidden;
}

.form-control {
    border-radius: 8px;
    transition: box-shadow 0.3s ease, transform 0.2s ease;
}

.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(78,115,223,0.25);
    transform: translateY(-2px);
}

.password-field {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #6c757d;
    z-index: 10;
}

.signin-btn {
    transition: all 0.3s ease;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    box-shadow: 0 4px 15px rgba(78,115,223,0.4);
}

.signin-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(78,115,223,0.5);
}

.signin-btn:active {
    transform: translateY(0);
}

a {
    transition: all 0.3s ease;
    color: #4e73df;
}

a:hover {
    color: #224abe;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Apply focus effect when clicking on form field containers
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
    
    // Add animation to form on load
    const formElements = document.querySelectorAll('.login-form .form-floating, .login-form .form-check, .login-form .d-grid');
    formElements.forEach((element, index) => {
        element.classList.add('animate__animated', 'animate__fadeInUp');
        element.style.animationDelay = `${index * 0.1 + 0.2}s`;
    });
});

// Toggle password visibility
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('password-toggle-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php require_once '../templates/footer.php'; ?>
