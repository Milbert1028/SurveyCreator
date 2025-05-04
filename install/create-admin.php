<?php
/**
 * Admin Account Creation Script
 * This script creates an admin account for the survey system
 */

// Basic security check - only allow running from command line or with specific parameter
if (php_sapi_name() !== 'cli' && (!isset($_GET['secure_token']) || $_GET['secure_token'] !== 'install_admin_secure')) {
    die('Access denied. This script can only be run from the command line or with the proper security token.');
}

// Include configuration
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/Database.php';

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Check if admin exists
$check_admin_query = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
$result = $conn->query($check_admin_query);
$row = $result->fetch_assoc();

if ($row['admin_count'] > 0 && !isset($_GET['force'])) {
    echo "An admin account already exists. Use '?force=true' parameter to override.\n";
    exit;
}

// Function to prompt for input (works in CLI)
function prompt($prompt) {
    if (php_sapi_name() === 'cli') {
        echo $prompt;
        return trim(fgets(STDIN));
    } else {
        return isset($_POST[$prompt]) ? $_POST[$prompt] : '';
    }
}

// Handle form submission for web interface
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password) || strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    $check_email_query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    
    if (empty($errors)) {
        create_admin($name, $email, $password);
    }
} elseif (php_sapi_name() === 'cli') {
    // Command line interface
    echo "===== Admin Account Creation =====\n";
    
    $name = prompt("Enter admin name: ");
    $email = prompt("Enter admin email: ");
    $password = prompt("Enter admin password (min 8 characters): ");
    $confirm_password = prompt("Confirm password: ");
    
    if (empty($name) || empty($email) || empty($password) || $password !== $confirm_password || strlen($password) < 8) {
        echo "Invalid input. Please check your entries and try again.\n";
        exit;
    }
    
    create_admin($name, $email, $password);
}

// Function to create an admin account
function create_admin($name, $email, $password) {
    global $conn;
    
    // Generate current timestamp
    $created_at = date('Y-m-d H:i:s');
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate account verification token
    $verification_token = bin2hex(random_bytes(32));
    
    // Insert the admin user
    $insert_query = "INSERT INTO users (name, email, password, role, is_verified, verification_token, created_at) 
                     VALUES (?, ?, ?, 'admin', 1, ?, ?)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sssss", $name, $email, $hashed_password, $verification_token, $created_at);
    
    if ($stmt->execute()) {
        $admin_id = $conn->insert_id;
        
        if (php_sapi_name() === 'cli') {
            echo "\n===== Admin Account Created Successfully =====\n";
            echo "ID: $admin_id\n";
            echo "Name: $name\n";
            echo "Email: $email\n";
            echo "Role: admin\n";
            echo "Account is verified and ready to use\n";
        } else {
            echo '<div class="alert alert-success">';
            echo '<h4 class="alert-heading">Admin Account Created Successfully!</h4>';
            echo "<p><strong>ID:</strong> $admin_id<br>";
            echo "<strong>Name:</strong> " . htmlspecialchars($name) . "<br>";
            echo "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>";
            echo "<strong>Role:</strong> admin</p>";
            echo '<p>Account is verified and ready to use. You can now <a href="../admin/login.php" class="alert-link">log in to the admin panel</a>.</p>';
            echo '</div>';
        }
    } else {
        if (php_sapi_name() === 'cli') {
            echo "Error creating admin account: " . $stmt->error . "\n";
        } else {
            echo '<div class="alert alert-danger">Error creating admin account: ' . $stmt->error . '</div>';
        }
    }
}

// Only show HTML form if not in CLI mode and form not yet submitted
if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] !== 'POST'):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 40px 0;
        }
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-radius: 0.5rem;
        }
        .card-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0 !important;
            padding: 1.25rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
        }
        .btn-primary:hover {
            background: #2e59d9;
        }
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="m-0 font-weight-bold"><i class="fas fa-user-shield mr-2"></i> Create Admin Account</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($errors) && !empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="password-field">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <span class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                                    </span>
                                </div>
                                <div class="form-text">Password must be at least 8 characters long</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="password-field">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye" id="confirm-password-toggle-icon"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i> Create Admin Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(id) {
            const passwordInput = document.getElementById(id);
            const toggleIconId = id === 'password' ? 'password-toggle-icon' : 'confirm-password-toggle-icon';
            const passwordToggleIcon = document.getElementById(toggleIconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggleIcon.classList.remove('fa-eye');
                passwordToggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggleIcon.classList.remove('fa-eye-slash');
                passwordToggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
<?php endif; ?> 