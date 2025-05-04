</div><!-- /.container -->

    <footer class="footer mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p class="text-muted">Create professional surveys and gather valuable feedback in minutes.</p>
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
                
                <div class="col-md-2 mb-4 mb-md-0">
                    <h6>Surveys</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/dashboard/create-survey.php">Create Survey</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard/templates.php">Templates</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard/view-surveys.php">My Surveys</a></li>
                    </ul>
                </div>
                
                <div class="col-md-2 mb-4 mb-md-0">
                    <h6>Account</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/profile.php">Profile</a></li>
                        <?php if (is_logged_in()): ?>
                            <li><a href="<?php echo SITE_URL; ?>/auth/logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>/auth/login.php">Login</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/auth/register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-md-4">
                    <h6>About</h6>
                    <p class="text-muted">Our survey system helps you create and distribute surveys online, collecting valuable feedback from your audience.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/enhanced-ui.js"></script>
</body>
</html>
