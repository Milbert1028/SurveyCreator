<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('/dashboard');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize Database connection for error handling
    $db = Database::getInstance();
    
    // Retrieve and sanitize form inputs
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    $terms = isset($_POST['terms']) ? true : false;

    try {
        // Validate CSRF token
        if (!verify_csrf_token($csrf_token)) {
            $errors[] = "Invalid request. Please try again.";
        }
        // Validate terms acceptance
        elseif (!$terms) {
            $errors[] = "You must agree to the Terms of Service and Privacy Policy";
        }
        // Validate username
        elseif (empty($username)) {
            $errors[] = "Username is required";
        }
        elseif (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long";
        }
        elseif (strlen($username) > 50) {
            $errors[] = "Username cannot exceed 50 characters";
        }
        elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, and the characters . _ -";
        }
        
        // Validate email - enhanced validation
        elseif (empty($email)) {
            $errors[] = "Email address is required";
        }
        elseif (!validate_email($email)) {
            $errors[] = "Invalid email format. Please enter a valid email address";
        }
        elseif (strlen($email) > 100) {
            $errors[] = "Email address is too long";
        }
        
        // Validate password
        elseif (empty($password)) {
            $errors[] = "Password is required";
        }
        elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        elseif (strlen($password) > 72) {
            $errors[] = "Password cannot exceed 72 characters";
        }
        elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        elseif ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        // Attempt registration
        else {
            // Check if email already exists
            $email_check = $db->query("SELECT id FROM users WHERE email = '" . $db->escape($email) . "'");
            if ($email_check && $email_check->num_rows > 0) {
                $errors[] = "Email already registered. Please use a different email or try to recover your password";
            }
            // Check if username already exists
            else {
                $username_check = $db->query("SELECT id FROM users WHERE username = '" . $db->escape($username) . "'");
                if ($username_check && $username_check->num_rows > 0) {
                    $errors[] = "Username already taken. Please choose a different username";
                }
                else {
                    // Attempt to register the user
                    $registration_result = register_user($username, $email, $password);
                    if ($registration_result === true) {
                        // Check if email verification is required
                        if (defined('REQUIRE_EMAIL_VERIFICATION') && REQUIRE_EMAIL_VERIFICATION === false) {
                            // Auto login if email verification is not required
                            $login_result = login_user($email, $password);
                            if ($login_result['success']) {
                                flash_message("Registration successful! Welcome to " . APP_NAME, "success");
                                redirect('/dashboard');
                            } else {
                                flash_message("Account created successfully. Please log in with your credentials.", "success");
                                redirect('/auth/login.php');
                            }
                        } else {
                            // Redirect to verification page if email verification is required
                            flash_message("Registration successful! Please check your email to verify your account.", "success");
                            redirect('/auth/verify-email.php');
                        }
                    } else {
                        // If the result is a string, it's an error message
                        if (is_string($registration_result)) {
                            if (strpos($registration_result, "Unknown column 'email_verified'") !== false) {
                                $errors[] = "Database schema needs to be updated. Please run the <a href='../update-database.php'>database update script</a> first.";
                            } else {
                                $errors[] = $registration_result;
                            }
                        } else {
                            $errors[] = "Registration failed. Please try again later.";
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Catch any unexpected errors during registration
        $errors[] = "An unexpected error occurred: " . $e->getMessage();
        error_log("Registration error: " . $e->getMessage());
    }
}

/**
 * Enhanced email validation function
 * 
 * @param string $email Email to validate
 * @return bool True if email is valid, false otherwise
 */
function validate_email($email) {
    // First use PHP's filter_var for basic format check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Check for valid email structure
    $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    if (!preg_match($pattern, $email)) {
        return false;
    }
    
    // Check that domain has DNS records (MX or A records)
    $domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
        return false;
    }
    
    return true;
}

require_once '../templates/header.php';
?>

<div class="auth-background position-fixed"></div>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card border-0 shadow-lg animate__animated animate__fadeInUp">
                <div class="card-header bg-primary text-white p-4 rounded-top">
                    <div class="text-center">
                        <div class="app-icon-wrapper mb-3 animate__animated animate__pulse animate__infinite animate__slower d-inline-block">
                            <i class="fas fa-user-plus fa-2x text-white"></i>
                        </div>
                        <h2 class="fw-bold mb-1">Join <?php echo APP_NAME; ?></h2>
                        <p class="mb-0">Create your account and start making amazing surveys</p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="register-progress mb-4">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" id="registerProgress"></div>
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm animate__animated animate__headShake">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                </div>
                                <div>
                                    <h5 class="alert-heading">Please fix the following issues:</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo SITE_URL; ?>/auth/register.php" id="registerForm" class="register-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="form-group mb-3 form-item" data-step="1">
                            <label for="username" class="form-label fw-bold">
                                <i class="fas fa-user me-2 text-primary"></i>Username
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-at text-primary"></i>
                                </span>
                                <input type="text" class="form-control form-control-lg" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                       placeholder="Choose a username" required>
                            </div>
                            <div class="form-text mt-2">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                3-50 characters, letters, numbers, and ._-
                            </div>
                        </div>

                        <div class="form-group mb-3 form-item" data-step="1">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope me-2 text-primary"></i>Email Address
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-envelope text-primary"></i>
                                </span>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       placeholder="Enter your email" required>
                            </div>
                            <div class="form-text mt-2">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                We'll send a verification link to this address
                            </div>
                        </div>

                        <div class="form-group mb-3 form-item" data-step="2">
                            <label for="password" class="form-label fw-bold">
                                <i class="fas fa-lock me-2 text-primary"></i>Create Password
                            </label>
                            <div class="input-group password-field">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-key text-primary"></i>
                                </span>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" 
                                       placeholder="Create a secure password" required>
                                <span class="password-toggle" onclick="togglePasswordVisibility('password')">
                                    <i class="fas fa-eye" id="password-toggle-icon"></i>
                                </span>
                            </div>
                            
                            <div class="password-strength mt-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Password Strength:</span>
                                    <span class="strength-text text-muted small">None</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%;" id="passwordStrength"></div>
                                </div>
                                <div class="password-requirements mt-3">
                                    <div class="requirement d-flex align-items-center mb-1" data-requirement="length">
                                        <span class="requirement-icon me-2">
                                            <i class="fas fa-circle-notch text-muted"></i>
                                        </span>
                                        <span class="small">At least 8 characters</span>
                                    </div>
                                    <div class="requirement d-flex align-items-center mb-1" data-requirement="uppercase">
                                        <span class="requirement-icon me-2">
                                            <i class="fas fa-circle-notch text-muted"></i>
                                        </span>
                                        <span class="small">At least 1 uppercase letter</span>
                                    </div>
                                    <div class="requirement d-flex align-items-center mb-1" data-requirement="lowercase">
                                        <span class="requirement-icon me-2">
                                            <i class="fas fa-circle-notch text-muted"></i>
                                        </span>
                                        <span class="small">At least 1 lowercase letter</span>
                                    </div>
                                    <div class="requirement d-flex align-items-center" data-requirement="number">
                                        <span class="requirement-icon me-2">
                                            <i class="fas fa-circle-notch text-muted"></i>
                                        </span>
                                        <span class="small">At least 1 number</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4 form-item" data-step="2">
                            <label for="confirm_password" class="form-label fw-bold">
                                <i class="fas fa-lock me-2 text-primary"></i>Confirm Password
                            </label>
                            <div class="input-group password-field">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-shield-alt text-primary"></i>
                                </span>
                                <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm your password" required>
                                <span class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                                    <i class="fas fa-eye" id="confirm-password-toggle-icon"></i>
                                </span>
                            </div>
                            <div id="passwordMatch" class="form-text mt-2 d-none">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Passwords match
                            </div>
                            <div id="passwordMismatch" class="form-text mt-2 text-danger d-none">
                                <i class="fas fa-times-circle me-1"></i>
                                Passwords do not match
                            </div>
                        </div>

                        <div class="form-group mb-4 form-item" data-step="3">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="../terms-of-service.php" class="text-decoration-none">Terms of Service</a> and
                                    <a href="../privacy-policy.php" class="text-decoration-none">Privacy Policy</a>
                                </label>
                            </div>
                        </div>

                        <div class="d-grid form-item" data-step="3">
                            <button type="submit" class="btn btn-primary btn-lg register-btn">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account? <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-decoration-none fw-bold">
                            Sign in <i class="fas fa-arrow-right ms-1"></i>
                        </a></p>
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
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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

.input-group-text {
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
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

.register-btn {
    transition: all 0.3s ease;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    box-shadow: 0 4px 15px rgba(78,115,223,0.4);
}

.register-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(78,115,223,0.5);
}

.register-btn:active {
    transform: translateY(0);
}

.requirement.valid .requirement-icon i {
    color: #198754 !important;
}

.requirement.valid span {
    color: #198754 !important;
}

.requirement.invalid .requirement-icon i {
    color: #dc3545 !important;
}

.requirement.invalid span {
    color: #dc3545 !important;
}

/* Animation for form elements */
.form-item {
    animation: fadeInUp 0.5s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Card appearance animation */
.card {
    animation: cardAppear 0.5s ease;
}

@keyframes cardAppear {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrength = document.getElementById('passwordStrength');
    const strengthText = document.querySelector('.strength-text');
    const passwordMatch = document.getElementById('passwordMatch');
    const passwordMismatch = document.getElementById('passwordMismatch');
    const registerProgress = document.getElementById('registerProgress');
    
    // Apply focus effect when clicking on form field containers
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
            
            // Update progress bar based on field
            const step = this.closest('.form-item').dataset.step;
            updateProgress(step);
        });
        
        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
    
    // Update progress bar
    function updateProgress(step) {
        let progress = 0;
        
        switch (step) {
            case '1':
                progress = 33;
                break;
            case '2':
                progress = 66;
                break;
            case '3':
                progress = 100;
                break;
        }
        
        registerProgress.style.width = progress + '%';
    }
    
    // Check password strength
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let score = 0;
            let strengthClass = '';
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            // Update requirement indicators
            updateRequirement('length', hasLength);
            updateRequirement('uppercase', hasUpperCase);
            updateRequirement('lowercase', hasLowerCase);
            updateRequirement('number', hasNumber);
            
            // Calculate score
            if (hasLength) score += 25;
            if (hasUpperCase) score += 25;
            if (hasLowerCase) score += 25;
            if (hasNumber) score += 25;
            
            // Set strength text and color
            if (score == 0) {
                strengthText.textContent = 'None';
                strengthClass = '';
            } else if (score < 50) {
                strengthText.textContent = 'Weak';
                strengthClass = 'bg-danger';
            } else if (score < 75) {
                strengthText.textContent = 'Medium';
                strengthClass = 'bg-warning';
            } else if (score < 100) {
                strengthText.textContent = 'Good';
                strengthClass = 'bg-info';
            } else {
                strengthText.textContent = 'Strong';
                strengthClass = 'bg-success';
            }
            
            // Update strength progress bar
            passwordStrength.style.width = score + '%';
            passwordStrength.className = 'progress-bar ' + strengthClass;
            
            // Check if passwords match
            if (confirmPasswordInput.value) {
                checkPasswordsMatch();
            }
        });
    }
    
    // Check if passwords match
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordsMatch);
    }
    
    function checkPasswordsMatch() {
        if (passwordInput.value && confirmPasswordInput.value) {
            if (passwordInput.value === confirmPasswordInput.value) {
                passwordMatch.classList.remove('d-none');
                passwordMismatch.classList.add('d-none');
            } else {
                passwordMatch.classList.add('d-none');
                passwordMismatch.classList.remove('d-none');
            }
        } else {
            passwordMatch.classList.add('d-none');
            passwordMismatch.classList.add('d-none');
        }
    }
    
    function updateRequirement(name, isValid) {
        const requirement = document.querySelector(`.requirement[data-requirement="${name}"]`);
        const icon = requirement.querySelector('.requirement-icon i');
        
        if (isValid) {
            requirement.classList.add('valid');
            requirement.classList.remove('invalid');
            icon.className = 'fas fa-check-circle text-success';
        } else {
            requirement.classList.remove('valid');
            requirement.classList.add('invalid');
            icon.className = 'fas fa-times-circle text-danger';
        }
    }
    
    // Initialize progress to first step
    updateProgress('1');
});

// Toggle password visibility
function togglePasswordVisibility(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(fieldId === 'password' ? 'password-toggle-icon' : 'confirm-password-toggle-icon');
    
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
