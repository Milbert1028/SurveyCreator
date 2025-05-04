            </main><!-- /.main-content -->
            
            <footer class="admin-footer">
                <div class="footer-left">
                    &copy; <?php echo date('Y'); ?> Survey System Admin Panel
                </div>
                <div class="footer-right">
                    <span>Version 1.0</span>
                </div>
            </footer>
        </div><!-- /.admin-content -->
    </div><!-- /.admin-container -->
    
    <!-- Bootstrap 5 and other JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar_collapsed', document.querySelector('.admin-container').classList.contains('sidebar-collapsed'));
            });
        }
        
        // Load sidebar state from local storage
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.querySelector('.admin-container').classList.add('sidebar-collapsed');
        }
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
    </script>
    
    <!-- Page-specific JS if needed -->
    <?php if (isset($page_specific_js)): ?>
    <script src="<?php echo $page_specific_js; ?>"></script>
    <?php endif; ?>
    
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html> 