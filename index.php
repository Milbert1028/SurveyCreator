<?php
require_once 'includes/config.php';
require_once 'templates/header.php';
?>

<!-- Hero Section with Background Animation -->
<div class="hero-section position-relative overflow-hidden">
    <div class="animated-background"></div>
    <div class="container position-relative">
        <div class="row py-5 align-items-center">
            <div class="col-lg-6 animate__animated animate__fadeInLeft">
                <h1 class="display-4 fw-bold text-white">Transform Your Data Collection</h1>
                <p class="lead text-light mb-4">Create, manage, and analyze surveys with our powerful yet easy-to-use platform.</p>
                <?php if (!is_logged_in()): ?>
                    <div class="d-flex gap-2">
                        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-primary btn-lg fw-semibold animate__animated animate__pulse animate__infinite animate__slower">
                            <i class="fas fa-rocket me-2"></i>Get Started
                        </a>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/dashboard/create-survey.php" class="btn btn-primary btn-lg fw-semibold">
                        <i class="fas fa-plus me-2"></i>Create New Survey
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 d-none d-lg-block animate__animated animate__fadeInRight">
                <div class="position-relative">
                    <img src="<?php echo SITE_URL; ?>/assets/img/survey-dashboard.png" alt="Survey Dashboard" class="img-fluid rounded-4 shadow-lg" onerror="this.src='https://placehold.co/600x400/5d87ff/ffffff?text=Survey+Dashboard'">
                    <div class="floating-badge bg-success text-white py-2 px-3 rounded-pill shadow position-absolute animate__animated animate__fadeInUp">
                        <i class="fas fa-check-circle me-1"></i> Trusted by 10,000+ users
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section with Animated Cards -->
<div class="container mt-5">
    <div class="text-center mb-5 animate__animated animate__fadeIn">
        <h2 class="display-5 fw-bold">Powerful Survey Features</h2>
        <p class="lead text-muted">Everything you need to collect and analyze data effectively</p>
    </div>
    
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm feature-card">
                <div class="card-body text-center p-4">
                    <div class="icon-wrapper mb-3">
                        <i class="fas fa-pencil-alt fa-2x text-primary"></i>
                    </div>
                    <h3 class="card-title h4">Intuitive Creation</h3>
                    <p class="card-text">Build professional surveys in minutes with our drag-and-drop interface and smart templates.</p>
                    <div class="feature-hover-content">
                        <a href="#" class="btn btn-sm btn-primary rounded-pill">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm feature-card">
                <div class="card-body text-center p-4">
                    <div class="icon-wrapper mb-3">
                        <i class="fas fa-chart-bar fa-2x text-primary"></i>
                    </div>
                    <h3 class="card-title h4">Real-time Analytics</h3>
                    <p class="card-text">Get instant insights with advanced analytics and beautiful visualization tools.</p>
                    <div class="feature-hover-content">
                        <a href="#" class="btn btn-sm btn-primary rounded-pill">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm feature-card">
                <div class="card-body text-center p-4">
                    <div class="icon-wrapper mb-3">
                        <i class="fas fa-mobile-alt fa-2x text-primary"></i>
                    </div>
                    <h3 class="card-title h4">Device Responsive</h3>
                    <p class="card-text">Your surveys work perfectly on all devices - desktop, tablet, and smartphone.</p>
                    <div class="feature-hover-content">
                        <a href="#" class="btn btn-sm btn-primary rounded-pill">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Stats Counter Section -->
<div class="bg-light py-5 mt-5">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-3 col-6">
                <div class="stat-counter animate__animated" data-count="10000">
                    <div class="display-4 fw-bold text-primary counter">0</div>
                    <p class="text-muted mb-0">Users</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-counter animate__animated" data-count="50000">
                    <div class="display-4 fw-bold text-primary counter">0</div>
                    <p class="text-muted mb-0">Surveys Created</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-counter animate__animated" data-count="1000000">
                    <div class="display-4 fw-bold text-primary counter">0</div>
                    <p class="text-muted mb-0">Responses</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-counter animate__animated" data-count="99">
                    <div class="display-4 fw-bold text-primary counter">0</div>
                    <p class="text-muted mb-0">Satisfaction %</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features and Getting Started -->
<div class="container my-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="card-title mb-4 fw-bold">Premium Features</h2>
                    <div class="features-list">
                        <div class="feature-item d-flex align-items-center mb-3 animate__animated">
                            <div class="feature-icon me-3 bg-primary text-white rounded-circle">
                                <i class="fas fa-infinity"></i>
                            </div>
                            <div>Unlimited surveys and responses</div>
                        </div>
                        <div class="feature-item d-flex align-items-center mb-3 animate__animated">
                            <div class="feature-icon me-3 bg-primary text-white rounded-circle">
                                <i class="fas fa-list"></i>
                            </div>
                            <div>15+ question types including multiple choice, rating, and open-ended</div>
                        </div>
                        <div class="feature-item d-flex align-items-center mb-3 animate__animated">
                            <div class="feature-icon me-3 bg-primary text-white rounded-circle">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div>Advanced analytics with beautiful charts and reports</div>
                        </div>
                        <div class="feature-item d-flex align-items-center mb-3 animate__animated">
                            <div class="feature-icon me-3 bg-primary text-white rounded-circle">
                                <i class="fas fa-file-export"></i>
                            </div>
                            <div>Export to Excel, CSV, and PDF formats</div>
                        </div>
                        <div class="feature-item d-flex align-items-center animate__animated">
                            <div class="feature-icon me-3 bg-primary text-white rounded-circle">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>GDPR compliant data handling and security</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="card-title mb-4 fw-bold">Quick Start Guide</h2>
                    <div class="steps-list">
                        <div class="step-item d-flex mb-4 animate__animated">
                            <div class="step-number me-3">1</div>
                            <div>
                                <h5 class="mb-1">Create Your Account</h5>
                                <p class="text-muted mb-0">Sign up for free in just 30 seconds</p>
                            </div>
                        </div>
                        <div class="step-item d-flex mb-4 animate__animated">
                            <div class="step-number me-3">2</div>
                            <div>
                                <h5 class="mb-1">Choose a Template</h5>
                                <p class="text-muted mb-0">Select from our templates or start from scratch</p>
                            </div>
                        </div>
                        <div class="step-item d-flex mb-4 animate__animated">
                            <div class="step-number me-3">3</div>
                            <div>
                                <h5 class="mb-1">Customize Your Survey</h5>
                                <p class="text-muted mb-0">Add questions, change designs, set logic rules</p>
                            </div>
                        </div>
                        <div class="step-item d-flex mb-4 animate__animated">
                            <div class="step-number me-3">4</div>
                            <div>
                                <h5 class="mb-1">Share With Participants</h5>
                                <p class="text-muted mb-0">Distribute via email, link, or embed on your site</p>
                            </div>
                        </div>
                        <div class="step-item d-flex animate__animated">
                            <div class="step-number me-3">5</div>
                            <div>
                                <h5 class="mb-1">Analyze Results</h5>
                                <p class="text-muted mb-0">View real-time data and generate reports</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials -->
<div class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">What Our Users Say</h2>
            <p class="lead text-muted">Trusted by individuals and organizations worldwide</p>
        </div>
        
        <div class="testimonials-slider">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card testimonial-card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="testimonial-avatar me-3">
                                    <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="User" class="rounded-circle">
                                </div>
                                <div>
                                    <h5 class="mb-0">Sarah Johnson</h5>
                                    <p class="text-muted mb-0">Marketing Director</p>
                                </div>
                            </div>
                            <p class="testimonial-text">"This platform has completely transformed how we collect customer feedback. The analytics are incredible, and it's so easy to use!"</p>
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card testimonial-card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="testimonial-avatar me-3">
                                    <img src="https://randomuser.me/api/portraits/men/71.jpg" alt="User" class="rounded-circle">
                                </div>
                                <div>
                                    <h5 class="mb-0">David Chen</h5>
                                    <p class="text-muted mb-0">Researcher</p>
                                </div>
                            </div>
                            <p class="testimonial-text">"As an academic researcher, I needed a tool that was both powerful and user-friendly. This platform exceeds all my expectations."</p>
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card testimonial-card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="testimonial-avatar me-3">
                                    <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="User" class="rounded-circle">
                                </div>
                                <div>
                                    <h5 class="mb-0">Maria Rodriguez</h5>
                                    <p class="text-muted mb-0">Small Business Owner</p>
                                </div>
                            </div>
                            <p class="testimonial-text">"Setting up customer satisfaction surveys used to take days. Now I can create and launch one in less than an hour. Game changer!"</p>
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="container my-5">
    <div class="card border-0 bg-primary text-white shadow overflow-hidden">
        <div class="card-body p-5 position-relative">
            <div class="row align-items-center">
                <div class="col-lg-8 position-relative z-1">
                    <h2 class="display-5 fw-bold mb-3">Ready to Get Started?</h2>
                    <p class="lead mb-4">Join thousands of satisfied users and start creating surveys today.</p>
                    <?php if (!is_logged_in()): ?>
                        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-light btn-lg fw-semibold text-primary me-2">
                            <i class="fas fa-user-plus me-2"></i>Create Free Account
                        </a>
                        <a href="#" class="btn btn-outline-light btn-lg">Learn More</a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/dashboard/create-survey.php" class="btn btn-light btn-lg fw-semibold text-primary">
                            <i class="fas fa-plus me-2"></i>Create New Survey
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4 d-none d-lg-block">
                    <div class="cta-decoration">
                        <i class="fas fa-chart-pie fa-8x text-white opacity-10"></i>
                        <i class="fas fa-clipboard-list fa-6x text-white opacity-10"></i>
                        <i class="fas fa-check-circle fa-7x text-white opacity-10"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for the landing page -->
<style>
/* Hero Section Styles */
.hero-section {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    padding: 60px 0;
    margin-top: -1.5rem;
    position: relative;
    z-index: 1;
    overflow: hidden;
}

.animated-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTI4MCAxNDAiIHByZXNlcnZlQXNwZWN0UmF0aW89Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0iI2ZmZmZmZiI+PHBhdGggZD0iTTEyODAgMTQwVjBTOTkzLjQ2IDEzLjg4IDUwMC4wNCAxMDkuMjNTMCAwIDAgMHYxNDB6Ii8+PC9nPjwvc3ZnPg==') center bottom/100% 100px no-repeat, linear-gradient(135deg, rgba(78,115,223,0.8) 0%, rgba(34,74,190,0.9) 100%);
    z-index: -1;
    opacity: 0.1;
    animation: slide 15s linear infinite;
}

@keyframes slide {
    0% { background-position: 0 bottom; }
    100% { background-position: 1000px bottom; }
}

.floating-badge {
    bottom: 20px;
    right: -15px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Feature Cards */
.feature-card {
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.icon-wrapper {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background-color: rgba(78,115,223,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    transition: all 0.3s ease;
}

.feature-card:hover .icon-wrapper {
    background-color: #4e73df;
    color: white;
}

.feature-card:hover .icon-wrapper i {
    color: white !important;
}

.feature-hover-content {
    opacity: 0;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.feature-card:hover .feature-hover-content {
    opacity: 1;
    max-height: 50px;
    margin-top: 15px;
}

/* Stats Counter */
.stat-counter {
    padding: 20px;
    transition: all 0.3s ease;
}

.counter {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

/* Feature List */
.feature-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.feature-item {
    transition: all 0.3s ease;
    opacity: 0;
}

.feature-item.animate__fadeInRight {
    opacity: 1;
}

/* Steps List */
.steps-list {
    position: relative;
}

.steps-list::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 15px;
    width: 2px;
    background-color: #e9ecef;
    z-index: 0;
}

.step-number {
    width: 32px;
    height: 32px;
    background-color: #4e73df;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    position: relative;
    z-index: 1;
    flex-shrink: 0;
}

.step-item {
    transition: all 0.3s ease;
    opacity: 0;
}

.step-item.animate__fadeInRight {
    opacity: 1;
}

/* Testimonials */
.testimonial-card {
    border-radius: 12px;
    transition: all 0.3s ease;
}

.testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.testimonial-avatar img {
    width: 50px;
    height: 50px;
    object-fit: cover;
}

.testimonial-text {
    font-style: italic;
    position: relative;
    padding: 0 10px;
}

.testimonial-text::before,
.testimonial-text::after {
    content: '"';
    font-size: 1.5rem;
    color: #4e73df;
    font-weight: bold;
}

/* CTA Section */
.cta-decoration {
    position: absolute;
    right: -20px;
    bottom: -20px;
    display: flex;
    flex-direction: column;
    transform: rotate(-10deg);
}

.cta-decoration i {
    margin: -15px 0;
}

/* Reading Progress Bar */
.reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    background: #4e73df;
    z-index: 9999;
    transition: width 0.2s ease;
}

/* Opacity utility class */
.opacity-10 {
    opacity: 0.1;
}

/* FAQ Widget Styles */
.faq-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

.faq-widget-button {
    background-color: #4e73df;
    color: white;
    border: none;
    border-radius: 50px;
    padding: 10px 20px;
    font-weight: 600;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.faq-widget-button:hover {
    background-color: #224abe;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.25);
}

.faq-widget-button.active {
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.faq-widget-container {
    position: absolute;
    bottom: 65px;
    right: 0;
    width: 350px;
    max-height: 0;
    overflow: hidden;
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    opacity: 0;
    visibility: hidden;
}

.faq-widget-container.show {
    max-height: 500px;
    opacity: 1;
    visibility: visible;
}

.faq-widget-header {
    padding: 15px 20px;
    background-color: #4e73df;
    color: white;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}

.faq-widget-header h5 {
    margin: 0;
}

.faq-widget-body {
    padding: 10px;
    max-height: 350px;
    overflow-y: auto;
}

.faq-widget-footer {
    padding: 15px;
    text-align: center;
    border-top: 1px solid #eee;
}

.faq-widget-footer a {
    color: #4e73df;
    text-decoration: none;
    font-size: 0.9rem;
}

.faq-widget-footer a:hover {
    text-decoration: underline;
}

/* FAQ Items */
.faq-item {
    margin-bottom: 10px;
    border: 1px solid #eee;
    border-radius: 6px;
    overflow: hidden;
}

.faq-toggle {
    width: 100%;
    background-color: #f8f9fa;
    border: none;
    text-align: left;
    padding: 12px 15px;
    cursor: pointer;
    position: relative;
    font-weight: 600;
    transition: all 0.3s ease;
}

.faq-toggle:hover {
    background-color: #f1f3f9;
}

.faq-toggle::after {
    content: '\f107';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    right: 15px;
    transition: all 0.3s ease;
}

.faq-toggle.active {
    background-color: #e8eeff;
}

.faq-toggle.active::after {
    transform: rotate(180deg);
}

.faq-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background-color: white;
}

.faq-content p {
    padding: 15px;
    margin: 0;
    font-size: 0.9rem;
}
</style>

<!-- JavaScript for interactivity -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate stats counters when they come into view
    const counters = document.querySelectorAll('.counter');
    const options = {
        threshold: 0.5
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const parent = counter.closest('.stat-counter');
                const target = parseInt(parent.dataset.count);
                let count = 0;
                const duration = 2000; // 2 seconds
                const increment = Math.ceil(target / (duration / 30)); // Update every 30ms
                
                parent.classList.add('animate__animated', 'animate__fadeInUp');
                
                const updateCount = () => {
                    if (count < target) {
                        count += increment;
                        if (count > target) count = target;
                        counter.textContent = count.toLocaleString();
                        requestAnimationFrame(updateCount);
                    }
                };
                
                updateCount();
                observer.unobserve(counter);
            }
        });
    }, options);
    
    counters.forEach(counter => {
        observer.observe(counter);
    });
    
    // Animate feature items when they come into view
    const featureItems = document.querySelectorAll('.feature-item');
    const featureObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('animate__fadeInRight');
                }, index * 200); // Stagger the animations
                featureObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    featureItems.forEach(item => {
        featureObserver.observe(item);
    });
    
    // Animate step items when they come into view
    const stepItems = document.querySelectorAll('.step-item');
    const stepObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('animate__fadeInRight');
                }, index * 200); // Stagger the animations
                stepObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    stepItems.forEach(item => {
        stepObserver.observe(item);
    });
    
    // Update progress bar as user scrolls down the page
    const progressBar = document.createElement('div');
    progressBar.className = 'reading-progress';
    document.body.appendChild(progressBar);
    
    window.addEventListener('scroll', () => {
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const progress = (scrollTop / scrollHeight) * 100;
        progressBar.style.width = progress + '%';
    });

    // Initialize the FAQ accordion
    const faqToggles = document.querySelectorAll('.faq-toggle');
    faqToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            // Toggle the active class on the button
            toggle.classList.toggle('active');
            
            // Get the target content
            const content = toggle.nextElementSibling;
            
            // Toggle the max-height to show/hide the content
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
            }
        });
    });

    // Toggle the FAQ widget
    const faqButton = document.querySelector('.faq-widget-button');
    const faqContainer = document.querySelector('.faq-widget-container');
    if (faqButton && faqContainer) {
        faqButton.addEventListener('click', () => {
            faqContainer.classList.toggle('show');
            faqButton.classList.toggle('active');
            
            if (faqButton.classList.contains('active')) {
                faqButton.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                faqButton.innerHTML = '<i class="fas fa-question"></i> FAQ';
            }
        });
    }
});
</script>

<!-- Floating FAQ Widget -->
<div class="faq-widget">
    <button class="faq-widget-button"><i class="fas fa-question"></i> FAQ</button>
    <div class="faq-widget-container">
        <div class="faq-widget-header">
            <h5>Frequently Asked Questions</h5>
        </div>
        <div class="faq-widget-body">
            <div class="faq-item">
                <button class="faq-toggle">How do I create a survey?</button>
                <div class="faq-content">
                    <p>After logging in, click on "Create Survey" from the dashboard. You can start from scratch or use one of our pre-made templates.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-toggle">Is there a free plan available?</button>
                <div class="faq-content">
                    <p>Yes! Our free plan lets you create unlimited surveys with up to 100 responses per month. For more responses and advanced features, check out our premium plans.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-toggle">How do I share my survey?</button>
                <div class="faq-content">
                    <p>You can share your survey via a direct link, email, embed it on your website, or share on social media directly from your dashboard.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-toggle">Can I export my survey data?</button>
                <div class="faq-content">
                    <p>Yes, you can export your survey results to CSV, Excel, or PDF formats from the Survey Analytics section.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-toggle">How secure is my data?</button>
                <div class="faq-content">
                    <p>We take security seriously. All data is encrypted using industry-standard protocols, and we are fully GDPR compliant. Your data is never shared with third parties.</p>
                </div>
            </div>
        </div>
        <div class="faq-widget-footer">
            <a href="<?php echo SITE_URL; ?>/contact.php">Still have questions? Contact us</a>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
