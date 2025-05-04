<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/SimpleMailer.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to share surveys", "warning");
    redirect('/auth/login.php');
}

$survey_id = $_GET['id'] ?? null;
if (!$survey_id) {
    flash_message("Survey ID is required", "danger");
    redirect('/dashboard');
}

// Get survey details
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

$survey = $db->query("
    SELECT * FROM surveys 
    WHERE id = $survey_id AND user_id = $user_id
");

if (!$survey || $survey->num_rows === 0) {
    flash_message("Survey not found", "danger");
    redirect('/dashboard');
}

$survey_data = $survey->fetch_assoc();
$share_settings = get_share_settings($survey_id); // Get share settings

if ($share_settings) {
    $show_progress_bar = $share_settings['show_progress_bar'];
    $allow_multiple_responses = $share_settings['allow_multiple_responses'];
    $require_login = $share_settings['require_login'];
    $response_limit = $share_settings['response_limit'];
    $close_date = $share_settings['close_date'];
} else {
    // Handle the case where share settings are not available
    $show_progress_bar = $allow_multiple_responses = $require_login = $response_limit = $close_date = null;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        flash_message("Invalid request", "danger");
        redirect("/dashboard/share-survey.php?id=$survey_id");
    }

    if ($_POST['action'] === 'update_status') {
        $new_status = $db->escape($_POST['status']);
        $update = $db->query("
            UPDATE surveys 
            SET status = '$new_status' 
            WHERE id = $survey_id AND user_id = $user_id
        ");

        if ($update) {
            flash_message("Survey status updated successfully", "success");
        } else {
            flash_message("Error updating survey status: " . $db->getLastError(), "danger");
        }
        redirect("/dashboard/share-survey.php?id=$survey_id");
    }
    
    // Handle email sending to selected users
    if ($_POST['action'] === 'send_to_users') {
        // Enable error display temporarily for debugging
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        try {
            // Get selected users
            $selected_users = $_POST['selected_users'] ?? [];
            $restrict_access = isset($_POST['restrict_access']) ? 1 : 0;
            
            // Log what we're about to do
            error_log("Processing survey invitations for survey ID: $survey_id. Restrict access: $restrict_access");
            error_log("Selected users: " . implode(',', $selected_users));
            
            if (empty($selected_users)) {
                flash_message("No users selected.", "warning");
                redirect("/dashboard/share-survey.php?id=$survey_id");
                exit;
            }
            
            // Update the survey to use restricted access mode (we already confirmed the column exists)
            $update_result = $db->query("UPDATE surveys SET restrict_access = $restrict_access WHERE id = $survey_id");
            if (!$update_result) {
                error_log("Error updating restrict_access: " . $db->getLastError());
            }
            
            // Get the survey URL
            $survey_url = SITE_URL . "/survey.php?id=" . $survey_id;
            
            // Get selected users' emails - use parameterized query to be safer
            $user_ids = implode(',', array_map('intval', $selected_users));
            $selected_users_query = $db->query("SELECT id, email, username FROM users WHERE id IN ($user_ids)");
            
            if (!$selected_users_query) {
                error_log("Error querying selected users: " . $db->getLastError());
                flash_message("Error retrieving user information", "danger");
                redirect("/dashboard/share-survey.php?id=$survey_id");
                exit;
            }
            
            // Clear existing access records
            $delete_result = $db->query("DELETE FROM survey_access WHERE survey_id = $survey_id");
            if (!$delete_result) {
                error_log("Error clearing existing access records: " . $db->getLastError());
            }
            
            $success_count = 0;
            $error_count = 0;
            
            // Process each user
            while ($user = $selected_users_query->fetch_assoc()) {
                try {
                    // Add user to survey_access table
                    if ($restrict_access == 1) {
                        $insert_result = $db->query("INSERT INTO survey_access (survey_id, user_id) VALUES ($survey_id, {$user['id']})");
                        if (!$insert_result) {
                            error_log("Error adding user {$user['id']} to survey_access: " . $db->getLastError());
                        }
                    }
                    
                    // Send email invitation
                    $to = $user['email'];
                    $subject = "You've been invited to complete a survey: " . $survey_data['title'];
                    
                    $body = '
                    <html>
                    <head>
                        <title>Survey Invitation</title>
                    </head>
                    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
                        <div style="background-color: #f7f7f7; padding: 20px; border-radius: 5px; border-top: 4px solid #0d6efd;">
                            <h2 style="color: #0d6efd; margin-top: 0;">Survey Invitation</h2>
                            <p>Hello ' . htmlspecialchars($user['username']) . ',</p>
                            <p>You have been invited to complete the survey: <strong>' . htmlspecialchars($survey_data['title']) . '</strong></p>
                            <p>' . htmlspecialchars($survey_data['description']) . '</p>
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="' . $survey_url . '" style="background-color: #0d6efd; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                                    Complete Survey
                                </a>
                            </div>
                            <p>Or copy and paste this link into your browser:</p>
                            <p style="background-color: #e9ecef; padding: 10px; border-radius: 4px;">' . $survey_url . '</p>
                            <p>Thank you for your participation!</p>
                        </div>
                    </body>
                    </html>';
                    
                    // Use try/catch to catch email sending errors
                    try {
                        $result = SimpleMailer::sendEmail($to, $subject, $body);
                        
                        if ($result === true) {
                            $success_count++;
                        } else {
                            error_log("Failed to send email to {$user['email']}: $result");
                            $error_count++;
                        }
                    } catch (Exception $e) {
                        error_log("Exception sending email to {$user['email']}: " . $e->getMessage());
                        $error_count++;
                    }
                } catch (Exception $e) {
                    error_log("Exception processing user {$user['id']}: " . $e->getMessage());
                    $error_count++;
                    continue;
                }
            }
            
            if ($success_count > 0) {
                if ($restrict_access == 1) {
                    flash_message(
                        "<strong>Access restricted:</strong> Survey invitation sent to $success_count user(s). ".
                        "<div class='mt-2'><i class='fas fa-lock text-warning'></i> Only the selected users will be able to view and complete this survey.</div>", 
                        "success"
                    );
                } else {
                    flash_message("Survey invitation sent to $success_count user(s).", "success");
                }
            }
            
            if ($error_count > 0) {
                flash_message("Failed to send invitation to $error_count user(s).", "danger");
            }
        } catch (Exception $e) {
            error_log("Exception in send_to_users: " . $e->getMessage());
            flash_message("An error occurred: " . $e->getMessage(), "danger");
        }
        
        redirect("/dashboard/share-survey.php?id=$survey_id");
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Get survey URL
$survey_url = SITE_URL . "/survey.php?id=" . $survey_id;

require_once '../templates/header.php';
?>

<style>
    /* Enhanced UI Styles */
    .share-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .page-title {
        position: relative;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
    }
    
    .page-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100px;
        height: 4px;
        background: linear-gradient(90deg, #0d6efd, #6610f2);
        border-radius: 2px;
    }
    
    .survey-title {
        color: #555;
        font-weight: 500;
    }
    
    .status-badge {
        font-size: 0.85rem;
        padding: 0.35rem 0.65rem;
        border-radius: 50px;
        font-weight: 500;
        display: inline-block;
        margin-left: 1rem;
    }
    
    .status-draft {
        background-color: #f8f9fa;
        color: #6c757d;
        border: 1px solid #dee2e6;
    }
    
    .status-published {
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
    }
    
    .status-closed {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
    }
    
    .share-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        overflow: hidden;
        margin-bottom: 1.75rem;
    }
    
    .share-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
        background: linear-gradient(to right, #f8f9fa, #ffffff);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1rem 1.25rem;
    }
    
    .card-header h5 {
        font-weight: 600;
        color: #444;
        display: flex;
        align-items: center;
    }
    
    .card-header h5 i {
        margin-right: 0.75rem;
        color: #0d6efd;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .form-control {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        border-color: #e4e6ef;
        font-size: 0.95rem;
    }
    
    .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        border-color: #86b7fe;
    }
    
    .input-group .form-control {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    
    .input-group .btn {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    /* Remove sharing icon grid styles */
    .sharing-icon-grid {
        display: none;
    }
    
    .sharing-button {
        display: none;
    }
    
    .qr-container {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 1rem;
    }
    
    .qr-code-img {
        padding: 1rem;
        background-color: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.25rem;
    }
    
    .copy-btn {
        position: relative;
        overflow: hidden;
    }
    
    .copy-btn .default-text,
    .copy-btn .success-text {
        transition: transform 0.3s ease;
    }
    
    .copy-btn.copied .default-text {
        transform: translateY(-100%);
        opacity: 0;
    }
    
    .copy-btn.copied .success-text {
        transform: translateY(-100%);
        opacity: 1;
    }
    
    .success-text {
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        opacity: 0;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        font-weight: 500;
        border-radius: 8px;
    }
    
    .btn-primary {
        background: linear-gradient(45deg, #0d6efd, #0b5ed7);
        border: none;
        box-shadow: 0 2px 5px rgba(13, 110, 253, 0.3);
    }
    
    .btn-primary:hover {
        background: linear-gradient(45deg, #0b5ed7, #0a58ca);
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.4);
    }
    
    .btn-outline-primary {
        border-color: #0d6efd;
        color: #0d6efd;
    }
    
    .btn-outline-primary:hover {
        background-color: #0d6efd;
        color: white;
    }
    
    .form-select {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        background-position: right 1rem center;
        border-color: #e4e6ef;
        font-size: 0.95rem;
    }
    
    /* Enhance multiselect */
    #selected_users {
        min-height: 220px;
        background-color: #f9fafb;
        border-color: #e4e6ef;
    }
    
    #selected_users option {
        padding: 10px 15px;
        margin-bottom: 2px;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s;
    }
    
    #selected_users option:hover {
        background-color: #e9ecef;
    }
    
    #selected_users option:checked {
        background-color: #0d6efd;
        color: white;
    }
    
    /* Form switch styles */
    .form-check-input {
        width: 3em;
        height: 1.5em;
        margin-left: 1rem;
    }
    
    .form-switch .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    /* Enhanced toast styles */
    .toast-container {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 1100;
    }
    
    .custom-toast {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        width: 350px;
        max-width: 100%;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s;
    }
    
    .custom-toast.show {
        transform: translateY(0);
        opacity: 1;
    }
    
    .toast-header {
        background-color: #f8f9fa;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .toast-header i {
        color: #0d6efd;
        margin-right: 0.5rem;
    }
    
    .toast-header strong {
        color: #495057;
    }
    
    .toast-body {
        padding: 1rem;
    }
    
    /* Status indicator animations */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .status-published {
        animation: pulse 2s infinite;
    }
    
    /* Loading indicator for share buttons */
    .loading-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s linear infinite;
        margin-right: 0.5rem;
        vertical-align: middle;
        display: none;
    }
    
    .btn.is-loading .loading-spinner {
        display: inline-block;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<div class="container share-container py-5">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div>
            <h1 class="page-title">Share Your Survey</h1>
            <h4 class="survey-title">
                <?php echo htmlspecialchars($survey_data['title']); ?>
                <span class="status-badge status-<?php echo $survey_data['status']; ?>">
                    <?php echo ucfirst($survey_data['status']); ?>
                </span>
            </h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo SITE_URL; ?>/dashboard/edit-survey.php?id=<?php echo $survey_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-edit me-1"></i> Edit Survey
            </a>
            <a href="<?php echo SITE_URL; ?>/dashboard/view-responses.php?id=<?php echo $survey_id; ?>" class="btn btn-primary">
                <i class="fas fa-chart-bar me-1"></i> View Responses
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Survey URL -->
            <div class="card share-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-link"></i> Survey Link</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="survey-url" 
                               value="<?php echo $survey_url; ?>" readonly>
                        <button class="btn btn-primary copy-btn" type="button" id="copy-url">
                            <span class="default-text"><i class="fas fa-copy"></i> Copy Link</span>
                            <span class="success-text"><i class="fas fa-check"></i> Copied!</span>
                        </button>
                    </div>
                    
                    <!-- Remove the sharing buttons section -->
                    
                </div>
            </div>

            <!-- Embed Code -->
            <div class="card share-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-code"></i> Embed Survey</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Copy and paste this code to embed the survey in your website:</p>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="embed-code" readonly
                               value='<iframe src="<?php echo $survey_url; ?>" width="100%" height="600" frameborder="0"></iframe>'>
                        <button class="btn btn-primary copy-btn" type="button" id="copy-embed">
                            <span class="default-text"><i class="fas fa-copy"></i> Copy Code</span>
                            <span class="success-text"><i class="fas fa-check"></i> Copied!</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- QR Code -->
            <div class="card share-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-qrcode"></i> QR Code</h5>
                </div>
                <div class="card-body">
                    <div class="qr-container">
                        <div class="qr-code-img">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($survey_url); ?>" alt="QR Code" class="img-fluid" />
                        </div>
                        <a href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($survey_url); ?>" download="survey-<?php echo $survey_id; ?>-qr.png" class="btn btn-outline-primary">
                            <i class="fas fa-download"></i> Download QR Code
                        </a>
                    </div>
                </div>
            </div>

            <!-- Share with Users -->
            <div class="card share-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-friends"></i> Share with Registered Users</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo SITE_URL; ?>/dashboard/share-survey.php?id=<?php echo $survey_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="send_to_users">
                        <div class="mb-3">
                            <label for="selected_users" class="form-label">Select Users to Share With:</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="user-search" class="form-control" placeholder="Search users...">
                            </div>
                            <select name="selected_users[]" id="selected_users" class="form-select" multiple size="6">
                                <?php
                                // Get all registered users except current user
                                $users_query = $db->query("SELECT id, username, email FROM users WHERE id != $user_id ORDER BY username");
                                while ($user = $users_query->fetch_assoc()) {
                                    echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['email']) . ')</option>';
                                }
                                ?>
                            </select>
                            <div class="form-text d-flex align-items-center mt-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Hold Ctrl (PC) or Command (Mac) to select multiple users
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="restrict_access" id="restrict-access" value="1">
                                <label class="form-check-label" for="restrict-access">
                                    <strong>Restrict Access to Selected Users Only</strong>
                                </label>
                            </div>
                            <div class="form-text mt-1 ms-4 ps-2 border-start border-2 border-warning">
                                <i class="fas fa-lock me-1 text-warning"></i>
                                When enabled, <strong>only the users selected above</strong> will be able to view and complete the survey.
                                Anyone else will see an access denied message.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" id="send-invitations-btn">
                            <span class="loading-spinner"></span>
                            <i class="fas fa-envelope"></i> Send Survey Invitations
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Survey Status -->
            <div class="card share-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-toggle-on"></i> Survey Status</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo SITE_URL; ?>/dashboard/share-survey.php?id=<?php echo $survey_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="update_status">
                        
                        <div class="mb-4">
                            <label class="form-label">Current Status:</label>
                            <select name="status" class="form-select" id="survey-status">
                                <option value="draft" <?php echo $survey_data['status'] === 'draft' ? 'selected' : ''; ?>>
                                    Draft
                                </option>
                                <option value="published" <?php echo $survey_data['status'] === 'published' ? 'selected' : ''; ?>>
                                    Published
                                </option>
                                <option value="closed" <?php echo $survey_data['status'] === 'closed' ? 'selected' : ''; ?>>
                                    Closed
                                </option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Status Information</h6>
                            <hr>
                            <p class="mb-1"><strong>Draft:</strong> Only you can view the survey</p>
                            <p class="mb-1"><strong>Published:</strong> Anyone with the link can respond</p>
                            <p class="mb-0"><strong>Closed:</strong> No new responses accepted</p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mt-3">Update Status</button>
                    </form>
                </div>
            </div>

            <!-- Share Settings -->
            <div class="card share-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Share Settings</h5>
                </div>
                <div class="card-body">
                    <form id="share-settings-form" method="POST" action="<?php echo SITE_URL; ?>/dashboard/update_share_settings.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">

                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                Show Progress Bar
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_progress_bar" id="show-progress" 
                                           <?php echo $show_progress_bar ? 'checked' : ''; ?>>
                                </div>
                            </label>
                            <div class="form-text ms-4 ps-2 border-start border-2">
                                Display a progress bar to respondents as they complete the survey.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                Allow Multiple Responses
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="allow_multiple_responses" id="multiple-responses" 
                                           <?php echo $allow_multiple_responses ? 'checked' : ''; ?>>
                                </div>
                            </label>
                            <div class="form-text ms-4 ps-2 border-start border-2">
                                Let respondents submit the survey more than once.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                Require Login
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="require_login" id="require-login" 
                                           <?php echo $require_login ? 'checked' : ''; ?>>
                                </div>
                            </label>
                            <div class="form-text ms-4 ps-2 border-start border-2">
                                Respondents must log in to complete the survey.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="response-limit" class="form-label">Response Limit</label>
                            <input type="number" class="form-control" name="response_limit" id="response-limit" min="0"
                                   value="<?php echo $response_limit ?? ''; ?>" 
                                   placeholder="Leave empty for unlimited">
                            <div class="form-text ms-4 ps-2 border-start border-2">
                                Maximum number of responses to collect.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="close-date" class="form-label">Close Date</label>
                            <input type="date" class="form-control" name="close_date" id="close-date"
                                   value="<?php echo $close_date ?? ''; ?>">
                            <div class="form-text ms-4 ps-2 border-start border-2">
                                Date when the survey will automatically close.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast container for notifications -->
    <div class="toast-container"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Copy URL functionality with animation
    document.getElementById('copy-url').addEventListener('click', function() {
        const urlInput = document.getElementById('survey-url');
        urlInput.select();
        document.execCommand('copy');
        
        // Show animation
        this.classList.add('copied');
        setTimeout(() => {
            this.classList.remove('copied');
        }, 1500);
        
        showToast('Survey URL copied to clipboard', 'success');
    });

    // Copy embed code functionality with animation
    document.getElementById('copy-embed').addEventListener('click', function() {
        const embedInput = document.getElementById('embed-code');
        embedInput.select();
        document.execCommand('copy');
        
        // Show animation
        this.classList.add('copied');
        setTimeout(() => {
            this.classList.remove('copied');
        }, 1500);
        
        showToast('Embed code copied to clipboard', 'success');
    });

    // Show loading state on send invitations button
    document.querySelector('form[action*="share-survey"]').addEventListener('submit', function() {
        const button = document.getElementById('send-invitations-btn');
        showButtonLoading(button);
    });

    // User search filter
    document.getElementById('user-search').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const selectElement = document.getElementById('selected_users');
        const options = selectElement.options;
        
        for (let i = 0; i < options.length; i++) {
            const optionText = options[i].text.toLowerCase();
            if (optionText.includes(searchTerm)) {
                options[i].style.display = '';
            } else {
                options[i].style.display = 'none';
            }
        }
    });

    // Auto-submit status form on change with confirmation
    document.getElementById('survey-status').addEventListener('change', function() {
        const newStatus = this.value;
        const currentStatus = "<?php echo $survey_data['status']; ?>";
        
        if (newStatus !== currentStatus) {
            if (newStatus === 'closed') {
                if (confirm('Are you sure you want to close this survey? Respondents will no longer be able to submit responses.')) {
                    this.closest('form').submit();
                } else {
                    this.value = currentStatus; // Reset to previous value if canceled
                }
            } else {
                this.closest('form').submit();
            }
        }
    });

    // Helper functions
    function showButtonLoading(button) {
        button.classList.add('is-loading');
        button.disabled = true;
    }
    
    function hideButtonLoading(button) {
        button.classList.remove('is-loading');
        button.disabled = false;
    }

    // Enhanced toast notification
    function showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'error') icon = 'exclamation-circle';
        
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas fa-${icon}"></i>
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Auto close
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
        
        // Close button
        toast.querySelector('.btn-close').addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        });
    }
});
</script>

<?php require_once '../templates/footer.php'; ?>
</div>
</body>
</html>
