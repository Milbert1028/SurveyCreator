<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = 'Forgot Password';
require_once '../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary">Password Recovery</h2>
                <p class="text-muted">Follow the steps to reset your password</p>
            </div>

            <!-- Progress Steps -->
            <div class="position-relative mb-5">
                <div class="progress" style="height: 3px;">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" id="progressBar"></div>
                </div>
                <div class="position-absolute top-0 start-0 translate-middle-y d-flex justify-content-between w-100 px-2" style="margin-top: 1.5px;">
                    <div class="step active" data-step="1">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">1</div>
                        <span class="position-absolute text-muted" style="font-size: 0.8rem; width: 80px; margin-top: 5px; margin-left: -25px;">Email</span>
                    </div>
                    <div class="step" data-step="2">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">2</div>
                        <span class="position-absolute text-muted" style="font-size: 0.8rem; width: 80px; margin-top: 5px; margin-left: -25px;">Verify</span>
                    </div>
                    <div class="step" data-step="3">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">3</div>
                        <span class="position-absolute text-muted" style="font-size: 0.8rem; width: 80px; margin-top: 5px; margin-left: -25px;">Reset</span>
                    </div>
                </div>
            </div>

            <!-- Step 1: Email Form -->
            <div class="card shadow-sm" id="emailStep">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4 text-center">Enter Your Email</h4>
                    <form id="emailForm">
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                            <label for="email">Email Address</label>
                            <div class="invalid-feedback" id="email-feedback"></div>
                        </div>
                        <!-- Error alert for email step -->
                        <div class="alert alert-danger d-none mb-3" id="emailError">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span id="emailErrorMessage"></span>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                                <i class="fas fa-paper-plane me-2"></i>
                                Send Reset Code
                            </button>
                           <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-link">
                               <i class="fas fa-arrow-left me-2"></i>Back to Login
                           </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Step 2: Verification Code -->
            <div class="card shadow-sm d-none" id="verificationStep">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4 text-center">Verify Your Email</h4>
                    <form id="verificationForm">
                        <div class="text-center mb-4">
                            <div class="verification-code">
                                <input type="text" maxlength="1" pattern="[0-9]" class="form-control code-input" required>
                                <input type="text" maxlength="1" pattern="[0-9]" class="form-control code-input" required>
                                <input type="text" maxlength="1" pattern="[0-9]" class="form-control code-input" required>
                                <input type="text" maxlength="1" pattern="[0-9]" class="form-control code-input" required>
                                <input type="text" maxlength="1" pattern="[0-9]" class="form-control code-input" required>
                                <input type="text" maxlength="1" pattern="[0-9]" class="form-control code-input" required>
                            </div>
                        </div>
                        <p class="text-center text-muted mb-4">
                            <i class="fas fa-envelope me-2"></i>
                            Enter the 6-digit code sent to your email
                        </p>
                        <!-- Error alert for verification step -->
                        <div class="alert alert-danger d-none mb-3" id="verificationError">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span id="verificationErrorMessage"></span>
                        </div>
                        <div class="text-center mb-4">
                            <button type="button" class="btn btn-link p-0" id="resendCode">
                                <i class="fas fa-sync-alt me-2"></i>Resend Code
                            </button>
                            <span id="resendTimer" class="text-muted ms-2"></span>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                                <i class="fas fa-check-circle me-2"></i>
                                Verify Code
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Step 3: New Password -->
            <div class="card shadow-sm d-none" id="passwordStep">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4 text-center">Create New Password</h4>
                    <form id="passwordForm">
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="New Password" required minlength="8">
                            <label for="password">New Password</label>
                        </div>
                        <div class="password-strength mb-3">
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" id="passwordStrength"></div>
                            </div>
                            <div class="requirements mt-2">
                                <p class="text-muted mb-1 requirement" data-requirement="length">
                                    <i class="fas fa-circle-notch"></i> At least 8 characters
                                </p>
                                <p class="text-muted mb-1 requirement" data-requirement="uppercase">
                                    <i class="fas fa-circle-notch"></i> One uppercase letter
                                </p>
                                <p class="text-muted mb-1 requirement" data-requirement="lowercase">
                                    <i class="fas fa-circle-notch"></i> One lowercase letter
                                </p>
                                <p class="text-muted mb-1 requirement" data-requirement="number">
                                    <i class="fas fa-circle-notch"></i> One number
                                </p>
                                <p class="text-muted mb-1 requirement" data-requirement="special">
                                    <i class="fas fa-circle-notch"></i> One special character
                                </p>
                            </div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="confirmPassword" 
                                   name="confirmPassword" placeholder="Confirm Password" required>
                            <label for="confirmPassword">Confirm Password</label>
                        </div>
                        <!-- Error alert for password step -->
                        <div class="alert alert-danger d-none mb-3" id="passwordError">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span id="passwordErrorMessage"></span>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                                <i class="fas fa-key me-2"></i>
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step {
    z-index: 1;
}
.verification-code {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-bottom: 1rem;
}
.verification-code input {
    width: 45px;
    height: 45px;
    text-align: center;
    font-size: 1.2rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}
.verification-code input:focus {
    transform: scale(1.05);
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.requirement.valid {
    color: #198754 !important;
}
.requirement.valid i {
    color: #198754;
}
.password-strength .progress-bar {
    transition: width 0.3s ease;
}
.card {
    transition: all 0.3s ease;
    border-radius: 15px;
    overflow: hidden;
}
.btn {
    transition: all 0.3s ease;
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.step .rounded-circle {
    transition: all 0.3s ease;
}
.step.active .rounded-circle {
    transform: scale(1.1);
}
.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    border-color: #86b7fe;
}
.alert {
    animation: fadeIn 0.5s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.progress-bar {
    transition: width 0.5s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Store elements
    const elements = {
        emailStep: document.getElementById('emailStep'),
        verificationStep: document.getElementById('verificationStep'),
        passwordStep: document.getElementById('passwordStep'),
        progressBar: document.getElementById('progressBar'),
        emailForm: document.getElementById('emailForm'),
        verificationForm: document.getElementById('verificationForm'),
        passwordForm: document.getElementById('passwordForm'),
        password: document.getElementById('password'),
        confirmPassword: document.getElementById('confirmPassword'),
        passwordStrength: document.getElementById('passwordStrength'),
        resendButton: document.getElementById('resendCode'),
        resendTimer: document.getElementById('resendTimer'),
        progressCircles: document.querySelectorAll('.progress-step')
    };

    // Verify all required elements exist
    const requiredElements = [
        'emailStep',
        'verificationStep',
        'passwordStep',
        'progressBar',
        'emailForm',
        'verificationForm',
        'passwordForm'
    ];

    for (const elementId of requiredElements) {
        if (!elements[elementId]) {
            console.error(`Required element not found: ${elementId}`);
            return;
        }
    }

    let resendTimeout = null;
    let userEmail = '';
    let verificationCode = ''; // Store the verification code here
    
    // Progress bar update
    function updateProgress(step) {
        console.log('Updating progress to step:', step);
        
        // Make sure we're using the cached elements
        if (elements.progressCircles) {
            elements.progressCircles.forEach((circle, index) => {
                // Set or remove active state based on current step
                if (index + 1 <= step) {
                    circle.classList.add('bg-primary', 'text-white');
                    circle.classList.remove('bg-light', 'border');
                } else {
                    circle.classList.remove('bg-primary', 'text-white');
                    circle.classList.add('bg-light', 'border');
                }
            });
        } else {
            console.warn('Progress circles not found in elements object');
            
            // Fallback to direct query if not cached
            const circles = document.querySelectorAll('.progress-step');
            circles.forEach((circle, index) => {
                if (index + 1 <= step) {
                    circle.classList.add('bg-primary', 'text-white');
                    circle.classList.remove('bg-light', 'border');
                } else {
                    circle.classList.remove('bg-primary', 'text-white');
                    circle.classList.add('bg-light', 'border');
                }
            });
        }
    }

    // Add a function to use the proxy for API calls
    async function callApi(endpoint, data) {
        const siteUrl = '<?php echo SITE_URL; ?>';
        // Use the endpoint directly from the API directory
        const proxyUrl = `${siteUrl}/api/proxy.php?endpoint=forgot-password.php`;
        
        console.log('Using API proxy:', proxyUrl);
        
        try {
            const response = await fetch(proxyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            // Check if response is ok (status in the range 200-299)
            if (!response.ok) {
                console.error('API error:', response.status, response.statusText);
                return { 
                    success: false, 
                    message: `Server error: ${response.status} ${response.statusText}` 
                };
            }
            
            // Safety check for empty responses
            const responseText = await response.text();
            if (!responseText || responseText.trim() === '') {
                console.error('Empty response received');
                return { 
                    success: false, 
                    message: 'Server returned an empty response' 
                };
            }
            
            // Parse JSON - handle SMTP debug output
            try {
                // Try to extract JSON from the response if it contains debug output
                const jsonMatch = responseText.match(/({[\s\S]*})$/);
                if (jsonMatch && jsonMatch[1]) {
                    try {
                        return JSON.parse(jsonMatch[1]);
                    } catch (innerError) {
                        console.error('Inner JSON parse error:', innerError);
                    }
                }
                
                // If no JSON found or parsing failed, try parsing the whole response
                return JSON.parse(responseText);
            } catch (e) {
                console.error('JSON parse error:', e, 'Response text:', responseText);
                
                // If the response contains "success":true, extract success message
                if (responseText.includes('"success":true')) {
                    return { 
                        success: true, 
                        message: 'Operation completed successfully' 
                    };
                }
                
                return { 
                    success: false, 
                    message: 'Invalid response format from server' 
                };
            }
        } catch (error) {
            console.error('Network error:', error);
            return {
                success: false,
                message: 'Network error: Could not connect to server'
            };
        }
    }

    // Replace direct API calls with proxy calls
    async function requestPasswordReset() {
        const form = document.getElementById('emailForm');
        if (!form) {
            console.error('Form not found');
            return;
        }

        const button = form.querySelector('button[type="submit"]');
        if (!button) {
            console.error('Submit button not found');
            return;
        }

        const spinner = button.querySelector('.spinner-border');
        if (!spinner) {
            console.error('Spinner not found');
            return;
        }

        const emailInput = document.getElementById('email');
        if (!emailInput) {
            console.error('Email input not found');
            return;
        }
        
        // Clear previous error messages
        const errorAlert = document.getElementById('emailError');
        const errorMessage = document.getElementById('emailErrorMessage');
        if (errorAlert) errorAlert.classList.add('d-none');
        
        try {
            button.disabled = true;
            spinner.classList.remove('d-none');
            
            userEmail = emailInput.value;
            console.log('Sending request with email:', userEmail);
            
            // Validate email format
            if (!validateEmail(userEmail)) {
                showEmailError('Please enter a valid email address');
                return;
            }
            
            // If email is not a Gmail address
            if (!userEmail.toLowerCase().endsWith('@gmail.com')) {
                showEmailError('Please enter a Gmail address');
                return;
            }
            
            const result = await callApi('forgot-password.php', {
                action: 'send_code',
                email: userEmail
            });
            
            console.log('Response:', result);
            
            if (result.success) {
                elements.emailStep.classList.add('d-none');
                elements.verificationStep.classList.remove('d-none');
                updateProgress(2);
                startResendTimer();
                
                // Focus on the first code input
                const firstCodeInput = document.querySelector('.code-input');
                if (firstCodeInput) {
                    firstCodeInput.focus();
                }
            } else {
                showEmailError(result.message || 'Failed to send verification code. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            showEmailError('An error occurred. Please try again later.');
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
        }
    }
    
    // Function to validate email format
    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
    
    // Function to display email error
    function showEmailError(message) {
        const errorAlert = document.getElementById('emailError');
        const errorMessage = document.getElementById('emailErrorMessage');
        
        if (errorAlert && errorMessage) {
            errorMessage.textContent = message;
            errorAlert.classList.remove('d-none');
            
            // Add shake animation
            errorAlert.classList.add('animate__animated', 'animate__shakeX');
            
            // Remove animation class after it completes
            setTimeout(() => {
                errorAlert.classList.remove('animate__animated', 'animate__shakeX');
            }, 1000);
        } else {
            alert(message);
        }
    }

    async function verifyCode() {
        const form = document.getElementById('verificationForm');
        if (!form) {
            console.error('Form not found');
            return;
        }

        const button = form.querySelector('button[type="submit"]');
        if (!button) {
            console.error('Submit button not found');
            return;
        }

        const spinner = button.querySelector('.spinner-border');
        if (!spinner) {
            console.error('Spinner not found');
            return;
        }

        const codeInputs = document.querySelectorAll('.code-input');
        if (!codeInputs || codeInputs.length === 0) {
            console.error('Code inputs not found');
            return;
        }
        
        // Clear previous error messages
        const errorAlert = document.getElementById('verificationError');
        const errorMessage = document.getElementById('verificationErrorMessage');
        if (errorAlert) errorAlert.classList.add('d-none');
        
        try {
            button.disabled = true;
            spinner.classList.remove('d-none');
            
            // Get code from inputs and ensure it's 6 digits
            const code = Array.from(codeInputs)
                .map(input => input.value.trim())
                .join('');
            
            // Store verification code in global variable
            verificationCode = code;
            
            console.log('Verifying code:', {
                email: userEmail,
                code: verificationCode
            });
            
            if (!code || code.length !== 6 || !/^\d{6}$/.test(code)) {
                showVerificationError('Please enter a valid 6-digit code');
                return;
            }
            
            // Use the API-specified action 'verify_code' instead of 'verify'
            const result = await callApi('forgot-password.php', {
                action: 'verify_code', // Changed from 'verify' to 'verify_code'
                email: userEmail,
                code: verificationCode
            });
            
            console.log('Response:', result);
            
            if (result.success) {
                elements.verificationStep.classList.add('d-none');
                elements.passwordStep.classList.remove('d-none');
                updateProgress(3);
            } else {
                // Improved error handling
                showVerificationError(result.message || 'Invalid verification code');
                // Clear the code inputs on error
                codeInputs.forEach(input => input.value = '');
                codeInputs[0]?.focus();
            }
        } catch (error) {
            console.error('Error:', error);
            showVerificationError('An error occurred. Please try again later.');
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
        }
    }
    
    // Function to display verification error
    function showVerificationError(message) {
        const errorAlert = document.getElementById('verificationError');
        const errorMessage = document.getElementById('verificationErrorMessage');
        
        if (errorAlert && errorMessage) {
            errorMessage.textContent = message;
            errorAlert.classList.remove('d-none');
            
            // Add shake animation
            errorAlert.classList.add('animate__animated', 'animate__shakeX');
            
            // Remove animation class after it completes
            setTimeout(() => {
                errorAlert.classList.remove('animate__animated', 'animate__shakeX');
            }, 1000);
        } else {
            alert(message);
        }
    }

    async function resetPassword() {
        const form = document.getElementById('passwordForm');
        if (!form) {
            console.error('Form not found');
            return;
        }

        const button = form.querySelector('button[type="submit"]');
        if (!button) {
            console.error('Submit button not found');
            return;
        }

        const spinner = button.querySelector('.spinner-border');
        if (!spinner) {
            console.error('Spinner not found');
            return;
        }

        if (!elements.password || !elements.confirmPassword) {
            console.error('Password inputs not found');
            return;
        }
        
        const password = elements.password.value.trim();
        const confirmPassword = elements.confirmPassword.value.trim();
        
        // Clear previous error messages
        const errorAlert = document.getElementById('passwordError');
        const errorMessage = document.getElementById('passwordErrorMessage');
        if (errorAlert) errorAlert.classList.add('d-none');
        
        // Validate password
        if (!password || password.length < 8) {
            showPasswordError('Password must be at least 8 characters long');
            return;
        }
        
        if (password !== confirmPassword) {
            showPasswordError('Passwords do not match');
            return;
        }
        
        try {
            button.disabled = true;
            spinner.classList.remove('d-none');
            
            console.log('Resetting password:', {
                email: userEmail,
                hasCode: Boolean(verificationCode),
                hasPassword: Boolean(password)
            });
            
            // Use the global verificationCode variable
            const result = await callApi('forgot-password.php', {
                action: 'reset_password',
                email: userEmail,
                code: verificationCode, // Use the global variable
                password: password
            });
            
            console.log('Response:', result);
            
            if (result.success) {
                // Show success message in a nicer way before redirecting
                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success text-center animate__animated animate__fadeIn';
                successAlert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    Password reset successful! Redirecting to login page...
                `;
                
                form.innerHTML = '';
                form.appendChild(successAlert);
                
                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                // Improved error handling
                showPasswordError(result.message || 'Failed to reset password. Please try again from the beginning.');
                
                // If the reset code is invalid/expired, go back to the first step
                elements.passwordStep.classList.add('d-none');
                elements.emailStep.classList.remove('d-none');
                elements.verificationStep.classList.add('d-none');
                updateProgress(1);
                elements.password.value = '';
                elements.confirmPassword.value = '';
            }
        } catch (error) {
            console.error('Error:', error);
            showPasswordError('An error occurred. Please try again later.');
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
        }
    }
    
    // Function to display password error
    function showPasswordError(message) {
        const errorAlert = document.getElementById('passwordError');
        const errorMessage = document.getElementById('passwordErrorMessage');
        
        if (errorAlert && errorMessage) {
            errorMessage.textContent = message;
            errorAlert.classList.remove('d-none');
            
            // Add shake animation
            errorAlert.classList.add('animate__animated', 'animate__shakeX');
            
            // Remove animation class after it completes
            setTimeout(() => {
                errorAlert.classList.remove('animate__animated', 'animate__shakeX');
            }, 1000);
        } else {
            alert(message);
        }
    }

    // Handle email form submission
    elements.emailForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await requestPasswordReset();
    });

    // Handle verification form submission
    elements.verificationForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await verifyCode();
    });

    // Handle password form submission
    elements.passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await resetPassword();
    });

    // Handle verification code input
    const codeInputs = document.querySelectorAll('.code-input');
    codeInputs.forEach((input, index) => {
        if (!input) return;

        input.addEventListener('keyup', (e) => {
            if (e.key !== 'Backspace' && index < codeInputs.length - 1 && input.value) {
                codeInputs[index + 1]?.focus();
            }
            if (e.key === 'Backspace' && index > 0) {
                codeInputs[index - 1]?.focus();
            }
        });
        
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text');
            const digits = pastedData.match(/\d/g);
            if (digits) {
                digits.forEach((digit, i) => {
                    if (i < codeInputs.length && codeInputs[i]) {
                        codeInputs[i].value = digit;
                    }
                });
                if (digits.length > 0) {
                    const lastIndex = Math.min(digits.length, codeInputs.length) - 1;
                    codeInputs[lastIndex]?.focus();
                }
            }
        });
    });

    // Handle resend code
    function startResendTimer() {
        if (!elements.resendButton || !elements.resendTimer) return;
        
        let timeLeft = 60;
        elements.resendButton.disabled = true;
        
        if (resendTimeout) clearInterval(resendTimeout);
        
        resendTimeout = setInterval(() => {
            timeLeft--;
            elements.resendTimer.textContent = `(${timeLeft}s)`;
            
            if (timeLeft <= 0) {
                clearInterval(resendTimeout);
                elements.resendButton.disabled = false;
                elements.resendTimer.textContent = '';
            }
        }, 1000);
    }

    // Handle resend button click
    if (elements.resendButton) {
        elements.resendButton.addEventListener('click', async () => {
            if (!elements.resendButton || elements.resendButton.disabled) return;
            
            try {
                elements.resendButton.disabled = true;
                await requestPasswordReset();
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                elements.resendButton.disabled = false;
            }
        });
    }

    // Update all relative API URLs to absolute URLs
    const siteUrl = '<?php echo SITE_URL; ?>';
    
    // Replace all fetch calls with absolute URLs
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        // If URL starts with '../api/' or '/api/', replace with absolute URL
        if (url.startsWith('../api/') || url.startsWith('/api/')) {
            const apiPath = url.replace('../api/', '/api/').replace('/api/', '/api/');
            url = siteUrl + apiPath;
            console.log('Updated API URL to:', url);
        }
        return originalFetch.call(this, url, options);
    };
    
    console.log('API URL patching applied');
});
</script>

<?php require_once '../templates/auth-footer.php'; ?>
