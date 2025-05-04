            </main>
            
            <!-- Footer -->
            <footer class="admin-footer">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>
                        </div>
                        <div>
                            <a href="<?php echo SITE_URL; ?>/" class="text-muted">Back to Site</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- Admin JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileToggle = document.getElementById('mobileToggle');
            const adminWrapper = document.querySelector('.admin-wrapper');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    adminWrapper.classList.toggle('sidebar-collapsed');
                });
            }
            
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    adminWrapper.classList.toggle('sidebar-mobile-visible');
                });
            }
            
            // Set active nav item based on current path
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.sidebar-link');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href.split('/').pop())) {
                    link.classList.add('active');
                    link.closest('.sidebar-item').classList.add('active');
                }
            });
        });
    </script>
</body>
</html> 