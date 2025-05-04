<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/SimpleMailer.php';

$page_title = "Contact Us";
$success = false;
$error = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verify_csrf_token($csrf_token)) {
        $error = "Invalid request. Please try again.";
    }
    // Validate inputs
    elseif (empty($name)) {
        $error = "Please provide your name.";
    }
    elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    }
    elseif (empty($subject)) {
        $error = "Please provide a subject.";
    }
    elseif (empty($message)) {
        $error = "Please provide a message.";
    }
    else {
        // Attempt to send email
        $to = defined('EMAIL_USERNAME') ? EMAIL_USERNAME : 'support@surveycreator.online';
        $email_subject = "Contact Form: " . $subject;
        $email_body = "
            <html>
            <head>
                <title>Contact Form Submission</title>
            </head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                    <h2 style='color: #0d6efd;'>New Contact Form Submission</h2>
                    <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                    <p><strong>Message:</strong></p>
                    <div style='background-color: #f9f9f9; padding: 15px; border-radius: 4px;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                    <p style='font-size: 12px; color: #777; margin-top: 30px;'>This message was sent from the contact form on " . SITE_URL . ".</p>
                </div>
            </body>
            </html>
        ";
        
        try {
            if (class_exists('SimpleMailer')) {
                $result = SimpleMailer::sendEmail($to, $email_subject, $email_body);
                
                if ($result === true) {
                    $success = "Your message has been sent successfully. We'll get back to you as soon as possible.";
                } else {
                    error_log("Contact form email failed: " . $result);
                    $error = "There was a problem sending your message. Please try again later.";
                }
            } else {
                // Fallback if mailer isn't available
                error_log("Contact form - SimpleMailer not available");
                $success = "Your message has been received. We'll get back to you as soon as possible.";
            }
        } catch (Exception $e) {
            error_log("Contact form exception: " . $e->getMessage());
            $error = "An unexpected error occurred. Please try again later.";
        }
    }
}

require_once 'templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="text-center mb-5">Contact Us</h1>
            
            <div class="row">
                <!-- Contact Information -->
                <div class="col-md-5 mb-4 mb-md-0">
                    <div class="card shadow-sm h-100">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-4">Get In Touch</h2>
                            
                            <div class="d-flex align-items-start mb-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-envelope text-primary" style="width: 20px;"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="h6 mb-1">Email</h3>
                                    <p class="mb-0"><a href="mailto:support@surveycreator.online" class="text-decoration-none">support@surveycreator.online</a></p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start mb-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-globe text-primary" style="width: 20px;"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="h6 mb-1">Website</h3>
                                    <p class="mb-0"><a href="<?php echo SITE_URL; ?>" class="text-decoration-none"><?php echo SITE_URL; ?></a></p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start mb-4">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock text-primary" style="width: 20px;"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="h6 mb-1">Support Hours</h3>
                                    <p class="mb-0">Monday - Friday, 9AM - 5PM<br>We aim to respond within 24 hours.</p>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <p class="mb-0 small">For questions about your account, surveys, or technical support, please include your username and as much detail as possible.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-4">Send Us a Message</h2>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                                </div>
                                <p class="text-center mt-4">
                                    <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Return to Home</a>
                                </p>
                            <?php elseif ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                                </div>
                                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 