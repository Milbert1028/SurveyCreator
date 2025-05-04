# Password Recovery System

This document provides an overview of the password recovery system for the Survey Creator application.

## Features

The password recovery system allows users to:

1. Request a password reset using their registered email address
2. Receive a 6-digit verification code
3. Verify the code and set a new password

## Implementation Details

### Components

1. **SimpleMailer** - A wrapper for PHPMailer that simplifies email sending
2. **Password Reset API** - RESTful API endpoints for handling password recovery requests
3. **Email Templates** - HTML templates for password reset and confirmation emails
4. **Email Logging** - Debugging system to view sent emails even when mail delivery fails

### API Endpoints

The API endpoints are defined in `api/forgot-password.php` and support the following actions:

1. `send_code` - Generates and sends a verification code to the user's email
2. `verify_code` - Validates the verification code entered by the user
3. `reset_password` - Updates the user's password after successful verification

## Email System

The password recovery system uses PHPMailer to send emails via SMTP. This approach ensures reliable email delivery across different environments.

### Setting up Gmail for Sending Emails

1. **Create a Gmail App Password**:
   - Go to your [Google Account](https://myaccount.google.com/security)
   - Go to Security → 2-Step Verification → App passwords
   - Select "Mail" and "Windows Computer"
   - Click "Generate" and copy the 16-character password

2. **Update Configuration**:
   - Open `includes/config.php`
   - Replace the placeholder values with your actual Gmail credentials:
     ```php
     define('EMAIL_USERNAME', 'your-email@gmail.com'); // Replace with your Gmail address
     define('EMAIL_PASSWORD', 'your-app-password');    // Replace with your Gmail app password
     ```

### Key Features

- **Reliable Delivery**: Uses SMTP for reliable email delivery
- **Email Logging**: All emails are logged to the `logs/emails` directory for debugging
- **HTML Templates**: Professional-looking emails with HTML formatting
- **Fallback Mechanism**: Even if emails fail to send, verification codes are shown in development mode

## Testing Email Functionality

To test the email functionality:

1. Navigate to `test-mail.php` in your browser
2. Enter your email address in the test form
3. Click "Send Test Email"
4. Check for success/error messages
5. If email sending fails, you can view the email content in the "Recent Email Logs" section

## Troubleshooting

### Common Issues

1. **Emails Not Sending**
   - Check if your Gmail credentials are correct
   - Make sure you're using an App Password, not your regular Gmail password
   - Confirm that 2-Step Verification is enabled on your Google account
   - Look in the "Recent Email Logs" section on the test page to verify email content

2. **Invalid Verification Codes**
   - Codes expire after 1 hour
   - Each code can only be used once
   - Make sure the user is entering the correct code (check case sensitivity)

## Security Considerations

- Verification codes are stored as plain text but are only valid for 1 hour
- Password reset links contain a unique verification token rather than the user's email
- Passwords are hashed using PHP's `password_hash()` with the default algorithm
- Rate limiting should be implemented to prevent brute force attacks
